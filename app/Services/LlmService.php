<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class LlmService
{
    /**
     * Rédige une justification d'achat À PARTIR de faits fournis (jamais de chiffre inventé).
     */
    public function writeJustification(array $facts): string
    {
        // On formate joliment les faits (calculs SCORE + faits du RAG) pour le prompt
        $factsText = collect($facts)->map(fn ($v, $k) => "- {$k}: {$v}")->implode("\n");

        // Appel à l'API Anthropic (Claude) via le client HTTP Laravel
        $response = Http::withHeaders([
                'x-api-key'         => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.anthropic.com/v1/messages', [
                'model'      => env('ANTHROPIC_MODEL', 'claude-3-haiku-20240307'), // Haiku est parfait (rapide et pas cher)
                'max_tokens' => 400,
                'temperature'=> 0.3, // Température basse vitale : on veut la fidélité stricte aux faits
                'system'     => "Tu es l'assistant cycliste Michelin. Tu rédiges une justification d'achat de pneu, factuelle et motivante. INTERDICTION absolue d'inventer un chiffre. Base-toi STRICTEMENT sur les faits fournis. Si une donnée manque, ne l'invente pas. 3 phrases maximum.",
                'messages'   => [[
                    'role'    => 'user',
                    'content' => "Faits (issus du catalogue et du calcul d'usage) :\n{$factsText}\n\nRédige 3 phrases pour convaincre le cycliste de ce que ce nouveau pneu va lui apporter par rapport à son usage.",
                ]],
            ]);

        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error("Erreur API Anthropic: " . $response->body());
            // Fallback de secours sécurisé pour ne pas planter la démo
            return "Ce pneu Michelin est parfaitement adapté à votre profil et votre terrain de prédilection."; 
        }

        return $response->json('content.0.text');
    }
}