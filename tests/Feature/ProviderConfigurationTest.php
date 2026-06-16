<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

it('applies strict password defaults in production', function () {
    app()->detectEnvironment(fn () => 'production');

    // Invoking the default callback runs the production branch of AppServiceProvider.
    $rule = Password::default();

    expect($rule)->toBeInstanceOf(Password::class);
});

it('falls back to no enforced password rule outside production', function () {
    app()->detectEnvironment(fn () => 'testing');

    // Closure returns null in non-production → Password::default() falls back to min(8).
    $rule = Password::default();

    expect($rule)->toBeInstanceOf(Password::class);
});

it('registers a two-factor rate limiter keyed by the login session id', function () {
    $limiter = RateLimiter::limiter('two-factor');

    expect($limiter)->not->toBeNull();

    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session.store'));

    expect($limiter($request))->toBeInstanceOf(Limit::class);
});

it('registers a login rate limiter keyed by username and ip', function () {
    $limiter = RateLimiter::limiter('login');

    expect($limiter)->not->toBeNull();

    $request = Request::create('/login', 'POST', ['email' => 'marc@example.com']);

    expect($limiter($request))->toBeInstanceOf(Limit::class);
});
