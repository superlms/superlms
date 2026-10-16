{{--
    The school on Google Maps: the embed link the school saved (a pasted
    <iframe> works too), else a map searched from its address. Only Google
    Maps links are embedded. Optional param: $height.
--}}
@php
    $mapSrc = trim((string) ($c['map_embed'] ?? ''));
    if (preg_match('/src=["\']([^"\']+)["\']/i', $mapSrc, $mm)) {
        $mapSrc = html_entity_decode($mm[1]);
    }
    if (! preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed#i', $mapSrc) && ! preg_match('#^https://maps\.google\.[a-z.]+/#i', $mapSrc)) {
        $mapSrc = '';
    }
    if ($mapSrc === '' && trim((string) ($c['address'] ?? '')) !== '') {
        $mapSrc = 'https://maps.google.com/maps?q=' . urlencode($c['school_name'] . ', ' . $c['address']) . '&z=14&output=embed';
    }
@endphp
@if ($mapSrc)
    <div class="map" style="height: {{ $height ?? 420 }}px; border: 1px solid var(--line); background: #E9EAEE;">
        <iframe src="{{ $mapSrc }}" title="Map to {{ $c['school_name'] }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                style="width:100%;height:100%;border:0;" allowfullscreen></iframe>
    </div>
@endif
