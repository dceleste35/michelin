<?php

namespace App\DTO;

use App\Enums\RidingStyle;
use App\Enums\Segment;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * Contrat partagé du profil du cycliste (UC-1).
 *
 * Producteur : Dan (ProfileInferenceService). Consommateurs : Nolan (moteur), Guillaume (UI).
 * Gelé en S1 afin que B & C puissent coder dessus pendant la construction de l'inférence (#9 / S3).
 *
 * Forme JSON :
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
     * @param  array<string, int|float>  $terrainPct  clé de surface => pourcentage (0–100), totalisant ~100
     */
    public function __construct(
        public Segment $segment,
        public int $weightKg,
        public array $terrainPct,
        public RidingStyle $ridingStyle,
    ) {}

    /**
     * Mock représentatif du cycliste vedette (Marc, GRAVEL, 60/40 asphalte/hors-route),
     * afin que Nolan & Guillaume puissent développer sur le contrat avant l'arrivée de l'inférence (#9).
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
