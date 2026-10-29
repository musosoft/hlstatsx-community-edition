<x-layouts.admin title="Admin Users">
    @if($errors->any())
        <div role="alert" style="color:var(--status-offline); margin-bottom:16px;">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div style="background-color:rgba(63,185,80,0.1); border:1px solid var(--status-online); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-online); font-size:var(--font-size-sm);">
            {{ session('success') }}
        </div>
    @endif

    <div style="margin-bottom:14px;">
        <a href="{{ route('admin.admin-users.create') }}" class="hlx-btn-gold">+ Add User</a>
    </div>

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr><th>Username</th><th>Access Level</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td class="hlx-text" style="font-family:var(--font-family-mono);">{{ $user->username }}</td>
                        <td class="hlx-muted">
                            @if($user->acclevel >= 100) <span style="color:var(--accent-primary);">Administrator</span>
                            @elseif($user->acclevel >= 80) <span style="color:var(--text-secondary);">Restricted</span>
                            @else <span class="hlx-muted">No Access ({{ $user->acclevel }})</span>
                            @endif
                        </td>
                        <td style="display:flex; gap:8px;">
                            <a href="{{ route('admin.admin-users.edit', $user->username) }}" class="hlx-link">Edit</a>
                            <form method="POST" action="{{ route('admin.admin-users.destroy', $user->username) }}"
                                  onsubmit="return confirm('Delete user {{ $user->username }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:none; color:var(--status-offline); cursor:pointer; font-size:var(--font-size-sm); padding:0;">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="hlx-muted" style="text-align:center; padding:20px;">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
