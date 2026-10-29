<x-layouts.app
    :title="($country->country ?: $flag) . ' — ' . __('Country Clans') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Countries' => route('countries.index', ['game' => $game]), __('Country Clans') => route('countries.clans', ['game' => $game]), $country->country ?: $flag => null]"
    :gameNav="$game"
    activeTab="countries">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:10px;">
    <x-ui.flag :code="$flag" size="lg" />
    <div style="font-size:20px; font-weight:700; color:var(--text-heading);">{{ $country->country ?: $flag }}</div>
</div>

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Clans') . ' (' . $clans->total() . ')'" />
    @if($clans->isEmpty())
        <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No clans found.') }}</div>
    @else
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th>#</th>
            <th>{{ __('Clan') }}</th>
            <th style="text-align:right;">{{ __('Members') }}</th>
            <th style="text-align:right;">{{ __('Kills') }}</th>
            <th style="text-align:right;">{{ __('Deaths') }}</th>
            <th style="text-align:right;">{{ __('K/D') }}</th>
        </tr></thead>
        <tbody>
            @php $i = $clans->firstItem(); @endphp
            @foreach($clans as $clan)
            <tr>
                <td class="hlx-muted">{{ $i++ }}</td>
                <td>
                    <a href="{{ route('clans.show', [$clan->clanId, 'game' => $game]) }}" class="hlx-link">
                        @if($clan->tag)<span style="color:var(--accent-primary); margin-right:4px;">[{{ $clan->tag }}]</span>@endif
                        {{ $clan->name }}
                    </a>
                </td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($clan->member_count) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($clan->kills) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($clan->deaths) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ $clan->deaths > 0 ? round($clan->kills / $clan->deaths, 2) : $clan->kills }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <x-ui.pagination :paginator="$clans" />
    @endif
</div>

</x-layouts.app>
