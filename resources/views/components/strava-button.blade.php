@props([
    'href' => '#',
])

<a href="{{ $href }}" {{ $attributes->merge(['class' => 'inline-flex h-12 items-center justify-center gap-2.5 rounded-lg bg-[#FC4C02] px-6 text-sm font-semibold text-white shadow-sm hover:bg-[#E34402] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#FC4C02] transition-colors duration-200']) }}>
    <svg class="h-5 w-5 fill-current" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.731 0 2 9.125h2.788L6.73 5.497l1.93 3.628h2.766zm4.694 9.125-1.372 2.756L8.66 9.125H6.547L10.053 16l3.484-6.875z"/>
    </svg>
    <span>{{ $slot->isEmpty() ? __('Se connecter avec Strava') : $slot }}</span>
</a>
