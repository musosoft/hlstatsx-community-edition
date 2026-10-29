<x-layouts.app
    :title="__('Weapon Statistics') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Weapon Statistics' => null]"
    :gameNav="$game"
    activeTab="weapons">

    <p class="hlx-muted" style="margin-bottom:1rem;">
        From a total of <strong>{{ number_format($totalKills) }}</strong> kills
        with <strong>{{ number_format($totalHeadshots) }}</strong> headshots
    </p>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Weapon') }}</th>
                    <th style="width:65px; text-align:right;">{{ __('Modifier') }}</th>
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
                @php $rank = $weapons->firstItem(); @endphp
                @forelse($weapons as $w)
                    @php
                        $kColor = $w->kpercent >= 50 ? '#facc15'
                                : ($w->kpercent >= 25 ? '#fb923c' : '#f87171');
                        $hColor = $w->hpercent >= 50 ? '#facc15'
                                : ($w->hpercent >= 25 ? '#fb923c' : '#f87171');
                    @endphp
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        @php $wImgPath = 'hlstatsimg/games/' . $realgame . '/weapons/' . strtolower($w->code) . '.png'; @endphp
                        <td style="text-align:center;">
                            <a href="{{ route('weapons.show', [$w->code, 'game' => $game]) }}" class="hlx-link">
                                <img src="{{ asset($wImgPath) }}" alt="{{ $w->name }}" style="width:auto; height:56px; max-width:140px; object-fit:contain;"
                                     onerror="this.outerHTML='<span style=\'font-weight:600;\'>{{ addslashes($w->name) }}</span>'">
                            </a>
                        </td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ number_format($w->modifier, 2) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($w->kills) }}</td>
                        <td class="hlx-muted" style="text-align:right;">{{ $w->kpercent }}%</td>
                        <td>
                            <div style="background:var(--bg-surface-alt); border-radius:3px; height:8px; overflow:hidden;">
                                <div style="width:{{ min(100, $w->kpercent) }}%; height:100%; background:{{ $kColor }}; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($w->headshots) }}</td>
                        <td class="hlx-muted" style="text-align:right;">{{ $w->hpercent }}%</td>
                        <td>
                            <div style="background:var(--bg-surface-alt); border-radius:3px; height:8px; overflow:hidden;">
                                <div style="width:{{ min(100, $w->hpercent) }}%; height:100%; background:{{ $hColor }}; border-radius:3px;"></div>
                            </div>
                        </td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ $w->hpk }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No weapons found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$weapons" />

</x-layouts.app>
