<x-layouts.ingame title="{{ __('Top Weapons') }}">
<h2>{{ __('Top Weapons') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Weapon') }}</th><th class="num">{{ __('Kills') }}</th><th class="num">{{ __('Headshots') }}</th></tr></thead>
<tbody>
@foreach($weapons as $i => $w)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('weapons.show', [$w->weaponId, 'game' => $game]) }}" target="_top">{{ $w->name }}</a></td>
    <td class="num">{{ number_format($w->kills) }}</td>
    <td class="num">{{ number_format($w->headshots) }}</td>
</tr>
@endforeach
</tbody>
</table>
</x-layouts.ingame>
