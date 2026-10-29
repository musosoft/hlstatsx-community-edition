<x-layouts.admin :title="'Edit Clan Tag: ' . $tag->pattern">
    @if($errors->any())<div style="background-color:rgba(248,81,73,0.1); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:16px; color:var(--status-offline); font-size:var(--font-size-sm);">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('admin.clan-tags.update', $tag->id) }}" style="max-width:400px;">
        @csrf @method('PUT')
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Pattern</label>
            <input type="text" name="pattern" value="{{ old('pattern', $tag->pattern) }}" required maxlength="64"
                   style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
        </div>
        <div style="margin-bottom:14px;">
            <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Position</label>
            <select name="position" style="width:100%; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
                <option value="EITHER" {{ old('position', $tag->position) === 'EITHER' ? 'selected' : '' }}>Either</option>
                <option value="START" {{ old('position', $tag->position) === 'START' ? 'selected' : '' }}>Start</option>
                <option value="END" {{ old('position', $tag->position) === 'END' ? 'selected' : '' }}>End</option>
            </select>
        </div>
        <div style="display:flex; gap:8px; margin-top:20px;">
            <button type="submit" class="hlx-btn-gold">Save</button>
            <a href="{{ route('admin.clan-tags.index') }}" class="hlx-btn-green">Cancel</a>
        </div>
    </form>
</x-layouts.admin>
