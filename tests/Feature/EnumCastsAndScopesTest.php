<?php

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use Carbon\CarbonInterface;

function makeProductForCasts(): Product
{
    return Product::create([
        'global_id' => 'PWR-GRVL-'.fake()->unique()->numberBetween(1, 999999),
        'web_range_name' => 'Power Gravel',
        'segment' => 'GRAVEL',
        'terrain_types' => ['ASPHALT', 'MIXED'],
    ]);
}

it('casts user segment and riding_style to enums', function () {
    $user = User::factory()->create([
        'segment' => 'GRAVEL',
        'riding_style' => 'ENDURANCE',
        'segment_overridden' => true,
    ]);

    expect($user->refresh()->segment)->toBe(Segment::Gravel)
        ->and($user->riding_style)->toBe(RidingStyle::Endurance)
        ->and($user->segment_overridden)->toBeTrue();
});

it('persists enum instances assigned directly', function () {
    $user = User::factory()->create();
    $user->segment = Segment::Mtb;
    $user->save();

    expect(User::find($user->id)->segment)->toBe(Segment::Mtb);
});

it('casts strava activity surface to enum and raw_json to array', function () {
    $user = User::factory()->create();

    $activity = StravaActivity::create([
        'user_id' => $user->id,
        'external_id' => 'act-1',
        'sport_type' => 'GravelRide',
        'distance_m' => 152340,
        'moving_time_s' => 19800,
        'average_speed_ms' => 7.69,
        'total_elevation_gain_m' => 1240,
        'surface' => Surface::Mixed,
        'start_date' => now(),
        'raw_json' => ['id' => 1429876, 'athlete' => ['id' => 42]],
    ])->refresh();

    expect($activity->surface)->toBe(Surface::Mixed)
        ->and($activity->raw_json)->toBeArray()
        ->and($activity->raw_json['athlete']['id'])->toBe(42)
        ->and($activity->start_date)->toBeInstanceOf(CarbonInterface::class);
});

it('casts product terrain_types to array and tire position to enum', function () {
    $product = makeProductForCasts();
    $user = User::factory()->create();

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
    ])->refresh();

    expect($product->refresh()->terrain_types)->toBe(['ASPHALT', 'MIXED'])
        ->and($tire->position)->toBe(TirePosition::Rear);
});

it('scopes users to those connected to Strava', function () {
    $connected = User::factory()->create(['strava_athlete_id' => '42']);
    User::factory()->create(['strava_athlete_id' => null]);

    $results = User::withStravaConnected()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->is($connected))->toBeTrue();
});

it('scopes strava activities to the last six months', function () {
    $user = User::factory()->create();

    $recent = StravaActivity::create([
        'user_id' => $user->id, 'external_id' => 'recent', 'sport_type' => 'GravelRide',
        'distance_m' => 1000, 'moving_time_s' => 100, 'average_speed_ms' => 10,
        'total_elevation_gain_m' => 0, 'start_date' => now()->subWeek(), 'raw_json' => [],
    ]);
    StravaActivity::create([
        'user_id' => $user->id, 'external_id' => 'old', 'sport_type' => 'GravelRide',
        'distance_m' => 1000, 'moving_time_s' => 100, 'average_speed_ms' => 10,
        'total_elevation_gain_m' => 0, 'start_date' => now()->subMonths(8), 'raw_json' => [],
    ]);

    $results = StravaActivity::lastSixMonths()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->is($recent))->toBeTrue();
});

it('scopes user tires to active ones', function () {
    $user = User::factory()->create();
    $product = makeProductForCasts();

    $active = UserTire::create([
        'user_id' => $user->id, 'product_id' => $product->id,
        'position' => TirePosition::Rear, 'is_active' => true,
    ]);
    UserTire::create([
        'user_id' => $user->id, 'product_id' => $product->id,
        'position' => TirePosition::Front, 'is_active' => false,
    ]);

    $results = UserTire::active()->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->is($active))->toBeTrue();
});
