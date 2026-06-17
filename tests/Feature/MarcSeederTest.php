<?php

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Enums\TirePosition;
use App\Models\StravaActivity;
use App\Models\User;
use App\Models\UserTire;
use App\Services\ProfileInferenceService;
use Database\Seeders\MarcSeeder;
use Database\Seeders\ProductCatalogSeeder;

function seedMarc(): User
{
    test()->seed(ProductCatalogSeeder::class); // Marc monte un Power Gravel
    test()->seed(MarcSeeder::class);

    return User::where('email', 'marc@rideready.test')->sole();
}

it('seeds Marc as a GRAVEL endurance rider with ~80 GravelRide activities', function () {
    $marc = seedMarc();

    expect($marc->segment)->toBe(Segment::Gravel)
        ->and($marc->riding_style)->toBe(RidingStyle::Endurance)
        ->and($marc->weight_kg)->toBe(90)
        ->and($marc->strava_athlete_id)->toBe('42')
        ->and($marc->stravaActivities()->count())->toBe(80)
        ->and($marc->stravaActivities()->where('sport_type', '!=', 'GravelRide')->count())->toBe(0);
});

it('splits Marc surfaces ~60/40 asphalt/off-road', function () {
    $marc = seedMarc();

    $asphalt = $marc->stravaActivities()->where('surface_derived', Surface::Asphalt->value)->count();
    $offroad = $marc->stravaActivities()->whereIn('surface_derived', [Surface::Hardpacked->value, Surface::Mixed->value])->count();

    expect($asphalt)->toBe(48) // 60 %
        ->and($offroad)->toBe(32); // 40 %
});

it('derives every Marc surface from its ride signals (not hard-coded)', function () {
    $marc = seedMarc();
    $service = new ProfileInferenceService;

    // La surface stockée doit être reproductible à partir des signaux de l'activité via les
    // règles documentées — prouve au jury « nous la dérivons, nous ne la codons pas en dur ».
    $marc->stravaActivities()->get()->each(
        fn (StravaActivity $activity) => expect($activity->surface_derived)->toBe($service->deriveSurface($activity))
    );
});

it('stores each activity as a faithful Strava DetailedActivity raw_json', function () {
    $marc = seedMarc();
    $first = $marc->stravaActivities()->orderBy('id')->first();

    expect($first->raw_json)->toBeArray()
        ->and($first->raw_json['athlete']['id'])->toBe(42)
        ->and($first->raw_json['sport_type'])->toBe('GravelRide')
        ->and($first->raw_json['type'])->toBe('Ride')
        ->and($first->raw_json['distance'])->toEqual($first->distance_m)
        ->and($first->raw_json['name'])->toBeString()
        ->and($first->raw_json['start_date_local'])->toBeString()
        ->and($first->raw_json['map']['summary_polyline'])->toBeString()
        ->and($first->raw_json['gear_id'])->toBe($first->gear_id)
        ->and($first->raw_json['gear']['id'])->toBe($first->gear_id)
        ->and($first->raw_json['gear']['distance'])->toBeGreaterThan(0)
        ->and($first->raw_json['_derived']['surface'])->toBe($first->surface_derived->value);
});

it('attributes every Marc activity to a single bike (gear) for tire tracking', function () {
    $marc = seedMarc();

    $gearIds = $marc->stravaActivities()->pluck('gear_id');
    $odometerM = $marc->stravaActivities()->orderBy('id')->first()->raw_json['gear']['distance'];

    expect($gearIds)->toHaveCount(80)
        ->and($gearIds->unique()->values()->all())->toBe(['b9100042'])
        // Le compteur kilométrique du vélo dépasse la référence de montage du pneu (1200 km) → l'usure est calculable.
        ->and($odometerM)->toBeGreaterThan(1200 * 1000);
});

it('mounts a worn Power Gravel (rear 86 %, active) on Marc', function () {
    $marc = seedMarc();

    $rear = $marc->tires()->where('position', TirePosition::Rear->value)->sole();

    expect((float) $rear->wear_percent)->toBe(86.0)
        ->and($rear->is_active)->toBeTrue()
        ->and($rear->product->web_range_name)->toBe('Power Gravel')
        ->and($marc->tires()->where('is_active', true)->count())->toBe(2); // avant + arrière
});

it('is deterministic and idempotent (re-seeding does not duplicate)', function () {
    $marc = seedMarc();
    test()->seed(MarcSeeder::class); // relance

    expect($marc->stravaActivities()->count())->toBe(80)
        ->and(UserTire::where('user_id', $marc->id)->count())->toBe(2)
        ->and(User::where('email', 'marc@rideready.test')->count())->toBe(1);
});

it('factory builds a gravel activity with raw_json consistent with its columns', function () {
    $activity = StravaActivity::factory()->create();

    expect($activity->sport_type)->toBe('GravelRide')
        ->and($activity->raw_json['distance'])->toEqual($activity->distance_m)
        ->and($activity->raw_json['moving_time'])->toEqual($activity->moving_time_s)
        ->and($activity->raw_json['_derived']['surface'])->toBe($activity->surface_derived->value)
        ->and($activity->raw_json['athlete']['id'])->toBeInt()
        ->and($activity->raw_json['gear_id'])->toBe($activity->gear_id);
});
