<x-layouts.app
    robots="noindex,nofollow"
    :title="'429 — ' . __('Too many requests') . ' — ' . config('services.hlstats.site_name')"
    :description="__('You have sent too many requests.')"
    :breadcrumb="[config('services.hlstats.site_name') => route('home'), __('Too many requests') => null]">

    <x-errors.panel
        code="429"
        :title="__('Too many requests')"
        :message="__('You have sent too many requests in a short period. Please wait a moment before trying again.')" />

</x-layouts.app>
