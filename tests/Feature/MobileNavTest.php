<?php

use App\Models\User;

it('shows the mobile bottom navigation to authenticated users', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('activities'))
        ->assertOk()
        ->assertSee('data-test="mobile-bottom-nav"', false)
        ->assertSee('data-test="mobile-nav-dashboard"', false)
        ->assertSee('data-test="mobile-nav-activities"', false)
        ->assertSee('data-test="mobile-nav-profile"', false)
        ->assertSee(route('dashboard'), false)
        ->assertSee(route('profile'), false);
});

it('does not show the app navigation to guests', function () {
    $this->get(route('login'))->assertDontSee('data-test="mobile-bottom-nav"', false);
});

it('paints the mobile status bar in Michelin blue via theme-color', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('activities'))
        ->assertOk()
        ->assertSee('<meta name="theme-color" content="#27509b" />', false)
        ->assertSee('viewport-fit=cover', false);
});

it('uses a clean mobile top bar without the web hamburger', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('activities'))
        ->assertOk()
        ->assertSee('michelin-logo.png', false) // logo Michelin dans la top-bar mobile
        ->assertDontSee('bars-2', false);        // plus de bouton hamburger web
});
