{{--
    Mint's inner-page title, as the superlms.in pages open: centred on a white
    page with two soft glows, a crumbs pill (Home / the menu group / the page),
    the page's name in the display face, the school as a grey meta pill, and
    the school's page banner photo (when it has one) in a wide rounded frame.
    Param: $heading.
--}}
@php
    $bannerSrc = trim((string) ($c['page_banner'] ?? ''));
    $crumb = null;
    foreach ($nav as $group => $items) {
        if (array_key_exists($current, $items) && $group !== 'Home') { $crumb = $group; break; }
    }
@endphp
<section class="m-banner">
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb">
            <span class="c-dot" aria-hidden="true"></span>
            <a href="{{ url('/') }}">Home</a>
            @if ($crumb && $crumb !== $heading)<i>/</i><span>{{ $crumb }}</span>@endif
            <i>/</i><b>{{ $heading }}</b>
        </nav>
        <h1>{{ $heading }}</h1>
        <span class="m-meta">@include('school-site.modern.partials.icon', ['n' => 'school']){{ $c['school_name'] }}@if (!empty($c['board'])) · {{ $c['board'] }}@endif</span>
        @if ($bannerSrc !== '')
            @include('school-site.classic.partials.ph', ['src' => $bannerSrc, 'alt' => $heading, 'class' => 'm-banner-photo'])
        @endif
    </div>
</section>
