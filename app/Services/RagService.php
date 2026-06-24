<?php

namespace App\Services;

use App\DTO\RiderProfile;
use Illuminate\Support\Facades\DB;

class RagService
{
    /**
     * On injecte EmbeddingService via le constructeur (injection de dépendance Laravel).
     * Fini le doublon HTTP — on délègue toujours à EmbeddingService pour obtenir un vecteur.
     */
    public function __construct(private readonly EmbeddingService $embedder) {}

    /**
     * Récupère les k chunks les plus proches d'une requête textuelle, filtrés par segment.
     *
     * Fonctionnement :
     *   1. La requête texte est convertie en vecteur via EmbeddingService (appel OpenAI).
     *   2. PostgreSQL calcule la distance cosinus entre ce vecteur et chaque chunk (opérateur <=>).
     *   3. On ne garde que les résultats dont la distance est < 0.55 (seuil anti-hallucination).
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    public function retrieve(string $query, ?string $segment = null, int $limit = 5): \Illuminate\Support\Collection
    {
        // 1. Transformer la question en vecteur (même modèle que lors de l'ingestion — obligatoire)
        $vectorArray  = $this->embedder->embed($query);
        $vectorString = '[' . implode(',', $vectorArray) . ']';

        return DB::table('knowledge_chunks')
            ->select('id', 'content', 'source', 'product_id', 'segment', 'metadata')
            // <=> = distance cosinus pgvector : petit = proche, grand = hors-sujet
            ->selectRaw('(embedding_1536 <=> ?::vector) AS distance', [$vectorString])
            ->when($segment, fn ($q) => $q->where('segment', $segment))
            ->whereNotNull('embedding_1536')
            // Seuil anti-hallucination : on préfère ne rien renvoyer plutôt qu'inventer
            ->havingRaw('(embedding_1536 <=> ?::vector) < 0.55', [$vectorString])
            ->orderBy('distance')
            ->limit($limit)
            ->get();
    }
}