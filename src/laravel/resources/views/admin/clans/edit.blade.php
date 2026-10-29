<x-layouts.admin :title="'Edit Clan: ' . $clan->name">
    <form method="POST" action="{{ route('admin.clans.update', $clan->clanId) }}" style="max-width:480px;">
        @csrf @method('PUT')
        @foreach(['name' => 'Clan Name', 'tag' => 'Tag', 'homepage' => 'Homepage URL'] as $field => $label)
            <div style="margin-bottom:16px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">{{ $label }}</label>
                <input type="text" name="{{ $field }}" value="{{ old($field, $clan->$field) }}"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @error($field) <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        @endforeach
        <div style="display:flex; gap:8px;">
            <button type="submit" class="hlx-btn-gold">Save</button>
            <a href="{{ route('admin.clans.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
