<?php

namespace App\DTO;

use App\Enums\RidingStyle;
use App\Enums\Segment;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Shared rider-profile contract (UC-1).
 *
 * Producer: Dan (ProfileInferenceService). Consumers: Nolan (moteur), Guillaume (UI).
 * Frozen in S1 so B & C can code against it while the inference is built (#9 / S3).
 *
 * JSON shape:
 *   {
 *     "segment": "GRAVEL",
 *     "weight_kg": 90,
 *     "terrain_pct": { "asphalt": 60, "hardpacked": 20, "mixed": 15, "soft": 5, "mud": 0 },
 *     "riding_style": "ENDURANCE"
 *   }
 *
 * @implements Arrayable<string, mixed>
 */
final readonly class RiderProfile implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, int|float>  $terrainPct  surface key => percent (0–100), summing ~100
     */
    public function __construct(
        public Segment $segment,
        public int $weightKg,
        public array $terrainPct,
        public RidingStyle $ridingStyle,
    ) {}

    /**
     * Representative mock of the hero rider (Marc, GRAVEL, 60/40 asphalt/off-road),
     * so Nolan & Guillaume can build against the contract before the inference lands (#9).
     */
    public static function mockMarc(): self
    {
        return new self(
            segment: Segment::Gravel,
            weightKg: 90,
            terrainPct: ['asphalt' => 60, 'hardpacked' => 20, 'mixed' => 15, 'soft' => 5, 'mud' => 0],
            ridingStyle: RidingStyle::Endurance,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'segment' => $this->segment->value,
            'weight_kg' => $this->weightKg,
            'terrain_pct' => $this->terrainPct,
            'riding_style' => $this->ridingStyle->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
