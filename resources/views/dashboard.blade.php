<x-layouts::app :title="__('Dashboard')">
    <div class="min-h-full bg-white">
        {{-- Bandeau de section charte Michelin --}}
        <div class="rr-section">
            <p class="rr-section__label">{{ __('Hello :name', ['name' => auth()->user()->name]) }} 👋</p>
            <h2 class="rr-section__title">{{ __('Mes Équipements') }}</h2>
            <p class="rr-section__sub">{{ __('Suivi en temps réel de l\'usure et des performances de vos pneus vélo Michelin.') }}</p>
        </div>

        {{-- Composants de Guillaume conservés tels quels (usure + recommandation) --}}
        <div class="flex flex-col gap-6 p-6 lg:grid lg:grid-cols-3 lg:items-start lg:p-8">
            <div class="lg:col-span-1">
                <livewire:tire-wear-card />
            </div>

            <div class="lg:col-span-2">
                <livewire:tire-recommendation />
            </div>
        </div>
    </div>
</x-layouts::app>
