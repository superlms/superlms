{{--
    More → Gatepass's slip (App\Support\GatePassSlip): one B5 page in the
    Report Card's look (user's ask of 2026-11-25; admin/report-card-pdf,
    scaled from A4 to B5): the affiliation number and the website above a
    blue frame, the logo, the school and its address centred, GATE PASS
    with its number and date, the student and the going out in grey-ruled
    tables, the remark, and three bold signatures near the foot.
    Tables, because dompdf has no flexbox; the topbar, the frame and the
    signatures are position:fixed. Poppins' line-height is kept low: dompdf
    multiplies it by the face's full height.

    In scope: $pass (with organization, studentDetail, standard, section,
    addedBy), $org, $logo, $contact, $fontCss; and from GatePassSlip also
    $website and $affiliation (blank when not given).
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gate Pass {{ $pass->number() }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        /* No `* { margin: 0 }` — that wipes the @page margins. */
        @page { size: B5 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 10.5px; color: #000; line-height: 1; }
        table { width: 100%; border-collapse: collapse; }
        .sb { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }

        /* The Report Card's sheet on B5 (176 × 250mm): the frame 5mm in at the
           sides, 8.5mm at the top (the topbar sits in that gap), 5.5mm at the foot. */
        .topbar { position: fixed; top: 2.6mm; left: 5mm; width: 166mm; }
        .topbar td { font-size: 9.8px; padding: 0; vertical-align: top; }
        .topbar td.right { text-align: right; }
        .frame { position: fixed; top: 8.5mm; left: 5mm; right: 5mm; bottom: 5.5mm; border: 2px solid #428eb8; }
        .page { padding: 14mm 12mm 0; }

        /* ─── Masthead ─── */
        .header { text-align: center; }
        .header img.logo { height: 26mm; width: auto; display: block; margin: 0 auto 1.4mm; }
        .school-name { font-family: 'Merriweather', 'PT Serif', serif; font-size: 22px; letter-spacing: .2px; line-height: 1.05; }
        .school-address { font-size: 10.6px; line-height: 1.25; margin-top: .8mm; }
        .doc-title { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 17px; letter-spacing: 3px; margin-top: 4.5mm; }
        .doc-sub { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 12px; margin-top: 1.4mm; margin-bottom: 6mm; }
        .doc-sub .no { color: #b91c1c; }

        /* ─── The Report Card's tables: grey rules, a heading row, four quarters ─── */
        table.info { table-layout: fixed; margin-bottom: 7mm; }
        table.info th { border: 1px solid #aaaaaa; background: #eaf3f9; font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
                        font-weight: normal; font-size: 11.4px; letter-spacing: .6px; text-transform: uppercase; padding: 8px 10px; text-align: left; }
        table.info td { border: 1px solid #aaaaaa; padding: 8.5px 10px; font-size: 11.8px; vertical-align: top; }
        table.info td.label { width: 25%; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        table.info td.value { width: 25%; }
        table.info td.big { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 13px; }

        .note { text-align: center; font-size: 10px; color: #374151; margin-top: 0; }

        /* ─── Issue line (no borders), then the signatures near the foot ─── */
        table.issue-row { margin-top: 6mm; }
        table.issue-row td { font-size: 11.4px; padding: 0; }
        table.issue-row td.right { text-align: right; }
        .page-foot { position: fixed; left: 12mm; right: 12mm; bottom: 13mm; }
        table.sign-row { table-layout: fixed; }
        table.sign-row td { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 13px; padding: 0; }
        table.sign-row td.mid { text-align: center; }
        table.sign-row td.right { text-align: right; }
    </style>
</head>
<body>
    @php
        $st = $pass->studentDetail;
        $or = fn ($v) => trim((string) $v) !== '' ? $v : '—';
        $class = trim(($pass->standard?->name ?? '') . ($pass->section?->name ? ' - ' . $pass->section->name : ''));
        $takenBy = trim($pass->picked_by_name . ($pass->picked_by_relation ? ' (' . $pass->picked_by_relation . ')' : ''));
        $website = trim((string) ($website ?? ''));
        $affiliation = trim((string) ($affiliation ?? ($org?->affiliation_no ?? '')));
    @endphp

    {{-- ─── Above the frame: affiliation number left, website right ─── --}}
    <table class="topbar">
        <tr>
            <td>@if ($affiliation !== '')Affiliation No: {{ $affiliation }}@endif</td>
            <td class="right">@if ($website !== '')website: {{ $website }}@endif</td>
        </tr>
    </table>

    <div class="frame"></div>

    {{-- ─── Signatures: bold, no rule above them, near the foot ─── --}}
    <div class="page-foot">
        <table class="sign-row">
            <tr>
                <td>Parent / Guardian</td>
                <td class="mid">Class Teacher</td>
                <td class="right">Principal</td>
            </tr>
        </table>
    </div>

    <div class="page">
        {{-- ─── Masthead ─── --}}
        <div class="header">
            @if ($logo)
                <img class="logo" src="{{ $logo }}" alt="">
            @endif
            <div class="school-name">{{ $org?->name }}</div>
            @if (trim((string) $org?->address) !== '')
                <div class="school-address">{{ $org->address }}</div>
            @endif
            @if ($contact !== '')
                <div class="school-address">{{ $contact }}</div>
            @endif
            <div class="doc-title">GATE PASS</div>
            <div class="doc-sub">Pass No: <span class="no">{{ $pass->number() }}</span>  ·  Date: {{ $pass->out_at->format('d/m/Y') }}</div>
        </div>

        {{-- ─── The student ─── --}}
        <table class="info">
            <tr><th colspan="4">Student Details</th></tr>
            <tr>
                <td class="label">Student Name:</td>
                <td class="value big" colspan="3">{{ $or($st?->full_name) }}</td>
            </tr>
            <tr>
                <td class="label">Class/Section:</td>
                <td class="value">{{ $or($class) }}</td>
                <td class="label">Admission No:</td>
                <td class="value">{{ $or($st?->admission_no) }}</td>
            </tr>
            <tr>
                <td class="label">Father's Name:</td>
                <td class="value">{{ $or($st?->father_name) }}</td>
                <td class="label">Roll No:</td>
                <td class="value">{{ $or($st?->roll_no) }}</td>
            </tr>
        </table>

        {{-- ─── Going out ─── --}}
        <table class="info">
            <tr><th colspan="4">Going Out</th></tr>
            <tr>
                <td class="label">Reason:</td>
                <td class="value" colspan="3">{{ $pass->reason }}</td>
            </tr>
            <tr>
                <td class="label">Time Out:</td>
                <td class="value big">{{ $pass->out_at->format('h:i A') }}</td>
                <td class="label">Came Back:</td>
                <td class="value">{{ $pass->returned_at ? $pass->returned_at->format('h:i A') : '—' }}</td>
            </tr>
            <tr>
                <td class="label">Taken By:</td>
                <td class="value" colspan="3">{{ $or($takenBy) }}</td>
            </tr>
            <tr>
                <td class="label">Mobile:</td>
                <td class="value">{{ $or($pass->picked_by_mobile) }}</td>
                <td class="label">Issued By:</td>
                <td class="value">{{ $or($pass->addedBy?->name) }}</td>
            </tr>
            @if (trim((string) $pass->remarks) !== '')
                <tr>
                    <td class="label">Remarks:</td>
                    <td class="value" colspan="3">{{ $pass->remarks }}</td>
                </tr>
            @endif
        </table>

        <div class="note">This pass is to be shown at the school gate and kept by the gatekeeper.</div>

        <table class="issue-row">
            <tr>
                <td>Issue Date: {{ $pass->out_at->format('d/m/Y') }}</td>
                <td class="right">Pass No: <span class="sb">{{ $pass->number() }}</span></td>
            </tr>
        </table>
    </div>
</body>
</html>
