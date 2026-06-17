<?php

use App\Enums\Segment;
use App\Models\User;
use Database\Seeders\MarcSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Livewire\Livewire;

function marcUser(): User
{
    test()->seed(ProductCatalogSeeder::class);
    test()->seed(MarcSeeder::class);

    return User::where('email', 'marc@rideready.test')->sole();
}

it('redirects guests to the login page', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

it('shows the inferred smart default for Marc', function () {
    $this->actingAs(marcUser());

    Livewire::test('pages::profile')
        ->assertSee('We figured out your profile')
        ->assertSee('a Gravel rider')
        ->assertSee('steady, long distance')
        ->assertSee('60% road / 40% trail');
});

it('infers and persists the profile on mount', function () {
    $marc = marcUser();
    $marc->update(['segment' => null, 'riding_style' => null, 'weight_kg' => null, 'segment_overridden' => false]);

    $this->actingAs($marc->fresh());
    Livewire::test('pages::profile');

    $marc->refresh();
    expect($marc->segment)->toBe(Segment::Gravel)
        ->and($marc->riding_style->value)->toBe('ENDURANCE')
        ->and($marc->weight_kg)->toBe(90);
});

it('confirms the smart default once and continues to the activities', function () {
    $marc = marcUser();
    $this->actingAs($marc);

    Livewire::test('pages::profile')
        ->call('confirm')
        ->assertRedirect(route('activities'));

    expect($marc->refresh()->profile_confirmed_at)->not->toBeNull(); // recorded so onboarding is shown only once
});

it('lets the rider override the segment via Adjust and flags it', function () {
    $marc = marcUser();
    $this->actingAs($marc);

    Livewire::test('pages::profile')
        ->assertSet('adjusting', false)
        ->call('adjust')
        ->assertSet('adjusting', true)
        ->set('segment', Segment::Road->value);

    $marc->refresh();
    expect($marc->segment)->toBe(Segment::Road)
        ->and($marc->segment_overridden)->toBeTrue();
});

it('keeps a user-overridden segment instead of re-inferring', function () {
    $marc = marcUser();
    $marc->update(['segment' => Segment::Road, 'segment_overridden' => true]);

    $this->actingAs($marc->fresh());

    Livewire::test('pages::profile')->assertSee('a Road rider');

    expect($marc->refresh()->segment)->toBe(Segment::Road); // not overwritten by inference
});

it('updates the system weight from the slider', function () {
    $marc = marcUser();
    $this->actingAs($marc);

    Livewire::test('pages::profile')->set('weightKg', 75);

    expect($marc->refresh()->weight_kg)->toBe(75);
});

it('rejects an out-of-range system weight', function () {
    $this->actingAs(marcUser());

    Livewire::test('pages::profile')
        ->set('weightKg', 999)
        ->assertHasErrors('weightKg');
});
