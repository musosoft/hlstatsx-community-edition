<x-layouts.admin title="Servers">
    <div style="margin-bottom:12px; text-align:right;">
        <a href="{{ route('admin.servers.create') }}" class="hlx-btn-gold">+ Add Server</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Address</th><th>Game</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($servers as $server)
                    <tr>
                        <td class="hlx-muted">{{ $server->serverId }}</td>
                        <td class="hlx-text">{{ $server->name }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">{{ $server->full_address }}</td>
                        <td class="hlx-text">{{ $server->game }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.servers.edit', $server->serverId) }}" class="hlx-link" style="margin-right:8px;">Edit</a>
                            <form action="{{ route('admin.servers.destroy', $server->serverId) }}" method="POST" style="display:inline;"
                                  onsubmit="return confirm('Supprimer le serveur {{ addslashes($server->name) }} ?\n\n⚠ Toutes les données liées (config, événements) seront définitivement supprimées.')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hlx-muted" style="text-align:center; padding:20px;">No servers configured.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$servers" />
</x-layouts.admin>
