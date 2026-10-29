<x-layouts.app
    :title="$game->name . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), $game->name => null]"
    :gameNav="$game"
    activeTab="servers">

    {{-- Participating Servers Header Stats --}}
    <div class="hlx-muted" style="margin-bottom:12px; font-size:var(--font-size-sm); padding:4px 0;">
        {{ __('Tracking') }} <strong class="hlx-text">{{ $totalPlayers }}</strong> {{ __('players') }}
        {{ __('with') }} <strong class="hlx-text">{{ number_format($totalKills) }}</strong> {{ __('kills') }}
        {{ __('on') }} <strong class="hlx-text">{{ $servers->count() }}</strong> {{ __('servers') }}
    </div>

    {{-- World Map --}}
    @if($mapMarkers->isNotEmpty())
        <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title :title="__('Server Locations')" />
            <div style="padding:8px;">
                <x-maps.world-map :markers="$mapMarkers->toArray()" :playerMarkers="$playerMarkers->toArray()" :tileUrl="$tileUrl" />
            </div>
        </div>
    @endif

    {{-- Curated Edition: bounded recent activity and skill-gain leaders. --}}
    @if($activity->isNotEmpty())
        <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title title="{{ __('Last 7 days activity') }}" />
            <div style="padding:8px;">
                <x-charts.activity-chart
                    canvasId="game-activity-{{ $game->code }}"
                    :labels="$activity->map(fn($point) => gmdate('M j H:i', $point->timestamp))->all()"
                    :data="$activity->pluck('players')->all()"
                    :label="__('Players online')"
                    height="160px"
                />
            </div>
        </div>
    @endif

    @if($recentSkillLeaders->isNotEmpty())
        <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title title="{{ __('Top skill gain · last 7 days') }}" />
            <table class="hlx-table">
                <thead><tr><th>{{ __('Player') }}</th><th>{{ __('Skill gained') }}</th><th>{{ __('Kills') }}</th></tr></thead>
                <tbody>
                    @foreach($recentSkillLeaders as $leader)
                        <tr>
                            <td><a class="hlx-link" href="{{ route('players.show', $leader->playerId) }}">{{ $leader->lastName }}</a></td>
                            <td class="hlx-text">{{ number_format($leader->skill_change) }}</td>
                            <td class="hlx-text">{{ number_format($leader->kills) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Servers table --}}
    @foreach($servers as $row)
        @php $server = $row['server']; @endphp
        <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title title="Participating Servers" />

            <table class="hlx-table">
                <thead>
                    <tr>
                        <th>{{ __('Server') }}</th>
                        <th>{{ __('Address') }}</th>
                        <th>{{ __('Map') }}</th>
                        <th>{{ __('Played') }}</th>
                        <th>{{ __('Players') }}</th>
                        <th>{{ __('Kills') }}</th>
                        <th>{{ __('Headshots') }}</th>
                        <th>{{ __('HS:K') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <a href="{{ route('servers.show', $server->serverId) }}" class="hlx-link" style="display:inline-flex; align-items:center; gap:6px;">
                                <x-ui.game-logo :game="$game->realgame ?? $game->code" :size="16" />
                                {{ $server->name }}
                            </a>
                        </td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            {{ $server->full_address }}
                            @php $connectAddr = !empty($server->publicaddress) ? $server->publicaddress : $server->address.':'.$server->port; @endphp
                            (<a href="steam://connect/{{ $connectAddr }}" class="hlx-link">{{ __('Join') }}</a>)
                        </td>
                        <td class="hlx-text">{{ $server->act_map ?? '—' }}</td>
                        <td class="hlx-text" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            @php
                                $stamp = ($server->map_started == 0) ? 0 : (time() - $server->map_started);
                                $stamp = max(0, $stamp);
                                echo sprintf('%02d:%02d:%02d', floor($stamp/3600), floor(($stamp%3600)/60), $stamp%60);
                            @endphp
                        </td>
                        <td class="hlx-text">{{ $server->act_players }}/{{ $server->max_players }}</td>
                        <td class="hlx-text">{{ number_format($server->kills) }}</td>
                        <td class="hlx-text">{{ number_format($server->headshots) }}</td>
                        <td class="hlx-text">
                            @if($server->kills > 0)
                                {{ sprintf('%.4f', $server->headshots / $server->kills) }}
                            @else
                                0.0000
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- Activity chart for this server --}}
            @if(!empty($row['chart']['kills']))
                <div style="padding:8px;">
                    <x-charts.activity-chart
                        :canvasId="'chart_' . $server->serverId"
                        :labels="$row['chart']['labels']"
                        :data="$row['chart']['kills']"
                        :label="$server->name . ' — Kill Activity'"
                        height="160px"
                    />
                </div>
            @endif

            {{-- Online players --}}
            <div style="padding:0 8px 8px;">
                <div class="hlx-section-title" style="font-size:var(--font-size-sm); margin-bottom:4px;">{{ __('Online Players') }}</div>
                @if($row['players']->isEmpty())
                    <p class="hlx-muted" style="padding:8px; font-size:var(--font-size-sm);">{{ __('No players online.') }}</p>
                @else
                    <table class="hlx-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Player') }}</th>
                                <th>{{ __('Kills') }}</th>
                                <th>{{ __('Headshots') }}</th>
                                <th>{{ __('Skill') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($row['players'] as $i => $lp)
                                <tr>
                                    <td class="hlx-muted">{{ $i + 1 }}</td>
                                    <td><x-ui.player-link :player="(object)$lp" /></td>
                                    <td class="hlx-text">{{ $lp->live_kills ?? 0 }}</td>
                                    <td class="hlx-text">{{ $lp->live_hs ?? 0 }}</td>
                                    <td class="hlx-text">{{ $lp->skill }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    @endforeach

    {{-- Daily Awards --}}
    @php
        $visibleAwards = collect($dailyAwards ?? [])->filter(fn($a) => ($a['d_winner_count'] ?? 0) > 0);
    @endphp
    @if($visibleAwards->isNotEmpty())
        <div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title title="Daily Awards ({{ now()->format('l d F') }})" />
            <table class="hlx-table">
                <thead>
                    <tr><th>{{ __('Award') }}</th><th>{{ __('Player') }}</th><th>{{ __('Count') }}</th></tr>
                </thead>
                <tbody>
                    @foreach($visibleAwards as $award)
                        <tr>
                            <td class="hlx-text">{{ $award['name'] ?? '—' }}</td>
                            <td>
                                @if(!empty($award['daily_winner']))
                                    <x-ui.player-link :player="(object)$award['daily_winner']" />
                                @else
                                    <span class="hlx-muted">—</span>
                                @endif
                            </td>
                            <td class="hlx-text">{{ $award['d_winner_count'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

</x-layouts.app>
