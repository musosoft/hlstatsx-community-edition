<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('services.hlstats.site_name', 'HLStatsX: CE') }}</title>



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
  @isset($breadcrumb)
    <x-layout.breadcrumb :crumbs="$breadcrumb" />
  @endisset

  {{-- Game navigation tabs --}}
  @isset($gameNav)
    <x-layout.game-nav :game="$gameNav" :active="$activeTab ?? null" />
  @endisset

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
