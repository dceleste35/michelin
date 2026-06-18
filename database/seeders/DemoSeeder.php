<?php

namespace Database\Seeders;

use App\Models\StravaActivity;
use App\Models\User;
use App\Services\ProfileInferenceService;
use Carbon\CarbonImmutable;
use Database\Factories\StravaActivityFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * État de départ de la démo : Marc, PREMIER ARRIVANT. Connecté à Strava avec ~16 sorties
 * GravelRide déjà importées (de quoi inférer le profil et remplir la liste d'activités),
 * mais AUCUN pneu et profil non confirmé. À partir de là, les commandes `demo:tires` et
 * `demo:wear` font avancer le scénario. Entièrement déterministe.
 */
class DemoSeeder extends Seeder
{
    private const ATHLETE_ID = 42;

    private const ACTIVITY_COUNT = 16;

    private const ACTIVITY_BASE_ID = 15_000_000_000;

    private const GEAR_ID = 'b9100042';

    public function run(): void
    {
        $marc = User::updateOrCreate(
            ['email' => 'marc@rideready.test'],
            [
                'name' => 'Marc',
                'password' => Hash::make('password'),
                'strava_athlete_id' => (string) self::ATHLETE_ID,
                'segment_overridden' => false,
                // Premier arrivant : profil non confirmé (l'onboarding l'inférera), rien de figé.
                'profile_confirmed_at' => null,
                'weight_kg' => null,
                'segment' => null,
                'riding_style' => null,
            ],
        );

        // Point de départ : collection de pneus vide.
        $marc->tires()->delete();

        $this->seedActivities($marc);
    }

    /**
     * Importe des sorties GravelRide récentes, sans pneu assigné (tout est « à vérifier »).
     */
    private function seedActivities(User $marc): void
    {
        $now = CarbonImmutable::now();
        $inference = app(ProfileInferenceService::class);

        for ($i = 0; $i < self::ACTIVITY_COUNT; $i++) {
            // Mêmes signaux de grimpe que la persona : ~60 % asphalte / 20 % damé / 20 % mixte (dérivé).
            $elevationPerKm = match ($i % 5) {
                0, 1, 2 => 5,
                3 => 11,
                default => 18,
            };

            $distanceKm = $i % 6 === 0 ? 120 : 32 + ($i * 7 % 38);
            $distanceM = $distanceKm * 1000;
            $avgSpeedMs = 7.2; // ~26 km/h
            $externalId = (string) (self::ACTIVITY_BASE_ID + $i);

            $attributes = [
                'external_id' => $externalId,
                'gear_id' => self::GEAR_ID,
                'sport_type' => 'GravelRide',
                'distance_m' => $distanceM,
                'moving_time_s' => (int) round($distanceM / $avgSpeedMs),
                'average_speed_ms' => $avgSpeedMs,
                'total_elevation_gain_m' => $distanceKm * $elevationPerKm,
                'average_watts' => 178,
                'average_cadence' => 84,
                'start_date' => $now->subDays((int) round($i * 60 / self::ACTIVITY_COUNT))->setTime(7, 30),
                // Aucun pneu encore : tout est à vérifier.
                'front_tire_id' => null,
                'rear_tire_id' => null,
                'tires_confirmed' => false,
            ];

            $attributes['surface_derived'] = $inference->deriveSurface(new StravaActivity($attributes));
            $attributes['raw_json'] = StravaActivityFactory::stravaPayload($attributes, self::ATHLETE_ID);

            StravaActivity::updateOrCreate(
                ['user_id' => $marc->id, 'external_id' => $externalId],
                $attributes,
            );
        }
    }
}
