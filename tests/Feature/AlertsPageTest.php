<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

it('redirects guests to the login page', function () {
    $this->get(route('alerts'))->assertRedirect(route('login'));
});

it('lists end-of-life tires to reorder, excluding fresh, archived and ordered ones', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $worn = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 86, 'is_active' => true]);
    $fresh = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 30, 'is_active' => true]);
    $archived = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 95, 'is_active' => false, 'archived_at' => now()]);
    $ordered = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 90, 'is_active' => false, 'ordered_at' => now()]);

    $this->actingAs($user);

    $component = Livewire::test('pages::alerts');

    expect($component->instance()->pendingTires->pluck('id'))
        ->toContain($worn->id)
        ->not->toContain($fresh->id)
        ->not->toContain($archived->id)
        ->not->toContain($ordered->id);

    $component->assertSee('Order on Decathlon')->assertSee('decathlon.fr', false);
});

it('shows an empty state when nothing is end of life', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 20, 'is_active' => true]);
    $this->actingAs($user);

    Livewire::test('pages::alerts')->assertSee('No alerts');
});

it('marks a tire as ordered so it leaves the bell, then cancels it', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $tire = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 86, 'is_active' => true]);
    $this->actingAs($user);

    expect($user->reorderCount())->toBe(1);

    $component = Livewire::test('pages::alerts')->call('markOrdered', $tire->id);

    expect($tire->fresh()->ordered_at)->not->toBeNull()
        ->and($user->reorderCount())->toBe(0)
        ->and($component->instance()->pendingTires->pluck('id'))->not->toContain($tire->id)
        ->and($component->instance()->orderedTires->pluck('id'))->toContain($tire->id);

    $component->call('cancelOrder', $tire->id);

    expect($tire->fresh()->ordered_at)->toBeNull()
        ->and($user->reorderCount())->toBe(1);
});

it('counts only pending (not-ordered) end-of-life tires for the badge', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 86, 'is_active' => true]);
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 99, 'is_active' => true, 'ordered_at' => now()]);

    expect($user->reorderCount())->toBe(1);
});
