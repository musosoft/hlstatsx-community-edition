@props(['game' => null, 'active' => null])

@php
    $gameCode     = is_object($game) ? $game->code : ($game ?? '');
    $realGameCode = $gameCode
        ? (is_object($game)
            ? ($game->realgame ?? $game->code)
            : (\Illuminate\Support\Facades\DB::table('hlstats_Games')->where('code', $gameCode)->value('realgame') ?? $gameCode))
        : '';
    $tabs = [
        'servers'   => ['label' => __('Servers'),   'route' => 'game.show',         'params' => [$gameCode], 'section' => 'servers'],
        'chat'      => ['label' => __('Chat'),      'route' => 'chat.index',        'params' => [],          'query'   => ['game' => $gameCode]],
        'players'   => ['label' => __('Players'),   'route' => 'players.index',     'params' => [],          'query'   => ['game' => $gameCode]],
        'clans'     => ['label' => __('Clans'),     'route' => 'clans.index',       'params' => [],          'query'   => ['game' => $gameCode]],
        'countries' => ['label' => __('Countries'), 'route' => 'countries.index',   'params' => [],          'query'   => ['game' => $gameCode]],
        'awards'    => ['label' => __('Awards'),    'route' => 'awards.index',      'params' => [],          'query'   => ['game' => $gameCode]],
        'actions'   => ['label' => __('Actions'),   'route' => 'actions.index',     'params' => [],          'query'   => ['game' => $gameCode]],
        'weapons'   => ['label' => __('Weapons'),   'route' => 'weapons.index',     'params' => [],          'query'   => ['game' => $gameCode]],
        'maps'      => ['label' => __('Maps'),      'route' => 'maps.index',        'params' => [],          'query'   => ['game' => $gameCode]],
        'bans'      => ['label' => __('Bans'),      'route' => 'bans.index',        'params' => [],          'query'   => ['game' => $gameCode]],
    ];
@endphp

<div style="background-color:var(--bg-surface); border-bottom:1px solid var(--border); padding:8px 16px;">
    <div class="hlx-pane-content">
        <div style="display:flex; gap:6px; flex-wrap:wrap; justify-content:center; align-items:center;">
            @if($gameCode)
                <x-ui.game-logo :game="$realGameCode" :size="20" style="margin-right:4px;" />
            @endif
            @foreach($tabs as $key => $tab)
                @php
                    $url = isset($tab['query'])
                        ? route($tab['route'], array_merge($tab['params'], ['query' => $tab['query']]))
                        : route($tab['route'], $tab['params']);

                    // Build URL with query string properly
                    if (isset($tab['query'])) {
                        $url = route($tab['route'], $tab['params']) . '?' . http_build_query($tab['query']);
                    }

                    $isActive = $active === $key;
                @endphp
                <a href="{{ $url }}" @class(['hlx-nav-tab', 'active' => $isActive])>
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</div>
