@props(['game' => '', 'size' => 16])
@if($game)
    <img src="{{ asset('hlstatsimg/games/' . $game . '/game.png') }}" alt="{{ $game }}" title="{{ $game }}"
         style="width:{{ $size }}px; height:{{ $size }}px; object-fit:contain; vertical-align:middle; flex-shrink:0;"
         onerror="this.style.display='none'">
@endif
