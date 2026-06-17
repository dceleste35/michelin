<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use App\Models\UserTire;
use Livewire\Livewire;

test('tire recommendation component mounts and displays the 3 pre-selected tires', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('tire-recommendation')
        ->assertSee('Power Gravel')
        ->assertSee('Power Gravel RS')
        ->assertSee('Power Adventure');
});

test('displays mock performance indicators', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('tire-recommendation')
        ->assertSee('96%')
        ->assertSee('Recommandé pour vous')
        ->assertSee('Idéal pour vos performances Strava')
        ->assertSee('59,90 €');
});

test('mounting a tire updates the active tire in database and dispatches event', function () {
    $user = User::factory()->create();

    // Seed products to ensure we match the ids
    $product = Product::create([
        'global_id' => 'BI-177',
        'web_range_name' => 'Power Gravel RS',
        'segment' => 'GRAVEL',
        'ean_code' => '3528705648480',
    ]);

    // Create an old active tire
    $oldTire = UserTire::create([
        'user_id' => $user->id,
        'product_id' => $product->id,
        'position' => TirePosition::Rear,
        'is_active' => true,
    ]);

    $this->actingAs($user);

    Livewire::test('tire-recommendation')
        ->call('mountProduct', $product->id)
        ->assertDispatched('tire-mounted');

    // Verify old tire is deactivated
    expect($oldTire->fresh()->is_active)->toBeFalse();

    // Verify new tire is created and active
    $newTire = UserTire::where('user_id', $user->id)->active()->first();
    expect($newTire)->not->toBeNull();
    expect($newTire->product_id)->toBe($product->id);
    expect($newTire->position)->toBe(TirePosition::Rear);
});
