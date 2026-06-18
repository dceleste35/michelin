{{-- Barre de navigation mobile (coquille mobile-first), charte Michelin. Masquée sur desktop, où la sidebar Flux prend le relais. --}}
@php
    $tabs = [
        ['route' => 'dashboard', 'label' => __('Home')],
        ['route' => 'activities', 'label' => __('Activities')],
        ['route' => 'tires', 'label' => __('Tires')],
        ['route' => 'profile', 'label' => __('Profile')],
    ];

    $icons = [
        'dashboard' => '<path d="M3 9.5 12 3l9 6.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'activities' => '<path d="M9 20 3 17V4l6 3 6-3 6 3v13l-6-3-6 3z"/><path d="M9 7v13M15 4v13"/>',
        'tires' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3.2"/><path d="M12 3v3M12 18v3M3 12h3M18 12h3"/>',
        'profile' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
    ];
@endphp

<nav
    class="rr-bottomnav fixed inset-x-0 bottom-0 z-30 lg:hidden"
    aria-label="{{ __('Primary') }}"
    data-test="mobile-bottom-nav"
>
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a
            href="{{ route($tab['route']) }}"
            wire:navigate
            @class(['rr-bottomnav__item', 'is-active' => $active])
            @if ($active) aria-current="page" @endif
            data-test="mobile-nav-{{ $tab['route'] }}"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$tab['route']] !!}</svg>
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
