<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

it('redirects guests to the login page', function () {
    $this->get(route('alerts'))->assertRedirect(route('login'));
});

it('lists end-of-life tires to reorder with a Decathlon link, excluding fresh and archived ones', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $worn = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 86, 'is_active' => true]);
    $fresh = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 30, 'is_active' => true]);
    $archived = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 95, 'is_active' => false, 'archived_at' => now()]);

    $this->actingAs($user);

    $component = Livewire::test('pages::alerts');

    expect($component->instance()->endOfLifeTires->pluck('id'))
        ->toContain($worn->id)
        ->not->toContain($fresh->id)
        ->not->toContain($archived->id);

    $component->assertSee('Order on Decathlon')->assertSee('decathlon.fr', false);
});

it('shows an empty state when nothing is end of life', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 20, 'is_active' => true]);
    $this->actingAs($user);

    Livewire::test('pages::alerts')->assertSee('No alerts');
});

it('counts end-of-life tires for the reorder badge', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'wear_percent' => 86, 'is_active' => true]);
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 30, 'is_active' => true]);
    $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'wear_percent' => 99, 'is_active' => false, 'archived_at' => now()]);

    expect($user->reorderCount())->toBe(1);
});
