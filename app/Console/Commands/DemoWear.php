<?php

namespace App\Console\Commands;

use App\Enums\TirePosition;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:wear {--rear= : Usure arrière en %} {--front= : Usure avant en %}')]
#[Description('Vieillit en direct les pneus montés du héros de démo (déclenche alerte / cloche).')]
class DemoWear extends Command
{
    /**
     * Étape de démo : le temps passe. Ajuste l'usure des pneus actifs de Marc pour
     * franchir les seuils en direct (≥ 80 % → fin de vie → alerte/cloche).
     */
    public function handle(): int
    {
        $marc = User::where('email', 'marc@rideready.test')->first();

        if (! $marc) {
            $this->components->error('Démo non initialisée — lancez d\'abord `php artisan demo:reset`.');

            return self::FAILURE;
        }

        $rear = $this->option('rear');
        $front = $this->option('front');

        if ($rear === null && $front === null) {
            $this->components->warn('Précisez --rear et/ou --front (ex. `demo:wear --rear=86 --front=62`).');

            return self::INVALID;
        }

        if ($rear !== null) {
            $marc->tires()->active()->where('position', TirePosition::Rear->value)->update(['wear_percent' => (float) $rear]);
        }

        if ($front !== null) {
            $marc->tires()->active()->where('position', TirePosition::Front->value)->update(['wear_percent' => (float) $front]);
        }

        $this->components->info('Usure mise à jour.');

        return self::SUCCESS;
    }
}
