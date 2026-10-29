<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Définir un nouveau mot de passe — {{ config('services.hlstats.site_name') }}</title>
    @php $theme = app(\App\Services\ThemeService::class)->getActive(); @endphp
    <style>
        :root {
            {!! app(\App\Services\ThemeService::class)->getCssVariables($theme) !!}
        }
    </style>
    @vite(['resources/css/app.css'])
</head>
<body style="background-color:var(--bg-body); min-height:100vh; display:flex; align-items:center; justify-content:center;">
    <div style="width:100%; max-width:420px; padding:32px; background-color:var(--bg-surface); border:1px solid var(--border); border-radius:var(--border-radius-lg);">
        <h1 style="margin:0 0 8px; color:var(--text-heading); font-size:20px; text-align:center;">
            HLStatsX Admin
        </h1>
        <p style="margin:0 0 24px; color:var(--text-secondary); font-size:var(--font-size-sm); text-align:center;">
            Bonjour <strong style="color:var(--text-heading);">{{ $username }}</strong>, votre compte utilise
            l'ancien système d'authentification.<br>
            Veuillez définir un nouveau mot de passe pour continuer.
        </p>

        @if($errors->any())
            <div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.migrate-password.submit') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:var(--font-size-sm);">Nouveau mot de passe <span style="color:var(--status-offline);">*</span></label>
                <input type="password" name="password" required autofocus minlength="12"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 10px; font-size:var(--font-size-sm);">
                <span style="color:var(--text-secondary); font-size:11px;">Minimum 12 caractères, maximum 72 octets</span>
            </div>
            <div style="margin-bottom:24px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:var(--font-size-sm);">Confirmer le mot de passe <span style="color:var(--status-offline);">*</span></label>
                <input type="password" name="password_confirmation" required
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 10px; font-size:var(--font-size-sm);">
            </div>
            <button type="submit" class="hlx-btn-gold" style="width:100%; padding:8px; font-size:var(--font-size-sm);">
                Définir le mot de passe et accéder au panneau
            </button>
        </form>
    </div>
</body>
</html>
