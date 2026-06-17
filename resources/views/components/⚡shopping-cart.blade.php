<?php

use Livewire\Component;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public ?Product $recommendedProduct = null;
    
    // Cart quantities
    public int $tireQty = 2;
    public int $sealantQty = 1;
    public int $valvesQty = 1;

    // Cart visibility / toggles
    public bool $includeSealant = true;
    public bool $includeValves = true;

    // Pricing
    public float $tirePrice = 54.99;
    public float $sealantPrice = 9.90;
    public float $valvesPrice = 14.90;
    public float $shipping = 4.90;

    public function mount(): void
    {
        $this->loadRecommendedProduct();
    }

    public function loadRecommendedProduct(): void
    {
        $user = Auth::user();
        
        // Match recommendation by user segment. Marc is GRAVEL, so BI-177 (Power Gravel RS)
        if ($user && $user->segment && $user->segment->value === 'ROAD') {
            $this->recommendedProduct = Product::where('global_id', 'BI-127')->first() ?? Product::first();
        } else {
            $this->recommendedProduct = Product::where('global_id', 'BI-177')->first() ?? Product::first();
        }
    }

    // Reactively compute prices
    public function getSubtotalProperty(): float
    {
        $total = $this->tireQty * $this->tirePrice;
        
        if ($this->includeSealant) {
            $total += $this->sealantQty * $this->sealantPrice;
        }
        
        if ($this->includeValves) {
            $total += $this->valvesQty * $this->valvesPrice;
        }

        return round($total, 2);
    }

    public function getTotalProperty(): float
    {
        $subtotal = $this->getSubtotalProperty();
        if ($subtotal > 80) {
            // Free shipping on orders over 80 EUR
            return round($subtotal, 2);
        }
        return round($subtotal + $this->shipping, 2);
    }

    public function checkout(): void
    {
        // Simulated checkout redirection message
        $url = 'https://www.decathlon.fr/search?Ntt=' . urlencode($this->recommendedProduct?->web_range_name ?? 'Michelin');
        
        Flux::toast(
            variant: 'success',
            text: __('Redirection vers Decathlon pour finaliser l\'achat...'),
        );

        $this->js("window.open('$url', '_blank')");
    }
}; ?>

<div id="shopping-cart-section" class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-col gap-1.5 mb-5 border-b border-zinc-150 dark:border-zinc-800 pb-3">
        <div class="flex items-center justify-between">
            <h3 class="text-md font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
                <span class="inline-block w-2 h-5 bg-michelin-blue rounded-full"></span>
                {{ __('Votre Panier de Remplacement Michelin') }}
            </h3>
            <span class="bg-michelin-yellow/20 text-zinc-800 dark:text-zinc-200 px-2 py-0.5 rounded-full text-[10px] font-black border border-michelin-yellow/40">
                {{ $this->tireQty + ($includeSealant ? $this->sealantQty : 0) + ($includeValves ? $this->valvesQty : 0) }} articles
            </span>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Paire de pneus recommandés pré-remplie avec accessoires de montage.') }}
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Products List -->
        <div class="lg:col-span-2 flex flex-col gap-4">
            <!-- Tire Recommended -->
            <div class="flex flex-col sm:flex-row gap-4 bg-zinc-50 dark:bg-zinc-950 p-4 rounded-xl border border-zinc-100 dark:border-zinc-800/50">
                <div class="w-16 h-16 rounded-lg bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center shrink-0 p-1">
                    <svg class="size-10 text-zinc-300 dark:text-zinc-700" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <circle cx="12" cy="12" r="9" />
                        <circle cx="12" cy="12" r="3" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v6m0 6v6M3 12h6m6 0h6m-3.17-5.83-4.24 4.24m-4.24 4.24-4.24 4.24M5.83 5.83l4.24 4.24m4.24 4.24 4.24 4.24" />
                    </svg>
                </div>
                
                <div class="flex-1 flex flex-col sm:flex-row sm:justify-between sm:items-start gap-2">
                    <div>
                        <span class="bg-michelin-blue/10 text-michelin-blue dark:text-michelin-blue-light text-[9px] px-2 py-0.5 rounded font-bold uppercase tracking-wider block w-max mb-1">
                            {{ __('Recommandé pour vous') }}
                        </span>
                        <h4 class="text-sm font-black text-zinc-800 dark:text-zinc-100 uppercase">
                            {{ $recommendedProduct?->web_range_name ?? 'Power Gravel RS' }}
                        </h4>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                            700×{{ $recommendedProduct?->width_etrto ?? 42 }}C · {{ $recommendedProduct?->casing_tech ?? 'Bead to Bead Shield' }}
                        </p>
                        <p class="text-[10px] text-zinc-400 dark:text-zinc-500 mt-1">
                            Ref: {{ $recommendedProduct?->ean_code ?? '3528705648480' }}
                        </p>
                    </div>

                    <div class="flex sm:flex-col items-center sm:items-end justify-between sm:justify-start gap-4">
                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ number_format($tirePrice, 2) }} €</span>
                        
                        <div class="flex items-center gap-1.5 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-0.5 shadow-sm">
                            <button type="button" wire:click="$set('tireQty', {{ max(1, $tireQty - 1) }})" class="w-6 h-6 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-500 cursor-pointer">-</button>
                            <span class="w-6 text-center text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $tireQty }}</span>
                            <button type="button" wire:click="$set('tireQty', {{ $tireQty + 1 }})" class="w-6 h-6 rounded-md hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs font-bold text-zinc-500 cursor-pointer">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cross-sells Section Header -->
            <div class="mt-2">
                <h4 class="text-xs font-black text-zinc-400 dark:text-zinc-500 uppercase tracking-widest mb-3 flex items-center gap-1.5">
                    <flux:icon icon="sparkles" class="size-3.5" />
                    {{ __('Indispensables pour le montage Tubeless') }}
                </h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Sealant -->
                    <div class="flex items-start gap-3 bg-zinc-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800/50 relative">
                        <input type="checkbox" wire:model.live="includeSealant" id="chk-sealant" class="mt-1.5 accent-michelin-blue cursor-pointer">
                        <label for="chk-sealant" class="flex-1 flex flex-col gap-0.5 cursor-pointer select-none">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">Liquide Préventif Muc-Off 140ml</span>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Pour 2 pneus gravel</span>
                            <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-1">{{ number_format($sealantPrice, 2) }} €</span>
                        </label>
                        @if($includeSealant)
                            <div class="absolute bottom-3 right-3 flex items-center gap-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-0.5 shadow-sm scale-90">
                                <button type="button" wire:click="$set('sealantQty', {{ max(1, $sealantQty - 1) }})" class="w-5 h-5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 cursor-pointer">-</button>
                                <span class="w-4 text-center text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $sealantQty }}</span>
                                <button type="button" wire:click="$set('sealantQty', {{ $sealantQty + 1 }})" class="w-5 h-5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 cursor-pointer">+</button>
                            </div>
                        @endif
                    </div>

                    <!-- Valves -->
                    <div class="flex items-start gap-3 bg-zinc-50 dark:bg-zinc-950 p-3.5 rounded-xl border border-zinc-100 dark:border-zinc-800/50 relative">
                        <input type="checkbox" wire:model.live="includeValves" id="chk-valves" class="mt-1.5 accent-michelin-blue cursor-pointer">
                        <label for="chk-valves" class="flex-1 flex flex-col gap-0.5 cursor-pointer select-none">
                            <span class="text-xs font-bold text-zinc-800 dark:text-zinc-100">Valves Tubeless Michelin 40mm</span>
                            <span class="text-[10px] text-zinc-400 dark:text-zinc-500">Lot de 2 valves noires</span>
                            <span class="text-xs font-bold text-zinc-600 dark:text-zinc-400 mt-1">{{ number_format($valvesPrice, 2) }} €</span>
                        </label>
                        @if($includeValves)
                            <div class="absolute bottom-3 right-3 flex items-center gap-1 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-lg p-0.5 shadow-sm scale-90">
                                <button type="button" wire:click="$set('valvesQty', {{ max(1, $valvesQty - 1) }})" class="w-5 h-5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 cursor-pointer">-</button>
                                <span class="w-4 text-center text-xs font-bold text-zinc-800 dark:text-zinc-100">{{ $valvesQty }}</span>
                                <button type="button" wire:click="$set('valvesQty', {{ $valvesQty + 1 }})" class="w-5 h-5 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 flex items-center justify-center text-xs text-zinc-500 cursor-pointer">+</button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary & Checkout -->
        <div class="bg-zinc-50 dark:bg-zinc-950/40 p-4 rounded-xl border border-zinc-150 dark:border-zinc-800/60 flex flex-col justify-between">
            <div>
                <span class="text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider block mb-3">{{ __('RÉCAPITULATIF') }}</span>
                
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                        <span>{{ $tireQty }}x {{ $recommendedProduct?->web_range_name ?? 'Power Gravel RS' }}</span>
                        <span>{{ number_format($tireQty * $tirePrice, 2) }} €</span>
                    </div>

                    @if($includeSealant)
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>{{ $sealantQty }}x Preventif Muc-Off</span>
                            <span>{{ number_format($sealantQty * $sealantPrice, 2) }} €</span>
                        </div>
                    @endif

                    @if($includeValves)
                        <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                            <span>{{ $valvesQty }}x Valves Michelin</span>
                            <span>{{ number_format($valvesQty * $valvesPrice, 2) }} €</span>
                        </div>
                    @endif

                    <flux:separator class="my-2" />

                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                        <span>{{ __('Sous-total') }}</span>
                        <span>{{ number_format($this->subtotal, 2) }} €</span>
                    </div>

                    <div class="flex justify-between text-zinc-600 dark:text-zinc-400">
                        <span>{{ __('Frais de livraison') }}</span>
                        @if($this->subtotal >= 80)
                            <span class="text-michelin-success font-bold uppercase text-[10px] bg-michelin-success/10 px-1.5 py-0.5 rounded">{{ __('Offert') }}</span>
                        @else
                            <span>{{ number_format($shipping, 2) }} €</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <flux:separator class="mb-3" />
                <div class="flex justify-between items-baseline mb-4">
                    <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ __('Total TTC') }}</span>
                    <span class="text-xl font-black text-michelin-blue dark:text-michelin-blue-light">{{ number_format($this->total, 2) }} €</span>
                </div>

                <button type="button" wire:click="checkout" class="w-full bg-michelin-yellow hover:bg-michelin-yellow-dark-03 text-zinc-950 font-black text-xs uppercase tracking-wider py-3.5 rounded-xl shadow-md cursor-pointer transition-colors flex items-center justify-center gap-1.5">
                    <flux:icon icon="shopping-bag" class="size-4 text-zinc-950" />
                    {{ __('Passer la commande') }}
                </button>
                <span class="text-[9px] text-zinc-400 dark:text-zinc-500 text-center block mt-2">
                    {{ __('Partenaire officiel Decathlon') }}
                </span>
            </div>
        </div>
    </div>
</div>
