@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $money   = fn($v) => 'Rs. ' . number_format((float) ($v ?? 0), 2);
    $val     = fn($v) => ($v !== null && $v !== '') ? $v : '—';
    $documents = $documents ?? [];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! $fontCss !!}

        @page { margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            color: #334155;
            font-size: 11px;
            line-height: 1.5;
        }
        .page { padding: 34px 40px 30px; }

        /* ── Masthead ── */
        table.mast { width: 100%; border-collapse: collapse; }
        table.mast td { vertical-align: middle; }
        .mast-logo { width: 66px; padding-right: 14px; }
        .mast-logo img { height: 60px; width: auto; display: block; }
        .school-name {
            font-family: 'PT Serif Bold', serif;
            font-size: 23px;
            color: #0f172a;
            letter-spacing: .2px;
            line-height: 1.15;
            margin: 0 0 3px;
        }
        .school-sub { font-size: 9.5px; color: #64748b; line-height: 1.45; }
        .school-sub .sep { color: #cbd5e1; padding: 0 6px; }
        .mast-photo { width: 92px; text-align: right; }
        .photo-box {
            width: 82px; height: 100px; float: right;
            border: 1px dashed #94a3b8; border-radius: 4px;
            color: #94a3b8; font-size: 8.5px; text-align: center;
            line-height: 1.4; padding-top: 38px;
        }

        .rule { border: 0; border-top: 2px solid #1d4ed8; margin: 12px 0 0; }
        .rule-thin { border: 0; border-top: 1px solid #e2e8f0; margin: 3px 0 0; }

        /* ── Title band ── */
        table.title-band { width: 100%; border-collapse: collapse; margin: 18px 0 6px; }
        .title-band .t-main {
            font-family: 'Poppins Bold', sans-serif;
            font-size: 15px; letter-spacing: 3px; color: #0f172a;
        }
        .title-band .t-meta { text-align: right; font-size: 9.5px; color: #64748b; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 11px; font-size: 9px;
                 font-family: 'Poppins SemiBold', sans-serif; letter-spacing: .3px; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }

        /* ── Section headers ── */
        .section-title {
            font-family: 'Poppins SemiBold', sans-serif;
            font-size: 10px; color: #1d4ed8;
            text-transform: uppercase; letter-spacing: 1.4px;
            margin: 20px 0 8px; padding-left: 9px;
            border-left: 3px solid #1d4ed8;
        }

        /* ── Detail grid ── */
        table.details { width: 100%; border-collapse: collapse; }
        table.details td { padding: 5px 8px; vertical-align: top; border-bottom: 1px solid #f1f5f9; }
        td.label { color: #64748b; width: 19%; font-size: 9.5px; text-transform: uppercase; letter-spacing: .4px; }
        td.value { color: #0f172a; font-family: 'Poppins SemiBold', sans-serif; width: 31%; font-size: 11px; }

        /* ── Fee table ── */
        table.fee { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.fee th {
            background: #f8fafc; color: #475569; text-align: left;
            font-family: 'Poppins SemiBold', sans-serif; font-size: 9px;
            text-transform: uppercase; letter-spacing: .6px; padding: 8px 12px;
            border-bottom: 1.5px solid #e2e8f0;
        }
        table.fee th.amt, table.fee td.amt { text-align: right; }
        table.fee td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; font-size: 10.5px; color: #334155; }
        table.fee tr.total td {
            background: #0f172a; color: #fff; font-family: 'Poppins Bold', sans-serif;
            font-size: 11.5px; border: 0;
        }
        table.fee tr.total td.amt { color: #fff; }
        .muted-note { font-size: 9.5px; color: #94a3b8; margin-top: 4px; padding-left: 2px; }

        /* ── Documents ── */
        ul.docs { list-style: none; margin: 2px 0 0; padding: 0; }
        ul.docs li {
            display: inline-block; margin: 0 6px 6px 0; padding: 4px 11px;
            background: #f1f5f9; border: 1px solid #e2e8f0; border-radius: 12px;
            font-size: 9.5px; color: #475569;
        }

        /* ── Instructions ── */
        ol.instructions { margin: 4px 0 0 15px; padding: 0; }
        ol.instructions li { font-size: 9.8px; color: #475569; margin-bottom: 5px; line-height: 1.5; }

        /* ── Signatures ── */
        table.sign { width: 100%; margin-top: 46px; border-collapse: collapse; }
        table.sign td { width: 50%; text-align: center; font-size: 9.5px; color: #475569; padding: 0 18px; }
        .sign-line { border-top: 1px solid #94a3b8; margin: 0 10px; padding-top: 6px;
                     font-family: 'Poppins SemiBold', sans-serif; color: #334155; }

        .footer { margin-top: 22px; padding-top: 10px; border-top: 1px solid #e2e8f0;
                  text-align: center; font-size: 8.5px; color: #94a3b8;
                  font-family: 'PT Serif', serif; font-style: italic; }
    </style>
</head>
<body>
<div class="page">

    {{-- ═══ Masthead ═══ --}}
    <table class="mast">
        <tr>
            @if (!empty($school['logo']))
                <td class="mast-logo"><img src="{{ $school['logo'] }}" alt=""></td>
            @endif
            <td class="mast-info">
                <div class="school-name">{{ $school['name'] ?: 'School' }}</div>
                @if (!empty($school['address']))
                    <div class="school-sub">{{ $school['address'] }}</div>
                @endif
                <div class="school-sub">
                    @if (!empty($school['contact'])){{ $school['contact'] }}@endif
                    @if (!empty($school['email']))<span class="sep">|</span>{{ $school['email'] }}@endif
                    @if (!empty($school['website']))<span class="sep">|</span>{{ $school['website'] }}@endif
                </div>
            </td>
            <td class="mast-photo">
                <div class="photo-box">Affix<br>Passport<br>Photo</div>
            </td>
        </tr>
    </table>
    <hr class="rule">
    <hr class="rule-thin">

    {{-- ═══ Title band ═══ --}}
    <table class="title-band">
        <tr>
            <td class="t-main">ADMISSION FORM</td>
            <td class="t-meta">
                Date: {{ $fmtDate($enquiry->created_at) }}
                &nbsp;&nbsp;
                @if (($enquiry->status ?? '') === 'updated')
                    <span class="badge badge-green">Updated</span>
                @else
                    <span class="badge badge-amber">Pending</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ═══ Student details ═══ --}}
    <div class="section-title">Student Details</div>
    <table class="details">
        <tr>
            <td class="label">Student Name</td><td class="value">{{ $val($enquiry->student_name) }}</td>
            <td class="label">Class</td><td class="value">{{ $val($enquiry->standard->name ?? null) }}</td>
        </tr>
        <tr>
            <td class="label">Guardian Name</td><td class="value">{{ $val($enquiry->guardian_name) }}</td>
            <td class="label">Stream</td><td class="value">{{ $val($enquiry->stream) }}</td>
        </tr>
        <tr>
            <td class="label">Mobile</td><td class="value">{{ $val($enquiry->mobile) }}</td>
            <td class="label">Email</td><td class="value">{{ $val($enquiry->email) }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td class="value" colspan="3" style="font-family:'Poppins',sans-serif;">{{ $val($enquiry->address) }}</td>
        </tr>
    </table>

    {{-- ═══ Class fee structure ═══ --}}
    <div class="section-title">Fee Structure @if ($feeYear)<span style="color:#94a3b8;font-family:'Poppins',sans-serif;text-transform:none;letter-spacing:0;">— {{ $enquiry->standard->name ?? 'Class' }} · {{ $feeYear }}</span>@endif</div>
    @if (count($feeRows))
        <table class="fee">
            <thead>
                <tr>
                    <th>Fee Component</th>
                    <th class="amt">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($feeRows as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td class="amt">{{ $money($row['amount']) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Fee</td>
                    <td class="amt">{{ $money($feeTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="muted-note">No fee structure configured for this class yet.</p>
    @endif

    {{-- ═══ Admission fee details ═══ --}}
    <div class="section-title">Admission Fee Details</div>
    <table class="details">
        <tr>
            <td class="label">Admission Fee</td><td class="value">{{ $money($enquiry->admission_fee) }}</td>
            <td class="label">Payment Mode</td><td class="value" style="text-transform:capitalize;">{{ $val($enquiry->payment_mode) }}</td>
        </tr>
        <tr>
            <td class="label">Collected By</td><td class="value">{{ $val($enquiry->collected_by) }}</td>
            <td class="label">Collected On</td><td class="value">{{ $fmtDate($enquiry->fee_collected_at) }}</td>
        </tr>
    </table>

    {{-- ═══ Documents attached ═══ --}}
    @if (count($documents))
        <div class="section-title">Documents Attached</div>
        <ul class="docs">
            @foreach ($documents as $doc)
                <li>{{ $doc }}</li>
            @endforeach
        </ul>
    @endif

    {{-- ═══ Instructions ═══ --}}
    <div class="section-title">Instructions</div>
    <ol class="instructions">
        @foreach ($instructions as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ol>

    {{-- ═══ Signatures ═══ --}}
    <table class="sign">
        <tr>
            <td><div class="sign-line">Parent / Guardian Signature</div></td>
            <td><div class="sign-line">Principal / Authorised Signatory</div></td>
        </tr>
    </table>

    <div class="footer">
        This is a computer-generated admission form for {{ $school['name'] ?: 'the school' }} · Generated on {{ now()->format('d M Y, g:i A') }}
    </div>
</div>
</body>
</html>
