<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset {--force : Forcer l\'exécution même en production}')]
#[Description('Réinitialise la démo à l\'état « premier arrivant » (Strava connecté, sorties, aucun pneu).')]
class DemoReset extends Command
{
    /**
     * Rejoue le seed proprement (migrate:fresh + catalogue + Marc premier arrivant) pour
     * redonner le point de départ de la démo. Déterministe : deux exécutions donnent un
     * état identique, sans doublon. Garde-fou : refus en production. Ensuite : demo:tires, demo:wear.
     */
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->components->error('Réinitialisation refusée en production. Utilisez --force pour passer outre.');

            return self::FAILURE;
        }

        $this->components->info('Réinitialisation de la base de démo…');

        $this->call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->components->info('Démo prête — Marc connecté, sorties importées, aucun pneu. Suite : demo:tires puis demo:wear.');

        return self::SUCCESS;
    }
}
