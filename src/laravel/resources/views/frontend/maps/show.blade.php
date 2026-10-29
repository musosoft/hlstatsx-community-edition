<x-layouts.app
    :title="$map . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Map Statistics' => route('maps.index', ['game' => $game]), $map => null]"
    :gameNav="$game"
    activeTab="maps">

    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:1rem;">
        <p class="hlx-muted">
            <strong>{{ $map }}</strong>: From a total of
            <strong>{{ number_format($totals->total_kills ?? 0) }}</strong> kills
        </p>
        <a href="{{ route('maps.index', ['game' => $game]) }}" class="hlx-link" style="font-size:var(--font-size-sm);">
            &larr; Back to Map Statistics
        </a>
    </div>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Player') }}</th>
                    <th style="width:160px; text-align:right;">{{ __('Kills') }} on {{ $map }}</th>
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
