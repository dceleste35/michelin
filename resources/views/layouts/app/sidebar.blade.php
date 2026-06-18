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
