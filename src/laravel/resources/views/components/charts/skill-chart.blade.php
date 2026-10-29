@props(['canvasId' => 'skillChart', 'labels' => [], 'data' => [], 'height' => '200px'])

<div style="position:relative; height:{{ $height }}; background-color:var(--chart-bg); border:1px solid var(--border); border-radius:var(--border-radius-md); padding:8px;">
    <canvas id="{{ $canvasId }}"></canvas>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    initSkillChart(
        '{{ $canvasId }}',
        @json($labels),
        @json($data)
    );
});
</script>
