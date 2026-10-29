<x-layouts.admin title="Host Groups">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; justify-content:flex-end; margin-bottom:14px;">
        <a href="{{ route('admin.host-groups.create') }}" class="hlx-btn-gold">+ Add Host Group</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Name</th><th>Pattern</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($groups as $group)
                    <tr>
                        <td class="hlx-text">{{ $group->name }}</td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $group->pattern }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.host-groups.edit', $group->id) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.host-groups.destroy', $group->id) }}" onsubmit="return confirm('Delete host group?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="hlx-muted" style="text-align:center; padding:20px;">No host groups defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
