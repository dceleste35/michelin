<?php

use App\Enums\RidingStyle;
use App\Enums\Segment;
use App\Services\ProfileInferenceService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Rider profile')] class extends Component
{
    public string $segment;

    public string $style;

    public int $weightKg;

    /** @var array<string, int> */
    public array $terrainPct;

    public bool $adjusting = false;

    /**
     * Infer + persist the profile (respects a user override), then load the
     * effective persisted values to display as a correctable smart default.
     */
    public function mount(ProfileInferenceService $service): void
    {
        $user = auth()->user();
        $service->inferAndPersist($user);
        $profile = $service->buildProfile($user->fresh());

        $this->segment = $profile->segment->value;
        $this->style = $profile->ridingStyle->value;
        $this->weightKg = $profile->weightKg;
        $this->terrainPct = $profile->terrainPct;
    }

    /**
     * Human labels for each segment (translatable).
     *
     * @return array<string, string>
     */
    #[Computed]
    public function segmentOptions(): array
    {
        return [
            Segment::Gravel->value => __('Gravel'),
            Segment::Road->value => __('Road'),
            Segment::Mtb->value => __('Mountain bike'),
            Segment::EbikeUrban->value => __('Urban e-bike'),
        ];
    }

    /**
     * Reveal the segment selector.
     */
    public function adjust(): void
    {
        $this->adjusting = true;
    }

    /**
     * Persist a manual segment correction and flag the override.
     */
    public function updatedSegment(string $value): void
    {
        $this->validate(['segment' => ['required', Rule::enum(Segment::class)]]);

        $user = auth()->user();
        $user->segment = Segment::from($value);
        $user->segment_overridden = true;
        $user->save();
    }

    /**
     * Persist the system weight from the slider.
     */
    public function updatedWeightKg(int $value): void
    {
        $this->validate(['weightKg' => ['required', 'integer', 'min:40', 'max:150']]);

        auth()->user()->update(['weight_kg' => $value]);
    }

    /**
     * Accept the smart default (recorded once) and continue to the activities.
     */
    public function confirm()
    {
        $user = auth()->user();
        $user->profile_confirmed_at = now();
        $user->save();

        return redirect()->route('activities');
    }
}; ?>

<section class="mx-auto flex w-full max-w-2xl flex-col gap-6">
    <div>
        <flux:heading size="xl" level="1">{{ __('We figured out your profile') }}</flux:heading>
        <flux:subheading>{{ __('No questionnaire — inferred from your rides. One tap to adjust.') }}</flux:subheading>
    </div>

    <flux:card class="flex flex-col gap-5">
        <p class="text-lg leading-relaxed">
            {{ __("Here's what we understood:") }}
            <span class="font-semibold">{{ __('a :segment rider', ['segment' => $this->segmentOptions[$segment]]) }}</span>,
            {{ $style === \App\Enums\RidingStyle::Aggressif->value ? __('punchy and aggressive') : __('steady, long distance') }},
            {{ __(':road% road / :trail% trail', ['road' => $terrainPct['asphalt'], 'trail' => 100 - $terrainPct['asphalt']]) }}.
        </p>

        <div class="flex flex-col gap-2">
            <div class="flex justify-between">
                <flux:badge color="blue" size="sm">{{ $terrainPct['asphalt'] }}% {{ __('road') }}</flux:badge>
                <flux:badge color="green" size="sm">{{ 100 - $terrainPct['asphalt'] }}% {{ __('trail') }}</flux:badge>
            </div>
            <div class="flex h-3 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="bg-michelin-blue" style="width: {{ $terrainPct['asphalt'] }}%"></div>
                <div class="bg-michelin-green" style="width: {{ 100 - $terrainPct['asphalt'] }}%"></div>
            </div>
        </div>

        @if ($adjusting)
            <flux:select wire:model.live="segment" :label="__('Segment')" data-test="segment-select">
                @foreach ($this->segmentOptions as $value => $label)
                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
        @endif

        <div class="flex flex-col gap-1">
            <label class="flex items-center justify-between text-sm font-medium">
                <span>{{ __('System weight') }}</span>
                <span class="font-semibold" data-test="weight-value">{{ $weightKg }} kg</span>
            </label>
            <input
                type="range"
                min="40"
                max="150"
                wire:model.live.debounce.300ms="weightKg"
                class="w-full accent-michelin-blue"
                data-test="weight-slider"
            />
            <flux:text size="sm" class="text-zinc-500">
                {{ __('Rider + bike. Affects tire wear estimates.') }}
            </flux:text>
        </div>
    </flux:card>

    <div class="flex items-center gap-3">
        <flux:button wire:click="confirm" variant="primary" data-test="confirm-profile">
            {{ __("Yes, that's right") }}
        </flux:button>
        @unless ($adjusting)
            <flux:button wire:click="adjust" variant="ghost" data-test="adjust-profile">
                {{ __('Adjust') }}
            </flux:button>
        @endunless
    </div>
</section>
