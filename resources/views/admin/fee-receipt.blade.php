<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt — {{ $payment->receipt_number }}</title>
    <style>
        /* A5 portrait, the size a receipt actually is. Minimal by design:
           thin rules, no fills, no colour beyond the ink. */
        @page { size: A5 portrait; margin: 9mm; }

        html { background: #e9ecf1; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 9.5px; line-height: 1.45; }

        .sheet { width: 130mm; min-height: 192mm; margin: 12px auto; padding: 9mm; background: #fff; }

        h1, h2, h3, p, table { margin: 0; }
        .num { text-align: right; }
        table { width: 100%; border-collapse: collapse; }

        /* Masthead */
        .head { display: flex; align-items: flex-start; gap: 8px;
                border-bottom: 1px solid #111827; padding-bottom: 6px; }
        .head .school { font-size: 13px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }
        .head .meta { font-size: 7.5px; color: #6b7280; margin-top: 1px; }
        .head .doc { margin-left: auto; text-align: right; white-space: nowrap; }
        .head .doc .kind { font-size: 8px; letter-spacing: 1.6px; text-transform: uppercase; color: #6b7280; }
        .head .doc .no { font-size: 11px; font-weight: bold; margin-top: 1px; }
        .head .doc .on { font-size: 7.5px; color: #6b7280; }

        /* Section heads */
        .sec { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #9ca3af;
               margin: 9px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }

        /* Key/value pairs, two to a row */
        table.kv td { padding: 1.6px 0; vertical-align: top; font-size: 9px; }
        table.kv td.k { color: #9ca3af; width: 22mm; }
        table.kv td.v { font-weight: bold; padding-right: 6mm; }

        /* Data tables */
        table.dt th { font-size: 7.5px; letter-spacing: .5px; text-transform: uppercase; color: #9ca3af;
                      font-weight: normal; text-align: left; padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        table.dt td { padding: 2.6px 0; font-size: 9px; border-bottom: 1px solid #f3f4f6; }
        table.dt td.sl { color: #cbd0d8; font-size: 8px; width: 6mm; }
        table.dt tr.sum td { border-top: 1px solid #111827; border-bottom: 0; font-weight: bold; padding-top: 4px; }
        .muted { color: #9ca3af; }

        /* The amount this receipt is for */
        .paid-for { border: 1px solid #111827; padding: 5px 8px; margin-top: 3px;
                    display: flex; align-items: baseline; justify-content: space-between; }
        .paid-for .lbl { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #6b7280; }
        .paid-for .amt { font-size: 17px; font-weight: bold; }
        .words { font-size: 7.5px; color: #6b7280; margin-top: 2px; }

        /* Overall standing */
        table.tot td { padding: 2.6px 0; font-size: 9px; }
        table.tot tr.grand td { border-top: 1px solid #111827; font-weight: bold; padding-top: 4px; font-size: 10px; }

        .foot { margin-top: 14px; padding-top: 6px; border-top: 1px solid #e5e7eb;
                display: flex; align-items: flex-end; justify-content: space-between; }
        .foot .note { font-size: 7px; color: #9ca3af; max-width: 62mm; line-height: 1.5; }
        .foot .sign { text-align: center; }
        .foot .sign .line { width: 34mm; border-top: 1px solid #111827; margin-bottom: 3px; }
        .foot .sign .role { font-size: 8px; }

        .toolbar { text-align: center; margin: 14px 0 24px; }
        .toolbar button { padding: 7px 18px; background: #111827; color: #fff; border: 0; border-radius: 5px;
                          cursor: pointer; font-size: 11px; letter-spacing: .3px; }

        @media print {
            html { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
</head>
<body>
<div class="sheet">

    {{-- ══════════ MASTHEAD ══════════ --}}
    <div class="head">
        <div>
            <div class="school">{{ $org->name ?? 'School' }}</div>
            <div class="meta">
                {{ $org->address ?? '' }}
                @if (!empty($org?->mobile_number)) · {{ $org->mobile_number }} @endif
            </div>
        </div>
        <div class="doc">
            <div class="kind">Fee Receipt</div>
            <div class="no">{{ $payment->receipt_number }}</div>
            <div class="on">{{ optional($payment->payment_date)->format('d M Y') }}</div>
        </div>
    </div>

    {{-- ══════════ STUDENT ══════════ --}}
    <div class="sec">Student</div>
    <table class="kv">
        <tr>
            <td class="k">Name</td>
            <td class="v">{{ $student->user->name ?? $student->full_name ?? '—' }}</td>
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
    </table>

    {{-- ══════════ THIS PAYMENT ══════════ --}}
    <div class="sec">This Payment</div>
    <table class="kv">
        <tr>
            <td class="k">Fee Type</td>
            <td class="v">{{ ucfirst($payment->fee_type) }}</td>
            <td class="k">Mode</td>
            <td class="v">{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</td>
        </tr>
        <tr>
            <td class="k">Received On</td>
            <td class="v">{{ optional($payment->payment_date)->format('d M Y') ?? '—' }}</td>
            <td class="k">Received By</td>
            <td class="v">{{ $collectedBy }}</td>
        </tr>
        @if ((float) $payment->penalty_amount > 0 || (float) $payment->waiver_amount > 0)
            <tr>
                <td class="k">Penalty</td>
                <td class="v">{{ number_format((float) $payment->penalty_amount, 2) }}</td>
                <td class="k">Waiver</td>
                <td class="v">{{ number_format((float) $payment->waiver_amount, 2) }}</td>
            </tr>
        @endif
        @if ($payment->remark)
            <tr>
                <td class="k">Remark</td>
                <td class="v" colspan="3">{{ $payment->remark }}</td>
            </tr>
        @endif
    </table>

    <div class="paid-for">
        <span class="lbl">Amount Received</span>
        <span class="amt">₹{{ number_format((float) $payment->amount, 2) }}</span>
    </div>

    {{-- ══════════ FEE CYCLE ══════════ --}}
    @forelse ($cycles as $cycle)
        <div class="sec">
            {{ ucfirst($cycle['fee_type']) }} Fee Cycle — {{ $cycle['label'] }} · {{ $cycle['year'] }}
        </div>
        <table class="dt">
            <thead>
                <tr>
                    <th style="width:6mm;">#</th>
                    <th>Installment</th>
                    <th>Due</th>
                    <th class="num" style="width:18mm;">Amount</th>
                    <th class="num" style="width:18mm;">Paid</th>
                    <th class="num" style="width:18mm;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cycle['installments'] as $i => $inst)
                    <tr>
                        <td class="sl">{{ $i + 1 }}</td>
                        <td>{{ $inst['label'] }}</td>
                        <td class="{{ $inst['overdue'] ? '' : 'muted' }}">{{ $inst['due_date'] ?? '—' }}</td>
                        <td class="num">{{ number_format($inst['amount'], 2) }}</td>
                        <td class="num {{ $inst['paid'] > 0 ? '' : 'muted' }}">{{ number_format($inst['paid'], 2) }}</td>
                        <td class="num {{ $inst['balance'] > 0 ? '' : 'muted' }}">{{ number_format($inst['balance'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="sum">
                    <td colspan="3">{{ $cycle['paid_count'] }} of {{ count($cycle['installments']) }} cleared</td>
                    <td class="num">{{ number_format($cycle['total'], 2) }}</td>
                    <td class="num">{{ number_format($cycle['paid'], 2) }}</td>
                    <td class="num">{{ number_format(max(0, $cycle['total'] - $cycle['paid']), 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <div class="sec">Fee Cycle</div>
        <p class="muted" style="font-size:8.5px;">No fee cycle defined for this year — the fee is collected in full.</p>
    @endforelse

    {{-- ══════════ OVERALL ══════════ --}}
    <div class="sec">Overall</div>
    <table class="tot">
        <tr>
            <td>Academic fee</td>
            <td class="num">{{ number_format($overall['academic']['total'], 2) }}</td>
            <td class="num muted" style="width:22mm;">paid {{ number_format($overall['academic']['paid'], 2) }}</td>
        </tr>
        @if ($route)
            <tr>
                <td>Transport fee <span class="muted">({{ $route->route_name }})</span></td>
                <td class="num">{{ number_format($overall['transport']['total'], 2) }}</td>
                <td class="num muted">paid {{ number_format($overall['transport']['paid'], 2) }}</td>
            </tr>
        @endif
        @if ($overall['concession'] > 0)
            <tr>
                <td>Concession applied</td>
                <td class="num">− {{ number_format($overall['concession'], 2) }}</td>
                <td class="num muted">
                    @foreach ($concessions as $c)
                        {{ $c->reason ?: 'Concession' }}@if (!$loop->last), @endif
                    @endforeach
                </td>
            </tr>
        @endif
        <tr class="grand">
            <td>Total payable</td>
            <td class="num">{{ number_format($overall['total'], 2) }}</td>
            <td class="num">bal {{ number_format($overall['balance'], 2) }}</td>
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
</div>

<div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
</body>
</html>
