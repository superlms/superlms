<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Account Statement</title>
    <style>
        {!! $fontCss ?? '' !!}

        /* No `* { margin: 0 }` here — that wipes the @page margins and the
           statement bleeds to the paper edge. */
        @page { margin: 30px 34px 46px 34px; }
        * { box-sizing: border-box; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', sans-serif;
            font-size: 10px; color: #1a1a1a; line-height: 1.45;
        }
        /* dompdf collapses font-weight per family, so each weight is its own
           family name (see App\Support\PdfFonts). */
        .semi { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        .bold { font-family: 'Poppins Bold', 'Poppins', sans-serif; }

        /* ─── Masthead (everything centred) ─────────────────────────────── */
        .masthead { text-align: center; }
        .masthead .logo { height: 54px; width: auto; }
        .school-name {
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
            font-size: 19px; color: #111; letter-spacing: 0.2px; margin-top: 6px;
        }
        .school-address { font-size: 8.5px; color: #6b7280; margin-top: 3px; }
        .school-contact { font-size: 8.5px; color: #4b5563; margin-top: 2px; }
        .school-contact span { color: #d1d5db; }

        .rule { height: 1.5px; background: #111; margin: 10px 0 0; }

        .stmt-tag { text-align: center; margin-top: 9px; }
        .stmt-tag .t1 {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 11px; letter-spacing: 2.5px; color: #111; text-transform: uppercase;
        }
        .stmt-tag .t2 { font-size: 8px; color: #9ca3af; margin-top: 2px; letter-spacing: 0.4px; }

        /* ─── Account name (centred) ────────────────────────────────────── */
        .acct { text-align: center; margin: 14px 0 12px; padding: 8px 0;
                border-top: 1px solid #ececec; border-bottom: 1px solid #ececec; }
        .acct .k { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1.2px; color: #9ca3af; }
        .acct .v { font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
                   font-size: 13px; color: #111; margin-top: 2px; }

        /* ─── Account info grid ─────────────────────────────────────────── */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { width: 50%; vertical-align: top; padding: 0; }
        .info .kv { width: 100%; border-collapse: collapse; }
        .info .kv td { padding: 2px 0; }
        .info .k { width: 96px; color: #9ca3af; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.4px; }
        .info .v { color: #111; font-size: 9.5px; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }

        /* ─── Summary chips ─────────────────────────────────────────────── */
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; border: 1px solid #dcdcdc; }
        .summary td { width: 25%; padding: 8px 10px; border-right: 1px solid #ececec; }
        .summary td:last-child { border-right: 0; }
        .summary .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #9ca3af; }
        .summary .value { font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
                          font-size: 12px; color: #111; margin-top: 3px; }
        .summary .dep { color: #047857; }
        .summary .wdr { color: #b91c1c; }

        /* ─── Statement table ───────────────────────────────────────────── */
        table.stmt { width: 100%; border-collapse: collapse; }
        table.stmt thead th {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #4b5563;
            padding: 7px 8px; text-align: left; border-top: 1.5px solid #111; border-bottom: 1px solid #111;
        }
        table.stmt tbody td { padding: 7px 8px; border-bottom: 1px solid #ededed; font-size: 9px; vertical-align: top; }
        table.stmt tbody tr:nth-child(even) td { background: #fafafa; }
        .num { text-align: right; white-space: nowrap; }
        .dep { color: #047857; }
        .wdr { color: #b91c1c; }
        .date-cell { white-space: nowrap; color: #333; }
        .date-cell .time { display: block; font-size: 7.5px; color: #9ca3af; margin-top: 1px; }
        .desc .title { color: #111; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        .desc .meta { color: #9ca3af; font-size: 7.5px; margin-top: 2px; }
        .desc .tag { color: #9ca3af; text-transform: uppercase; letter-spacing: 0.3px; font-size: 7px; }
        .bal { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #111; }

        .row-open td, .row-close td, .row-total td { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        .row-open td { border-bottom: 1px solid #111; color: #333; }
        .row-close td { border-top: 1.5px solid #111; border-bottom: 1.5px solid #111; color: #111; font-size: 10px; }
        .row-total td { border-top: 1px solid #ccc; color: #333; }

        .foot-note { margin-top: 14px; font-size: 8px; color: #9ca3af; line-height: 1.5; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; text-align: center; font-size: 7.5px; color: #c4c4c4; }
    </style>
</head>
<body>

    {{-- ─── Masthead: logo, name, address, then contacts on one line ────── --}}
    <div class="masthead">
        @if ($logoSrc)
            <img src="{{ $logoSrc }}" class="logo" alt="">
        @endif
        <div class="school-name">{{ $org->name ?? 'School Name' }}</div>

        @if (!empty($contact['address']))
            <div class="school-address">{{ $contact['address'] }}</div>
        @endif

        @php
            $bits = array_values(array_filter([
                $contact['email'] ?? null,
                $contact['website'] ?? null,
                $contact['mobile'] ?? null,
            ]));
        @endphp
        @if ($bits)
            <div class="school-contact">
                @foreach ($bits as $i => $bit)
                    @if ($i > 0)<span>&nbsp;·&nbsp;</span>@endif{{ $bit }}
                @endforeach
            </div>
        @endif
    </div>

    <div class="rule"></div>

    <div class="stmt-tag">
        <div class="t1">Account Statement</div>
        <div class="t2">Generated {{ $generatedAt->format('d M Y, g:i A') }}</div>
    </div>

    {{-- ─── Account name, centred above the rest of the details ────────── --}}
    <div class="acct">
        <div class="k">Account Name</div>
        <div class="v">{{ $org->bank_holder_name ?: ($org->name ?? '—') }}</div>
    </div>

    {{-- ─── Account / period details ───────────────────────────────────── --}}
    <table class="info">
        <tr>
            <td>
                <table class="kv">
                    @if (!empty($org?->bank_account_no))
                        <tr><td class="k">Account No.</td><td class="v">{{ $org->bank_account_no }}</td></tr>
                    @endif
                    @if (!empty($org?->bank_name))
                        <tr><td class="k">Bank</td><td class="v">{{ $org->bank_name }}@if(!empty($org?->bank_branch)), {{ $org->bank_branch }}@endif</td></tr>
                    @endif
                    @if (!empty($org?->bank_ifsc))
                        <tr><td class="k">IFSC</td><td class="v">{{ $org->bank_ifsc }}</td></tr>
                    @endif
                    <tr><td class="k">Currency</td><td class="v">INR (Rs.)</td></tr>
                </table>
            </td>
            <td>
                <table class="kv">
                    <tr>
                        <td class="k">Period</td>
                        <td class="v">
                            @if ($overall ?? false)
                                All Transactions (to date)
                            @else
                                {{ $start->format('d M Y') }} — {{ $end->format('d M Y') }}
                            @endif
                        </td>
                    </tr>
                    <tr><td class="k">Opening Bal.</td><td class="v">Rs. {{ number_format($opening, 2) }}</td></tr>
                    <tr><td class="k">Closing Bal.</td><td class="v">Rs. {{ number_format($closing, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ─── Summary chips ──────────────────────────────────────────────── --}}
    <table class="summary">
        <tr>
            <td><div class="label">Opening Balance</div><div class="value">Rs. {{ number_format($opening, 2) }}</div></td>
            <td><div class="label">Total Deposits</div><div class="value dep">Rs. {{ number_format($totalCredit, 2) }}</div></td>
            <td><div class="label">Total Withdrawals</div><div class="value wdr">Rs. {{ number_format($totalExpense, 2) }}</div></td>
            <td><div class="label">Closing Balance</div><div class="value">Rs. {{ number_format($closing, 2) }}</div></td>
        </tr>
    </table>

    {{-- ─── Transactions: deposits first, then withdrawals ─────────────── --}}
    <table class="stmt">
        <thead>
            <tr>
                <th style="width: 58px;">Date</th>
                <th>Description</th>
                <th class="num" style="width: 78px;">Deposit</th>
                <th class="num" style="width: 78px;">Withdrawal</th>
                <th class="num" style="width: 82px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="row-open">
                <td colspan="4">Opening Balance</td>
                <td class="num">Rs. {{ number_format($opening, 2) }}</td>
            </tr>

            @forelse ($rows as $row)
                <tr>
                    <td class="date-cell">
                        {{ $row['date']->format('d M Y') }}
                        @if (!empty($row['time']))<span class="time">{{ $row['time'] }}</span>@endif
                    </td>
                    <td class="desc">
                        <div class="title">{{ $row['reason'] }}</div>
                        <div class="meta">
                            {{ $row['from'] ?? '—' }} &rarr; {{ $row['to'] ?? '—' }}@if(!empty($row['mode'])) &nbsp;·&nbsp; {{ $row['mode'] }}@endif
                            &nbsp; <span class="tag">{{ $row['source'] }}</span>
                        </div>
                    </td>
                    <td class="num dep">{{ $row['type'] === 'credit' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
                    <td class="num wdr">{{ $row['type'] === 'expense' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
                    <td class="num bal">Rs. {{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align:center; padding: 22px; color:#aaa;">
                        No transactions in this period.
                    </td>
                </tr>
            @endforelse

            <tr class="row-total">
                <td colspan="2" class="num">Period Totals</td>
                <td class="num dep">Rs. {{ number_format($totalCredit, 2) }}</td>
                <td class="num wdr">Rs. {{ number_format($totalExpense, 2) }}</td>
                <td class="num"></td>
            </tr>
            <tr class="row-close">
                <td colspan="4">Closing Balance</td>
                <td class="num">Rs. {{ number_format($closing, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="foot-note">
        Deposits include academic, transport &amp; admission fee collections and manual credit entries.
        Withdrawals include paid staff salaries and manual expense entries.
        Net balance across all records to date: <span class="semi">Rs. {{ number_format($netBalance, 2) }}</span>.
        This is a system-generated statement and does not require a signature.
    </p>

    <div class="footer">
        {{ $org->name ?? 'School' }} &nbsp;·&nbsp; Account Statement &nbsp;·&nbsp; Page <span class="pagenum"></span>
    </div>

</body>
</html>
