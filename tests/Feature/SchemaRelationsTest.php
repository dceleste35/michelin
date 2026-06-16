<?php

use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function makeProduct(array $overrides = []): Product
{
    return Product::create(array_merge([
        'global_id' => 'PWR-GRVL',
        'web_range_name' => 'Power Gravel',
        'segment' => 'GRAVEL',
        'width_etrto' => 42,
        'diameter_etrto' => 622,
    ], $overrides));
}

function makeActivity(User $user, array $overrides = []): StravaActivity
{
    return StravaActivity::create(array_merge([
        'user_id' => $user->id,
        'external_id' => 'act-'.fake()->unique()->numberBetween(1, 999999),
        'sport_type' => 'GravelRide',
        'distance_m' => 152340,
        'moving_time_s' => 19800,
        'average_speed_ms' => 7.69,
        'total_elevation_gain_m' => 1240,
        'average_watts' => 178,
        'average_cadence' => 84,
        'surface' => 'MIXED',
        'start_date' => now(),
        'raw_json' => json_encode(['id' => 1429876]),
    ], $overrides));
}

it('relates a user to many strava activities (1—N)', function () {
    $user = User::factory()->create();
    makeActivity($user);
    makeActivity($user);

    expect($user->stravaActivities())->toBeInstanceOf(HasMany::class)
        ->and($user->stravaActivities)->toHaveCount(2)
        ->and($user->stravaActivities->first())->toBeInstanceOf(StravaActivity::class);
});

it('relates a strava activity back to its user (N—1)', function () {
    $user = User::factory()->create();
    $activity = makeActivity($user);

    expect($activity->user())->toBeInstanceOf(BelongsTo::class)
        ->and($activity->user->is($user))->toBeTrue();
});

it('relates users and products through user_tires (1—N—1)', function () {
    $user = User::factory()->create();
    $product = makeProduct();

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => 'REAR',
        'wear_percent' => 86.00,
        'is_active' => true,
    ]);

    expect($user->tires)->toHaveCount(1)
        ->and($tire->product->is($product))->toBeTrue()
        ->and($tire->user->is($user))->toBeTrue()
        ->and($product->userTires)->toHaveCount(1);
});

it('constrains enum columns at the database level', function () {
    $user = User::factory()->create();
    $product = makeProduct();

    // Insert via the query builder to bypass the model enum cast and prove the
    // database CHECK constraint itself rejects out-of-range values.
    expect(fn () => DB::table('user_tires')->insert([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => 'SIDE', // not in FRONT|REAR
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(QueryException::class);
});
