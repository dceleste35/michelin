<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    /**
     * Renvoie un vecteur de 1536 floats pour un texte donné.
     */
    public function embed(string $text): array
    {
        $response = Http::withToken(config('services.openai.key'))
            ->timeout(30)
            ->post('https://api.openai.com/v1/embeddings', [
                'model' => config('services.openai.embeddings_model', 'text-embedding-3-small'),
                'input' => $text,
            ])
            ->throw();

        return $response->json('data.0.embedding');
    }
}