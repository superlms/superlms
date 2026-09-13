{{--
    Transport fee receipt as a PDF (dompdf) — the student app's download. The
    same sheet as admin/transport-receipt, laid out in tables because dompdf
    has no flexbox.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transport Fee Receipt — {{ $payment->receipt_number }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        /* No `* { margin: 0 }` here — that wipes the @page margins. */
        @page { margin: 26px 28px 28px 28px; }
        body { margin: 0; font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 9px; color: #111827; line-height: 1.3; }
        /* dompdf collapses font-weight per family, so each weight is its own
           family name (see App\Support\PdfFonts). */
        .semi { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        .bold { font-family: 'Poppins Bold', 'Poppins', sans-serif; }

        table { width: 100%; border-collapse: collapse; }
        .num { text-align: right; }

        /* Masthead — logo, name, address, contacts, all centred */
        .masthead { text-align: center; border-bottom: 1px solid #111827; padding-bottom: 6px; }
        .masthead .logo { height: 40px; width: auto; }
        .school { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 13px; letter-spacing: .3px; text-transform: uppercase; }
        .addr, .contact { font-size: 7.5px; color: #4b5563; }

        .doc-title { text-align: center; margin-top: 7px; font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
                     font-size: 8.5px; letter-spacing: 2px; text-transform: uppercase; }
        .doc-no { text-align: center; font-size: 8px; color: #4b5563; }

        /* Section heads */
        .sec { font-size: 7px; letter-spacing: 1.2px; text-transform: uppercase; color: #6b7280;
               margin: 9px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }

        /* Key/value pairs, two to a row */
        table.kv td { padding: 1.5px 0; vertical-align: top; }
        table.kv td.k { width: 17%; font-size: 6.5px; letter-spacing: .6px; text-transform: uppercase; color: #6b7280; padding-top: 3px; }
        table.kv td.v { width: 33%; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 8.5px; padding-right: 8px; }

        /* This payment — a strip of cells */
        table.pay { border: 1px solid #e5e7eb; margin-top: 3px; }
        table.pay td { width: 20%; padding: 4px 5px; border-right: 1px solid #e5e7eb; vertical-align: top; }
        table.pay td.last { border-right: 0; }
        .lbl { font-size: 6px; letter-spacing: .7px; text-transform: uppercase; color: #6b7280; }
        .val { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 8.5px; }
        .remark { font-size: 7.5px; color: #4b5563; margin-top: 4px; }

        /* Amount */
        table.amt { border: 1px solid #111827; margin-top: 5px; }
        table.amt td { padding: 5px 8px; vertical-align: middle; }
        table.amt td.chip { font-size: 8px; color: #4b5563; }
        table.amt td.grand { text-align: right; font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 15px; }
        table.amt td.words { font-size: 7.5px; color: #374151; border-top: 1px solid #e5e7eb; background: #f9fafb; }
        .words-lbl { font-size: 6px; letter-spacing: .7px; text-transform: uppercase; color: #6b7280; }

        /* The year on the route */
        table.dt th { font-size: 6.5px; letter-spacing: .4px; text-transform: uppercase; color: #6b7280;
                      font-weight: normal; text-align: left; padding: 2px 0; border-bottom: 1px solid #e5e7eb; }
        table.dt th.num { text-align: right; }
        table.dt td { padding: 3px 0; font-size: 8px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        .muted { color: #6b7280; }

        /* Foot */
        table.foot { margin-top: 22px; border-top: 1px solid #e5e7eb; }
        table.foot td { padding-top: 5px; vertical-align: bottom; }
        table.foot td.note { width: 60%; font-size: 6.5px; color: #6b7280; line-height: 1.4; }
        table.foot td.sign { text-align: center; font-size: 7.5px; }
        .sign-line { width: 110px; border-top: 1px solid #111827; margin: 0 auto 2px; }
        .foot-num { text-align: center; font-size: 6.5px; letter-spacing: .4px; color: #6b7280; margin-top: 8px; }
    </style>
</head>
<body>
@php
    $logo = $org->logo ?? null;
    if ($logo && !preg_match('#^(https?:|data:)#i', $logo)) {
        $logo = url($logo);
    }
    $contactBits = array_values(array_filter([$org->mobile_number ?? null, $org->email ?? null]));
    $paidOn = $payment->payment_date;
    $balance = max(0, $yearTotal - $paidSoFar);
@endphp

{{-- ══════════ MASTHEAD ══════════ --}}
<div class="masthead">
    @if ($logo)
        <img src="{{ $logo }}" class="logo" alt="">
    @endif
    <div class="school">{{ $org->name ?? 'School' }}</div>
    @if (!empty($org?->address))
        <div class="addr">{{ $org->address }}</div>
    @endif
    @if ($contactBits)
        <div class="contact">{{ implode(' · ', $contactBits) }}</div>
    @endif
</div>
<div class="doc-title">Transport Fee Receipt</div>
<div class="doc-no">Receipt No. {{ $payment->receipt_number }}</div>

{{-- ══════════ STUDENT ══════════ --}}
<div class="sec">Student</div>
<table class="kv">
    <tr>
        <td class="k">Name</td>
        <td class="v">{{ $student->user->name ?? $student->full_name ?? '—' }}</td>
        <td class="k">Class</td>
        <td class="v">{{ $student->standard->name ?? '—' }}{{ $student && $student->section ? ' · ' . $student->section->name : '' }}</td>
    </tr>
    <tr>
        <td class="k">Father</td>
        <td class="v">{{ $student->father_name ?? null ?: '—' }}</td>
        <td class="k">Adm No.</td>
        <td class="v">{{ $student->admission_no ?? null ?: '—' }}</td>
    </tr>
    <tr>
        <td class="k">Phone</td>
        <td class="v">{{ $student->phone ?? null ?: '—' }}</td>
        <td class="k">Roll No.</td>
        <td class="v">{{ $student->roll_no ?? null ?: '—' }}</td>
    </tr>
</table>

{{-- ══════════ THIS PAYMENT ══════════ --}}
<div class="sec">This Payment</div>
<table class="pay">
    <tr>
        <td>
            <div class="lbl">Date</div>
            <div class="val">{{ $paidOn ? $paidOn->format('d M Y') : '—' }}</div>
        </td>
        <td>
            <div class="lbl">Day</div>
            <div class="val">{{ $paidOn ? $paidOn->format('l') : '—' }}</div>
        </td>
        <td>
            <div class="lbl">Type</div>
            <div class="val">{{ $payType }}</div>
        </td>
        <td>
            <div class="lbl">Mode</div>
            <div class="val">{{ $payMode }}</div>
        </td>
        <td class="last">
            <div class="lbl">Submitted By</div>
            <div class="val">{{ $submittedBy }}</div>
        </td>
    </tr>
</table>
@if ($payment->remark)
    <div class="remark"><span class="semi">Remark —</span> {{ $payment->remark }}</div>
@endif

<table class="amt">
    <tr>
        <td class="chip">Transport Fee @if ($payment->academic_year)<span class="semi">{{ $payment->academic_year }}</span>@endif</td>
        <td class="grand">₹{{ number_format((float) $payment->amount, 2) }}</td>
    </tr>
    <tr>
        <td class="words" colspan="2">
            <div class="words-lbl">Amount in Words</div>
            {{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}
        </td>
    </tr>
</table>

{{-- ══════════ TRANSPORT STANDING ══════════ --}}
<div class="sec">Transport{{ $route ? ' — ' . $route->route_name : '' }}</div>
@if ($route)
    <table class="dt">
        <thead>
            <tr>
                <th style="width: 30%;">Route</th>
                <th class="num" style="width: 14%;">Per Month</th>
                <th class="num" style="width: 12%;">Months</th>
                <th class="num" style="width: 15%;">Year Total</th>
                <th class="num" style="width: 14%;">Paid</th>
                <th class="num" style="width: 15%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $route->route_name }}</td>
                <td class="num">{{ number_format((float) $route->monthly_fee, 2) }}</td>
                <td class="num">{{ $billedMonths }}</td>
                <td class="num">{{ number_format($yearTotal, 2) }}</td>
                <td class="num">{{ number_format($paidSoFar, 2) }}</td>
                <td class="num semi">{{ number_format($balance, 2) }}</td>
            </tr>
        </tbody>
    </table>
@else
    <div class="muted" style="font-size: 7.5px;">This student is not on a route right now, so only this payment is shown.</div>
@endif

{{-- ══════════ FOOT ══════════ --}}
<table class="foot">
    <tr>
        <td class="note">
            All amounts in INR. Fees once paid are not refundable.
            This is a computer-generated receipt and is valid without a physical signature.
        </td>
        <td class="sign">
            <div class="sign-line"></div>
            Authorised Signatory
        </td>
    </tr>
</table>
<div class="foot-num">Receipt No. {{ $payment->receipt_number }}</div>
</body>
</html>
