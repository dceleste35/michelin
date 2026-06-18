<?php

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

// `demo:reset` réinitialise au niveau données (db:seed) sans migrate:fresh — il marche donc
// aussi en production. On garde DatabaseMigrations ici pour isoler le seeding de ce test du
// reste de la suite (pas de transaction enveloppante qui interférerait avec les seeders).
uses(TestCase::class, DatabaseMigrations::class);

it('refuse de réinitialiser la démo en production sans --force', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->artisan('demo:reset')->assertFailed();

    expect(User::where('email', 'marc@rideready.test')->exists())->toBeFalse(); // rien n'a été touché

    $this->app->detectEnvironment(fn () => 'testing'); // restaure l'env avant le teardown des migrations
});

it('réinitialise la démo sur un premier arrivant (Strava connecté, sorties, aucun pneu) et reste idempotent', function () {
    $this->artisan('demo:reset')->assertSuccessful();

    $marc = User::where('email', 'marc@rideready.test')->firstOrFail();
    expect($marc->strava_athlete_id)->not->toBeNull()       // connecté à Strava
        ->and($marc->profile_confirmed_at)->toBeNull()       // profil pas encore confirmé (onboarding)
        ->and($marc->stravaActivities()->count())->toBeGreaterThan(0) // sorties déjà importées
        ->and($marc->tires()->count())->toBe(0);             // aucun pneu

    // Deuxième exécution → état identique, aucun doublon, toujours sans pneu.
    $this->artisan('demo:reset')->assertSuccessful();
    expect(User::where('email', 'marc@rideready.test')->count())->toBe(1)
        ->and($marc->fresh()->tires()->count())->toBe(0);
});
