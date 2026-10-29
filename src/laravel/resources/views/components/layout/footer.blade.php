@php
    $footerPages = \App\Models\Page::footerLinks();
@endphp
<footer style="background-color:var(--bg-header); border-top:1px solid var(--border); padding:16px; text-align:center; margin-top:24px;">
    <div style="color:var(--text-secondary); font-size:var(--font-size-sm);">
        <strong style="color:var(--accent-secondary);">HLX:CE</strong>
        &mdash;
        <a href="https://github.com/Royal-Multi-Gamers/hlstatsx-community-edition-laravel" target="_blank" class="hlx-link" style="font-size:var(--font-size-sm);">HLStatsX Community Edition Laravel</a>
        &mdash; GPL-2.0
    </div>

    @if($footerPages->isNotEmpty())
        <div style="margin-top:8px; color:var(--text-secondary); font-size:var(--font-size-sm);">
            @foreach($footerPages as $footerPage)
                @if(!$loop->first)<span style="opacity:0.5;"> &middot; </span>@endif
                <a href="{{ route('pages.show', $footerPage->slug) }}" class="hlx-link" style="font-size:var(--font-size-sm);">{{ $footerPage->renderedTitle() }}</a>
            @endforeach
            <span style="opacity:0.5;"> &middot; </span>
            <a href="{{ route('cookies') }}" data-cookie-reopen class="hlx-link" style="font-size:var(--font-size-sm);">{{ __('Cookie settings') }}</a>
        </div>
    @endif
</footer>
