<x-layouts.admin title="Themes">

    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:16px;">
        @foreach($themes as $theme)
            @php
                $slug = $theme['meta']['slug'] ?? '';
                $isActive = ($active['meta']['slug'] ?? '') === $slug;
            @endphp
            <div style="background-color:var(--bg-surface-alt); border:2px solid {{ $isActive ? 'var(--accent-primary)' : 'var(--border)' }}; border-radius:var(--border-radius-md); overflow:hidden;">
                {{-- Theme color preview --}}
                <div style="height:40px; display:flex;">
                    @foreach(array_slice(array_values($theme['colors'] ?? []), 0, 5) as $color)
                        <div style="flex:1; background-color:{{ $color }};"></div>
                    @endforeach
                </div>
                <div style="padding:12px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                        <span style="color:var(--text-heading); font-size:14px; font-weight:600;">{{ $theme['meta']['name'] ?? $slug }}</span>
                        @if($isActive)
                            <span style="background-color:var(--accent-primary); color:var(--bg-body); font-size:10px; font-weight:700; padding:2px 6px; border-radius:100px;">{{ __('ACTIVE') }}</span>
                        @endif
                    </div>
                    <p class="hlx-muted" style="font-size:11px; margin:0 0 12px;">{{ $theme['meta']['description'] ?? '' }}</p>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        @if(!$isActive)
                            <form action="{{ route('admin.themes.activate', $slug) }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="hlx-btn-gold" style="font-size:11px; padding:3px 8px;">{{ __('Activate') }}</button>
                            </form>
                        @endif
                        <a href="{{ route('admin.themes.edit', $slug) }}" class="hlx-btn-green" style="font-size:11px; padding:3px 8px; text-decoration:none;">{{ __('Edit') }}</a>
                        <form action="{{ route('admin.themes.duplicate', $slug) }}" method="POST" style="display:inline;">
                            @csrf
                                <button type="submit" style="background:none; border:1px solid var(--border); color:var(--text-secondary); border-radius:var(--border-radius-sm); cursor:pointer; font-size:11px; padding:3px 8px;">{{ __('Copy') }}</button>
                        </form>
                        @if(!$isActive)
                            <form action="{{ route('admin.themes.destroy', $slug) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this theme?')">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none; border:1px solid var(--status-offline); color:var(--status-offline); border-radius:var(--border-radius-sm); cursor:pointer; font-size:11px; padding:3px 8px;">{{ __('Delete') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

</x-layouts.admin>
