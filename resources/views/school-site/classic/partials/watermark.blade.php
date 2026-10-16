{{--
    What a photo slot shows when it has no photo, or its photo fails to load:
    the school's logo, faint, over a faint tiling of itself (its seal when it
    has no logo). Hidden by CSS until its .ph / .slide is marked is-mark.
    Optional param: $label (a caption under the mark).
--}}
@php $wmLogo = \App\Models\SchoolWebsite::media($c['logo'] ?? null); @endphp
<div class="wm" aria-hidden="true" @if ($wmLogo) style="--wm-tile:url(&quot;{{ str_replace(['"', '\\'], ['%22', '%5C'], $wmLogo) }}&quot;)" @endif>
    @if ($wmLogo)
        <img src="{{ $wmLogo }}" alt="" onerror="this.remove()">
    @else
        @include('school-site.classic.partials.seal', ['center' => null, 'ring' => $c['school_name'], 'sealId' => 'wm'])
    @endif
    @if (!empty($label))<small>{{ $label }}</small>@endif
</div>
