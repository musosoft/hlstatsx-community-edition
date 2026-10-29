<x-layouts.app
    :title="__('Player Rankings') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Players' => null]"
    :gameNav="$game"
    activeTab="players">

    {{-- Country filter banner --}}
    @if(!empty($country))
    <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:8px 14px; margin-bottom:10px; display:flex; align-items:center; justify-content:space-between;">
        <span style="font-size:13px; color:var(--text-heading);">
            Players from <strong>{{ $country }}</strong>
        </span>
        <a href="{{ route('players.index', array_filter(['game' => $game, 'sort' => $sort])) }}" class="hlx-muted" style="font-size:12px; text-decoration:none;">✕ Clear filter</a>
    </div>
    @endif

    {{-- Search + View controls --}}
    <form method="GET" action="{{ route('players.index') }}" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <input type="hidden" name="game" value="{{ $game }}">

        <div style="display:flex; align-items:center; gap:8px;">
            <span class="hlx-muted" style="font-size:var(--font-size-sm);">&gt;&gt; {{ __('Find a player') }}:</span>
            <input type="text" name="search" value="{{ $search }}"
                   style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:3px 8px; font-size:var(--font-size-sm); width:180px;"
                   placeholder="{{ __('Player name...') }}">
            <button type="submit" class="hlx-btn-green">{{ __('Search') }}</button>
        </div>

        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span class="hlx-muted" style="font-size:var(--font-size-sm);">&gt;&gt; {{ __('Period') }}:</span>
            <select name="period"
                    style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:3px 6px; font-size:var(--font-size-sm);">
                <option value="0" @selected(($period ?? 0) == 0)>{{ __('Global') }}</option>
                <option value="1" @selected(($period ?? 0) == 1)>{{ __('Yesterday') }}</option>
                <option value="2" @selected(($period ?? 0) == 2)>{{ __('Last Weekend') }}</option>
                <option value="3" @selected(($period ?? 0) == 3)>{{ __('Last 7 Days') }}</option>
                <option value="4" @selected(($period ?? 0) == 4)>{{ __('Last 28 Days') }}</option>
            </select>

            <span class="hlx-muted" style="font-size:var(--font-size-sm);">&gt;&gt; {{ __('Ranking View') }}:</span>
            <select name="sort"
                    style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:3px 6px; font-size:var(--font-size-sm);">
                <option value="skill"           @selected($sort === 'skill')>{{ __('By Skill') }}</option>
                <option value="kills"           @selected($sort === 'kills')>{{ __('By Kills') }}</option>
                <option value="deaths"          @selected($sort === 'deaths')>{{ __('By Deaths') }}</option>
                <option value="headshots"       @selected($sort === 'headshots')>{{ __('By Headshots') }}</option>
                <option value="kd_ratio"        @selected($sort === 'kd_ratio')>{{ __('By K/D') }}</option>
                <option value="hs_percent"      @selected($sort === 'hs_percent')>{{ __('By HS%') }}</option>
                <option value="accuracy"        @selected($sort === 'accuracy')>{{ __('By Accuracy') }}</option>
                <option value="connection_time" @selected($sort === 'connection_time')>{{ __('By Play Time') }}</option>
            </select>
            <button type="submit" class="hlx-btn-green">{{ __('View') }}</button>
        </div>
    </form>

    {{-- Player locations map --}}
    @if(!empty($playerMarkers))
        <div class="hlx-surface" style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
            <x-ui.section-title title="{{ __('Player Locations') }} ({{ count($playerMarkers) }})" />
            <div style="padding:8px;">
                <x-maps.world-map
                    containerId="playersMap"
                    :markers="[]"
                    :playerMarkers="$playerMarkers"
                    :tileUrl="$tileUrl"
                    height="280px"
                />
            </div>
        </div>
    @endif

    {{-- Players table --}}
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th style="width:280px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'lastName']) }}" class="hlx-muted" style="text-decoration:none;">{{ __('Player') }}</a>
                    </th>
                    <th style="width:70px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'skill']) }}" style="color:{{ $sort === 'skill' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Points') }}</a>
                    </th>
                    <th style="width:150px;">{{ __('Activity') }}</th>
                    <th style="width:110px;">{{ __('Conn. Time') }}</th>
                    <th style="width:60px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'kills']) }}" style="color:{{ $sort === 'kills' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Kills') }}</a>
                    </th>
                    <th style="width:60px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'deaths']) }}" style="color:{{ $sort === 'deaths' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Deaths') }}</a>
                    </th>
                    <th style="width:50px;">{{ __('K:D') }}</th>
                    <th style="width:75px;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'headshots']) }}" style="color:{{ $sort === 'headshots' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Headshots') }}</a>
                    </th>
                    <th style="width:55px;">{{ __('HS%') }}</th>
                    <th style="width:75px;">{{ __('Accuracy') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $players->firstItem(); @endphp
                @forelse($players as $player)
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        <td>
                            <x-ui.player-link :player="$player" />
                        </td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">
                            {{ number_format($player->skill) }}
                        </td>
                        <td class="hlx-activity-cell">
                            <x-ui.activity-bar :score="$player->skill" :max="$maxSkill" />
                        </td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            @php
                                $secs = (int)$player->connection_time;
                                $days = floor($secs / 86400);
                                $h    = floor(($secs % 86400) / 3600);
                                $m    = floor(($secs % 3600) / 60);
                                $s    = $secs % 60;
                            @endphp
                            {{ $days }}d {{ str_pad($h, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($m, 2, '0', STR_PAD_LEFT) }}:{{ str_pad($s, 2, '0', STR_PAD_LEFT) }}h
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($player->kills) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($player->deaths) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ $player->kd_ratio }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($player->headshots) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ $player->hs_percent }}%</td>
                        <td class="hlx-text" style="text-align:right;">{{ $player->accuracy }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No players found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <x-ui.pagination :paginator="$players" />

</x-layouts.app>
