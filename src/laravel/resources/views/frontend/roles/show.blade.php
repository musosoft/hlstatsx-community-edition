<x-layouts.app
    :title="$role->name . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Roles' => route('roles.index', ['game' => $game]), $role->name => null]"
    :gameNav="$game"
    activeTab="roles">

@php
    $img      = asset('hlstatsimg/games/' . $realgame . '/roles/' . strtolower($role->code) . '.png');
    $fallback = asset('hlstatsimg/games/' . $realgame . '/roles/default.png');
@endphp

<div style="margin-bottom:16px; display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
    <img src="{{ $img }}" alt="{{ $role->name }}" style="width:64px;height:64px;object-fit:contain;"
         onerror="this.onerror=null;this.src='{{ $fallback }}'">
    <div>
        <div style="font-size:20px; font-weight:700; color:var(--text-heading);">{{ $role->name }}</div>
        <div class="hlx-muted" style="font-size:12px;">{{ $role->code }}</div>
    </div>
</div>

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
    <x-ui.section-title :title="__('Top Players')" />
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
            @foreach($players as $i => $player)
            <tr>
                <td class="hlx-muted">{{ $i + 1 }}</td>
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
    @endif
</div>

</x-layouts.app>
