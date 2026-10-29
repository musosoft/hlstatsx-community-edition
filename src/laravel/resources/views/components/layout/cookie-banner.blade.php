{{--
    Cookie notice. Only strictly necessary cookies are used, so the banner
    informs and records the acknowledgement; it never blocks the page.
    Visibility is decided client-side (see resources/js/app.js) so the markup
    stays cacheable and the consent cookie stays readable by JavaScript.
--}}
<div data-cookie-banner hidden
     role="dialog"
     aria-live="polite"
     aria-label="{{ __('Cookie notice') }}"
     class="hlx-cookie-banner">

    <div class="hlx-cookie-banner__text">
        <strong>{{ __('Cookies') }}</strong>
        <p>
            {{ __('This site only uses cookies that are strictly necessary for it to work: session, sign-in and form protection. No advertising, no tracking, no third-party analytics.') }}
            <a href="{{ route('cookies') }}" class="hlx-link">{{ __('Cookie policy') }}</a>
            &middot;
            <a href="{{ route('privacy') }}" class="hlx-link">{{ __('Privacy Policy') }}</a>
        </p>
    </div>

    <div class="hlx-cookie-banner__actions">
        <button type="button" class="hlx-btn-gold" data-cookie-accept>{{ __('Got it') }}</button>
    </div>
</div>
