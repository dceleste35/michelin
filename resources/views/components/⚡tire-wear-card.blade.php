<?php

use Livewire\Component;
use App\Models\UserTire;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?UserTire $userTire = null;
    public int $simulatedKm = 50;

    /**
     * Mount the component.
     */
    public function mount(?UserTire $userTire = null): void
    {
        if ($userTire) {
            $this->userTire = $userTire;
        } else {
            // Find the first active tire of the user, or create/fetch a mock one for demonstration
            $user = Auth::user();
            if ($user) {
                $this->userTire = $user->tires()->active()->first()
                    ?? $user->tires()->first()
                    ?? $this->createMockTireForUser($user);
            }
        }
    }

    /**
     * Create a mock tire mount if the user doesn't have any tires registered.
     */
    protected function createMockTireForUser(App\Models\User $user): UserTire
    {
        $product = Product::first() ?? Product::create([
            'global_id' => 'MIC-PWR-GRVL',
            'web_range_name' => 'Michelin Power Gravel',
            'segment' => 'GRAVEL',
            'width_etrto' => 40,
            'diameter_etrto' => 622,
            'expected_life_km' => 5000,
            'image_url' => '/images/michelin_bike_tire.jpg',
        ]);

        return UserTire::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'position' => \App\Enums\TirePosition::Rear,
            'mounted_at' => now()->subMonths(4),
            'mounted_odometer_km' => 1500,
            'wear_percent' => 32.00,
            'is_active' => true,
        ]);
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
     * Rotate the tire position (Front <-> Rear).
     */
    public function rotateTire(): void
    {
        if (!$this->userTire) {
            return;
        }

        $newPosition = $this->userTire->position === \App\Enums\TirePosition::Front
            ? \App\Enums\TirePosition::Rear
            : \App\Enums\TirePosition::Front;

        $this->userTire->position = $newPosition;
        $this->userTire->save();

        Flux::toast(
            variant: 'success',
            text: __('Pneu permuté vers la position :position !', [
                'position' => $newPosition === \App\Enums\TirePosition::Front ? __('avant') : __('arrière')
            ]),
        );
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
            return 'red';
        }
        if ($wear >= 50) {
            return 'orange';
        }
        return 'green';
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
     * Get the Flux badge variant based on wear level.
     */
    public function getStatusBadgeVariant(): string
    {
        $wear = $this->userTire->wear_percent ?? 0;
        if ($wear >= 80) {
            return 'danger';
        }
        if ($wear >= 50) {
            return 'warning';
        }
        return 'success';
    }
}; ?>

<div class="w-full max-w-md mx-auto bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl overflow-hidden shadow-lg transition-all duration-300 hover:shadow-xl">
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
                <circle cx="30" cy="35" r="10" class="{{ $this->userTire->position === \App\Enums\TirePosition::Rear ? 'stroke-accent animate-pulse stroke-[3.5px]' : 'stroke-zinc-650 dark:stroke-zinc-700' }}" />
                <!-- Front Wheel -->
                <circle cx="70" cy="35" r="10" class="{{ $this->userTire->position === \App\Enums\TirePosition::Front ? 'stroke-accent animate-pulse stroke-[3.5px]' : 'stroke-zinc-650 dark:stroke-zinc-700' }}" />
            </svg>
            <span class="text-[10px] font-extrabold uppercase tracking-wider text-white">
                {{ $this->userTire->position === \App\Enums\TirePosition::Front ? __('Avant') : __('Arrière') }}
            </span>
        </div>

        <!-- Wear status badge -->
        <div class="absolute top-4 right-4 z-10">
            <flux:badge size="sm" variant="{{ $this->getStatusBadgeVariant() }}" class="font-bold uppercase tracking-wider text-[9px] px-2 py-0.5 shadow-sm">
                {{ $this->getWearStatusLabel() }}
            </flux:badge>
        </div>

        <!-- Premium Tire Image -->
        <img 
            src="{{ $this->userTire->product->image_url ?? asset('images/michelin_bike_tire.jpg') }}" 
            alt="Michelin Tire Product Shot" 
            class="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
        />

        <!-- Title / Brand Banner at the bottom of image -->
        <div class="absolute bottom-4 left-4 right-4 text-white z-10 flex items-end justify-between">
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
        <!-- Wear metrics overview -->
        <div class="flex items-center justify-between">
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
                            'red' => 'stroke-red-500 dark:stroke-red-400',
                            'orange' => 'stroke-amber-500 dark:stroke-amber-400',
                            default => 'stroke-emerald-500 dark:stroke-emerald-400'
                        };
                    @endphp
                    <path class="{{ $colorClass }} transition-all duration-500" stroke-width="3.5" stroke-dasharray="{{ $this->userTire->wear_percent }}, 100" stroke-linecap="round" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="absolute flex flex-col items-center justify-center">
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
                        'red' => 'bg-gradient-to-r from-red-500 via-rose-500 to-red-600 shadow-[0_0_8px_rgba(239,68,68,0.2)]',
                        'orange' => 'bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 shadow-[0_0_8px_rgba(245,158,11,0.2)]',
                        default => 'bg-gradient-to-r from-emerald-500 via-teal-500 to-green-600 shadow-[0_0_8px_rgba(16,185,129,0.2)]'
                    };
                @endphp
                <div class="h-full rounded-full transition-all duration-500 {{ $barGradient }} flex items-center justify-end pr-1.5 text-[8px] text-white font-black" style="width: {{ min(100, $this->userTire->wear_percent) }}%">
                    @if($this->userTire->wear_percent > 12)
                        {{ number_format($this->userTire->wear_percent, 0) }}%
                    @endif
                </div>
            </div>
        </div>

        <!-- Info Grid -->
        <div class="grid grid-cols-2 gap-3 bg-zinc-50 dark:bg-zinc-950/40 p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800/40">
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
            <flux:callout variant="danger" icon="exclamation-triangle" heading="{{ __('Pneu en fin de vie !') }}">
                {{ __('Ce pneu a atteint :wear% d\'usure. Pour votre sécurité, veuillez le remplacer rapidement.', ['wear' => number_format($this->userTire->wear_percent, 0)]) }}
            </flux:callout>
        @elseif (($this->userTire->wear_percent ?? 0) >= 50)
            <flux:callout variant="warning" icon="exclamation-circle" heading="{{ __('Usure modérée') }}">
                {{ __('Pensez à permuter vos pneus avant et arrière pour équilibrer l\'usure et prolonger leur durée de vie.') }}
            </flux:callout>
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
                    <button type="button" wire:click="quickSimulate(50)" class="flex-1 py-1.5 px-2 text-[10px] font-bold rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors">
                        +50 km
                    </button>
                    <button type="button" wire:click="quickSimulate(100)" class="flex-1 py-1.5 px-2 text-[10px] font-bold rounded bg-zinc-100 dark:bg-zinc-800 border border-zinc-200/50 dark:border-zinc-700/50 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition-colors">
                        +100 km
                    </button>
                </div>
            </div>

            <!-- Manage Actions Buttons -->
            <div class="flex gap-2">
                <!-- Rotate Button -->
                <flux:button 
                    icon="arrow-path" 
                    variant="outline" 
                    wire:click="rotateTire" 
                    class="flex-1 text-xs font-bold text-zinc-700 dark:text-zinc-300"
                >
                    {{ __('Permuter') }}
                </flux:button>

                <!-- Reset/Replace Button -->
                <flux:button 
                    icon="arrow-uturn-left" 
                    variant="outline" 
                    wire:click="resetTire" 
                    class="flex-1 text-xs font-bold text-zinc-750 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400"
                >
                    {{ __('Nouveau Pneu') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
