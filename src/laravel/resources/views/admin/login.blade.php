<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login — {{ config('services.hlstats.site_name') }}</title>
    @php $theme = app(\App\Services\ThemeService::class)->getActive(); @endphp
    <style>
        :root {
            {!! app(\App\Services\ThemeService::class)->getCssVariables($theme) !!}
        }
    </style>
    @vite(['resources/css/app.css'])
</head>
<body style="background-color:var(--bg-body); min-height:100vh; display:flex; align-items:center; justify-content:center;">
    <div style="width:100%; max-width:380px; padding:32px; background-color:var(--bg-surface); border:1px solid var(--border); border-radius:var(--border-radius-lg);">
        <h1 style="margin:0 0 24px; color:var(--text-heading); font-size:20px; text-align:center;">
            {{ __('HLStatsX Admin') }}
        </h1>

        @if($errors->any())
            <div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.submit') }}">
            @csrf
            <div style="margin-bottom:16px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:var(--font-size-sm);">{{ __('Username') }}</label>
                <input type="text" name="username" value="{{ old('username') }}" required autofocus
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 10px; font-size:var(--font-size-sm);">
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; margin-bottom:4px; color:var(--text-secondary); font-size:var(--font-size-sm);">{{ __('Password') }}</label>
                <input type="password" name="password" required
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 10px; font-size:var(--font-size-sm);">
            </div>
            <button type="submit" class="hlx-btn-gold" style="width:100%; padding:8px; font-size:var(--font-size-sm);">
                {{ __('Sign In') }}
            </button>
        </form>
    </div>
</body>
</html>
