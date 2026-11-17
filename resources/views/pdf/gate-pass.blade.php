{{--
    More → Gatepass's slip (App\Support\GatePassSlip): one A5 page the gate
    keeps — the school at the head, GATE PASS with its number and date, the
    student, why and when they went, who took them, and three signatures.
    Tables, because dompdf has no flexbox. Poppins' line-height is set low on
    purpose: dompdf multiplies it by the face's full height (~1.54em).

    In scope: $pass (with organization, studentDetail, standard, section,
    addedBy), $org, $logo, $contact, $fontCss.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Gate Pass {{ $pass->number() }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        @page { size: A5 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 9.5px; color: #1f2937; line-height: .9; }
        table { width: 100%; border-collapse: collapse; }
        .sb { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }

        .frame    { position: fixed; top: 5mm; left: 5mm; right: 5mm; bottom: 5mm; border: 1.1px solid #1e3a5f; }
        .frame-in { position: fixed; top: 6.1mm; left: 6.1mm; right: 6.1mm; bottom: 6.1mm; border: 0.5px solid #b8c4d6; }
        .page { padding: 10mm 11mm 0; }

        table.mast { table-layout: fixed; }
        table.mast td { padding: 0; vertical-align: middle; }
        table.mast td.mid { text-align: center; }
        .logo { height: 15mm; width: auto; }
        .school { font-family: 'Merriweather Bold', 'PT Serif Bold', serif; font-size: 14px; color: #1e3a5f; line-height: .85; }
        .sub { font-size: 7.3px; color: #4b5563; margin-top: 1.2px; }

        .title { margin-top: 3.5mm; padding: 1.6mm 0 1.5mm; text-align: center;
                 border-top: 0.9px solid #1e3a5f; border-bottom: 0.9px solid #1e3a5f; }
        .title .t { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 13px; letter-spacing: 3px; color: #1e3a5f; }

        table.meta { margin-top: 3mm; }
        table.meta td { font-size: 9px; padding: 0; }
        table.meta td.r { text-align: right; }
        .no { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 12px; color: #b91c1c; }

        .sec { margin-top: 4mm; font-size: 6.8px; letter-spacing: 1.2px; text-transform: uppercase; color: #1e3a5f;
               font-family: 'Poppins SemiBold', 'Poppins', sans-serif; padding-bottom: 1mm; border-bottom: 0.6px solid #c7d2e0; }
        table.kv { table-layout: fixed; }
        table.kv td { padding: 1.5mm 0; border-bottom: 0.5px solid #eef0f3; vertical-align: top; }
        table.kv td.k { width: 30%; font-size: 7px; letter-spacing: .5px; text-transform: uppercase; color: #6b7280; padding-top: 1.8mm; }
        table.kv td.v { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 9.5px; color: #111827; }
        table.kv td.v.big { font-size: 11px; }

        .foot { position: fixed; left: 11mm; right: 11mm; bottom: 11mm; }
        .note { text-align: center; font-size: 6.8px; color: #6b7280; padding-bottom: 12mm; }
        table.sign { table-layout: fixed; }
        table.sign td { text-align: center; font-size: 7.6px; color: #374151; padding: 0 3mm; }
        .sign-line { border-top: 0.7px solid #374151; padding-top: 1mm; }
    </style>
</head>
<body>
    @php
        $st = $pass->studentDetail;
        $or = fn ($v) => trim((string) $v) !== '' ? $v : '—';
        $class = trim(($pass->standard?->name ?? '') . ($pass->section?->name ? ' - ' . $pass->section->name : ''));
        $takenBy = trim($pass->picked_by_name . ($pass->picked_by_relation ? ' (' . $pass->picked_by_relation . ')' : ''));
    @endphp

    <div class="frame"></div>
    <div class="frame-in"></div>

    <div class="foot">
        <div class="note">This pass is to be shown at the school gate and kept by the gatekeeper.</div>
        <table class="sign">
            <tr>
                <td><div class="sign-line">Parent / Guardian</div></td>
                <td><div class="sign-line">Class Teacher</div></td>
                <td><div class="sign-line">Principal</div></td>
            </tr>
        </table>
    </div>

    <div class="page">
        <table class="mast">
            <tr>
                <td style="width: 15%;">
                    @if ($logo)
                        <img class="logo" src="{{ $logo }}" alt="">
                    @endif
                </td>
                <td class="mid" style="width: 70%;">
                    <div class="school">{{ $org?->name }}</div>
                    @if (trim((string) $org?->address) !== '')
                        <div class="sub">{{ $org->address }}</div>
                    @endif
                    @if ($contact !== '')
                        <div class="sub">{{ $contact }}</div>
                    @endif
                </td>
                <td style="width: 15%;"></td>
            </tr>
        </table>

        <div class="title"><div class="t">GATE PASS</div></div>

        <table class="meta">
            <tr>
                <td>Pass No: <span class="no">{{ $pass->number() }}</span></td>
                <td class="r">Date: <span class="sb">{{ $pass->out_at->format('d/m/Y') }}</span></td>
            </tr>
        </table>

        <div class="sec">Student</div>
        <table class="kv">
            <tr><td class="k">Name</td><td class="v big">{{ $or($st?->full_name) }}</td></tr>
            <tr><td class="k">Class</td><td class="v">{{ $or($class) }}</td></tr>
            <tr><td class="k">Admission No</td><td class="v">{{ $or($st?->admission_no) }}{{ $st?->roll_no ? '   ·   Roll No ' . $st->roll_no : '' }}</td></tr>
            <tr><td class="k">Father's Name</td><td class="v">{{ $or($st?->father_name) }}</td></tr>
        </table>

        <div class="sec">Going Out</div>
        <table class="kv">
            <tr><td class="k">Reason</td><td class="v">{{ $pass->reason }}</td></tr>
            <tr><td class="k">Time Out</td><td class="v big">{{ $pass->out_at->format('h:i A') }}</td></tr>
            <tr><td class="k">Taken By</td><td class="v">{{ $or($takenBy) }}</td></tr>
            <tr><td class="k">Mobile</td><td class="v">{{ $or($pass->picked_by_mobile) }}</td></tr>
            @if ($pass->returned_at)
                <tr><td class="k">Came Back</td><td class="v">{{ $pass->returned_at->format('h:i A') }}</td></tr>
            @endif
            @if (trim((string) $pass->remarks) !== '')
                <tr><td class="k">Remarks</td><td class="v">{{ $pass->remarks }}</td></tr>
            @endif
            <tr><td class="k">Issued By</td><td class="v">{{ $or($pass->addedBy?->name) }}</td></tr>
        </table>
    </div>
</body>
</html>
