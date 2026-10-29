{{--
    Standalone for the same reason as 500.blade.php: this view is what
    "php artisan down" and failed dependencies render, so it must not touch the
    database, the cache or the compiled Vite manifest.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>503 — {{ __('Service unavailable') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #0d1117; color: #c9d1d9; padding: 24px;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; line-height: 1.5;
        }
        .panel { max-width: 560px; text-align: center; }
        .code { font-size: 84px; font-weight: 700; line-height: 1; letter-spacing: -.04em; color: #d4a017; }
        h1 { margin: 12px 0 0; font-size: 22px; font-weight: 600; color: #e6edf3; }
        p { margin: 12px 0 0; font-size: 13px; line-height: 1.7; color: #8b949e; }
    </style>
</head>
<body>
    <div class="panel">
        <div class="code">503</div>
        <h1>{{ __('Service unavailable') }}</h1>
        <p>{{ __('The site is temporarily unavailable, most likely for maintenance. Please come back in a few minutes.') }}</p>
    </div>
</body>
</html>
