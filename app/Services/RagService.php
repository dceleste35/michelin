<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RagService
{
    /**
     * Appelle l'API d'embeddings pour transformer un texte en vecteur.
     *
     * @return float[]
     */
    public function embedText(string $text): array
    {
        // Remplacer par l'URL et la clé de l'API d'embedding que vous avez choisie (ex: OpenAI text-embedding-3-small)
        // La spec impose de passer par le HTTP client Laravel
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.embedding.key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.embedding.url'), [
            'input' => $text,
            'model' => 'votre-modele-1536-dimensions', 
        ]);

        if ($response->failed()) {
            throw new \Exception("Erreur lors de la génération de l'embedding.");
        }

        return $response->json('data.0.embedding');
    }

    /**
     * Recherche les documents les plus pertinents (Retrieval).
     * C'est le cœur du RAG : on récupère les faits pour les donner au LLM.
     */
    /**
     * @return array<int, \stdClass>
     */
    public function retrieve(string $query, string $segment, int $limit = 5): array
    {
        // 1. Transformer la question de l'utilisateur en vecteur
        $queryEmbedding = $this->embedText($query);
        $vectorString = '[' . implode(',', $queryEmbedding) . ']';

        // 2. Recherche par similarité cosinus (<=>) dans pgvector, avec un filtre métier fort (segment)
        $chunks = DB::table('knowledge_chunks')
            ->select('content', 'source', 'product_id')
            // Calcul de la distance cosinus
            ->selectRaw('embedding_1536 <=> ?::vector AS distance', [$vectorString])
            ->where('segment', $segment)
            ->whereNotNull('embedding_1536')
            // Seuil anti-hallucination : on ne prend que ce qui est assez proche (distance faible = grande similarité)
            ->having('distance', '<', 0.25) 
            ->orderBy('distance', 'asc')
            ->limit($limit)
            ->get();

        return $chunks->toArray();
    }
}