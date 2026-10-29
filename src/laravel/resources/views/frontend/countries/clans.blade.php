<x-layouts.app
    :title="__('Country Clans') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Countries' => route('countries.index', ['game' => $game]), __('Country Clans') => null]"
    :gameNav="$game"
    activeTab="countries">

@if(empty($countryClanRows))
    <div class="hlx-muted" style="padding:40px; text-align:center;">{{ __('No data found.') }}</div>
@else
<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th style="width:30px;">#</th>
            <th>{{ __('Country') }}</th>
            <th style="text-align:right;">{{ __('Clans') }}</th>
            <th style="text-align:right;">{{ __('Players') }}</th>
            <th style="text-align:right;">{{ __('Kills') }}</th>
            <th style="text-align:right;">{{ __('Deaths') }}</th>
        </tr></thead>
        <tbody>
            @foreach($countryClanRows as $i => $row)
            <tr>
                <td class="hlx-muted">{{ $i + 1 }}</td>
                <td>
                    <a href="{{ route('countries.clan-detail', [$row->flag, 'game' => $game]) }}" class="hlx-link" style="display:inline-flex;align-items:center;gap:6px;">
                        <x-ui.flag :code="$row->flag ?? ''" />
                        {{ $row->country ?: ($row->flag ?: __('Unknown')) }}
                    </a>
                </td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($row->clan_count) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($row->player_count) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($row->kills) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($row->deaths) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

</x-layouts.app>
