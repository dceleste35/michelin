<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WearService;
use Illuminate\Http\JsonResponse;

class TireHealthController extends Controller
{
    private WearService $wearService;

    public function __construct(WearService $wearService)
    {
        $this->wearService = $wearService;
    }

    /**
     * Retourne l'état de santé d'un pneu spécifique.
     */
    public function show(int $userTireId): JsonResponse
    {
        try {
            // Appel de ton service déterministe
            $healthData = $this->wearService->getTireHealth($userTireId);
            
            return response()->json([
                'success' => true,
                'data' => $healthData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul de l\'usure : ' . $e->getMessage()
            ], 404);
        }
    }
}