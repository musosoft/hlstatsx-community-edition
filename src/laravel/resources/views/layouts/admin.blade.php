<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} — {{ config('services.hlstats.site_name', 'HLStatsX: CE') }}</title>


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root {
        {!! app(\App\Services\ThemeService::class)->getCssVariables(app(\App\Services\ThemeService::class)->getActive()) !!}
      }
      /* Admin sidebar overrides */
      .admin-sidebar {
        width: var(--sidebar-width);
        background-color: var(--bg-surface);
        border-right: 1px solid var(--border);
        min-height: calc(100vh - var(--header-height));
        flex-shrink: 0;
      }
      .admin-sidebar a {
        display: block;
        padding: 7px 16px;
        color: var(--text-secondary);
        font-size: var(--font-size-sm);
        border-left: 3px solid transparent;
      }
      .admin-sidebar a:hover,
      .admin-sidebar a.active {
        color: var(--link);
        background-color: var(--bg-surface-alt);
        border-left-color: var(--accent-primary);
        text-decoration: none;
      }
      .admin-sidebar .sidebar-group {
        padding: 8px 16px 4px;
        color: var(--text-secondary);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-top: 8px;
      }
    </style>
</head>
<body>

{{-- Header --}}
<x-layout.header :adminMode="true" />

<div x-data="{ sidebarOpen: false }" style="display:flex; min-height:calc(100vh - var(--header-height)); position:relative;">

    {{-- Mobile overlay (closes sidebar on tap outside) --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition
         style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:299;"></div>

    {{-- Admin sidebar --}}
    <nav class="admin-sidebar" :class="{ open: sidebarOpen }">
        <div class="sidebar-group">Dashboard</div>
        <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
            Overview
        </a>

        <div class="sidebar-group">Content</div>
        <a href="{{ route('admin.players.index') }}" @class(['active' => request()->routeIs('admin.players.*')])>Players</a>
        <a href="{{ route('admin.clans.index') }}" @class(['active' => request()->routeIs('admin.clans.*')])>Clans</a>
        <a href="{{ route('admin.servers.index') }}" @class(['active' => request()->routeIs('admin.servers.*')])>Servers</a>
        <a href="{{ route('admin.games.index') }}" @class(['active' => request()->routeIs('admin.games.*')])>Games</a>
        <a href="{{ route('admin.weapons.index') }}" @class(['active' => request()->routeIs('admin.weapons.*')])>Weapons</a>
        <a href="{{ route('admin.bans.index') }}" @class(['active' => request()->routeIs('admin.bans.*')])>Bans</a>

        @if(auth('admin')->user()?->isSuperAdmin())
        <div class="sidebar-group">System</div>
        <a href="{{ route('admin.themes.index') }}" @class(['active' => request()->routeIs('admin.themes.*')])>Themes</a>
        @endif

        <div style="margin-top:auto; padding:16px;">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="hlx-btn-gold" style="width:100%;text-align:center;">Logout</button>
            </form>
        </div>
    </nav>

    {{-- Main content area --}}
    <div class="admin-content" style="flex:1; padding:16px; overflow-x:auto; min-width:0;">
        {{-- Mobile sidebar toggle --}}
        <button class="hlx-admin-toggle" @click="sidebarOpen = true">
            &#9776; {{ __('Menu') }}
        </button>
        @if(session('success'))
            <div style="background-color:#1a4731; color:#3fb950; padding:8px 12px; border-radius:4px; margin-bottom:12px; border:1px solid #2a6741;">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background-color:#4a1414; color:#f85149; padding:8px 12px; border-radius:4px; margin-bottom:12px; border:1px solid #6a2424;">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </div>

</div>

<x-layout.footer />
</body>
</html>
