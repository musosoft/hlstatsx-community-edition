<x-layouts.app
    :title="($player->lastName ?? __('Player')) . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => route('players.index', ['game' => $player->game]), $player->lastName => null]"
    :gameNav="$player->game"
    activeTab="players">

    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $player->lastName,
            'url' => route('players.show', $player->playerId),
            'description' => $player->fullName ?: null,
            'sameAs' => $steamId64 ? ['https://steamcommunity.com/profiles/' . $steamId64] : null,
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-slot:head>

@php
    // Connection time â†’ Xd HH:MM:SSh
    $ct   = (int) $player->connection_time;
    $days = (int) floor($ct / 86400);
    $hrs  = (int) floor(($ct % 86400) / 3600);
    $mins = (int) floor(($ct % 3600) / 60);
    $secs = (int) ($ct % 60);
    $connectionStr = "{$days}d "
        . str_pad($hrs,  2, '0', STR_PAD_LEFT) . ':'
        . str_pad($mins, 2, '0', STR_PAD_LEFT) . ':'
        . str_pad($secs, 2, '0', STR_PAD_LEFT) . 'h';

    // Computed stats
    $kpm = $player->connection_time > 0
        ? round($player->kills / ($player->connection_time / 60), 2)
        : '-';
    $kpd = $player->deaths > 0
        ? number_format($player->kills / $player->deaths, 4)
        : number_format((float) $player->kills, 4);
    $hpk = $player->kills > 0
        ? number_format($player->headshots / $player->kills, 4)
        : '-';
    $spk = ($player->kills > 0 && $player->shots > 0)
        ? round($player->shots / $player->kills, 2)
        : '-';
    $acc = $player->shots > 0
        ? round($player->hits / $player->shots * 100, 1) . '%'
        : '-';

    // Date formatting â€” createdate/last_event are Unix timestamps stored directly on the player record
    $dateFmt = 'D\. M\. jS, Y \@ H:i:s';
    $firstConnectFmt = (int)($player->createdate ?? 0) > 0
        ? \Carbon\Carbon::createFromTimestamp((int)$player->createdate)->format($dateFmt)
        : '-';
    $lastConnectFmt = (int)($player->last_event ?? 0) > 0
        ? \Carbon\Carbon::createFromTimestamp((int)$player->last_event)->format($dateFmt)
        : '-';

    // Favorites from top lists
    $favMap    = $topMaps->first()?->map    ?? '-';
    $favWeaponName = $topWeapons->first()?->name ?? $topWeapons->first()?->code ?? '-';
    $favWeaponCode = $topWeapons->first()?->code ?? null;
    $favWeapon = $favWeaponName;

    // Rank display
    $rankName = $player->hideranking ? 'Not active' : ($rank?->rankName ?? 'Not active');
@endphp

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">

    {{-- Section header --}}
    <div style="background:var(--bg-surface-alt); border-bottom:1px solid var(--border); padding:4px 12px; font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em;">
        - Player Information
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'general' }">

        <div style="display:flex; align-items:center; gap:6px; padding:6px 12px; flex-wrap:wrap; font-size:12px; background:var(--bg-surface-alt); border-bottom:1px solid var(--border);">
            @foreach([
                'general'   => __('General'),
                'actions'   => __('Teams & Actions'),
                'weapons'   => __('Weapons'),
                'maps'      => __('Maps & Servers'),
                'killstats' => __('Killstats'),
            ] as $key => $label)
                @unless($loop->first)<span class="hlx-muted">|</span>@endunless
                <button
                    @click="tab='{{ $key }}'"
                    style="background:none; border:none; padding:0; cursor:pointer; font-size:12px; white-space:nowrap; transition:color .15s;"
                    :style="tab==='{{ $key }}' ? 'color:var(--accent-primary); font-weight:600;' : 'color:var(--link);'">
                    {!! $label !!}
                </button>
            @endforeach
        </div>

        {{-- ============================================================
             GENERAL TAB
        ============================================================ --}}
        <div x-show="tab==='general'" style="padding:12px 14px;">
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start;">

                {{-- LEFT â€” Player Profile --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                        {{ __('Player Profile') }}
                    </div>

                    {{-- Name row with avatar --}}
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <div style="font-size:15px; font-weight:700; color:var(--text-heading); margin-bottom:5px;">
                                <x-ui.flag :code="$player->flag ?? ''" />
                                {{ $player->lastName }}
                            </div>

                            @if($player->city || $player->country)
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                {{ __('Location:') }}
                                @if($player->city)<span style="color:var(--text-heading);">{{ $player->city }}</span>@endif
                                @if($player->city && $player->country), @endif
                                @if($player->country)<a href="{{ route('players.index', ['game' => $player->game]) }}" class="hlx-link">{{ $player->country }}</a>@endif
                            </div>
                            @endif

                            @if($steamUniqueId)
                            @php
                                $displaySteamId = preg_match('/^STEAM_\d+:/', $steamUniqueId)
                                    ? $steamUniqueId
                                    : 'STEAM_0:' . $steamUniqueId;
                            @endphp
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                {{ __('Steam:') }}
                                @if($steamId64)
                                    <a href="https://steamcommunity.com/profiles/{{ $steamId64 }}" target="_blank" rel="noopener noreferrer" class="hlx-link">{{ $displaySteamId }}</a>
                                @else
                                    <span style="color:var(--text-heading);">{{ $displaySteamId }}</span>
                                @endif
                            </div>
                            @endif

                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                {{ __('Member Since:') }} <span style="color:var(--text-heading);">{{ __('Private') }}</span>
                            </div>
                            <div style="font-size:12px; color:var(--text-secondary); margin-bottom:3px;">
                                {{ __('Status:') }} <strong style="color:{{ $isOnline ? '#4ade80' : 'var(--text-heading)' }};">{{ $isOnline ? __('online') : __('offline') }}</strong>
                            </div>
                            <div style="font-size:12px; margin-bottom:2px;">
                                {{ __('Karma:') }}
                                @if($isBanned)
                                    <strong style="color:#f87171;">{{ __('Banned') }}</strong>
                                @else
                                    <strong style="color:#4ade80;">{{ __('In good standing') }}</strong>
                                @endif
                            </div>
                        </div>

                        @if($avatar)
                            <img src="{{ $avatar }}" alt="{{ $player->lastName }}" width="84" height="84"
                                 style="border-radius:4px; border:1px solid var(--border); flex-shrink:0; margin-left:10px;">
                        @endif
                    </div>

                    {{-- Info rows --}}
                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary); width:44%;">{{ __('Member of Clan:') }}</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">
                                @if($player->clanRelation)
                                    <a href="{{ route('clans.show', $player->clanRelation->clanId) }}" class="hlx-link">{{ $player->clanRelation->tag }}</a>
                                @else
                                    {{ __('(None)') }}
                                @endif
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Real Name:') }}</td>
                            <td style="padding:4px 6px;">
                                @if($player->fullName)
                                    <span style="color:var(--text-heading);">{{ $player->fullName }}</span>
                                @else
                                    <span style="color:#f97316; font-style:italic;">{{ __('(Not Specified)') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('E-mail Address:') }}</td>
                            <td style="padding:4px 6px;">
                                @if($player->email)
                                    <span style="color:var(--text-heading);">{{ $player->email }}</span>
                                @else
                                    <span style="color:#f97316; font-style:italic;">{{ __('(Not Specified)') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Home Page:') }}</td>
                            <td style="padding:4px 6px;">
                                @if($player->homepage)
                                    <a href="{{ $player->homepage }}" target="_blank" rel="noopener noreferrer" class="hlx-link">{{ $player->homepage }}</a>
                                @else
                                    <span style="color:#f97316; font-style:italic;">{{ __('(Not Specified)') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('First Connect:') }}*</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">{{ $firstConnectFmt }}</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Last Connect:') }}*</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">{{ $lastConnectFmt }}</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Total Connection Time:') }}</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">{{ $connectionStr }}</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Last Ping:') }}</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">
                                @if(($livePing ?? 0) > 0)
                                    {{ $livePing }} ms
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Favorite Server:') }}*</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">{{ $favoriteServer?->server_name ?? '-' }}</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Favorite Map:') }}*</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">{{ $favMap }}</td>
                        </tr>
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ __('Favorite Weapon:') }}*</td>
                            <td style="padding:4px 6px; color:var(--text-heading);">
                                @if($favWeaponCode)
                                    @php $fwImg = 'hlstatsimg/games/' . $realgame . '/weapons/' . strtolower($favWeaponCode) . '.png'; @endphp
                                    <a href="{{ route('weapons.show', [$favWeaponCode, 'game' => $player->game]) }}">
                                        <img src="{{ asset($fwImg) }}" alt="{{ $favWeaponName }}" style="height:32px; width:auto; max-width:110px; object-fit:contain;"
                                             onerror="this.replaceWith(document.createTextNode('{{ addslashes($favWeaponName) }}'))">
                                    </a>
                                @else
                                    {{ $favWeapon }}
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- RIGHT â€” Statistics Summary --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                        {{ __('Statistics Summary') }}
                    </div>

                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                        {{-- Activity bar --}}
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary); width:44%;">{{ __('Activity:') }}</td>
                            <td style="padding:4px 6px;">
                                @php
                                    $activityVal = max(0, min(100, (int) $player->activity));
                                @endphp
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <div style="flex:1; background:#1a1a2e; border:1px solid var(--border); border-radius:2px; height:8px; overflow:hidden;">
                                        <div style="background:#4ade80; height:100%; width:{{ $activityVal }}%;"></div>
                                    </div>
                                    <span style="color:var(--text-heading); font-size:11px; white-space:nowrap;">{{ $activityVal }}%</span>
                                </div>
                            </td>
                        </tr>

                        @foreach([
                            __('Points')               => ['val' => number_format($player->skill),             'bold' => true],
                            __('Rank')                 => ['val' => $rankName,                                  'bold' => true],
                            __('Kills per Minute')     => ['val' => $kpm,                                       'bold' => false],
                            __('Kills per Death')      => ['val' => $kpd,                                       'bold' => false],
                            __('Headshots per Kill')   => ['val' => $hpk,                                       'bold' => false],
                            __('Shots per Kill')       => ['val' => $spk,                                       'bold' => false],
                            __('Weapon Accuracy')      => ['val' => $acc,                                       'bold' => false],
                            __('Headshots')            => ['val' => number_format($player->headshots),          'bold' => false],
                            __('Kills')                => ['val' => number_format($player->kills),              'bold' => false],
                            __('Deaths')               => ['val' => number_format($player->deaths),             'bold' => false],
                            __('Longest Kill Streak')  => ['val' => number_format($player->kill_streak),       'bold' => false],
                            __('Longest Death Streak') => ['val' => number_format($player->death_streak),      'bold' => false],
                            __('Suicides')             => ['val' => number_format($player->suicides),          'bold' => false],
                            __('Teammate Kills')       => ['val' => number_format($player->teamkills),         'bold' => false],
                        ] as $label => $row)
                        <tr style="border-top:1px solid var(--border);">
                            <td style="padding:4px 6px; color:var(--text-secondary);">{{ $label }}:</td>
                            <td style="padding:4px 6px; color:var(--text-heading);{{ $row['bold'] ? ' font-weight:600;' : '' }}">
                                {{ $row['val'] }}
                            </td>
                        </tr>
                        @endforeach
                    </table>

                    {{-- History links --}}
                    <div style="margin-top:12px; padding-top:10px; border-top:1px solid var(--border); font-size:12px;">
                        <div style="margin-bottom:5px;">
                            <x-ui.flag :code="$player->flag ?? ''" />
                            <strong style="color:var(--text-heading);">{{ $player->lastName }}</strong>'s {{ __('History:') }}
                        </div>
                        <div style="display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
                            <a href="{{ route('players.events', $player->playerId) }}" class="hlx-link">{{ __('Events') }}</a>
                            <span class="hlx-muted">|</span>
                            <a href="{{ route('players.sessions', $player->playerId) }}" class="hlx-link">{{ __('Sessions') }}</a>
                            <span class="hlx-muted">|</span>
                            <a href="{{ route('players.awards', $player->playerId) }}" class="hlx-link">{{ __('Awards') }} ({{ $awardsCount }})</a>
                            <span class="hlx-muted">|</span>
                            <a href="{{ route('players.chat', $player->playerId) }}" class="hlx-link">{{ __('Chat') }}</a>
                        </div>
                        <div style="margin-top:6px;">
                            <a href="{{ route('players.index', ['game' => $player->game, 'search' => $player->lastName]) }}" class="hlx-link" style="font-size:11px;">
                                {{ __('Find other players with the same name') }}
                            </a>
                        </div>
                    </div>
                </div>

            </div>{{-- end grid --}}

            {{-- ============================================================
                 MISCELLANEOUS STATISTICS (inside General tab)
            ============================================================ --}}
            <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-top:16px;">

                <div style="background:var(--bg-surface-alt); border-bottom:1px solid var(--border); padding:4px 12px; font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em;">
                    {{ __('Miscellaneous Statistics') }}
                </div>

                {{-- Row 1: Player Trend | Forum Signature --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; border-bottom:1px solid var(--border);">

                    {{-- Player Trend --}}
                    <div style="padding:12px 14px; border-right:1px solid var(--border);">
                        <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                            {{ __('Player Trend') }}
                        </div>
                        @if(!empty($chartData))
                            <x-charts.skill-chart :canvasId="'miscSkillChart'" :labels="$chartLabels" :data="$chartData" height="160px" />
                        @else
                            <div class="hlx-muted" style="text-align:center; padding:20px; font-size:12px;">{{ __('No trend data available.') }}</div>
                        @endif
                    </div>

                    {{-- Forum Signature --}}
                    <div style="padding:12px 14px;">
                        <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                            {{ __('Forum Signature') }}
                        </div>
                        @php
                            $profileUrl = route('players.show', $player->playerId);
                            $sigUrl     = route('players.signature', $player->playerId);
                            $bbCode     = '[url=' . $profileUrl . '][img]' . $sigUrl . '[/img][/url]';
                        @endphp
                        <div style="margin-bottom:8px;">
                            <img src="{{ $sigUrl }}" alt="{{ __('Forum Signature') }}"
                                 width="400" height="75" loading="lazy"
                                 style="max-width:100%; height:auto; border:1px solid var(--border); border-radius:3px;">
                        </div>
                        <div style="font-size:11px; color:var(--text-secondary); margin-bottom:6px;">
                            {{ __('Use this code in your forum signature:') }}
                        </div>
                        <textarea onclick="this.select();" readonly
                            style="width:100%; font-size:10px; background:var(--bg-surface-alt); border:1px solid var(--border); color:var(--text-secondary); padding:4px 6px; border-radius:3px; resize:vertical; min-height:40px; font-family:monospace;">{{ $bbCode }}</textarea>
                    </div>
                </div>

                {{-- Row 2: Ranks | Rank History --}}
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0; border-bottom:1px solid var(--border);">

                    {{-- Ranks --}}
                    <div style="padding:12px 14px; border-right:1px solid var(--border);">
                        <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                            {{ __('Ranks') }}
                        </div>
                        <div style="font-size:12px; color:var(--text-secondary); margin-bottom:8px;">
                            {{ __('Current rank:') }} <strong style="color:var(--text-heading);">{{ $rankName }}</strong>
                        </div>
                        @if($rank)
                        <div style="margin-bottom:8px; display:flex; justify-content:center;">
                            <img src="{{ asset('hlstatsimg/ranks/' . $rank->image . '.png') }}"
                                 alt="{{ $rank->rankName }}" title="{{ $rank->rankName }}"
                                 width="48" height="48"
                                 style="border:2px solid var(--accent-primary); border-radius:4px;">
                        </div>
                        @endif
                        @if($nextRank)
                            @php
                                $killsNeeded = $nextRank->minKills - $player->kills;
                                $rangeTotal  = $nextRank->minKills - ($rank?->minKills ?? 0);
                                $progress    = $rangeTotal > 0 ? min(100, round(($player->kills - ($rank?->minKills ?? 0)) / $rangeTotal * 100)) : 100;
                            @endphp
                            <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                                <div style="flex:1; background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:2px; height:10px; overflow:hidden;">
                                    <div style="background:var(--accent-primary); height:100%; width:{{ $progress }}%;"></div>
                                </div>
                                <span style="font-size:11px; color:var(--text-secondary); white-space:nowrap;">
                                    {{ __('Kills needed:') }} {{ number_format($killsNeeded) }} ({{ $progress }}%)
                                </span>
                            </div>
                        @else
                            <div style="font-size:11px; color:#4ade80;">{{ __('Maximum rank achieved!') }}</div>
                        @endif
                    </div>

                    {{-- Rank History --}}
                    <div style="padding:12px 14px;">
                        <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                            {{ __('Rank History') }}
                        </div>
                        @if($rankHistory->isEmpty())
                            <div class="hlx-muted" style="font-size:12px;">{{ __('No history data.') }}</div>
                        @else
                            @php
                                $prevRankName = null;
                                $transitions  = [];
                                foreach ($rankHistory as $day) {
                                    $dayRank = $allRanks->filter(fn($r) => $r->minKills <= $day->kills)->last();
                                    $rName   = $dayRank?->rankName ?? '-';
                                    if ($rName !== $prevRankName) {
                                        $transitions[] = ['date' => $day->eventTime, 'rankName' => $rName, 'image' => $dayRank?->image ?? ''];
                                        $prevRankName   = $rName;
                                    }
                                }
                            @endphp
                            @if(empty($transitions))
                                <div class="hlx-muted" style="font-size:12px;">{{ __('No rank changes recorded.') }}</div>
                            @else
                                <table style="width:100%; border-collapse:collapse; font-size:11px;">
                                    @foreach($transitions as $t)
                                    <tr style="border-top:1px solid var(--border);">
                                        <td style="padding:3px 6px; color:var(--text-secondary); width:110px;">{{ $t['date'] }}</td>
                                        <td style="padding:3px 6px;">
                                            @if($t['image'])
                                                <img src="{{ asset('hlstatsimg/ranks/' . $t['image'] . '.png') }}" alt="{{ $t['rankName'] }}" width="14" height="14" style="vertical-align:middle; margin-right:4px;">
                                            @endif
                                            <span style="color:var(--text-heading);">{{ $t['rankName'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </table>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Row 3: Awards --}}
                <div style="padding:12px 14px; border-bottom:1px solid var(--border);">
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                        {{ __('Awards') }} <span style="font-weight:400; text-transform:none;">({{ __('hover over image to see name') }})</span>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                        <div>
                            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:6px; font-style:italic;">{{ __('Ribbons') }}</div>
                            @if($ribbons->isEmpty())
                                <div class="hlx-muted" style="font-size:12px;">{{ __('No ribbons earned.') }}</div>
                            @else
                                <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                    @foreach($ribbons as $ribbon)
                                        <img src="{{ asset('hlstatsimg/games/' . $realgame . '/ribbons/' . $ribbon->image) }}"
                                             alt="{{ $ribbon->ribbonName }}" title="{{ $ribbon->ribbonName }}"
                                             style="width:48px; height:48px; cursor:help;"
                                             onerror="this.onerror=null;this.src='{{ asset('hlstatsimg/noimage.gif') }}'">
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div>
                            <div style="font-size:11px; color:var(--text-secondary); margin-bottom:6px; font-style:italic;">{{ __('Global Awards') }}</div>
                            @if($playerGlobalAwards->isEmpty())
                                <div class="hlx-muted" style="font-size:12px;">{{ __('No global awards.') }}</div>
                            @else
                                <div style="max-height:300px; overflow-y:auto; scrollbar-width:thin; scrollbar-color:var(--border) transparent;">
                                <table style="width:100%; border-collapse:collapse; font-size:11px;">
                                    <thead style="position:sticky; top:0; background:var(--bg-surface); z-index:1;">
                                        <tr>
                                            <th style="text-align:left; padding:2px 6px; color:var(--text-secondary); border-bottom:1px solid var(--border);">{{ __('Award') }}</th>
                                            <th style="padding:2px 6px; color:var(--text-secondary); border-bottom:1px solid var(--border);">{{ __('Count') }}</th>
                                            <th style="padding:2px 6px; color:var(--text-secondary); border-bottom:1px solid var(--border);">{{ __('Date') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($playerGlobalAwards as $aw)
                                        <tr style="border-top:1px solid var(--border);">
                                            <td style="padding:3px 6px; color:var(--text-heading);">{{ $aw->name }}</td>
                                            <td style="padding:3px 6px; text-align:center; color:var(--text-heading);">{{ number_format($aw->count) }}</td>
                                            <td style="padding:3px 6px; color:var(--text-secondary);">{{ $aw->awardTime }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Aliases --}}
                <div>
                    <div style="padding:8px 14px 4px; font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border);">
                        {{ __('Aliases') }}
                    </div>
                    @if($aliases->isEmpty())
                        <div class="hlx-muted" style="text-align:center; padding:16px; font-size:12px;">{{ __('No alias data.') }}</div>
                    @else
                        @php $totalKills = max(1, $aliases->sum('kills')); @endphp
                        <table class="hlx-table" style="font-size:11px;">
                            <thead>
                                <tr>
                                    <th style="text-align:center; width:30px;">#</th>
                                    <th>{{ __('Name') }}</th>
                                    <th>{{ __('Time') }}</th>
                                    <th>{{ __('Last Seen') }}</th>
                                    <th>{{ __('Kills') }}</th>
                                    <th>{{ __('Deaths') }}</th>
                                    <th>{{ __('K:D') }}</th>
                                    <th>{{ __('Headshots') }}</th>
                                    <th>HpK</th>
                                    <th>{{ __('Suicides') }}</th>
                                    <th>{{ __('Activity') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($aliases as $i => $alias)
                                @php
                                    $aliasCt   = (int)$alias->connection_time;
                                    $aDays     = (int)floor($aliasCt / 86400);
                                    $aHrs      = (int)floor(($aliasCt % 86400) / 3600);
                                    $aMins     = (int)floor(($aliasCt % 3600) / 60);
                                    $aSecs     = (int)($aliasCt % 60);
                                    $aliasTime = "{$aDays}d " . str_pad($aHrs,2,'0',STR_PAD_LEFT) . ':' . str_pad($aMins,2,'0',STR_PAD_LEFT) . ':' . str_pad($aSecs,2,'0',STR_PAD_LEFT) . 'h';
                                    $aliasKpd  = (int)$alias->deaths > 0 ? number_format($alias->kills / $alias->deaths, 2) : number_format((float)$alias->kills, 2);
                                    $aliasHpk  = (int)$alias->kills > 0 ? number_format($alias->headshots / $alias->kills, 4) : '-';
                                    $aliasAct  = number_format($alias->kills / $totalKills * 100, 1) . '%';
                                @endphp
                                <tr>
                                    <td class="hlx-muted" style="text-align:center;">{{ $i + 1 }}</td>
                                    <td class="hlx-text" style="font-weight:600;">{{ $alias->name }}</td>
                                    <td class="hlx-muted">{{ $aliasTime }}</td>
                                    <td class="hlx-muted">{{ $alias->lastuse ? \Carbon\Carbon::parse($alias->lastuse)->format('Y-m-d H:i:s') : '-' }}</td>
                                    <td class="hlx-text">{{ number_format($alias->kills) }}</td>
                                    <td class="hlx-text">{{ number_format($alias->deaths) }}</td>
                                    <td class="hlx-text">{{ $aliasKpd }}</td>
                                    <td class="hlx-text">{{ number_format($alias->headshots) }}</td>
                                    <td class="hlx-text">{{ $aliasHpk }}</td>
                                    <td class="hlx-text">{{ number_format($alias->suicides) }}</td>
                                    <td class="hlx-text">{{ $aliasAct }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </div>{{-- end Miscellaneous Statistics --}}

        </div>{{-- end General tab --}}

        {{-- ============================================================
             TEAMS & ACTIONS TAB
        ============================================================ --}}
        <div x-show="tab==='actions'" style="padding:12px 14px;">

            {{-- Team Selection --}}
            @if($playerTeams->isNotEmpty())
            <div style="margin-bottom:16px;">
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:0;">
                    {{ __('Team Selection') }} *
                </div>
                <table class="hlx-table">
                    <thead>
                        <tr>
                            <th>{{ __('Team') }}</th>
                            <th>{{ __('Joined') }}</th>
                            <th>%</th>
                            <th style="width:35%;">Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playerTeams as $t)
                        <tr>
                            <td class="hlx-text">{{ $t->name }}</td>
                            <td class="hlx-text">{{ number_format($t->teamcount) }}×</td>
                            <td class="hlx-muted">{{ $t->percent }}%</td>
                            <td>
                                <div style="background:#1a1a2e; border-radius:2px; height:8px; overflow:hidden;">
                                    <div style="background:var(--accent); height:100%; width:{{ min(100,$t->percent) }}%;"></div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Player Actions --}}
            @if($playerActions->isNotEmpty())
            <div style="margin-bottom:16px;">
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:0;">
                    {{ __('Player Actions') }} *
                </div>
                <table class="hlx-table">
                    <thead>
                        <tr>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Earned') }}</th>
                            <th>{{ __('Accumulated Points') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playerActions as $a)
                        <tr>
                            <td class="hlx-text">{{ $a->description }}</td>
                            <td class="hlx-text">{{ number_format($a->obj_count) }}×</td>
                            <td class="hlx-text">{{ number_format($a->obj_bonus) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            {{-- Player-Player Actions --}}
            @if($playerPlayerActions->isNotEmpty())
            <div>
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:0;">
                    {{ __('Player vs Player Actions') }} *
                </div>
                <table class="hlx-table">
                    <thead>
                        <tr>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Earned Against') }}</th>
                            <th>{{ __('Accumulated Points') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playerPlayerActions as $a)
                        <tr>
                            <td class="hlx-text">{{ $a->description }}</td>
                            <td class="hlx-text">{{ number_format($a->obj_count) }}×</td>
                            <td class="hlx-text">{{ number_format($a->obj_bonus) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            @if($playerTeams->isEmpty() && $playerActions->isEmpty() && $playerPlayerActions->isEmpty())
                <div class="hlx-muted" style="text-align:center; padding:20px; font-size:13px;">{{ __('No team & action data available.') }}</div>
            @endif
        </div>

        {{-- ============================================================
             WEAPONS TAB
        ============================================================ --}}
        <div x-show="tab==='weapons'" style="padding:0;">
            @if($topWeapons->isEmpty())
                <div class="hlx-muted" style="text-align:center; padding:20px; font-size:13px;">{{ __('No weapon data available.') }}</div>
            @else
                @php $maxKillPct = $topWeapons->max('kpercent') ?: 1; @endphp
                <table class="hlx-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="text-align:center; width:30px;">#</th>
                            <th style="width:14%;">{{ __('Weapon') }}</th>
                            <th>{{ __('Modifier') }}</th>
                            <th>{{ __('Kills') }}</th>
                            <th>%</th>
                            <th style="width:14%;">{{ __('Ratio') }}</th>
                            <th>{{ __('Headshots') }}</th>
                            <th>%</th>
                            <th style="width:14%;">{{ __('Ratio') }}</th>
                            <th>{{ __('HS:K') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topWeapons as $i => $pw)
                        <tr>
                            <td class="hlx-muted" style="text-align:center;">{{ $i + 1 }}</td>
                            <td class="hlx-text" style="white-space:nowrap;">
                                @php $wImg = 'hlstatsimg/games/' . $realgame . '/weapons/' . strtolower($pw->code) . '.png'; @endphp
                                <img src="{{ asset($wImg) }}" alt="{{ $pw->name ?? $pw->code }}" style="width:32px; height:32px; object-fit:contain; vertical-align:middle; margin-right:6px;" onerror="this.style.display='none'">
                                <a href="{{ route('weapons.show', [$pw->code, 'game' => $player->game]) }}" class="hlx-link">{{ $pw->name ?? $pw->code }}</a>
                            </td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pw->modifier }}</td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($pw->kills) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pw->kpercent }}%</td>
                            <td>
                                <div style="background:var(--bg-surface-alt); border-radius:2px; height:7px; overflow:hidden;">
                                    <div style="background:var(--accent-primary); height:100%; width:{{ $maxKillPct > 0 ? min(100, round($pw->kpercent / $maxKillPct * 100)) : 0 }}%;"></div>
                                </div>
                            </td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($pw->headshots) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pw->hpercent }}%</td>
                            <td>
                                <div style="background:var(--bg-surface-alt); border-radius:2px; height:7px; overflow:hidden;">
                                    <div style="background:#a78bfa; height:100%; width:{{ $topWeapons->max('hpercent') > 0 ? min(100, round($pw->hpercent / $topWeapons->max('hpercent') * 100)) : 0 }}%;"></div>
                                </div>
                            </td>
                            <td class="hlx-text" style="text-align:right;">{{ $pw->hpk }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ============================================================
             MAPS & SERVERS TAB
        ============================================================ --}}
        <div x-show="tab==='maps'" style="padding:0;">

            {{-- Maps --}}
            @if($topMaps->isEmpty())
                <div class="hlx-muted" style="text-align:center; padding:20px; font-size:13px;">{{ __('No map data available.') }}</div>
            @else
                @php $maxMapKillPct = $topMaps->max('kpercent') ?: 1; $maxMapDeathPct = $topMaps->max('dpercent') ?: 1; @endphp
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; padding:6px 12px; border-bottom:1px solid var(--border); background:var(--bg-surface-alt);">
                    {{ __('Map Performance') }} *
                </div>
                <table class="hlx-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="text-align:center; width:30px;">#</th>
                            <th>{{ __('Map') }}</th>
                            <th>{{ __('Kills') }}</th><th>%</th><th style="width:10%;">{{ __('Ratio') }}</th>
                            <th>{{ __('Deaths') }}</th><th>%</th><th style="width:10%;">{{ __('Ratio') }}</th>
                            <th>{{ __('K:D') }}</th>
                            <th>{{ __('Headshots') }}</th><th>%</th>
                            <th>{{ __('HS:K') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topMaps as $i => $pm)
                        <tr>
                            <td class="hlx-muted" style="text-align:center;">{{ $i + 1 }}</td>
                            <td class="hlx-text">{{ $pm->map }}</td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($pm->kills) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pm->kpercent }}%</td>
                            <td><div style="background:#1a1a2e; border-radius:2px; height:7px; overflow:hidden;"><div style="background:var(--accent); height:100%; width:{{ $maxMapKillPct > 0 ? min(100,round($pm->kpercent/$maxMapKillPct*100)) : 0 }}%;"></div></div></td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($pm->deaths) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pm->dpercent }}%</td>
                            <td><div style="background:#1a1a2e; border-radius:2px; height:7px; overflow:hidden;"><div style="background:#f87171; height:100%; width:{{ $maxMapDeathPct > 0 ? min(100,round($pm->dpercent/$maxMapDeathPct*100)) : 0 }}%;"></div></div></td>
                            <td class="hlx-text" style="text-align:right;">{{ $pm->kpd }}</td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($pm->headshots) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $pm->hpercent }}%</td>
                            <td class="hlx-text" style="text-align:right;">{{ $pm->hpk }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Servers --}}
            @if($playerServers->isNotEmpty())
                @php $maxSrvKillPct = $playerServers->max('kpercent') ?: 1; @endphp
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; padding:6px 12px; border-top:2px solid var(--border); border-bottom:1px solid var(--border); background:var(--bg-surface-alt); margin-top:8px;">
                    {{ __('Server Performance') }} *
                </div>
                <table class="hlx-table" style="font-size:12px;">
                    <thead>
                        <tr>
                            <th style="text-align:center; width:30px;">#</th>
                            <th>{{ __('Server') }}</th>
                            <th>{{ __('Kills') }}</th><th>%</th><th style="width:18%;">{{ __('Ratio') }}</th>
                            <th>{{ __('Deaths') }}</th><th>{{ __('K:D') }}</th>
                            <th>{{ __('Headshots') }}</th><th>{{ __('HS%') }}</th>
                            <th>{{ __('HS:K') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($playerServers as $i => $srv)
                        <tr>
                            <td class="hlx-muted" style="text-align:center;">{{ $i + 1 }}</td>
                            <td class="hlx-text" style="display:flex; align-items:center; gap:6px;">
                                <x-ui.game-logo :game="$realgame" :size="16" />
                                {{ $srv->server }}
                            </td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($srv->kills) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $srv->kpercent }}%</td>
                            <td><div style="background:#1a1a2e; border-radius:2px; height:7px; overflow:hidden;"><div style="background:var(--accent); height:100%; width:{{ $maxSrvKillPct > 0 ? min(100,round($srv->kpercent/$maxSrvKillPct*100)) : 0 }}%;"></div></div></td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($srv->deaths) }}</td>
                            <td class="hlx-text" style="text-align:right;">{{ $srv->kpd }}</td>
                            <td class="hlx-text" style="text-align:right;">{{ number_format($srv->headshots) }}</td>
                            <td class="hlx-muted" style="text-align:right;">{{ $srv->hpercent }}%</td>
                            <td class="hlx-text" style="text-align:right;">{{ $srv->hpk }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- ============================================================
             KILLSTATS TAB
        ============================================================ --}}
        <div x-show="tab==='killstats'" style="padding:12px 14px;">

            {{-- Skill evolution chart --}}
            @if(!empty($chartData))
            <div style="margin-bottom:16px;">
                <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:8px;">
                    Skill Evolution (last 30 days)
                </div>
                <x-charts.skill-chart :canvasId="'skillChart'" :labels="$chartLabels" :data="$chartData" height="200px" />
            </div>
            @endif

            @php
                $totalVKills  = max(1, $topVictims->sum('kills'));
                $totalVDeaths = max(1, $topVictims->sum('deaths'));
                $maxVKillPct  = $topVictims->max('kills') ?: 1;
            @endphp

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">

                {{-- Top Victims --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:0;">
                        {{ __('Top Victims') }}
                    </div>
                    @if($topVictims->isEmpty())
                        <div class="hlx-muted" style="text-align:center; padding:16px; font-size:13px;">{{ __('No data') }}</div>
                    @else
                        <table class="hlx-table" style="font-size:11px;">
                            <thead>
                                <tr>
                                    <th>#</th><th>{{ __('Victim') }}</th>
                                    <th>{{ __('Kills') }}</th><th>%</th><th style="width:12%;">{{ __('Ratio') }}</th>
                                    <th>{{ __('Deaths') }}</th><th>{{ __('K:D') }}</th>
                                    <th>{{ __('Headshots') }}</th><th>{{ __('HS:K') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topVictims as $i => $v)
                                @php
                                    $vKpct = round($v->kills / $totalVKills * 100, 1);
                                @endphp
                                <tr>
                                    <td class="hlx-muted" style="text-align:center;">{{ $i+1 }}</td>
                                    <td>
                                        <x-ui.flag :code="$v->flag ?? ''" />
                                        <a href="{{ route('players.show', $v->playerId) }}" class="hlx-link">{{ $v->lastName }}</a>
                                    </td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($v->kills) }}</td>
                                    <td class="hlx-muted" style="text-align:right;">{{ $vKpct }}%</td>
                                    <td><div style="background:#1a1a2e; border-radius:2px; height:6px; overflow:hidden;"><div style="background:var(--accent); height:100%; width:{{ $maxVKillPct > 0 ? min(100,round($v->kills/$maxVKillPct*100)) : 0 }}%;"></div></div></td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($v->deaths) }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ $v->kpd }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($v->headshots) }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ $v->kills > 0 ? round($v->headshots / $v->kills, 2) : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                {{-- Top Killers --}}
                <div>
                    <div style="font-size:11px; font-weight:600; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.06em; border-bottom:1px solid var(--border); padding-bottom:4px; margin-bottom:0;">
                        {{ __('Top Killers') }}
                    </div>
                    @if($topKillers->isEmpty())
                        <div class="hlx-muted" style="text-align:center; padding:16px; font-size:13px;">{{ __('No data') }}</div>
                    @else
                        @php $maxKKillPct = $topKillers->max('kills') ?: 1; @endphp
                        <table class="hlx-table" style="font-size:11px;">
                            <thead>
                                <tr>
                                    <th>#</th><th>{{ __('Killer') }}</th>
                                    <th>{{ __('Kills') }}</th><th>%</th><th style="width:12%;">{{ __('Ratio') }}</th>
                                    <th>{{ __('Deaths') }}</th><th>{{ __('K:D') }}</th>
                                    <th>{{ __('Headshots') }}</th><th>{{ __('HS:K') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($topKillers as $i => $k)
                                @php
                                    $kKpct = round($k->kills / max(1,$topKillers->sum('kills')) * 100, 1);
                                @endphp
                                <tr>
                                    <td class="hlx-muted" style="text-align:center;">{{ $i+1 }}</td>
                                    <td>
                                        <x-ui.flag :code="$k->flag ?? ''" />
                                        <a href="{{ route('players.show', $k->playerId) }}" class="hlx-link">{{ $k->lastName }}</a>
                                    </td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($k->kills) }}</td>
                                    <td class="hlx-muted" style="text-align:right;">{{ $kKpct }}%</td>
                                    <td><div style="background:#1a1a2e; border-radius:2px; height:6px; overflow:hidden;"><div style="background:#f87171; height:100%; width:{{ $maxKKillPct > 0 ? min(100,round($k->kills/$maxKKillPct*100)) : 0 }}%;"></div></div></td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($k->deaths) }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ $k->kpd }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ number_format($k->headshots) }}</td>
                                    <td class="hlx-text" style="text-align:right;">{{ $k->kills > 0 ? round($k->headshots / $k->kills, 2) : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

            </div>{{-- end grid --}}
        </div>{{-- end Killstats tab --}}

    </div>{{-- end x-data tabs --}}
</div>{{-- end Player Information --}}

</x-layouts.app>
