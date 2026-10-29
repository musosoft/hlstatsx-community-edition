<x-layouts.admin title="Pages — New page">
    <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf

        @include('admin.pages._form', ['page' => $page])

        <div style="padding:4px 0 16px;">
            <button type="submit" class="hlx-btn-gold">{{ __('Create') }}</button>
            <a href="{{ route('admin.pages.index') }}" class="hlx-link" style="margin-left:12px; font-size:var(--font-size-sm);">{{ __('Cancel') }}</a>
        </div>
    </form>
</x-layouts.admin>
