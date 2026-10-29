<x-layouts.app
    :title="$page->renderedTitle() . ' — ' . config('services.hlstats.site_name')"
    :breadcrumb="['HLStatsX' => route('home'), $page->renderedTitle() => null]">

<div class="hlx-static-page" style="max-width:820px; margin:0 auto; line-height:1.7;">

    <x-ui.section-title :title="$page->renderedTitle()" />

    <div style="margin-bottom:24px;">
        {!! $page->renderedBody() !!}
    </div>

    <p style="color:var(--text-secondary); font-size:var(--font-size-sm);">
        {{ __('Last updated:') }} {{ optional($page->updated_at)->format('Y-m-d') }}
    </p>

</div>

</x-layouts.app>
