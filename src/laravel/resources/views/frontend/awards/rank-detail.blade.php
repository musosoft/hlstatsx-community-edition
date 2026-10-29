<x-layouts.app
    :title="$rank->rankName . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Awards' => route('awards.index', ['game' => $game, 'tab' => 'ranks']), $rank->rankName => null]"
    :gameNav="$game"
    activeTab="awards">

<div style="margin-bottom:16px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <img src="{{ asset('hlstatsimg/ranks/'.$rank->image.'.png') }}"
         alt="{{ $rank->rankName }}" style="width:56px;height:56px;object-fit:contain;"
         onerror="this.onerror=null;this.src='{{ asset('hlstatsimg/ranks/default.png') }}'">
    <div>
        <div style="font-size:20px; font-weight:700; color:var(--text-heading);">{{ $rank->rankName }}</div>
        <div class="hlx-muted" style="font-size:12px;">{{ number_format($rank->minKills) }} – {{ number_format($rank->maxKills) }} {{ __('kills') }}</div>
    </div>
</div>

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Players with this rank') . ' (' . $players->total() . ')'" />
    @if($players->isEmpty())
        <div class="hlx-muted" style="padding:20px;text-align:center;">{{ __('No players found.') }}</div>
    @else
    <table class="hlx-table" style="font-size:12px;">
        <thead><tr>
            <th>#</th>
            <th>{{ __('Player') }}</th>
            <th style="text-align:right;">{{ __('Kills') }}</th>
            <th style="text-align:right;">{{ __('Skill') }}</th>
        </tr></thead>
        <tbody>
            @php $i = $players->firstItem(); @endphp
            @foreach($players as $player)
            <tr>
                <td class="hlx-muted">{{ $i++ }}</td>
                <td>
                    <span style="display:inline-flex;align-items:center;gap:5px;">
                        <x-ui.flag :code="$player->flag ?? ''" />
                        <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">{{ $player->lastName }}</a>
                    </span>
                </td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($player->kills) }}</td>
                <td class="hlx-text" style="text-align:right;">{{ number_format($player->skill) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <x-ui.pagination :paginator="$players" />
    @endif
</div>

</x-layouts.app>
