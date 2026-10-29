<x-layouts.admin title="Pages">
    <div style="margin-bottom:12px; text-align:right;">
        <a href="{{ route('admin.pages.create') }}" class="hlx-btn-gold">+ {{ __('New page') }}</a>
    </div>

    @if(session('success'))
        <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:12px; font-size:var(--font-size-sm); color:#4ade80;">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div style="background:var(--bg-surface-alt); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:12px; font-size:var(--font-size-sm); color:var(--status-offline);">
            {{ $errors->first() }}
        </div>
    @endif

    <div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden;">
        <table class="hlx-table">
            <thead>
                <tr>
                    <th style="width:40px;">ID</th>
                    <th>{{ __('Title') }}</th>
                    <th style="width:140px;">{{ __('Slug') }}</th>
                    <th style="width:70px;">{{ __('Locale') }}</th>
                    <th style="width:90px;">{{ __('Published') }}</th>
                    <th style="width:110px;">{{ __('Show in footer') }}</th>
                    <th style="width:70px;">{{ __('Sort order') }}</th>
                    <th style="width:130px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pages as $page)
                    <tr>
                        <td class="hlx-muted">{{ $page->id }}</td>
                        <td class="hlx-text" style="font-weight:600;">
                            {{ $page->title }}
                            @if($page->is_system)
                                <span style="font-size:11px; font-weight:600; color:var(--accent-secondary); background:rgba(255,255,255,.06); padding:2px 7px; border-radius:3px; margin-left:6px;">{{ __('System page') }}</span>
                            @endif
                        </td>
                        <td class="hlx-muted" style="font-family:var(--font-family-mono); font-size:var(--font-size-sm);">{{ $page->slug }}</td>
                        <td class="hlx-muted" style="text-transform:uppercase;">{{ $page->locale }}</td>
                        <td style="text-align:center;">{!! $page->is_published ? '&#10003;' : '&mdash;' !!}</td>
                        <td style="text-align:center;">{!! $page->show_in_footer ? '&#10003;' : '&mdash;' !!}</td>
                        <td class="hlx-muted" style="text-align:center;">{{ $page->sort_order }}</td>
                        <td style="white-space:nowrap;">
                            <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="hlx-link" style="font-size:var(--font-size-sm);">{{ __('View') }}</a>
                            &middot;
                            <a href="{{ route('admin.pages.edit', $page->id) }}" class="hlx-link" style="font-size:var(--font-size-sm);">{{ __('Edit') }}</a>
                            @unless($page->is_system)
                                &middot;
                                <form method="POST" action="{{ route('admin.pages.destroy', $page->id) }}" style="display:inline;"
                                      onsubmit="return confirm('{{ __('Delete this page?') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="background:none; border:none; padding:0; cursor:pointer; color:var(--status-offline); font-size:var(--font-size-sm);">{{ __('Delete') }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="hlx-muted" style="text-align:center; padding:16px;">{{ __('No pages yet.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
