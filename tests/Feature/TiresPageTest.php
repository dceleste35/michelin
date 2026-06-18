<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use App\Models\UserTire;
use Database\Seeders\MarcSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

it('redirects guests to the login page', function () {
    $this->get(route('tires'))->assertRedirect(route('login'));
});

it('shows the add-tire form and an empty state for a rider with no tires', function () {
    test()->seed(ProductCatalogSeeder::class);
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::tires')
        ->assertSee('Add a tire')
        ->assertSee('No tire registered yet');
});

it('mounts a tire from the catalogue', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::tires')
        ->set('productId', (string) Product::first()->id)
        ->set('position', 'REAR')
        ->set('mountedOdometerKm', 1200)
        ->call('addTire')
        ->assertHasNoErrors();

    expect($user->tires()->count())->toBe(1)
        ->and($user->tires()->first()->position)->toBe(TirePosition::Rear);
});

it('retires the previous tire at a position when a new one is mounted', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $this->actingAs($user);
    $products = Product::take(2)->get();

    Livewire::test('pages::tires')
        ->set('productId', (string) $products[0]->id)->set('position', 'REAR')->call('addTire')
        ->set('productId', (string) $products[1]->id)->set('position', 'REAR')->call('addTire');

    // Un seul actif à l'arrière (le dernier monté), l'ancien est conservé pour l'historique.
    expect($user->tires()->where('position', 'REAR')->where('is_active', true)->count())->toBe(1)
        ->and($user->tires()->where('position', 'REAR')->where('is_active', true)->sole()->product_id)->toBe($products[1]->id)
        ->and($user->tires()->where('position', 'REAR')->count())->toBe(2);
});

it('removes a mounted tire', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $tire = $user->tires()->create([
        'product_id' => Product::first()->id,
        'position' => TirePosition::Rear,
        'is_active' => true,
    ]);
    $this->actingAs($user);

    Livewire::test('pages::tires')->call('removeTire', $tire->id);

    expect($user->tires()->count())->toBe(0);
});

it('archives an available tire and restores it', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $tire = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => false]);
    $this->actingAs($user);

    Livewire::test('pages::tires')->call('archiveTire', $tire->id);
    expect($tire->fresh()->archived_at)->not->toBeNull();

    Livewire::test('pages::tires')->call('unarchiveTire', $tire->id);
    expect($tire->fresh()->archived_at)->toBeNull();
});

it('does not archive the currently mounted tire', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $tire = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => true]);
    $this->actingAs($user);

    Livewire::test('pages::tires')->call('archiveTire', $tire->id);

    expect($tire->fresh()->archived_at)->toBeNull(); // un pneu monté n'est pas archivable
});

it('hides archived tires from the current collection but lists them apart', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $active = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => true]);
    $archived = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'is_active' => false, 'archived_at' => now()]);
    $this->actingAs($user);

    $component = Livewire::test('pages::tires');

    expect($component->instance()->tires->pluck('id'))->toContain($active->id)->not->toContain($archived->id)
        ->and($component->instance()->archivedTires->pluck('id'))->toContain($archived->id);
});

it('rejects an invalid tire submission', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::tires')
        ->set('productId', '')
        ->call('addTire')
        ->assertHasErrors('productId');
});

it('shows an empty state on the tire card instead of fabricating tires', function () {
    $this->actingAs(User::factory()->create()); // no tires

    Livewire::test('tire-wear-card')
        ->assertSee('No tire registered yet')
        ->assertSee(route('tires'), false);

    expect(UserTire::count())->toBe(0); // nothing fabricated
});

it('still shows tire data on the card when the rider has tires', function () {
    test()->seed(ProductCatalogSeeder::class);
    test()->seed(MarcSeeder::class);
    $this->actingAs(User::where('email', 'marc@rideready.test')->sole());

    Livewire::test('tire-wear-card')->assertDontSee('No tire registered yet');
});
