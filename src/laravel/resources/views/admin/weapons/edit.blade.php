<x-layouts.admin :title="'Edit Weapon: ' . $weapon->name">
    <form method="POST" action="{{ route('admin.weapons.update', $weapon->weaponId) }}" style="max-width:480px;">
        @csrf @method('PUT')
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Weapon Name</label>
            <input type="text" name="name" value="{{ old('name', $weapon->name) }}" required
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Modifier</label>
            <input type="number" step="0.01" name="modifier" value="{{ old('modifier', $weapon->modifier) }}"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="hlx-btn-gold">Save</button>
            <a href="{{ route('admin.weapons.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
