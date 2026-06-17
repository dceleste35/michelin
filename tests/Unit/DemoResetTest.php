<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

// `demo:reset` rejoue lui-même un `migrate:fresh`, qui ne peut pas s'exécuter dans
// la transaction de LazilyRefreshDatabase (VACUUM SQLite interdit en transaction).
// On utilise donc DatabaseMigrations (sans transaction enveloppante) pour ce test.
uses(TestCase::class, DatabaseMigrations::class);

it('refuse de réinitialiser la démo en production sans --force', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->artisan('demo:reset')->assertFailed();

    expect(User::where('email', 'marc@rideready.test')->exists())->toBeFalse(); // rien n'a été touché

    $this->app->detectEnvironment(fn () => 'testing'); // restaure l'env avant le teardown des migrations
});

it('réinitialise la démo sur Marc à 86 % de façon déterministe et idempotente', function () {
    $this->artisan('demo:reset')->assertSuccessful();

    $marc = User::where('email', 'marc@rideready.test')->firstOrFail();
    expect((float) $marc->tires()->where('position', 'REAR')->where('is_active', true)->first()->wear_percent)->toBe(86.0)
        ->and($marc->stravaActivities()->count())->toBe(80);

    // Deuxième exécution → état identique, aucun doublon.
    $this->artisan('demo:reset')->assertSuccessful();
    expect(User::where('email', 'marc@rideready.test')->count())->toBe(1)
        ->and($marc->fresh()->stravaActivities()->count())->toBe(80);
});
