<?php

use App\Enums\TirePosition;
use App\Models\StravaActivity;
use App\Models\UserTire;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
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
        return auth()->user()->stravaActivities()
            ->when(
                $this->userTire->mounted_at,
                fn ($query) => $query->where('start_date', '>=', $this->userTire->mounted_at)
            )
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

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <style>
        @keyframes mt-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes mt-ring { from { stroke-dasharray: 0 100; } }
        .mt-rise { animation: mt-rise .6s cubic-bezier(.16,1,.3,1) both; }
        .mt-ring { animation: mt-ring 1.1s cubic-bezier(.34,1.56,.64,1) forwards; }
    </style>

    {{-- HERO sombre à halo bleu (charte Michelin midnight) --}}
    <div class="mt-rise relative overflow-hidden rounded-3xl bg-michelin-midnight px-6 py-7 text-white shadow-xl">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 rounded-full bg-[radial-gradient(circle,rgba(39,80,155,0.55),transparent_70%)]"></div>
        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-px bg-gradient-to-r from-transparent via-michelin-blue-light/40 to-transparent"></div>

        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-michelin-blue-light">{{ __('Tire detail') }}</p>
        <h1 class="mt-1 text-2xl font-black leading-tight tracking-tight">{{ $userTire->product->web_range_name }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-bold">
            <span class="inline-flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-3 py-1 uppercase tracking-wider backdrop-blur">
                <span class="size-1.5 rounded-full bg-michelin-yellow"></span>
                {{ $userTire->position === TirePosition::Front ? __('Front') : __('Rear') }}
            </span>
            <span class="text-white/60">{{ __('Mounted on') }} {{ $userTire->mounted_at?->translatedFormat('j M Y') ?? '—' }}</span>
        </div>
    </div>

    {{-- TUILES DE STATS --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="mt-rise rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" style="animation-delay:.06s" data-test="stat-km">
            <p class="text-[9px] font-black uppercase tracking-wider text-zinc-400">{{ __('Distance on this tire') }}</p>
            <p class="mt-1 text-2xl font-black tracking-tight text-zinc-900 dark:text-white">
                {{ Number::format($this->kmOnTire, maxPrecision: 0) }}<span class="ml-0.5 text-xs font-bold text-zinc-400">km</span>
            </p>
        </div>

        <div class="mt-rise rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" style="animation-delay:.12s" data-test="stat-rides">
            <p class="text-[9px] font-black uppercase tracking-wider text-zinc-400">{{ __('Ride count') }}</p>
            <p class="mt-1 text-2xl font-black tracking-tight text-zinc-900 dark:text-white">{{ $this->rides->count() }}</p>
        </div>

        {{-- Usure : gauge radiale --}}
        <div class="mt-rise flex items-center gap-3 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900" style="animation-delay:.18s" data-test="stat-wear">
            <div class="relative flex size-12 shrink-0 items-center justify-center">
                <svg class="size-12 -rotate-90" viewBox="0 0 36 36">
                    <path class="text-zinc-100 dark:text-zinc-800" stroke="currentColor" stroke-width="4" fill="none" d="M18 2.0845a15.9155 15.9155 0 0 1 0 31.831a15.9155 15.9155 0 0 1 0-31.831" />
                    <path class="mt-ring" stroke="{{ $wearHex }}" stroke-width="4" stroke-linecap="round" fill="none"
                          style="stroke-dasharray: {{ min(100, $wear) }} 100;"
                          d="M18 2.0845a15.9155 15.9155 0 0 1 0 31.831a15.9155 15.9155 0 0 1 0-31.831" />
                </svg>
                <span class="absolute text-[11px] font-black text-zinc-900 dark:text-white">{{ $userTire->wear_percent !== null ? number_format($wear, 0).'%' : '—' }}</span>
            </div>
            <p class="text-[9px] font-black uppercase leading-tight tracking-wider text-zinc-400">{{ __('Wear (provisional)') }}</p>
        </div>
    </div>
    <p class="-mt-2 text-[11px] italic text-zinc-400">{{ __('Wear is computed by the scoring engine — provisional for now.') }}</p>

    {{-- SORTIES DEPUIS LE MONTAGE --}}
    <div class="flex items-center justify-between">
        <h2 class="flex items-center gap-2 text-sm font-black uppercase tracking-wider text-zinc-800 dark:text-zinc-100">
            <span class="inline-block h-4 w-1 rounded-full bg-michelin-blue"></span>
            {{ __('Rides since mount') }}
        </h2>
        <span class="rounded-full bg-zinc-100 px-2.5 py-0.5 text-[11px] font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $this->rides->count() }}</span>
    </div>

    @if ($this->rides->isNotEmpty())
        <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800" data-test="tire-rides-table">
            @foreach ($this->rides as $ride)
                @php $surface = $ride->surface_derived; @endphp
                <div class="mt-rise flex items-center justify-between gap-3 border-b border-zinc-100 bg-white px-4 py-3 last:border-0 dark:border-zinc-800/60 dark:bg-zinc-900"
                     style="animation-delay: {{ min(0.4, $loop->index * 0.03) }}s" data-test="tire-ride-row">
                    <div class="flex min-w-0 flex-col">
                        <span class="text-sm font-bold text-zinc-800 dark:text-zinc-100">{{ $ride->start_date->translatedFormat('j M Y') }}</span>
                        @if ($surface)
                            <span class="mt-0.5 inline-flex w-fit items-center gap-1 text-[10px] font-bold uppercase tracking-wider {{ $surface === \App\Enums\Surface::Asphalt ? 'text-michelin-blue' : 'text-michelin-green' }}">
                                <span class="size-1.5 rounded-full {{ $surface === \App\Enums\Surface::Asphalt ? 'bg-michelin-blue' : 'bg-michelin-green' }}"></span>
                                {{ __($surface->name) }}
                            </span>
                        @endif
                    </div>
                    <span class="shrink-0 text-base font-black tracking-tight text-zinc-900 tabular-nums dark:text-white">
                        {{ Number::format($ride->distance_m / 1000, precision: 1) }} km
                    </span>
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center gap-2 rounded-2xl border border-dashed border-zinc-300 px-6 py-10 text-center dark:border-zinc-700" data-test="tire-rides-empty">
            <svg class="size-8 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.586a1 1 0 0 1 .707.293l5.414 5.414a1 1 0 0 1 .293.707V19a2 2 0 0 1-2 2Z" /></svg>
            <p class="text-sm font-medium text-zinc-500">{{ __('No ride recorded since this tire was mounted.') }}</p>
        </div>
    @endif

    <a href="{{ route('tires') }}" wire:navigate class="inline-flex items-center gap-1.5 text-sm font-bold text-michelin-blue transition hover:gap-2.5 dark:text-michelin-blue-light">
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
        {{ __('Back to my tires') }}
    </a>
</section>
