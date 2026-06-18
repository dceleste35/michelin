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

        <style>
            /* ============================================================
               RideReady — Landing « In Motion » (charte Michelin)
               Midnight #000c34 / Reflex Blue #27509b / Yellow #fce500.
               Police : Noto Sans (charte). Aucune police ni couleur hors charte.
               ============================================================ */
            .rr-landing {
                margin: 0;
                min-height: 100vh;
                background: var(--color-michelin-midnight, #000c34);
                font-family: 'Noto Sans', ui-sans-serif, system-ui, sans-serif;
                -webkit-font-smoothing: antialiased;
            }

            .rr-hero {
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                min-height: 100vh;
                min-height: 100svh;
                overflow: hidden;
                isolation: isolate;
            }

            /* Ligne de support jaune (signature charte, p.6) */
            .rr-hero::before {
                content: "";
                position: absolute;
                inset: 0 0 auto 0;
                height: 4px;
                background: var(--color-michelin-yellow, #fce500);
                z-index: 6;
            }

            /* Fond : pneu gravel Michelin réel, lent ken-burns */
            .rr-hero__bg {
                position: absolute;
                inset: 0;
                z-index: -3;
                background-size: cover;
                background-position: center 35%;
                transform: scale(1.05);
                animation: rr-kenburns 26s ease-in-out infinite alternate;
            }

            /* Voile bleu→midnight pour la lisibilité + teinte Michelin */
            .rr-hero__veil {
                position: absolute;
                inset: 0;
                z-index: -2;
                background:
                    linear-gradient(180deg,
                        rgba(0, 12, 52, 0.72) 0%,
                        rgba(0, 32, 91, 0.78) 42%,
                        rgba(0, 12, 52, 0.96) 100%);
            }

            /* Halo bleu radial qui respire, derrière le logo */
            .rr-hero__glow {
                position: absolute;
                top: -28%;
                left: 50%;
                width: 150%;
                aspect-ratio: 1 / 1;
                transform: translateX(-50%);
                z-index: -1;
                background: radial-gradient(circle,
                    rgba(39, 80, 155, 0.55) 0%,
                    rgba(39, 80, 155, 0.18) 38%,
                    rgba(39, 80, 155, 0) 62%);
                filter: blur(8px);
                animation: rr-breathe 9s ease-in-out infinite;
                pointer-events: none;
            }

            /* Grain fin pour la profondeur */
            .rr-hero__grain {
                position: absolute;
                inset: 0;
                z-index: 5;
                opacity: 0.07;
                mix-blend-mode: overlay;
                pointer-events: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='2' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
            }

            .rr-hero__top {
                position: relative;
                z-index: 7;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                gap: 22px;
                padding: max(28px, env(safe-area-inset-top)) 28px 0;
                max-width: 560px;
                margin-inline: auto;
                width: 100%;
                flex: 1;
                justify-content: center;
            }

            /* Logo couleur sur pastille blanche (charte : clear-space sur fond foncé/imagé) */
            .rr-hero__brand {
                display: inline-flex;
                align-items: center;
                background: #fff;
                border-radius: 14px;
                padding: 10px 16px;
                box-shadow: 0 14px 36px -12px rgba(0, 0, 0, 0.55);
            }
            .rr-hero__logo {
                height: 30px;
                width: auto;
            }

            .rr-hero__eyebrow {
                margin: 0;
                font-size: 11px;
                font-weight: 800;
                letter-spacing: 0.22em;
                text-transform: uppercase;
                color: rgba(255, 255, 255, 0.62);
            }
            .rr-hero__eyebrow b { color: var(--color-michelin-yellow, #fce500); font-weight: 800; }

            .rr-hero__title {
                margin: 0;
                font-size: clamp(30px, 8.5vw, 46px);
                font-weight: 800;
                line-height: 1.04;
                letter-spacing: -0.015em;
                text-transform: uppercase;
                color: #fff;
            }
            .rr-hero__title span { display: block; }
            .rr-hero__title .rr-y { color: var(--color-michelin-yellow, #fce500); }

            .rr-hero__rule {
                display: block;
                height: 4px;
                width: 0;
                border-radius: 2px;
                background: var(--color-michelin-yellow, #fce500);
                animation: rr-draw 0.9s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
                animation-delay: 1s;
            }

            .rr-hero__sub {
                margin: 0;
                max-width: 330px;
                font-size: 15px;
                line-height: 1.6;
                color: rgba(255, 255, 255, 0.74);
            }

            .rr-hero__bottom {
                position: relative;
                z-index: 7;
                display: flex;
                flex-direction: column;
                gap: 14px;
                padding: 22px 24px max(24px, env(safe-area-inset-bottom));
                max-width: 560px;
                margin-inline: auto;
                width: 100%;
            }

            /* CTA Strava soigné */
            .rr-strava {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                height: 58px;
                width: 100%;
                border-radius: 6px;
                background: var(--color-strava, #fc4c02);
                color: #fff;
                font-size: 16px;
                font-weight: 700;
                text-decoration: none;
                overflow: hidden;
                box-shadow: 0 14px 30px -10px rgba(252, 76, 2, 0.55);
                transition: transform 0.18s ease, box-shadow 0.18s ease;
            }
            .rr-strava:hover {
                transform: translateY(-2px);
                box-shadow: 0 20px 38px -10px rgba(252, 76, 2, 0.65);
            }
            .rr-strava:active { transform: translateY(0); }
            /* Reflet qui balaie le bouton */
            .rr-strava::after {
                content: "";
                position: absolute;
                top: 0;
                left: -60%;
                width: 40%;
                height: 100%;
                background: linear-gradient(100deg, transparent, rgba(255, 255, 255, 0.35), transparent);
                transform: skewX(-18deg);
                animation: rr-sheen 4.5s ease-in-out 1.6s infinite;
            }
            .rr-strava svg { width: 22px; height: 22px; position: relative; z-index: 1; }

            .rr-legal {
                margin: 0;
                text-align: center;
                font-size: 11px;
                line-height: 1.5;
                color: rgba(255, 255, 255, 0.45);
            }

            /* Révélation en cascade au chargement */
            .rr-reveal {
                opacity: 0;
                transform: translateY(16px);
                animation: rr-rise 0.75s cubic-bezier(0.22, 0.61, 0.36, 1) forwards;
                animation-delay: var(--d, 0s);
            }

            @keyframes rr-rise { to { opacity: 1; transform: none; } }
            @keyframes rr-draw { to { width: 76px; } }
            @keyframes rr-kenburns {
                from { transform: scale(1.05) translateY(0); }
                to { transform: scale(1.16) translateY(-2%); }
            }
            @keyframes rr-breathe {
                0%, 100% { opacity: 0.85; transform: translateX(-50%) scale(1); }
                50% { opacity: 1; transform: translateX(-50%) scale(1.06); }
            }
            @keyframes rr-sheen {
                0% { left: -60%; }
                28%, 100% { left: 130%; }
            }

            /* Accessibilité : on coupe le mouvement si demandé */
            @media (prefers-reduced-motion: reduce) {
                .rr-hero__bg,
                .rr-hero__glow,
                .rr-strava::after { animation: none; }
                .rr-reveal { opacity: 1; transform: none; animation: none; }
                .rr-hero__rule { width: 76px; animation: none; }
                .rr-strava { transition: none; }
            }
        </style>
    </head>
    <body class="rr-landing">
        <main class="rr-hero">
            <div class="rr-hero__bg" style="background-image: url('{{ asset('images/michelin_bike_tire.jpg') }}');"></div>
            <div class="rr-hero__veil"></div>
            <div class="rr-hero__glow"></div>

            <div class="rr-hero__top">
                <span class="rr-hero__brand rr-reveal" style="--d: 0.1s;">
                    <img src="{{ asset('images/michelin-logo.png') }}" alt="Michelin" class="rr-hero__logo" />
                </span>

                <p class="rr-hero__eyebrow rr-reveal" style="--d: 0.25s;">
                    RideReady <b>·</b> {{ __('by Michelin') }}
                </p>

                <h1 class="rr-hero__title">
                    <span class="rr-reveal" style="--d: 0.4s;">{{ __('Your tires have an end of life.') }}</span>
                    <span class="rr-y rr-reveal" style="--d: 0.55s;">{{ __('We tell you') }}</span>
                    <span class="rr-reveal" style="--d: 0.7s;">{{ __('before the flat.') }}</span>
                </h1>

                <span class="rr-hero__rule"></span>

                <p class="rr-hero__sub rr-reveal" style="--d: 0.9s;">
                    {{ __('Connect Strava. We analyse your rides, predict wear, and recommend the right Michelin tire.') }}
                </p>
            </div>

            <div class="rr-hero__bottom">
                <a href="{{ route('strava.connect') }}" class="rr-strava rr-reveal" style="--d: 1.05s;">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M15.387 17.944l-2.089-4.116h-3.065L15.387 24l5.15-10.172h-3.066m-7.008-5.599l2.836 5.598h4.172L10.463 0l-7 13.828h4.169"/>
                    </svg>
                    {{ __('Connect with Strava') }}
                </a>
                <p class="rr-legal rr-reveal" style="--d: 1.18s;">
                    {{ __('We don\'t store your Strava data. We analyse it, that\'s all.') }}
                </p>
            </div>

            <div class="rr-hero__grain"></div>
        </main>
    </body>
</html>
