<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Database\Seeders\ProductCatalogSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset {--force : Forcer l\'exécution même en production}')]
#[Description('Réinitialise la démo à l\'état « premier arrivant » (Strava connecté, sorties, aucun pneu).')]
class DemoReset extends Command
{
    /**
     * Remet le point de départ de la démo au niveau DONNÉES (pas de migrate:fresh) : rejoue le
     * catalogue puis le DemoSeeder, qui purge les sorties/pneus de Marc et recrée un premier
     * arrivant. Marche aussi en production (où migrate:fresh est interdit). Déterministe et
     * idempotent. Garde-fou : refus en production sans --force.
     */
    public function handle(): int
    {
        if (app()->isProduction() && ! $this->option('force')) {
            $this->components->error('Réinitialisation refusée en production. Utilisez --force pour passer outre.');

            return self::FAILURE;
        }

        $this->components->info('Réinitialisation de la base de démo…');

        $this->callSilent('db:seed', ['--class' => ProductCatalogSeeder::class, '--force' => true]);
        $this->callSilent('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->components->info('Démo prête — Marc connecté, sorties importées, aucun pneu. Suite : demo:tires puis demo:wear.');

        return self::SUCCESS;
    }
}
