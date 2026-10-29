<x-layouts.admin title="Clans">

    <form method="GET" style="display:flex; gap:8px; margin-bottom:16px;">
        <input type="text" name="search" value="{{ $search }}"
               style="background-color:var(--bg-surface); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:5px 10px; font-size:var(--font-size-sm); width:220px;"
               placeholder="Search by name or tag...">
        <button type="submit" class="hlx-btn-green">Search</button>
    </form>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Tag</th><th>Game</th><th>Kills</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($clans as $clan)
                    <tr>
                        <td class="hlx-muted">{{ $clan->clanId }}</td>
                        <td class="hlx-text">{{ $clan->name }}</td>
                        <td class="hlx-text">{{ $clan->tag }}</td>
                        <td class="hlx-text">{{ $clan->game }}</td>
                        <td class="hlx-text">{{ number_format($clan->kills) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.clans.edit', $clan->clanId) }}" class="hlx-link" style="margin-right:8px;">Edit</a>
                            <form action="{{ route('admin.clans.destroy', $clan->clanId) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this clan?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hlx-muted" style="text-align:center; padding:20px;">No clans found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$clans" />

</x-layouts.admin>
