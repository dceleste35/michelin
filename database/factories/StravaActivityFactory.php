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
     * Define the model's default state (a realistic gravel ride).
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
            'external_id' => (string) fake()->unique()->numberBetween(1_000_000, 9_999_999),
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

        // raw_json mirrors the final attributes in Strava API shape (closure so it
        // reflects any state overrides applied to the columns above).
        $attributes['raw_json'] = fn (array $attrs): array => self::stravaPayload(
            $attrs,
            fake()->numberBetween(1, 99_999),
        );

        return $attributes;
    }

    /**
     * Build a Strava-API-shaped payload from the model attributes.
     *
     * @param  array<string, mixed>  $attrs
     * @return array<string, mixed>
     */
    public static function stravaPayload(array $attrs, int $athleteId): array
    {
        $surface = $attrs['surface_derived'];

        return [
            'id' => $attrs['external_id'],
            'athlete' => ['id' => $athleteId],
            'sport_type' => $attrs['sport_type'],
            'distance' => (int) $attrs['distance_m'],
            'moving_time' => (int) $attrs['moving_time_s'],
            'average_speed' => (float) $attrs['average_speed_ms'],
            'total_elevation_gain' => (int) $attrs['total_elevation_gain_m'],
            'average_watts' => $attrs['average_watts'] !== null ? (int) $attrs['average_watts'] : null,
            'average_cadence' => $attrs['average_cadence'] !== null ? (int) $attrs['average_cadence'] : null,
            'start_date' => CarbonImmutable::parse($attrs['start_date'])->toIso8601ZuluString(),
            '_derived' => [
                'surface' => $surface instanceof Surface ? $surface->value : $surface,
            ],
        ];
    }
}
