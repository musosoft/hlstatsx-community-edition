<x-layouts.app
    :title="__('Action Statistics') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Action Statistics' => null]"
    :gameNav="$game"
    activeTab="actions">

    <p class="hlx-muted" style="margin-bottom:1rem;">
        From a total of <strong>{{ number_format($total) }}</strong> earned actions
    </p>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px; text-align:center;">{{ __('Rank') }}</th>
                    <th>{{ __('Actions') }}</th>
                    <th style="width:130px; text-align:right;">{{ __('Earned') }}</th>
                    <th style="width:80px; text-align:right;">{{ __('Reward') }}</th>
                </tr>
            </thead>
            <tbody>
                @php $rank = $actions->firstItem(); @endphp
                @forelse($actions as $row)
                    <tr>
                        <td style="text-align:center;" class="hlx-muted">{{ $rank++ }}</td>
                        <td>
                            <a href="{{ route('actions.show', [$row->id, 'game' => $game]) }}" class="hlx-link">
                                {{ $row->description }}
                            </a>
                        </td>
                        <td class="hlx-text" style="text-align:right;">{{ number_format($row->count) }} times</td>
                        <td class="hlx-text" style="text-align:right;">{{ $row->reward_player }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No actions found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <x-ui.pagination :paginator="$actions" />

</x-layouts.app>
