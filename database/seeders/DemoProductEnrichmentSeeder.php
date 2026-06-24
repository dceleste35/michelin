<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DemoProductEnrichmentSeeder extends Seeder
{
    /**
     * Enrichit les produits de la démo avec les valeurs métier absentes du fichier Excel.
     * Ces valeurs sont des références catalogue/labo — à assumer au jury.
     */
    public function run(): void
    {
        $enrichments = [
            'MICHELIN POWER GRAVEL RS RACING LINE' => [
                'expected_life_km'        => 4200,
                'rolling_resistance_watts' => 14.0,
                'price_eur'               => 59.90,
            ],
            'MICHELIN POWER GRAVEL COMPETITION LINE' => [
                'expected_life_km'        => 4000,
                'rolling_resistance_watts' => 18.0,
                'price_eur'               => 49.90,
            ],
        ];

        foreach ($enrichments as $name => $data) {
            $updated = Product::where('web_range_name', $name)->update($data);

            if ($updated === 0) {
                $this->command->warn("Produit introuvable : {$name} — avez-vous exécuté catalog:import ?");
            }
        }

        $this->command->info('✅ Enrichissement démo appliqué.');
    }
}