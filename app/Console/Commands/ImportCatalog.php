<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\CatalogNormalizer as N;
use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportCatalog extends Command
{
    // Le chemin par défaut pointe vers le fichier à la racine du repo (hors source_code/)
    protected $signature   = 'catalog:import {path=../Ressources/2W Bicycle Product Catalog v4 - 2026.xlsx}';
    protected $description = 'Importe le catalogue Excel Michelin dans la table products (segment dérivé, terrains normalisés)';

    public function handle(): int
    {
        $path = base_path($this->argument('path'));

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $this->info("📂 Lecture du fichier : {$path}");

        $rows = IOFactory::load($path)->getActiveSheet()->toArray();
        array_shift($rows); // Supprimer la ligne d'en-tête

        // Convertit une lettre de colonne Excel (A, B, … BS) en index 0-based
        $col = static function (string $letter): int {
            return array_reduce(
                str_split($letter),
                static fn (int $carry, string $char) => $carry * 26 + (ord($char) - 64),
                0,
            ) - 1;
        };

        $imported = 0;

        foreach ($rows as $row) {
            // On ne garde que les TYRE (pas les TUBE ni TUBULAR — piège n°2)
            if (mb_strtoupper(trim((string) ($row[$col('C')] ?? ''))) !== 'TYRE') {
                continue;
            }

            $webRangeName = trim((string) ($row[$col('AJ')] ?? ''));

            if (blank($webRangeName)) {
                continue;
            }

            $segment = N::deriveSegment(
                $row[$col('D')] ?? null,  // Cycle Type
                $row[$col('BD')] ?? null, // Use
            );

            // updateOrCreate sur le nom : 1 produit logique par modèle, pas 1 par dimension ETRTO
            // La 1ʳᵉ dimension rencontrée fait foi — les suivantes sont ignorées (piège n°3)
            Product::updateOrCreate(
                ['web_range_name' => $webRangeName],
                [
                    'global_id'          => $row[$col('A')] ?? null,
                    'segment'            => $segment,
                    'width_etrto'        => (int) ($row[$col('K')] ?? 0) ?: null,
                    'diameter_etrto'     => (int) ($row[$col('L')] ?? 0) ?: null,
                    'tpi'                => (int) filter_var($row[$col('AR')] ?? '', FILTER_SANITIZE_NUMBER_INT) ?: null,
                    'min_pressure_bar'   => N::toFloat($row[$col('AS')] ?? null),
                    'max_pressure_bar'   => N::toFloat($row[$col('AT')] ?? null),
                    'rubber_tech'        => $row[$col('BE')] ?? null,
                    'casing_tech'        => $row[$col('BF')] ?? null,
                    'reinforcement_tech' => $row[$col('BH')] ?? null,
                    'ebike_tech'         => $row[$col('BI')] ?? null,
                    'terrain_types'      => N::normalizeTerrains($row[$col('BC')] ?? null),
                    'use'                => $row[$col('BD')] ?? null,
                    'weight_g'           => (int) ($row[$col('V')] ?? 0) ?: null,
                    'ean_code'           => $row[$col('Q')] ?? null,
                ],
            );

            $imported++;
        }

        $this->info("✅ {$imported} références TYRE importées (regroupées par Web Range Name).");

        return self::SUCCESS;
    }
}   