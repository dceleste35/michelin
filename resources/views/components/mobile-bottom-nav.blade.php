{{-- Barre de navigation mobile (coquille mobile-first). Masquée sur desktop, où la sidebar Flux prend le relais. --}}
@php
    $tabs = [
        ['route' => 'dashboard', 'icon' => 'home', 'label' => __('Home')],
        ['route' => 'activities', 'icon' => 'map', 'label' => __('Activities')],
        ['route' => 'profile', 'icon' => 'identification', 'label' => __('Profile')],
    ];
@endphp

<nav
    class="fixed inset-x-0 bottom-0 z-30 flex border-t border-zinc-200 bg-white/95 backdrop-blur lg:hidden dark:border-zinc-700 dark:bg-zinc-900/95"
    style="padding-bottom: env(safe-area-inset-bottom);"
    aria-label="{{ __('Primary') }}"
    data-test="mobile-bottom-nav"
>
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a
            href="{{ route($tab['route']) }}"
            wire:navigate
            @class([
                'flex flex-1 flex-col items-center justify-center gap-1 py-2.5 text-xs font-medium transition',
                'text-accent' => $active,
                'text-zinc-500 dark:text-zinc-400' => ! $active,
            ])
            @if ($active) aria-current="page" @endif
            data-test="mobile-nav-{{ $tab['route'] }}"
        >
            <flux:icon :icon="$tab['icon']" class="size-6" />
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
