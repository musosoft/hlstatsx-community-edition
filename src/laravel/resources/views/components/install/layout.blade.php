<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>HLStatsX:CE — Installation</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #0d1117; color: #e6edf3; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #161b22; border: 1px solid #30363d; border-radius: 10px; width: 100%; max-width: 560px; padding: 40px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .subtitle { color: #8b949e; font-size: 14px; margin-bottom: 32px; }
        .steps { display: flex; gap: 8px; margin-bottom: 32px; }
        .step { flex: 1; height: 4px; border-radius: 2px; background: #30363d; }
        .step.done { background: #3fb950; }
        .step.active { background: #c9a84c; }
        label { display: block; font-size: 13px; color: #8b949e; margin-bottom: 5px; margin-top: 14px; }
        input[type=text], input[type=password], input[type=url], input[type=number] {
            width: 100%; padding: 8px 12px; background: #0d1117; border: 1px solid #30363d; border-radius: 6px;
            color: #e6edf3; font-size: 14px; outline: none;
        }
        input:focus { border-color: #c9a84c; }
        .btn { display: inline-block; padding: 9px 20px; border: none; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .btn-primary { background: #c9a84c; color: #0d1117; }
        .btn-secondary { background: #21262d; color: #e6edf3; border: 1px solid #30363d; }
        .btn-row { display: flex; gap: 10px; margin-top: 28px; }
        .error { background: rgba(248,81,73,.1); border: 1px solid #f85149; border-radius: 6px; padding: 10px 14px; margin-top: 16px; font-size: 13px; color: #f85149; }
        .success { background: rgba(63,185,80,.1); border: 1px solid #3fb950; border-radius: 6px; padding: 10px 14px; margin-top: 16px; font-size: 13px; color: #3fb950; }
        .info { background: rgba(201,168,76,.08); border: 1px solid #c9a84c; border-radius: 6px; padding: 10px 14px; margin-top: 14px; font-size: 13px; color: #c9a84c; }
        code { font-family: monospace; background: #0d1117; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .logo { font-weight: 900; font-size: 18px; letter-spacing: -0.5px; margin-bottom: 24px; color: #c9a84c; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">HLStatsX:CE</div>
        {{ $slot }}
    </div>
</body>
</html>
