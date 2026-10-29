<x-layouts.admin title="Create Admin User">
    @if($errors->any())
        <div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.admin-users.store') }}" style="max-width:440px;">
        @csrf
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Username</label>
            <input type="text" name="username" value="{{ old('username') }}" required maxlength="16"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Password</label>
            <input type="password" name="password" required minlength="12"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            <span style="font-size:11px; color:var(--text-secondary);">{{ __('12 characters minimum, 72 bytes maximum. Stored with bcrypt.') }}</span>
        </div>
        <div style="margin-bottom:20px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Access Level</label>
            <select name="acclevel" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                <option value="0">No Access</option>
                <option value="80">Restricted</option>
                <option value="100" selected>Administrator</option>
            </select>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="hlx-btn-gold">Create</button>
            <a href="{{ route('admin.admin-users.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
