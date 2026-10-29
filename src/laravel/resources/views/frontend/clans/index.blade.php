<x-layouts.app
    :title="__('Clans') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Clans' => null]"
    :gameNav="$game"
    activeTab="clans">

    @php
        function fmtTime(int $secs): string {
            $d = floor($secs / 86400);
            $h = floor(($secs % 86400) / 3600);
            $m = floor(($secs % 3600) / 60);
            return sprintf('%dd %02d:%02dh', $d, $h, $m);
        }
    @endphp

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'name', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Clan') }}</a>
                    </th>
                    <th style="width:90px;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'tag', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Tag') }}</a>
                    </th>
                    <th style="width:80px; text-align:right;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'avg_skill', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Avg Points') }}</a>
                    </th>
                    <th style="width:70px; text-align:right;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'members_count', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Members') }}</a>
                    </th>
                    <th style="width:120px;">{{ __('Activity') }}</th>
                    <th style="width:100px; text-align:right;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'total_connection_time', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Connection Time') }}</a>
                    </th>
                    <th style="width:75px; text-align:right;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'kills', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Kills') }}</a>
                    </th>
                    <th style="width:75px; text-align:right;">
                        <a href="{{ route('clans.index', ['game' => $game, 'sort' => 'deaths', 'minmembers' => $minMembers]) }}" class="hlx-link" style="color:inherit;">{{ __('Deaths') }}</a>
                    </th>
                    <th style="width:55px; text-align:right;">{{ __('K:D') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $clans->firstItem(); @endphp
                @forelse($clans as $clan)
                    @php
                        $activity = (float) ($clan->avg_activity ?? 0);
                        $actColor = $activity >= 66 ? 'var(--accent-success, #4ade80)'
                                  : ($activity >= 33 ? 'var(--accent-warning, #facc15)'
                                  : 'var(--accent-danger, #f87171)');
                    @endphp
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        <td>
                            <a href="{{ route('clans.show', $clan->clanId) }}" class="hlx-link">{{ $clan->name }}</a>
                        </td>
                        <td class="hlx-text">{{ $clan->tag }}</td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ number_format($clan->avg_skill ?? 0) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ $clan->members_count ?? 0 }}</td>
                        <td>
                            <div style="background:var(--bg-surface-alt); border-radius:4px; height:8px; width:100%; overflow:hidden;">
                                <div style="width:{{ min(100, $activity) }}%; height:100%; background:{{ $actColor }}; border-radius:4px;"></div>
                            </div>
                        </td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            {{ fmtTime((int)($clan->total_connection_time ?? 0)) }}
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($clan->kills ?? 0) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($clan->deaths ?? 0) }}</td>
                        <td class="hlx-text" style="text-align:right;">
                            @php $d = $clan->deaths ?? 0; $k = $clan->kills ?? 0; @endphp
                            {{ $d > 0 ? round($k / $d, 2) : $k }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No clans found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <form method="get" action="{{ route('clans.index') }}" style="display:flex; align-items:center; gap:8px; margin-top:12px;">
        @if($game !== '')<input type="hidden" name="game" value="{{ $game }}">@endif
        <label for="minmembers" class="hlx-muted">{{ __('Show clans with at least') }}</label>
        <input id="minmembers" name="minmembers" type="number" min="1" max="99" value="{{ $minMembers }}" required>
        <span class="hlx-muted">{{ __('members') }}</span>
        <button type="submit" class="hlx-button">{{ __('Apply') }}</button>
    </form>

    <x-ui.pagination :paginator="$clans" />

</x-layouts.app>
