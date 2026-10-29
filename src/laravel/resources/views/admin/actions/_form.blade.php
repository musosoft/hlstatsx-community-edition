@php $a = $action ?? null; @endphp
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
    <select name="game" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        @foreach($games as $g)
            <option value="{{ $g->code }}" {{ ($a?->game ?? $selectedGame) === $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
    </select>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Code</label>
        <input type="text" name="code" value="{{ old('code', $a?->code) }}" required maxlength="64"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Description</label>
        <input type="text" name="description" value="{{ old('description', $a?->description) }}" maxlength="128"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:14px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Player Reward</label>
        <input type="number" name="reward_player" value="{{ old('reward_player', $a?->reward_player ?? 0) }}" required
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Team Reward</label>
        <input type="number" name="reward_team" value="{{ old('reward_team', $a?->reward_team ?? 0) }}" required
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Team Filter</label>
        <input type="text" name="team" value="{{ old('team', $a?->team) }}" maxlength="64" placeholder="blank = all"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
<div style="margin-bottom:8px;">
    <label class="hlx-muted" style="display:block; margin-bottom:6px; font-size:var(--font-size-sm);">Applies to</label>
    <div style="display:flex; flex-wrap:wrap; gap:12px;">
        @foreach(['for_PlayerActions' => 'Player Actions', 'for_PlayerPlayerActions' => 'Player-Player Actions', 'for_TeamActions' => 'Team Actions', 'for_WorldActions' => 'World Actions'] as $field => $label)
        <label style="display:flex; align-items:center; gap:6px; cursor:pointer; color:var(--text-secondary); font-size:var(--font-size-sm);">
            <input type="hidden" name="{{ $field }}" value="0">
            <input type="checkbox" name="{{ $field }}" value="1"
                {{ old($field, $a?->{$field} ?? '0') == '1' ? 'checked' : '' }}>
            {{ $label }}
        </label>
        @endforeach
    </div>
</div>
