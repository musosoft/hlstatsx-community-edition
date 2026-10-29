@props([
    'code'       => '500',
    'title'      => '',
    'message'    => '',
    'showSearch' => false,
])

@php
    $links = [
        ['label' => __('Home'),    'url' => route('home')],
        ['label' => __('Players'), 'url' => route('players.index')],
        ['label' => __('Servers'), 'url' => route('servers.index')],
        ['label' => __('Clans'),   'url' => route('clans.index')],
        ['label' => __('Maps'),    'url' => route('maps.index')],
        ['label' => __('Awards'),  'url' => route('awards.index')],
    ];
@endphp

<div style="max-width:640px; margin:48px auto; text-align:center;">

    <div style="font-size:84px; line-height:1; font-weight:700; color:var(--accent-primary); letter-spacing:-.04em;">
        {{ $code }}
    </div>

    <h1 style="margin:12px 0 0; font-size:22px; font-weight:600; color:var(--text-heading);">
        {{ $title }}
    </h1>

    <p style="margin:12px 0 0; font-size:13px; line-height:1.7; color:var(--text-secondary);">
        {{ $message }}
    </p>

    @if($showSearch)
        <form action="{{ route('search') }}" method="get"
              style="display:flex; gap:6px; justify-content:center; margin-top:24px;">
            <input type="text" name="q" value="{{ request()->query('q') }}"
                   placeholder="{{ __('Search for a player, clan or server…') }}"
                   style="flex:1; max-width:340px; font-size:13px; padding:7px 10px;
                          background:var(--bg-surface-alt); border:1px solid var(--border);
                          border-radius:var(--border-radius-sm); color:var(--text-primary);">
            <button type="submit" class="hlx-btn-gold" style="padding:7px 14px; font-size:13px;">
                {{ __('Search') }}
            </button>
        </form>
    @endif

    <div style="display:flex; flex-wrap:wrap; gap:8px; justify-content:center; margin-top:28px;">
        @foreach($links as $link)
            <a href="{{ $link['url'] }}"
               style="font-size:12px; padding:6px 14px; text-decoration:none;
                      background:var(--bg-surface-alt); border:1px solid var(--border);
                      border-radius:var(--border-radius-pill); color:var(--link);">
                {{ $link['label'] }}
            </a>
        @endforeach
    </div>

</div>
