<?php

use App\Enums\TirePosition;
use App\Models\UserTire;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Alerts')] class extends Component
{
    /**
     * The rider's end-of-life tires to reorder (archived ones excluded).
     *
     * @return Collection<int, UserTire>
     */
    #[Computed]
    public function endOfLifeTires(): Collection
    {
        return auth()->user()->tires()
            ->with('product')
            ->notArchived()
            ->endOfLife()
            ->orderByDesc('wear_percent')
            ->get();
    }
}; ?>

<div class="rr-screen">
    {{-- HEADER --}}
    <div class="rr-section">
        <p class="rr-section__label">{{ __('Alerts') }}</p>
        <h1 class="rr-section__title">{{ __('End-of-life tires') }}</h1>
        <p class="rr-section__sub">{{ __('These tires need your attention — reorder them whenever you like.') }}</p>
    </div>

    <div class="rr-body">
        @forelse ($this->endOfLifeTires as $tire)
            @php $isFront = $tire->position === TirePosition::Front; @endphp
            <article class="rr-card flex flex-col gap-4" wire:key="reorder-{{ $tire->id }}" data-test="reorder-item">
                <div class="flex items-center gap-3">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-michelin-blue">
                        <img src="{{ $tire->product->image_url ?? asset('images/michelin_bike_tire.jpg') }}" alt="{{ $tire->product->web_range_name }}" class="h-full w-full object-cover" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <span class="rr-card__eyebrow">Michelin</span>
                        <h3 class="truncate text-base font-black text-michelin-blue-dark">{{ $tire->product->web_range_name }}</h3>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="rr-chip">{{ $isFront ? __('Front') : __('Rear') }}</span>
                            <span class="rr-chip--danger">{{ number_format((float) $tire->wear_percent, 0) }}% · {{ __('End of life') }}</span>
                            <span class="font-bold text-michelin-gray">{{ $tire->product->width_etrto }}-{{ $tire->product->diameter_etrto }}</span>
                        </div>
                    </div>
                </div>

                <a href="https://www.decathlon.fr/search?Ntt=michelin+{{ urlencode($tire->product->web_range_name) }}" target="_blank" rel="noopener" class="rr-btn tracking-normal" data-test="reorder-decathlon-{{ $tire->id }}">
                    {{ __('Order on Decathlon') }}
                </a>
            </article>
        @empty
            <div class="rr-card flex flex-col items-center gap-3 py-12 text-center" data-test="reorder-empty">
                <svg class="size-11 text-michelin-blue/30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                <h2 class="text-base font-black text-michelin-blue-dark">{{ __('No alerts') }}</h2>
                <p class="text-sm text-michelin-gray">{{ __('No tire needs your attention right now.') }}</p>
                <a href="{{ route('tires') }}" wire:navigate class="rr-btn rr-btn--secondary">{{ __('Back to my tires') }}</a>
            </div>
        @endforelse

        @if ($this->endOfLifeTires->isNotEmpty())
            <p class="text-center text-xs text-michelin-gray">{{ __('You will be redirected to our partner retailer.') }}</p>
        @endif
    </div>
</div>
