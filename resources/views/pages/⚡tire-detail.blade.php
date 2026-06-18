<?php

use App\Enums\TirePosition;
use App\Models\StravaActivity;
use App\Models\UserTire;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Tire detail')] class extends Component
{
    public UserTire $userTire;

    public function mount(UserTire $userTire): void
    {
        abort_unless($userTire->user_id === auth()->id(), 403);

        $this->userTire = $userTire->load('product');
    }

    /**
     * The rides ridden on this tire (the owner's activities since it was mounted).
     *
     * @return Collection<int, StravaActivity>
     */
    #[Computed]
    public function rides(): Collection
    {
        return StravaActivity::query()
            ->forUserTire($this->userTire)
            ->orderByDesc('start_date')
            ->get();
    }

    /**
     * Total kilometers ridden on this tire (deterministic — sum of its rides).
     */
    #[Computed]
    public function kmOnTire(): float
    {
        return (int) $this->rides->sum('distance_m') / 1000;
    }
}; ?>

@php
    $wear = (float) ($userTire->wear_percent ?? 0);
    $wearTone = $wear >= 80 ? 'danger' : ($wear >= 50 ? 'warn' : 'ok');
    $wearHex = $wear >= 80 ? '#b71c1c' : ($wear >= 50 ? '#f9a825' : '#27509b');
@endphp

<div class="rr-screen">
    <div class="rr-section">
        <p class="rr-section__label">{{ __('Tire detail') }}</p>
        <h1 class="rr-section__title">{{ $userTire->product->web_range_name }}</h1>
        <p class="rr-section__sub">
            {{ $userTire->position === TirePosition::Front ? __('Front') : __('Rear') }}
            · {{ __('Mounted on') }} {{ $userTire->mounted_at?->translatedFormat('j M Y') ?? '—' }}
        </p>
    </div>

    <div class="rr-body">
        {{-- TUILES DE STATS --}}
        <div class="rr-gauges">
            <div class="rr-gauge" data-test="stat-km">
                <div class="rr-gauge__pos">{{ __('Distance on this tire') }}</div>
                <div class="rr-gauge__km">{{ Number::format($this->kmOnTire, maxPrecision: 0) }} km</div>
            </div>

            <div class="rr-gauge" data-test="stat-rides">
                <div class="rr-gauge__pos">{{ __('Ride count') }}</div>
                <div class="rr-gauge__km">{{ $this->rides->count() }}</div>
            </div>

            <div class="rr-gauge" data-test="stat-wear">
                <div class="rr-gauge__pos">{{ __('Wear (provisional)') }}</div>
                <div class="rr-gauge__km rr-{{ $wearTone }}">{{ $userTire->wear_percent !== null ? number_format($wear, 0).'%' : '—' }}</div>
            </div>
        </div>
        <p class="rr-quote">{{ __('Wear is computed by the scoring engine — provisional for now.') }}</p>

        <hr class="rr-divider">

        {{-- SORTIES DEPUIS LE MONTAGE --}}
        <div class="rr-card__eyebrow">{{ __('Rides on this tire') }} ({{ $this->rides->count() }})</div>

        @if ($this->rides->isNotEmpty())
            <div class="rr-card" data-test="tire-rides-table">
                @foreach ($this->rides as $ride)
                    @php $surface = $ride->surface_derived; @endphp
                    <div class="rr-row" data-test="tire-ride-row">
                        <div class="rr-row__key">
                            {{ $ride->start_date->translatedFormat('j M Y') }}
                            @if ($surface)
                                <span class="rr-badge-tag">{{ __($surface->name) }}</span>
                            @endif
                        </div>
                        <div class="rr-row__val">
                            {{ Number::format($ride->distance_m / 1000, precision: 1) }} km
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rr-card" data-test="tire-rides-empty">
                <p class="rr-quote">{{ __('No ride recorded on this tire yet.') }}</p>
            </div>
        @endif

        <a href="{{ route('tires') }}" wire:navigate class="rr-btn--secondary rr-btn">
            {{ __('Back to my tires') }}
        </a>
    </div>
</div>
