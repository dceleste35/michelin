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

        <!-- Connection banner -->
        <div class="relative overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-zinc-900 p-6 shadow-xs">
            <div class="flex flex-col items-center justify-center text-center gap-4 max-w-md mx-auto">
                <h3 class="text-base font-bold text-zinc-800 dark:text-zinc-200">{{ __('Connectez vos activités cyclistes') }}</h3>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 leading-relaxed">
                    {{ __('Associez votre compte Strava pour synchroniser vos sorties vélo et analyser vos performances avec vos pneus Michelin.') }}
                </p>
                <x-strava-button href="#" />
            </div>
        </div>
    </div>
</x-layouts::app>

