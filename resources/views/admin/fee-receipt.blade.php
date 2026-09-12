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
        <div class="payamt-row">
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
        <div class="words"><span class="words-lbl">Amount in Words</span>{{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}</div>
    </div>

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
                        @php $instNet = (float) ($inst['penalty_net'] ?? 0); @endphp
                        <td class="num {{ $instNet > 0 ? 'penalty-flag' : 'muted' }}">
                            {{ $instNet > 0 ? number_format($instNet, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
                <tr class="sum">
                    <td colspan="3">{{ $cycle['paid_count'] }} of {{ count($cycle['installments']) }} cleared</td>
                    <td class="num">{{ number_format($cycle['total'], 2) }}</td>
                    <td class="num">{{ number_format($cycle['paid'], 2) }}</td>
                    <td class="num">{{ number_format(max(0, $cycle['total'] - $cycle['paid']), 2) }}</td>
                    <td class="num {{ ($cycle['penalty_net'] ?? 0) > 0 ? 'penalty-flag' : '' }}">{{ number_format($cycle['penalty_net'] ?? 0, 2) }}</td>
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
            <td class="num muted" style="width:22mm;">Paid {{ number_format($overall['academic']['paid'], 2) }}</td>
        </tr>
        @if ($route)
            <tr>
                <td>Transport fee <span class="muted">({{ $route->route_name }})</span></td>
                <td class="num">{{ number_format($overall['transport']['total'], 2) }}</td>
                <td class="num muted">Paid {{ number_format($overall['transport']['paid'], 2) }}</td>
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
        <tr>
            <td>Penalty <span class="muted">(overdue installments)</span></td>
            <td class="num {{ $totalPenalty > 0 ? 'penalty-flag' : 'muted' }}">{{ $totalPenalty > 0 ? number_format($totalPenalty, 2) : '—' }}</td>
            <td class="num muted">{{ $totalPenalty > 0 ? 'still due' : '' }}</td>
        </tr>
        <tr class="grand">
            <td>Total payable</td>
            <td class="num">{{ number_format($overall['total'], 2) }}</td>
            <td class="num">Balance {{ number_format($overall['balance'], 2) }}</td>
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
