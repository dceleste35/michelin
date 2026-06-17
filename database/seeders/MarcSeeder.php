<?php

namespace Database\Seeders;

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use Carbon\CarbonImmutable;
use Database\Factories\StravaActivityFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Hero persona: Marc (GRAVEL). ~80 GravelRide activities over 6 months, 60/40
 * asphalt/off-road, riding a worn Power Gravel (rear 86 %). Fully deterministic
 * so `demo:reset` reproduces the exact demo state. Requires ProductCatalogSeeder.
 */
class MarcSeeder extends Seeder
{
    private const ACTIVITY_COUNT = 80;

    private const ATHLETE_ID = 42;

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

        for ($i = 0; $i < self::ACTIVITY_COUNT; $i++) {
            // 60 % asphalt / 40 % off-road (hard-packed + mixed) — gravel rider.
            $surface = match ($i % 5) {
                0, 1, 2 => Surface::Asphalt,
                3 => Surface::Hardpacked,
                default => Surface::Mixed,
            };

            // A long ride (~150 km) every 12th outing, otherwise 35–75 km.
            $distanceKm = $i % 12 === 0 ? 150 : 35 + ($i * 7 % 41);
            $distanceM = $distanceKm * 1000;
            $avgSpeedMs = 7.2; // ~26 km/h, steady endurance pace
            $externalId = 'marc-'.($i + 1);

            $attributes = [
                'external_id' => $externalId,
                'sport_type' => 'GravelRide',
                'distance_m' => $distanceM,
                'moving_time_s' => (int) round($distanceM / $avgSpeedMs),
                'average_speed_ms' => $avgSpeedMs,
                'total_elevation_gain_m' => $distanceKm * ($surface === Surface::Asphalt ? 8 : 14),
                'average_watts' => 178,
                'average_cadence' => 84,
                'surface_derived' => $surface,
                'start_date' => $now->subDays((int) round($i * 180 / self::ACTIVITY_COUNT))->setTime(7, 30),
            ];
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

        // Rear wears faster → 86 % (triggers the demo alert); front trails at 72 %.
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
