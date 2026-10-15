<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt — {{ $payment->receipt_number }}</title>
    @include('admin.partials.receipt-style')
</head>
<body>
<div class="sheet">

    {{-- ══════════ MASTHEAD — logo, then name, then address, then contacts ══════════ --}}
    <div class="masthead">
        @if (!empty($org?->logo))
            <img src="{{ $org->logo }}" class="logo" alt="">
        @endif
        <div class="school">{{ $org->name ?? 'School' }}</div>
        @if (!empty($org?->address))
            <div class="addr">{{ $org->address }}</div>
        @endif
        @php
            $contactBits = array_values(array_filter([$org->mobile_number ?? null, $org->email ?? null]));
        @endphp
        @if ($contactBits)
            <div class="contact">{{ implode(' · ', $contactBits) }}</div>
        @endif
    </div>

    {{-- ══════════ STUDENT ══════════ --}}
    <div class="sec">Student</div>
    <table class="kv">
        <tr>
            <td class="k">Name</td>
            <td class="v">{{ $student->full_name ?: ($student->user->name ?? '—') }}</td>
            <td class="k">Class</td>
            <td class="v">{{ $student->standard->name ?? '—' }}{{ $student->section ? ' · ' . $student->section->name : '' }}</td>
        </tr>
        <tr>
            <td class="k">Father</td>
            <td class="v">{{ $student->father_name ?: '—' }}</td>
            <td class="k">Adm No.</td>
            <td class="v">{{ $student->admission_no ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Phone</td>
            <td class="v">{{ $student->phone ?: '—' }}</td>
            <td class="k">Roll No.</td>
            <td class="v">{{ $student->roll_no ?: '—' }}</td>
        </tr>
        <tr>
            <td class="k">Fee Submitted</td>
            <td class="v" colspan="3">
                {{ optional($payment->created_at)->format('d M Y') ?? '—' }}
                @if ($payment->created_at)
                    <span style="font-weight:normal; color:#6b7280;">· {{ $payment->created_at->format('h:i A') }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══════════ THIS PAYMENT ══════════ --}}
    <div class="sec">This Payment</div>
    <div class="payinfo">
        <div class="cell">
            <div class="lbl">Date</div>
            <div class="val">{{ optional($payment->payment_date)->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Time</div>
            <div class="val">{{ optional($payment->created_at)->format('h:i A') ?? '—' }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Mode</div>
            <div class="val">{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Collected By</div>
            <div class="val">{{ $payment->submitted_by ?: '—' }}</div>
        </div>
    </div>
    @if ($payment->remark)
        <div class="remark-line"><strong>Remark —</strong> {{ $payment->remark }}</div>
    @endif

    <div class="payamt">
        <div class="payamt-row">
            <span class="chip">Fee <b>₹{{ number_format((float) $payment->amount, 2) }}</b></span>
            <span class="eq">=</span>
            <span class="grand">₹{{ number_format((float) $payment->amount, 2) }}</span>
        </div>
        <div class="words"><span class="words-lbl">Amount in Words</span>{{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}</div>
    </div>

    {{-- ══════════ FEE STRUCTURE ══════════
         The student's own particulars, in the fee cycle's place. --}}
    <div class="sec">Fee Structure</div>
    @if ($items->isEmpty())
        <p class="muted" style="font-size:8.5px;">No fee particulars are set on this student now.</p>
    @else
        <table class="dt">
            <colgroup>
                <col style="width:8%;"><col style="width:67%;"><col style="width:25%;">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Particular</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $i => $item)
                    <tr>
                        <td class="sl">{{ $i + 1 }}</td>
                        <td>{{ $item->fee_name }}</td>
                        <td class="num">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="sum">
                    <td colspan="2">{{ $items->count() }} {{ \Illuminate\Support\Str::plural('particular', $items->count()) }}</td>
                    <td class="num">{{ number_format($overall['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- ══════════ OVERALL ══════════
         As it stood with this receipt: paid up to and with it, and what was left. --}}
    <div class="sec">Overall</div>
    <table class="tot">
        <tr>
            <td>Total fee</td>
            <td class="num">{{ number_format($overall['total'], 2) }}</td>
        </tr>
        <tr>
            <td>Paid <span class="muted">(with this receipt)</span></td>
            <td class="num">{{ number_format($paidThrough, 2) }}</td>
        </tr>
        <tr class="grand">
            <td>Balance</td>
            <td class="num">{{ number_format(max(0, $overall['total'] - $paidThrough), 2) }}</td>
        </tr>
    </table>

    {{-- ══════════ FOOT ══════════ --}}
    <div class="foot">
        <p class="note">
            All amounts in INR. Fees once paid are not refundable.
            This is a computer-generated receipt and is valid without a physical signature.
        </p>
        <div class="sign">
            <div class="line"></div>
            <div class="role">Authorised Signatory</div>
        </div>
    </div>
    <div class="foot-num">Receipt No. {{ $payment->receipt_number }}</div>
</div>

<div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
</body>
</html>
