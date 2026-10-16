{{--
    A photo slot that falls back to the school's watermark — when no photo is
    set, and when the one set fails to load.
    Params: $src (stored media value or null), optional $alt, $class,
    $label (caption on the watermark), $lb (true → opens in the lightbox).
--}}
@php $phUrl = \App\Models\SchoolWebsite::media($src ?? null); @endphp
<div class="ph {{ $class ?? '' }} {{ $phUrl ? '' : 'is-mark' }}" @if ($phUrl && !empty($lb)) data-lb @endif>
    @if ($phUrl)
        <img src="{{ $phUrl }}" alt="{{ $alt ?? '' }}" loading="lazy"
             onerror="var p=this.parentNode;p.classList.add('is-mark');p.removeAttribute('data-lb');this.remove();">
    @endif
    @include('school-site.classic.partials.watermark', ['label' => $label ?? null])
</div>
