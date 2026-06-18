<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use App\Models\UserTire;
use Livewire\Livewire;

test('tire wear card can be mounted and displays details', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
        'segment' => 'ROAD',
        'expected_life_km' => 4000,
    ]);

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'mounted_at' => now(),
        'mounted_odometer_km' => 1000,
        'wear_percent' => 25.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-card', ['userTire' => $tire])
        ->assertSet('userTire.id', $tire->id)
        ->assertSee('Test Speed Tire')
        ->assertSee('25%');
});

test('tire wear card can simulate a ride and update wear percentage', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
        'expected_life_km' => 4000,
    ]);

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'mounted_at' => now(),
        'mounted_odometer_km' => 1000,
        'wear_percent' => 25.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    // Initial mileage = 25% of 4000 = 1000 km.
    // Simulating a 400 km ride will add to it, making it 1400 km.
    // New wear = (1400 / 4000) * 100 = 35%
    Livewire::test('tire-wear-card', ['userTire' => $tire])
        ->call('simulateRide', 400)
        ->assertSet('userTire.wear_percent', 35.00);

    expect($tire->fresh()->wear_percent)->toEqual(35.00);
});

test('tire wear card can switch view between front and rear tire', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('tire-wear-card')
        ->assertSet('activePosition', 'REAR')
        ->call('setPosition', 'FRONT')
        ->assertSet('activePosition', 'FRONT');
});

test('tire wear card can reset tire stats when replaced', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
    ]);

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 75.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-card', ['userTire' => $tire])
        ->call('resetTire')
        ->assertSet('userTire.wear_percent', 0.00);

    expect($tire->fresh()->wear_percent)->toEqual(0.00);
});

test('tire wear card displays alerts based on wear level', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
    ]);

    $this->actingAs($user);

    // Case 1: Low wear (< 50%) -> no alerts
    $tireLow = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 20.00,
        'is_active' => true,
    ]);

    Livewire::test('tire-wear-card', ['userTire' => $tireLow])
        ->assertDontSee('Usure modérée')
        ->assertDontSee('Pneu en fin de vie !');

    // Case 2: Moderate wear (50% - 80%) -> moderate wear callout
    $tireMod = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 60.00,
        'is_active' => true,
    ]);

    Livewire::test('tire-wear-card', ['userTire' => $tireMod])
        ->assertSee('Usure modérée')
        ->assertDontSee('Pneu en fin de vie !');

    // Case 3: Critical wear (>= 80%) -> end-of-life callout
    $tireCrit = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 85.00,
        'is_active' => true,
    ]);

    Livewire::test('tire-wear-card', ['userTire' => $tireCrit])
        ->assertSee('Pneu en fin de vie !')
        ->assertDontSee('Usure modérée');
});

test('tire wear card can swap the mounted tire from the collection', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
    ]);

    $active = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 60.00,
        'is_active' => true,
    ]);
    $spare = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 0.00,
        'is_active' => false,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-card')
        ->call('mountTire', $spare->id)
        ->assertSet('userTire.id', $spare->id);

    expect($spare->fresh()->is_active)->toBeTrue()
        ->and($active->fresh()->is_active)->toBeFalse();
});

test('tire wear card excludes archived tires from the swap selector', function () {
    $user = User::factory()->create();
    $product = Product::create(['global_id' => 'TEST-TIRE', 'web_range_name' => 'Test Speed Tire']);
    $active = UserTire::create(['user_id' => $user->id, 'product_id' => $product->id, 'position' => TirePosition::Rear, 'is_active' => true]);
    $archived = UserTire::create(['user_id' => $user->id, 'product_id' => $product->id, 'position' => TirePosition::Rear, 'is_active' => false, 'archived_at' => now()]);

    $this->actingAs($user);

    $ids = Livewire::test('tire-wear-card')->instance()->positionTires()->pluck('id');

    expect($ids)->toContain($active->id)->not->toContain($archived->id);
});

test('tire wear card can add simulated km from the input slider', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE',
        'web_range_name' => 'Test Speed Tire',
        'expected_life_km' => 4000,
    ]);

    $tire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'wear_percent' => 25.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-card', ['userTire' => $tire])
        ->set('simulatedKm', 200)
        ->call('addSimulatedKm')
        ->assertSet('simulatedKm', 50)
        ->assertSet('userTire.wear_percent', 30.00);

    expect($tire->fresh()->wear_percent)->toEqual(30.00);
});
