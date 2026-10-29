<x-layouts.app :title="__('My Account')" :breadcrumb="[__('Account') => route('account.index')]">
    @if(session('success'))
        <div style="background-color:rgba(63,185,80,0.12); color:var(--status-online); border:1px solid var(--status-online); border-radius:6px; padding:10px 12px; margin-bottom:12px;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background-color:rgba(248,81,73,0.12); color:var(--status-offline); border:1px solid var(--status-offline); border-radius:6px; padding:10px 12px; margin-bottom:12px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @if(!$user)
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:18px; max-width:680px; margin:0 auto;">
            <h2 style="margin:0 0 10px; color:var(--text-heading); font-size:18px;">{{ __('Sign in with Steam') }}</h2>
            <p class="hlx-muted" style="margin:0 0 14px; font-size:13px;">
                {{ __('Use your Steam account to access your player space and edit your profile information.') }}
            </p>
            <a href="{{ route('steam.login') }}" class="hlx-btn-gold">{{ __('Continue with Steam') }}</a>
        </div>
    @elseif(!$player)
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:18px; max-width:680px; margin:0 auto;">
            <h2 style="margin:0 0 10px; color:var(--text-heading); font-size:18px;">{{ __('Account linked') }}</h2>
            <p class="hlx-muted" style="margin:0 0 14px; font-size:13px;">
                {{ __('Your Steam account is connected, but no HLStats profile could be found for it.') }}
            </p>
            <form method="POST" action="{{ route('account.logout') }}">
                @csrf
                <button type="submit" class="hlx-btn-green">{{ __('Sign out') }}</button>
            </form>
        </div>
    @else
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:18px; max-width:760px; margin:0 auto;">
            <h2 style="margin:0 0 12px; color:var(--text-heading); font-size:18px;">{{ __('My Account') }}</h2>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:14px;">
                <div>
                    <div class="hlx-muted" style="font-size:12px;">{{ __('Steam ID') }}</div>
                    <div class="hlx-text" style="font-family:var(--font-family-mono);">{{ $user->steam_id }}</div>
                </div>
                <div>
                    <div class="hlx-muted" style="font-size:12px;">{{ __('Player') }}</div>
                    <div class="hlx-text">{{ $player->lastName }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('account.update') }}" style="display:grid; gap:12px;">
                @csrf

                <div>
                    <label for="fullName" class="hlx-muted" style="font-size:12px; display:block; margin-bottom:4px;">{{ __('Real Name:') }}</label>
                    <input id="fullName" name="fullName" type="text" value="{{ old('fullName', $player->fullName ?? '') }}"
                           style="width:100%; background:var(--bg-surface); border:1px solid var(--border); border-radius:6px; padding:8px 10px; color:var(--text-primary);">
                </div>

                <div>
                    <label for="email" class="hlx-muted" style="font-size:12px; display:block; margin-bottom:4px;">{{ __('E-mail Address:') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $player->email ?? '') }}"
                           style="width:100%; background:var(--bg-surface); border:1px solid var(--border); border-radius:6px; padding:8px 10px; color:var(--text-primary);">
                </div>

                <div>
                    <label for="homepage" class="hlx-muted" style="font-size:12px; display:block; margin-bottom:4px;">{{ __('Home Page:') }}</label>
                    <input id="homepage" name="homepage" type="url" value="{{ old('homepage', $player->homepage ?? '') }}"
                           style="width:100%; background:var(--bg-surface); border:1px solid var(--border); border-radius:6px; padding:8px 10px; color:var(--text-primary);">
                </div>

                <div style="display:flex; gap:10px; align-items:center; margin-top:4px;">
                    <button type="submit" class="hlx-btn-gold">{{ __('Save') }}</button>
                </div>
            </form>

            <form method="POST" action="{{ route('account.logout') }}" style="margin-top:14px;">
                @csrf
                <button type="submit" class="hlx-btn-green">{{ __('Sign out') }}</button>
            </form>
        </div>
    @endif
</x-layouts.app>
