<?php

namespace App\Console\Commands;

use App\Enums\TirePosition;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:tires {--wear=0 : Usure du pneu arrière en %} {--front= : Usure avant en % (défaut : arrière − 24)}')]
#[Description('Monte une paire Power Gravel sur le héros de démo et la rattache à ses sorties.')]
class DemoTires extends Command
{
    /**
     * Étape de démo : Marc équipe son vélo. Monte un pneu avant + arrière Power Gravel
     * (frais par défaut, usés via --wear) et les assigne à l'historique de sorties.
     */
    public function handle(): int
    {
        $marc = User::where('email', 'marc@rideready.test')->first();

        if (! $marc) {
            $this->components->error('Démo non initialisée — lancez d\'abord `php artisan demo:reset`.');

            return self::FAILURE;
        }

        $product = Product::where('web_range_name', 'Power Gravel')->first();

        if (! $product) {
            $this->components->error('Catalogue absent — lancez `php artisan demo:reset`.');

            return self::FAILURE;
        }

        $rearWear = (float) $this->option('wear');
        $frontWear = $this->option('front') !== null ? (float) $this->option('front') : max(0.0, $rearWear - 24);
        $mountedAt = CarbonImmutable::now()->subDays(45)->toDateString();

        // On retire les pneus actifs précédents (conservés en historique) puis on monte la paire.
        $marc->tires()->where('is_active', true)->update(['is_active' => false]);

        $rear = $marc->tires()->create([
            'product_id' => $product->id,
            'position' => TirePosition::Rear,
            'mounted_at' => $mountedAt,
            'mounted_odometer_km' => 2500,
            'wear_percent' => $rearWear,
            'is_active' => true,
        ]);

        $front = $marc->tires()->create([
            'product_id' => $product->id,
            'position' => TirePosition::Front,
            'mounted_at' => $mountedAt,
            'mounted_odometer_km' => 2500,
            'wear_percent' => $frontWear,
            'is_active' => true,
        ]);

        // Historique cohérent : on rattache la paire aux sorties, en laissant les 3 plus
        // récentes « à vérifier » (auto-assignées + notification de vérification).
        $marc->stravaActivities()->update([
            'front_tire_id' => $front->id,
            'rear_tire_id' => $rear->id,
            'tires_confirmed' => true,
        ]);

        $recent = $marc->stravaActivities()->orderByDesc('start_date')->limit(3)->pluck('id');
        $marc->stravaActivities()->whereIn('id', $recent)->update(['tires_confirmed' => false]);

        $this->components->info("Paire Power Gravel montée — arrière {$rearWear} % / avant {$frontWear} %.");

        return self::SUCCESS;
    }
}
