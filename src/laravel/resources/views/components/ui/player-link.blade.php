@props(['player'])

<span style="display:inline-flex; align-items:center; gap:4px;">
    @if(!empty($player->flag))
        <x-ui.flag :code="$player->flag" />
    @elseif(!empty($player->country))
        <x-ui.flag :code="$player->country" />
    @endif
    <a href="{{ route('players.show', $player->playerId) }}" class="hlx-link">
        {{ $player->lastName ?? 'Unknown' }}
    </a>
</span>
