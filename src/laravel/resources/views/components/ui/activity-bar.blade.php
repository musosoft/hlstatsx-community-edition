@props(['score' => 0, 'max' => 1])

@php
    $ratio = $max > 0 ? ($score / $max) : 0;
    $ratio = min(1, max(0, $ratio));
    $width = round($ratio * 100);

    if ($ratio > 0.66) {
        $colorClass = 'hlx-activity-high';
    } elseif ($ratio > 0.33) {
        $colorClass = 'hlx-activity-mid';
    } else {
        $colorClass = 'hlx-activity-low';
    }
@endphp

<div style="background-color:var(--bg-body); height:10px; width:100%; border-radius:2px; overflow:hidden;">
    <div class="hlx-activity-bar {{ $colorClass }}" style="width:{{ $width }}%; height:100%;"></div>
</div>
