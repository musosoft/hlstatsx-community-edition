<x-layouts.ingame title="{{ __('Top Maps') }}">
<h2>{{ __('Top Maps') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Map') }}</th><th class="num">{{ __('Kills') }}</th></tr></thead>
<tbody>
@foreach($maps as $i => $m)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('maps.show', [$m->map, 'game' => $game]) }}" target="_top">{{ $m->map }}</a></td>
    <td class="num">{{ number_format($m->kills) }}</td>
</tr>
@endforeach
</tbody>
</table>
</x-layouts.ingame>
