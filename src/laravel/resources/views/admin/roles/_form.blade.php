@php $r = $role ?? null; @endphp
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
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Code</label>
        <input type="text" name="code" value="{{ old('code', $r?->code) }}" required maxlength="64"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
    <div>
        <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Name</label>
        <input type="text" name="name" value="{{ old('name', $r?->name) }}" required maxlength="64"
               style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
    </div>
</div>
<div>
    <label style="display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--text-secondary); font-size:var(--font-size-sm);">
        <input type="checkbox" name="hidden" value="1" {{ old('hidden', $r?->hidden) == '1' ? 'checked' : '' }}>
        Hidden from public
    </label>
</div>
