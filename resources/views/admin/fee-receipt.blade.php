<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt — {{ $payment->receipt_number }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* A5 portrait, the size a receipt actually is. Minimal by design:
           thin rules, no fills, no colour beyond the ink. */
        @page { size: A5 portrait; margin: 9mm; }

        html { background: #e9ecf1; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; font-size: 9.5px; line-height: 1.45; }
        * { box-sizing: border-box; }

        .sheet { width: 130mm; min-height: 192mm; margin: 12px auto; padding: 9mm; background: #fff; }

        h1, h2, h3, p, table { margin: 0; }
        .num { text-align: right; }
        table { width: 100%; border-collapse: collapse; }

        /* Masthead — logo first, then name, then address, then contacts, all centred */
        .masthead { text-align: center; border-bottom: 1px solid #111827; padding-bottom: 7px; }
        .masthead .logo { height: 34px; width: auto; margin-bottom: 4px; }
        .masthead .school { font-family: 'Poppins', Arial, sans-serif; font-weight: 600; font-size: 14px; letter-spacing: .3px; text-transform: uppercase; }
        .masthead .addr { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
        .masthead .contact { font-size: 7.5px; color: #6b7280; margin-top: 1px; }

        /* Section heads */
        .sec { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #9ca3af;
               margin: 10px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }

        /* Key/value pairs, two to a row — full-strength labels, semibold data */
        table.kv td { padding: 1.6px 0; vertical-align: top; font-size: 9px; }
        table.kv td.k { color: #4b5563; width: 22mm; }
        table.kv td.v { font-weight: 600; color: #111827; padding-right: 6mm; }

        /* This Payment — a slim meta strip: date, time, status, mode, collected by */
        .payinfo { display: flex; border: 1px solid #e5e7eb; border-radius: 3px; margin-top: 3px; overflow: hidden; }
        .payinfo .cell { flex: 1 1 0; min-width: 0; padding: 5px 6px; border-right: 1px solid #e5e7eb; }
        .payinfo .cell:last-child { border-right: 0; }
        .payinfo .lbl { font-size: 6.3px; letter-spacing: .8px; text-transform: uppercase; color: #9ca3af; white-space: nowrap; }
        .payinfo .val { font-size: 9px; font-weight: 600; margin-top: 1.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .payinfo .val.status-late { color: #b45309; }
        .payinfo .val.status-ontime { color: #0a8a4a; }
        .remark-line { font-size: 8px; color: #6b7280; margin-top: 5px; }

        /* Amount for this payment — one minimalistic row, no table */
        .payamt { display: flex; align-items: baseline; flex-wrap: wrap; gap: 7px;
                  border: 1px solid #111827; border-radius: 3px; padding: 7px 9px; margin-top: 5px; }
        .payamt .chip { font-size: 8px; color: #6b7280; white-space: nowrap; }
        .payamt .chip b { color: #111827; font-weight: 600; }
        .payamt .chip.plus b, .payamt .chip.plus { color: #b45309; }
        .payamt .eq { color: #9ca3af; font-size: 11px; }
        .payamt .grand { margin-left: auto; font-size: 17px; font-weight: bold; white-space: nowrap; }
        .words { font-size: 8px; color: #6b7280; margin-top: 4px; font-style: italic; }

        /* Data tables — fixed layout so every row's columns line up exactly */
        table.dt { table-layout: fixed; }
        table.dt col.c-sl { width: 6%; }
        table.dt col.c-inst { width: 30%; }
        table.dt col.c-due { width: 16%; }
        table.dt col.c-num { width: 12%; }
        table.dt th { font-size: 7px; letter-spacing: .4px; text-transform: uppercase; color: #9ca3af;
                      font-weight: normal; text-align: left; padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        table.dt th.num { text-align: right; }
        table.dt td { padding: 3px 0; font-size: 8.5px; border-bottom: 1px solid #f3f4f6; vertical-align: top;
                      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        table.dt td.sl { color: #cbd0d8; font-size: 8px; }
        table.dt tr.sum td { border-top: 1px solid #111827; border-bottom: 0; font-weight: bold; padding-top: 4px; }
        .muted { color: #9ca3af; }
        .penalty-flag { color: #b45309; }

        /* Overall standing */
        table.tot td { padding: 2.6px 0; font-size: 9px; }
        table.tot tr.grand td { border-top: 1px solid #111827; font-weight: bold; padding-top: 4px; font-size: 10px; }

        .foot { margin-top: 14px; padding-top: 6px; border-top: 1px solid #e5e7eb;
                display: flex; align-items: flex-end; justify-content: space-between; }
        .foot .note { font-size: 7px; color: #9ca3af; max-width: 62mm; line-height: 1.5; }
        .foot .sign { text-align: center; }
        .foot .sign .line { width: 34mm; border-top: 1px solid #111827; margin-bottom: 3px; }
        .foot .sign .role { font-size: 8px; }
        .foot-num { text-align: center; font-size: 7px; letter-spacing: .4px; color: #9ca3af; margin-top: 10px; }

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
    @php
        $isLate = (float) $payment->penalty_amount > 0;
        $base   = max(0, (float) $payment->amount - (float) $payment->penalty_amount + (float) $payment->waiver_amount);
    @endphp
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
            <div class="lbl">Status</div>
            <div class="val {{ $isLate ? 'status-late' : 'status-ontime' }}">{{ $isLate ? 'Late' : 'On Time' }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Mode</div>
            <div class="val">{{ ucfirst(str_replace('_', ' ', $payment->payment_mode)) }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Collected By</div>
            <div class="val">{{ $collectedBy }}</div>
        </div>
    </div>
    @if ($payment->remark)
        <div class="remark-line"><strong>Remark —</strong> {{ $payment->remark }}</div>
    @endif

    <div class="payamt">
        <span class="chip">{{ $payment->fee_type === 'penalty' ? 'Penalty' : ucfirst($payment->fee_type) . ' Fee' }} <b>₹{{ number_format($base, 2) }}</b></span>
        @if ((float) $payment->penalty_amount > 0)
            <span class="chip plus">+ Penalty <b>₹{{ number_format((float) $payment->penalty_amount, 2) }}</b></span>
        @endif
        @if ((float) $payment->waiver_amount > 0)
            <span class="chip">− Waiver <b>₹{{ number_format((float) $payment->waiver_amount, 2) }}</b></span>
        @endif
        <span class="eq">=</span>
        <span class="grand">₹{{ number_format((float) $payment->amount, 2) }}</span>
    </div>
    <p class="words">Amount in Words: {{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}</p>

    {{-- ══════════ FEE CYCLE ══════════ --}}
    @forelse ($cycles as $cycle)
        <div class="sec">
            {{ ucfirst($cycle['fee_type']) }} Fee Cycle — {{ $cycle['label'] }} · {{ $cycle['year'] }}
        </div>
        <table class="dt">
            <colgroup>
                <col class="c-sl"><col class="c-inst"><col class="c-due">
                <col class="c-num"><col class="c-num"><col class="c-num"><col class="c-num">
            </colgroup>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Installment</th>
                    <th>Due Date</th>
                    <th class="num">Amount</th>
                    <th class="num">Paid</th>
                    <th class="num">Balance</th>
                    <th class="num">Penalty</th>
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
                        <td class="num {{ $inst['penalty'] > 0 ? 'penalty-flag' : 'muted' }}">
                            {{ $inst['penalty'] > 0 ? number_format($inst['penalty'], 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
                <tr class="sum">
                    <td colspan="3">{{ $cycle['paid_count'] }} of {{ count($cycle['installments']) }} cleared</td>
                    <td class="num">{{ number_format($cycle['total'], 2) }}</td>
                    <td class="num">{{ number_format($cycle['paid'], 2) }}</td>
                    <td class="num">{{ number_format(max(0, $cycle['total'] - $cycle['paid']), 2) }}</td>
                    <td class="num {{ $cycle['penalty_net'] > 0 ? 'penalty-flag' : '' }}">{{ number_format($cycle['penalty_net'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <div class="sec">Fee Cycle</div>
        <p class="muted" style="font-size:8.5px;">No fee cycle defined for this year — the fee is collected in full.</p>
    @endforelse

    {{-- ══════════ OVERALL ══════════ --}}
    @php $totalPenalty = collect($cycles)->sum('penalty_net'); @endphp
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
        @if ($totalPenalty > 0)
            <tr>
                <td>Penalty <span class="muted">(overdue installments)</span></td>
                <td class="num penalty-flag">{{ number_format($totalPenalty, 2) }}</td>
                <td class="num muted">still due</td>
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
    <div class="foot-num">Receipt No. {{ $payment->receipt_number }}</div>
</div>

<div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
</body>
</html>
