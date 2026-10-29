<x-layouts.ingame title="{{ __('Top 10 Players') }}">
<h2>{{ __('Top 10 Players') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Player') }}</th><th class="num">{{ __('Kills') }}</th><th class="num">{{ __('Skill') }}</th></tr></thead>
<tbody>
@foreach($topPlayers as $i => $p)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('players.show', $p->playerId) }}" target="_top">{{ $p->lastName }}</a></td>
    <td class="num">{{ number_format($p->kills) }}</td>
    <td class="num">{{ number_format($p->skill) }}</td>
</tr>
@endforeach
</tbody>
</table>
<p style="margin-top:10px; text-align:right;"><a href="{{ route('home', $game ? ['game' => $game] : []) }}" target="_top">{{ __('Full Stats') }}</a></p>
</x-layouts.ingame>
