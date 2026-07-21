@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $money   = fn($v) => 'Rs. ' . number_format((float) ($v ?? 0), 2);
    $val     = fn($v) => ($v !== null && $v !== '') ? $v : '—';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! $fontCss !!}

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            color: #1f2933;
            font-size: 11.5px;
            line-height: 1.5;
        }
        .page { padding: 30px 38px 26px; }

        /* ── Centered school masthead ── */
        .masthead { text-align: center; padding-bottom: 14px; }
        .masthead .logo { height: 74px; width: auto; margin: 0 auto 8px; display: block; }
        .school-name {
            font-family: 'PT Serif Bold', serif;
            font-size: 25px;
            color: #1e1b4b;
            letter-spacing: .3px;
            margin: 2px 0 4px;
        }
        .school-line { font-size: 10.5px; color: #6b7280; margin: 1px 0; }
        .school-line .sep { color: #c7cad1; padding: 0 5px; }

        .rule { border: 0; border-top: 2px solid #4f46e5; margin: 6px 0 0; }
        .rule-soft { border: 0; border-top: 1px solid #e5e7eb; margin: 0 0 16px; }

        /* ── Title ribbon ── */
        .ribbon {
            background: #4f46e5;
            color: #fff;
            text-align: center;
            font-family: 'Poppins Bold', sans-serif;
            font-size: 14px;
            letter-spacing: 4px;
            padding: 8px 0;
            border-radius: 6px;
            margin: 16px 0 18px;
        }

        /* ── Section headers ── */
        .section-title {
            font-family: 'Poppins SemiBold', sans-serif;
            font-size: 11.5px;
            color: #4338ca;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            border-bottom: 1.5px solid #e0e0f5;
            padding: 0 0 5px 0;
            margin: 18px 0 9px;
        }

        /* ── Detail grids ── */
        table.details { width: 100%; border-collapse: collapse; }
        table.details td { padding: 4px 6px; vertical-align: top; }
        td.label { color: #6b7280; width: 20%; font-size: 10.5px; }
        td.value { color: #111827; font-family: 'Poppins SemiBold', sans-serif; width: 30%; font-size: 11px; }

        .badge { display: inline-block; padding: 1px 9px; border-radius: 10px; font-size: 9.5px;
                 font-family: 'Poppins SemiBold', sans-serif; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }

        /* ── Fee tables ── */
        table.fee { width: 100%; border-collapse: collapse; margin-top: 2px; }
        table.fee th {
            background: #eef2ff; color: #3730a3; text-align: left;
            font-family: 'Poppins SemiBold', sans-serif; font-size: 10px;
            text-transform: uppercase; letter-spacing: .5px; padding: 7px 10px;
            border: 1px solid #e0e0f5;
        }
        table.fee th.amt, table.fee td.amt { text-align: right; }
        table.fee td { padding: 7px 10px; border: 1px solid #ececef; font-size: 11px; }
        table.fee tr.total td {
            background: #4f46e5; color: #fff; font-family: 'Poppins Bold', sans-serif;
            font-size: 12px; border: 1px solid #4f46e5;
        }
        .muted-note { font-size: 9.5px; color: #9ca3af; margin-top: 4px; }

        /* ── Instructions ── */
        ol.instructions { margin: 4px 0 0 16px; padding: 0; }
        ol.instructions li { font-size: 10.5px; color: #374151; margin-bottom: 5px; line-height: 1.5; }

        /* ── Signatures ── */
        table.sign { width: 100%; margin-top: 42px; }
        table.sign td { width: 50%; text-align: center; font-size: 10.5px; color: #374151; }
        .sign-line { border-top: 1px solid #9ca3af; margin: 0 24px; padding-top: 5px;
                     font-family: 'Poppins SemiBold', sans-serif; }

        .footer { margin-top: 18px; text-align: center; font-size: 9px; color: #a1a1aa;
                  font-family: 'PT Serif', serif; font-style: italic; }
    </style>
</head>
<body>
<div class="page">

    {{-- ═══ Centered school masthead ═══ --}}
    <div class="masthead">
        @if (!empty($school['logo']))
            <img src="{{ $school['logo'] }}" class="logo" alt="">
        @endif
        <div class="school-name">{{ $school['name'] ?: 'School' }}</div>
        @if (!empty($school['address']))
            <div class="school-line">{{ $school['address'] }}</div>
        @endif
        <div class="school-line">
            @if (!empty($school['email'])){{ $school['email'] }}@endif
            @if (!empty($school['contact']))<span class="sep">|</span>{{ $school['contact'] }}@endif
            @if (!empty($school['website']))<span class="sep">|</span>{{ $school['website'] }}@endif
        </div>
    </div>
    <hr class="rule">

    <div class="ribbon">ADMISSION FORM</div>

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
            <td class="label">Admission Date</td><td class="value">{{ $fmtDate($enquiry->created_at) }}</td>
            <td class="label">Status</td>
            <td class="value">
                @if (($enquiry->status ?? '') === 'updated')
                    <span class="badge badge-green">Updated</span>
                @else
                    <span class="badge badge-amber">Pending</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td class="value" colspan="3" style="font-family:'Poppins',sans-serif;font-weight:normal;">{{ $val($enquiry->address) }}</td>
        </tr>
    </table>

    {{-- ═══ Class fee structure ═══ --}}
    <div class="section-title">Fee Structure @if ($feeYear)<span style="color:#9ca3af;font-family:'Poppins',sans-serif;text-transform:none;letter-spacing:0;">· {{ $enquiry->standard->name ?? 'Class' }} · {{ $feeYear }}</span>@endif</div>
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
