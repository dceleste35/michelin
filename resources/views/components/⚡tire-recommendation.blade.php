<?php

use Livewire\Component;
use App\Models\Product;
use App\Models\UserTire;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    // We will pre-load 3 products from the database for our Gravel segment POC.
    public array $recommendedProducts = [];

    /**
     * Initialize component and load pre-selected products.
     */
    public function mount(): void
    {
        $this->loadRecommendations();
    }

    /**
     * Load the 3 pre-selected Gravel tires for comparison.
     */
    public function loadRecommendations(): void
    {
        $user = Auth::user();
        
        $p1 = Product::where('global_id', 'BI-38')->first() ?? Product::create([
            'global_id' => 'BI-38',
            'web_range_name' => 'Power Gravel',
            'segment' => 'GRAVEL',
            'width_etrto' => 40,
            'diameter_etrto' => 622,
            'tpi' => 120,
            'min_pressure_bar' => 3.0,
            'max_pressure_bar' => 4.5,
            'rubber_tech' => 'MAGI-X',
            'casing_tech' => 'BEAD TO BEAD SHIELD',
            'expected_life_km' => 4000,
            'rolling_resistance_watts' => 22.0,
            'weight_g' => 490,
            'ean_code' => '3528702637890',
        ]);

        $p2 = Product::where('global_id', 'BI-177')->first() ?? Product::create([
            'global_id' => 'BI-177',
            'web_range_name' => 'Power Gravel RS',
            'segment' => 'GRAVEL',
            'width_etrto' => 42,
            'diameter_etrto' => 622,
            'tpi' => 120,
            'max_pressure_bar' => 4.5,
            'rubber_tech' => 'GUM-X',
            'casing_tech' => 'BEAD TO BEAD SHIELD',
            'expected_life_km' => 3000,
            'rolling_resistance_watts' => 16.0,
            'weight_g' => 445,
            'ean_code' => '3528705648480',
        ]);

        $p3 = Product::where('global_id', 'BI-129')->first() ?? Product::create([
            'global_id' => 'BI-129',
            'web_range_name' => 'Power Adventure',
            'segment' => 'GRAVEL',
            'width_etrto' => 48,
            'diameter_etrto' => 584,
            'tpi' => 100,
            'min_pressure_bar' => 2.5,
            'max_pressure_bar' => 4.0,
            'rubber_tech' => 'GUM-X',
            'casing_tech' => 'BEAD TO BEAD SHIELD',
            'expected_life_km' => 6000,
            'rolling_resistance_watts' => 20.0,
            'weight_g' => 510,
            'ean_code' => '3528706213281',
        ]);

        $this->recommendedProducts = [$p1, $p2, $p3];
    }

    /**
     * Mount the selected product to the user's bike.
     */
    public function mountProduct(int $productId): void
    {
        $user = Auth::user();
        if (!$user) {
            Flux::toast(
                variant: 'danger',
                text: __('Veuillez vous connecter pour monter un pneu.')
            );
            return;
        }

        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        // Deactivate previous active tire at position REAR (standard demonstration position)
        UserTire::where('user_id', $user->id)
            ->where('position', \App\Enums\TirePosition::Rear)
            ->update(['is_active' => false]);

        // Create the new tire
        UserTire::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'position' => \App\Enums\TirePosition::Rear,
            'mounted_at' => now(),
            'mounted_odometer_km' => 1500, // mock current odometer
            'wear_percent' => 0.00,
            'is_active' => true,
        ]);

        Flux::toast(
            variant: 'success',
            text: __('Le pneu :name a été monté à l\'arrière !', ['name' => $product->web_range_name])
        );

        $this->dispatch('tire-mounted');
    }

    /**
     * Get mock performance match details for the 3 tires.
     */
    public function getMockPerformanceData(string $globalId): array
    {
        return match ($globalId) {
            'BI-38' => [
                'match_percent' => 78,
                'match_badge' => 'success',
                'match_label' => __('Choix Standard'),
                'price' => '44,90 €',
                'puncture_resistance' => '⭐⭐⭐⭐',
                'grip' => '⭐⭐⭐⭐',
                'rolling_efficiency' => '⭐⭐⭐',
                'description' => __('Polyvalent et robuste pour toutes les sorties.'),
            ],
            'BI-177' => [
                'match_percent' => 96,
                'match_badge' => 'accent',
                'match_label' => __('Recommandé pour vous'),
                'price' => '59,90 €',
                'puncture_resistance' => '⭐⭐⭐',
                'grip' => '⭐⭐⭐⭐⭐',
                'rolling_efficiency' => '⭐⭐⭐⭐⭐',
                'description' => __('Idéal pour vos performances Strava (vitesse moyenne 24 km/h).'),
            ],
            'BI-129' => [
                'match_percent' => 85,
                'match_badge' => 'warning',
                'match_label' => __('Alternative Endurante'),
                'price' => '49,90 €',
                'puncture_resistance' => '⭐⭐⭐⭐⭐',
                'grip' => '⭐⭐⭐',
                'rolling_efficiency' => '⭐⭐⭐⭐',
                'description' => __('Recommandé pour vos sorties de plus de 100 km.'),
            ],
            default => [
                'match_percent' => 50,
                'match_badge' => 'zinc',
                'match_label' => __('Non évalué'),
                'price' => '40,00 €',
                'puncture_resistance' => '⭐⭐⭐',
                'grip' => '⭐⭐⭐',
                'rolling_efficiency' => '⭐⭐⭐',
                'description' => '',
            ]
        };
    }
}; ?>

<div class="flex flex-col gap-5">
    <!-- Header explaining the mock algorithm -->
    <div class="p-4 bg-gradient-to-br from-michelin-blue/10 to-michelin-midnight/5 dark:from-michelin-blue/20 dark:to-michelin-midnight/20 rounded-2xl border border-michelin-blue/10 dark:border-michelin-blue/20 flex flex-col gap-2">
        <div class="flex items-center gap-2">
            <span class="p-1.5 bg-accent/15 dark:bg-accent/20 rounded-lg text-accent dark:text-michelin-blue-light">
                <flux:icon icon="bolt" class="size-4" />
            </span>
            <h3 class="text-sm font-black text-zinc-800 dark:text-zinc-100 uppercase tracking-widest leading-none">
                {{ __('Comparateur de Performance Michelin (POC)') }}
            </h3>
        </div>
        <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
            {{ __('Notre algorithme a analysé vos 6 derniers mois d\'activités Strava (vitesse moyenne de 24 km/h, terrains à 70% bitume / 30% chemins). Voici les 3 pneus gravel recommandés pour votre profil.') }}
        </p>
    </div>

    <!-- Comparison Table Grid (Scrollable on Mobile) -->
    <div class="overflow-x-auto pb-2 -mx-5 px-5 sm:mx-0 sm:px-0 scrollbar-thin">
        <div class="min-w-[620px] grid grid-cols-4 gap-4 text-xs">
            <!-- Left Header Row Titles -->
            <div class="flex flex-col justify-between py-2 text-zinc-400 dark:text-zinc-500 font-bold uppercase tracking-wider text-[9px] gap-6">
                <div class="h-20 flex items-center">{{ __('Modèle') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Indice d\'Adéquation') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Section ETRTO') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Prix Public') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Rendement') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Grip Humide') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Anti-crevaison') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Longévité Estimée') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Pertes (Watts)') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Mélange / Gomme') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Avis Algorithme') }}</div>
                <div class="border-t border-zinc-200/50 dark:border-zinc-800/50 pt-2.5">{{ __('Sélection') }}</div>
            </div>

            <!-- Product Columns -->
            @foreach($recommendedProducts as $product)
                @php
                    $mock = $this->getMockPerformanceData($product->global_id);
                    $isBest = $mock['match_percent'] >= 90;
                @endphp
                <div class="bg-white dark:bg-zinc-900 border {{ $isBest ? 'border-accent dark:border-michelin-blue-light shadow-md shadow-accent/5' : 'border-zinc-200/60 dark:border-zinc-800/70' }} rounded-2xl p-4 flex flex-col gap-6 relative transition-all duration-300 hover:shadow-lg">
                    <!-- Highlight badge for best match -->
                    @if($isBest)
                        <div class="absolute -top-2.5 left-1/2 transform -translate-x-1/2 bg-accent text-white text-[8px] font-black uppercase tracking-widest px-2.5 py-0.5 rounded-full shadow-sm">
                            {{ __('Recommandé') }}
                        </div>
                    @endif

                    <!-- Product Image & Name -->
                    <div class="h-20 flex flex-col gap-1">
                        <span class="text-[8px] uppercase font-black text-zinc-400 tracking-wider">Michelin</span>
                        <h4 class="font-extrabold text-xs text-zinc-800 dark:text-zinc-100 leading-tight">{{ $product->web_range_name }}</h4>
                        <span class="text-[9px] text-zinc-500 font-bold mt-0.5">{{ $product->width_etrto }}-{{ $product->diameter_etrto }}</span>
                    </div>

                    <!-- Match Score Badge -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 flex items-center gap-2">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full font-black text-[11px] {{ $isBest ? 'bg-accent/10 text-accent dark:bg-michelin-blue/20 dark:text-michelin-blue-light' : 'bg-zinc-150 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300' }}">
                            {{ $mock['match_percent'] }}%
                        </div>
                        <span class="text-[8px] font-black uppercase tracking-wider {{ $isBest ? 'text-accent dark:text-michelin-blue-light' : 'text-zinc-500' }}">
                            {{ $mock['match_label'] }}
                        </span>
                    </div>

                    <!-- Dimensions -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 font-bold text-zinc-700 dark:text-zinc-300">
                        {{ $product->width_etrto }} mm (28")
                    </div>

                    <!-- Price -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 font-extrabold text-zinc-800 dark:text-zinc-200">
                        {{ $mock['price'] }}
                    </div>

                    <!-- Ratings stars -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 text-[10px] text-amber-500 dark:text-amber-450 tracking-wider">
                        {{ $mock['rolling_efficiency'] }}
                    </div>
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 text-[10px] text-amber-500 dark:text-amber-450 tracking-wider">
                        {{ $mock['grip'] }}
                    </div>
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 text-[10px] text-amber-500 dark:text-amber-450 tracking-wider">
                        {{ $mock['puncture_resistance'] }}
                    </div>

                    <!-- Lifespan km -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 font-extrabold text-zinc-800 dark:text-zinc-200">
                        {{ number_format($product->expected_life_km, 0, ',', ' ') }} km
                    </div>

                    <!-- Rolling Resistance -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 font-bold text-zinc-700 dark:text-zinc-300">
                        {{ $product->rolling_resistance_watts ? $product->rolling_resistance_watts . ' W' : 'N/A' }}
                    </div>

                    <!-- Compound & casing -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5">
                        <flux:badge size="sm" variant="success" class="font-bold text-[8px] uppercase tracking-wider text-green-700 dark:text-green-400">{{ $product->rubber_tech }}</flux:badge>
                    </div>

                    <!-- Description advice -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 text-[9px] leading-normal text-zinc-500 dark:text-zinc-400 h-10 line-clamp-3">
                        {{ $mock['description'] }}
                    </div>

                    <!-- Mount Button -->
                    <div class="border-t border-zinc-100 dark:border-zinc-850 pt-2.5 mt-auto">
                        <flux:button 
                            size="xs" 
                            variant="{{ $isBest ? 'primary' : 'outline' }}" 
                            wire:click="mountProduct({{ $product->id }})"
                            class="w-full font-bold"
                        >
                            {{ __('Monter ce pneu') }}
                        </flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
