<x-layouts.app
    :title="($weapon->name ?? $weapon->code) . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Weapon Statistics' => route('weapons.index', ['game' => $game]), $weapon->name => null]"
    :gameNav="$game"
    activeTab="weapons">

    @php $wImgPath = 'hlstatsimg/games/' . $realgame . '/weapons/' . strtolower($weapon->code) . '.png'; @endphp
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="{{ asset($wImgPath) }}" alt="{{ $weapon->name }}" style="width:64px; height:64px; object-fit:contain;" onerror="this.style.display='none'">
            <p class="hlx-muted" style="margin:0;">
                From a total of <strong>{{ number_format($totals->total_kills ?? 0) }}</strong> kills
                with <strong>{{ number_format($totals->total_headshots ?? 0) }}</strong> headshots
            </p>
        </div>
        <a href="{{ route('weapons.index', ['game' => $game]) }}" class="hlx-link" style="font-size:var(--font-size-sm); white-space:nowrap;">
            &larr; Back to Weapon Statistics
        </a>
    </div>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Player') }}</th>
                    <th style="width:120px; text-align:right;">{{ $weapon->name }} kills</th>
                    <th style="width:100px; text-align:right;">{{ __('Headshots') }}</th>
                    <th style="width:70px; text-align:right;">{{ __('Hpk') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $players->firstItem(); @endphp
                @forelse($players as $row)
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        <td>
                            <x-ui.flag :code="$row->flag ?? ''" />
                            <a href="{{ route('players.show', $row->playerId) }}" class="hlx-link">
                                {{ $row->lastName }}
                            </a>
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($row->frags) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($row->headshots) }}</td>
                        <td class="hlx-text" style="text-align:right; font-family:var(--font-family-mono);">{{ $row->hpk }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No players found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$players" />

</x-layouts.app>
