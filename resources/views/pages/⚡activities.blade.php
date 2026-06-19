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
        return auth()->user()->tires()->notArchived()->with('product')->where('position', TirePosition::Front)->get();
    }

    /**
     * Pneus arrière de la collection (pour le sélecteur).
     *
     * @return Collection<int, \App\Models\UserTire>
     */
    #[Computed]
    public function rearTires(): Collection
    {
        return auth()->user()->tires()->notArchived()->with('product')->where('position', TirePosition::Rear)->get();
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

        $activity = auth()->user()->stravaActivities()->findOrFail($this->editingId);

        // Pneus impactés par le changement : anciens (qui perdent la sortie) + nouveaux.
        $affectedTireIds = array_unique(array_filter([
            $activity->front_tire_id, $activity->rear_tire_id,
            $this->editFront, $this->editRear,
        ]));

        $activity->update([
            'front_tire_id' => $this->editFront,
            'rear_tire_id' => $this->editRear,
            'tires_confirmed' => true,
        ]);

        // L'usure se dérive des sorties associées : on recalcule les pneus impactés.
        auth()->user()->tires()->with('product')->whereKey($affectedTireIds)->get()
            ->each->recomputeWear();

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

<div class="rr-screen">
    <div class="rr-section">
        <p class="rr-section__label">Strava</p>
        <h1 class="rr-section__title">{{ __('Your activities') }}</h1>
        <p class="rr-section__sub">
            {{ trans_choice('rides.imported', $this->activities->total(), ['count' => $this->activities->total()]) }}
        </p>
    </div>

    <div class="rr-body">
        {{-- BANNIÈRE DE VÉRIFICATION --}}
        @if ($this->unconfirmedCount > 0)
            <div class="rr-alert" data-test="verify-tires-banner">
                <div class="rr-alert__left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    <div>
                        <div class="rr-alert__title">{{ trans_choice('rides.to_verify', $this->unconfirmedCount, ['count' => $this->unconfirmedCount]) }}</div>
                    </div>
                </div>
                <button type="button" wire:click="confirmAllTires" data-test="confirm-all-tires" class="rr-alert__cta">
                    {{ __('Confirm all') }}
                </button>
            </div>
        @endif

        @if ($this->activities->isEmpty())
            <div class="rr-card flex flex-col items-center gap-3 py-12 text-center" data-test="activities-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-9 text-michelin-blue/40"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" /></svg>
                <h2 class="text-base font-black text-michelin-blue-dark">{{ __('No activities yet') }}</h2>
                <p class="text-sm text-michelin-gray">{{ __('Connect with Strava to import your rides.') }}</p>
                <a href="{{ route('strava.connect') }}" data-test="strava-connect-cta" class="rr-btn rr-btn--strava">
                    {{ __('Connect with Strava') }}
                </a>
            </div>
        @else
            <div class="flex flex-col gap-4" data-test="activities-cards">
                @foreach ($this->activities as $activity)
                    @php $surface = $activity->surface_derived; @endphp
                    <article wire:key="act-{{ $activity->id }}" data-test="activity-card" class="rr-card {{ $activity->tires_confirmed ? '' : 'rr-card--alert' }}">
                        {{-- En-tête --}}
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex min-w-0 flex-col">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-black text-michelin-blue-dark">{{ $activity->start_date->translatedFormat('j M Y') }}</span>
                                    @unless ($activity->tires_confirmed)
                                        <span class="rr-chip rr-chip--warn" data-test="ride-unverified">{{ __('To verify') }}</span>
                                    @endunless
                                </div>
                                <span class="rr-card__eyebrow mt-1">{{ __(Str::headline($activity->sport_type)) }}</span>
                            </div>
                            @if ($surface)
                                <span class="rr-chip shrink-0">{{ __($surface->name) }}</span>
                            @endif
                        </div>

                        {{-- Métriques --}}
                        <div class="mt-3 flex gap-8">
                            <div>
                                <p class="rr-card__eyebrow">{{ __('Distance') }}</p>
                                <p class="text-lg font-black tracking-tight text-michelin-blue-dark tabular-nums">{{ Number::format($activity->distance_m / 1000, precision: 1) }} km</p>
                            </div>
                            <div>
                                <p class="rr-card__eyebrow">{{ __('Elevation') }}</p>
                                <p class="text-lg font-black tracking-tight text-michelin-blue-dark tabular-nums">{{ Number::format($activity->total_elevation_gain_m) }} m</p>
                            </div>
                        </div>

                        {{-- Pneus montés / édition --}}
                        <hr class="rr-divider">
                        @if ($editingId === $activity->id)
                            <div class="flex flex-col gap-3" data-test="ride-edit-{{ $activity->id }}">
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="flex flex-col gap-1">
                                        <span class="rr-label">{{ __('Front tire') }}</span>
                                        <select wire:model="editFront" class="rr-field">
                                            <option value="">{{ __('None') }}</option>
                                            @foreach ($this->frontTires as $tire)
                                                <option value="{{ $tire->id }}">{{ $tire->product->web_range_name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="flex flex-col gap-1">
                                        <span class="rr-label">{{ __('Rear tire') }}</span>
                                        <select wire:model="editRear" class="rr-field">
                                            <option value="">{{ __('None') }}</option>
                                            @foreach ($this->rearTires as $tire)
                                                <option value="{{ $tire->id }}">{{ $tire->product->web_range_name }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" wire:click="saveRide" data-test="ride-save" class="rr-btn rr-btn--sm">
                                        {{ __('Confirm') }}
                                    </button>
                                    <button type="button" wire:click="cancelEdit" class="rr-btn rr-btn--secondary rr-btn--sm">
                                        {{ __('Cancel') }}
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 flex-col gap-0.5 text-xs">
                                    <span class="rr-card__eyebrow">{{ __('Mounted tires') }}</span>
                                    <span class="truncate text-michelin-gray">
                                        <span class="font-semibold">{{ __('Front') }}</span> {{ $activity->frontTire?->product?->web_range_name ?? '—' }}
                                        · <span class="font-semibold">{{ __('Rear') }}</span> {{ $activity->rearTire?->product?->web_range_name ?? '—' }}
                                    </span>
                                </div>
                                <button type="button" wire:click="startEdit({{ $activity->id }})" data-test="ride-verify-{{ $activity->id }}" class="rr-btn rr-btn--secondary rr-btn--sm shrink-0">
                                    {{ $activity->tires_confirmed ? __('Edit') : __('Verify') }}
                                </button>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>

            <div>{{ $this->activities->links() }}</div>
        @endif
    </div>
</div>
