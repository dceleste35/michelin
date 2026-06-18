<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // pgvector n'existe que sous PostgreSQL ; en test (SQLite) on s'en passe.
        $isPostgres = Schema::getConnection()->getDriverName() === 'pgsql';

        // L'extension est déjà activée par la migration dédiée ; on la garde par sécurité côté pgsql.
        if ($isPostgres) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');
        }

        Schema::create('knowledge_chunks', function (Blueprint $table) use ($isPostgres) {
            $table->id();
            $table->string('source');
            $table->string('segment')->nullable();
            // Toujours en mode "débloqué" sans clé étrangère stricte
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('content');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            // Hors PostgreSQL, on stocke l'embedding en texte pour garder la table cohérente.
            if (! $isPostgres) {
                $table->text('embedding_1536')->nullable();
            }
        });

        // Colonne vector(1536) en SQL pur pour garantir la dimension (PostgreSQL uniquement).
        if ($isPostgres) {
            DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding_1536 vector(1536)');
        }

        // ATTENTION : On retire la création de l'index HNSW d'ici.
        // Il devra être lancé à la main ou via une autre commande APRÈS l'EmbedChunksJob.
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
