<x-layouts.admin :title="'Edit Role: ' . $role->name">
    @if($errors->any())<div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.roles.update', $role->roleId) }}" style="max-width:440px;">
        @csrf @method('PUT')
        @include('admin.roles._form', ['games' => $games, 'selectedGame' => $role->game, 'role' => $role])
        <div style="display:flex; gap:8px; margin-top:20px;">
            <button type="submit" class="hlx-btn-gold">Save</button>
            <a href="{{ route('admin.roles.index', ['game' => $role->game]) }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
