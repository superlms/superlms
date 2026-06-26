<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt — {{ $payment->receipt_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', Arial, sans-serif; font-size: 13px; color: #1f2937; background: #eef1f6; padding: 26px 14px; }
        .sheet { max-width: 640px; margin: 0 auto; background: #fff; border-radius: 14px; overflow: hidden;
            box-shadow: 0 18px 40px rgba(15,40,80,.14); }

        /* Header */
        .r-head { background: linear-gradient(135deg, #15355f 0%, #0e2647 60%, #0a1c36 100%); color: #fff;
            padding: 22px 28px; display: flex; align-items: center; gap: 16px; position: relative; }
        .r-head::after { content: ''; position: absolute; left: 0; right: 0; bottom: 0; height: 3px;
            background: linear-gradient(90deg, transparent, #e6cd7e, transparent); }
        .r-logo { width: 56px; height: 56px; border-radius: 12px; background: #fff; flex: 0 0 56px;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px rgba(230,205,126,.6); }
        .r-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: 12px; padding: 5px; }
        .r-logo span { color: #15355f; font-weight: 700; font-size: 22px; font-family: Georgia, serif; }
        .r-school h1 { font-size: 19px; font-weight: 700; letter-spacing: .3px; font-family: Georgia, serif; }
        .r-school p { font-size: 10.5px; color: rgba(255,255,255,.78); margin-top: 3px; line-height: 1.4; }
        .r-tag { margin-left: auto; text-align: right; }
        .r-tag .lbl { font-size: 9px; text-transform: uppercase; letter-spacing: 2px; color: #e6cd7e; }
        .r-tag .doc { font-size: 15px; font-weight: 700; margin-top: 2px; }

        /* Meta bar */
        .r-meta { display: flex; justify-content: space-between; gap: 12px; padding: 14px 28px;
            background: #f7f9fc; border-bottom: 1px solid #e8ecf3; font-size: 12px; }
        .r-meta .k { color: #7c8aa3; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; }
        .r-meta .v { font-weight: 700; color: #15355f; margin-top: 2px; }

        .r-body { padding: 22px 28px; }
        .sec-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px;
            color: #15355f; margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .sec-title::after { content: ''; flex: 1; height: 1px; background: #e8ecf3; }

        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 28px; margin-bottom: 22px; }
        .cell .label { font-size: 10px; color: #7c8aa3; }
        .cell .value { font-size: 13px; font-weight: 600; color: #1b2840; margin-top: 2px; }

        .badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-academic { background: #dbeafe; color: #1d4ed8; }
        .badge-transport { background: #d1fae5; color: #065f46; }

        .amount-box { background: linear-gradient(135deg, #eef4ff, #e4ecfb); border: 1px solid #c9d8f2;
            border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; justify-content: space-between; }
        .amount-box .label { font-size: 11px; color: #15355f; text-transform: uppercase; letter-spacing: 1px; }
        .amount-box .sub { font-size: 10.5px; color: #7c8aa3; margin-top: 3px; }
        .amount-box .amount { font-size: 30px; font-weight: 700; color: #0e2647; }

        .r-foot { display: flex; justify-content: space-between; align-items: flex-end; padding: 18px 28px 24px;
            border-top: 1px dashed #cdd6e4; }
        .r-foot .note { font-size: 10.5px; color: #7c8aa3; line-height: 1.6; }
        .r-foot .sign { text-align: center; }
        .r-foot .sign .line { width: 150px; border-top: 1px solid #1b2840; margin-bottom: 5px; }
        .r-foot .sign .role { font-size: 11px; font-weight: 700; color: #1b2840; }
        .r-foot .sign .sub { font-size: 9px; color: #7c8aa3; text-transform: uppercase; letter-spacing: 1px; }

        .toolbar { text-align: center; margin: 20px; }
        .toolbar button { padding: 10px 24px; background: #15355f; color: #fff; border: 0; border-radius: 8px;
            cursor: pointer; font-size: 13px; font-weight: 600; box-shadow: 0 6px 14px rgba(15,53,95,.28); }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    @php
        $org   = $org ?? ($payment->organization ?? null);
        $logo  = $org->logo ?? null;
        $mono  = strtoupper(mb_substr($org->name ?? 'S', 0, 1));
        $student = $payment->studentDetail;
    @endphp

    <div class="sheet">
        {{-- School header --}}
        <div class="r-head">
            <div class="r-logo">
                @if ($logo)<img src="{{ $logo }}" alt="logo">@else<span>{{ $mono }}</span>@endif
            </div>
            <div class="r-school">
                <h1>{{ $org->name ?? 'School' }}</h1>
                <p>
                    {{ $org->address ?? '' }}@if(!empty($org?->mobile_number)) · {{ $org->mobile_number }}@endif
                    @if(!empty($org?->email)) · {{ $org->email }}@endif
                </p>
            </div>
            <div class="r-tag">
                <div class="lbl">Receipt</div>
                <div class="doc">FEE PAYMENT</div>
            </div>
        </div>

        {{-- Meta --}}
        <div class="r-meta">
            <div><div class="k">Receipt No.</div><div class="v">{{ $payment->receipt_number }}</div></div>
            <div><div class="k">Payment Date</div><div class="v">{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</div></div>
            <div><div class="k">Status</div><div class="v" style="color:#1a7f3c;">PAID</div></div>
        </div>

        <div class="r-body">
            {{-- Student --}}
            <div class="sec-title">Student Details</div>
            <div class="grid">
                <div class="cell"><div class="label">Student Name</div><div class="value">{{ $student->full_name ?? ($student->user->name ?? '-') }}</div></div>
                <div class="cell"><div class="label">Admission No.</div><div class="value">{{ $student->admission_no ?? '-' }}</div></div>
                <div class="cell"><div class="label">Class &amp; Section</div><div class="value">{{ $payment->standard->name ?? '-' }}{{ $payment->section ? ' / ' . $payment->section->name : '' }}</div></div>
                <div class="cell"><div class="label">Father's Name</div><div class="value">{{ $student->father_name ?? '-' }}</div></div>
            </div>

            {{-- Payment --}}
            <div class="sec-title">Payment Details</div>
            <div class="grid">
                <div class="cell"><div class="label">Fee Type</div><div class="value"><span class="badge {{ $payment->fee_type === 'academic' ? 'badge-academic' : 'badge-transport' }}">{{ ucfirst($payment->fee_type) }}</span></div></div>
                <div class="cell"><div class="label">Payment Mode</div><div class="value">{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</div></div>
                <div class="cell"><div class="label">Collected By</div><div class="value">{{ $payment->submitted_by ?: '-' }}</div></div>
                @if ($payment->remark)
                    <div class="cell"><div class="label">Remark</div><div class="value">{{ $payment->remark }}</div></div>
                @endif
            </div>

            <div class="amount-box">
                <div>
                    <div class="label">Amount Paid</div>
                    <div class="sub">Rupees {{ ucwords(\Illuminate\Support\Str::of(number_format((float) $payment->amount, 0))) }} only</div>
                </div>
                <div class="amount">₹{{ number_format($payment->amount, 2) }}</div>
            </div>
        </div>

        <div class="r-foot">
            <div class="note">
                This is a computer-generated receipt and<br>does not require a physical signature.
            </div>
            <div class="sign">
                <div class="line"></div>
                <div class="role">Authorised Signatory</div>
                <div class="sub">{{ $org->name ?? 'School' }}</div>
            </div>
        </div>
    </div>

    <div class="toolbar no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>
</body>
</html>
