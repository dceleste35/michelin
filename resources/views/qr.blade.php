<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">

        <title>{{ config('app.name', 'RideReady') }} — {{ __('by Michelin') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/michelin-icon-32.png') }}">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="rr-landing">
        <div class="relative flex min-h-screen flex-col items-center justify-center gap-7 overflow-hidden bg-michelin-midnight px-6 py-12 text-center">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-michelin-yellow"></div>
            <div class="pointer-events-none absolute left-1/2 top-[-20%] aspect-square w-[120%] -translate-x-1/2 rounded-full" style="background: radial-gradient(circle, rgba(39,80,155,0.45) 0%, rgba(39,80,155,0) 60%); filter: blur(8px);"></div>

            <span class="relative inline-flex items-center rounded-2xl bg-white px-5 py-3.5 shadow-2xl">
                <img src="{{ asset('images/michelin-logo.png') }}" alt="Michelin" class="h-9 w-auto" />
            </span>

            {{-- DESKTOP : scanner pour passer sur mobile --}}
            <div class="relative hidden flex-col items-center gap-7 lg:flex" data-test="qr-gate">
                <div class="max-w-md">
                    <h1 class="text-3xl font-extrabold uppercase leading-tight tracking-tight text-white">{{ __('An experience designed for mobile') }}</h1>
                    <p class="mt-4 text-base leading-relaxed text-white/70">{{ __('RideReady was built for your phone. Scan this QR code to get the best experience.') }}</p>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-2xl">
                    <div class="h-60 w-60">{!! $qrSvg !!}</div>
                </div>

                <p class="text-sm font-semibold tracking-wide text-white/50">{{ $appUrl }}</p>

                <a href="{{ route('home') }}" class="rr-btn rr-btn--secondary rr-btn--sm tracking-normal" data-test="continue-desktop">{{ __('Continue on this device') }}</a>
            </div>

            {{-- MOBILE : déjà sur téléphone, accès direct --}}
            <div class="relative flex max-w-xs flex-col items-center gap-6 lg:hidden" data-test="qr-mobile">
                <p class="text-base leading-relaxed text-white/70">{{ __("You're on your phone — let's go.") }}</p>
                <a href="{{ route('home') }}" class="rr-btn tracking-normal">{{ __('Open the app') }}</a>
            </div>
        </div>
    </body>
</html>
