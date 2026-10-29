<x-layouts.app
    :title="__('Servers') . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), 'Servers' => null]"
    :gameNav="$game"
    activeTab="servers">

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th>{{ __('Server') }}</th>
                    <th>{{ __('Address') }}</th>
                    <th>{{ __('Game') }}</th>
                    <th>{{ __('Map') }}</th>
                    <th>{{ __('Players') }}</th>
                    <th>{{ __('Total Kills') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($servers as $server)
                    <tr>
                        <td>
                            <a href="{{ route('servers.show', $server->serverId) }}" class="hlx-link" style="display:inline-flex; align-items:center; gap:6px;">
                                <x-ui.game-logo :game="$server->realgame" :size="16" />
                                {{ $server->name }}
                            </a>
                        </td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">
                            {{ $server->full_address }}
                        </td>
                        <td class="hlx-text">{{ $server->game }}</td>
                        <td class="hlx-text">{{ $server->act_map ?? '—' }}</td>
                        <td class="hlx-text">{{ $server->act_players }}/{{ $server->max_players }}</td>
                        <td class="hlx-text">{{ number_format($server->kills) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="hlx-muted" style="text-align:center; padding:20px;">{{ __('No servers found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</x-layouts.app>
