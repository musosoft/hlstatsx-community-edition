<x-layouts.app
    :title="__('Map Statistics') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Map Statistics' => null]"
    :gameNav="$game"
    activeTab="maps">

    <p class="hlx-muted" style="margin-bottom:1rem;">
        From a total of <strong>{{ number_format($totalKills) }}</strong> kills
        with <strong>{{ number_format($totalHeadshots) }}</strong> headshots
    </p>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Map') }}</th>
                    <th style="width:75px; text-align:right;">{{ __('Kills') }}</th>
                    <th style="width:55px; text-align:right;">%</th>
                    <th style="width:160px;">{{ __('Ratio') }}</th>
                    <th style="width:85px; text-align:right;">{{ __('Headshots') }}</th>
                    <th style="width:55px; text-align:right;">%</th>
                    <th style="width:160px;">{{ __('Ratio') }}</th>
                    <th style="width:55px; text-align:right;">{{ __('HS:K') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $maps->firstItem(); @endphp
                @forelse($maps as $m)
                    @php
                        $kColor = $m->kpercent >= 50 ? '#facc15'
                                : ($m->kpercent >= 25 ? '#fb923c' : '#f87171');
                        $hColor = $m->hpercent >= 50 ? '#facc15'
                                : ($m->hpercent >= 25 ? '#fb923c' : '#f87171');
                    @endphp
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        <td class="hlx-text" style="font-weight:600;">
                            <a href="{{ route('maps.show', [$m->map, 'game' => $game]) }}" class="hlx-link">{{ $m->map }}</a>
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($m->kills) }}</td>
                        <td class="hlx-muted" style="text-align:right;">{{ $m->kpercent }}%</td>
                        <td>
                            <div style="background:var(--bg-surface-alt); border-radius:3px; height:8px; overflow:hidden;">
                                <div style="width:{{ min(100, $m->kpercent) }}%; height:100%; background:{{ $kColor }}; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($m->headshots) }}</td>
                        <td class="hlx-muted" style="text-align:right;">{{ $m->hpercent }}%</td>
                        <td>
                            <div style="background:var(--bg-surface-alt); border-radius:3px; height:8px; overflow:hidden;">
                                <div style="width:{{ min(100, $m->hpercent) }}%; height:100%; background:{{ $hColor }}; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ $m->hpk }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No maps found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$maps" />

</x-layouts.app>
