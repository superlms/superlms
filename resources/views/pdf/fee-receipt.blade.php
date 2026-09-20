{{--
    Academic fee receipt as a PDF (dompdf) — the student app's download, laid
    out exactly as the transport receipt: the school at the head, the student
    and the payment as label/value lines, the amount the one loud thing on the
    sheet. Tables, because dompdf has no flexbox.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fee Receipt — {{ $payment->receipt_number }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        /* No `* { margin: 0 }` here — that wipes the @page margins. */
        @page { margin: 30px 30px 26px 30px; }
        body { margin: 0; font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #111827; line-height: 1.35; }
        /* dompdf collapses font-weight per family, so each weight is its own
           family name (see App\Support\PdfFonts). */

        table { width: 100%; border-collapse: collapse; }

        /* Head — the school, then a single rule under it */
        .logo { height: 24px; width: auto; margin-bottom: 3px; }
        .school { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 12.5px; letter-spacing: .4px; }
        .school-sub { font-size: 7.5px; color: #6b7280; margin-top: 1px; }
        table.head td { vertical-align: top; padding: 0 0 8px; }
        table.head td.right { text-align: right; }
        .kicker { font-size: 7px; letter-spacing: 1.6px; text-transform: uppercase; color: #6b7280; }
        .no { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 9px; margin-top: 2px; }
        .rule { border-top: 1px solid #111827; height: 0; font-size: 0; line-height: 0; }

        /* A block of label/value lines under a small heading */
        .sec { font-size: 7px; letter-spacing: 1.4px; text-transform: uppercase; color: #6b7280; margin: 11px 0 1px; }
        table.kv td { padding: 3px 0; border-bottom: 1px solid #f1f2f4; vertical-align: top; }
        table.kv td.k { width: 34%; color: #6b7280; }
        table.kv td.v { text-align: right; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        table.kv tr.last td { border-bottom: 0; }

        /* The amount — the one loud thing on the sheet */
        .amount { margin-top: 12px; padding: 8px 0 9px; border-top: 1px solid #111827; border-bottom: 1px solid #111827; }
        .amount .lbl { font-size: 7px; letter-spacing: 1.4px; text-transform: uppercase; color: #6b7280; }
        .amount .fig { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 20px; line-height: 1.15; margin-top: 2px; }
        .amount .words { font-size: 8px; color: #4b5563; margin-top: 1px; }

        /* Foot */
        .foot { margin-top: 20px; }
        table.foot td { vertical-align: bottom; padding: 0; }
        table.foot td.note { width: 62%; font-size: 6.5px; color: #9ca3af; line-height: 1.5; padding-right: 16px; }
        table.foot td.sign { text-align: right; font-size: 7.5px; color: #6b7280; }
        .sign-line { width: 120px; border-top: 1px solid #d1d5db; margin: 18px 0 3px auto; }
    </style>
</head>
<body>
@php
    $logo = $org->logo ?? null;
    if ($logo && !preg_match('#^(https?:|data:)#i', $logo)) {
        $logo = url($logo);
    }
    $contactBits = array_values(array_filter([$org->mobile_number ?? null, $org->email ?? null]));
    $paidOn  = $payment->payment_date;
    $penalty = (float) $payment->penalty_amount;
    $balance = max(0, $yearTotal - $paidSoFar);
    $money   = fn ($n) => '₹ ' . number_format((float) $n, 2);

    // Only the lines that are actually filled in.
    $studentRows = array_filter([
        'Name'          => $student->user->name ?? $student->full_name ?? null,
        'Class'         => $student
            ? trim(($student->standard->name ?? '') . ($student->section ? ' · ' . $student->section->name : ''))
            : null,
        'Admission No.' => $student->admission_no ?? null,
        'Roll No.'      => $student->roll_no ?? null,
        'Father'        => $student->father_name ?? null,
    ], fn ($v) => $v !== null && $v !== '');

    $paymentRows = array_filter([
        'Paid on'     => $paidOn ? $paidOn->format('d M Y') . ' · ' . $paidOn->format('l') : null,
        'Mode'        => $payMode === '—' ? $payType : trim($payMode . ' · ' . $payType),
        'Received by' => $collectedBy,
        'Late fee'    => $penalty > 0 ? $money($penalty) . ' included' : null,
        'Remark'      => $payment->remark,
    ], fn ($v) => $v !== null && $v !== '' && $v !== '—');

    $feeRows = array_filter([
        'Payable this year' => $money($yearTotal),
        'Concession'        => $concession > 0 ? '− ' . $money($concession) : null,
        'Paid to date'      => $money($paidSoFar),
        'Balance'           => $money($balance),
    ], fn ($v) => $v !== null && $v !== '');
@endphp

{{-- ══════════ HEAD ══════════ --}}
<table class="head">
    <tr>
        <td>
            @if ($logo)
                <img src="{{ $logo }}" class="logo" alt="">
            @endif
            <div class="school">{{ $org->name ?? 'School' }}</div>
            @if (!empty($org?->address))
                <div class="school-sub">{{ $org->address }}</div>
            @endif
            @if ($contactBits)
                <div class="school-sub">{{ implode(' · ', $contactBits) }}</div>
            @endif
        </td>
        <td class="right">
            <div class="kicker">Fee Receipt</div>
            <div class="no">No. {{ $payment->receipt_number }}</div>
        </td>
    </tr>
</table>
<div class="rule"></div>

{{-- ══════════ STUDENT ══════════ --}}
<div class="sec">Student</div>
<table class="kv">
    @foreach ($studentRows as $label => $value)
        <tr class="{{ $loop->last ? 'last' : '' }}">
            <td class="k">{{ $label }}</td>
            <td class="v">{{ $value }}</td>
        </tr>
    @endforeach
</table>

{{-- ══════════ AMOUNT ══════════ --}}
<div class="amount">
    <div class="lbl">Amount Paid</div>
    <div class="fig">{{ $money($payment->amount) }}</div>
    <div class="words">{{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}</div>
</div>

{{-- ══════════ PAYMENT ══════════ --}}
<div class="sec">Payment</div>
<table class="kv">
    @foreach ($paymentRows as $label => $value)
        <tr class="{{ $loop->last ? 'last' : '' }}">
            <td class="k">{{ $label }}</td>
            <td class="v">{{ $value }}</td>
        </tr>
    @endforeach
</table>

{{-- ══════════ THE YEAR'S FEE ══════════ --}}
<div class="sec">Academic fee</div>
<table class="kv">
    @foreach ($feeRows as $label => $value)
        <tr class="{{ $loop->last ? 'last' : '' }}">
            <td class="k">{{ $label }}</td>
            <td class="v">{{ $value }}</td>
        </tr>
    @endforeach
</table>

{{-- ══════════ FOOT ══════════ --}}
<table class="foot">
    <tr>
        <td class="note">
            All amounts in INR. Fees once paid are not refundable.
            Computer-generated receipt — valid without a signature.
        </td>
        <td class="sign">
            <div class="sign-line"></div>
            Authorised Signatory
        </td>
    </tr>
</table>
</body>
</html>
