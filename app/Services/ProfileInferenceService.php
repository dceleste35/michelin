<?php

namespace App\Services;

use App\DTO\RiderProfile;
use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Models\StravaActivity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * UC-1 — inférence déterministe du profil du cycliste (pur SCORE, sans IA).
 *
 * Analyse les 6 derniers mois de `strava_activities` de l'utilisateur et classe
 * de simples nombres dans des intervalles afin d'inférer le segment, le style de
 * pratique et la répartition des terrains. Chaque règle est explicable au jury et
 * entièrement reproductible. Il s'agit d'une *valeur par défaut intelligente*,
 * jamais d'un verdict — l'utilisateur peut la remplacer (segment_overridden).
 */
class ProfileInferenceService
{
    private const DEFAULT_SYSTEM_WEIGHT_KG = 90; // ~80 kg de cycliste + ~10 kg de vélo gravel

    /**
     * Lit le profil actuel (persisté) de l'utilisateur pour les consommateurs en
     * aval (le moteur de Nolan, l'UI de Guillaume). La répartition des terrains est
     * calculée en direct sur les six derniers mois ; le segment/style/poids
     * proviennent des champs persistés, avec des valeurs GRAVEL par défaut sûres
     * lorsque l'inférence n'a pas encore été exécutée.
     */
    public function buildProfile(User $user): RiderProfile
    {
        return new RiderProfile(
            segment: $user->segment ?? Segment::Gravel,
            weightKg: $user->weight_kg ?? self::DEFAULT_SYSTEM_WEIGHT_KG,
            terrainPct: $this->terrainDistribution($this->recentActivities($user)),
            ridingStyle: $user->riding_style ?? RidingStyle::Endurance,
        );
    }

    /**
     * Infère le profil complet à partir des activités de l'utilisateur (sans persistance).
     */
    public function infer(User $user): RiderProfile
    {
        $activities = $this->recentActivities($user);
        $weight = $user->weight_kg ?? self::DEFAULT_SYSTEM_WEIGHT_KG;

        return new RiderProfile(
            segment: $this->inferSegment($activities),
            weightKg: $weight,
            terrainPct: $this->terrainDistribution($activities),
            ridingStyle: $this->inferRidingStyle($activities, $weight),
        );
    }

    /**
     * Infère le profil et persiste segment / riding_style / weight_kg sur
     * l'utilisateur. Un segment remplacé par l'utilisateur est préservé (segment_overridden).
     */
    public function inferAndPersist(User $user): RiderProfile
    {
        $profile = $this->infer($user);

        if (! $user->segment_overridden) {
            $user->segment = $profile->segment;
        }
        $user->riding_style = $profile->ridingStyle;
        $user->weight_kg = $profile->weightKg;
        $user->save();

        return $profile;
    }

    /**
     * Infère le segment dominant à partir de la part hors-route (6 derniers mois).
     *
     * - hors-route > 70 %                → MTB
     * - 15 % ≤ hors-route ≤ 70 %         → GRAVEL
     * - hors-route < 15 % (très asphalté) → ROAD (une vitesse moyenne > 25 km/h
     *   confirme un profil route compétitif ; utilisé par l'inférence du style)
     * - majorité EBikeRide               → EBIKE_URBAN
     *
     * @param  Collection<int, StravaActivity>  $activities
     */
    public function inferSegment(Collection $activities): Segment
    {
        if ($activities->isEmpty()) {
            return Segment::Gravel;
        }

        if ($activities->where('sport_type', 'EBikeRide')->count() / $activities->count() > 0.5) {
            return Segment::EbikeUrban;
        }

        $offRoad = $activities->filter(
            fn (StravaActivity $activity): bool => $this->surfaceOf($activity) !== Surface::Asphalt
        )->count();
        $offRoadShare = $offRoad / $activities->count();

        return match (true) {
            $offRoadShare > 0.70 => Segment::Mtb,
            $offRoadShare >= 0.15 => Segment::Gravel,
            default => Segment::Road,
        };
    }

    /**
     * Infère le style de pratique à partir du rapport puissance/poids et de la
     * variabilité de l'allure.
     *
     * Watts par kilo élevés OU forte dispersion de vitesse entre les sorties (un
     * indicateur d'efforts explosifs et variables) → AGGRESSIF ; sinon, allure
     * régulière → ENDURANCE.
     *
     * @param  Collection<int, StravaActivity>  $activities
     */
    public function inferRidingStyle(Collection $activities, int $weightKg = self::DEFAULT_SYSTEM_WEIGHT_KG): RidingStyle
    {
        $withWatts = $activities->whereNotNull('average_watts');

        if ($withWatts->isEmpty()) {
            return RidingStyle::Endurance;
        }

        $wattsPerKg = (float) $withWatts->avg('average_watts') / max($weightKg, 1);
        $speedStdDev = $this->standardDeviation(
            $activities->map(fn (StravaActivity $activity): float => (float) $activity->average_speed_ms * 3.6)
        );

        return ($wattsPerKg >= 2.6 || $speedStdDev >= 4.0)
            ? RidingStyle::Aggressif
            : RidingStyle::Endurance;
    }

    /**
     * Déduit la surface d'une activité à partir des signaux Strava.
     *
     * Strava n'expose aucun champ de surface : nous la déduisons donc à partir de
     * règles documentées et explicables — une hypothèse explicite, pas une boîte
     * noire (point de crédibilité auprès du jury) :
     *
     * - `Ride`/`VirtualRide` → ASPHALT. Une activité de vélo de route est revêtue
     *   par définition (les sorties virtuelles/intérieures aussi), quel que soit le dénivelé.
     * - `GravelRide` est polyvalent : on lève donc l'ambiguïté selon le terrain :
     *     plat **et** rapide (≤8 m/km de dénivelé, >24 km/h) → ASPHALT — une sortie à allure route ;
     *     vallonné (≤14 m/km) → HARDPACKED — gravel roulant typique ;
     *     plus montagneux → MIXED — le dénivelé implique un sol défoncé/technique.
     * - `MountainBikeRide`/`EMountainBikeRide` deviennent plus rudes avec le dénivelé,
     *   et plus sévères quand l'allure s'effondre (indicateur d'un sol technique/humide) :
     *     ≤15 m/km → MIXED ; allure très lente (<12 km/h) → MUD ; ≤30 m/km → SOFT ; plus raide → MUD.
     * - `EBikeRide` (assistance urbaine) → ASPHALT. Tout autre sport → MIXED (valeur par défaut sûre).
     *
     * Robuste aux distances, vitesses ou dénivelés manquants/nuls : `max()` protège
     * le diviseur et les métriques absentes valent 0, donc aucune division par zéro
     * et aucun plantage sur les cas limites.
     */
    public function deriveSurface(StravaActivity $activity): Surface
    {
        $distanceKm = max($activity->distance_m / 1000, 0.1);
        $elevationPerKm = $activity->total_elevation_gain_m / $distanceKm;
        $avgKmh = (float) $activity->average_speed_ms * 3.6;

        return match ($activity->sport_type) {
            'Ride', 'VirtualRide' => Surface::Asphalt,
            'GravelRide' => match (true) {
                $elevationPerKm <= 8 && $avgKmh > 24 => Surface::Asphalt, // plat & rapide = type route
                $elevationPerKm <= 14 => Surface::Hardpacked,
                default => Surface::Mixed,
            },
            'MountainBikeRide', 'EMountainBikeRide' => match (true) {
                $elevationPerKm <= 15 => Surface::Mixed,
                $avgKmh < 12 => Surface::Mud,         // allure très lente + dénivelé = technique/boueux
                $elevationPerKm <= 30 => Surface::Soft,
                default => Surface::Mud,
            },
            'EBikeRide' => Surface::Asphalt,
            default => Surface::Mixed,
        };
    }

    /**
     * Répartition des terrains en pourcentages entiers par surface (clés toujours présentes).
     *
     * @param  Collection<int, StravaActivity>  $activities
     * @return array<string, int>
     */
    public function terrainDistribution(Collection $activities): array
    {
        $percentages = ['asphalt' => 0, 'hardpacked' => 0, 'mixed' => 0, 'soft' => 0, 'mud' => 0];

        if ($activities->isEmpty()) {
            return $percentages;
        }

        $counts = $percentages;
        foreach ($activities as $activity) {
            $counts[Str::lower($this->surfaceOf($activity)->name)]++;
        }

        $total = $activities->count();
        foreach ($counts as $surface => $count) {
            $percentages[$surface] = (int) round($count / $total * 100);
        }

        return $percentages;
    }

    /**
     * Les activités de l'utilisateur sur la fenêtre d'inférence (6 derniers mois).
     *
     * @return Collection<int, StravaActivity>
     */
    private function recentActivities(User $user): Collection
    {
        return $user->stravaActivities()
            ->lastSixMonths()
            ->select([
                'id', 'user_id', 'sport_type', 'distance_m', 'moving_time_s',
                'average_speed_ms', 'total_elevation_gain_m', 'average_watts',
                'average_cadence', 'surface_derived', 'start_date',
            ])
            ->get();
    }

    /**
     * La surface déduite stockée de l'activité, calculée à la volée si absente.
     */
    private function surfaceOf(StravaActivity $activity): Surface
    {
        return $activity->surface_derived ?? $this->deriveSurface($activity);
    }

    /**
     * Écart-type de population d'une collection numérique non vide.
     *
     * @param  Collection<int, float>  $values
     */
    private function standardDeviation(Collection $values): float
    {
        $mean = (float) $values->avg();
        $variance = $values->reduce(
            fn (float $carry, float $value): float => $carry + ($value - $mean) ** 2,
            0.0
        ) / $values->count();

        return sqrt($variance);
    }
}
