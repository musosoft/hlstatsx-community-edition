@props(['paginator'])

@if($paginator->hasPages())
    <div style="display:flex; gap:4px; justify-content:center; padding:12px 0;">

        {{-- Previous --}}
        @if($paginator->onFirstPage())
            <span class="hlx-page-link" style="opacity:0.4;">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="hlx-page-link">&laquo;</a>
        @endif

        {{-- Page numbers --}}
        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 4), min($paginator->lastPage(), $paginator->currentPage() + 4)) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="hlx-page-link active" style="color:var(--link); font-weight:600;">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="hlx-page-link">{{ $page }}</a>
            @endif
        @endforeach

        {{-- Next --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="hlx-page-link">&raquo;</a>
        @else
            <span class="hlx-page-link" style="opacity:0.4;">&raquo;</span>
        @endif

        <span style="color:var(--text-secondary); font-size:var(--font-size-sm); margin-left:8px; align-self:center;">
            {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}
        </span>
    </div>
@endif
