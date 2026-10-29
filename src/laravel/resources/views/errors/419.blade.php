<x-layouts.app
    robots="noindex,nofollow"
    :title="'419 — ' . __('Session expired') . ' — ' . config('services.hlstats.site_name')"
    :description="__('Your session has expired.')"
    :breadcrumb="[config('services.hlstats.site_name') => route('home'), __('Session expired') => null]">

    <x-errors.panel
        code="419"
        :title="__('Session expired')"
        :message="__('Your session has expired for security reasons. Please reload the page and try again.')" />

</x-layouts.app>
