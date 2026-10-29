@props(['crumbs' => []])

@php
    $siteName = (string) \App\Models\Option::get('sitename', config('services.hlstats.site_name', 'HLStatsX'));
@endphp

<div class="hlx-breadcrumb">
    <div class="hlx-pane-content">
        <span style="color:var(--text-primary); font-weight:600;">
            {{ $siteName }}
        </span>

        @foreach($crumbs as $label => $url)
            <span style="color:var(--text-primary); margin:0 4px;">&gt;&gt;</span>
            @if($url)
                <a href="{{ $url }}" class="hlx-link">{{ $label }}</a>
            @else
                <span style="color:var(--text-secondary);">{{ $label }}</span>
            @endif
        @endforeach
    </div>
</div>
