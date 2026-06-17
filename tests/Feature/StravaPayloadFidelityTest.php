<?php

use App\Enums\Surface;
use App\Models\StravaActivity;

it('produces a Strava DetailedActivity payload with the expected key set', function () {
    $payload = StravaActivity::factory()->create()->raw_json;

    $expectedKeys = [
        'id', 'external_id', 'name', 'distance', 'moving_time', 'elapsed_time',
        'total_elevation_gain', 'elev_high', 'elev_low', 'sport_type', 'type',
        'start_date', 'start_date_local', 'timezone', 'utc_offset',
        'average_speed', 'max_speed', 'average_cadence', 'average_watts',
        'weighted_average_watts', 'kilojoules', 'device_watts', 'has_heartrate',
        'average_heartrate', 'max_heartrate', 'athlete', 'gear_id', 'gear', 'map',
        'trainer', 'commute', 'manual', 'private', 'resource_state', '_derived',
    ];

    foreach ($expectedKeys as $key) {
        expect($payload)->toHaveKey($key);
    }
});

it('uses Strava-native units and types', function () {
    $payload = StravaActivity::factory()->create([
        'distance_m' => 42195,
        'average_speed_ms' => 7.5,
        'average_watts' => 200,
    ])->raw_json;

    expect($payload['moving_time'])->toBeInt()                                  // seconds
        ->and($payload['average_speed'])->toBeFloat()                            // m/s
        ->and($payload['max_speed'])->toBeGreaterThan($payload['average_speed'])
        ->and($payload['elapsed_time'])->toBeGreaterThanOrEqual($payload['moving_time'])
        ->and($payload['device_watts'])->toBeBool()
        ->and($payload['kilojoules'])->toBeGreaterThan(0)                       // kJ
        ->and($payload['average_watts'])->toEqual(200)                          // watts
        ->and($payload['sport_type'])->toBe('GravelRide')                       // real enum value
        ->and($payload['start_date'])->toEndWith('Z');                          // ISO-8601 UTC
});

it('exposes the bike via gear but carries no per-tire data (Strava provides none)', function () {
    $payload = StravaActivity::factory()->create()->raw_json;

    expect($payload['gear_id'])->toStartWith('b')
        ->and($payload['gear']['id'])->toBe($payload['gear_id'])
        ->and($payload['gear']['distance'])->toBeGreaterThan(0)  // bike lifetime odometer (meters)
        ->and($payload['gear'])->not->toHaveKey('tire')
        ->and($payload['gear'])->not->toHaveKey('components');
});

it('namespaces our own derived surface separately from real Strava fields', function () {
    $payload = StravaActivity::factory()->create(['surface_derived' => Surface::Mixed])->raw_json;

    expect($payload['_derived']['surface'])->toBe('MIXED')
        ->and($payload)->not->toHaveKey('surface'); // Strava itself never sends a surface field
});
