{{--
    Transport fee receipt — the SAME sheet as the academic receipt
    (admin/fee-receipt.blade.php): same stylesheet, same masthead, same student
    block, same payment strip, same footing. What makes it a bus receipt is the
    route line and the year's transport standing at the bottom.

    The old navy-gradient design it used to carry is kept at
    admin/transport-receipt-classic.blade.php.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Fee Receipt — {{ $payment->receipt_number }}</title>
    @include('admin.partials.receipt-style')
</head>
<body>
@php
    $autoPrint = request()->boolean('print');
@endphp
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
            <td class="v">{{ $student->standard->name ?? '—' }}{{ $student && $student->section ? ' · ' . $student->section->name : '' }}</td>
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
            <div class="val">{{ ucfirst(str_replace('_', ' ', (string) $payment->payment_mode)) }}</div>
        </div>
        <div class="cell">
            <div class="lbl">Route</div>
            <div class="val">{{ $route->route_name ?? '—' }}</div>
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
            <span class="chip">Transport Fee
                @if ($payment->academic_year)<b>{{ $payment->academic_year }}</b>@endif
            </span>
            <span class="eq">=</span>
            <span class="grand">₹{{ number_format((float) $payment->amount, 2) }}</span>
        </div>
        <div class="words"><span class="words-lbl">Amount in Words</span>{{ \App\Support\NumberToWords::rupees((float) $payment->amount) }}</div>
    </div>

    {{-- ══════════ TRANSPORT STANDING ══════════ --}}
    <div class="sec">Transport{{ $route ? ' — ' . $route->route_name : '' }}</div>
    @if ($route)
        <table class="dt">
            <colgroup>
                <col class="c-sl"><col class="c-inst"><col class="c-due">
                <col class="c-num"><col class="c-num"><col class="c-num"><col class="c-num">
            </colgroup>
            <thead>
                <tr>
                    <th colspan="3">Route</th>
                    <th class="num">Per Month</th>
                    <th class="num">Months</th>
                    <th class="num">Year Total</th>
                    <th class="num">Paid</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="sl">1</td>
                    <td colspan="2">{{ $route->route_name }}@if ($route->pickup_time)<span class="muted"> · {{ $route->pickup_time }}</span>@endif</td>
                    <td class="num">{{ number_format((float) $route->monthly_fee, 2) }}</td>
                    <td class="num">{{ $billedMonths }}</td>
                    <td class="num">{{ number_format($yearTotal, 2) }}</td>
                    <td class="num {{ $paidSoFar > 0 ? '' : 'muted' }}">{{ number_format($paidSoFar, 2) }}</td>
                </tr>
                <tr class="sum">
                    <td colspan="3">Balance for the year</td>
                    <td class="num"></td>
                    <td class="num"></td>
                    <td class="num">{{ number_format($yearTotal, 2) }}</td>
                    <td class="num">{{ number_format(max(0, $yearTotal - $paidSoFar), 2) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <p class="muted" style="font-size:8.5px;">This student is not on a route right now, so only this payment is shown.</p>
    @endif

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

@unless ($autoPrint)
    <div class="toolbar"><button onclick="window.print()">Print / Save as PDF</button></div>
@endunless

@if ($autoPrint)
    {{-- Opened from the Transactions list: print straight away and close, so the
         receipt is never something the user has to look at and dismiss. --}}
    <script>
        window.addEventListener('load', function () { window.focus(); window.print(); });
        window.addEventListener('afterprint', function () { window.close(); });
    </script>
@endif
</body>
</html>
