<?php

use Livewire\Component;
use App\Models\UserTire;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

new class extends Component {
    public ?UserTire $userTire = null;
    public int $simulatedKm = 50;
    public string $activePosition = 'REAR';

    /**
     * Refresh the active tire when a new one is mounted.
     */
    #[On('tire-mounted')]
    public function refreshTire(): void
    {
        $this->activePosition = 'REAR';
        $this->loadTire();
    }

    /**
     * Mount the component.
     */
    public function mount(?UserTire $userTire = null): void
    {
        if ($userTire) {
            $this->userTire = $userTire;
            $this->activePosition = $userTire->position->value;
        } else {
            $this->loadTire();
        }
    }

    /**
     * Load the tire for the current position.
     */
    public function loadTire(): void
    {
        $user = Auth::user();
        if ($user) {
            // Pas de fabrication de données : un compte sans pneu reste à null → état vide.
            $this->userTire = $user->tires()
                ->active()
                ->where('position', $this->activePosition)
                ->first()
                ?? $user->tires()
                    ->where('position', $this->activePosition)
                    ->first();
        }
    }

    /**
     * Set the viewed tire position (FRONT or REAR).
     */
    public function setPosition(string $position): void
    {
        $this->activePosition = $position;
        $this->loadTire();
    }

    /**
     * Simulate a ride by adding kilometers and updating wear percent.
     */
    public function simulateRide(int $km): void
    {
        if (!$this->userTire) {
            return;
        }

        $currentKm = $this->getCurrentMileage();
        $newKm = $currentKm + $km;
        $expectedLife = $this->userTire->product->expected_life_km ?? 5000;

        $newWear = min(100.00, ($newKm / $expectedLife) * 100);
        $this->userTire->wear_percent = $newWear;
        $this->userTire->save();

        Flux::toast(
            variant: 'success',
            text: __('Sortie de :km km enregistrée ! Usure à :wear%', [
                'km' => $km,
                'wear' => number_format($newWear, 0)
            ]),
        );
    }

    /**
     * Quick action to simulate a ride.
     */
    public function quickSimulate(int $km): void
    {
        $this->simulateRide($km);
    }

    /**
     * Add simulated mileage from the slider value.
     */
    public function addSimulatedKm(): void
    {
        if ($this->simulatedKm > 0) {
            $this->simulateRide($this->simulatedKm);
            $this->simulatedKm = 50; // reset to default value
        }
    }

    /**
     * Reset the tire stats when a brand new tire is mounted.
     */
    public function resetTire(): void
    {
        if (!$this->userTire) {
            return;
        }

        $this->userTire->mounted_at = now();
        $this->userTire->wear_percent = 0.00;
        $this->userTire->save();

        Flux::toast(
            variant: 'success',
            text: __('Pneu réinitialisé. Usure remise à 0%.'),
        );
    }

    /**
     * Calculate current mileage from wear percentage.
     */
    public function getCurrentMileage(): float
    {
        if (!$this->userTire) {
            return 0;
        }

        $expectedLife = $this->userTire->product->expected_life_km ?? 5000;
        return ($this->userTire->wear_percent / 100) * $expectedLife;
    }

    /**
     * Get the theme color based on current wear.
     */
    public function getWearColor(): string
    {
        $wear = $this->userTire->wear_percent ?? 0;
        if ($wear >= 80) {
            return 'critical';
        }
        if ($wear >= 50) {
            return 'moderate';
        }
        return 'excellent';
    }

    /**
     * Get the user-friendly wear status label.
     */
    public function getWearStatusLabel(): string
    {
        $wear = $this->userTire->wear_percent ?? 0;
        if ($wear >= 80) {
            return __('Critique - Remplacer');
        }
        if ($wear >= 50) {
            return __('Moyen - Permuter');
        }
        return __('Excellent état');
    }

    /**
     * Get the high-contrast Tailwind classes for the status badge using digital charter colors.
     */
    public function getWearBadgeClasses(): string
    {
        $wear = $this->userTire->wear_percent ?? 0;
        if ($wear >= 80) {
            return 'bg-michelin-yellow dark:bg-michelin-yellow-dark-03 text-zinc-950 font-bold uppercase tracking-wider text-[9px] px-2.5 py-1 shadow-sm rounded-md';
        }
        if ($wear >= 50) {
            return 'bg-michelin-yellow-dark-02 dark:bg-michelin-yellow-light-03 text-zinc-950 font-bold uppercase tracking-wider text-[9px] px-2.5 py-1 shadow-sm rounded-md';
        }
        return 'bg-michelin-blue dark:bg-michelin-blue-dark-01 text-white font-bold uppercase tracking-wider text-[9px] px-2.5 py-1 shadow-sm rounded-md';
    }
}; ?>

<div class="w-full max-w-md mx-auto bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl">
    @if ($this->userTire)
    <style>
        @keyframes tire-fade-in {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        @keyframes tire-slide-up {
            from {
                transform: translateY(12px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        @keyframes tire-draw-radial {
            from {
                stroke-dasharray: 0, 100;
            }
            to {
                stroke-dasharray: var(--wear-percent), 100;
            }
        }
        @keyframes tire-progress-grow {
            from {
                width: 0%;
            }
            to {
                width: var(--wear-width);
            }
        }

        .animate-tire-fade {
            animation: tire-fade-in 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-tire-slide {
            animation: tire-slide-up 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }
        .animate-tire-radial {
            animation: tire-draw-radial 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
        .animate-tire-progress {
            animation: tire-progress-grow 1.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }
    </style>

    <!-- Image Header with Gradient & Overlay -->
    <div class="relative h-48 w-full bg-zinc-950 flex items-center justify-center overflow-hidden">
        <!-- Radial background glow -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(39,80,155,0.2)_0%,transparent_70%)] pointer-events-none"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-zinc-950 via-zinc-950/40 to-transparent pointer-events-none"></div>

        <!-- Dynamic Position Indicator (Bicycle SVG Overlay) -->
        <div class="absolute top-4 left-4 z-10 bg-black/50 backdrop-blur-md px-3 py-1.5 rounded-full border border-white/10 flex items-center gap-2">
            <svg class="w-12 h-6 text-zinc-400" viewBox="0 0 100 50" fill="none" stroke="currentColor" stroke-width="2.5">
                <!-- Frame -->
                <path d="M 30,35 L 48,15 L 70,35 L 45,35 L 30,35 L 42,20 L 70,20 L 70,35" stroke-linejoin="round" />
                <path d="M 48,15 L 45,35" /> <!-- Seatpost -->
                <!-- Fork / Handlebars -->
                <path d="M 70,35 L 75,12 L 68,12" />
                <!-- Rear Wheel -->
                <circle cx="30" cy="35" r="10" class="transition-all duration-300 {{ $activePosition === 'REAR' ? 'stroke-accent animate-pulse stroke-[3.5px]' : 'stroke-zinc-600 dark:stroke-zinc-700 stroke-[2.5px]' }}" />
                <!-- Front Wheel -->
                <circle cx="70" cy="35" r="10" class="transition-all duration-300 {{ $activePosition === 'FRONT' ? 'stroke-accent animate-pulse stroke-[3.5px]' : 'stroke-zinc-600 dark:stroke-zinc-700 stroke-[2.5px]' }}" />
            </svg>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-white">
                {{ $activePosition === 'FRONT' ? __('Avant') : __('Arrière') }}
            </span>
        </div>

        <!-- Wear status badge -->
        <div wire:key="tire-badge-{{ $this->userTire->id }}" class="absolute top-4 right-4 z-10 animate-tire-fade">
            <span class="{{ $this->getWearBadgeClasses() }}">
                {{ $this->getWearStatusLabel() }}
            </span>
        </div>

        <!-- Premium Tire Image -->
        <img 
            wire:key="tire-image-{{ $this->userTire->id }}"
            src="{{ $this->userTire->product->image_url ?? asset('images/michelin_bike_tire.jpg') }}" 
            alt="Michelin Tire Product Shot" 
            class="h-full w-full object-cover transition-transform duration-500 hover:scale-105 animate-tire-fade"
        />

        <!-- Title / Brand Banner at the bottom of image -->
        <div wire:key="tire-title-{{ $this->userTire->id }}" class="absolute bottom-4 left-4 right-4 text-white z-10 flex items-end justify-between animate-tire-slide">
            <div class="flex flex-col gap-0.5">
                <span class="text-[9px] uppercase font-bold tracking-widest text-michelin-blue-light dark:text-zinc-400">MICHELIN COLOURED TREADS</span>
                <h3 class="text-base font-black tracking-tight leading-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.8)]">
                    {{ $this->userTire->product->web_range_name }}
                </h3>
            </div>
            <div class="text-right flex flex-col items-end">
                <span class="text-[9px] text-zinc-300 uppercase tracking-wider block">{{ __('Section') }}</span>
                <span class="text-[10px] font-black bg-white/20 backdrop-blur-xs px-2 py-0.5 rounded text-white border border-white/10">{{ $this->userTire->product->width_etrto }}-{{ $this->userTire->product->diameter_etrto }}</span>
            </div>
        </div>
    </div>

    <!-- Details and Progress Section -->
    <div class="p-5 flex flex-col gap-5 bg-white dark:bg-zinc-900">
        <!-- Position Selector Tabs -->
        <div class="flex bg-zinc-50 dark:bg-zinc-950 p-1 rounded-xl border border-zinc-150 dark:border-zinc-800/80">
            <button 
                type="button" 
                wire:click="setPosition('FRONT')"
                class="flex-1 py-1.5 text-xs font-black uppercase tracking-wider rounded-lg transition-all duration-300 {{ $activePosition === 'FRONT' 
                    ? 'bg-white dark:bg-zinc-850 text-accent dark:text-michelin-blue-light shadow-xs border border-zinc-200/20 dark:border-zinc-700/30' 
                    : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-350' }}"
            >
                {{ __('Pneu Avant') }}
            </button>
            <button 
                type="button" 
                wire:click="setPosition('REAR')"
                class="flex-1 py-1.5 text-xs font-black uppercase tracking-wider rounded-lg transition-all duration-300 {{ $activePosition === 'REAR' 
                    ? 'bg-white dark:bg-zinc-850 text-accent dark:text-michelin-blue-light shadow-xs border border-zinc-200/20 dark:border-zinc-700/30' 
                    : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-350' }}"
            >
                {{ __('Pneu Arrière') }}
            </button>
        </div>

        <!-- Wear metrics overview -->
        <div wire:key="tire-metrics-{{ $this->userTire->id }}" class="flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-[10px] uppercase font-bold tracking-wider text-zinc-400 dark:text-zinc-500">{{ __('Kilométrage actuel') }}</span>
                <span class="text-2xl font-black text-zinc-800 dark:text-zinc-100 tracking-tight">
                    {{ number_format($this->getCurrentMileage(), 0, ',', ' ') }} <span class="text-xs font-semibold text-zinc-500">km</span>
                </span>
            </div>
            
            <!-- Radial Wear Circle -->
            <div class="relative flex items-center justify-center">
                <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 36 36">
                    <!-- Track -->
                    <path class="text-zinc-100 dark:text-zinc-850" stroke-width="3" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <!-- Circle Bar -->
                    @php
                        $colorClass = match($this->getWearColor()) {
                            'critical' => 'stroke-michelin-yellow dark:stroke-michelin-yellow-dark-03',
                            'moderate' => 'stroke-michelin-yellow-dark-02 dark:stroke-michelin-yellow-light-03',
                            default => 'stroke-michelin-blue dark:stroke-michelin-blue-dark-01'
                        };
                    @endphp
                    <path 
                        wire:key="tire-radial-{{ $this->userTire->id }}"
                        class="{{ $colorClass }} animate-tire-radial" 
                        style="--wear-percent: {{ $this->userTire->wear_percent }}"
                        stroke-width="3.5" 
                        stroke-linecap="round" 
                        stroke="currentColor" 
                        fill="none" 
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                    />
                </svg>
                <div wire:key="tire-wear-pct-{{ $this->userTire->id }}" class="absolute flex flex-col items-center justify-center animate-tire-slide">
                    <span class="text-sm font-black text-zinc-800 dark:text-zinc-100 leading-none">{{ number_format($this->userTire->wear_percent, 0) }}%</span>
                    <span class="text-[7px] uppercase tracking-widest font-extrabold text-zinc-400 mt-0.5 leading-none">{{ __('Usure') }}</span>
                </div>
            </div>
        </div>

        <!-- Progress Bar for Mileage -->
        <div class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">
                <span>0 km</span>
                <span>Limite : {{ number_format($this->userTire->product->expected_life_km ?? 5000, 0, ',', ' ') }} km</span>
            </div>
            
            <!-- Custom Premium Progress Bar with Wear Color -->
            <div class="h-4 w-full bg-zinc-100 dark:bg-zinc-950/60 rounded-full p-0.5 overflow-hidden border border-zinc-200/40 dark:border-zinc-800/40">
                @php
                    $barGradient = match($this->getWearColor()) {
                        'critical' => 'bg-gradient-to-r from-michelin-yellow to-michelin-yellow-dark-03 shadow-[0_0_8px_rgba(252,229,0,0.2)]',
                        'moderate' => 'bg-gradient-to-r from-michelin-yellow-dark-02 to-michelin-yellow-dark-01 shadow-[0_0_8px_rgba(253,237,68,0.2)]',
                        default => 'bg-gradient-to-r from-michelin-blue to-michelin-blue-dark-02 shadow-[0_0_8px_rgba(39,80,155,0.2)]'
                    };
                @endphp
                <div 
                    wire:key="tire-bar-{{ $this->userTire->id }}"
                    class="h-full rounded-full {{ $barGradient }} flex items-center justify-end pr-1.5 text-[8px] text-white font-black whitespace-nowrap animate-tire-progress" 
                    style="--wear-width: {{ min(100, $this->userTire->wear_percent) }}%"
                >
                    @if($this->userTire->wear_percent > 12)
                        {{ number_format($this->userTire->wear_percent, 0) }}%
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div wire:key="tire-infogrid-{{ $this->userTire->id }}" class="grid grid-cols-2 gap-3 bg-zinc-50 dark:bg-zinc-950/40 p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800/40 animate-tire-slide">
            <div class="flex flex-col gap-0.5">
                <span class="text-[9px] uppercase font-bold tracking-wider text-zinc-400 block mb-0.5">{{ __('Date de montage') }}</span>
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    {{ $this->userTire->mounted_at ? $this->userTire->mounted_at->translatedFormat('d F Y') : __('Non renseignée') }}
                </span>
            </div>
            <div class="flex flex-col gap-0.5">
                <span class="text-[9px] uppercase font-bold tracking-wider text-zinc-400 block mb-0.5">{{ __('Odomètre initial') }}</span>
                <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200">
                    {{ number_format($this->userTire->mounted_odometer_km ?? 0, 0, ',', ' ') }} km
                </span>
            </div>
        </div>

        <!-- End of Life / Wear Warnings -->
        @if (($this->userTire->wear_percent ?? 0) >= 80)
            <div wire:key="tire-warning-danger-{{ $this->userTire->id }}" class="animate-tire-slide">
                <flux:callout 
                    icon="exclamation-triangle" 
                    heading="{{ __('Pneu en fin de vie !') }}"
                    class="[--callout-border:var(--color-michelin-yellow-dark-02)] [--callout-background:var(--color-michelin-yellow-light-01)] [--callout-heading:var(--color-zinc-950)] [--callout-text:var(--color-zinc-800)] [--callout-icon:var(--color-michelin-yellow-dark-03)]"
                >
                    {{ __('Ce pneu a atteint :wear% d\'usure. Pour votre sécurité, veuillez le remplacer rapidement.', ['wear' => number_format($this->userTire->wear_percent, 0)]) }}
                </flux:callout>
            </div>
        @elseif (($this->userTire->wear_percent ?? 0) >= 50)
            <div wire:key="tire-warning-warning-{{ $this->userTire->id }}" class="animate-tire-slide">
                <flux:callout 
                    icon="exclamation-circle" 
                    heading="{{ __('Usure modérée') }}"
                    class="[--callout-border:var(--color-michelin-blue-light-03)] [--callout-background:var(--color-michelin-blue-light-01)] [--callout-heading:var(--color-michelin-blue)] [--callout-text:var(--color-michelin-blue-dark-03)] [--callout-icon:var(--color-michelin-blue-dark-02)]"
                >
                    {{ __('Pensez à remplacer ce pneu bientôt ou équilibrez vos sorties.') }}
                </flux:callout>
            </div>
        @endif

        <flux:separator />

        <!-- Simulation and Actions Section -->
        <div class="flex flex-col gap-4">
            <h4 class="text-[10px] font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest flex items-center gap-1.5">
                <flux:icon icon="bolt" class="size-3.5 text-zinc-400 dark:text-zinc-500" />
                {{ __('Actions & Simulation de Sorties') }}
            </h4>

            <!-- Interactive Slider for adding km -->
            <div class="flex flex-col gap-2.5 p-3.5 bg-zinc-50 dark:bg-zinc-950/40 rounded-xl border border-zinc-100 dark:border-zinc-800/40">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-bold text-zinc-500 dark:text-zinc-450">{{ __('Simuler une distance') }}</span>
                    <span class="font-extrabold text-accent dark:text-michelin-blue-light">{{ $simulatedKm }} km</span>
                </div>
                
                <div class="flex items-center gap-3">
                    <input 
                        type="range" 
                        min="10" 
                        max="500" 
                        step="10" 
                        wire:model.live="simulatedKm"
                        class="w-full h-1.5 bg-zinc-200 dark:bg-zinc-850 rounded-lg appearance-none cursor-pointer accent-accent"
                    />
                    <flux:button 
                        size="xs" 
                        variant="primary" 
                        wire:click="addSimulatedKm"
                        class="shrink-0 font-bold"
                    >
                        {{ __('Ajouter') }}
                    </flux:button>
                </div>

                <!-- Quick add presets -->
                <div class="flex gap-2 mt-1">
                    <button type="button" wire:click="quickSimulate(30)" class="flex-1 py-1.5 px-2 text-[10px] font-bold rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors">
                        +30 km
                    </button>
                    <button type="button" wire:click="quickSimulate(50)" class="flex-1 py-1.5 px-2 text-[10px] font-bold rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-350 transition-colors">
                        +50 km
                    </button>
                    <button type="button" wire:click="quickSimulate(100)" class="flex-1 py-1.5 px-2 text-[10px] font-bold rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors">
                        +100 km
                    </button>
                </div>
            </div>

            <!-- Manage Actions Button (Reset/Replace) -->
            <flux:button 
                icon="arrow-uturn-left" 
                variant="outline" 
                wire:click="resetTire" 
                class="w-full text-xs font-bold text-zinc-750 dark:text-zinc-300 hover:text-michelin-blue dark:hover:text-michelin-blue-light-03"
            >
                {{ __('Remplacer / Réinitialiser ce pneu') }}
            </flux:button>
        </div>
    </div>
    @else
        {{-- État vide : aucun pneu enregistré (compte neuf) — on ne fabrique plus de données --}}
        <div class="flex flex-col items-center justify-center gap-4 px-6 py-12 text-center" data-test="tire-card-empty">
            <flux:icon icon="cube-transparent" class="size-12 text-zinc-300 dark:text-zinc-600" />
            <div class="flex flex-col gap-1">
                <flux:heading size="lg">{{ __('No tire registered yet') }}</flux:heading>
                <flux:text class="text-zinc-500">{{ __('Add your Michelin tires to start tracking their wear.') }}</flux:text>
            </div>
            <flux:button :href="route('tires')" variant="primary" icon="plus" wire:navigate data-test="tire-card-add">
                {{ __('Add my tires') }}
            </flux:button>
        </div>
    @endif
</div>
