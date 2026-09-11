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

        /* Doc tag — receipt kind, number, date — a thin line above the masthead */
        .doctag { text-align: right; font-size: 7.5px; color: #6b7280; letter-spacing: .3px; }
        .doctag .kind { text-transform: uppercase; letter-spacing: 1.4px; }
        .doctag .no { font-weight: bold; color: #111827; margin: 0 3px; }

        /* Masthead — logo first, then name, then address, then contacts, all centred */
        .masthead { text-align: center; border-bottom: 1px solid #111827; padding-bottom: 7px; margin-top: 2px; }
        .masthead .logo { height: 30px; width: auto; margin-bottom: 4px; }
        .masthead .school { font-size: 13.5px; font-weight: bold; letter-spacing: .3px; text-transform: uppercase; }
        .masthead .addr { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
        .masthead .contact { font-size: 7.5px; color: #6b7280; margin-top: 1px; }

        /* Section heads */
        .sec { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #9ca3af;
               margin: 9px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }

        /* Key/value pairs, two to a row */
        table.kv td { padding: 1.6px 0; vertical-align: top; font-size: 9px; }
        table.kv td.k { color: #9ca3af; width: 22mm; }
        table.kv td.v { font-weight: bold; padding-right: 6mm; }

        /* This Payment — a minimalistic grid of cells rather than kv rows */
        .payinfo { display: flex; flex-wrap: wrap; border: 1px solid #e5e7eb; border-radius: 3px;
                   margin-top: 3px; overflow: hidden; }
        .payinfo .cell { flex: 1 1 33.33%; box-sizing: border-box; padding: 5px 7px;
                          border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; }
        .payinfo .cell:nth-child(3n) { border-right: 0; }
        .payinfo .lbl { font-size: 6.5px; letter-spacing: 1px; text-transform: uppercase; color: #9ca3af; }
        .payinfo .val { font-size: 9.5px; font-weight: bold; margin-top: 1.5px; }
        .payinfo .val.status-late { color: #b45309; }
        .payinfo .val.status-ontime { color: #0a8a4a; }
        .remark-line { font-size: 8px; color: #6b7280; margin-top: 4px; }

        /* Data tables */
        table.dt th { font-size: 7px; letter-spacing: .4px; text-transform: uppercase; color: #9ca3af;
                      font-weight: normal; text-align: left; padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        table.dt td { padding: 3px 0; font-size: 8.5px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
        table.dt td.sl { color: #cbd0d8; font-size: 8px; width: 5mm; }
        table.dt tr.sum td { border-top: 1px solid #111827; border-bottom: 0; font-weight: bold; padding-top: 4px; }
        .muted { color: #9ca3af; }
        .period-range { display: block; font-size: 6.8px; color: #9ca3af; margin-top: 1px; }
        .penalty-flag { color: #b45309; }

        /* The amount this receipt is for — the minimalistic focal point */
        .paid-for { border: 1px solid #111827; border-radius: 3px; padding: 7px 9px; margin-top: 4px; }
        .paid-for .row { display: flex; align-items: baseline; justify-content: space-between; }
        .paid-for .lbl { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #6b7280; }
        .paid-for .amt { font-size: 18px; font-weight: bold; }
        .paid-for .breakdown { margin-top: 5px; padding-top: 5px; border-top: 1px solid #e5e7eb;
                                display: flex; flex-wrap: wrap; gap: 8px; font-size: 7.5px; color: #6b7280; }
        .paid-for .breakdown b { color: #111827; }
        .words { font-size: 7.5px; color: #6b7280; margin-top: 2px; }

        /* Penalties section — which installments carry an overdue penalty */
        table.pen td, table.pen th { font-size: 8.5px; }
        table.pen th { font-size: 7px; letter-spacing: .4px; text-transform: uppercase; color: #9ca3af;
                       font-weight: normal; text-align: left; padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        table.pen td { padding: 3px 0; border-bottom: 1px solid #f3f4f6; }
        table.pen tr.sum td { border-top: 1px solid #111827; border-bottom: 0; font-weight: bold; padding-top: 4px; color: #b45309; }
        .no-penalty { font-size: 8.5px; color: #9ca3af; padding: 2px 0 4px; }

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

    {{-- ══════════ DOC TAG ══════════ --}}
    <div class="doctag">
        <span class="kind">Fee Receipt</span>
        <span class="no">{{ $payment->receipt_number }}</span>
        · {{ optional($payment->payment_date)->format('d M Y') }}
    </div>

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
                    <span class="muted" style="font-weight:normal;">· {{ $payment->created_at->format('h:i A') }}</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- ══════════ THIS PAYMENT ══════════ --}}
    @php
        $isLate = (float) $payment->penalty_amount > 0;
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
        <div class="cell">
            <div class="lbl">Fee Type</div>
            <div class="val">{{ ucfirst($payment->fee_type) }}</div>
        </div>
    </div>
    @if ($payment->remark)
        <div class="remark-line"><strong>Remark —</strong> {{ $payment->remark }}</div>
    @endif

    <div class="paid-for">
        <div class="row">
            <span class="lbl">Amount Received</span>
            <span class="amt">₹{{ number_format((float) $payment->amount, 2) }}</span>
        </div>
        @if ((float) $payment->penalty_amount > 0 || (float) $payment->waiver_amount > 0)
            <div class="breakdown">
                @if ((float) $payment->penalty_amount > 0)
                    <span>Penalty included <b>₹{{ number_format((float) $payment->penalty_amount, 2) }}</b></span>
                @endif
                @if ((float) $payment->waiver_amount > 0)
                    <span>Waiver applied <b>₹{{ number_format((float) $payment->waiver_amount, 2) }}</b></span>
                @endif
            </div>
        @endif
    </div>

    {{-- ══════════ FEE CYCLE ══════════ --}}
    @forelse ($cycles as $cycle)
        <div class="sec">
            {{ ucfirst($cycle['fee_type']) }} Fee Cycle — {{ $cycle['label'] }} · {{ $cycle['year'] }}
        </div>
        <table class="dt">
            <thead>
                <tr>
                    <th style="width:5mm;">#</th>
                    <th>Installment</th>
                    <th style="width:26mm;">Period</th>
                    <th class="num" style="width:15mm;">Amount</th>
                    <th class="num" style="width:15mm;">Paid</th>
                    <th class="num" style="width:15mm;">Balance</th>
                    <th class="num" style="width:15mm;">Penalty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cycle['installments'] as $i => $inst)
                    <tr>
                        <td class="sl">{{ $i + 1 }}</td>
                        <td>{{ $inst['label'] }}</td>
                        <td class="{{ $inst['overdue'] ? '' : 'muted' }}">
                            {{ $inst['due_date'] ?? '—' }}
                            @if ($inst['start_date'] && $inst['end_date'])
                                <span class="period-range">{{ $inst['start_date'] }} – {{ $inst['end_date'] }}</span>
                            @endif
                        </td>
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
                    <td class="num {{ $cycle['penalty_total'] > 0 ? 'penalty-flag' : '' }}">{{ number_format($cycle['penalty_total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <div class="sec">Fee Cycle</div>
        <p class="muted" style="font-size:8.5px;">No fee cycle defined for this year — the fee is collected in full.</p>
    @endforelse

    {{-- ══════════ PENALTIES — which installments are late, and by how much ══════════ --}}
    @php
        $penaltyRows = collect($cycles)->flatMap(function ($cycle) {
            return collect($cycle['installments'])
                ->filter(fn ($inst) => $inst['penalty'] > 0)
                ->map(fn ($inst) => $inst + ['fee_type' => $cycle['fee_type']]);
        });
    @endphp
    <div class="sec">Penalties</div>
    @if ($penaltyRows->isEmpty())
        <p class="no-penalty">No penalties currently due — every installment is either paid or within its due date.</p>
    @else
        <table class="pen">
            <thead>
                <tr>
                    <th>Fee Cycle</th>
                    <th>Installment</th>
                    <th>Due Date</th>
                    <th class="num" style="width:16mm;">Days Late</th>
                    <th class="num" style="width:16mm;">Rate / Day</th>
                    <th class="num" style="width:18mm;">Penalty</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($penaltyRows as $row)
                    <tr>
                        <td class="muted">{{ ucfirst($row['fee_type']) }}</td>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['due_date'] ?? '—' }}</td>
                        <td class="num">{{ $row['days_late'] }}</td>
                        <td class="num">{{ number_format($row['penalty_per_day'], 2) }}</td>
                        <td class="num penalty-flag">{{ number_format($row['penalty'], 2) }}</td>
                    </tr>
                @endforeach
                <tr class="sum">
                    <td colspan="5">Total penalty outstanding</td>
                    <td class="num">{{ number_format($penaltyRows->sum('penalty'), 2) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

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
