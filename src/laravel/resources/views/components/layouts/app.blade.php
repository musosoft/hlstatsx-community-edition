@props([
    'title'     => null,
    'description' => null,
    'breadcrumb' => null,
    'gameNav'   => null,
    'activeTab' => null,
    'robots' => 'index,follow',
    'canonical' => null,
    'ogImage' => null,
])
@php
    $siteName = config('services.hlstats.site_name', 'HLStatsX: CE');
    $metaTitle = $title ?? $siteName;
    $metaDescription = $description
        ?? config('services.hlstats.site_subtitle')
        ?? 'HLStatsX community statistics for players, clans, maps, servers, and weapons.';
    $canonicalUrl = $canonical ?: request()->fullUrlWithoutQuery([
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ]);
    $ogImageUrl = $ogImage ?: asset('hlstatsimg/favicon.ico');

    $availableLocales = \Illuminate\Support\Facades\Cache::remember('available_locales', 86400, fn() =>
        collect(glob(lang_path('*.json')))
            ->map(fn($f) => pathinfo($f, PATHINFO_FILENAME))
            ->sort()
            ->values()
            ->all()
    );

    $jsonLd = [
        [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $siteName,
            'url' => $canonicalUrl,
            'description' => $metaDescription,
        ],
        [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => $canonicalUrl,
            'logo' => $ogImageUrl,
        ],
    ];

    if (!empty($breadcrumb) && is_array($breadcrumb)) {
        $position = 1;
        $breadcrumbItems = [[
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => $siteName,
            'item' => request()->fullUrlWithoutQuery(['lang']),
        ]];

        foreach ($breadcrumb as $label => $url) {
            $breadcrumbItems[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => (string) $label,
                'item' => $url ?: $canonicalUrl,
            ];
        }

        $jsonLd[] = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbItems,
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonicalUrl }}">

    @foreach($availableLocales as $locale)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ request()->fullUrlWithoutQuery(['lang']) }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $ogImageUrl }}">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $ogImageUrl }}">

    @foreach($jsonLd as $schema)
        <script type="application/ld+json">{!! json_encode($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    @endforeach



    {{-- Vite assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Inject active theme CSS variables --}}
    <style>
      :root {
        {!! app(\App\Services\ThemeService::class)->getCssVariables(app(\App\Services\ThemeService::class)->getActive()) !!}
      }
    </style>

    {{ $head ?? '' }}
</head>
<body class="hlx-app-body">

<div id="hlxAppShell" class="hlx-app-shell">

{{-- Header --}}
<x-layout.header />

<div class="hlx-layout-shell">
    {{-- Breadcrumb --}}
    @if($breadcrumb)
        <x-layout.breadcrumb :crumbs="$breadcrumb" />
    @endif

    {{-- Game navigation tabs --}}
    @if($gameNav)
        <x-layout.game-nav :game="$gameNav" :active="$activeTab" />
    @endif

    {{-- Main content --}}
    <main class="hlx-container hlx-main-content">
        {{ $slot }}
    </main>

    {{-- Footer --}}
    <x-layout.footer />
</div>

{{-- Cookie notice --}}
<x-layout.cookie-banner />

</div>

</body>
</html>
