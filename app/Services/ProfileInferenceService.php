<?php

namespace App\Services;

use App\DTO\RiderProfile;
use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Enums\Surface;
use App\Models\StravaActivity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * UC-1 — deterministic rider-profile inference (pure SCORE, no AI).
 *
 * Analyses the user's last 6 months of `strava_activities` and classifies plain
 * numbers into intervals to infer segment, riding style and terrain mix. Every
 * rule is explainable to the jury and fully reproducible. This is a *smart
 * default*, never a verdict — the user can override it (segment_overridden).
 */
class ProfileInferenceService
{
    private const DEFAULT_SYSTEM_WEIGHT_KG = 90; // ~80 kg rider + ~10 kg gravel bike

    /**
     * Read the user's current (persisted) profile for downstream consumers
     * (Nolan's engine, Guillaume's UI). Terrain mix is computed live from the
     * last six months; segment/style/weight come from the persisted fields with
     * safe GRAVEL defaults when inference has not run yet.
     */
    public function buildProfile(User $user): RiderProfile
    {
        return new RiderProfile(
            segment: $user->segment ?? Segment::Gravel,
            weightKg: $user->weight_kg ?? self::DEFAULT_SYSTEM_WEIGHT_KG,
            terrainPct: $this->terrainDistribution($this->recentActivities($user)),
            ridingStyle: $user->riding_style ?? RidingStyle::Endurance,
        );
    }

    /**
     * Infer the full profile from the user's activities (no persistence).
     */
    public function infer(User $user): RiderProfile
    {
        $activities = $this->recentActivities($user);
        $weight = $user->weight_kg ?? self::DEFAULT_SYSTEM_WEIGHT_KG;

        return new RiderProfile(
            segment: $this->inferSegment($activities),
            weightKg: $weight,
            terrainPct: $this->terrainDistribution($activities),
            ridingStyle: $this->inferRidingStyle($activities, $weight),
        );
    }

    /**
     * Infer the profile and persist segment / riding_style / weight_kg on the
     * user. A user-overridden segment is preserved (segment_overridden).
     */
    public function inferAndPersist(User $user): RiderProfile
    {
        $profile = $this->infer($user);

        if (! $user->segment_overridden) {
            $user->segment = $profile->segment;
        }
        $user->riding_style = $profile->ridingStyle;
        $user->weight_kg = $profile->weightKg;
        $user->save();

        return $profile;
    }

    /**
     * Infer the dominant segment from the off-road share (last 6 months).
     *
     * - off-road > 70 %                  → MTB
     * - 15 % ≤ off-road ≤ 70 %           → GRAVEL
     * - off-road < 15 % (asphalt-heavy)  → ROAD (avg speed > 25 km/h confirms a
     *   competitive road profile; consumed by the style inference)
     * - majority EBikeRide               → EBIKE_URBAN
     *
     * @param  Collection<int, StravaActivity>  $activities
     */
    public function inferSegment(Collection $activities): Segment
    {
        if ($activities->isEmpty()) {
            return Segment::Gravel;
        }

        if ($activities->where('sport_type', 'EBikeRide')->count() / $activities->count() > 0.5) {
            return Segment::EbikeUrban;
        }

        $offRoad = $activities->filter(
            fn (StravaActivity $activity): bool => $this->surfaceOf($activity) !== Surface::Asphalt
        )->count();
        $offRoadShare = $offRoad / $activities->count();

        return match (true) {
            $offRoadShare > 0.70 => Segment::Mtb,
            $offRoadShare >= 0.15 => Segment::Gravel,
            default => Segment::Road,
        };
    }

    /**
     * Infer riding style from power-to-weight and pace variability.
     *
     * High watts per kilo OR high cross-ride speed dispersion (a proxy for
     * punchy, variable efforts) → AGGRESSIF; otherwise steady → ENDURANCE.
     *
     * @param  Collection<int, StravaActivity>  $activities
     */
    public function inferRidingStyle(Collection $activities, int $weightKg = self::DEFAULT_SYSTEM_WEIGHT_KG): RidingStyle
    {
        $withWatts = $activities->whereNotNull('average_watts');

        if ($withWatts->isEmpty()) {
            return RidingStyle::Endurance;
        }

        $wattsPerKg = (float) $withWatts->avg('average_watts') / max($weightKg, 1);
        $speedStdDev = $this->standardDeviation(
            $activities->map(fn (StravaActivity $activity): float => (float) $activity->average_speed_ms * 3.6)
        );

        return ($wattsPerKg >= 2.6 || $speedStdDev >= 4.0)
            ? RidingStyle::Aggressif
            : RidingStyle::Endurance;
    }

    /**
     * Derive an activity's surface from Strava signals.
     *
     * Strava exposes no surface field, so we infer it from documented, explainable
     * rules — an explicit hypothesis, not a black box (jury credibility point):
     *
     * - `Ride`/`VirtualRide` → ASPHALT. A road-bike activity is paved by definition
     *   (and virtual/indoor rides too), regardless of how hilly it is.
     * - `GravelRide` is dual-purpose, so we disambiguate by terrain:
     *     flat **and** fast (≤8 m/km climbing, >24 km/h) → ASPHALT — a road-pace outing;
     *     rolling (≤14 m/km) → HARDPACKED — typical groomed gravel;
     *     hillier → MIXED — climbing implies broken/technical ground.
     * - `MountainBikeRide`/`EMountainBikeRide` get rougher with elevation, and harsher
     *   when the pace collapses (a proxy for technical/wet ground):
     *     ≤15 m/km → MIXED; crawling (<12 km/h) → MUD; ≤30 m/km → SOFT; steeper → MUD.
     * - `EBikeRide` (urban assist) → ASPHALT. Any other sport → MIXED (safe default).
     *
     * Robust to missing/zero distance, speed or elevation: `max()` guards the divisor
     * and absent metrics read as 0, so no division-by-zero and no crash on edge cases.
     */
    public function deriveSurface(StravaActivity $activity): Surface
    {
        $distanceKm = max($activity->distance_m / 1000, 0.1);
        $elevationPerKm = $activity->total_elevation_gain_m / $distanceKm;
        $avgKmh = (float) $activity->average_speed_ms * 3.6;

        return match ($activity->sport_type) {
            'Ride', 'VirtualRide' => Surface::Asphalt,
            'GravelRide' => match (true) {
                $elevationPerKm <= 8 && $avgKmh > 24 => Surface::Asphalt, // flat & fast = road-like
                $elevationPerKm <= 14 => Surface::Hardpacked,
                default => Surface::Mixed,
            },
            'MountainBikeRide', 'EMountainBikeRide' => match (true) {
                $elevationPerKm <= 15 => Surface::Mixed,
                $avgKmh < 12 => Surface::Mud,         // crawling pace + climbing = technical/muddy
                $elevationPerKm <= 30 => Surface::Soft,
                default => Surface::Mud,
            },
            'EBikeRide' => Surface::Asphalt,
            default => Surface::Mixed,
        };
    }

    /**
     * Terrain mix as integer percentages per surface (keys always present).
     *
     * @param  Collection<int, StravaActivity>  $activities
     * @return array<string, int>
     */
    public function terrainDistribution(Collection $activities): array
    {
        $percentages = ['asphalt' => 0, 'hardpacked' => 0, 'mixed' => 0, 'soft' => 0, 'mud' => 0];

        if ($activities->isEmpty()) {
            return $percentages;
        }

        $counts = $percentages;
        foreach ($activities as $activity) {
            $counts[Str::lower($this->surfaceOf($activity)->name)]++;
        }

        $total = $activities->count();
        foreach ($counts as $surface => $count) {
            $percentages[$surface] = (int) round($count / $total * 100);
        }

        return $percentages;
    }

    /**
     * The user's activities over the inference window (last 6 months).
     *
     * @return Collection<int, StravaActivity>
     */
    private function recentActivities(User $user): Collection
    {
        return $user->stravaActivities()
            ->lastSixMonths()
            ->select([
                'id', 'user_id', 'sport_type', 'distance_m', 'moving_time_s',
                'average_speed_ms', 'total_elevation_gain_m', 'average_watts',
                'average_cadence', 'surface_derived', 'start_date',
            ])
            ->get();
    }

    /**
     * The activity's stored derived surface, deriving it on the fly when absent.
     */
    private function surfaceOf(StravaActivity $activity): Surface
    {
        return $activity->surface_derived ?? $this->deriveSurface($activity);
    }

    /**
     * Population standard deviation of a non-empty numeric collection.
     *
     * @param  Collection<int, float>  $values
     */
    private function standardDeviation(Collection $values): float
    {
        $mean = (float) $values->avg();
        $variance = $values->reduce(
            fn (float $carry, float $value): float => $carry + ($value - $mean) ** 2,
            0.0
        ) / $values->count();

        return sqrt($variance);
    }
}
