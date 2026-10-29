<x-layouts.ingame title="{{ __('Player Stats') }}">
<h2>{{ __('Player Stats') }}</h2>
@if($player)
<table>
<tbody>
    <tr><th>{{ __('Name') }}</th><td>{{ $player->lastName }}</td></tr>
    <tr><th>{{ __('Kills') }}</th><td>{{ number_format($player->kills) }}</td></tr>
    <tr><th>{{ __('Deaths') }}</th><td>{{ number_format($player->deaths) }}</td></tr>
    <tr><th>{{ __('K/D') }}</th><td>{{ $player->deaths > 0 ? round($player->kills / $player->deaths, 2) : $player->kills }}</td></tr>
    <tr><th>{{ __('Skill') }}</th><td>{{ number_format($player->skill) }}</td></tr>
    <tr><th>{{ __('Headshots') }}</th><td>{{ number_format($player->headshots ?? 0) }}</td></tr>
</tbody>
</table>
<p style="margin-top:8px;"><a href="{{ route('players.show', $player->playerId) }}" target="_top">{{ __('Full Profile') }}</a></p>
@else
<p style="color:#888;">{{ __('Player not found.') }}</p>
@endif
</x-layouts.ingame>
