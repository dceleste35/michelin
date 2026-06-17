<?php

namespace App\Services;

use App\DTO\RiderProfile;
use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Models\User;

class ProfileInferenceService
{
    /**
     * Build the rider profile for a user (UC-1).
     *
     * Contract stub (#10 / S1): returns the user's persisted profile fields with
     * safe GRAVEL defaults so consumers have a real entry point now. The actual
     * deterministic inference from `strava_activities` (segment, surface
     * derivation, terrain %, riding style) is implemented in #9 (S3).
     */
    public function buildProfile(User $user): RiderProfile
    {
        return new RiderProfile(
            segment: $user->segment ?? Segment::Gravel,
            weightKg: $user->weight_kg ?? 90,
            terrainPct: RiderProfile::mockMarc()->terrainPct,
            ridingStyle: $user->riding_style ?? RidingStyle::Endurance,
        );
    }
}
