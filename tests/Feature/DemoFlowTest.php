<?php

use App\Enums\TirePosition;
use App\Models\StravaActivity;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductCatalogSeeder;

beforeEach(function () {
    test()->seed(ProductCatalogSeeder::class);
    test()->seed(DemoSeeder::class);
});

it('seeds a first-time rider: connected to Strava, rides imported, no tires', function () {
    $marc = User::where('email', 'marc@rideready.test')->sole();

    expect($marc->strava_athlete_id)->not->toBeNull()
        ->and($marc->profile_confirmed_at)->toBeNull()
        ->and($marc->stravaActivities()->count())->toBeGreaterThan(0)
        ->and($marc->stravaActivities()->where('tires_confirmed', true)->count())->toBe(0) // tout à vérifier
        ->and($marc->tires()->count())->toBe(0);
});

it('demo:tires mounts a Power Gravel pair at the given wear and assigns it to the rides', function () {
    $this->artisan('demo:tires', ['--wear' => 86])->assertSuccessful();

    $marc = User::where('email', 'marc@rideready.test')->sole();

    expect($marc->tires()->active()->count())->toBe(2)
        ->and((float) $marc->tires()->active()->where('position', TirePosition::Rear->value)->sole()->wear_percent)->toBe(86.0)
        ->and((float) $marc->tires()->active()->where('position', TirePosition::Front->value)->sole()->wear_percent)->toBe(62.0)
        ->and($marc->stravaActivities()->whereNotNull('rear_tire_id')->count())->toBe($marc->stravaActivities()->count())
        ->and($marc->reorderCount())->toBe(1); // arrière 86 % → fin de vie → cloche
});

it('demo:wear ages the mounted tires to cross the end-of-life threshold', function () {
    $this->artisan('demo:tires')->assertSuccessful(); // paire fraîche

    $marc = User::where('email', 'marc@rideready.test')->sole();
    expect($marc->reorderCount())->toBe(0); // fraîche → pas d'alerte

    $this->artisan('demo:wear', ['--rear' => 90, '--front' => 60])->assertSuccessful();

    expect((float) $marc->tires()->active()->where('position', TirePosition::Rear->value)->sole()->wear_percent)->toBe(90.0)
        ->and($marc->reorderCount())->toBe(1); // arrière 90 % → cloche armée
});

it('demo:tires fails clearly when the demo is not seeded', function () {
    StravaActivity::query()->delete();
    User::query()->delete();

    $this->artisan('demo:tires')->assertFailed();
});
