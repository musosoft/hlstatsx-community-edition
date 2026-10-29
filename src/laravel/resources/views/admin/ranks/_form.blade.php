@php $r = $rank ?? null; @endphp

<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
    <select name="game" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        @foreach($games as $g)
            <option value="{{ $g->code }}" {{ ($r?->game ?? $selectedGame) === $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
    </select>
</div>
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Rank Name</label>
    <input type="text" name="rankName" value="{{ old('rankName', $r?->rankName) }}" required maxlength="50"
           style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
</div>
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Image filename <span style="font-weight:normal;">(e.g. rank1.png)</span></label>
    <input type="text" name="image" value="{{ old('image', $r?->image) }}" required maxlength="30"
           style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Min Kills</label>
        <input type="number" name="minKills" value="{{ old('minKills', $r?->minKills ?? 0) }}" required min="0"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Max Kills</label>
        <input type="number" name="maxKills" value="{{ old('maxKills', $r?->maxKills ?? 100) }}" required
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
