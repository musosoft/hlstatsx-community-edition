<x-layouts.ingame title="{{ __('Servers') }}">
<h2>{{ __('Servers') }}</h2>
<table>
<thead><tr><th>#</th><th>{{ __('Server') }}</th><th>{{ __('Map') }}</th><th class="num">{{ __('Players') }}</th></tr></thead>
<tbody>
@foreach($servers as $i => $s)
<tr>
    <td class="muted">{{ $i + 1 }}</td>
    <td><a href="{{ route('servers.show', $s->serverId) }}" target="_top">{{ $s->name ?: $s->address . ':' . $s->port }}</a></td>
    <td>{{ $s->act_map ?? '—' }}</td>
    <td class="num">{{ ($s->act_players ?? 0) }}/{{ $s->max_players ?? '?' }}</td>
</tr>
@endforeach
</tbody>
</table>
</x-layouts.ingame>
