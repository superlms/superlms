{{--
    Breeze's inner-page title: a pale band over a faint grid with the page's
    name, the menu group it sits under, and the school's page banner photo
    (else its first slider photo) in a rounded frame beside it. Param: $heading.
--}}
@php
    $bannerSrc = trim((string) ($c['page_banner'] ?? ''));
    if ($bannerSrc === '') {
        foreach ($c['hero_slides'] ?? [] as $s) {
            if (trim((string) ($s['image'] ?? '')) !== '') { $bannerSrc = $s['image']; break; }
        }
    }
    $crumb = null;
    foreach ($nav as $group => $items) {
        if (array_key_exists($current, $items) && $group !== 'Home') { $crumb = $group; break; }
    }
@endphp
<section class="z-banner">
    <div class="wrap z-banner-in">
        <div>
            <nav class="crumbs" aria-label="Breadcrumb">
                <a href="{{ url('/') }}">Home</a>
                @if ($crumb && $crumb !== $heading)<i>/</i><span>{{ $crumb }}</span>@endif
                <i>/</i><b>{{ $heading }}</b>
            </nav>
            <h1>{{ $heading }}</h1>
            <p>{{ $c['school_name'] }}@if (!empty($c['board'])) · {{ $c['board'] }}@endif</p>
        </div>
        @if ($bannerSrc !== '')
            <div class="z-banner-photo">@include('school-site.classic.partials.ph', ['src' => $bannerSrc, 'alt' => ''])</div>
        @endif
    </div>
</section>
