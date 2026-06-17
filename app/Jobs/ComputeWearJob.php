<?php

namespace App\Jobs;

use App\Models\UserTire;
use App\Services\WearService;
use App\Services\LlmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ComputeWearJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(WearService $wearService): void
    {
        // 1. Récupérer tous les pneus actuellement montés sur les vélos
        $activeTires = DB::table('user_tires')->where('is_active', true)->get();

        foreach ($activeTires as $tire) {
            // 2. Recalculer l'usure à jour
            $health = $wearService->getTireHealth($tire->id);

            // 3. Estimer le rythme hebdomadaire (basé sur les 4 dernières semaines par exemple)
            // Pour le hackathon, on peut faire une requête simplifiée ou fixer une moyenne (ex: Marc fait 160km/semaine)
            $weeklyAverageKm = $this->getWeeklyAverage($tire->user_id, $tire->mounted_at);

            // Éviter la division par zéro si l'utilisateur n'a pas encore roulé
            if ($weeklyAverageKm > 0) {
                // 4. Combien de semaines de roulage reste-t-il ?
                $weeksRemaining = $health['remaining_km'] / $weeklyAverageKm;

                // 5. La règle métier : Seuil d'alerte à ~3 semaines
                if ($weeksRemaining <= 3.0 && $health['wear_percent'] > 80) {
                    $this->triggerAlert($tire->user_id, $tire->id, $health, $weeklyAverageKm);
                }
            }
            
            // On peut mettre à jour un cache en base pour Guillaume (champ wear_percent dans user_tires)
            DB::table('user_tires')->where('id', $tire->id)->update([
                'wear_percent' => $health['wear_percent']
            ]);
        }
    }

    private function getWeeklyAverage(int $userId, string $mountedAt): float
    {
        // Logique de moyenne Strava. Pour le MVP, on simule le profil de Marc à 160km/semaine
        return 160.0;
    }

    private function triggerAlert(int $userId, int $tireId, array $health, float $weeklyAverageKm): void
        {
            // On génère la recommandation et on la met en cache !
            $recommender = app(\App\Services\RecommenderService::class);
            $reco = $recommender->generateRecommendation($tireId);

            \Illuminate\Support\Facades\Log::info("Alerte déclenchée pour le user {$userId} ! Reco générée : " . $reco['recommended_tire']);
            
            // La suite : Pousser la notification in-app ou par mail
        }
}