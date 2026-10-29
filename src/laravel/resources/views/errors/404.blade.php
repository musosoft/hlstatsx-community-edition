<x-layouts.app
    robots="noindex,follow"
    :title="'404 — ' . __('Page not found') . ' — ' . config('services.hlstats.site_name')"
    :description="__('This page does not exist or has been removed.')"
    :breadcrumb="[config('services.hlstats.site_name') => route('home'), __('Page not found') => null]">

    <x-errors.panel
        code="404"
        :title="__('Page not found')"
        :message="__('This page does not exist or has been removed. It may have been part of the old HLstatsX interface, or the player, clan or server you are looking for is no longer tracked.')"
        :showSearch="true" />

</x-layouts.app>
