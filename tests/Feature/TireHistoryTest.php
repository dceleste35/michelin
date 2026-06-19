<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use Database\Seeders\MarcSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

it('links an activity to its front and rear tires', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $front = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'is_active' => true]);
    $rear = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => true]);

    $activity = StravaActivity::factory()->for($user)->create([
        'front_tire_id' => $front->id,
        'rear_tire_id' => $rear->id,
        'tires_confirmed' => true,
    ]);

    expect($activity->frontTire->is($front))->toBeTrue()
        ->and($activity->rearTire->is($rear))->toBeTrue()
        ->and($activity->tires_confirmed)->toBeTrue();
});

it('scopes activities to those ridden on a given tire (front or rear)', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $tire = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => true]);
    $onTire = StravaActivity::factory()->for($user)->create(['rear_tire_id' => $tire->id]);
    StravaActivity::factory()->for($user)->create(); // aucun pneu assigné

    $rides = StravaActivity::forUserTire($tire)->get();

    expect($rides)->toHaveCount(1)
        ->and($rides->first()->is($onTire))->toBeTrue();
});

it('seeds Marc with a tire swap and preserves per-ride history', function () {
    test()->seed(ProductCatalogSeeder::class);
    test()->seed(MarcSeeder::class);
    $marc = User::where('email', 'marc@rideready.test')->sole();

    expect($marc->tires()->where('is_active', true)->count())->toBe(2)   // jeu actuel
        ->and($marc->tires()->where('is_active', false)->count())->toBe(2); // ancien jeu retiré

    $currentRear = $marc->tires()->where('position', TirePosition::Rear->value)->where('is_active', true)->sole();
    $oldRear = $marc->tires()->where('position', TirePosition::Rear->value)->where('is_active', false)->sole();

    expect(StravaActivity::forUserTire($currentRear)->count())->toBeGreaterThan(0)   // sorties récentes
        ->and(StravaActivity::forUserTire($oldRear)->count())->toBeGreaterThan(0)    // sorties anciennes conservées
        ->and($marc->stravaActivities()->where('tires_confirmed', false)->count())->toBe(8); // récentes à vérifier
});

it('verifies and edits the tires of a single ride from the activities cards', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $front = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'is_active' => true]);
    $rear = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Rear, 'is_active' => true]);
    $ride = StravaActivity::factory()->for($user)->create(['tires_confirmed' => false]);
    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->call('startEdit', $ride->id)
        ->set('editFront', $front->id)
        ->set('editRear', $rear->id)
        ->call('saveRide');

    $ride->refresh();
    expect($ride->front_tire_id)->toBe($front->id)
        ->and($ride->rear_tire_id)->toBe($rear->id)
        ->and($ride->tires_confirmed)->toBeTrue();
});

it('derives a tire wear from the rides assigned to it', function () {
    $user = User::factory()->create();
    $product = Product::create(['global_id' => 'TEST', 'web_range_name' => 'Test', 'expected_life_km' => 4000]);
    $tire = $user->tires()->create(['product_id' => $product->id, 'position' => TirePosition::Rear, 'is_active' => true, 'wear_percent' => 0, 'baseline_wear_km' => 0]);
    $ride = StravaActivity::factory()->for($user)->create(['distance_m' => 2_000_000, 'tires_confirmed' => false]); // 2 000 km
    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->call('startEdit', $ride->id)
        ->set('editRear', $tire->id)
        ->call('saveRide');

    // 2 000 km / 4 000 km de durée de vie = 50 % d'usure.
    expect((float) $tire->fresh()->wear_percent)->toBe(50.0);
});

it('reports the real km on a tire (rides + baseline), not derived from rounded wear', function () {
    $user = User::factory()->create();
    $product = Product::create(['global_id' => 'CITY', 'web_range_name' => 'City Cargo']); // expected_life_km null
    $tire = $user->tires()->create(['product_id' => $product->id, 'position' => TirePosition::Rear, 'is_active' => true, 'baseline_wear_km' => 0]);
    StravaActivity::factory()->for($user)->create(['distance_m' => 120000, 'rear_tire_id' => $tire->id]);
    $tire->recomputeWear();

    // Une sortie de 120 km → 120 km réels (pas 150 = 3 % × 5000), durée de vie par défaut 4000.
    expect((int) $tire->currentKm())->toBe(120)
        ->and($tire->expectedLifeKm())->toBe(4000)
        ->and((float) $tire->fresh()->wear_percent)->toBe(3.0);
});

it('excludes archived tires from the per-ride tire selectors', function () {
    test()->seed(ProductCatalogSeeder::class);
    $user = User::factory()->create();
    $active = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'is_active' => true]);
    $archived = $user->tires()->create(['product_id' => Product::first()->id, 'position' => TirePosition::Front, 'is_active' => false, 'archived_at' => now()]);
    $this->actingAs($user);

    $ids = Livewire::test('pages::activities')->instance()->frontTires->pluck('id');

    expect($ids)->toContain($active->id)->not->toContain($archived->id);
});

it('shows a verify-tires banner on activities and confirms all', function () {
    $user = User::factory()->create();
    StravaActivity::factory()->count(2)->for($user)->create(['tires_confirmed' => false]);
    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->assertSee('rides with tires to verify')
        ->call('confirmAllTires');

    expect($user->stravaActivities()->where('tires_confirmed', false)->count())->toBe(0);
});
