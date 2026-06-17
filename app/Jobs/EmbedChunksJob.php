<?php

namespace App\Jobs;

use App\Services\EmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class EmbedChunksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(EmbeddingService $embedder): void
    {
        // On cible uniquement les chunks qui n'ont pas encore de vecteur (idempotence)
        DB::table('knowledge_chunks')
            ->whereNull('embedding_1536')
            ->orderBy('id')
            ->chunk(50, function ($chunks) use ($embedder) {
                foreach ($chunks as $chunk) {
                    try {
                        // 1. Appel de l'API OpenAI via ton service
                        $vectorArray = $embedder->embed($chunk->content);
                        
                        // 2. Formatage explicite du tableau en string pour la colonne vector de PostgreSQL
                        $vectorString = '[' . implode(',', $vectorArray) . ']';

                        // 3. Mise à jour de la ligne en base
                        DB::table('knowledge_chunks')
                            ->where('id', $chunk->id)
                            ->update([
                                'embedding_1536' => $vectorString,
                                'updated_at' => now(),
                            ]);
                    } catch (\Exception $e) {
                        // On loggue l'erreur mais on ne bloque pas la boucle entière pour un seul échec
                        \Illuminate\Support\Facades\Log::error("Erreur d'embedding pour le chunk ID {$chunk->id} : " . $e->getMessage());
                    }
                }
            });
    }
}