<?php

use App\DTO\RiderProfile;
use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Models\User;
use App\Services\ProfileInferenceService;

it('serializes RiderProfile to the agreed JSON shape', function () {
    $profile = new RiderProfile(
        segment: Segment::Gravel,
        weightKg: 90,
        terrainPct: ['asphalt' => 60, 'hardpacked' => 20, 'mixed' => 15, 'soft' => 5, 'mud' => 0],
        ridingStyle: RidingStyle::Endurance,
    );

    $expected = [
        'segment' => 'GRAVEL',
        'weight_kg' => 90,
        'terrain_pct' => ['asphalt' => 60, 'hardpacked' => 20, 'mixed' => 15, 'soft' => 5, 'mud' => 0],
        'riding_style' => 'ENDURANCE',
    ];

    expect($profile->toArray())->toBe($expected)
        ->and(json_decode(json_encode($profile), true))->toBe($expected);
});

it('provides a representative Marc mock for parallel dev (60/40 gravel)', function () {
    $marc = RiderProfile::mockMarc();

    expect($marc->segment)->toBe(Segment::Gravel)
        ->and($marc->ridingStyle)->toBe(RidingStyle::Endurance)
        ->and($marc->weightKg)->toBe(90)
        ->and(array_sum($marc->terrainPct))->toBe(100)
        ->and($marc->terrainPct['asphalt'])->toBe(60); // 60 % asphalte / 40 % tout-terrain
});

it('builds the profile from a user with inferred fields persisted', function () {
    $user = User::factory()->create([
        'segment' => 'ROAD',
        'weight_kg' => 72,
        'riding_style' => 'AGGRESSIF',
    ]);

    $profile = app(ProfileInferenceService::class)->buildProfile($user);

    expect($profile->segment)->toBe(Segment::Road)
        ->and($profile->weightKg)->toBe(72)
        ->and($profile->ridingStyle)->toBe(RidingStyle::Aggressif);
});

it('falls back to gravel/90kg defaults for a not-yet-inferred user', function () {
    $user = User::factory()->create(); // pas encore de segment / poids / style

    $profile = app(ProfileInferenceService::class)->buildProfile($user);

    expect($profile->segment)->toBe(Segment::Gravel)
        ->and($profile->weightKg)->toBe(90)
        ->and($profile->ridingStyle)->toBe(RidingStyle::Endurance)
        ->and($profile->terrainPct)->toHaveKey('asphalt');
});
