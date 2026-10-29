<x-layouts.app
    robots="noindex,nofollow"
    :title="'403 — ' . __('Access denied') . ' — ' . config('services.hlstats.site_name')"
    :description="__('You do not have permission to view this page.')"
    :breadcrumb="[config('services.hlstats.site_name') => route('home'), __('Access denied') => null]">

    <x-errors.panel
        code="403"
        :title="__('Access denied')"
        :message="(isset($exception) ? $exception->getMessage() : '') ?: __('You do not have permission to view this page.')" />

</x-layouts.app>
