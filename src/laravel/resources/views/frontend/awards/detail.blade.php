<x-layouts.app
    :title="$award->name . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Awards' => route('awards.index', ['game' => $game]), $award->name => null]"
    :gameNav="$game"
    activeTab="awards">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <img src="{{ asset('hlstatsimg/games/'.$realgame.'/dawards/'.strtolower($award->awardType.'_'.$award->code).'.png') }}"
         alt="{{ $award->name }}" style="width:64px;height:64px;object-fit:contain;"
         onerror="this.onerror=null;this.src='{{ asset('hlstatsimg/games/'.$realgame.'/dawards/w_standard.png') }}'">
    <div>
        <div style="font-size:20px; font-weight:700; color:var(--text-heading);">{{ $award->name }}</div>
        <div class="hlx-muted" style="font-size:12px;">{{ $award->verb }}</div>
    </div>
</div>

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Award History')" />
    @if($history->isEmpty())
        <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No award history found.') }}</div>
    @else
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th>{{ __('Date') }}</th>
            <th>{{ __('Player') }}</th>
            <th style="text-align:right;">{{ __('Count') }}</th>
        </tr></thead>
        <tbody>
            @foreach($history as $row)
            <tr>
                <td class="hlx-muted">{{ \Carbon\Carbon::parse($row->awardTime)->locale(app()->getLocale())->translatedFormat('d F Y') }}</td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;">
                        <x-ui.flag :code="$row->flag ?? ''" />
                        <a href="{{ route('players.show', $row->playerId) }}" class="hlx-link">{{ $row->lastName }}</a>
                    </span>
                </td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($row->count) }} {{ $award->verb }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <x-ui.pagination :paginator="$history" />
    @endif
</div>

</x-layouts.app>
