@props(['code' => '', 'size' => '16'])

@php
    $code = strtolower($code ?: 'unknown');
    $src  = "/hlstatsimg/flags/{$code}.gif";
    $alt  = strtoupper($code);
@endphp

<img src="{{ $src }}" alt="{{ $alt }}" title="{{ $alt }}"
     width="{{ $size }}" height="{{ (int)($size * 0.6875) }}"
     style="vertical-align:middle; display:inline-block;"
     onerror="this.style.display='none'">
