<?php

use App\Enums\Surface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Activities')] class extends Component
{
    use WithPagination;

    /**
     * The authenticated rider's Strava activities, most recent first.
     *
     * @return LengthAwarePaginator<int, \App\Models\StravaActivity>
     */
    #[Computed]
    public function activities(): LengthAwarePaginator
    {
        return auth()->user()
            ->stravaActivities()
            ->orderByDesc('start_date')
            ->paginate(20);
    }
}; ?>

<section class="w-full">
    <flux:heading size="xl" level="1">{{ __('Your activities') }}</flux:heading>
    <flux:subheading size="lg" class="mb-6">
        {{ $this->activities->total() }} {{ Str::plural('ride', $this->activities->total()) }} {{ __('imported from Strava') }}
    </flux:subheading>
    <flux:separator variant="subtle" class="mb-6" />

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
                        <flux:table.cell>{{ $activity->start_date->format('M j, Y') }}</flux:table.cell>
                        <flux:table.cell>{{ Str::headline($activity->sport_type) }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($activity->surface_derived)
                                <flux:badge size="sm" :color="match ($activity->surface_derived) {
                                    Surface::Asphalt => 'zinc',
                                    Surface::Hardpacked => 'amber',
                                    Surface::Mixed => 'lime',
                                    Surface::Soft => 'orange',
                                    Surface::Mud => 'red',
                                }">{{ $activity->surface_derived->name }}</flux:badge>
                            @else
                                <flux:text class="text-zinc-400">&mdash;</flux:text>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell align="end" variant="strong">
                            {{ number_format($activity->distance_m / 1000, 1) }} km
                        </flux:table.cell>
                        <flux:table.cell align="end">
                            {{ number_format($activity->total_elevation_gain_m) }} m
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @endif
</section>
