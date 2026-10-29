<x-layouts.admin :title="'Edit Theme: ' . ($theme['meta']['name'] ?? $slug)">

    <div style="display:grid; grid-template-columns:1fr 300px; gap:24px; align-items:start;">

        <form method="POST" action="{{ route('admin.themes.update', $slug) }}" id="themeForm">
            @csrf @method('PUT')

            <div style="margin-bottom:16px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Theme Name</label>
                <input type="text" name="meta[name]" value="{{ old('meta.name', $theme['meta']['name'] ?? '') }}"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            </div>

            <div style="margin-bottom:16px;">
                <label class="hlx-muted" style="display:block; margin-bottom:4px; font-size:var(--font-size-sm);">Description</label>
                <input type="text" name="meta[description]" value="{{ old('meta.description', $theme['meta']['description'] ?? '') }}"
                       style="width:100%; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 10px; font-size:var(--font-size-sm);">
            </div>

            <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
                <div style="padding:8px 12px; background-color:var(--bg-surface-alt); border-bottom:1px solid var(--border); font-size:var(--font-size-sm); font-weight:600; color:var(--text-heading);">{{ __('Colors') }}</div>
                <div style="padding:12px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                    @foreach($theme['colors'] ?? [] as $key => $value)
                        <div>
                            <label class="hlx-muted" style="display:block; margin-bottom:3px; font-size:11px; text-transform:capitalize;">{{ str_replace('-', ' ', $key) }}</label>
                            <div style="display:flex; gap:6px; align-items:center;">
                                <input type="color" value="{{ $value }}" oninput="document.getElementById('color_{{ $key }}').value = this.value"
                                       style="width:32px; height:26px; border:none; padding:0; cursor:pointer; background:none;">
                                <input type="text" name="colors[{{ $key }}]" id="color_{{ $key }}" value="{{ old('colors.' . $key, $value) }}"
                                       style="flex:1; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:4px 8px; font-size:11px; font-family:var(--font-family-mono);">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="hlx-btn-gold">{{ __('Save Theme') }}</button>
        </form>

        {{-- Live preview panel --}}
        <div style="position:sticky; top:20px;">
            <div style="background-color:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:12px;">
                <div style="font-size:12px; font-weight:600; color:var(--text-heading); margin-bottom:8px;">{{ __('Preview') }}</div>
                <div id="previewBg" style="height:60px; border-radius:var(--border-radius-sm); margin-bottom:8px; background-color:{{ $theme['colors']['bg-body'] ?? '#0d1117' }}; border:1px solid var(--border);"></div>
                <div style="display:flex; gap:4px; margin-bottom:8px;">
                    @foreach(['accent-primary', 'accent-secondary', 'bg-surface', 'bg-surface-alt', 'border'] as $k)
                        <div style="flex:1; height:24px; border-radius:2px; background-color:{{ $theme['colors'][$k] ?? '#333' }};" title="{{ $k }}"></div>
                    @endforeach
                </div>
                <p class="hlx-muted" style="font-size:11px; margin:0;">{{ __('Colors update live as you type hex values.') }}</p>
            </div>
        </div>

    </div>

</x-layouts.admin>
