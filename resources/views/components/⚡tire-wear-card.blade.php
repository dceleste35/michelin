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
            // Les pneus archivés sont exclus (rangés hors collection courante).
            $this->userTire = $user->tires()
                ->active()
                ->notArchived()
                ->where('position', $this->activePosition)
                ->first()
                ?? $user->tires()
                    ->notArchived()
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
     * Tires owned at the currently viewed position — to swap the mounted one.
     *
     * @return \Illuminate\Support\Collection<int, UserTire>
     */
    public function positionTires(): \Illuminate\Support\Collection
    {
        $user = Auth::user();

        if (! $user) {
            return collect();
        }

        return $user->tires()
            ->with('product')
            ->notArchived()
            ->where('position', $this->activePosition)
            ->orderByDesc('is_active')
            ->get();
    }

    /**
     * Swap the mounted tire at the viewed position to an existing one from the collection.
     * The previously active tire is retired (kept for ride history), not deleted.
     */
    public function mountTire(int $tireId): void
    {
        $user = Auth::user();
        $tire = $user?->tires()->whereKey($tireId)->first();

        if (! $tire) {
            return;
        }

        $user->tires()
            ->where('position', $tire->position->value)
            ->whereKeyNot($tire->id)
            ->update(['is_active' => false]);

        $tire->update(['is_active' => true]);

        $this->activePosition = $tire->position->value;
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

<div class="w-full">
@php
    // Mappe l'état d'usure (excellent/moderate/critical) sur les couleurs fonctionnelles de la charte.
    $tone = $this->userTire ? $this->getWearColor() : 'excellent';
    $toneText = ['excellent' => 'rr-ok', 'moderate' => 'rr-warn', 'critical' => 'rr-danger'][$tone];
    $toneChip = ['excellent' => 'rr-chip--ok', 'moderate' => 'rr-chip--warn', 'critical' => 'rr-chip--danger'][$tone];
    $toneStroke = [
        'excellent' => 'var(--color-michelin-success)',
        'moderate' => 'var(--color-michelin-warning)',
        'critical' => 'var(--color-michelin-danger)',
    ][$tone];
@endphp

@if ($this->userTire)
    <div class="rr-card flex flex-col gap-5">
        {{-- En-tête : pneu + état --}}
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 flex-col">
                <span class="rr-card__eyebrow">Michelin</span>
                <h3 class="truncate text-lg font-black text-michelin-blue-dark">{{ $this->userTire->product->web_range_name }}</h3>
                <span class="text-xs font-semibold text-michelin-gray">{{ $this->userTire->product->width_etrto }}-{{ $this->userTire->product->diameter_etrto }}</span>
            </div>
            <span class="{{ $toneChip }} shrink-0">{{ $this->getWearStatusLabel() }}</span>
        </div>

        {{-- Sélecteur de position --}}
        <div class="flex gap-1 rounded-xl bg-zinc-100 p-1">
            <button type="button" wire:click="setPosition('FRONT')" @class([
                'flex-1 rounded-lg py-1.5 text-xs font-black uppercase tracking-wider transition',
                'bg-white text-michelin-blue shadow-sm' => $activePosition === 'FRONT',
                'text-michelin-gray' => $activePosition !== 'FRONT',
            ])>{{ __('Pneu Avant') }}</button>
            <button type="button" wire:click="setPosition('REAR')" @class([
                'flex-1 rounded-lg py-1.5 text-xs font-black uppercase tracking-wider transition',
                'bg-white text-michelin-blue shadow-sm' => $activePosition === 'REAR',
                'text-michelin-gray' => $activePosition !== 'REAR',
            ])>{{ __('Pneu Arrière') }}</button>
        </div>

        {{-- Permuter le pneu monté à cette position (depuis la collection) --}}
        @php $positionTires = $this->positionTires(); @endphp
        @if ($positionTires->count() > 1)
            <label class="flex flex-col gap-1.5">
                <span class="rr-label !mb-0">{{ __('Mounted tire') }}</span>
                <select class="rr-field" wire:change="mountTire($event.target.value)" data-test="wear-tire-select">
                    @foreach ($positionTires as $t)
                        <option value="{{ $t->id }}" @selected($t->id === $this->userTire->id)>{{ $t->product->web_range_name }}</option>
                    @endforeach
                </select>
            </label>
        @endif

        {{-- Kilométrage + jauge radiale --}}
        <div class="flex items-center justify-between gap-4">
            <div class="flex flex-col">
                <span class="rr-card__eyebrow">{{ __('Kilométrage actuel') }}</span>
                <span class="text-2xl font-black tracking-tight text-michelin-blue-dark tabular-nums">{{ number_format($this->getCurrentMileage(), 0, ',', ' ') }} <span class="text-xs font-semibold text-michelin-gray">km</span></span>
                <span class="mt-1 text-[11px] font-bold uppercase tracking-wider text-michelin-gray">{{ __('Limite') }} : {{ number_format($this->userTire->product->expected_life_km ?? 5000, 0, ',', ' ') }} km</span>
            </div>

            <div class="relative flex shrink-0 items-center justify-center">
                <svg class="h-20 w-20 -rotate-90" viewBox="0 0 36 36">
                    <path stroke="rgba(39,80,155,0.12)" stroke-width="3.5" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                    <path stroke="{{ $toneStroke }}" stroke-width="3.5" stroke-linecap="round" fill="none" stroke-dasharray="{{ $this->userTire->wear_percent }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="absolute flex flex-col items-center justify-center">
                    <span class="text-base font-black leading-none {{ $toneText }}">{{ number_format($this->userTire->wear_percent, 0) }}%</span>
                    <span class="mt-0.5 text-[7px] font-extrabold uppercase tracking-widest text-michelin-gray">{{ __('Usure') }}</span>
                </div>
            </div>
        </div>

        {{-- Barre de progression --}}
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-zinc-200">
            <div class="h-full rounded-full" style="width: {{ min(100, $this->userTire->wear_percent) }}%; background: {{ $toneStroke }};"></div>
        </div>

        {{-- Infos montage --}}
        <div class="grid grid-cols-2 gap-3">
            <div class="flex flex-col gap-0.5">
                <span class="rr-card__eyebrow">{{ __('Date de montage') }}</span>
                <span class="text-xs font-bold text-michelin-blue-dark">{{ $this->userTire->mounted_at ? $this->userTire->mounted_at->translatedFormat('d F Y') : __('Non renseignée') }}</span>
            </div>
            <div class="flex flex-col gap-0.5">
                <span class="rr-card__eyebrow">{{ __('Odomètre initial') }}</span>
                <span class="text-xs font-bold text-michelin-blue-dark">{{ number_format($this->userTire->mounted_odometer_km ?? 0, 0, ',', ' ') }} km</span>
            </div>
        </div>

        {{-- Alertes d'usure (charte) --}}
        @if (($this->userTire->wear_percent ?? 0) >= 80)
            <div class="rr-alert" style="background: var(--color-michelin-danger);">
                <div class="rr-alert__left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <div>
                        <p class="rr-alert__title">{{ __('Pneu en fin de vie !') }}</p>
                        <p class="rr-alert__sub">{{ __('Ce pneu a atteint :wear% d\'usure. Pour votre sécurité, veuillez le remplacer rapidement.', ['wear' => number_format($this->userTire->wear_percent, 0)]) }}</p>
                    </div>
                </div>
            </div>
        @elseif (($this->userTire->wear_percent ?? 0) >= 50)
            <div class="rr-alert">
                <div class="rr-alert__left">
                    <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <div>
                        <p class="rr-alert__title">{{ __('Usure modérée') }}</p>
                        <p class="rr-alert__sub">{{ __('Pensez à remplacer ce pneu bientôt ou équilibrez vos sorties.') }}</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
@else
    {{-- État vide : aucun pneu enregistré (compte neuf) — on ne fabrique plus de données --}}
    <div class="rr-card flex flex-col items-center gap-3 py-12 text-center" data-test="tire-card-empty">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="size-11 text-michelin-blue/30"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" /></svg>
        <div class="flex flex-col gap-1">
            <h2 class="text-base font-black text-michelin-blue-dark">{{ __('No tire registered yet') }}</h2>
            <p class="text-sm text-michelin-gray">{{ __('Add your Michelin tires to start tracking their wear.') }}</p>
        </div>
        <a href="{{ route('tires') }}" wire:navigate class="rr-btn" data-test="tire-card-add">{{ __('Add my tires') }}</a>
    </div>
@endif
</div>
