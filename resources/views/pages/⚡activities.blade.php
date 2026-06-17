<?php

use App\Enums\TirePosition;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Activities')] class extends Component
{
    use WithPagination;

    public ?int $editingId = null;

    public ?int $editFront = null;

    public ?int $editRear = null;

    /**
     * Les sorties du cycliste, les plus récentes d'abord, avec leurs pneus montés.
     *
     * @return LengthAwarePaginator<int, \App\Models\StravaActivity>
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return auth()->user()
            ->stravaActivities()
            ->select(['id', 'sport_type', 'surface_derived', 'distance_m', 'total_elevation_gain_m', 'start_date', 'tires_confirmed', 'front_tire_id', 'rear_tire_id'])
            ->with(['frontTire.product', 'rearTire.product'])
            ->orderByDesc('start_date')
            ->paginate(20);
    }

    /**
     * Nombre de sorties dont les pneus restent à confirmer.
     */
    #[Computed]
    public function unconfirmedCount(): int
    {
        return auth()->user()->stravaActivities()->where('tires_confirmed', false)->count();
    }

    /**
     * Pneus avant de la collection (pour le sélecteur).
     *
     * @return Collection<int, \App\Models\UserTire>
     */
    #[Computed]
    public function frontTires(): Collection
    {
        return auth()->user()->tires()->with('product')->where('position', TirePosition::Front)->get();
    }

    /**
     * Pneus arrière de la collection (pour le sélecteur).
     *
     * @return Collection<int, \App\Models\UserTire>
     */
    #[Computed]
    public function rearTires(): Collection
    {
        return auth()->user()->tires()->with('product')->where('position', TirePosition::Rear)->get();
    }

    /**
     * Ouvre l'édition des pneus d'une sortie.
     */
    public function startEdit(int $activityId): void
    {
        $activity = auth()->user()->stravaActivities()->findOrFail($activityId);

        $this->editingId = $activity->id;
        $this->editFront = $activity->front_tire_id;
        $this->editRear = $activity->rear_tire_id;
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editFront', 'editRear');
    }

    /**
     * Enregistre les pneus choisis pour la sortie et la marque vérifiée.
     */
    public function saveRide(): void
    {
        $owned = auth()->user()->tires()->pluck('id');

        $this->validate([
            'editFront' => ['nullable', Rule::in($owned)],
            'editRear' => ['nullable', Rule::in($owned)],
        ]);

        auth()->user()->stravaActivities()->whereKey($this->editingId)->update([
            'front_tire_id' => $this->editFront,
            'rear_tire_id' => $this->editRear,
            'tires_confirmed' => true,
        ]);

        $this->reset('editingId', 'editFront', 'editRear');
        unset($this->activities, $this->unconfirmedCount);
    }

    /**
     * Confirme les pneus auto-assignés sur toutes les sorties en attente.
     */
    public function confirmAllTires(): void
    {
        auth()->user()->stravaActivities()->where('tires_confirmed', false)->update(['tires_confirmed' => true]);

        unset($this->unconfirmedCount);
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <style>
        @keyframes mt-rise { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: translateY(0); } }
        .mt-rise { animation: mt-rise .5s cubic-bezier(.16,1,.3,1) both; }
        .mt-field { width: 100%; border-radius: 0.625rem; border: 1px solid var(--color-zinc-300); background: var(--color-blanc); padding: 0.5rem 0.75rem; font-size: 0.8125rem; font-weight: 500; color: var(--color-zinc-800); }
        .mt-field:focus { outline: none; border-color: var(--color-michelin-blue); box-shadow: 0 0 0 3px color-mix(in oklab, var(--color-michelin-blue), transparent 75%); }
        .dark .mt-field { border-color: var(--color-zinc-700); background: var(--color-zinc-900); color: var(--color-zinc-100); }
    </style>

    {{-- HEADER --}}
    <header class="mt-rise">
        <p class="text-[10px] font-black uppercase tracking-[0.25em] text-michelin-blue dark:text-michelin-blue-light">Strava</p>
        <h1 class="mt-1 text-2xl font-black tracking-tight text-zinc-900 dark:text-white">{{ __('Your activities') }}</h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice('rides.imported', $this->activities->total(), ['count' => $this->activities->total()]) }}
        </p>
    </header>

    {{-- BANNIÈRE DE VÉRIFICATION --}}
    @if ($this->unconfirmedCount > 0)
        <div class="mt-rise flex flex-wrap items-center justify-between gap-3 rounded-xl border border-michelin-warning/30 bg-michelin-warning/10 px-4 py-3" data-test="verify-tires-banner">
            <span class="flex items-center gap-2.5 text-sm font-medium text-zinc-700 dark:text-zinc-200">
                <svg class="size-5 shrink-0 text-michelin-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                {{ trans_choice('rides.to_verify', $this->unconfirmedCount, ['count' => $this->unconfirmedCount]) }}
            </span>
            <button type="button" wire:click="confirmAllTires" data-test="confirm-all-tires"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-michelin-blue px-3.5 py-2 text-xs font-black uppercase tracking-wider text-white transition hover:bg-michelin-blue-dark">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                {{ __('Confirm all') }}
            </button>
        </div>
    @endif

    @if ($this->activities->isEmpty())
        <div class="mt-rise flex flex-col items-center gap-3 rounded-2xl border border-dashed border-zinc-300 px-6 py-12 text-center dark:border-zinc-700" data-test="activities-empty">
            <svg class="size-9 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg>
            <div class="flex flex-col gap-1">
                <h2 class="text-base font-black text-zinc-800 dark:text-zinc-100">{{ __('No activities yet') }}</h2>
                <p class="text-sm text-zinc-500">{{ __('Connect with Strava to import your rides.') }}</p>
            </div>
            <a href="{{ route('strava.connect') }}" data-test="strava-connect-cta"
               class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#FC4C02] px-4 py-2 text-sm font-medium text-white transition hover:opacity-90">
                {{ __('Connect with Strava') }}
            </a>
        </div>
    @else
        <div class="flex flex-col gap-3" data-test="activities-cards">
            @foreach ($this->activities as $activity)
                @php $surface = $activity->surface_derived; @endphp
                <article wire:key="act-{{ $activity->id }}" data-test="activity-card"
                         class="mt-rise rounded-2xl border bg-white p-4 shadow-sm dark:bg-zinc-900 {{ $activity->tires_confirmed ? 'border-zinc-200 dark:border-zinc-800' : 'border-michelin-warning/40 ring-1 ring-michelin-warning/20' }}"
                         style="animation-delay: {{ min(0.3, $loop->index * 0.03) }}s">
                    {{-- En-tête --}}
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 flex-col">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-black text-zinc-900 dark:text-white">{{ $activity->start_date->translatedFormat('j M Y') }}</span>
                                @unless ($activity->tires_confirmed)
                                    <span class="rounded-full bg-michelin-warning/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-michelin-warning" data-test="ride-unverified">{{ __('To verify') }}</span>
                                @endunless
                            </div>
                            <span class="text-[11px] font-bold uppercase tracking-wider text-zinc-400">{{ __(Str::headline($activity->sport_type)) }}</span>
                        </div>
                        @if ($surface)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                <span class="size-1.5 rounded-full {{ $surface === \App\Enums\Surface::Asphalt ? 'bg-michelin-blue' : 'bg-michelin-green' }}"></span>
                                {{ __($surface->name) }}
                            </span>
                        @endif
                    </div>

                    {{-- Métriques --}}
                    <div class="mt-3 flex gap-8">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-zinc-400">{{ __('Distance') }}</p>
                            <p class="text-lg font-black tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ Number::format($activity->distance_m / 1000, precision: 1) }} km</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-zinc-400">{{ __('Elevation') }}</p>
                            <p class="text-lg font-black tracking-tight text-zinc-900 tabular-nums dark:text-white">{{ Number::format($activity->total_elevation_gain_m) }} m</p>
                        </div>
                    </div>

                    {{-- Pneus montés / édition --}}
                    <div class="mt-3 border-t border-zinc-100 pt-3 dark:border-zinc-800/60">
                        @if ($editingId === $activity->id)
                            <div class="flex flex-col gap-3" data-test="ride-edit-{{ $activity->id }}">
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex flex-col gap-1">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ __('Front tire') }}</span>
                                        <select wire:model="editFront" class="mt-field">
                                            <option value="">{{ __('None') }}</option>
                                            @foreach ($this->frontTires as $tire)
                                                <option value="{{ $tire->id }}">{{ $tire->product->web_range_name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="flex flex-col gap-1">
                                        <span class="text-[10px] font-black uppercase tracking-wider text-zinc-500">{{ __('Rear tire') }}</span>
                                        <select wire:model="editRear" class="mt-field">
                                            <option value="">{{ __('None') }}</option>
                                            @foreach ($this->rearTires as $tire)
                                                <option value="{{ $tire->id }}">{{ $tire->product->web_range_name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="saveRide" data-test="ride-save"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-michelin-blue px-3 py-1.5 text-xs font-black uppercase tracking-wider text-white transition hover:bg-michelin-blue-dark">
                                        {{ __('Confirm') }}
                                    </button>
                                    <button type="button" wire:click="cancelEdit" class="rounded-lg px-3 py-1.5 text-xs font-bold text-zinc-500 transition hover:text-zinc-800 dark:hover:text-zinc-200">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 flex-col gap-0.5 text-xs">
                                    <span class="font-bold text-zinc-400">{{ __('Mounted tires') }}</span>
                                    <span class="truncate text-zinc-700 dark:text-zinc-300">
                                        <span class="font-semibold">{{ __('Front') }}</span> {{ $activity->frontTire?->product?->web_range_name ?? '—' }}
                                        · <span class="font-semibold">{{ __('Rear') }}</span> {{ $activity->rearTire?->product?->web_range_name ?? '—' }}
                                    </span>
                                </div>
                                <button type="button" wire:click="startEdit({{ $activity->id }})" data-test="ride-verify-{{ $activity->id }}"
                                        class="shrink-0 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-black uppercase tracking-wider text-michelin-blue transition hover:bg-michelin-blue/10 dark:border-zinc-700 dark:text-michelin-blue-light">
                                    {{ $activity->tires_confirmed ? __('Edit') : __('Verify') }}
                                </button>
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div>{{ $this->activities->links() }}</div>
    @endif
</section>
