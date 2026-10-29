<x-layouts.admin title="Weapons">
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Code</th><th>Game</th><th>Kills</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($weapons as $weapon)
                    <tr>
                        <td class="hlx-muted">{{ $weapon->weaponId }}</td>
                        <td class="hlx-text">{{ $weapon->name }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">{{ $weapon->code }}</td>
                        <td class="hlx-text">{{ $weapon->game }}</td>
                        <td class="hlx-text">{{ number_format($weapon->kills) }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('admin.weapons.edit', $weapon->weaponId) }}" class="hlx-link" style="margin-right:8px;">Edit</a>
                            <form action="{{ route('admin.weapons.destroy', $weapon->weaponId) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this weapon?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="hlx-muted" style="text-align:center; padding:20px;">No weapons found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <x-ui.pagination :paginator="$weapons" />
</x-layouts.admin>
