<x-layouts.ingame title="{{ __('Top Players') }}">
<h2>{{ __('Top Players') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Player') }}</th><th class="num">{{ __('K') }}</th><th class="num">{{ __('D') }}</th><th class="num">{{ __('Skill') }}</th></tr></thead>
<tbody>
@foreach($players as $i => $p)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('players.show', $p->playerId) }}" target="_top">{{ $p->lastName }}</a></td>
    <td class="num">{{ number_format($p->kills) }}</td>
    <td class="num">{{ number_format($p->deaths) }}</td>
    <td class="num">{{ number_format($p->skill) }}</td>
</tr>
@endforeach
</tbody>
</table>
</x-layouts.ingame>
