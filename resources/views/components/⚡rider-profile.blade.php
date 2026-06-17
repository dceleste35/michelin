<?php

use Livewire\Component;
use App\Models\User;
use App\Models\UserTire;
use App\Services\ProfileInferenceService;
use App\Enums\TirePosition;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?User $user = null;
    public string $name = '';
    public string $segment = '';
    public string $ridingStyle = '';
    public int $weight = 0;
    public int $weeklyKm = 0;
    public int $terrainRoad = 0;
    public int $terrainOffroad = 0;
    
    public array $activeTires = [];

    public function mount(): void
    {
        $this->loadProfile();
    }

    public function loadProfile(): void
    {
        $this->user = Auth::user();

        if ($this->user) {
            $service = app(ProfileInferenceService::class);
            $profile = $service->buildProfile($this->user);

            $this->name = $this->user->name;
            $this->segment = $profile->segment->value ?? 'Gravel';
            $this->ridingStyle = $profile->ridingStyle->value ?? 'Endurance';
            $this->weight = $profile->weightKg;

            // Calculate terrain percents from activities
            $activities = $this->user->stravaActivities()->get();
            $ridesCount = $activities->count();
            
            if ($ridesCount > 0) {
                $surfaceAsphalt = $activities->where('surface_derived', \App\Enums\Surface::Asphalt)->count();
                $this->terrainRoad = (int) round(($surfaceAsphalt / $ridesCount) * 100);
                $this->terrainOffroad = 100 - $this->terrainRoad;

                // Average weekly km over active weeks (max 26 weeks)
                $totalDistanceKm = $activities->sum('distance_m') / 1000;
                $firstActivity = $activities->sortBy('start_date')->first();
                $weeks = $firstActivity ? max(1, $firstActivity->start_date->diffInWeeks(now())) : 26;
                $this->weeklyKm = (int) round($totalDistanceKm / max(1, $weeks));
            } else {
                // Fallback to defaults
                $this->terrainRoad = 60;
                $this->terrainOffroad = 40;
                $this->weeklyKm = 140;
            }

            // Load active tires
            $tires = UserTire::where('user_id', $this->user->id)
                ->where('is_active', true)
                ->with('product')
                ->get();

            foreach ($tires as $tire) {
                $expectedLife = $tire->product->expected_life_km ?? 4000;
                $runKm = ($tire->wear_percent / 100) * $expectedLife;
                $remainingKm = max(0, round($expectedLife - $runKm));

                $this->activeTires[$tire->position->value] = [
                    'model' => $tire->product->web_range_name,
                    'specs' => '700×' . ($tire->product->width_etrto ?? 42) . 'C · ' . ($tire->product->casing_tech ?? 'Tubeless Ready'),
                    'wear' => round($tire->wear_percent),
                    'remaining_km' => $remainingKm,
                    'mounted_at' => $tire->mounted_at ? $tire->mounted_at->translatedFormat('d/m/Y') : 'Récemment',
                ];
            }
        } else {
            // Guest mode defaults (Marc's profile simulator data)
            $this->name = 'Marc';
            $this->segment = 'Gravel';
            $this->ridingStyle = 'Endurance';
            $this->weight = 90;
            $this->terrainRoad = 60;
            $this->terrainOffroad = 40;
            $this->weeklyKm = 140;

            $this->activeTires = [
                'FRONT' => [
                    'model' => 'Power Gravel',
                    'specs' => '700×42C · Tubeless Ready',
                    'wear' => 72,
                    'remaining_km' => 310,
                    'mounted_at' => '12/2025',
                ],
                'REAR' => [
                    'model' => 'Power Gravel',
                    'specs' => '700×42C · Tubeless Ready',
                    'wear' => 86,
                    'remaining_km' => 190,
                    'mounted_at' => '12/2025',
                ],
            ];
        }
    }
}; ?>

<div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-col gap-1.5 mb-5 border-b border-zinc-150 dark:border-zinc-800 pb-3">
        <h3 class="text-md font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
            <span class="inline-block w-2 h-5 bg-michelin-blue rounded-full"></span>
            {{ __('Profil Rider & Activités Strava') }}
        </h3>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Analyse de vos sorties des 6 derniers mois et configuration actuelle.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Profile details -->
        <div class="flex flex-col gap-4">
            <div class="bg-zinc-50 dark:bg-zinc-950 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50">
                <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-2">{{ __('PROFIL INFERÉ') }}</span>
                <div class="inline-flex items-center gap-2 bg-michelin-blue text-michelin-yellow font-black text-xs uppercase px-3 py-2 rounded-lg w-full mb-3 shadow-sm">
                    <flux:icon icon="user" class="size-4" />
                    <span>{{ $name }} · {{ $segment }} · {{ $ridingStyle }}</span>
                </div>
                
                <div class="space-y-2.5 text-xs">
                    <div class="flex justify-between items-center py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Terrain dominant') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-100">{{ $terrainRoad }}% {{ __('route') }} / {{ $terrainOffroad }}% {{ __('chemin') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Distance type') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-100">~{{ $weeklyKm }} km / {{ __('semaine') }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5 border-b border-zinc-100 dark:border-zinc-800">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Style de pilotage') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-100">{{ $ridingStyle }}</span>
                    </div>
                    <div class="flex justify-between items-center py-1.5">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Poids système') }}</span>
                        <span class="font-bold text-zinc-800 dark:text-zinc-100">~{{ $weight }} kg</span>
                    </div>
                </div>
                
                <div class="mt-3 flex items-center justify-between">
                    <span class="text-[9px] font-bold text-michelin-blue dark:text-michelin-blue-light bg-michelin-blue/5 dark:bg-michelin-blue/10 border border-michelin-blue/20 dark:border-michelin-blue/30 px-2 py-0.5 rounded uppercase">
                        {{ __('Données synchronisées Strava') }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Current mounted tires -->
        <div class="flex flex-col gap-4">
            <div class="bg-zinc-50 dark:bg-zinc-950 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50">
                <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-2">{{ __('ÉQUIPEMENT ACTUEL') }}</span>
                
                <div class="space-y-4">
                    @if(isset($activeTires['FRONT']))
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-600 dark:text-zinc-400 shrink-0">
                                AV
                            </div>
                            <div class="flex-1">
                                <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $activeTires['FRONT']['model'] }}</h4>
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $activeTires['FRONT']['specs'] }}</p>
                                <p class="text-[9px] text-zinc-400 mt-0.5">Monté le: {{ $activeTires['FRONT']['mounted_at'] }} · Usure: <span class="font-bold">{{ $activeTires['FRONT']['wear'] }}%</span></p>
                            </div>
                        </div>
                    @endif

                    @if(isset($activeTires['REAR']))
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-lg bg-zinc-200 dark:bg-zinc-800 flex items-center justify-center font-bold text-xs text-zinc-600 dark:text-zinc-400 shrink-0">
                                AR
                            </div>
                            <div class="flex-1 border-t border-zinc-100 dark:border-zinc-800/50 pt-3 md:border-t-0 md:pt-0">
                                <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $activeTires['REAR']['model'] }}</h4>
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $activeTires['REAR']['specs'] }}</p>
                                <p class="text-[9px] text-zinc-400 mt-0.5">Monté le: {{ $activeTires['REAR']['mounted_at'] }} · Usure: <span class="font-bold">{{ $activeTires['REAR']['wear'] }}%</span></p>
                            </div>
                        </div>
                    @endif

                    @if(empty($activeTires))
                        <div class="text-xs text-zinc-500 dark:text-zinc-400 py-3 text-center">
                            {{ __('Aucun pneu monté détecté.') }}
                        </div>
                    @endif
                </div>

                <div class="mt-4 pt-3 border-t border-zinc-100 dark:border-zinc-800 flex justify-end">
                    <a href="#tire-wear-card" class="text-xs font-bold text-michelin-blue dark:text-michelin-blue-light hover:underline flex items-center gap-1">
                        {{ __('Gérer mes pneus') }} &rarr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
