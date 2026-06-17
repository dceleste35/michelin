<?php

use Livewire\Component;
use App\Models\UserTire;
use App\Enums\TirePosition;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public array $criticalTires = [];
    public int $weeklyKm = 140;

    #[On('tire-mounted')]
    #[On('tire-wear-updated')]
    public function loadAlerts(): void
    {
        $this->criticalTires = [];
        $user = Auth::user();

        if ($user) {
            // Calculate average weekly km from Strava
            $activities = $user->stravaActivities()->get();
            $ridesCount = $activities->count();
            if ($ridesCount > 0) {
                $totalDistanceKm = $activities->sum('distance_m') / 1000;
                $firstActivity = $activities->sortBy('start_date')->first();
                $weeks = $firstActivity ? max(1, $firstActivity->start_date->diffInWeeks(now())) : 26;
                $this->weeklyKm = (int) round($totalDistanceKm / max(1, $weeks));
            }

            // Find tires with >= 80% wear
            $tires = UserTire::where('user_id', $user->id)
                ->where('is_active', true)
                ->where('wear_percent', '>=', 80.00)
                ->with('product')
                ->get();

            foreach ($tires as $tire) {
                $expectedLife = $tire->product->expected_life_km ?? 4000;
                $runKm = ($tire->wear_percent / 100) * $expectedLife;
                $remainingKm = max(0, round($expectedLife - $runKm));
                
                // Estimate remaining weeks
                $remainingWeeks = $this->weeklyKm > 0 ? max(1, round($remainingKm / $this->weeklyKm)) : 4;

                $this->criticalTires[] = [
                    'position' => $tire->position->value === 'FRONT' ? __('avant') : __('arrière'),
                    'model' => $tire->product->web_range_name,
                    'wear' => round($tire->wear_percent),
                    'remaining_km' => $remainingKm,
                    'remaining_weeks' => $remainingWeeks,
                ];
            }
        } else {
            // Guest mode simulator data (Marc mock)
            $this->criticalTires = [
                [
                    'position' => __('arrière'),
                    'model' => 'Power Gravel',
                    'wear' => 86,
                    'remaining_km' => 190,
                    'remaining_weeks' => 1,
                ]
            ];
        }
    }

    public function mount(): void
    {
        $this->loadAlerts();
    }
}; ?>

<div>
    @if(!empty($criticalTires))
        @foreach($criticalTires as $alert)
            <div wire:key="alert-{{ $loop->index }}" class="mb-6 bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800/80 rounded-2xl p-4 md:p-5 flex flex-col md:flex-row items-start md:items-center justify-between gap-4 shadow-sm animate-pulse-slow">
                <div class="flex items-start gap-3.5">
                    <div class="p-2 bg-red-500/10 dark:bg-red-500/20 rounded-xl text-red-600 dark:text-red-400 shrink-0 mt-0.5">
                        <flux:icon icon="exclamation-triangle" class="size-6" />
                    </div>
                    <div>
                        <h4 class="text-sm font-black text-red-900 dark:text-red-200 uppercase tracking-tight">
                            {{ __('Alerte sécurité : usure critique détectée') }}
                        </h4>
                        <p class="text-xs text-red-700 dark:text-red-300 mt-1 leading-relaxed">
                            {{ __('Votre pneu :position (:model) a atteint :wear% d\'usure.', ['position' => $alert['position'], 'model' => $alert['model'], 'wear' => $alert['wear']]) }}
                            {{ __('Il lui reste environ :km km d\'autonomie.', ['km' => $alert['remaining_km']]) }}
                            {{ __('Avec votre moyenne de :weekly km/semaine, il sera hors-service dans environ :weeks semaine(s) !', ['weekly' => $weeklyKm, 'weeks' => $alert['remaining_weeks']]) }}
                        </p>
                    </div>
                </div>

                <div class="shrink-0 w-full md:w-auto">
                    <a href="#shopping-cart-section" class="inline-flex w-full md:w-auto justify-center bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider py-3 px-5 rounded-xl shadow-md cursor-pointer transition-colors items-center gap-1.5">
                        <flux:icon icon="shopping-bag" class="size-4" />
                        {{ __('Commander son remplaçant') }}
                    </a>
                </div>
            </div>
        @endforeach
    @endif
</div>
