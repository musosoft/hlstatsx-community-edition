@props(['canvasId' => 'activityChart', 'labels' => [], 'data' => [], 'label' => 'Activity', 'height' => '180px'])

<div style="position:relative; height:{{ $height }}; background-color:var(--chart-bg); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:8px;">
    <canvas id="{{ $canvasId }}"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    initActivityChart(
        '{{ $canvasId }}',
        @json($labels),
        @json($data),
        '{{ addslashes($label) }}'
    );
});
</script>
