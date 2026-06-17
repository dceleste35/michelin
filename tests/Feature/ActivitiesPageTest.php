<?php

use App\Enums\Surface;
use App\Models\StravaActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

it('redirects guests to the login page', function () {
    $this->get(route('activities'))->assertRedirect(route('login'));
});

it('renders the activities page for an authenticated rider', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('activities'))->assertOk();
});

it('lists the rider activities with date, type, distance, elevation and surface', function () {
    $user = User::factory()->create();
    StravaActivity::factory()->for($user)->create([
        'sport_type' => 'GravelRide',
        'distance_m' => 42195,
        'total_elevation_gain_m' => 1234,
        'surface_derived' => Surface::Mixed,
        'start_date' => CarbonImmutable::parse('2026-03-15 07:30'),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->assertSee('Mar 15, 2026')   // date
        ->assertSee('Gravel Ride')    // humanised type
        ->assertSee('42.2 km')        // distance in km
        ->assertSee('1,234 m')        // elevation
        ->assertSee('Mixed');         // surface badge
});

it('only shows the authenticated rider own activities', function () {
    $marc = User::factory()->create();
    $other = User::factory()->create();

    StravaActivity::factory()->for($marc)->create(['distance_m' => 10000]);
    StravaActivity::factory()->for($other)->create(['distance_m' => 99000]);

    $this->actingAs($marc);

    Livewire::test('pages::activities')
        ->assertSee('10.0 km')
        ->assertDontSee('99.0 km');
});

it('shows an empty state when the rider has no activities', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::activities')
        ->assertSee('No activities yet')
        ->assertSee('0 rides imported from Strava')
        ->assertSee('Connect with Strava')
        ->assertSee(route('strava.connect'), false) // empty-state CTA links to the simulated connect
        ->assertDontSee('activities-table');
});

it('paginates activities at 20 per page, most recent first', function () {
    $user = User::factory()->create();

    foreach (range(1, 20) as $day) {
        StravaActivity::factory()->for($user)->create([
            'total_elevation_gain_m' => 100,
            'start_date' => CarbonImmutable::now()->subDays($day),
        ]);
    }

    // Oldest ride — must land on page 2 with a unique elevation marker.
    StravaActivity::factory()->for($user)->create([
        'total_elevation_gain_m' => 9999,
        'start_date' => CarbonImmutable::now()->subDays(365),
    ]);

    $this->actingAs($user);

    Livewire::test('pages::activities')
        ->assertSee('21 rides imported from Strava')
        ->assertDontSee('9,999 m')           // hidden on page 1
        ->call('gotoPage', 2)
        ->assertSee('9,999 m');              // visible on page 2
});
