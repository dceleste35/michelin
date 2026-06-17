<?php

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use Carbon\CarbonImmutable;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

function tireFor(User $user): UserTire
{
    test()->seed(ProductCatalogSeeder::class);

    return $user->tires()->create([
        'product_id' => Product::first()->id,
        'position' => TirePosition::Rear,
        'mounted_at' => CarbonImmutable::now()->subDays(30),
        'mounted_odometer_km' => 1000,
        'wear_percent' => 42,
        'is_active' => true,
    ]);
}

it('redirects guests to the login page', function () {
    $tire = tireFor(User::factory()->create());

    $this->get(route('tires.show', $tire))->assertRedirect(route('login'));
});

it('forbids viewing another rider tire', function () {
    $tire = tireFor(User::factory()->create());
    $this->actingAs(User::factory()->create());

    $this->get(route('tires.show', $tire))->assertForbidden();
});

it('shows the rides ridden on this tire, with distance and provisional wear', function () {
    $user = User::factory()->create();
    $tire = tireFor($user);
    StravaActivity::factory()->for($user)->create(['distance_m' => 40000, 'rear_tire_id' => $tire->id]); // monté sur ce pneu
    StravaActivity::factory()->for($user)->create(['distance_m' => 99000]);                              // aucun pneu assigné

    $this->actingAs($user);

    Livewire::test('pages::tire-detail', ['userTire' => $tire])
        ->assertSee('40.0 km')      // sortie sur ce pneu (locale en)
        ->assertDontSee('99.0 km')  // sortie hors de ce pneu exclue
        ->assertSee('42%');         // usure provisoire
});

it('shows an empty rides state when nothing was ridden on this tire', function () {
    $user = User::factory()->create();
    $tire = tireFor($user);

    $this->actingAs($user);

    Livewire::test('pages::tire-detail', ['userTire' => $tire])
        ->assertSee('No ride recorded on this tire yet.');
});
