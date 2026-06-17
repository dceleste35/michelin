<?php

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use App\Models\UserTire;
use Livewire\Livewire;

test('rider profile component mounts and displays default info for guest', function () {
    Livewire::test('rider-profile')
        ->assertSee('Gravel')
        ->assertSee('Endurance')
        ->assertSee('Marc')
        ->assertSee('Power Gravel');
});

test('rider profile component displays active user stats and tires', function () {
    $user = User::factory()->create([
        'name' => 'Jean Michel',
        'segment' => Segment::Road,
        'riding_style' => RidingStyle::Aggressif,
        'weight_kg' => 75,
    ]);

    $product = Product::create([
        'global_id' => 'ROAD-TIRE-1',
        'web_range_name' => 'Power Cup TLR',
        'segment' => 'ROAD',
        'width_etrto' => 25,
        'ean_code' => '3528701764214',
    ]);

    UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Front,
        'mounted_at' => now(),
        'mounted_odometer_km' => 0,
        'wear_percent' => 10.00,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('rider-profile')
        ->assertSee('Jean Michel')
        ->assertSee('ROAD')
        ->assertSee('AGGRESSIF')
        ->assertSee('Power Cup TLR')
        ->assertSee('10%');
});
