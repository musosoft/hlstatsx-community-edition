<x-layouts.app
    :title="($action->description ?? 'Action Detail') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Action Statistics' => route('actions.index', ['game' => $game]), $action->description => null]"
    :gameNav="$game"
    activeTab="actions">

    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:1rem;">
        <p class="hlx-muted">
            <strong>{{ $action->description }}</strong>
            from a total of <strong>{{ number_format($total) }}</strong> achievements
        </p>
        <a href="{{ route('actions.index', ['game' => $game]) }}" class="hlx-link" style="font-size:var(--font-size-sm);">
            &larr; Back to Action Statistics
        </a>
    </div>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Player') }}</th>
                    <th style="width:100px; text-align:right;">{{ __('Achieved') }}</th>
                    <th style="width:130px; text-align:right;">{{ __('Skill Bonus Total') }}</th>
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
                        <td class="hlx-text" style="text-align:right;">{{ number_format($row->achieved) }}</td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($row->skill_bonus) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No players found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$players" />

</x-layouts.app>
