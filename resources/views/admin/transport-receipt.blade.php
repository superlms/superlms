<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Transport Fee Receipt — {{ $payment->receipt_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #f3f4f6; padding: 24px; }
        .toolbar { text-align: center; margin-bottom: 16px; }
        .toolbar button { background: #111827; color: #fff; border: 0; padding: 8px 18px; border-radius: 6px; font-size: 13px; cursor: pointer; font-weight: 600; }
        .receipt { width: 600px; max-width: 100%; margin: 0 auto; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; }
        .head { background: linear-gradient(135deg,#2563eb,#4f46e5); color: #fff; padding: 20px 24px; text-align: center; }
        .head h1 { font-size: 20px; }
        .head p { font-size: 12px; opacity: .9; margin-top: 2px; }
        .badge { display: inline-block; margin-top: 8px; font-size: 11px; background: rgba(255,255,255,.2); padding: 3px 12px; border-radius: 999px; letter-spacing: 1px; }
        .body { padding: 24px; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #eee; font-size: 13px; }
        .row span:first-child { color: #6b7280; }
        .row span:last-child { font-weight: 600; color: #111827; }
        .amount { margin-top: 18px; background: #ecfdf5; border: 1px solid #d1fae5; border-radius: 8px; padding: 16px; text-align: center; }
        .amount .label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #059669; }
        .amount .value { font-size: 28px; font-weight: 700; color: #047857; margin-top: 2px; }
        .sign { display: flex; justify-content: space-between; margin-top: 36px; font-size: 12px; }
        .sign div { border-top: 1px solid #9ca3af; padding-top: 4px; width: 180px; text-align: center; }
        .foot { background: #f9fafb; padding: 10px; text-align: center; font-size: 11px; color: #9ca3af; border-top: 1px solid #eee; }
        @media print { body { background: #fff; padding: 0; } .toolbar { display: none; } .receipt { border: none; } }
    </style>
</head>
<body>
    <div class="toolbar"><button onclick="window.print()">🖨 Print / Save as PDF</button></div>

    <div class="receipt">
        <div class="head">
            <h1>{{ $payment->organization->name ?? 'School' }}</h1>
            <p>Transport Fee Receipt</p>
            <span class="badge">{{ $payment->receipt_number }}</span>
        </div>
        <div class="body">
            <div class="row"><span>Student</span><span>{{ $payment->studentDetail->full_name ?? '—' }}</span></div>
            <div class="row"><span>Admission No.</span><span>{{ $payment->studentDetail->admission_no ?? '—' }}</span></div>
            <div class="row"><span>Class</span><span>{{ $payment->studentDetail->standard->name ?? '—' }}{{ $payment->studentDetail->section ? ' - ' . $payment->studentDetail->section->name : '' }}</span></div>
            <div class="row"><span>Route</span><span>{{ $payment->transportation->route_name ?? '—' }}</span></div>
            <div class="row"><span>Payment Date</span><span>{{ $payment->payment_date?->format('d M Y') }}</span></div>
            <div class="row"><span>Payment Mode</span><span style="text-transform:capitalize">{{ $payment->payment_mode }}</span></div>
            @if ($payment->academic_year)
                <div class="row"><span>Academic Year</span><span>{{ $payment->academic_year }}</span></div>
            @endif
            @if ($payment->remark)
                <div class="row"><span>Remark</span><span>{{ $payment->remark }}</span></div>
            @endif

            <div class="amount">
                <div class="label">Amount Paid</div>
                <div class="value">₹{{ number_format($payment->amount, 2) }}</div>
            </div>

            <div class="sign">
                <div>Received By{{ $payment->submittedBy ? ' — ' . $payment->submittedBy->name : '' }}</div>
                <div>Authorised Signatory</div>
            </div>
        </div>
        <div class="foot">Generated {{ now()->format('d M Y H:i') }} · This is a system-generated receipt.</div>
    </div>
</body>
</html>
