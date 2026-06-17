<?php

namespace Database\Factories;

use App\Enums\Surface;
use App\Models\StravaActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StravaActivity>
 */
class StravaActivityFactory extends Factory
{
    /**
     * Définit l'état par défaut du modèle (une sortie gravel réaliste).
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $distanceM = fake()->numberBetween(20_000, 160_000);
        $avgSpeedMs = fake()->randomFloat(2, 6.0, 8.5);
        $surface = fake()->randomElement(Surface::cases());

        $attributes = [
            'user_id' => User::factory(),
            // Les identifiants d'activité Strava sont des entiers 64 bits (~11 chiffres aujourd'hui).
            'external_id' => (string) (15_000_000_000 + fake()->unique()->numberBetween(1, 1_000_000_000)),
            'gear_id' => 'b'.fake()->numberBetween(1_000_000, 9_999_999),
            'sport_type' => 'GravelRide',
            'distance_m' => $distanceM,
            'moving_time_s' => (int) round($distanceM / $avgSpeedMs),
            'average_speed_ms' => $avgSpeedMs,
            'total_elevation_gain_m' => (int) round($distanceM / 1000 * fake()->numberBetween(6, 18)),
            'average_watts' => fake()->numberBetween(150, 220),
            'average_cadence' => fake()->numberBetween(78, 92),
            'surface_derived' => $surface,
            'start_date' => CarbonImmutable::now()->subDays(fake()->numberBetween(0, 180))->setTime(7, 30),
        ];

        // raw_json reflète les attributs finaux au format de l'API Strava (closure afin
        // de prendre en compte toute surcharge d'état appliquée aux colonnes ci-dessus).
        $attributes['raw_json'] = fn (array $attrs): array => self::stravaPayload(
            $attrs,
            fake()->numberBetween(1, 99_999),
        );

        return $attributes;
    }

    /**
     * Construit un payload Strava DetailedActivity fidèle à partir des attributs du modèle.
     *
     * Les noms de champs, unités et types reproduisent l'API publique Strava
     * (developers.strava.com) : distance/dénivelé en mètres, durées en secondes,
     * vitesses en m/s, watts en watts, cadence en tr/min, dates en ISO-8601. La seule
     * clé non-Strava est `_derived`, qui regroupe les valeurs que nous calculons nous-mêmes
     * (Strava n'expose aucune surface par activité). Certains champs illustratifs (polyline
     * de carte, fréquence cardiaque, compteur kilométrique du vélo) sont dérivés de manière
     * déterministe afin que le mock reste reproductible tout en ressemblant à une vraie réponse.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    public static function stravaPayload(array $attrs, int $athleteId): array
    {
        $surface = $attrs['surface_derived'];
        $surfaceValue = $surface instanceof Surface ? $surface->value : $surface;

        $activityId = (int) $attrs['external_id'];
        $start = CarbonImmutable::parse($attrs['start_date']);
        $distanceM = (int) $attrs['distance_m'];
        $movingTime = (int) $attrs['moving_time_s'];
        $avgSpeed = (float) $attrs['average_speed_ms'];
        $elevGain = (int) $attrs['total_elevation_gain_m'];
        $avgWatts = $attrs['average_watts'] !== null ? (int) $attrs['average_watts'] : null;
        $avgCadence = $attrs['average_cadence'] !== null ? (int) $attrs['average_cadence'] : null;
        $gearId = isset($attrs['gear_id']) && is_string($attrs['gear_id']) ? $attrs['gear_id'] : null;

        $hasPower = $avgWatts !== null;
        $elapsedTime = (int) round($movingTime * 1.12); // ~12 % de marge pour les pauses café
        $hour = (int) $start->format('G');
        $dayPart = match (true) {
            $hour < 12 => 'Morning',
            $hour < 17 => 'Afternoon',
            $hour < 21 => 'Evening',
            default => 'Night',
        };
        $avgHr = $avgWatts !== null ? min(186.0, round($avgWatts * 0.78, 1)) : null;
        $rideLabel = $attrs['sport_type'] === 'GravelRide' ? 'Gravel Ride' : 'Ride';

        return [
            'resource_state' => 3,
            'id' => $activityId,
            'external_id' => 'garmin_push_'.$activityId.'.fit',
            'upload_id' => $activityId + 1,
            'name' => $dayPart.' '.$rideLabel,
            'distance' => (float) $distanceM,
            'moving_time' => $movingTime,
            'elapsed_time' => $elapsedTime,
            'total_elevation_gain' => (float) $elevGain,
            'elev_high' => round(80 + $elevGain * 0.35, 1),
            'elev_low' => 80.0,
            'sport_type' => $attrs['sport_type'],
            'type' => 'Ride',
            'start_date' => $start->utc()->format('Y-m-d\TH:i:s\Z'),
            'start_date_local' => $start->setTimezone('Europe/Paris')->format('Y-m-d\TH:i:s\Z'),
            'timezone' => '(GMT+01:00) Europe/Paris',
            'utc_offset' => 3600.0,
            'average_speed' => round($avgSpeed, 3),
            'max_speed' => round($avgSpeed * 1.7, 3),
            'average_cadence' => $avgCadence !== null ? (float) $avgCadence : null,
            'average_watts' => $avgWatts !== null ? (float) $avgWatts : null,
            'weighted_average_watts' => $avgWatts !== null ? (int) round($avgWatts * 1.06) : null,
            'kilojoules' => $avgWatts !== null ? round($avgWatts * $movingTime / 1000, 1) : null,
            'device_watts' => $hasPower,
            'has_heartrate' => $hasPower,
            'average_heartrate' => $avgHr,
            'max_heartrate' => $avgHr !== null ? round($avgHr * 1.18, 1) : null,
            'athlete' => ['id' => $athleteId, 'resource_state' => 1],
            'gear_id' => $gearId,
            'gear' => $gearId !== null ? self::gearPayload($gearId) : null,
            'map' => [
                'id' => 'a'.$activityId,
                'summary_polyline' => self::fakePolyline(),
                'resource_state' => 2,
            ],
            'trainer' => false,
            'commute' => false,
            'manual' => false,
            'private' => false,
            'visibility' => 'everyone',
            'flagged' => false,
            'achievement_count' => 0,
            'kudos_count' => $distanceM % 37,
            'comment_count' => $distanceM % 5,
            'athlete_count' => 1 + ($distanceM % 4),
            'pr_count' => $distanceM % 3,
            '_derived' => [
                'surface' => $surfaceValue,
            ],
        ];
    }

    /**
     * Construit un payload Strava SummaryGear (vélo). Strava expose le vélo via
     * `gear_id`/`gear` et sa `distance` totale (compteur kilométrique) — mais aucune
     * donnée par pneu ou par composant. L'usure des pneus est donc attribuée dans notre
     * propre domaine (UserTire) à partir de ce compteur. Le compteur est dérivé de manière
     * déterministe à partir du gear id afin de rester stable d'une activité à l'autre.
     *
     * @return array<string, mixed>
     */
    private static function gearPayload(string $gearId): array
    {
        $digits = (int) (preg_replace('/\D/', '', $gearId) ?? '');
        $odometerKm = 2_500 + ($digits % 6_000); // 2 500–8 499 km au total

        return [
            'id' => $gearId,
            'primary' => true,
            'name' => 'Gravel Bike',
            'nickname' => 'Gravel Bike',
            'resource_state' => 2,
            'retired' => false,
            'distance' => (float) ($odometerKm * 1_000),
            'brand_name' => 'Canyon',
            'model_name' => 'Grizl',
            'frame_type' => 2,
            'description' => null,
        ];
    }

    /**
     * Une polyline encodée au format Google à titre d'illustration (pas une vraie trace GPS).
     */
    private static function fakePolyline(): string
    {
        return '}_~iF~ps|U_ulLnnqC_mqNvxq`@';
    }
}
