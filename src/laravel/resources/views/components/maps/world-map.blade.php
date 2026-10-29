@props(['containerId' => 'serverMap', 'markers' => [], 'playerMarkers' => [], 'height' => '380px', 'tileUrl' => null, 'centerLat' => null, 'centerLng' => null, 'zoom' => null])

@php
    use App\Http\Controllers\Admin\AdminOptionsController;
    use App\Models\Option;
    $region  = Option::get('map_region', 'france');
    $coords  = AdminOptionsController::REGION_COORDS[$region] ?? AdminOptionsController::REGION_COORDS['france'];
    $mapLat  = $centerLat  ?? $coords['lat'];
    $mapLng  = $centerLng  ?? $coords['lng'];
    $mapZoom = $zoom       ?? $coords['zoom'];
@endphp

<div id="{{ $containerId }}" style="width:100%; height:{{ $height }}; border:1px solid var(--border); border-radius:var(--border-radius-md);"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var markers       = @json($markers);
    var playerMarkers = @json($playerMarkers);
    var tileUrl       = @json($tileUrl ?? 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');
    initServerMap('{{ $containerId }}', markers, tileUrl, playerMarkers, {{ $mapLat }}, {{ $mapLng }}, {{ $mapZoom }});
});
</script>
