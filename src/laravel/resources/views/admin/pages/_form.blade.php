@php
    $locales = collect(glob(lang_path('*.json')))->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))->sort()->values();
    $inputStyle = 'width:100%; max-width:520px; box-sizing:border-box; background-color:var(--bg-body); color:var(--text-primary); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:6px 9px; font-size:var(--font-size-sm);';
@endphp

@if($errors->any())
    <div style="background:var(--bg-surface-alt); border:1px solid var(--status-offline); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:12px; font-size:var(--font-size-sm); color:var(--status-offline);">
        <ul style="margin:0; padding-left:16px;">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

@if(session('success'))
    <div style="background:var(--bg-surface-alt); border:1px solid var(--border); border-radius:var(--border-radius-sm); padding:8px 12px; margin-bottom:12px; font-size:var(--font-size-sm); color:#4ade80;">
        {{ session('success') }}
    </div>
@endif

<div style="border:1px solid var(--border); border-radius:var(--border-radius-md); overflow:hidden; margin-bottom:16px;">
    <table class="hlx-table" style="margin:0;">
        <tr>
            <td style="width:30%; color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Slug') }}</td>
            <td style="padding:6px 14px;">
                <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" style="{{ $inputStyle }}"
                       @disabled($page->is_system) required>
                @if($page->is_system)
                    <input type="hidden" name="slug" value="{{ $page->slug }}">
                    <div class="hlx-muted" style="font-size:11px; margin-top:4px;">{{ __('System page: the slug is used by the site navigation and cannot be changed.') }}</div>
                @else
                    <div class="hlx-muted" style="font-size:11px; margin-top:4px;">{{ __('Lowercase letters, digits and dashes. Used in the URL: /pages/<slug>') }}</div>
                @endif
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Locale') }}</td>
            <td style="padding:6px 14px;">
                <select name="locale" style="{{ $inputStyle }} max-width:180px;">
                    @foreach($locales as $locale)
                        <option value="{{ $locale }}" @selected(old('locale', $page->locale) === $locale)>{{ strtoupper($locale) }}</option>
                    @endforeach
                </select>
                <div class="hlx-muted" style="font-size:11px; margin-top:4px;">{{ __('One row per language. Visitors fall back to EN when their language is missing.') }}</div>
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Title') }}</td>
            <td style="padding:6px 14px;">
                <input type="text" name="title" value="{{ old('title', $page->title) }}" style="{{ $inputStyle }}" required>
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px; vertical-align:top;">{{ __('Body') }}</td>
            <td style="padding:6px 14px;">
                <textarea name="body" rows="24" spellcheck="false"
                          style="{{ $inputStyle }} max-width:100%; font-family:var(--font-family-mono); font-size:12px; line-height:1.5;" required>{{ old('body', $page->body) }}</textarea>
                <div class="hlx-muted" style="font-size:11px; margin-top:4px;">
                    {{ __('HTML is allowed. Placeholders replaced when the page is displayed:') }}
                    <code>%SITE_NAME%</code>, <code>%SITE_URL%</code>, <code>%CONTACT%</code>.
                </div>
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Published') }}</td>
            <td style="padding:6px 14px;">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published)) @disabled($page->is_system)>
                @if($page->is_system)
                    <span class="hlx-muted" style="font-size:11px;">{{ __('System pages are always published.') }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Show in footer') }}</td>
            <td style="padding:6px 14px;">
                <input type="hidden" name="show_in_footer" value="0">
                <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer))>
            </td>
        </tr>
        <tr>
            <td style="color:var(--text-secondary); font-size:var(--font-size-sm); padding:8px 14px;">{{ __('Sort order') }}</td>
            <td style="padding:6px 14px;">
                <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order) }}" min="0" max="9999"
                       style="{{ $inputStyle }} max-width:110px;">
            </td>
        </tr>
    </table>
</div>
