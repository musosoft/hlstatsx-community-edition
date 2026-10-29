@props(['adminMode' => false])

@php
    use Illuminate\Support\Str;

    $theme = app(\App\Services\ThemeService::class)->getActive();
    $logo    = $theme['logo']   ?? [];
    $header  = $theme['header'] ?? [];
    $navBtns = $header['nav-buttons'] ?? [];

    $forumUrl     = \App\Models\Option::get('forum_address', '#');
    $showChat     = \App\Models\Option::get('nav_globalchat', '1') === '1';
    $showCheaters = \App\Models\Option::get('nav_cheaters', '1') === '1';
    $steamUrl     = \App\Models\Option::get('steam_url', '');
    $discordUrl   = \App\Models\Option::get('discord_url', '');
    $showSteam    = \App\Models\Option::get('nav_steam', '1') === '1' && !empty($steamUrl);
    $showDiscord  = \App\Models\Option::get('nav_discord', '1') === '1' && !empty($discordUrl);
    $showLang     = \App\Models\Option::get('nav_lang_switcher', '1') === '1';

    // Detect available locales from lang/*.json files (cached 24h)
    $availableLocales = collect(
        \Illuminate\Support\Facades\Cache::remember('available_locales', 86400, fn() =>
            collect(glob(lang_path('*.json')))
                ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
                ->sort()
                ->values()
                ->all()
        )
    );

    // Map locale code → country flag code
    $flagMap = [
        'en' => 'gb', 'fr' => 'fr', 'de' => 'de', 'es' => 'es',
        'it' => 'it', 'pt' => 'pt', 'nl' => 'nl', 'ru' => 'ru',
        'zh' => 'cn', 'ja' => 'jp', 'ko' => 'kr', 'ar' => 'sa',
        'pl' => 'pl', 'cs' => 'cz', 'tr' => 'tr', 'sv' => 'se',
        'da' => 'dk', 'fi' => 'fi', 'no' => 'no', 'hr' => 'hr',
        'hu' => 'hu', 'ro' => 'ro', 'bg' => 'bg', 'uk' => 'ua',
        'sk' => 'sk', 'sl' => 'si', 'el' => 'gr', 'lt' => 'lt',
        'lv' => 'lv', 'et' => 'ee',
    ];
    $currentFlag = $flagMap[app()->getLocale()] ?? app()->getLocale();

    // Build nav links once — reused in sidebar nav and mobile drawer
    $allNavLinks = collect($navBtns)->map(function ($btn) use ($forumUrl) {
        $url = $btn['url'];
        $translatedLabel = __($btn['label']);
        if (strtolower($btn['label']) === 'forums') $url = !empty($forumUrl) ? $forumUrl : '#';
        elseif (strtolower($btn['label']) === 'help') $url = route('help');
        return [
            'label' => $translatedLabel,
            'url' => $url,
            'badge' => Str::upper(Str::substr(preg_replace('/[^A-Za-z0-9]/', '', $translatedLabel), 0, 2) ?: 'NA'),
        ];
    });
    if ($showChat) {
        $allNavLinks->push(['label' => __('Chat'), 'url' => route('chat.index'), 'badge' => 'CH']);
    }
    if ($showCheaters) {
        $allNavLinks->push(['label' => __('Banned Players'), 'url' => route('bans.index'), 'badge' => 'BP']);
    }

    if (auth()->check() && !empty(auth()->user()?->steam_id)) {
        $allNavLinks->push(['label' => __('My Account'), 'url' => route('account.index'), 'badge' => 'ME']);
    } else {
        $allNavLinks->push(['label' => __('Steam Login'), 'url' => route('steam.login'), 'badge' => 'ST']);
    }

    $allNavLinks->push(['label' => __('Admin'), 'url' => route('admin.dashboard'), 'badge' => 'AD']);
@endphp

@if($adminMode)
<div x-data="{ open: false, langOpen: false }">

    <header class="hlx-header">

        {{-- Logo --}}
        <a href="{{ route('home') }}" class="hlx-header-brand">
            @if($logo['show-icon'] ?? true)
                <span class="hlx-header-brand-icon" style="background-color:{{ $logo['icon-bg'] ?? 'var(--accent-primary)' }};">H</span>
            @endif
            <span class="hlx-header-brand-text" style="color:{{ $logo['color'] ?? 'var(--accent-secondary)' }};">
                {{ $logo['text'] ?? 'HLSTATSX: CE' }}
            </span>
        </a>

        {{-- Desktop: nav buttons + social icons (hidden on mobile via CSS) --}}
        <div class="hlx-header-desktop">

            <nav class="hlx-header-nav" aria-label="Primary">
                @foreach($allNavLinks as $link)
                    <a href="{{ $link['url'] }}" class="hlx-header-link">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            @if($header['show-social-icons'] ?? true)
                <div class="hlx-header-meta">
                    @if($showSteam)
                        <a href="{{ $steamUrl }}" title="Steam" target="_blank" rel="noopener" class="hlx-header-meta-link">{{ __('Steam') }}</a>
                    @endif
                    @if($showDiscord)
                        <a href="{{ $discordUrl }}" title="Discord" target="_blank" rel="noopener" class="hlx-header-meta-link">{{ __('Discord') }}</a>
                    @endif
                    @if(($showSteam || $showDiscord) && $showLang)
                        <span class="hlx-header-meta-sep">|</span>
                    @endif
                    @if($showLang && $availableLocales->count() > 1)
                        <div class="hlx-lang-switcher" @click.outside="langOpen = false">
                            <button @click="langOpen = !langOpen"
                                    class="hlx-lang-trigger">
                                <img src="/hlstatsimg/flags/{{ $currentFlag }}.gif" alt="{{ app()->getLocale() }}" style="width:16px; height:11px; object-fit:cover;">
                                {{ strtoupper(app()->getLocale()) }}
                                <span style="font-size:8px; line-height:1;">&#9660;</span>
                            </button>
                            <div x-show="langOpen" x-transition
                                 class="hlx-lang-menu">
                                @foreach($availableLocales as $loc)
                                    @php $fc = $flagMap[$loc] ?? $loc; @endphp
                                    <form method="POST" action="{{ route('language.switch', $loc) }}" style="margin:0;">
                                        @csrf
                                        <button type="submit"
                                                class="hlx-lang-option"
                                                style="background:{{ app()->getLocale() === $loc ? 'var(--accent-primary)' : 'none' }}; color:{{ app()->getLocale() === $loc ? '#fff' : 'var(--text-primary)' }};">
                                            <img src="/hlstatsimg/flags/{{ $fc }}.gif" alt="{{ $loc }}" style="width:16px; height:11px; object-fit:cover;">
                                            {{ strtoupper($loc) }}
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

        </div>

        {{-- Mobile: hamburger button (hidden on desktop via CSS) --}}
        <button class="hlx-hamburger" @click="open = !open" :aria-expanded="open.toString()" aria-label="Toggle navigation">
            <span x-show="!open" aria-hidden="true">&#9776;</span>
            <span x-show="open"  aria-hidden="true">&#x2715;</span>
        </button>

    </header>

    {{-- Mobile nav drawer --}}
    <div x-show="open" x-transition class="hlx-mobile-nav">
        @foreach($allNavLinks as $link)
            <a href="{{ $link['url'] }}" @click="open = false">{{ $link['label'] }}</a>
        @endforeach
        <div class="hlx-mobile-lang">
            @if($showLang && $availableLocales->count() > 1)
                @foreach($availableLocales as $loc)
                    @php $fc = $flagMap[$loc] ?? $loc; @endphp
                    <form method="POST" action="{{ route('language.switch', $loc) }}" style="display:inline;">
                        @csrf
                        <button type="submit"
                                style="display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:600; border:none; cursor:pointer; padding:4px 10px; border-radius:4px; background:{{ app()->getLocale() === $loc ? 'var(--accent-primary)' : 'transparent' }}; color:{{ app()->getLocale() === $loc ? '#fff' : 'var(--text-secondary)' }}; {{ app()->getLocale() !== $loc ? 'border:1px solid var(--border);' : '' }}">
                            <img src="/hlstatsimg/flags/{{ $fc }}.gif" alt="{{ $loc }}" style="width:16px; height:11px; object-fit:cover;">
                            {{ strtoupper($loc) }}
                        </button>
                    </form>
                @endforeach
            @endif
        </div>
    </div>

 </div>
@else
<div class="hlx-mobile-topbar">
    <button class="hlx-mobile-topbar-btn" data-nav-mobile-toggle aria-label="Toggle menu">
        <span class="hlx-icon-open" aria-hidden="true">&#9776;</span>
        <span class="hlx-icon-close" aria-hidden="true">&#x2715;</span>
    </button>
    <a href="{{ route('home') }}" class="hlx-mobile-topbar-brand">
        <img src="/favicon.ico" alt="{{ $logo['text'] ?? 'HLStatsX: CE' }}" class="hlx-mobile-topbar-favicon">
        <span>{{ $logo['text'] ?? 'HLStatsX: CE' }}</span>
    </a>
</div>

<div class="hlx-app-sidebar-overlay" data-nav-overlay></div>

<aside class="hlx-app-sidebar">
    <div class="hlx-app-sidebar-header">
        <a href="{{ route('home') }}" class="hlx-app-brand" title="{{ $logo['text'] ?? 'HLStatsX: CE' }}">
            <img src="/favicon.ico" alt="{{ $logo['text'] ?? 'HLStatsX: CE' }}" class="hlx-app-brand-favicon">
            <span class="hlx-app-brand-text">{{ $logo['text'] ?? 'HLStatsX: CE' }}</span>
        </a>

        <button class="hlx-app-sidebar-toggle" data-nav-collapse-toggle aria-label="Collapse menu">
            <span class="hlx-toggle-open" aria-hidden="true">&#10094;</span>
            <span class="hlx-toggle-close" aria-hidden="true">&#10095;</span>
        </button>
    </div>

    <nav class="hlx-app-sidebar-nav" aria-label="Primary">
        @foreach($allNavLinks as $link)
            @php
                $isExternal = Str::startsWith($link['url'], ['http://', 'https://']);
                $isActive = !$isExternal && $link['url'] !== '#' && request()->fullUrlIs(rtrim($link['url'], '/'), rtrim($link['url'], '/') . '/*');
            @endphp
            <a href="{{ $link['url'] }}"
               class="hlx-app-nav-link{{ $isActive ? ' is-active' : '' }}"
               title="{{ $link['label'] }}"
               @if($isExternal) target="_blank" rel="noopener" @endif>
                <span class="hlx-app-nav-icon">{{ $link['badge'] }}</span>
                <span class="hlx-app-nav-label">{{ $link['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="hlx-app-sidebar-tools">
        @if($showSteam)
            <a href="{{ $steamUrl }}" target="_blank" rel="noopener" class="hlx-app-tool-link" title="Steam">
                <span class="hlx-app-nav-icon">ST</span>
                <span class="hlx-app-nav-label">Steam</span>
            </a>
        @endif
        @if($showDiscord)
            <a href="{{ $discordUrl }}" target="_blank" rel="noopener" class="hlx-app-tool-link" title="Discord">
                <span class="hlx-app-nav-icon">DS</span>
                <span class="hlx-app-nav-label">Discord</span>
            </a>
        @endif

        @if($showLang && $availableLocales->count() > 1)
            <details class="hlx-app-lang-dropdown">
                <summary class="hlx-app-lang-trigger" title="{{ __('Language') }}">
                    <img src="/hlstatsimg/flags/{{ $currentFlag }}.gif" alt="{{ app()->getLocale() }}" class="hlx-app-lang-flag">
                    <span class="hlx-app-nav-label">{{ strtoupper(app()->getLocale()) }}</span>
                    <span class="hlx-app-lang-caret" aria-hidden="true">&#9662;</span>
                </summary>

                <div class="hlx-app-lang-menu">
                    @foreach($availableLocales as $loc)
                        @php
                            $fc = $flagMap[$loc] ?? $loc;
                            $isCurrentLocale = app()->getLocale() === $loc;
                        @endphp
                        <form method="POST" action="{{ route('language.switch', $loc) }}">
                            @csrf
                            <button type="submit" class="hlx-app-lang-btn{{ $isCurrentLocale ? ' is-active' : '' }}" title="{{ strtoupper($loc) }}">
                                <img src="/hlstatsimg/flags/{{ $fc }}.gif" alt="{{ $loc }}" class="hlx-app-lang-flag">
                                <span class="hlx-app-nav-label">{{ strtoupper($loc) }}</span>
                            </button>
                        </form>
                    @endforeach
                </div>
            </details>
        @endif
    </div>
</aside>
@endif
