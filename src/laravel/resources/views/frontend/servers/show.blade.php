<x-layouts.app
    :title="($server->name ?? 'Server') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Servers' => route('servers.index'), $server->name => null]"
    :gameNav="$server->game"
    activeTab="servers">

    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'GameServer',
            'name' => $server->name,
            'url' => route('servers.show', $server->serverId),
            'game' => $server->realgame,
            'serverStatus' => ($server->last_event ?? 0) >= now()->subMinutes(5)->timestamp ? 'Online' : 'Offline',
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>

    {{-- Server info header --}}
    <div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); padding:16px; margin-bottom:16px; display:flex; gap:16px; justify-content:space-between; align-items:flex-start; flex-wrap:wrap;">
        <div>
            <h2 style="margin:0 0 4px; color:var(--text-heading); display:flex; align-items:center; gap:8px;">
                <x-ui.game-logo :game="$server->realgame" :size="20" />
                {{ $server->name }}
            </h2>
            <div class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                {{ $server->full_address }}
            </div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            @foreach([__('Map') => $server->act_map ?? '—', __('Players') => ($server->act_players ?? 0) . '/' . ($server->max_players ?? 0), __('Kills') => number_format($server->kills)] as $label => $value)
                <div style="background-color:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 12px; text-align:center; min-width:80px;">
                    <div class="hlx-muted" style="font-size:10px; text-transform:uppercase;">{{ $label }}</div>
                    <div class="hlx-text" style="font-size:14px; font-weight:600; font-family:var(--font-family-mono);">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Activity chart --}}
    @if(!empty($chartData))
        <div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); margin-bottom:16px; overflow:hidden;">
            <x-ui.section-title title="{{ __('24h Activity') }}" />
            <div style="padding:8px;">
                <x-charts.activity-chart
                    :canvasId="'serverActivity'"
                    :labels="$chartLabels"
                    :data="$chartData"
                    :label="$server->name . ' — Kill Activity'"
                    height="180px"
                />
            </div>
        </div>
    @endif

    {{-- Online players --}}
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
        <x-ui.section-title title="{{ __('Online Players') }} ({{ count($onlinePlayers) }})" />
        @if(empty($onlinePlayers))
            <p class="hlx-muted" style="text-align:center; padding:20px;">{{ __('Server is empty or offline.') }}</p>
        @else
            <table class="hlx-table">
                <thead><tr><th>#</th><th>{{ __('Player') }}</th><th>{{ __('Kills') }}</th><th>{{ __('Headshots') }}</th><th>{{ __('Skill') }}</th></tr></thead>
                <tbody>
                    @foreach($onlinePlayers as $i => $lp)
                        <tr>
                            <td class="hlx-muted">{{ $i + 1 }}</td>
                            <td><x-ui.player-link :player="(object)$lp" /></td>
                            <td class="hlx-text">{{ $lp->live_kills ?? 0 }}</td>
                            <td class="hlx-text">{{ $lp->live_hs ?? 0 }}</td>
                            <td class="hlx-text">{{ $lp->skill ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</x-layouts.app>
