<x-layouts.admin :title="'Edit Player: ' . $player->lastName">

    <form method="POST" action="{{ route('admin.players.update', $player->playerId) }}" style="max-width:480px;">
        @csrf @method('PUT')

        <div style="margin-bottom:16px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Player Name</label>
            <input type="text" name="lastName" value="{{ old('lastName', $player->lastName) }}" required
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('lastName') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:flex; align-items:center; gap:8px; color:var(--text-secondary); font-size:var(--font-size-sm); cursor:pointer;">
                <input type="checkbox" name="hideranking" value="1" {{ old('hideranking', $player->hideranking) ? 'checked' : '' }}>
                Hide from ranking
            </label>
        </div>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="hlx-btn-gold">Save Changes</button>
            <a href="{{ route('admin.players.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>

</x-layouts.admin>
