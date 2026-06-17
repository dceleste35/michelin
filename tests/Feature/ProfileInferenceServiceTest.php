<?php

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Models\StravaActivity;
use App\Models\User;
use App\Services\ProfileInferenceService;
use Database\Seeders\MarcSeeder;
use Database\Seeders\ProductCatalogSeeder;

beforeEach(function () {
    $this->service = new ProfileInferenceService;
});

/** Build an in-memory (unsaved) activity for pure-rule tests. */
function rideAttrs(array $attrs = []): StravaActivity
{
    return new StravaActivity(array_merge([
        'sport_type' => 'GravelRide',
        'distance_m' => 50_000,
        'moving_time_s' => 7_200,
        'average_speed_ms' => 7.0,
        'total_elevation_gain_m' => 500,
        'average_watts' => 178,
        'average_cadence' => 84,
    ], $attrs));
}

// ---------------------------------------------------------------------------
// deriveSurface — documented credibility rules
// ---------------------------------------------------------------------------

it('derives surface from Strava signals', function (array $attrs, Surface $expected) {
    expect($this->service->deriveSurface(rideAttrs($attrs)))->toBe($expected);
})->with([
    'flat road Ride → asphalt' => [['sport_type' => 'Ride', 'distance_m' => 50_000, 'total_elevation_gain_m' => 300], Surface::Asphalt],
    'hilly Ride → hardpacked' => [['sport_type' => 'Ride', 'distance_m' => 50_000, 'total_elevation_gain_m' => 600], Surface::Hardpacked],
    'virtual ride → asphalt' => [['sport_type' => 'VirtualRide', 'distance_m' => 40_000, 'total_elevation_gain_m' => 100], Surface::Asphalt],
    'flat fast gravel → asphalt' => [['sport_type' => 'GravelRide', 'distance_m' => 60_000, 'total_elevation_gain_m' => 300, 'average_speed_ms' => 7.5], Surface::Asphalt],
    'rolling gravel → hardpacked' => [['sport_type' => 'GravelRide', 'distance_m' => 50_000, 'total_elevation_gain_m' => 500, 'average_speed_ms' => 6.0], Surface::Hardpacked],
    'steep gravel → mixed' => [['sport_type' => 'GravelRide', 'distance_m' => 30_000, 'total_elevation_gain_m' => 600, 'average_speed_ms' => 5.0], Surface::Mixed],
    'rolling MTB → mixed' => [['sport_type' => 'MountainBikeRide', 'distance_m' => 30_000, 'total_elevation_gain_m' => 300], Surface::Mixed],
    'climbing MTB at pace → soft' => [['sport_type' => 'MountainBikeRide', 'distance_m' => 30_000, 'total_elevation_gain_m' => 700, 'average_speed_ms' => 5.0], Surface::Soft],
    'crawling MTB → mud' => [['sport_type' => 'MountainBikeRide', 'distance_m' => 20_000, 'total_elevation_gain_m' => 500, 'average_speed_ms' => 2.5], Surface::Mud],
    'very steep MTB → mud' => [['sport_type' => 'EMountainBikeRide', 'distance_m' => 20_000, 'total_elevation_gain_m' => 800, 'average_speed_ms' => 4.0], Surface::Mud],
    'ebike → asphalt' => [['sport_type' => 'EBikeRide', 'distance_m' => 20_000, 'total_elevation_gain_m' => 300], Surface::Asphalt],
    'unknown sport → mixed' => [['sport_type' => 'Kayaking', 'distance_m' => 10_000, 'total_elevation_gain_m' => 0], Surface::Mixed],
]);

// ---------------------------------------------------------------------------
// inferSegment
// ---------------------------------------------------------------------------

it('infers GRAVEL as the default for no activities', function () {
    expect($this->service->inferSegment(collect()))->toBe(Segment::Gravel);
});

it('infers EBIKE_URBAN when e-bike rides dominate', function () {
    $activities = collect([
        rideAttrs(['sport_type' => 'EBikeRide', 'surface_derived' => Surface::Asphalt]),
        rideAttrs(['sport_type' => 'EBikeRide', 'surface_derived' => Surface::Asphalt]),
        rideAttrs(['sport_type' => 'GravelRide', 'surface_derived' => Surface::Mixed]),
    ]);

    expect($this->service->inferSegment($activities))->toBe(Segment::EbikeUrban);
});

it('infers MTB when off-road share exceeds 70%', function () {
    $activities = collect([
        rideAttrs(['surface_derived' => Surface::Mixed]),
        rideAttrs(['surface_derived' => Surface::Soft]),
        rideAttrs(['surface_derived' => Surface::Mud]),
        rideAttrs(['surface_derived' => Surface::Asphalt]),
    ]); // 3/4 = 75 % off-road

    expect($this->service->inferSegment($activities))->toBe(Segment::Mtb);
});

it('infers GRAVEL for a mixed 15–70% off-road share', function () {
    $activities = collect([
        rideAttrs(['surface_derived' => Surface::Asphalt]),
        rideAttrs(['surface_derived' => Surface::Asphalt]),
        rideAttrs(['surface_derived' => Surface::Hardpacked]),
        rideAttrs(['surface_derived' => Surface::Mixed]),
    ]); // 2/4 = 50 % off-road

    expect($this->service->inferSegment($activities))->toBe(Segment::Gravel);
});

it('infers ROAD for an asphalt-dominant rider (deriving surface on the fly)', function () {
    // surface_derived null → service derives it from the flat road rides.
    $activities = collect([
        rideAttrs(['sport_type' => 'Ride', 'total_elevation_gain_m' => 200, 'surface_derived' => null]),
        rideAttrs(['sport_type' => 'Ride', 'total_elevation_gain_m' => 150, 'surface_derived' => null]),
        rideAttrs(['sport_type' => 'Ride', 'total_elevation_gain_m' => 250, 'surface_derived' => null]),
    ]); // all asphalt → 0 % off-road

    expect($this->service->inferSegment($activities))->toBe(Segment::Road);
});

// ---------------------------------------------------------------------------
// inferRidingStyle
// ---------------------------------------------------------------------------

it('infers ENDURANCE when no power data is available', function () {
    $activities = collect([
        rideAttrs(['average_watts' => null]),
        rideAttrs(['average_watts' => null]),
    ]);

    expect($this->service->inferRidingStyle($activities, 75))->toBe(RidingStyle::Endurance);
});

it('infers AGGRESSIF on high power-to-weight', function () {
    $activities = collect([
        rideAttrs(['average_watts' => 300, 'average_speed_ms' => 9.0]),
        rideAttrs(['average_watts' => 290, 'average_speed_ms' => 9.0]),
    ]);

    expect($this->service->inferRidingStyle($activities, 70))->toBe(RidingStyle::Aggressif); // ~4.2 W/kg
});

it('infers AGGRESSIF on high pace variability', function () {
    $activities = collect([
        rideAttrs(['average_watts' => 150, 'average_speed_ms' => 11.0]),
        rideAttrs(['average_watts' => 150, 'average_speed_ms' => 5.0]),
        rideAttrs(['average_watts' => 150, 'average_speed_ms' => 11.0]),
        rideAttrs(['average_watts' => 150, 'average_speed_ms' => 5.0]),
    ]); // low W/kg but ~10 km/h spread → high std dev

    expect($this->service->inferRidingStyle($activities, 90))->toBe(RidingStyle::Aggressif);
});

it('infers ENDURANCE for a steady, moderate-power rider', function () {
    $activities = collect([
        rideAttrs(['average_watts' => 178, 'average_speed_ms' => 7.2]),
        rideAttrs(['average_watts' => 180, 'average_speed_ms' => 7.2]),
    ]);

    expect($this->service->inferRidingStyle($activities, 90))->toBe(RidingStyle::Endurance);
});

// ---------------------------------------------------------------------------
// terrainDistribution
// ---------------------------------------------------------------------------

it('returns an all-zero terrain map for no activities', function () {
    expect($this->service->terrainDistribution(collect()))
        ->toBe(['asphalt' => 0, 'hardpacked' => 0, 'mixed' => 0, 'soft' => 0, 'mud' => 0]);
});

it('computes terrain percentages per surface', function () {
    $activities = collect([
        rideAttrs(['surface_derived' => Surface::Asphalt]),
        rideAttrs(['surface_derived' => Surface::Asphalt]),
        rideAttrs(['surface_derived' => Surface::Hardpacked]),
        rideAttrs(['surface_derived' => Surface::Mixed]),
    ]);

    expect($this->service->terrainDistribution($activities))
        ->toBe(['asphalt' => 50, 'hardpacked' => 25, 'mixed' => 25, 'soft' => 0, 'mud' => 0]);
});

// ---------------------------------------------------------------------------
// infer + inferAndPersist (DB-backed)
// ---------------------------------------------------------------------------

it('infers Marc as a GRAVEL endurance rider with ~60/40 terrain', function () {
    test()->seed(ProductCatalogSeeder::class);
    test()->seed(MarcSeeder::class);
    $marc = User::where('email', 'marc@rideready.test')->sole();

    $profile = $this->service->infer($marc);

    expect($profile->segment)->toBe(Segment::Gravel)
        ->and($profile->ridingStyle)->toBe(RidingStyle::Endurance)
        ->and($profile->weightKg)->toBe(90)
        ->and($profile->terrainPct['asphalt'])->toBe(60)
        ->and($profile->terrainPct['hardpacked'] + $profile->terrainPct['mixed'])->toBe(40);
});

it('persists inferred fields and overrides a wrong persisted segment', function () {
    $user = User::factory()->create([
        'segment' => 'ROAD',          // wrong on purpose
        'weight_kg' => null,
        'segment_overridden' => false,
    ]);
    StravaActivity::factory()->count(4)->for($user)->sequence(
        ['surface_derived' => Surface::Asphalt],
        ['surface_derived' => Surface::Asphalt],
        ['surface_derived' => Surface::Hardpacked],
        ['surface_derived' => Surface::Mixed],
    )->create();

    $profile = $this->service->inferAndPersist($user->fresh());

    expect($profile->segment)->toBe(Segment::Gravel)
        ->and($user->fresh()->segment)->toBe(Segment::Gravel)   // ROAD overwritten
        ->and($user->fresh()->weight_kg)->toBe(90);             // default applied
});

it('preserves a user-overridden segment', function () {
    $user = User::factory()->create([
        'segment' => 'ROAD',
        'segment_overridden' => true, // user chose it manually
    ]);
    StravaActivity::factory()->count(3)->for($user)->create([
        'surface_derived' => Surface::Mixed, // would infer GRAVEL/MTB
    ]);

    $this->service->inferAndPersist($user->fresh());

    expect($user->fresh()->segment)->toBe(Segment::Road); // untouched
});
