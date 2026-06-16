<?php

namespace App\Services;

use App\Models\UserTire;
use App\Models\StravaActivity;
use Illuminate\Support\Facades\DB;

class WearService
{
    /**
     * Calcule l'usure d'une seule activité en kilomètres équivalents.
     */
    public function calculateActivityWear(float $distanceKm, float $terrainCoef, float $weightKg, float $styleCoef): float
    {
        // Coefficient de poids : 1 + (poids - 80) / 100 | Borné entre 0.85 et 1.30
        $weightCoef = 1 + (($weightKg - 80) / 100);
        $weightCoef = max(0.85, min(1.30, $weightCoef));

        // Formule SCORE : accumulation physique pondérée
        return $distanceKm * $terrainCoef * $weightCoef * $styleCoef;
    }

    /**
     * Calcule l'état de santé global d'un pneu monté (Tire Health).
     */
    public function getTireHealth(int $userTireId): array
    {
        // 1. Récupérer le pneu de l'utilisateur avec son profil utilisateur
        $tire = DB::table('user_tires')
            ->join('products', 'user_tires.product_id', '=', 'products.id')
            ->join('users', 'user_tires.user_id', '=', 'users.id')
            ->where('user_tires.id', $userTireId)
            ->select(
                'user_tires.*', 
                'products.segment', 
                'users.weight_kg', 
                'users.riding_style'
            )
            ->first();

        if (!$tire) {
            throw new \Exception("Pneu utilisateur introuvable.");
        }

        // Mapping du coefficient de style (Valeurs par défaut de la spec)
        $styleCoefs = ['ENDURANCE' => 1.00, 'MIXED' => 1.08, 'AGRESSIF' => 1.15];
        $currentStyleCoef = $styleCoefs[$tire->riding_style] ?? 1.00;

        // 2. Récupérer toutes les activités Strava depuis la date de montage du pneu
        $activities = DB::table('strava_activities')
            ->where('user_id', $tire->user_id)
            ->where('start_date', '>=', $tire->mounted_at)
            ->get();

        // Récupérer l'ensemble des coefficients de ce segment pour éviter les requêtes N+1 en boucle
        $coefficients = DB::table('wear_coefficients')
            ->where('segment', $tire->segment)
            ->pluck('coef', 'terrain')
            ->toArray();

        // Déterminer la durée de vie de référence (baseline) pour ce segment
        // On prend la première valeur disponible ou un défaut de 4000 km (Spec Gravel)
        $baselineEol = DB::table('wear_coefficients')
            ->where('segment', $tire->segment)
            ->value('km_to_eol_baseline') ?? 4000;

        $totalEquivalentKm = 0;
        $totalRealKmRecent = 0;
        $recentActivitiesCount = 0;
        $totalCoefRecent = 0;

        // 3. Boucler sur les activités pour calculer l'usure accumulée
        foreach ($activities as $activity) {
            // Conversion de la distance Strava (mètres -> kilomètres)
            $distanceKm = $activity->distance_m / 1000;
            
            // Récupération du coefficient lié au terrain détecté par Dan
            $terrainCoef = $coefficients[$activity->surface_derived] ?? 1.00;

            // Cumuler l'usure en kilomètres équivalents
            $wearForActivity = $this->calculateActivityWear(
                $distanceKm, 
                $terrainCoef, 
                $tire->weight_kg ?? 80, 
                $currentStyleCoef
            );
            
            $totalEquivalentKm += $wearForActivity;

            // On stocke les stats récentes (ex: les 10 dernières sorties) pour estimer le rythme d'usure futur
            $totalRealKmRecent += $distanceKm;
            $totalCoefRecent += ($terrainCoef * $currentStyleCoef);
            $recentActivitiesCount++;
        }

        // 4. Calcul du pourcentage d'usure (borné à 100%)
        $wearPercent = min(100.0, ($totalEquivalentKm / $baselineEol) * 100);

        // 5. Calcul des kilomètres réels restants estimables
        // Si aucune activité, on estime sur une base neutre (coef moyen de 1.0)
        $averageRecentCoef = $recentActivitiesCount > 0 ? ($totalCoefRecent / $recentActivitiesCount) : 1.0;
        $weightCoef = max(0.85, min(1.30, 1 + ((($tire->weight_kg ?? 80) - 80) / 100)));
        
        $remainingEquivalentKm = max(0.0, $baselineEol - $totalEquivalentKm);
        $remainingRealKm = $remainingEquivalentKm / ($averageRecentCoef * $weightCoef);

        return [
            'user_tire_id' => $userTireId,
            'wear_percent' => round($wearPercent, 1),
            'remaining_km' => round($remainingRealKm, 0),
            'accumulated_equivalent_km' => round($totalEquivalentKm, 1),
            'baseline_eol_km' => $baselineEol
        ];
    }
}