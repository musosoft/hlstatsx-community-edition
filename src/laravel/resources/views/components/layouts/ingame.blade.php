<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('services.hlstats.site_name', 'HLStatsX') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0d0d0d; color: #e0e0e0; font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; padding: 8px; }
        h2 { font-size: 13px; color: #f0c040; margin-bottom: 6px; border-bottom: 1px solid #333; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #1a1a1a; color: #aaa; font-size: 10px; text-align: left; padding: 3px 5px; border-bottom: 1px solid #333; }
        td { padding: 3px 5px; border-bottom: 1px solid #1e1e1e; color: #ccc; }
        tr:hover td { background: #181818; }
        a { color: #5dade2; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .num { text-align: right; }
        .muted { color: #666; }
    </style>
</head>
<body>
{{ $slot }}
</body>
</html>
