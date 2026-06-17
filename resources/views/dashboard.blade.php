<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <!-- Title and description -->
        <div class="flex flex-col gap-1.5">
            <h2 class="text-xl font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
                <span class="inline-block w-2 h-5 bg-accent rounded-full"></span>
                {{ __('Mes Équipements') }}
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Suivi en temps réel de l\'usure et des performances de vos pneus vélo Michelin.') }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <!-- Tire Wear Card Component -->
            <div class="lg:col-span-1">
                <livewire:tire-wear-card />
            </div>

            <!-- Tire Recommendation & Comparison Component -->
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <livewire:tire-recommendation />
            </div>
        </div>

    </div>
</x-layouts::app>

