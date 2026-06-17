<?php

use Illuminate\Support\Facades\DB;

it('runs all migrations cleanly on the test connection', function () {
    // RefreshDatabase a déjà migré ; la migration pgvector doit être
    // sans effet sur les pilotes non pgsql afin que la suite reste verte sur sqlite.
    expect(DB::getSchemaBuilder()->hasTable('migrations'))->toBeTrue();
});

it('configures a postgres connection for local + cloud', function () {
    $pgsql = config('database.connections.pgsql');

    expect($pgsql['driver'])->toBe('pgsql')
        ->and((string) $pgsql['port'])->toBe('5432')
        ->and($pgsql)->toHaveKey('url'); // piloté par l'environnement → compatible Laravel Cloud
});

it('wires the external service credentials for strava, anthropic and embeddings', function () {
    expect(config('services.strava'))
        ->toHaveKeys(['client_id', 'client_secret', 'redirect', 'base_url'])
        ->and(config('services.strava.base_url'))->toBe('https://www.strava.com/api/v3');

    expect(config('services.anthropic'))
        ->toHaveKeys(['key', 'base_url', 'model'])
        ->and(config('services.anthropic.base_url'))->toBe('https://api.anthropic.com');

    expect(config('services.embeddings'))
        ->toHaveKeys(['provider', 'key', 'base_url', 'model', 'dimensions'])
        ->and(config('services.embeddings.dimensions'))->toBeInt();
});

it('enables the pgvector extension on postgres', function () {
    if (DB::connection()->getDriverName() !== 'pgsql') {
        $this->markTestSkipped('Default connection is not pgsql (sqlite test env).');
    }

    $rows = DB::select("SELECT extname FROM pg_extension WHERE extname = 'vector'");

    expect($rows)->not->toBeEmpty();
});
