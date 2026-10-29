<x-layouts.ingame title="{{ __('Top Clans') }}">
<h2>{{ __('Top Clans') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Clan') }}</th><th class="num">{{ __('Members') }}</th><th class="num">{{ __('Kills') }}</th><th class="num">{{ __('Deaths') }}</th></tr></thead>
<tbody>
@foreach($clans as $i => $c)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('clans.show', [$c->clanId, 'game' => $game]) }}" target="_top">@if($c->tag)[{{ $c->tag }}] @endif{{ $c->name }}</a></td>
    <td class="num">{{ number_format($c->members) }}</td>
    <td class="num">{{ number_format($c->kills) }}</td>
    <td class="num">{{ number_format($c->deaths) }}</td>
</tr>
@endforeach
</tbody>
</table>
</x-layouts.ingame>
