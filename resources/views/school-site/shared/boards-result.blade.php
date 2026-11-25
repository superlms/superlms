{{--
    What a student signed in on the Boards page sees (App\Support\BoardsStudent::data
    as $b): who they are, their personal and class details, their fee — the
    admin's View Fee figures — and the school's payment QR. Sent back as HTML
    by SchoolBoardsController and put into the page; its look is shared.boards-style.
--}}
@php
    use App\Support\SchoolSiteData;
    use App\Support\UpiQr;

    $money   = fn ($n) => SchoolSiteData::money((float) $n);
    $fee     = $b['fee'] ?? [];
    $t       = $fee['totals'] ?? ['gross' => 0, 'concession' => 0, 'net' => 0, 'paid' => 0, 'remaining' => 0];
    $cls     = $b['class'] ?? [];
    $initial = mb_strtoupper(mb_substr($b['name'] ?: 'S', 0, 1));
    $line    = implode(' · ', array_filter([
        isset($cls['Class']) ? 'Class ' . preg_replace('/^class\s+/i', '', $cls['Class']) . (isset($cls['Section']) ? ' – ' . $cls['Section'] : '') : null,
        isset($cls['Roll No']) ? 'Roll No ' . $cls['Roll No'] : null,
        isset($cls['Admission No']) ? 'Adm. No ' . $cls['Admission No'] : null,
    ]));

    $academic  = $fee['academic']['rows'] ?? [];
    $transport = $fee['transport'] ?? null;
    $route     = $transport['route'] ?? null;
    $cycles    = $fee['cycles'] ?? [];
    $payments  = array_slice(array_values(array_filter($fee['payments'] ?? [], fn ($p) => empty($p['is_concession']))), 0, 25);
    $status    = ['paid' => 'Paid', 'partial' => 'Part Paid', 'pending' => 'Due', 'na' => '—'];

    $bd     = $b['board'] ?? null;
    $pct    = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.') . '%';
    $locked = $bd ? array_keys(array_filter(['board roll number' => !$bd['roll_ok'], 'admit card' => !$bd['admit_ok']])) : [];
    $need   = !$bd ? '' : (!$bd['roll_ok']
        ? 'Your roll number shows when ' . $pct($bd['roll_need']) . ' of the fee is paid'
            . (!$bd['admit_ok'] ? ' and your admit card when ' . $pct($bd['admit_need']) . ' is paid;' : ';')
        : 'Your admit card shows when ' . $pct($bd['admit_need']) . ' of the fee is paid;');

    $qr     = $b['qr'] ?? null;
    $qrSvg  = null;
    if ($qr && empty($qr['image']) && !empty($qr['upi_id'])) {
        $qrSvg = UpiQr::svg(UpiQr::link($qr['upi_id'], $qr['payee'] ?: 'School', (float) $t['remaining']), 200);
    }
@endphp

<div class="lms-card bd-who">
    <div class="bd-who-in">
        @if (!empty($b['photo']))
            <img class="bd-ph" src="{{ $b['photo'] }}" alt="">
        @else
            <span class="bd-ph bd-ini" aria-hidden="true">{{ $initial }}</span>
        @endif
        <div class="bd-who-t">
            <h2>{{ $b['name'] }}</h2>
            @if ($line !== '')<p>{{ $line }}</p>@endif
        </div>
        <div class="bd-who-act">
            <button type="button" class="lms-btn" data-bd-print>Print</button>
            <button type="button" class="lms-btn" data-bd-out>Sign out</button>
        </div>
    </div>
    <div class="bd-figs">
        <div><span>Total Fee</span><b>{{ $money($t['net']) }}</b></div>
        <div><span>Concession</span><b>{{ $money($t['concession']) }}</b></div>
        <div class="ok"><span>Paid</span><b>{{ $money($t['paid']) }}</b></div>
        <div class="{{ $t['remaining'] > 0 ? 'due' : 'ok' }}"><span>Balance</span><b>{{ $money($t['remaining']) }}</b></div>
    </div>
</div>

@if ($bd)
    {{-- The board roll number and admit card — each only once the share of the
         fee the school asks for it is paid (More → Boards → Settings). --}}
    <div class="lms-card bd-board">
        <div class="lms-head">
            <h2>Board Roll Number &amp; Admit Card</h2>
            <span class="meta">Fee paid {{ $pct($bd['paid_pct']) }}</span>
        </div>
        @if ($locked)
            <div class="bd-msg" role="alert">
                <b>Please pay your fee to get your {{ implode(' and ', $locked) }}.</b>
                <span>
                    Pay it to your school{{ $qr && (!empty($qr['image']) || $qrSvg) ? " on the school's QR code below" : ' at the school office' }}.
                    {{ $need }} you have paid {{ $pct($bd['paid_pct']) }}.
                </span>
                @if ($qr && (!empty($qr['image']) || $qrSvg))<a href="#bd-pay" class="bd-msg-go">Pay fee ↓</a>@endif
            </div>
        @endif
        <dl class="bd-dl">
            <div>
                <dt>Board Roll Number</dt>
                <dd>
                    @if (!$bd['roll_ok'])
                        <span class="bd-lock">Shows when {{ $pct($bd['roll_need']) }} of the fee is paid</span>
                    @elseif ($bd['roll_number'])
                        <span class="bd-roll">{{ $bd['roll_number'] }}</span>
                    @else
                        <span class="muted">The school has not added it yet.</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt>Admit Card</dt>
                <dd>
                    @if (!$bd['admit_ok'])
                        <span class="bd-lock">Shows when {{ $pct($bd['admit_need']) }} of the fee is paid</span>
                    @elseif ($bd['admit_url'])
                        <a class="lms-btn bd-dl-btn" href="{{ $bd['admit_url'] }}" target="_blank" rel="noopener">Download Admit Card</a>
                    @else
                        <span class="muted">The school has not added it yet.</span>
                    @endif
                </dd>
            </div>
        </dl>
    </div>
@endif

<div class="bd-cols">
    <div class="lms-card">
        <div class="lms-head"><h2>Personal Details</h2></div>
        <dl class="bd-dl">
            @foreach ($b['personal'] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
    </div>
    <div class="lms-card">
        <div class="lms-head"><h2>Class Details</h2></div>
        <dl class="bd-dl">
            @foreach ($cls as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
    </div>
</div>

<div class="lms-card">
    <div class="lms-head">
        <h2>Fee Details</h2>
        @if (!empty($cycles[0]['year']))<span class="meta">Session {{ $cycles[0]['year'] }}</span>@endif
    </div>
    @if (!$academic && !$route)
        <p class="bd-none">No fee has been set for your class yet.</p>
    @else
        <div class="lms-scroll">
            <table class="lms-tbl">
                <thead><tr><th class="sno">#</th><th>Fee</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @php $n = 0; @endphp
                    @foreach ($academic as $row)
                        <tr>
                            <td class="sno">{{ ++$n }}</td>
                            <td class="strong">{{ $row['fee_name'] }}@if (!empty($row['is_dues'])) <span class="muted">(Last Year Dues)</span>@endif</td>
                            <td class="num">{{ $money($row['amount']) }}</td>
                        </tr>
                    @endforeach
                    @if ($route)
                        <tr>
                            <td class="sno">{{ ++$n }}</td>
                            <td class="strong">Transport — {{ $route['name'] }} <span class="muted">({{ $route['months'] }} months × {{ $money($route['monthly']) }})</span></td>
                            <td class="num">{{ $money($transport['gross']) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    @if ($t['concession'] > 0)
                        <tr><td></td><td>Concession</td><td class="num">− {{ $money($t['concession']) }}</td></tr>
                    @endif
                    <tr><td></td><td>Total Fee</td><td class="num">{{ $money($t['net']) }}</td></tr>
                    <tr><td></td><td>Paid</td><td class="num">{{ $money($t['paid']) }}</td></tr>
                    <tr><td></td><td>Balance</td><td class="num total">{{ $money($t['remaining']) }}</td></tr>
                </tfoot>
            </table>
        </div>
    @endif

    @foreach ($cycles as $cy)
        @if (!empty($cy['installments']))
            <p class="bd-sub">{{ $cy['fee_type'] === 'transport' ? 'Transport' : 'Fee' }} Installments <span class="muted">· {{ $cy['label'] }}</span></p>
            <div class="lms-scroll">
                <table class="lms-tbl">
                    <thead><tr><th class="sno">#</th><th>Installment</th><th>Due Date</th><th class="num">Amount</th><th class="num">Paid</th><th class="num">Balance</th><th class="fit">Status</th></tr></thead>
                    <tbody>
                        @foreach ($cy['installments'] as $i => $in)
                            <tr>
                                <td class="sno">{{ $i + 1 }}</td>
                                <td class="strong nw">{{ $in['label'] }}</td>
                                <td class="nw">{{ $in['due_date'] ?: '—' }}</td>
                                <td class="num">{{ $money($in['amount']) }}</td>
                                <td class="num">{{ $money($in['paid']) }}</td>
                                <td class="num">{{ $money($in['balance']) }}</td>
                                <td class="fit"><span class="bd-st {{ $in['status'] }}">{{ $status[$in['status']] ?? ucfirst($in['status']) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <p class="bd-sub">Payments</p>
    @if ($payments)
        <div class="lms-scroll">
            <table class="lms-tbl">
                <thead><tr><th class="sno">#</th><th>Date</th><th>Receipt No</th><th>Fee</th><th>Mode</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    @foreach ($payments as $i => $p)
                        <tr>
                            <td class="sno">{{ $i + 1 }}</td>
                            <td class="nw">{{ $p['payment_date'] ?: '—' }}</td>
                            <td class="nw">{{ $p['receipt_number'] ?: '—' }}</td>
                            <td class="nw">{{ $p['kind'] === 'transport' ? 'Transport' : (!empty($p['is_dues']) ? 'Last Year Dues' : ucfirst((string) $p['fee_type'])) }}</td>
                            <td class="nw">{{ $p['payment_mode'] ? ucfirst((string) $p['payment_mode']) : '—' }}</td>
                            <td class="num strong">{{ $money($p['amount']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="bd-none">No payment yet.</p>
    @endif
</div>

@if ($qr && (!empty($qr['image']) || $qrSvg))
    <div class="lms-card bd-pay" id="bd-pay">
        <div class="lms-head">
            <h2>Pay Fee</h2>
            <p class="note">Scan the school's QR with any UPI app to pay your fee.</p>
        </div>
        <div class="bd-pay-in">
            <div class="bd-qr">
                @if (!empty($qr['image']))
                    <img src="{{ $qr['image'] }}" alt="{{ $b['school'] }} payment QR">
                @else
                    {!! $qrSvg !!}
                @endif
            </div>
            <div class="bd-pay-t">
                <span class="muted">Balance to pay</span>
                <h3>{{ $money($t['remaining']) }}</h3>
                @if (!empty($qr['payee']))<p>Pay to <span class="upi">{{ $qr['payee'] }}</span></p>@endif
                @if (!empty($qr['upi_id']))<p>UPI ID <span class="upi">{{ $qr['upi_id'] }}</span></p>@endif
                @if (!empty($qr['instructions']))<p style="white-space:pre-line">{{ $qr['instructions'] }}</p>@endif
                @if (!empty($qr['link']))
                    <a class="bd-upi" href="{{ $qr['link'] }}">Pay {{ $money($t['remaining']) }} in a UPI app</a>
                @endif
            </div>
        </div>
    </div>
@endif
