{{--
    The photo strip with the page's title over it, on every inner page. The
    photo is the school's page banner, else its first slider photo, else the
    watermark (also what shows if the photo fails to load). Param: $heading.
--}}
@php
    $bannerSrc = trim((string) ($c['page_banner'] ?? ''));
    if ($bannerSrc === '') {
        foreach ($c['hero_slides'] ?? [] as $s) {
            if (trim((string) ($s['image'] ?? '')) !== '') { $bannerSrc = $s['image']; break; }
        }
    }
    $bannerUrl = \App\Models\SchoolWebsite::media($bannerSrc ?: null);
@endphp
<section class="banner ph {{ $bannerUrl ? '' : 'is-mark' }}">
    @if ($bannerUrl)
        <img src="{{ $bannerUrl }}" alt="" onerror="this.parentNode.classList.add('is-mark');this.remove();">
    @endif
    @include('school-site.classic.partials.watermark', ['label' => null])
    <h1>{{ $heading }}</h1>
</section>
