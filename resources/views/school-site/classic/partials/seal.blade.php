{{--
    A round seal drawn in the theme's colours — stands in for the school's
    logo (its initials) or the board's (its name) when no image is set.
    Params: $center (text in the middle; null → the initials of $ring),
    $ring (text round the edge), $sealId (unique per page for the text path).
--}}
@php
    $ringText = mb_strtoupper(\Illuminate\Support\Str::limit(trim((string) $ring), 34, ''));
    if ($center === null) {
        // "N. R. I. Public School" → NRI; "Adarsh Satyadev Inter College" → ASI.
        $skip  = ['THE', 'OF', 'AND', 'PUBLIC', 'SCHOOL', 'SR', 'SEC', 'SENIOR', 'SECONDARY', 'COLLEGE', 'ACADEMY', 'CONVENT', 'INTER', 'HIGH', 'MODEL', 'VIDYALAYA'];
        $words = array_values(array_filter(preg_split('/[^\pL\pN]+/u', mb_strtoupper((string) $ring)), fn ($w) => $w !== '' && ! in_array($w, $skip, true)));
        $center = implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 3))) ?: mb_substr($ringText, 0, 1);
    }
    $center = mb_strtoupper(trim((string) $center));
    $size   = min(26, max(10, 92 / max(1, mb_strlen($center))));
@endphp
<svg viewBox="0 0 120 120" role="img" aria-label="{{ $ring }}">
    <defs><path id="seal-path-{{ $sealId }}" d="M16,60 a44,44 0 1,1 88,0 a44,44 0 1,1 -88,0"/></defs>
    <circle cx="60" cy="60" r="57" style="fill:#fff;stroke:var(--primary)" stroke-width="3"/>
    <circle cx="60" cy="60" r="52" style="fill:none;stroke:var(--dark)" stroke-width="1"/>
    <circle cx="60" cy="60" r="35" style="fill:var(--light);stroke:var(--primary)" stroke-width="1.5"/>
    <text font-family="Poppins, Arial, sans-serif" font-size="9" font-weight="700" letter-spacing="1.2" style="fill:var(--dark)">
        <textPath href="#seal-path-{{ $sealId }}" startOffset="25%" text-anchor="middle">{{ $ringText }}</textPath>
    </text>
    <text x="60" y="61" text-anchor="middle" dominant-baseline="central" font-family="Poppins, Arial, sans-serif" font-weight="800" font-size="{{ round($size, 1) }}" style="fill:var(--primary)">{{ $center }}</text>
</svg>
