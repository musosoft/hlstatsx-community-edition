@props(['title' => null])
@php
    $resolvedTitle = $title ?? 'Admin';
    if (is_string($resolvedTitle)) {
        if (preg_match('/^(.*?)(:|—)\s*(.+)$/u', $resolvedTitle, $matches)) {
            $resolvedTitle = trim(__($matches[1])) . ' ' . $matches[2] . ' ' . $matches[3];
        } else {
            $resolvedTitle = __($resolvedTitle);
        }
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $resolvedTitle }} — {{ config('services.hlstats.site_name', 'HLStatsX: CE') }}</title>


    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
      :root {
        {!! app(\App\Services\ThemeService::class)->getCssVariables(app(\App\Services\ThemeService::class)->getActive()) !!}
      }
      .admin-sidebar .sidebar-group-btn {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 8px 16px 4px;
        color: var(--text-secondary);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin-top: 8px;
        background: none;
        border: none;
        cursor: pointer;
        text-align: left;
      }
      .admin-sidebar .sidebar-group-btn:hover {
        color: var(--text-primary);
      }
      .sidebar-chevron {
        width: 10px;
        height: 10px;
        flex-shrink: 0;
        transition: transform 0.2s ease;
      }
      .sidebar-chevron.collapsed {
        transform: rotate(-90deg);
      }
      .admin-sidebar {
        width: var(--sidebar-width, 200px);
        background-color: var(--bg-surface);
        border-right: 1px solid var(--border);
        min-height: calc(100vh - var(--header-height, 48px));
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
      }
      .admin-sidebar a {
        display: block;
        padding: 7px 16px;
        color: var(--text-secondary);
        font-size: var(--font-size-sm);
        border-left: 3px solid transparent;
        text-decoration: none;
      }
      .admin-sidebar a:hover,
      .admin-sidebar a.active {
        color: var(--link);
        background-color: var(--bg-surface-alt);
        border-left-color: var(--accent-primary);
      }
    </style>
</head>
<body>

{{-- Header --}}
<x-layout.header :adminMode="true" />

<div x-data="{ sidebarOpen: false }" style="display:flex; min-height:calc(100vh - var(--header-height, 48px)); position:relative;">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" x-transition
         style="position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:299;"></div>

    {{-- Admin sidebar --}}
    <nav class="admin-sidebar" :class="{ open: sidebarOpen }">
        <div style="flex:1; overflow-y:auto;">

            {{-- Dashboard --}}
            <div x-data="{ open: true }">
                <button @click="open = !open" class="sidebar-group-btn">
                    <span>{{ __('Dashboard') }}</span>
                    <svg :class="{ 'collapsed': !open }" class="sidebar-chevron" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5L5 6.5L8 3.5"/></svg>
                </button>
                <div x-show="open" x-transition>
                    <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>{{ __('Overview') }}</a>
                </div>
            </div>

            {{-- Content --}}
            <div x-data="{ open: {{ request()->routeIs('admin.players.*', 'admin.clans.*', 'admin.servers.*', 'admin.games.*', 'admin.weapons.*', 'admin.bans.*', 'admin.voicecomm.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-group-btn">
                    <span>{{ __('Content') }}</span>
                    <svg :class="{ 'collapsed': !open }" class="sidebar-chevron" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5L5 6.5L8 3.5"/></svg>
                </button>
                <div x-show="open" x-transition>
                    <a href="{{ route('admin.players.index') }}" @class(['active' => request()->routeIs('admin.players.*')])>{{ __('Players') }}</a>
                    <a href="{{ route('admin.clans.index') }}" @class(['active' => request()->routeIs('admin.clans.*')])>{{ __('Clans') }}</a>
                    <a href="{{ route('admin.servers.index') }}" @class(['active' => request()->routeIs('admin.servers.*')])>{{ __('Servers') }}</a>
                    <a href="{{ route('admin.games.index') }}" @class(['active' => request()->routeIs('admin.games.*')])>{{ __('Games') }}</a>
                    <a href="{{ route('admin.weapons.index') }}" @class(['active' => request()->routeIs('admin.weapons.*')])>{{ __('Weapons') }}</a>
                    <a href="{{ route('admin.bans.index') }}" @class(['active' => request()->routeIs('admin.bans.*')])>{{ __('Bans') }}</a>
                    <a href="{{ route('admin.voicecomm.index') }}" @class(['active' => request()->routeIs('admin.voicecomm.*')])>{{ __('Voice Servers') }}</a>
                </div>
            </div>

            {{-- Game Data --}}
            <div x-data="{ open: {{ request()->routeIs('admin.ranks.*', 'admin.teams.*', 'admin.roles.*', 'admin.actions.*', 'admin.awards.*', 'admin.ribbons.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-group-btn">
                    <span>{{ __('Game Data') }}</span>
                    <svg :class="{ 'collapsed': !open }" class="sidebar-chevron" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5L5 6.5L8 3.5"/></svg>
                </button>
                <div x-show="open" x-transition>
                    <a href="{{ route('admin.ranks.index') }}" @class(['active' => request()->routeIs('admin.ranks.*')])>{{ __('Ranks') }}</a>
                    <a href="{{ route('admin.teams.index') }}" @class(['active' => request()->routeIs('admin.teams.*')])>{{ __('Teams') }}</a>
                    <a href="{{ route('admin.roles.index') }}" @class(['active' => request()->routeIs('admin.roles.*')])>{{ __('Roles') }}</a>
                    <a href="{{ route('admin.actions.index') }}" @class(['active' => request()->routeIs('admin.actions.*')])>{{ __('Actions') }}</a>
                    <a href="{{ route('admin.awards.index') }}" @class(['active' => request()->routeIs('admin.awards.*')])>{{ __('Awards') }}</a>
                    <a href="{{ route('admin.ribbons.index') }}" @class(['active' => request()->routeIs('admin.ribbons.*')])>{{ __('Ribbons') }}</a>
                </div>
            </div>

            {{-- Configuration --}}
            <div x-data="{ open: {{ request()->routeIs('admin.options.*', 'admin.admin-users.*', 'admin.pages.*', 'admin.clan-tags.*', 'admin.host-groups.*', 'admin.server-config.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-group-btn">
                    <span>{{ __('Configuration') }}</span>
                    <svg :class="{ 'collapsed': !open }" class="sidebar-chevron" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5L5 6.5L8 3.5"/></svg>
                </button>
                <div x-show="open" x-transition>
                    @if(auth('admin')->user()?->isSuperAdmin())
                        <a href="{{ route('admin.options.index') }}" @class(['active' => request()->routeIs('admin.options.*')])>{{ __('Options') }}</a>
                        <a href="{{ route('admin.admin-users.index') }}" @class(['active' => request()->routeIs('admin.admin-users.*')])>{{ __('Admin Users') }}</a>
                        <a href="{{ route('admin.pages.index') }}" @class(['active' => request()->routeIs('admin.pages.*')])>{{ __('Pages') }}</a>
                    @endif
                    <a href="{{ route('admin.clan-tags.index') }}" @class(['active' => request()->routeIs('admin.clan-tags.*')])>{{ __('Clan Tags') }}</a>
                    <a href="{{ route('admin.host-groups.index') }}" @class(['active' => request()->routeIs('admin.host-groups.*')])>{{ __('Host Groups') }}</a>
                    <a href="{{ route('admin.server-config.index') }}" @class(['active' => request()->routeIs('admin.server-config.*')])>{{ __('Server Config') }}</a>
                </div>
            </div>

            {{-- System (super administrators only) --}}
            @if(auth('admin')->user()?->isSuperAdmin())
            <div x-data="{ open: {{ request()->routeIs('admin.themes.*', 'admin.tools.*') ? 'true' : 'false' }} }">
                <button @click="open = !open" class="sidebar-group-btn">
                    <span>{{ __('System') }}</span>
                    <svg :class="{ 'collapsed': !open }" class="sidebar-chevron" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3.5L5 6.5L8 3.5"/></svg>
                </button>
                <div x-show="open" x-transition>
                    <a href="{{ route('admin.themes.index') }}" @class(['active' => request()->routeIs('admin.themes.*')])>{{ __('Themes') }}</a>
                    <a href="{{ route('admin.tools.index') }}" @class(['active' => request()->routeIs('admin.tools.*')])>{{ __('Tools') }}</a>
                </div>
            </div>
            @endif

        </div>

        <div style="padding:12px 16px;">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="hlx-btn-gold" style="width:100%; text-align:center;">{{ __('Logout') }}</button>
            </form>
        </div>
    </nav>

    {{-- Main content area --}}
    <div class="admin-content" style="flex:1; padding:16px; overflow-x:auto; min-width:0;">
        {{-- Mobile sidebar toggle --}}
        <button class="hlx-admin-toggle" @click="sidebarOpen = true">&#9776; {{ __('Menu') }}</button>

        @if(session('success'))
            <div style="background-color:rgba(63,185,80,0.1); color:var(--status-online); padding:8px 12px; border-radius:4px; margin-bottom:12px; border:1px solid var(--status-online);">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div style="background-color:rgba(248,81,73,0.1); color:var(--status-offline); padding:8px 12px; border-radius:4px; margin-bottom:12px; border:1px solid var(--status-offline);">
                {{ session('error') }}
            </div>
        @endif

        {{ $slot }}
    </div>

</div>

<x-layout.footer />
</body>
</html>
