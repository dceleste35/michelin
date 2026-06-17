<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use Livewire\Livewire;

test('instagram generator component can be mounted and loads stats', function () {
    $user = User::factory()->create(['name' => 'Marc Dupont']);

    $product = Product::create([
        'global_id' => 'GRAVEL-TIRE',
        'web_range_name' => 'Power Gravel',
        'segment' => 'GRAVEL',
        'expected_life_km' => 5000,
    ]);

    // Seed some mock activities for the year
    StravaActivity::create([
        'user_id' => $user->id,
        'external_id' => 'act-1',
        'sport_type' => 'GravelRide',
        'distance_m' => 12000, // 12 km
        'moving_time_s' => 1800,
        'average_speed_ms' => '6.6',
        'total_elevation_gain_m' => 150,
        'start_date' => now()->startOfYear(),
        'raw_json' => [],
    ]);

    UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'mounted_at' => now()->subMonths(3),
        'mounted_odometer_km' => 0,
        'wear_percent' => 50.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('instagram-generator')
        ->assertSet('username', '@marcdupont')
        ->assertSet('distance', 12)
        ->assertSet('elevation', 150)
        ->assertSet('ridesCount', 1)
        ->assertSet('tireModel', 'Power Gravel')
        ->assertSee('Aperçu de votre post');
});

test('instagram generator falls back to mock stats when user has no activities', function () {
    $user = User::factory()->create(['name' => 'New Rider']);
    $this->actingAs($user);

    Livewire::test('instagram-generator')
        ->assertSet('username', '@newrider')
        ->assertSet('distance', 3840)
        ->assertSet('elevation', 42100)
        ->assertSet('ridesCount', 82)
        ->assertSee('Aperçu de votre post');
});

test('instagram generator can toggle style templates and metrics', function () {
    $user = User::factory()->create(['name' => 'Jane Rider']);
    $this->actingAs($user);

    Livewire::test('instagram-generator')
        ->set('style', 'dark-carbon')
        ->set('highlightMetric', 'elevation')
        ->assertSet('style', 'dark-carbon')
        ->assertSet('highlightMetric', 'elevation');
});
