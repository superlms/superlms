{{--
    Vibrant's inner-page title: a rounded gradient card with the page's name,
    the menu group it sits under, and the school's page banner photo (else
    its first slider photo) washed into the gradient. Param: $heading.
--}}
@php
    $bannerSrc = trim((string) ($c['page_banner'] ?? ''));
    if ($bannerSrc === '') {
        foreach ($c['hero_slides'] ?? [] as $s) {
            if (trim((string) ($s['image'] ?? '')) !== '') { $bannerSrc = $s['image']; break; }
        }
    }
    $bannerUrl = \App\Models\SchoolWebsite::media($bannerSrc ?: null);
    $crumb = null;
    foreach ($nav as $group => $items) {
        if (array_key_exists($current, $items) && $group !== 'Home') { $crumb = $group; break; }
    }
@endphp
<section class="v-banner">
    <div class="wrap">
        <div class="v-banner-card">
            @if ($bannerUrl)<img src="{{ $bannerUrl }}" alt="" onerror="this.remove();">@endif
            <i class="v-blob b1" aria-hidden="true"></i><i class="v-blob b2" aria-hidden="true"></i>
            <div>
                <nav class="crumbs" aria-label="Breadcrumb">
                    <a href="{{ url('/') }}">Home</a>
                    @if ($crumb && $crumb !== $heading)<i>/</i><span>{{ $crumb }}</span>@endif
                    <i>/</i><b>{{ $heading }}</b>
                </nav>
                <h1>{{ $heading }}</h1>
            </div>
        </div>
    </div>
</section>
