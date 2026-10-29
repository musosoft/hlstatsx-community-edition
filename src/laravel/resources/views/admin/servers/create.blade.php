<x-layouts.admin title="Add Server">
    <form method="POST" action="{{ route('admin.servers.store') }}" style="max-width:480px;">
        @csrf
        @foreach(['name' => 'Server Name', 'address' => 'IP Address', 'port' => 'Port', 'publicaddress' => 'Public Address (optional)', 'rcon_password' => 'RCON Password (optional)'] as $field => $label)
            <div style="margin-bottom:14px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">{{ $label }}</label>
                <input type="text" name="{{ $field }}" value="{{ old($field) }}"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @error($field) <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
        @endforeach
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
            <select name="game" style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @foreach($games as $g)
                    <option value="{{ $g->code }}" @selected(old('game') === $g->code)>{{ $g->name }} ({{ $g->code }})</option>
                @endforeach
            </select>
            @error('game') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">
                Admin Mod <span style="color:var(--status-offline);">*</span>
            </label>
            <select name="game_mod" required
                    style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                <option value="" disabled {{ old('game_mod') === null ? 'selected' : '' }}>PLEASE SELECT</option>
                @foreach($mods as $mod)
                    <option value="{{ $mod->code }}" @selected(old('game_mod') === $mod->code)>{{ $mod->name }}</option>
                @endforeach
            </select>
            @error('game_mod') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            <div class="hlx-muted" style="font-size:var(--font-size-sm); margin-top:4px;">
                La configuration par défaut du jeu et du mod sera automatiquement appliquée.
            </div>
        </div>
        <div style="display:flex; gap:8px; margin-top:8px;">
            <button type="submit" class="hlx-btn-gold">Create Server</button>
            <a href="{{ route('admin.servers.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
