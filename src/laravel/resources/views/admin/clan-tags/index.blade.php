<x-layouts.admin title="Clan Tags">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; justify-content:flex-end; margin-bottom:14px;">
        <a href="{{ route('admin.clan-tags.create') }}" class="hlx-btn-gold">+ Add Clan Tag</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Pattern</th><th>Position</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($tags as $tag)
                    <tr>
                        <td class="hlx-text" style="font-family:var(--font-family-mono);">{{ $tag->pattern }}</td>
                        <td class="hlx-muted">{{ $tag->position }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.clan-tags.edit', $tag->id) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.clan-tags.destroy', $tag->id) }}" onsubmit="return confirm('Delete clan tag?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="hlx-muted" style="text-align:center; padding:20px;">No clan tags defined.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
