<?php

use App\Enums\Surface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Activities')] class extends Component
{
    use WithPagination;

    /**
     * Les activités Strava du cycliste authentifié, les plus récentes en premier.
     *
     * @return LengthAwarePaginator<int, \App\Models\StravaActivity>
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return auth()->user()
            ->stravaActivities()
            ->select(['id', 'sport_type', 'surface_derived', 'distance_m', 'total_elevation_gain_m', 'start_date', 'tires_confirmed'])
            ->orderByDesc('start_date')
            ->paginate(20);
    }

    /**
     * Nombre de sorties dont les pneus montés restent à confirmer.
     */
    #[Computed]
    public function unconfirmedCount(): int
    {
        return auth()->user()->stravaActivities()->where('tires_confirmed', false)->count();
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

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Your activities') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        {{ trans_choice('rides.imported', $this->activities->total(), ['count' => $this->activities->total()]) }}
    </flux:subheading>
    <flux:separator variant="subtle" class="mb-6" />

    @if ($this->unconfirmedCount > 0)
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-michelin-warning/30 bg-michelin-warning/10 px-4 py-3" data-test="verify-tires-banner">
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
        <flux:callout icon="information-circle" data-test="activities-empty">
            <flux:callout.heading>{{ __('No activities yet') }}</flux:callout.heading>
            <flux:callout.text>{{ __('Connect with Strava to import your rides.') }}</flux:callout.text>

            <x-slot name="actions">
                <a
                    href="{{ route('strava.connect') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#FC4C02] px-4 py-2 text-sm font-medium text-white transition hover:opacity-90"
                    data-test="strava-connect-cta"
                >
                    {{ __('Connect with Strava') }}
                </a>
            </x-slot>
        </flux:callout>
    @else
        <flux:table :paginate="$this->activities" data-test="activities-table">
            <flux:table.columns>
                <flux:table.column>{{ __('Date') }}</flux:table.column>
                <flux:table.column>{{ __('Type') }}</flux:table.column>
                <flux:table.column>{{ __('Surface') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Distance') }}</flux:table.column>
                <flux:table.column align="end">{{ __('Elevation') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->activities as $activity)
                    <flux:table.row :key="$activity->id" data-test="activity-row">
                        <flux:table.cell>
                            {{ $activity->start_date->translatedFormat('j M Y') }}
                            @unless ($activity->tires_confirmed)
                                <span class="ml-1.5 inline-block rounded-full bg-michelin-warning/15 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider text-michelin-warning" data-test="ride-unverified">{{ __('To verify') }}</span>
                            @endunless
                        </flux:table.cell>
                        <flux:table.cell>{{ __(Str::headline($activity->sport_type)) }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($activity->surface_derived)
                                <flux:badge size="sm" :color="match ($activity->surface_derived) {
                                    Surface::Asphalt => 'zinc',
                                    Surface::Hardpacked => 'amber',
                                    Surface::Mixed => 'lime',
                                    Surface::Soft => 'orange',
                                    Surface::Mud => 'red',
                                }">{{ __($activity->surface_derived->name) }}</flux:badge>
                            @else
                                <flux:text class="text-zinc-400">&mdash;</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" variant="strong">
                            {{ Number::format($activity->distance_m / 1000, precision: 1) }} km
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            {{ Number::format($activity->total_elevation_gain_m) }} m
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
