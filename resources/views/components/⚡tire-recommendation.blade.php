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
    {{-- En-tête : l'algorithme (POC) --}}
    <div class="rr-card--blue flex flex-col gap-1.5">
        <span class="text-[11px] font-extrabold uppercase tracking-wider text-michelin-yellow">{{ __('Comparateur de Performance Michelin (POC)') }}</span>
        <p class="text-sm leading-relaxed text-white/85">
            {{ __('Notre algorithme a analysé vos 6 derniers mois d\'activités Strava (vitesse moyenne de 24 km/h, terrains à 70% bitume / 30% chemins). Voici les 3 pneus gravel recommandés pour votre profil.') }}
        </p>
    </div>

    {{-- Les 3 pneus recommandés --}}
    <div class="flex flex-col gap-4">
        @foreach ($recommendedProducts as $product)
            @php
                $mock = $this->getMockPerformanceData($product->global_id);
                $isBest = $mock['match_percent'] >= 90;
            @endphp
            <div wire:key="reco-{{ $product->id }}" @class([
                'rr-card relative flex flex-col gap-4',
                '!border-michelin-blue shadow-md' => $isBest,
            ])>
                @if ($isBest)
                    <span class="rr-badge-tag absolute -top-2.5 right-4">{{ __('Recommandé') }}</span>
                @endif

                {{-- En-tête produit --}}
                <div class="flex items-center gap-3">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-michelin-blue">
                        <img src="{{ $product->image_url ?? asset('images/michelin_bike_tire.jpg') }}" alt="{{ $product->web_range_name }}" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="rr-card__eyebrow">Michelin</span>
                        <h4 class="truncate text-base font-black text-michelin-blue-dark">{{ $product->web_range_name }}</h4>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="text-xs font-bold text-michelin-gray">{{ $product->width_etrto }}-{{ $product->diameter_etrto }}</span>
                            <span class="text-sm font-black text-michelin-blue">{{ $mock['price'] }}</span>
                        </div>
                    </div>
                    <div class="flex shrink-0 flex-col items-center">
                        <span class="text-lg font-black tabular-nums {{ $isBest ? 'text-michelin-blue' : 'text-michelin-gray' }}">{{ $mock['match_percent'] }}%</span>
                        <span class="rr-card__eyebrow">Match</span>
                    </div>
                </div>

                {{-- Verdict de l'algorithme --}}
                <span class="self-start {{ $isBest ? 'rr-chip--ok' : 'rr-chip' }}">{{ $mock['match_label'] }}</span>

                @if ($mock['description'])
                    <p class="rr-quote">{{ $mock['description'] }}</p>
                @endif

                {{-- Caractéristiques --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 border-y border-[rgba(39,80,155,0.1)] py-2.5 text-xs">
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Rendement') }}</span>
                        <span class="font-bold text-michelin-warning">{{ $mock['rolling_efficiency'] }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Gomme') }}</span>
                        <span class="rr-ok font-bold">{{ $product->rubber_tech }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Adhérence') }}</span>
                        <span class="font-bold text-michelin-warning">{{ $mock['grip'] }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Poids') }}</span>
                        <span class="font-bold text-michelin-blue-dark">{{ $product->weight_g ? $product->weight_g . ' g' : 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Anti-crevaison') }}</span>
                        <span class="font-bold text-michelin-warning">{{ $mock['puncture_resistance'] }}</span>
                    </div>
                    <div class="flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Pertes') }}</span>
                        <span class="font-bold text-michelin-blue-dark">{{ $product->rolling_resistance_watts ? $product->rolling_resistance_watts . ' W' : 'N/A' }}</span>
                    </div>
                    <div class="col-span-2 flex justify-between gap-2">
                        <span class="text-michelin-gray">{{ __('Longévité estimée') }}</span>
                        <span class="rr-ok font-black">{{ number_format($product->expected_life_km, 0, ',', ' ') }} km</span>
                    </div>
                </div>

                {{-- Actions (empilées : pleine largeur, pas de retour à la ligne) --}}
                <div class="flex flex-col gap-2">
                    <button type="button" wire:click="mountProduct({{ $product->id }})" @class([
                        'rr-btn rr-btn--sm w-full',
                        'rr-btn--secondary' => ! $isBest,
                    ])>{{ __('Monter ce pneu') }}</button>

                    <a href="https://www.decathlon.fr/search?Ntt=michelin+{{ urlencode($product->web_range_name) }}" target="_blank" rel="noopener" class="rr-btn rr-btn--secondary rr-btn--sm w-full">
                        {{ __('Acheter partenaire') }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
