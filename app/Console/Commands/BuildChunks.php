<?php

namespace App\Console\Commands;

use App\Models\KnowledgeChunk;
use App\Models\Product;
use App\Support\ChunkBuilder;
use Illuminate\Console\Command;

class BuildChunks extends Command
{
    // --segment= permet de limiter à un seul segment pour la démo (ex: GRAVEL uniquement)
    protected $signature   = 'chunks:build {--segment= : Limiter à un segment (GRAVEL, ROAD, MTB, EBIKE_URBAN)}';
    protected $description = 'Génère les knowledge_chunks textuels depuis les produits (sans embedding — fait par chunks:embed)';

    public function handle(): int
    {
        $query = Product::query()->whereNotNull('segment');

        if ($segment = $this->option('segment')) {
            $query->where('segment', strtoupper($segment));
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            $this->warn('Aucun produit trouvé. Avez-vous exécuté catalog:import ou le seeder ?');
            return self::FAILURE;
        }

        $count = 0;

        foreach ($products as $product) {
            KnowledgeChunk::updateOrCreate(
                // Clé d'unicité : 1 chunk par produit par source
                ['product_id' => $product->id, 'source' => 'catalogue_2026'],
                [
                    'segment' => $product->segment,
                    'content' => ChunkBuilder::fromProduct($product),
                    'metadata' => [
                        'web_range_name' => $product->web_range_name,
                        'etrto'          => "{$product->width_etrto}-{$product->diameter_etrto}",
                    ],
                    // embedding_1536 laissé NULL : sera calculé par chunks:embed
                    'embedding_1536' => null,
                ],
            );
            $count++;
        }

        $this->info("✅ {$count} chunks générés (embeddings à calculer avec chunks:embed).");

        return self::SUCCESS;
    }
}