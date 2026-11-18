{{-- The top of the Super Admin's sign-in screens, as the website's header:
     the logo and the SUPER·LMS wordmark, and a pill back to the website. --}}
<header class="sa-auth-top">
    <a href="{{ url('/') }}" class="sa-auth-brand" aria-label="SUPERLMS website">
        <img src="{{ \App\Support\Platform::logoUrl() }}" alt="">
        <b>SUPER<span>LMS</span></b>
    </a>
    <a href="{{ url('/') }}" class="sa-auth-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
        Back to website
    </a>
</header>
