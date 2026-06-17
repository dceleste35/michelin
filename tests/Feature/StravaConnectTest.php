<?php

use App\Models\User;

function seedDemoMarc(): User
{
    return User::factory()->create(['email' => 'marc@rideready.test']);
}

it('shows the Connect with Strava button on the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Connect with Strava')
        ->assertSee(route('strava.connect'), false);
});

it('simulated connect signs in as Marc and shows the Strava interstitial', function () {
    $marc = seedDemoMarc();

    $this->get(route('strava.connect'))
        ->assertOk()
        ->assertSee('Connecting to Strava', false)
        ->assertSee(route('activities'), false); // interstitial meta-refresh target

    $this->assertAuthenticatedAs($marc);
});

it('connect errors and stays guest when the demo profile is not seeded', function () {
    $this->get(route('strava.connect'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('strava');

    $this->assertGuest();
});
