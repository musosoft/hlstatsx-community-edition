<x-layouts.admin :title="'Pages — ' . $page->title">
    <form method="POST" action="{{ route('admin.pages.update', $page->id) }}">
        @csrf @method('PUT')

        @include('admin.pages._form', ['page' => $page])

        <div style="padding:4px 0 16px;">
            <button type="submit" class="hlx-btn-gold">{{ __('Save') }}</button>
            <a href="{{ route('pages.show', $page->slug) }}" target="_blank" class="hlx-link" style="margin-left:12px; font-size:var(--font-size-sm);">{{ __('View') }}</a>
            <a href="{{ route('admin.pages.index') }}" class="hlx-link" style="margin-left:12px; font-size:var(--font-size-sm);">{{ __('Back') }}</a>
        </div>
    </form>
</x-layouts.admin>
