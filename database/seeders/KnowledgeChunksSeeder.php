<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KnowledgeChunksSeeder extends Seeder
{
    public function run(): void
    {
        $chunks = [
            // --- FICHE PRODUIT : POWER GRAVEL RS ---
            [
                'source' => 'fiche_technique_power_gravel_rs',
                'segment' => 'GRAVEL',
                'content' => 'MICHELIN Power Gravel RS Racing Line est conçu spécifiquement pour la compétition et la performance sur les parcours mixtes. Dimension disponible : 700x42C (42-622). Il dispose d\'une carcasse TPI de 3x120 pour une légèreté et une réactivité maximale sur l\'asphalte et les chemins damés.',
                'metadata' => json_encode(['range' => 'Racing Line', 'tpi' => '3x120'])
            ],
            [
                'source' => 'fiche_technique_power_gravel_rs',
                'segment' => 'GRAVEL',
                'content' => 'Le pneu MICHELIN Power Gravel RS utilise le mélange de gomme exclusif GUM-X Technology. Cette technologie garantit un excellent rendement de roulement (économie estimée à environ 4 watts par rapport au Power Gravel classique) tout en maintenant un haut niveau de grip sur sol humide.',
                'metadata' => json_encode(['compound' => 'GUM-X', 'watts_saved' => 4])
            ],
            [
                'source' => 'fiche_technique_power_gravel_rs',
                'segment' => 'GRAVEL',
                'content' => 'La plage de pression homologuée pour le MICHELIN Power Gravel RS en taille 700x42C est de 2,0 à 4,0 bar (29-58 Psi). Pour un montage Tubeless Ready (TLR), il est fortement recommandé de descendre la pression entre 2,2 et 2,8 bar selon le poids du pilote pour optimiser le confort et éviter les crevaisons par pincement.',
                'metadata' => json_encode(['min_press' => 2.0, 'max_press' => 4.0])
            ],

            // --- FICHE PRODUIT : POWER GRAVEL (CLASSIQUE) ---
            [
                'source' => 'fiche_technique_power_gravel',
                'segment' => 'GRAVEL',
                'content' => 'Le MICHELIN Power Gravel de la gamme Performance Line est le pneu polyvalent par excellence pour un usage 60% route et 40% chemins. Il offre une excellente longévité grâce à son renfort anti-crevaison de tringle à tringle (Bead to Bead Shield) qui protège l\'intégralité de la carcasse.',
                'metadata' => json_encode(['range' => 'Performance Line', 'protection' => 'Bead to Bead Shield'])
            ],
            [
                'source' => 'fiche_technique_power_gravel',
                'segment' => 'GRAVEL',
                'content' => 'La longévité kilométrique de référence du pneu MICHELIN Power Gravel classique est estimée à 4 000 km en usage mixte normalisé, avant d\'atteindre le témoin d\'usure ou une dégradation critique de la bande de roulement centrale.',
                'metadata' => json_encode(['expected_life_km' => 4000])
            ],

            // --- FICHE PRODUIT : POWER ADVENTURE ---
            [
                'source' => 'fiche_technique_power_adventure',
                'segment' => 'GRAVEL',
                'content' => 'Le pneu MICHELIN Power Adventure s\'adresse aux cyclistes recherchant un pneu à profil "All Road", idéal pour 80% d\'asphalte et 20% de chemins stabilisés. Sa bande de roulement centrale lisse privilégie un rendement maximal proche d\'un pneu de route.',
                'metadata' => json_encode(['usage' => 'All Road', 'terrain_dominant' => 'ASPHALT'])
            ],

            // --- GLOSSAIRE TECHNOLOGIES ---
            [
                'source' => 'glossaire_technologies_michelin',
                'segment' => 'TRANSVERSE',
                'content' => 'La technologie GUM-X de Michelin désigne une série de mélanges de gommes composites qui associent de manière optimale le rendement, l\'adhérence (grip) et la longévité de la bande de roulement. Elle est particulièrement efficace sur les relances et le comportement en virage sur sol mixte.',
                'metadata' => json_encode(['tech' => 'GUM-X'])
            ],
            [
                'source' => 'glossaire_technologies_michelin',
                'segment' => 'TRANSVERSE',
                'content' => 'Le renfort Bead to Bead Shield (Bouclier de tringle à tringle) est une nappe de protection haute densité croisée qui recouvre la totalité de la carcasse du pneu, des flancs jusqu\'au sommet. Cela confère une résistance maximale contre les coupures par silex ou roches saillantes.',
                'metadata' => json_encode(['tech' => 'Bead to Bead Shield'])
            ],

            // --- CONSEILS D'USAGE ET ENTIRETIEN ---
            [
                'source' => 'guide_entretien_pression',
                'segment' => 'GRAVEL',
                'content' => 'En Gravel, le sur-gonflage est une cause fréquente de perte de performance. Un pneu gonflé à plus de 3,5 bar sur un chemin caillouteux va rebondir, ce qui augmente la résistance au roulement globale et dégrade drastiquement le grip ainsi que le confort du cycliste.',
                'metadata' => json_encode(['topic' => 'pression'])
            ]
        ];

        foreach ($chunks as $chunk) {
            DB::table('knowledge_chunks')->updateOrInsert(
                [
                    'source' => $chunk['source'], 
                    // Recherche sur un extrait significatif du contenu pour éviter les doublons au ré-exécution
                    'content' => $chunk['content']
                ],
                [
                    'segment' => $chunk['segment'],
                    'product_id' => $chunk['product_id'] ?? null,
                    'metadata' => $chunk['metadata'],
                    'embedding_1536' => null, // Sera calculé par le job d'ingestion
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}