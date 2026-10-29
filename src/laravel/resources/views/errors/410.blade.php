<x-layouts.app
    robots="noindex,follow"
    :title="'410 — ' . __('Page permanently removed') . ' — ' . config('services.hlstats.site_name')"
    :description="__('This page has been permanently removed.')"
    :breadcrumb="[config('services.hlstats.site_name') => route('home'), __('Page permanently removed') => null]">

    <x-errors.panel
        code="410"
        :title="__('Page permanently removed')"
        :message="__('This page belonged to the old HLstatsX interface and has been permanently removed. It will not come back.')"
        :showSearch="true" />

</x-layouts.app>
