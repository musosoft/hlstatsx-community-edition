<x-layouts.admin title="Roles">
    @if(session('success'))<div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">{{ session('success') }}</div>@endif
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; flex-wrap:wrap; gap:8px;">
        <x-admin.game-filter :games="$games" :current="$game" route="admin.roles.index" />
        <a href="{{ route('admin.roles.create', ['game' => $game]) }}" class="hlx-btn-gold">+ Add Role</a>
    </div>
    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead><tr><th>Code</th><th>Name</th><th>Hidden</th><th>Actions</th></tr></thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono);">{{ $role->code }}</td>
                        <td class="hlx-text">{{ $role->name }}</td>
                        <td class="hlx-muted">{{ $role->hidden === '1' ? 'Yes' : 'No' }}</td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.roles.edit', $role->roleId) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.roles.destroy', $role->roleId) }}" onsubmit="return confirm('Delete role?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="hlx-muted" style="text-align:center; padding:20px;">No roles for this game.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
