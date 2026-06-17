<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * Retourne la dernière recommandation générée pour un utilisateur.
     */
    public function show(int $userId): JsonResponse
    {
        try {
            // On récupère la recommandation la plus récente en cache
            $recommendation = DB::table('recommendations')
                ->where('user_id', $userId)
                ->orderByDesc('id')
                ->first();

            if (!$recommendation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune recommandation disponible pour cet utilisateur.'
                ], 404);
            }

            // On récupère aussi le nom du pneu recommandé pour faciliter la vie du front
            $recommendedProduct = DB::table('products')
                ->where('id', $recommendation->recommended_product_id)
                ->value('web_range_name');

            return response()->json([
                'success' => true,
                'data' => [
                    'recommendation_id' => $recommendation->id,
                    'recommended_tire'  => $recommendedProduct,
                    'rationale'         => json_decode($recommendation->rationale_json, true),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage()
            ], 500);
        }
    }
}