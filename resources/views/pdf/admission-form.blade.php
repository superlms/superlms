@php
    $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
    $money   = fn($v) => '₹ ' . number_format((float) ($v ?? 0), 2);
    $val     = fn($v) => ($v !== null && $v !== '') ? $v : '—';
    $logoUrl = (!empty($org?->logo) && \Illuminate\Support\Str::startsWith($org->logo, ['http://', 'https://'])) ? $org->logo : null;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 12px; margin: 0; }
        .page { padding: 28px 34px; }
        .school { border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 4px; }
        .school-table { width: 100%; }
        .school-table td { vertical-align: middle; }
        .logo { width: 68px; height: 68px; }
        .school-name { font-size: 20px; font-weight: bold; color: #111827; }
        .school-meta { font-size: 10.5px; color: #6b7280; margin-top: 2px; line-height: 1.5; }
        .title-bar { background: #1d4ed8; color: #fff; text-align: center; font-size: 14px;
                     font-weight: bold; letter-spacing: 2px; padding: 7px; margin: 14px 0 16px; border-radius: 3px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1d4ed8; text-transform: uppercase;
                         letter-spacing: 1px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; margin: 14px 0 8px; }
        table.details { width: 100%; border-collapse: collapse; }
        table.details td { padding: 5px 6px; font-size: 11.5px; vertical-align: top; }
        td.label { color: #6b7280; width: 22%; }
        td.value { color: #111827; font-weight: bold; width: 28%; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .badge-green { background: #dcfce7; color: #15803d; }
        .badge-amber { background: #fef3c7; color: #b45309; }
        .fee-box { border: 1px solid #e5e7eb; border-radius: 4px; padding: 4px 8px; margin-top: 4px; }
        ol.instructions { margin: 6px 0 0 16px; padding: 0; }
        ol.instructions li { font-size: 11px; color: #374151; margin-bottom: 5px; line-height: 1.5; }
        .sign-table { width: 100%; margin-top: 46px; }
        .sign-table td { width: 50%; text-align: center; font-size: 11px; color: #374151; padding-top: 4px; }
        .sign-line { border-top: 1px solid #9ca3af; margin: 0 20px; padding-top: 4px; }
        .foot-note { margin-top: 20px; font-size: 9.5px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
<div class="page">

    {{-- School header --}}
    <div class="school">
        <table class="school-table">
            <tr>
                @if ($logoUrl)
                    <td style="width: 78px;"><img src="{{ $logoUrl }}" class="logo" alt=""></td>
                @endif
                <td>
                    <div class="school-name">{{ $org->name ?? 'School' }}</div>
                    <div class="school-meta">
                        @if (!empty($org->address)){{ $org->address }}<br>@endif
                        @if (!empty($org->mobile_number))Phone: {{ $org->mobile_number }}@endif
                        @if (!empty($org->email)) &nbsp;|&nbsp; {{ $org->email }}@endif
                        @if (!empty($org->education_board) || !empty($org->medium) || !empty($org->school_code))
                            <br>
                            @if (!empty($org->education_board))Board: {{ $org->education_board }}@endif
                            @if (!empty($org->medium)) &nbsp;|&nbsp; Medium: {{ $org->medium }}@endif
                            @if (!empty($org->school_code)) &nbsp;|&nbsp; Code: {{ $org->school_code }}@endif
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="title-bar">ADMISSION FORM</div>

    {{-- Student details --}}
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
            <td class="value" colspan="3" style="font-weight: normal;">{{ $val($enquiry->address) }}</td>
        </tr>
    </table>

    {{-- Fee details --}}
    <div class="section-title">Fee Details</div>
    <table class="details">
        <tr>
            <td class="label">Admission Fee</td><td class="value">{{ $money($enquiry->admission_fee) }}</td>
            <td class="label">Amount Collected</td><td class="value">{{ $money($enquiry->collected_amount) }}</td>
        </tr>
        <tr>
            <td class="label">Payment Mode</td><td class="value" style="text-transform: capitalize;">{{ $val($enquiry->payment_mode) }}</td>
            <td class="label">Collected On</td><td class="value">{{ $fmtDate($enquiry->fee_collected_at) }}</td>
        </tr>
        <tr>
            <td class="label">Collected By</td><td class="value">{{ $val($enquiry->collected_by) }}</td>
            <td class="label"></td><td class="value"></td>
        </tr>
    </table>

    {{-- Instructions --}}
    <div class="section-title">Instructions</div>
    <ol class="instructions">
        @foreach ($instructions as $line)
            <li>{{ $line }}</li>
        @endforeach
    </ol>

    {{-- Signatures --}}
    <table class="sign-table">
        <tr>
            <td><div class="sign-line">Parent / Guardian Signature</div></td>
            <td><div class="sign-line">Principal / Authorised Signatory</div></td>
        </tr>
    </table>

    <div class="foot-note">
        This is a computer-generated admission form for {{ $org->name ?? 'the school' }}.
        Generated on {{ now()->format('d M Y, g:i A') }}.
    </div>
</div>
</body>
</html>
