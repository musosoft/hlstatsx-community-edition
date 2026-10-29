<x-layouts.app
    :title="__('Country Rankings') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Countries' => null]"
    :gameNav="$game"
    activeTab="countries">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <div style="background:var(--bg-surface-alt); border-bottom:1px solid var(--border); padding:6px 12px; font-size:12px; font-weight:700; color:var(--text-heading); letter-spacing:.04em;">
            {{ __('Country Rankings') }}
        </div>
        <table class="hlx-table" style="font-size:12px;">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Country') }}</th>
                    <th style="width:90px; text-align:right;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'avg_skill']) }}" style="color:{{ $sort === 'avg_skill' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Avg. Points') }}</a>
                    </th>
                    <th style="width:80px; text-align:right;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'members']) }}" style="color:{{ $sort === 'members' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Members') }}{{ $sort === 'members' ? ' ▼' : '' }}</a>
                    </th>
                    <th style="width:180px;">{{ __('Activity') }}</th>
                    <th style="width:120px; text-align:right;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'connection_time']) }}" style="color:{{ $sort === 'connection_time' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Connection Time') }}</a>
                    </th>
                    <th style="width:70px; text-align:right;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'kills']) }}" style="color:{{ $sort === 'kills' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Kills') }}</a>
                    </th>
                    <th style="width:70px; text-align:right;">
                        <a href="{{ request()->fullUrlWithQuery(['sort' => 'deaths']) }}" style="color:{{ $sort === 'deaths' ? 'var(--link)' : 'var(--text-secondary)' }}; text-decoration:none;">{{ __('Deaths') }}</a>
                    </th>
                    <th style="width:55px; text-align:right;">{{ __('K:D') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $countries->firstItem(); @endphp
                @forelse($countries as $row)
                @php
                    $secs = (int) $row->connection_time;
                    $days = floor($secs / 86400);
                    $h    = floor(($secs % 86400) / 3600);
                    $m    = floor(($secs % 3600) / 60);
                    $s    = $secs % 60;
                    $timeStr = sprintf('%dd %02d:%02d:%02dh', $days, $h, $m, $s);
                    $activity = max(0, min(100, (int) $row->avg_activity));
                    $actColor = $activity >= 70 ? '#4ade80' : ($activity >= 35 ? '#facc15' : '#f87171');
                @endphp
                <tr>
                    <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                    <td>
                        <span style="display:inline-flex; align-items:center; gap:5px;">
                            <x-ui.flag :code="$row->flagCode ?? ''" />
                            <a href="{{ route('players.index', ['game' => $game, 'country' => $row->country]) }}" class="hlx-link">
                                {{ $row->country ?: '—' }}
                            </a>
                        </span>
                    </td>
                    <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">
                        {{ number_format($row->avg_skill) }} <span class="hlx-muted" style="font-size:10px;">◆</span>
                    </td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($row->members) }}</td>
                    <td>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <div style="flex:1; background:#1a1a2e; border-radius:2px; height:10px; overflow:hidden;">
                                <div style="background:{{ $actColor }}; height:100%; width:{{ $activity }}%;"></div>
                            </div>
                        </div>
                    </td>
                    <td class="hlx-muted" style="text-align:right; font-family:var(--font-family-mono); font-size:11px;">{{ $timeStr }}</td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($row->kills) }}</td>
                    <td class="hlx-text" style="text-align:right;">{{ number_format($row->deaths) }}</td>
                    <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ $row->kd }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="hlx-muted" style="text-align:center; padding:20px;">No country data found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$countries" />

</x-layouts.app>
