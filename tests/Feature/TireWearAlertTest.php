<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use App\Models\UserTire;
use Livewire\Livewire;

test('tire wear alert displays alert when tire wear is critical', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE-ALERT',
        'web_range_name' => 'Power Gravel',
        'segment' => 'GRAVEL',
        'expected_life_km' => 4000,
        'ean_code' => '3528702637890',
    ]);

    // Tire worn at 85%
    UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'mounted_at' => now(),
        'mounted_odometer_km' => 0,
        'wear_percent' => 85.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-alert')
        ->assertSee('Alerte sécurité')
        ->assertSee('pneu arrière')
        ->assertSee('85%');
});

test('tire wear alert does not display alert when tire wear is fine', function () {
    $user = User::factory()->create();
    $product = Product::create([
        'global_id' => 'TEST-TIRE-FINE',
        'web_range_name' => 'Power Gravel',
        'segment' => 'GRAVEL',
        'expected_life_km' => 4000,
        'ean_code' => '3528702637890',
    ]);

    // Tire worn at 30%
    UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'mounted_at' => now(),
        'mounted_odometer_km' => 0,
        'wear_percent' => 30.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-wear-alert')
        ->assertDontSee('Alerte sécurité');
});
