<?php

namespace App\Support;

use App\DTO\RiderProfile;

class RagQueryBuilder
{
    /**
     * Traduit le profil inféré d'un cycliste en une phrase de recherche en langage naturel.
     *
     * Pourquoi une phrase et pas du JSON ?
     * Le modèle d'embedding (text-embedding-3-small) a été entraîné sur du langage naturel.
     * Lui passer du JSON ou des données brutes dégrade la qualité de la recherche sémantique.
     * On parle au modèle comme l'utilisateur l'aurait fait.
     *
     * Exemple de sortie :
     *   "pneu gravel pour usage mixte 60% route 40% chemin, longues sorties d'environ 150 km,
     *    style endurance régulière, montage tubeless"
     */
    public static function forRecommendation(RiderProfile $profile): string
    {
        $asphaltPct  = (int) ($profile->terrainPct['asphalt'] ?? 60);
        $offroadPct  = 100 - $asphaltPct;
        $segmentLower = strtolower($profile->segment->value);

        // On décrit le style de conduite en langage humain (pas l'enum brut)
        $styleLabel = $profile->ridingStyle->value === 'AGGRESSIF'
            ? 'roulage dynamique et intense'
            : 'endurance régulière et longue distance';

        return sprintf(
            'pneu %s pour usage mixte %d%% route %d%% chemin, style %s, montage tubeless',
            $segmentLower,
            $asphaltPct,
            $offroadPct,
            $styleLabel,
        );
    }
}