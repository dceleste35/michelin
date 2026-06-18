<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('activities')" :current="request()->routeIs('activities')" wire:navigate>
                        {{ __('Activities') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="lifebuoy" :href="route('tires')" :current="request()->routeIs('tires')" wire:navigate>
                        {{ __('My tires') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="bell" :href="route('alerts')" :current="request()->routeIs('alerts')" wire:navigate>
                        {{ __('Alerts') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="identification" :href="route('profile')" :current="request()->routeIs('profile')" wire:navigate>
                        {{ __('Rider profile') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                    {{ __('Repository') }}
                </flux:sidebar.item>

                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Top bar mobile — coquille mobile-first charte Michelin (le desktop garde la sidebar Flux) -->
        <!-- Le menu utilisateur (réglages / déconnexion) est déporté dans l'écran Profil. -->
        <flux:header class="!bg-michelin-blue !border-0 lg:hidden">
            <a href="{{ route('dashboard') }}" wire:navigate class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 shadow-sm" aria-label="{{ config('app.name') }}">
                <img src="{{ asset('images/michelin-logo.png') }}" alt="{{ config('app.name') }} — Michelin" class="h-5 w-auto" />
            </a>

            <flux:spacer />

            @php $reorderCount = auth()->user()->reorderCount(); @endphp
            <a href="{{ route('alerts') }}" wire:navigate class="relative grid size-10 place-items-center rounded-lg text-white transition hover:bg-white/10" aria-label="{{ __('Alerts') }}" data-test="topbar-alerts">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                @if ($reorderCount > 0)
                    <span class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-michelin-yellow px-1 text-[9px] font-black text-michelin-blue-dark" data-test="alerts-badge">{{ $reorderCount }}</span>
                @endif
            </a>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        <x-mobile-bottom-nav />

        @fluxScripts
    </body>
</html>
