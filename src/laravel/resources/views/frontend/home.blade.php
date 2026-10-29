<x-layouts.app :title="config('services.hlstats.site_name')" :breadcrumb="['HLStatsX' => route('home')]">

    {{-- Voice Server Section --}}
    @if(count($voiceServers) > 0)
    <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title title="{{ __('Voice Server') }}" />
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Server Name') }}</th>
                    <th>{{ __('Server Address') }}</th>
                    <th>{{ __('Password') }}</th>
                    <th>{{ __('Channels') }}</th>
                    <th>{{ __('Slots used') }}</th>
                    <th>{{ __('Notes') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voiceServers as $vs)
                @php
                    $typeIcon = match((int)$vs->serverType) {
                        0 => '🔊', 1 => '🎮', 2 => '💬', default => '📡'
                    };
                    // Address / link column
                    if ($vs->inviteUrl) {
                        $addrHtml = '<a href="' . e($vs->inviteUrl) . '" class="hlx-link" target="_blank" rel="noopener">' . e($vs->displayAddr ?? $vs->addr) . '</a>';
                    } elseif ((int)$vs->serverType === 0) {
                        $tsLink = 'ts3server://' . e($vs->addr) . '?port=' . (int)$vs->UDPPort . (!empty($vs->password) ? '&password=' . urlencode($vs->password) : '');
                        $addrHtml = '<a href="' . $tsLink . '" class="hlx-link">' . e(($vs->displayAddr ?? $vs->addr . ':' . $vs->UDPPort)) . '</a>';
                    } elseif ((int)$vs->serverType === 1) {
                        $steamUrl = ctype_digit((string)$vs->addr)
                            ? 'https://steamcommunity.com/gid/' . $vs->addr
                            : 'https://steamcommunity.com/groups/' . rawurlencode($vs->addr);
                        $addrHtml = '<a href="' . e($steamUrl) . '" class="hlx-link" target="_blank" rel="noopener">' . e($vs->displayAddr ?? $vs->addr) . '</a>';
                    } else {
                        $addrHtml = '<span class="hlx-muted">—</span>';
                    }
                    // Detail page link
                    $detailRoute = match((int)$vs->serverType) {
                        0 => route('voicecomm.teamspeak', $vs->serverId),
                        1 => route('voicecomm.steam',     $vs->serverId),
                        2 => route('voicecomm.discord',   $vs->serverId),
                        default => null,
                    };
                @endphp
                <tr>
                    <td class="hlx-text">
                        <span style="margin-right:4px;">{{ $typeIcon }}</span>
                        @if($detailRoute)
                            <a href="{{ $detailRoute }}" class="hlx-link">{{ $vs->name }}</a>
                        @else
                            {{ $vs->name }}
                        @endif
                    </td>
                    <td>{!! $addrHtml !!}</td>
                    <td class="hlx-muted">
                        @if((int)$vs->serverType === 0 && !empty($vs->password))
                            {{ $vs->password }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="hlx-text">
                        {{ $vs->channels !== null ? $vs->channels : '—' }}
                    </td>
                    <td class="hlx-text">
                        @if($vs->slotsUsed !== null)
                            {{ $vs->slotsUsed }}{{ $vs->slotsMax ? '/' . number_format($vs->slotsMax) : '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="hlx-muted">{{ $vs->descr }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- World Map --}}
    @if(!empty($serverMarkers) || !empty($playerMarkers))
        <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
            <x-ui.section-title title="{{ __('World Map') }}" />
            <div style="padding:8px 8px 4px;">
                <x-maps.world-map
                    containerId="homeMap"
                    :markers="$serverMarkers"
                    :playerMarkers="$playerMarkers"
                    :tileUrl="$tileUrl"
                    height="360px"
                />
            </div>
            <div style="padding:4px 12px 10px; font-size:11px; color:var(--text-secondary);">
                <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#58d9f0;border:1px solid #fff;vertical-align:middle;margin-right:4px;"></span>{{ __('Player (top 500)') }}</span>
            </div>
        </div>
    @endif

    {{-- Games Section --}}
    <div class="hlx-surface" style="margin-bottom:16px; border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title title="{{ __('Games') }}" />
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Game') }}</th>
                    <th></th>
                    <th>{{ __('Players') }}</th>
                    <th>{{ __('Top Player') }}</th>
                    <th>{{ __('Top Clan') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($games as $row)
                    @php $game = $row['game']; @endphp
                    <tr>
                        <td style="display:flex; align-items:center; gap:6px;">
                            <x-ui.game-logo :game="$game->realgame ?? $game->code" :size="16" />
                            <a href="{{ route('game.show', $game->code) }}" class="hlx-link">{{ $game->name }}</a>
                        </td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('players.index', ['game' => $game->code]) }}" class="hlx-link" title="Players" style="margin-right:6px;">
                                👤 {{ __('Players') }}
                            </a>
                            <a href="{{ route('clans.index', ['game' => $game->code]) }}" class="hlx-link" title="Clans">
                                👥 {{ __('Clans') }}
                            </a>
                        </td>
                        <td class="hlx-text">{{ $row['connectedPlayers'] }}/{{ $row['maxPlayers'] }}</td>
                        <td>
                            @if($row['topPlayer'])
                                <x-ui.flag :code="$row['topPlayer']->flag ?? ''" />
                                <a href="{{ route('players.show', $row['topPlayer']->playerId) }}" class="hlx-link">
                                    {{ $row['topPlayer']->lastName }}
                                </a>
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($row['topClan'])
                                <a href="{{ route('clans.show', $row['topClan']->clanId) }}" class="hlx-link">
                                    {{ $row['topClan']->name }}
                                </a>
                            @else
                                <span class="hlx-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="hlx-muted" style="text-align:center; padding:16px;">No games configured.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- General Statistics --}}
    <div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <x-ui.section-title :title="__('General Statistics')" />
        <div style="padding:12px 16px;">
            <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:6px;">
                <li class="hlx-text">
                    {!! __(':players players and :clans clans ranked in :games games on :servers servers with :kills kills.', [
                        'players' => '<strong>' . number_format($globalStats['players']) . '</strong>',
                        'clans'   => '<strong>' . number_format($globalStats['clans']) . '</strong>',
                        'games'   => '<strong>' . $globalStats['games'] . '</strong>',
                        'servers' => '<strong>' . $globalStats['servers'] . '</strong>',
                        'kills'   => '<strong>' . number_format($globalStats['kills']) . '</strong>',
                    ]) !!}
                </li>
                @if($globalStats['lastKill'])
                    <li class="hlx-text">
                        {{ __('Last Kill') }} <strong>{{ \Carbon\Carbon::parse($globalStats['lastKill'])->locale(app()->getLocale())->translatedFormat('H:i:s, l d F Y') }}</strong>
                    </li>
                @endif
                <li class="hlx-text">
                    {!! __('All statistics are generated in real time. Event history is kept for each player\'s most recent :days days of activity.', [
                        'days' => '<strong>' . config('services.hlstats.history_days', 28) . '</strong>',
                    ]) !!}
                </li>
            </ul>
        </div>
    </div>

</x-layouts.app>
