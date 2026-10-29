@php $r = $ribbon ?? null; @endphp
<div style="margin-bottom:14px;">
    <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Game</label>
    <select name="game" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        @foreach($games as $g)
            <option value="{{ $g->code }}" {{ ($r?->game ?? $selectedGame) === $g->code ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
    </select>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Ribbon Name</label>
        <input type="text" name="ribbonName" value="{{ old('ribbonName', $r?->ribbonName) }}" required maxlength="50"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Image filename</label>
        <input type="text" name="image" value="{{ old('image', $r?->image) }}" maxlength="50" placeholder="ribbon.png"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
<div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:14px;">
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Award Code</label>
        <input type="text" name="awardCode" value="{{ old('awardCode', $r?->awardCode) }}" required maxlength="50"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Award Count</label>
        <input type="number" name="awardCount" value="{{ old('awardCount', $r?->awardCount ?? 1) }}" required min="1"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Special</label>
        <input type="number" name="special" value="{{ old('special', $r?->special ?? 0) }}" min="0"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
