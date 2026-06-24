<?php

namespace App\Support;

use App\Models\Product;

class ChunkBuilder
{
    /**
     * Génère le texte factuel auto-suffisant d'un produit Michelin.
     *
     * Règles respectées (GUIDE-RAG-CATALOGUE.md §4.1) :
     *  - Le nom du produit est répété EN TÊTE : le LLM ne voit que le chunk, il doit savoir de quoi on parle.
     *  - Tous les chiffres présents viennent de la base (jamais inventés).
     *  - Les champs absents sont omis (pas de "n/c" qui polluent la recherche sémantique).
     *  - Longueur cible : 150–400 tokens (une fiche compacte et complète).
     */
    public static function fromProduct(Product $product): string
    {
        $parts = [];

        // En-tête : nom + segment + usage — la "carte d'identité" du pneu
        $parts[] = sprintf(
            '%s. Pneu vélo Michelin, segment %s, usage %s.',
            $product->web_range_name,
            $product->segment ?? 'non précisé',
            $product->use ?? 'polyvalent',
        );

        // Dimensions ETRTO (ex: "42-622, soit 700×42C, largeur 42 mm")
        if ($product->width_etrto && $product->diameter_etrto) {
            $parts[] = sprintf(
                'Dimensions ETRTO : %d-%d (largeur %d mm).',
                $product->width_etrto,
                $product->diameter_etrto,
                $product->width_etrto,
            );
        }

        // TPI — densité de trame : indicateur de légèreté et de souplesse
        if ($product->tpi) {
            $parts[] = sprintf('Carcasse TPI : %d.', $product->tpi);
        }

        // Plage de pression (champ critique pour la reco)
        if ($product->min_pressure_bar && $product->max_pressure_bar) {
            $parts[] = sprintf(
                'Pression recommandée : %.1f à %.1f bar.',
                $product->min_pressure_bar,
                $product->max_pressure_bar,
            );
        }

        // Terrains adaptés (ex: "ASPHALT, HARDPACKED")
        if (! empty($product->terrain_types)) {
            $parts[] = 'Terrains adaptés : ' . implode(', ', $product->terrain_types) . '.';
        }

        // Technologies Michelin (GUM-X, MAGI-X, Bead to Bead…)
        $techs = array_filter([
            $product->rubber_tech,
            $product->casing_tech,
            $product->reinforcement_tech,
            $product->ebike_tech,
        ]);
        if (! empty($techs)) {
            $parts[] = 'Technologies : ' . implode(', ', $techs) . '.';
        }

        // Valeurs métier — absentes du fichier Excel, enrichies manuellement pour la démo
        if ($product->expected_life_km) {
            $parts[] = sprintf('Durée de vie estimée : %d km.', $product->expected_life_km);
        }
        if ($product->rolling_resistance_watts) {
            $parts[] = sprintf('Résistance au roulement : ~%.1f W.', $product->rolling_resistance_watts);
        }

        return implode(' ', $parts);
    }
}