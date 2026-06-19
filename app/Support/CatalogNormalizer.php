<?php

namespace App\Support;

class CatalogNormalizer
{
    /**
     * Dérive le segment MÉTIER depuis les colonnes brutes du fichier Excel.
     *
     * Piège n°1 du guide : la colonne "Segment" du fichier contient des lignes de gamme
     * commerciale ("PREMIUM RACING LINE"…), PAS les segments métier de la spec.
     * Le segment réel se calcule depuis Cycle Type + Use.
     */
    public static function deriveSegment(?string $cycleType, ?string $use): ?string
    {
        $use   = mb_strtoupper(trim((string) $use));
        $cycle = mb_strtoupper(trim((string) $cycleType));

        // "GRAVEL" ou "E-GRAVEL" vit dans la colonne Use (ex: "RACING,E-GRAVEL")
        if (str_contains($use, 'GRAVEL')) {
            return 'GRAVEL';
        }

        return match ($cycle) {
            'ROAD' => 'ROAD',
            'MTB'  => 'MTB',
            'CITY' => 'EBIKE_URBAN',
            default => null,
        };
    }

    /**
     * Normalise les terrains bruts vers l'enum Surface de la spec.
     *
     * Piège n°2 du guide : la colonne "Terrain Types" est incohérente.
     * On peut y trouver : "OFFROAD MIXED", "ASPHALT, OFFROAD HARD PACKED", casses variées, vides.
     * Résultat attendu : ['ASPHALT', 'HARDPACKED', 'MIXED'] (enum propre, sans doublons).
     *
     * @return string[]
     */
    public static function normalizeTerrains(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        $result = [];

        foreach (explode(',', $raw) as $token) {
            $token = trim(mb_strtoupper($token));
            $token = str_replace('OFFROAD ', '', $token);  // "OFFROAD MIXED" → "MIXED"
            $token = str_replace(' ', '', $token);          // "HARD PACKED" → "HARDPACKED"

            $normalized = match ($token) {
                'ASPHALT'    => 'ASPHALT',
                'HARDPACKED' => 'HARDPACKED',
                'MIXED'      => 'MIXED',
                'SOFT'       => 'SOFT',
                'MUD'        => 'MUD',
                default      => null,
            };

            if ($normalized !== null && ! in_array($normalized, $result, true)) {
                $result[] = $normalized;
            }
        }

        return $result;
    }

    /**
     * Convertit une valeur de pression en float ou null.
     * Les cellules vides ou non-numériques du fichier doivent retourner null.
     */
    public static function toFloat(?string $value): ?float
    {
        $trimmed = trim((string) $value);

        return is_numeric($trimmed) ? (float) $trimmed : null;
    }
}