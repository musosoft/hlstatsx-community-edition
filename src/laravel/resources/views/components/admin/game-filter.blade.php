@props(['games', 'current', 'route', 'extra' => []])
<form method="GET" action="{{ route($route) }}" style="display:inline-flex; align-items:center; gap:8px; margin-bottom:16px;">
    @foreach($extra as $k => $v)
        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
    @endforeach
    <label class="hlx-muted" style="font-size:var(--font-size-sm);">Game:</label>
    <select name="game" onchange="this.form.submit()"
            style="background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:4px 8px; font-size:var(--font-size-sm);">
        @foreach($games as $g)
            <option value="{{ $g->code }}" {{ $g->code === $current ? 'selected' : '' }}>{{ $g->name }}</option>
        @endforeach
    </select>
</form>
