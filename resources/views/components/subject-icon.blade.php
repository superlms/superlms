@props(['name' => null, 'size' => 'w-8 h-8'])

{{--
    Built-in subject icon. The artwork is picked from the subject NAME
    (normalized, so "Hindi" / "HINDI" / "hindi" are the same), with a generic
    book tile for anything unrecognised. See App\Support\SubjectIcons.
--}}
@php
    $iconColor = \App\Support\SubjectIcons::color($name);
    $iconGlyph = \App\Support\SubjectIcons::glyph($name);
@endphp

<span {{ $attributes->merge(['class' => $size . ' rounded-lg flex items-center justify-center flex-shrink-0']) }}
    style="background-color: {{ $iconColor }}" title="{{ $name }}" aria-hidden="true">
    <svg viewBox="0 0 24 24" class="w-[64%] h-[64%]" fill="none" stroke="#ffffff" stroke-width="1.7"
        stroke-linecap="round" stroke-linejoin="round">{!! $iconGlyph !!}</svg>
</span>
