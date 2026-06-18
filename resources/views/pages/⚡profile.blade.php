<?php

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
     * Déduit puis persiste le profil (en respectant une correction de l'utilisateur), puis charge
     * les valeurs persistées effectives afin de les afficher comme valeur par défaut intelligente et corrigeable.
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
     * Libellés lisibles pour chaque segment (traduisibles).
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
     * Affiche le sélecteur de segment.
     */
    public function adjust(): void
    {
        $this->adjusting = true;
    }

    /**
     * Persiste une correction manuelle du segment et signale la surcharge.
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
     * Persiste le poids du système depuis le curseur.
     */
    public function updatedWeightKg(int $value): void
    {
        $this->validate(['weightKg' => ['required', 'integer', 'min:40', 'max:150']]);

        auth()->user()->update(['weight_kg' => $value]);
    }

    /**
     * Accepte la valeur par défaut intelligente (enregistrée une seule fois) et passe aux activités.
     */
    public function confirm()
    {
        $user = auth()->user();
        $user->profile_confirmed_at = now();
        $user->save();

        return redirect()->route('activities');
    }
}; ?>

<div class="rr-screen">
    <div class="rr-section">
        <div class="rr-section__label">{{ __('Rider profile') }}</div>
        <div class="rr-section__title">{{ __('We figured out your profile') }}</div>
        <div class="rr-section__sub">{{ __('No questionnaire — inferred from your rides. One tap to adjust.') }}</div>
    </div>

    <div class="rr-body">
        <div class="rr-card flex flex-col gap-5">
            {{-- Identité rider (l'ancre de la carte) --}}
            <div class="rr-badge-rider">
                {{ __('a :segment rider', ['segment' => $this->segmentOptions[$segment]]) }} ·
                {{ $style === \App\Enums\RidingStyle::Aggressif->value ? __('punchy and aggressive') : __('steady, long distance') }}
            </div>

            {{-- Terrain : une seule représentation (libellé + barre) --}}
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="rr-row__key">{{ __('Terrain') }}</span>
                    <span class="rr-row__val">{{ __(':road% road / :trail% trail', ['road' => $terrainPct['asphalt'], 'trail' => 100 - $terrainPct['asphalt']]) }}</span>
                </div>
                <div class="flex h-2.5 overflow-hidden rounded-full bg-zinc-200">
                    <div class="bg-michelin-blue" style="width: {{ $terrainPct['asphalt'] }}%"></div>
                    <div class="bg-michelin-green" style="width: {{ 100 - $terrainPct['asphalt'] }}%"></div>
                </div>
            </div>

            {{-- Poids système --}}
            <div class="flex flex-col gap-2">
                <div class="flex items-center justify-between">
                    <span class="rr-label !mb-0">{{ __('System weight') }}</span>
                    <span class="text-base font-black tabular-nums text-michelin-blue-dark" data-test="weight-value">{{ $weightKg }} kg</span>
                </div>
                <input
                    type="range"
                    min="40"
                    max="150"
                    wire:model.live.debounce.300ms="weightKg"
                    class="w-full accent-michelin-blue"
                    data-test="weight-slider"
                />
                <p class="text-xs leading-relaxed text-michelin-gray">{{ __('Rider + bike. Affects tire wear estimates.') }}</p>
            </div>

            {{-- Ajustement manuel du segment --}}
            @if ($adjusting)
                <div class="flex flex-col gap-1.5">
                    <label class="rr-label !mb-0">{{ __('Segment') }}</label>
                    <select wire:model.live="segment" class="rr-field" data-test="segment-select">
                        @foreach ($this->segmentOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Provenance courte (charte) --}}
            <span class="rr-badge-source self-start">{{ __('Calculated · SCORE · Strava') }}</span>
        </div>

        <button type="button" wire:click="confirm" class="rr-btn" data-test="confirm-profile">
            {{ __("Yes, that's right") }}
        </button>
        @unless ($adjusting)
            <button type="button" wire:click="adjust" class="rr-btn rr-btn--secondary" data-test="adjust-profile">
                {{ __('Adjust') }}
            </button>
        @endunless

        {{-- Compte utilisateur — déporté ici depuis la top-bar (réglages + déconnexion) --}}
        <div class="rr-card">
            <p class="rr-card__eyebrow">{{ __('Account') }}</p>
            <div class="mt-3 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-michelin-blue text-sm font-extrabold text-white">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate font-bold text-michelin-blue-dark">{{ auth()->user()->name }}</p>
                    <p class="truncate text-sm text-michelin-gray">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <div class="rr-divider"></div>

            <a href="{{ route('profile.edit') }}" wire:navigate class="rr-btn rr-btn--secondary">
                {{ __('Settings') }}
            </a>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="w-full py-2 text-center text-sm font-bold uppercase tracking-wide text-michelin-danger" data-test="logout-button">
                    {{ __('Log out') }}
                </button>
            </form>
        </div>
    </div>
</div>
