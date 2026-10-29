<x-layouts.admin title="Add Game">
    <form method="POST" action="{{ route('admin.games.store') }}" style="max-width:480px;">
        @csrf
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game Code <span style="color:var(--status-offline);">*</span></label>
            <input type="text" name="code" value="{{ old('code') }}" required maxlength="32"
                   placeholder="e.g. css, tf, csgo"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('code') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game Name <span style="color:var(--status-offline);">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required maxlength="128"
                   placeholder="e.g. Counter-Strike: Source"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('name') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Real Game (for assets)</label>
            <input type="text" name="realgame" value="{{ old('realgame') }}" maxlength="32"
                   placeholder="e.g. css (folder name in hlstatsimg/games/)"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('realgame') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Display Order</label>
            <input type="number" name="sortorder" value="{{ old('sortorder', 0) }}" min="0" max="255"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            @error('sortorder') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
        </div>
        <div style="margin-bottom:16px;">
            <label style="display:flex; align-items:center; gap:8px; color:var(--text-secondary); font-size:var(--font-size-sm); cursor:pointer;">
                <input type="checkbox" name="hidden" value="1" {{ old('hidden') ? 'checked' : '' }}>
                Hide from public
            </label>
        </div>

        <fieldset style="border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:14px 16px; margin-bottom:16px;">
            <legend class="hlx-muted" style="padding:0 6px; font-size:var(--font-size-sm);">{{ __('Custom status / max-players API (optional)') }}</legend>
            <p class="hlx-muted" style="margin:0 0 12px; font-size:var(--font-size-sm); line-height:1.4;">
                {{ __('For games without A2S_INFO support (e.g. BattleBit). The endpoint must return a JSON array of objects. Each server is matched by its name against the chosen field. A matching entry also marks the server online/offline on the map.') }}
            </p>
            <div style="margin-bottom:12px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">{{ __('Query URL') }}</label>
                <input type="url" name="query_url" value="{{ old('query_url') }}" maxlength="255"
                       placeholder="https://publicapi.battlebit.cloud/Servers/GetServerList"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                @error('query_url') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
            </div>
            <div style="display:flex; gap:10px; margin-bottom:4px;">
                <div style="flex:1;">
                    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">{{ __('Name field (match server.name)') }}</label>
                    <input type="text" name="query_match_field" value="{{ old('query_match_field') }}" maxlength="64"
                           placeholder="Name"
                           style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                    @error('query_match_field') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
                </div>
                <div style="flex:1;">
                    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">{{ __('Max players field') }}</label>
                    <input type="text" name="query_max_players_field" value="{{ old('query_max_players_field') }}" maxlength="64"
                           placeholder="MaxPlayers"
                           style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                    @error('query_max_players_field') <div style="color:var(--status-offline); font-size:var(--font-size-sm); margin-top:4px;">{{ $message }}</div> @enderror
                </div>
            </div>
        </fieldset>

        <div style="display:flex; gap:8px;">
            <button type="submit" class="hlx-btn-gold">Create Game</button>
            <a href="{{ route('admin.games.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
