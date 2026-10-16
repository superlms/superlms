{{--
    The school's logo. Should its link fail, the logo on the school's profile
    is tried next, then the school's seal shows in its place — never a broken
    image. Param: $sealId (unique per page).
--}}
@php
    $logoSrc  = \App\Models\SchoolWebsite::media($c['logo'] ?? null);
    $logoAlt  = \App\Models\SchoolWebsite::media($site->organization?->logo);
    $logoAlt  = $logoAlt !== $logoSrc ? $logoAlt : null;
@endphp
@if ($logoSrc)
    <img src="{{ $logoSrc }}" alt="{{ $c['school_name'] }} logo" @if ($logoAlt) data-alt="{{ $logoAlt }}" @endif
         onerror="if(this.dataset.alt){this.src=this.dataset.alt;this.removeAttribute('data-alt');}else{this.hidden=true;this.nextElementSibling.hidden=false;}">
    <span hidden>@include('school-site.classic.partials.seal', ['center' => null, 'ring' => $c['school_name'], 'sealId' => $sealId])</span>
@else
    @include('school-site.classic.partials.seal', ['center' => null, 'ring' => $c['school_name'], 'sealId' => $sealId])
@endif
