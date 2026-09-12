<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Certificate - {{ $cert->student->full_name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; }
        @page { size: A4 portrait; margin: 0; }
        body { font-family: "DejaVu Sans", sans-serif; width: 210mm; height: 297mm; background: #fff; }

        .page { width: 210mm; height: 297mm; position: relative; }

        {{-- Double gold matting. Sized with top/left/right/bottom (dompdf ignores
             box-sizing, so explicit widths would have to fight the border width). --}}
        .frame       { position: absolute; top: 9mm;  left: 9mm;  right: 9mm;  bottom: 9mm;  border: 2px solid #c9a24b; }
        .frame-inner { position: absolute; top: 12mm; left: 12mm; right: 12mm; bottom: 12mm; border: 0.8px solid #e6ce90; }

        .content { position: absolute; top: 0; left: 0; right: 0; bottom: 0; text-align: center; padding: 26mm 20mm 0; }

        .logo { height: 30mm; margin-bottom: 5mm; }
        {{-- School name + address share the student-name serif; the address sits exactly
             2px below the name's size (25px → 23px). --}}
        .school-name { font-size: 25px; font-weight: bold; color: #1f2937; font-family: "DejaVu Serif", Georgia, serif; letter-spacing: 0.5px; }
        .school-addr { font-size: 23px; color: #6b7280; font-family: "DejaVu Serif", Georgia, serif; margin-top: 2mm; }

        .cert-title { font-size: 36pt; font-weight: bold; color: #1f2937; font-family: "DejaVu Serif", Georgia, serif; letter-spacing: 5px; text-transform: uppercase; margin-top: 13mm; }
        {{-- No rule through this line: dompdf cannot place `top:50%` inside an inline
             wrapper and strands the stroke ~120mm further down the page. --}}
        .cert-sub { font-size: 11.5pt; color: #a8862f; letter-spacing: 6px; text-transform: uppercase; margin-top: 4.5mm; }

        .presented { font-size: 9.5pt; color: #9ca3af; letter-spacing: 3px; text-transform: uppercase; margin-top: 15mm; }
        .student-name { font-size: 34pt; color: #8a6d1f; font-style: italic; font-family: "DejaVu Serif", Georgia, serif; margin-top: 6mm; }

        .description { font-size: 11pt; color: #4b5563; line-height: 1.8; width: 150mm; margin: 15mm auto 0; }
        .event-name { font-size: 11pt; color: #a8862f; letter-spacing: 3px; text-transform: uppercase; margin-top: 14mm; }

        {{-- Bottom band: date (left) · seal (centre) · signature (right). The seal is
             what keeps the lower third of the page from reading as dead paper. --}}
        .footer { position: absolute; left: 24mm; right: 24mm; bottom: 36mm; }
        .footer-table { width: 100%; }
        .footer-table td { vertical-align: bottom; font-size: 11pt; color: #374151; }

        .seal-outer { width: 26mm; height: 26mm; margin: 0 auto; border: 1.5px solid #c9a24b; border-radius: 13mm; }
        .seal-inner { width: 21mm; height: 21mm; margin: 2mm auto 0; border: 0.8px solid #e6ce90; border-radius: 10.5mm; text-align: center; }
        .seal-star { font-size: 11pt; color: #c9a24b; line-height: 1; margin-top: 3.4mm; }
        .seal-kind { font-size: 5pt; color: #a8862f; letter-spacing: 0.6px; margin-top: 1.2mm; }
        .seal-year { font-size: 8pt; font-weight: bold; color: #8a6d1f; margin-top: 0.6mm; }

        {{-- No rule above the issuer's name — the signature sits on bare paper. --}}
        .sig-rule { width: 55mm; margin-left: auto; text-align: center; font-size: 9pt; color: #6b7280; }

        .contact { position: absolute; left: 24mm; right: 24mm; bottom: 17mm; }
        .contact-table { width: 100%; }
        .contact-table td { font-size: 9pt; color: #6b7280; }
        .contact-mid { text-align: center; color: #9ca3af; }
        .contact-right { text-align: right; }
    </style>
</head>
<body>
<div class="page">
    <div class="frame"></div>
    <div class="frame-inner"></div>

    <div class="content">
        @php
            $logoSrc = null;
            if (!empty($cert->organization?->logo)) {
                if (\Illuminate\Support\Str::startsWith($cert->organization->logo, ['http://', 'https://'])) {
                    $logoSrc = $cert->organization->logo;
                } elseif (file_exists(public_path('storage/' . $cert->organization->logo))) {
                    $logoSrc = public_path('storage/' . $cert->organization->logo);
                }
            }
            $kind = $cert->type === 'participation' ? 'Participation' : 'Achievement';
        @endphp
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
        @endif

        <div class="school-name">{{ strtoupper($cert->organization->name ?? 'School Name') }}</div>
        @if ($cert->organization->address ?? false)
            <div class="school-addr">{{ $cert->organization->address }}</div>
        @endif

        <div class="cert-title">Certificate</div>
        <div class="cert-sub">Of {{ $kind }}</div>

        <div class="presented">This is proudly presented to</div>
        <div class="student-name">{{ $cert->student->full_name ?? 'Student Name' }}</div>

        @if ($cert->description)
            <div class="description">{{ $cert->description }}</div>
        @else
            <div class="description">
                This certificate acknowledges
                {{ $cert->type === 'participation' ? 'the active participation' : 'the outstanding achievement' }}
                of {{ $cert->student->full_name ?? 'the student' }} in
                <strong>{{ $cert->event_name }}</strong>.
            </div>
        @endif

        @if ($cert->event_name)
            <div class="event-name">{{ strtoupper($cert->event_name) }}</div>
        @endif
    </div>

    {{-- Date · seal · signature --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width:34%; text-align:left;">{{ $cert->issued_date->format('d F, Y') }}</td>
                <td style="width:32%; text-align:center;">
                    <div class="seal-outer">
                        <div class="seal-inner">
                            <div class="seal-star">&#9733;</div>
                            <div class="seal-kind">{{ strtoupper($kind) }}</div>
                            <div class="seal-year">{{ $cert->issued_date->format('Y') }}</div>
                        </div>
                    </div>
                </td>
                <td style="width:34%;">
                    <div class="sig-rule">
                        {{ $cert->issued_by }}@if ($cert->issued_by_designation)<br>{{ $cert->issued_by_designation }}@endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Contact footer --}}
    <div class="contact">
        <table class="contact-table">
            <tr>
                <td style="width:34%;">@if ($cert->organization->mobile_number ?? false) Mobile: {{ $cert->organization->mobile_number }} @endif</td>
                <td class="contact-mid" style="width:32%;">@if ($cert->certificate_no) No. {{ $cert->certificate_no }} @endif</td>
                <td class="contact-right" style="width:34%;">@if ($cert->organization->email ?? false) Email: {{ $cert->organization->email }} @endif</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
