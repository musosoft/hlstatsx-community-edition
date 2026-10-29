@php $aw = $award ?? null; @endphp
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
    <select name="game" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        @foreach($games as $g)
            <option value="{{ $g->code }}" {{ ($aw?->game ?? $selectedGame) === $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
    </select>
</div>
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Type</label>
    <select name="awardType" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        <option value="W" {{ old('awardType', $aw?->awardType) === 'W' ? 'selected' : '' }}>Weapon</option>
        <option value="1" {{ old('awardType', $aw?->awardType) === '1' ? 'selected' : '' }}>Player Action</option>
        <option value="2" {{ old('awardType', $aw?->awardType) === '2' ? 'selected' : '' }}>Player-Player Action</option>
    </select>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Code</label>
        <input type="text" name="code" value="{{ old('code', $aw?->code) }}" required maxlength="128"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Name</label>
        <input type="text" name="name" value="{{ old('name', $aw?->name) }}" required maxlength="128"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
<div>
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Verb <span style="font-weight:normal;">(e.g. "killed the most with")</span></label>
    <input type="text" name="verb" value="{{ old('verb', $aw?->verb) }}" maxlength="128"
           style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
</div>
