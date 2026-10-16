{{--
    The school on Google Maps (SchoolWebsite::mapSrc): the embed link the
    school saved, else its pin, else a map searched from its address.
    Optional param: $height.
--}}
@php $mapSrc = \App\Models\SchoolWebsite::mapSrc($c); @endphp
@if ($mapSrc)
    <div class="map" style="height: {{ $height ?? 420 }}px; border: 1px solid var(--line); background: #E9EAEE;">
        <iframe src="{{ $mapSrc }}" title="Map to {{ $c['school_name'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="width:100%;height:100%;border:0;" allowfullscreen></iframe>
    </div>
@endif
