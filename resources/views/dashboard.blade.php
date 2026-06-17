<x-layouts::app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <!-- Desktop View (Visible on medium screens and larger) -->
        <div class="hidden md:flex flex-col gap-6">
            <!-- Title and description -->
            <div class="flex flex-col gap-1.5">
                <h2 class="text-xl font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
                    <span class="inline-block w-2 h-5 bg-michelin-blue rounded-full"></span>
                    {{ __('Tableau de bord RideReady') }}
                </h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Suivez l\'usure de vos pneus Michelin, analysez vos données Strava et préparez vos prochains montages.') }}
                </p>
            </div>

            <!-- Dynamic Tire Wear Warnings/Alerts -->
            <livewire:tire-wear-alert />

            <!-- Dual column layout -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-start">
                <!-- Left Side: Rider Profile and Wear Status -->
                <div class="xl:col-span-1 flex flex-col gap-6">
                    <!-- Rider profile & Strava stats -->
                    <livewire:rider-profile />

                    <!-- Real-time Tire Wear Tracker & Simulator -->
                    <livewire:tire-wear-card />
                </div>

                <!-- Right Side: Recommendation Engine & Shopping Cart -->
                <div class="xl:col-span-2 flex flex-col gap-6">
                    <!-- Tire Recommendation & Comparison -->
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                        <livewire:tire-recommendation />
                    </div>

                    <!-- Prefilled Decathlon Checkout Cart -->
                    <livewire:shopping-cart />
                </div>
            </div>

            <!-- Social Media / Instagram Post Generator -->
            <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                <div class="flex flex-col gap-1.5 mb-5 border-b border-zinc-150 dark:border-zinc-800 pb-3">
                    <h3 class="text-md font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
                        <span class="inline-block w-2 h-5 bg-michelin-blue rounded-full"></span>
                        {{ __('Bilan de l\'Année & Partage Social') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Générez des posts et des images récapitulatives personnalisées de vos kilomètres pour Instagram, Strava ou vos réseaux préférés.') }}
                    </p>
                </div>
                
                <livewire:instagram-generator />
            </div>
        </div>

        <!-- Mobile Single Page View with Bottom Navigation (Visible on mobile/small screens only) -->
        <div class="flex md:hidden flex-col gap-4 pb-28" x-data="{ tab: 'profil' }" x-init="
            // Listen to redirection events to focus appropriate tabs
            window.addEventListener('hashchange', () => {
                if (window.location.hash === '#shopping-cart-section') {
                    tab = 'panier';
                } else if (window.location.hash === '#tire-wear-card') {
                    tab = 'sante';
                }
            });
        ">
            <!-- Alert Banner always visible at top of mobile screen if critical tires exist -->
            <livewire:tire-wear-alert />

            <!-- Tab content -->
            <div x-show="tab === 'profil'" class="space-y-4">
                <livewire:rider-profile />
            </div>

            <div x-show="tab === 'sante'" class="space-y-4">
                <livewire:tire-wear-card />
            </div>

            <div x-show="tab === 'reco'" class="space-y-4">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <livewire:tire-recommendation />
                </div>
            </div>

            <div x-show="tab === 'panier'" class="space-y-4">
                <livewire:shopping-cart />
            </div>

            <div x-show="tab === 'partage'" class="space-y-4">
                <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex flex-col gap-1.5 mb-5 border-b border-zinc-150 dark:border-zinc-800 pb-3">
                        <h3 class="text-md font-black text-zinc-800 dark:text-zinc-100 tracking-tight flex items-center gap-2">
                            <span class="inline-block w-2 h-5 bg-michelin-blue rounded-full"></span>
                            {{ __('Bilan de l\'Année & Partage Social') }}
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Générez des posts et des images récapitulatives personnalisées de vos kilomètres pour Instagram.') }}
                        </p>
                    </div>
                    <livewire:instagram-generator />
                </div>
            </div>

            <!-- Sticky Bottom Navigation Bar -->
            <div class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-950 border-t border-zinc-200 dark:border-zinc-800 py-3 px-2 flex justify-around items-center shadow-[0_-4px_10px_rgba(0,0,0,0.05)] dark:shadow-[0_-4px_10px_rgba(0,0,0,0.3)]">
                <!-- Nav Item: Profil -->
                <button type="button" @click="tab = 'profil'" class="flex flex-col items-center gap-1 cursor-pointer transition-colors" :class="tab === 'profil' ? 'text-michelin-blue dark:text-michelin-blue-light' : 'text-zinc-400 dark:text-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-400'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">{{ __('Profil') }}</span>
                </button>

                <!-- Nav Item: Santé -->
                <button type="button" @click="tab = 'sante'" class="flex flex-col items-center gap-1 cursor-pointer transition-colors" :class="tab === 'sante' ? 'text-michelin-blue dark:text-michelin-blue-light' : 'text-zinc-400 dark:text-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-400'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3" />
                    </svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">{{ __('Santé') }}</span>
                </button>

                <!-- Nav Item: Reco -->
                <button type="button" @click="tab = 'reco'" class="flex flex-col items-center gap-1 cursor-pointer transition-colors" :class="tab === 'reco' ? 'text-michelin-blue dark:text-michelin-blue-light' : 'text-zinc-400 dark:text-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-400'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">{{ __('Reco') }}</span>
                </button>

                <!-- Nav Item: Panier -->
                <button type="button" @click="tab = 'panier'" class="flex flex-col items-center gap-1 cursor-pointer transition-colors relative" :class="tab === 'panier' ? 'text-michelin-blue dark:text-michelin-blue-light' : 'text-zinc-400 dark:text-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-400'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">{{ __('Panier') }}</span>
                </button>

                <!-- Nav Item: Partage -->
                <button type="button" @click="tab = 'partage'" class="flex flex-col items-center gap-1 cursor-pointer transition-colors" :class="tab === 'partage' ? 'text-michelin-blue dark:text-michelin-blue-light' : 'text-zinc-400 dark:text-zinc-600 hover:text-zinc-600 dark:hover:text-zinc-400'">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.684 10.742l4.885 2.786M8.684 13.258l4.885-2.786M5.25 12a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zm10.5 5.25a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0zm0-10.5a2.25 2.25 0 114.5 0 2.25 2.25 0 01-4.5 0z" />
                    </svg>
                    <span class="text-[9px] font-black uppercase tracking-wider">{{ __('Partage') }}</span>
                </button>
            </div>
        </div>

    </div>
</x-layouts::app>

