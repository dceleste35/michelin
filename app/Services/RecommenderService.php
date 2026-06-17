<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RecommenderService
{
    public function __construct(
        private RagService $ragService,
        private LlmService $llmService
    ) {}

    /**
     * Génère et met en cache une recommandation pour un pneu arrivant en fin de vie.
     */
    /**
     * @return array{
     *     recommendation_id: int,
     *     recommended_tire: string,
     *     justification: string
     * }
     */
    public function generateRecommendation(int $userTireId): array
    {
        // 1. Récupérer les infos du pneu actuel et du profil utilisateur
        $currentTireData = DB::table('user_tires')
            ->join('users', 'user_tires.user_id', '=', 'users.id')
            ->join('products', 'user_tires.product_id', '=', 'products.id')
            ->where('user_tires.id', $userTireId)
            ->select('users.id as user_id', 'users.weight_kg', 'users.riding_style', 'products.segment', 'products.web_range_name as current_tire_name', 'user_tires.product_id')
            ->first();

        if (!$currentTireData) {
            throw new \Exception("Pneu utilisateur introuvable.");
        }

        // 2. Logique de Recommandation DÉTERMINISTE (SCORE)
        // Pour le MVP (Scénario de Marc), on recommande directement la montée en gamme Gravel RS
        $recommendedProduct = DB::table('products')
            ->where('web_range_name', 'MICHELIN POWER GRAVEL RS RACING LINE')
            ->first();

        // 3. Récupérer les faits produit factuels via le RAG
        // On interroge la base vectorielle avec un filtre strict sur le segment
        $ragChunks = $this->ragService->retrieve(
            "comparatif gravel mixte longue distance et rendement watts", 
            $currentTireData->segment, 
            3 // On prend les 3 meilleurs chunks
        );
        $factsCatalogue = collect($ragChunks)->pluck('content')->implode(' | ');

        // 4. Préparer le "Prompt Augmenté" (Contexte) pour le LLM
        $facts = [
            'pneu_actuel'         => $currentTireData->current_tire_name,
            'pneu_recommande'     => $recommendedProduct->web_range_name ?? 'Nouveau Pneu Michelin',
            'profil_utilisateur'  => "Segment {$currentTireData->segment}, poids {$currentTireData->weight_kg}kg, style {$currentTireData->riding_style}",
            'faits_catalogue_RAG' => $factsCatalogue,
        ];

        // 5. Appel au LLM (Haiku) pour rédiger la justification comparative
        $proseMotivante = $this->llmService->writeJustification($facts);

        // 6. Mise en cache de la recommandation (Pour ne pas rappeler l'API à chaque affichage)
        $recommendationId = DB::table('recommendations')->insertGetId([
            'user_id'                => $currentTireData->user_id,
            'current_product_id'     => $currentTireData->product_id ?? null,
            'recommended_product_id' => $recommendedProduct->id ?? null,
            'rationale_json'         => json_encode([
                'texte_llm' => $proseMotivante,
                // On pourrait ajouter ici les chiffres comparatifs pour le tableau du front (watts gagnés, etc.)
            ]),
        ]);

        return [
            'recommendation_id' => $recommendationId,
            'recommended_tire'  => $recommendedProduct->web_range_name,
            'justification'     => $proseMotivante
        ];
    }
}