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
 * Hero persona: Marc (GRAVEL). ~80 GravelRide activities over 6 months, 60/40
 * asphalt/off-road, riding a worn Power Gravel (rear 86 %). Fully deterministic
 * so `demo:reset` reproduces the exact demo state. Requires ProductCatalogSeeder.
 */
class MarcSeeder extends Seeder
{
    private const ACTIVITY_COUNT = 80;

    private const ATHLETE_ID = 42;

    private const ACTIVITY_BASE_ID = 15_000_000_000;

    private const GEAR_ID = 'b9100042'; // Marc's single gravel bike (Strava gear id)

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
            // Realistic climbing profile per ride (m/km). At Marc's steady 26 km/h these
            // signals DERIVE to ~60 % asphalt / 20 % hard-packed / 20 % mixed — the surface
            // is computed by deriveSurface(), never hard-coded (jury credibility).
            $elevationPerKm = match ($i % 5) {
                0, 1, 2 => 5,   // flat & fast → asphalt (road-pace gravel)
                3 => 11,        // rolling → hard-packed
                default => 18,  // hilly → mixed
            };

            // A long ride (~150 km) every 12th outing, otherwise 35–75 km.
            $distanceKm = $i % 12 === 0 ? 150 : 35 + ($i * 7 % 41);
            $distanceM = $distanceKm * 1000;
            $avgSpeedMs = 7.2; // ~26 km/h, steady endurance pace
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

            // Surface is DERIVED from the ride signals via the documented rules.
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
