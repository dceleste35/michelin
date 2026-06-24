<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int         $id
 * @property string      $source       Source du chunk : 'catalogue_2026', 'fiche_redigee'…
 * @property string|null $segment      Segment métier : GRAVEL | ROAD | MTB | EBIKE_URBAN
 * @property int|null    $product_id   Lien optionnel vers la table products
 * @property string      $content      Le texte factu­el qui sera embedé et renvoyé au LLM
 * @property array|null  $metadata     Données structurées annexes (web_range_name, etrto…)
 * @property string|null $embedding_1536 Colonne pgvector : 1536 floats (NULL tant que non embedé)
 */
#[Fillable(['source', 'segment', 'product_id', 'content', 'metadata', 'embedding_1536'])]
class KnowledgeChunk extends Model
{
    protected function casts(): array
    {
        return [
            // metadata est stocké en JSON dans la base, on veut un array PHP à la lecture
            'metadata' => 'array',
        ];
    }

    /**
     * Un chunk peut pointer vers un produit du catalogue (nullable pour les chunks transverses).
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}