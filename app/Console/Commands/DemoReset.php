<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset {--force : Forcer l\'exécution même en production}')]
#[Description('Réinitialise la démo à son état déterministe exact (Marc 86 % d\'usure arrière, alerte prête).')]
class DemoReset extends Command
{
    /**
     * Rejoue le seed proprement (migrate:fresh + catalogue + Marc calibré) pour
     * redonner exactement l'état de démo. Déterministe : deux exécutions donnent
     * un état identique, sans doublon. Garde-fou : refus en production.
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

        $this->components->info('Démo prête — Marc à 86 % d\'usure arrière, alerte armée.');

        return self::SUCCESS;
    }
}
