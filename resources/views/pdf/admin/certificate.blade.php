<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Certificate - {{ $cert->student->full_name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 0; }
        body { font-family: "DejaVu Sans", sans-serif; width: 210mm; height: 297mm; background: #fff; }

        .page { width: 210mm; height: 297mm; position: relative; }
        .frame { position: absolute; top: 7mm; left: 7mm; right: 7mm; bottom: 7mm; border: 2px solid #c9a24b; }
        .frame-inner { position: absolute; top: 3mm; left: 3mm; right: 3mm; bottom: 3mm; border: 0.8px solid #e2c878; }

        .content { position: absolute; top: 0; left: 0; right: 0; bottom: 0; text-align: center; padding: 22mm 22mm; }

        .logo { height: 24mm; margin-bottom: 6mm; }
        .school-name { font-size: 19pt; font-weight: bold; color: #1f2937; font-family: "DejaVu Serif", Georgia, serif; letter-spacing: 0.5px; }
        .school-addr { font-size: 9pt; color: #6b7280; margin-top: 2mm; }

        .cert-title { font-size: 34pt; font-weight: bold; color: #1f2937; font-family: "DejaVu Serif", Georgia, serif; letter-spacing: 4px; text-transform: uppercase; margin-top: 12mm; }
        .cert-sub-wrap { margin: 3mm auto 0; width: 60%; position: relative; }
        .cert-sub-line { border-top: 1.2px solid #c9a24b; position: absolute; top: 50%; left: 0; right: 0; }
        .cert-sub { font-size: 12pt; color: #4b5563; letter-spacing: 4px; text-transform: uppercase; background: #fff; display: inline-block; padding: 0 5mm; position: relative; }

        .presented { font-size: 10pt; color: #9ca3af; letter-spacing: 3px; text-transform: uppercase; margin-top: 12mm; }
        .student-name { font-size: 36pt; color: #8a6d1f; font-style: italic; font-family: "DejaVu Serif", Georgia, serif; margin-top: 4mm; }
        .name-rule { width: 62%; margin: 4mm auto 0; border-top: 1.2px solid #c9a24b; }

        .description { font-size: 11pt; color: #4b5563; line-height: 1.7; max-width: 150mm; margin: 12mm auto 0; }
        .dated { font-size: 10pt; color: #6b7280; letter-spacing: 1px; margin-top: 8mm; }

        .footer { position: absolute; left: 22mm; right: 22mm; bottom: 26mm; }
        .footer-table { width: 100%; }
        .footer-table td { vertical-align: bottom; font-size: 11pt; color: #374151; }
        .sig-rule { border-top: 1.2px solid #9ca3af; width: 55mm; margin-left: auto; padding-top: 2mm; text-align: center; font-size: 9pt; color: #6b7280; }

        .contact { position: absolute; left: 22mm; right: 22mm; bottom: 14mm; }
        .contact-table { width: 100%; }
        .contact-table td { font-size: 9pt; color: #6b7280; }
        .contact-right { text-align: right; }
    </style>
</head>
<body>
<div class="page">
    <div class="frame"><div class="frame-inner"></div></div>

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
        @endphp
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
        @endif

        <div class="school-name">{{ strtoupper($cert->organization->name ?? 'School Name') }}</div>
        @if ($cert->organization->address ?? false)
            <div class="school-addr">{{ $cert->organization->address }}</div>
        @endif

        <div class="cert-title">Certificate</div>
        <div class="cert-sub-wrap">
            <span class="cert-sub-line"></span>
            <span class="cert-sub">Of {{ $cert->type === 'participation' ? 'Participation' : 'Achievement' }}</span>
        </div>

        <div class="presented">This is proudly presented to</div>
        <div class="student-name">{{ $cert->student->full_name ?? 'Student Name' }}</div>
        <div class="name-rule"></div>

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
            <div class="dated">{{ strtoupper($cert->event_name) }}</div>
        @endif
    </div>

    {{-- Footer: date (left) + signature (right) --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width:50%; text-align:left;">{{ $cert->issued_date->format('d F, Y') }}</td>
                <td style="width:50%;">
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
                <td>@if ($cert->organization->mobile_number ?? false) Mobile: {{ $cert->organization->mobile_number }} @endif</td>
                <td class="contact-right">@if ($cert->organization->email ?? false) Email: {{ $cert->organization->email }} @endif</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
