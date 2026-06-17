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

it('first connect signs in as Marc and routes to the onboarding smart default', function () {
    $marc = seedDemoMarc(); // profile_confirmed_at null → première fois

    $this->get(route('strava.connect'))
        ->assertOk()
        ->assertSee('Connecting to Strava', false)
        ->assertSee(route('profile'), false); // cible de l'onboarding

    $this->assertAuthenticatedAs($marc);
});

it('connect skips onboarding and goes to activities once the profile is confirmed', function () {
    $marc = seedDemoMarc();
    $marc->profile_confirmed_at = now();
    $marc->save();

    $this->get(route('strava.connect'))
        ->assertOk()
        ->assertSee(route('activities'), false)   // directement vers les activités
        ->assertDontSee(route('profile'), false); // ne redemande plus
});

it('connect errors and stays guest when the demo profile is not seeded', function () {
    $this->get(route('strava.connect'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('strava');

    $this->assertGuest();
});
