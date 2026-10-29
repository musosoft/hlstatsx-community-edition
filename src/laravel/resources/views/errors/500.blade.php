{{--
    Deliberately standalone: a 500 is often caused by the database or cache being
    unreachable, and the app layout queries hlstats_Options through the header.
    Rendering it here would throw a second exception and fall back to Laravel's
    bare page, so this view stays dependency-free (no DB, no Vite, no theme).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>500 — {{ __('Server error') }}</title>
    <style>
        :root { color-scheme: dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background: #0d1117; color: #c9d1d9; padding: 24px;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; line-height: 1.5;
        }
        .panel { max-width: 560px; text-align: center; }
        .code { font-size: 84px; font-weight: 700; line-height: 1; letter-spacing: -.04em; color: #1f6feb; }
        h1 { margin: 12px 0 0; font-size: 22px; font-weight: 600; color: #e6edf3; }
        p { margin: 12px 0 0; font-size: 13px; line-height: 1.7; color: #8b949e; }
        .actions { margin-top: 28px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        a {
            font-size: 12px; padding: 6px 14px; text-decoration: none; color: #58a6ff;
            background: #161b22; border: 1px solid #1e293b; border-radius: 999px;
        }
        a:hover { color: #79b8ff; }
    </style>
</head>
<body>
    <div class="panel">
        <div class="code">500</div>
        <h1>{{ __('Server error') }}</h1>
        <p>{{ __('Something went wrong on our side. The error has been logged and will be looked at. Please try again in a few minutes.') }}</p>
        <div class="actions">
            <a href="/">{{ __('Home') }}</a>
        </div>
    </div>
</body>
</html>
