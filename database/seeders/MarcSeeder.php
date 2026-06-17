<?php

namespace Database\Seeders;

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use App\Services\ProfileInferenceService;
use Carbon\CarbonImmutable;
use Database\Factories\StravaActivityFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Persona vedette : Marc (GRAVEL). ~80 activités GravelRide sur 6 mois, 60/40
 * asphalte/tout-terrain, roulant sur un Power Gravel usé (arrière 86 %). Entièrement
 * déterministe afin que `demo:reset` reproduise exactement l'état de la démo.
 * Nécessite ProductCatalogSeeder.
 */
class MarcSeeder extends Seeder
{
    private const ACTIVITY_COUNT = 80;

    private const ATHLETE_ID = 42;

    private const ACTIVITY_BASE_ID = 15_000_000_000;

    private const GEAR_ID = 'b9100042'; // L'unique vélo gravel de Marc (gear id Strava)

    public function run(): void
    {
        $marc = User::updateOrCreate(
            ['email' => 'marc@rideready.test'],
            [
                'name' => 'Marc',
                'password' => Hash::make('password'),
                'strava_athlete_id' => (string) self::ATHLETE_ID,
                'weight_kg' => 90,
                'segment' => Segment::Gravel,
                'riding_style' => RidingStyle::Endurance,
                'segment_overridden' => false,
            ],
        );

        $this->seedActivities($marc);
        $this->mountPowerGravel($marc);
    }

    private function seedActivities(User $marc): void
    {
        $now = CarbonImmutable::now();
        $inference = app(ProfileInferenceService::class);

        for ($i = 0; $i < self::ACTIVITY_COUNT; $i++) {
            // Profil de grimpe réaliste par sortie (m/km). À l'allure régulière de 26 km/h de Marc, ces
            // signaux SE DÉRIVENT en ~60 % asphalte / 20 % chemin damé / 20 % mixte — la surface
            // est calculée par deriveSurface(), jamais codée en dur (crédibilité auprès du jury).
            $elevationPerKm = match ($i % 5) {
                0, 1, 2 => 5,   // plat & rapide → asphalte (gravel à allure route)
                3 => 11,        // vallonné → chemin damé
                default => 18,  // accidenté → mixte
            };

            // Une longue sortie (~150 km) toutes les 12 sorties, sinon 35–75 km.
            $distanceKm = $i % 12 === 0 ? 150 : 35 + ($i * 7 % 41);
            $distanceM = $distanceKm * 1000;
            $avgSpeedMs = 7.2; // ~26 km/h, allure d'endurance régulière
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
                'start_date' => $now->subDays((int) round($i * 180 / self::ACTIVITY_COUNT))->setTime(7, 30),
            ];

            // La surface est DÉRIVÉE des signaux de la sortie via les règles documentées.
            $attributes['surface_derived'] = $inference->deriveSurface(new StravaActivity($attributes));
            $attributes['raw_json'] = StravaActivityFactory::stravaPayload($attributes, self::ATHLETE_ID);

            StravaActivity::updateOrCreate(
                ['user_id' => $marc->id, 'external_id' => $externalId],
                $attributes,
            );
        }
    }

    private function mountPowerGravel(User $marc): void
    {
        $powerGravel = Product::where('web_range_name', 'Power Gravel')->sole();
        $mountedAt = CarbonImmutable::now()->subDays(180)->toDateString();

        // L'arrière s'use plus vite → 86 % (déclenche l'alerte de la démo) ; l'avant suit à 72 %.
        foreach ([[TirePosition::Rear, 86.0], [TirePosition::Front, 72.0]] as [$position, $wear]) {
            UserTire::updateOrCreate(
                ['user_id' => $marc->id, 'product_id' => $powerGravel->id, 'position' => $position],
                [
                    'mounted_at' => $mountedAt,
                    'mounted_odometer_km' => 1200,
                    'wear_percent' => $wear,
                    'is_active' => true,
                ],
            );
        }
    }
}
