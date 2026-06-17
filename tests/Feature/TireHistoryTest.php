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

it('shows a verify-tires banner on activities and confirms all', function () {
    $user = User::factory()->create();
    StravaActivity::factory()->count(2)->for($user)->create(['tires_confirmed' => false]);
    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->assertSee('rides with tires to verify')
        ->call('confirmAllTires');

    expect($user->stravaActivities()->where('tires_confirmed', false)->count())->toBe(0);
});
