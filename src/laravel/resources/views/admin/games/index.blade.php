<x-layouts.admin title="Games">
    <div style="display:flex; justify-content:flex-end; margin-bottom:12px;">
        <a href="{{ route('admin.games.create') }}" class="hlx-btn-gold">+ Add Game</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>Order</th><th>Code</th><th>Name</th><th>Hidden</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($games as $game)
                    <tr>
                        <td class="hlx-muted">{{ (int) ($game->sortorder ?? 0) }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $game->code }}</td>
                        <td class="hlx-text">{{ $game->name }}</td>
                        <td class="hlx-muted">{{ $game->hidden == '1' ? 'Yes' : 'No' }}</td>
                        <td style="display:flex; gap:8px; align-items:center;">
                            <a href="{{ route('admin.games.edit', $game->code) }}" class="hlx-link">Edit</a>
                            <a href="{{ route('admin.games.duplicate', $game->code) }}" class="hlx-link" style="color:var(--text-muted);">Duplicate</a>
                            <form method="POST" action="{{ route('admin.games.destroy', $game->code) }}"
                                  onsubmit="return confirm('Supprimer le jeu {{ addslashes($game->name) }} ?\n\n⚠ Tous les joueurs, serveurs, armes, rangs, événements et données liées seront définitivement supprimés.')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="hlx-muted" style="text-align:center; padding:20px;">No games found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
