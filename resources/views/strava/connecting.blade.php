<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="2;url={{ $target }}">
    <title>{{ __('Connecting to Strava…') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center;
               justify-content: center; gap: 1.5rem; font-family: 'Noto Sans', ui-sans-serif, system-ui, sans-serif;
               background: #fff; color: #18181b; }
        .spinner { width: 48px; height: 48px; border: 4px solid #e4e4e7; border-top-color: #FC4C02;
                   border-radius: 9999px; animation: spin .8s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 1.125rem; margin: 0; }
        .sub { color: #71717a; font-size: .875rem; margin: .25rem 0 0; }
    </style>
</head>
<body>
    <div class="spinner" role="status" aria-label="{{ __('Connecting to Strava…') }}"></div>
    <div style="text-align: center;">
        <h1>{{ __('Connecting to Strava…') }}</h1>
        <p class="sub">{{ __('Importing your rides') }}</p>
    </div>
    <noscript><a href="{{ $target }}">{{ __('Continue') }}</a></noscript>
</body>
</html>
