<?php

namespace App\Console\Commands;

use App\Jobs\EmbedChunksJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EmbedChunks extends Command
{
    protected $signature   = 'chunks:embed';
    protected $description = 'Calcule les embeddings pour tous les chunks sans vecteur, puis crée l\'index HNSW';

    public function handle(): int
    {
        $pending = DB::table('knowledge_chunks')->whereNull('embedding_1536')->count();

        if ($pending === 0) {
            $this->info('✅ Tous les chunks ont déjà un embedding. Rien à faire.');
            return self::SUCCESS;
        }

        $this->info("⏳ {$pending} chunks à embedder…");

        // dispatchSync() = exécution immédiate dans le processus courant (pas de worker de queue)
        // Parfait pour la démo : on voit la progression directement dans le terminal.
        EmbedChunksJob::dispatchSync();

        $this->info('✅ Embeddings calculés.');

        // L'index HNSW DOIT être créé APRÈS que les vecteurs soient en base.
        // Indexer une colonne vide ne sert à rien et l'index ne se met pas à jour automatiquement.
        $this->info('⏳ Création de l\'index HNSW (accélérateur de recherche cosinus)…');

        DB::statement('
            CREATE INDEX IF NOT EXISTS knowledge_chunks_embedding_hnsw
            ON knowledge_chunks
            USING hnsw (embedding_1536 vector_cosine_ops)
        ');

        $this->info('✅ Index HNSW créé. Le pipeline RAG est opérationnel.');

        return self::SUCCESS;
    }
}