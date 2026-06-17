<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // On s'assure que l'extension pgvector est activée
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector;');

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source'); 
            $table->string('segment')->nullable(); 
            // Toujours en mode "débloqué" sans clé étrangère stricte
            $table->unsignedBigInteger('product_id')->nullable();
            $table->text('content'); 
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });

        // Ajout de la colonne vector(1536) en SQL pur pour garantir la dimension
        DB::statement('ALTER TABLE knowledge_chunks ADD COLUMN embedding_1536 vector(1536)');
        
        // ATTENTION : On retire la création de l'index HNSW d'ici. 
        // Il devra être lancé à la main ou via une autre commande APRÈS l'EmbedChunksJob.
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};