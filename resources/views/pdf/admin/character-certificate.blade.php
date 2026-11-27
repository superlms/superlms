<!DOCTYPE html>
<html lang="{{ $cc->isHindi() ? 'hi' : 'en' }}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Character Certificate - {{ $cc->student->full_name ?? '' }}</title>
    {{-- Character certificate (चरित्र प्रमाण-पत्र), user's ask of 2026-11-27: the
         school's own form — book and serial numbers, the years studied, the
         examination with its division, the date of birth from the admission
         register in figures and words, the conduct line and the principal's
         signature — in Hindi or English, on an A4 sheet framed navy and gold,
         the school's logo faint behind the words. Set in Poppins, whose faces
         carry the shaped Devanagari (App\Support\Pdf\Devanagari). --}}
    <style>
        {{-- The Poppins faces, embedded (App\Support\PdfFonts). --}}
        {!! $fontCss ?? '' !!}
        * { padding: 0; }
        @page { size: A4 portrait; margin: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; color: #1f2937; background: #fff; }

        .page   { position: relative; width: 210mm; height: 297mm; }
        .frame  { position: absolute; top: 9mm;  left: 9mm;  right: 9mm;  bottom: 9mm;  border: 2.4px solid #1e3a5f; }
        .frame2 { position: absolute; top: 11.5mm; left: 11.5mm; right: 11.5mm; bottom: 11.5mm; border: 0.8px solid #b8933f; }

        {{-- The logo, large and faint, in the middle of the sheet. --}}
        .mark { position: absolute; left: 0; right: 0; top: 92mm; text-align: center; }
        .mark img { width: 118mm; opacity: 0.07; }

        .content { position: absolute; top: 18mm; left: 22mm; right: 22mm; bottom: 18mm; }

        .head { text-align: center; }
        .logo { height: 22mm; margin-bottom: 2mm; }
        .school { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 21pt; color: #1e3a5f; line-height: 1.05; letter-spacing: .2px; }
        .place  { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 11pt; color: #374151; margin-top: 1.5mm; line-height: 1.05; }
        .contact { font-size: 8pt; color: #6b7280; margin-top: 1.5mm; line-height: 1.05; }
        .rule { margin: 4mm auto 0; width: 60mm; border-top: 1px solid #b8933f; }

        {{-- The title boxed, as on the printed form. --}}
        .title-wrap { text-align: center; margin-top: 5mm; }
        .title { display: inline-block; padding: 1.6mm 9mm; border: 1.4px solid #1e3a5f; border-radius: 6mm;
                 font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 15pt; color: #1e3a5f; line-height: 1.05; letter-spacing: .5px; }
        .en .title { letter-spacing: 3px; font-size: 13.5pt; }

        .meta { width: 100%; border-collapse: collapse; margin-top: 7mm; }
        .meta td { font-size: 10.5pt; line-height: 1.1; vertical-align: top; }
        .meta td.r { text-align: right; }
        .meta b { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-weight: normal; color: #111827; }

        .body { margin-top: 9mm; font-size: 12pt; line-height: 1.45; text-align: justify; color: #1f2937; }
        .body p { margin: 0 0 5mm; }
        .body p.lead { text-indent: 12mm; }
        {{-- What was filled in: set off as the form's dotted blanks. --}}
        .f { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #111827; border-bottom: 1px dotted #6b7280; padding: 0 .6mm; }

        .foot { position: absolute; left: 22mm; right: 22mm; bottom: 24mm; }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { vertical-align: bottom; font-size: 10.5pt; line-height: 1.1; }
        .sign { width: 62mm; margin-left: auto; text-align: center; border-top: 1px solid #374151; padding-top: 2mm;
                font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #111827; }
        .sign small { display: block; font-family: 'Poppins', sans-serif; font-size: 8.5pt; color: #6b7280; margin-top: 1mm; }
    </style>
</head>
<body>
@php
    $org = $cc->organization;
    $s   = $cc->student;
    $hi  = $cc->isHindi();
    $she = $cc->isFemale();

    // The logo: a full URL as it is (S3), else the local public file.
    $logoSrc = null;
    if (!empty($org?->logo)) {
        if (\Illuminate\Support\Str::startsWith($org->logo, ['http://', 'https://'])) {
            $logoSrc = $org->logo;
        } elseif (file_exists(public_path('storage/' . $org->logo))) {
            $logoSrc = public_path('storage/' . $org->logo);
        }
    }

    $contact = $contact ?? [];
    $address = $contact['address'] ?? ($org->address ?? null);
    $bits    = array_filter([$contact['mobile'] ?? ($org->mobile_number ?? null), $contact['email'] ?? ($org->email ?? null), $contact['website'] ?? null]);

    // Hindi names where they were typed; the record's otherwise.
    $school = $hi ? ($cc->school_name_hi ?: ($org->name ?? '')) : ($org->name ?? '');
    $place  = $hi ? ($cc->school_place_hi ?: $address) : $address;
    $name   = $hi ? ($cc->student_name_hi ?: ($s->full_name ?? '—')) : ($s->full_name ?? '—');
    $father = $hi ? ($cc->father_name_hi ?: ($s->father_name ?? '—')) : ($s->father_name ?? '—');

    $dob      = $s?->dob;
    $dobFig   = $dob ? $dob->format('d/m/Y') : '—';
    $dobWords = $dob ? ($hi ? \App\Support\DateInWords::hindi($dob) : \App\Support\DateInWords::english($dob)) : '—';

    $passed   = $cc->result !== 'failed';
    $division = \App\Models\Admin\CharacterCertificate::DIVISIONS[$cc->division ?? ''] ?? null;
    $issued   = $cc->issue_date?->format('d/m/Y');
@endphp
<div class="page {{ $hi ? 'hi' : 'en' }}">
    <div class="frame"></div>
    <div class="frame2"></div>
    @if ($logoSrc)
        <div class="mark"><img src="{{ $logoSrc }}" alt=""></div>
    @endif

    <div class="content">
        <div class="head">
            @if ($logoSrc)
                <img class="logo" src="{{ $logoSrc }}" alt="">
            @endif
            <div class="school">{{ $school }}</div>
            @if ($place)
                <div class="place">{{ $place }}</div>
            @endif
            @if ($bits)
                <div class="contact">{{ implode('  ·  ', $bits) }}</div>
            @endif
            <div class="rule"></div>
        </div>

        <div class="title-wrap"><span class="title">{{ $hi ? 'चरित्र प्रमाण-पत्र' : 'CHARACTER CERTIFICATE' }}</span></div>

        <table class="meta">
            <tr>
                <td>{{ $hi ? 'पुस्तक संख्या' : 'Book No.' }}: <b>{{ $cc->book_no }}</b></td>
                <td class="r">{{ $hi ? 'क्रम संख्या' : 'Serial No.' }}: <b>{{ $cc->serial_no }}</b></td>
            </tr>
            <tr>
                <td></td>
                <td class="r" style="padding-top: 2mm">{{ $hi ? 'दिनांक' : 'Date' }}: <b>{{ $issued }}</b></td>
            </tr>
        </table>

        <div class="body">
            @if ($hi)
                <p class="lead">मुझे प्रमाणित करते हुए हर्ष है कि {{ $she ? 'कु०' : 'श्री' }} <span class="f">{{ $name }}</span>
                    {{ $she ? 'आत्मजा' : 'आत्मज' }} श्री <span class="f">{{ $father }}</span>
                    सन् <span class="f">{{ $cc->from_year }}</span> से <span class="f">{{ $cc->to_year }}</span> तक इस संस्था के
                    {{ $she ? 'छात्रा रही हैं' : 'छात्र रहे हैं' }}। आप सन् <span class="f">{{ $cc->exam_year }}</span> की
                    <span class="f">{{ $cc->exam_name }}</span> परीक्षा में
                    @if ($passed && $division)
                        <span class="f">{{ $division[0] }}</span> श्रेणी में <span class="f">उत्तीर्ण</span> {{ $she ? 'हुईं' : 'हुए' }}।
                    @else
                        <span class="f">{{ $passed ? 'उत्तीर्ण' : 'अनुत्तीर्ण' }}</span> {{ $she ? 'हुईं' : 'हुए' }}।
                    @endif
                </p>
                <p class="lead">{{ $she ? 'छात्रा' : 'छात्र' }} प्रवेश रजिस्टर के अनुसार आपकी जन्मतिथि <span class="f">{{ $dobFig }}</span>
                    (शब्दों में) <span class="f">{{ $dobWords }}</span> है।</p>
                <p class="lead">जहाँ तक मुझे ज्ञात है, आपने अपने अध्ययन काल में किसी अवैधानिक कार्य में
                    {{ $cc->took_part_unlawful ? 'भाग लिया था' : 'भाग नहीं लिया है' }}। मैं आपके उज्ज्वल भविष्य की कामना करता हूँ।</p>
            @else
                <p class="lead">It gives me pleasure to certify that {{ $she ? 'Ms.' : 'Mr.' }} <span class="f">{{ $name }}</span>,
                    {{ $she ? 'daughter' : 'son' }} of Mr. <span class="f">{{ $father }}</span>, was a student of this institution
                    from <span class="f">{{ $cc->from_year }}</span> to <span class="f">{{ $cc->to_year }}</span>.
                    {{ $she ? 'She' : 'He' }}
                    @if ($passed && $division)
                        passed the <span class="f">{{ $cc->exam_name }}</span> examination of <span class="f">{{ $cc->exam_year }}</span>
                        in the <span class="f">{{ $division[1] }}</span> division.
                    @else
                        appeared in the <span class="f">{{ $cc->exam_name }}</span> examination of <span class="f">{{ $cc->exam_year }}</span>
                        {{ $passed ? 'and passed.' : 'but did not pass.' }}
                    @endif
                </p>
                <p class="lead">According to the admission register, {{ $she ? 'her' : 'his' }} date of birth is
                    <span class="f">{{ $dobFig }}</span> (in words: <span class="f">{{ $dobWords }}</span>).</p>
                <p class="lead">As far as I know, {{ $she ? 'she' : 'he' }}
                    {{ $cc->took_part_unlawful ? 'had taken part in an unlawful activity' : 'has not taken part in any unlawful activity' }}
                    during {{ $she ? 'her' : 'his' }} period of study. I wish {{ $she ? 'her' : 'him' }} a bright future.</p>
            @endif
        </div>
    </div>

    {{-- The date and the principal's signature on the sheet's last band (placed
         on the page itself: dompdf does not size the content box from its
         top and bottom). --}}
    <div class="foot">
        <table>
            <tr>
                <td>{{ $hi ? 'दिनांक' : 'Date' }}: <b class="f">{{ $issued }}</b></td>
                <td>
                    <div class="sign">{{ $hi ? 'प्रधानाचार्य' : 'Principal' }}<small>{{ $school }}</small></div>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
