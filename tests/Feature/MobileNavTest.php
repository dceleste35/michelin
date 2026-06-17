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
