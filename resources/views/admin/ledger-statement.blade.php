<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Account Statement</title>
    <style>
        @page { margin: 30px 34px 46px 34px; }
        * { box-sizing: border-box; }
        body {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 10px; color: #1a1a1a; line-height: 1.4;
        }

        /* ─── Masthead ──────────────────────────────────────────────────── */
        .masthead { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .masthead td { vertical-align: middle; }
        .masthead .logo { width: 52px; }
        .masthead .logo img { height: 46px; width: 46px; object-fit: contain; }
        .brand-name { font-size: 17px; font-weight: bold; letter-spacing: 0.3px; color: #111; }
        .brand-meta { font-size: 8.5px; color: #666; margin-top: 2px; }
        .stmt-tag { text-align: right; }
        .stmt-tag .t1 { font-size: 12px; font-weight: bold; letter-spacing: 1.5px; color: #111; text-transform: uppercase; }
        .stmt-tag .t2 { font-size: 8px; color: #888; margin-top: 2px; letter-spacing: 0.5px; }

        .rule { height: 1.5px; background: #111; margin: 8px 0 12px; }

        /* ─── Account info grid ─────────────────────────────────────────── */
        .info { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .info td { width: 50%; vertical-align: top; padding: 0; }
        .info .kv { width: 100%; border-collapse: collapse; }
        .info .kv td { padding: 2px 0; }
        .info .k { width: 96px; color: #888; font-size: 8.5px; text-transform: uppercase; letter-spacing: 0.4px; }
        .info .v { color: #111; font-size: 9.5px; font-weight: 600; }

        /* ─── Summary chips ─────────────────────────────────────────────── */
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 16px; border: 1px solid #dcdcdc; }
        .summary td { width: 25%; padding: 8px 10px; border-right: 1px solid #ececec; }
        .summary td:last-child { border-right: 0; }
        .summary .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 0.5px; color: #888; }
        .summary .value { font-size: 12px; font-weight: bold; color: #111; margin-top: 3px; }

        /* ─── Statement table ───────────────────────────────────────────── */
        table.stmt { width: 100%; border-collapse: collapse; }
        table.stmt thead th {
            font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #555;
            padding: 7px 8px; text-align: left; border-top: 1.5px solid #111; border-bottom: 1px solid #111;
        }
        table.stmt tbody td { padding: 7px 8px; border-bottom: 1px solid #ededed; font-size: 9px; vertical-align: top; }
        table.stmt tbody tr:nth-child(even) td { background: #fafafa; }
        .num { text-align: right; white-space: nowrap; }
        .date-cell { white-space: nowrap; color: #333; }
        .date-cell .time { display: block; font-size: 7.5px; color: #999; margin-top: 1px; }
        .desc .title { color: #111; font-weight: 600; }
        .desc .meta { color: #888; font-size: 7.5px; margin-top: 2px; }
        .desc .tag { color: #999; text-transform: uppercase; letter-spacing: 0.3px; font-size: 7px; }
        .bal { font-weight: 600; color: #111; }

        .row-open td, .row-close td { font-weight: bold; }
        .row-open td { border-bottom: 1px solid #111; color: #333; }
        .row-close td { border-top: 1.5px solid #111; border-bottom: 1.5px solid #111; color: #111; font-size: 10px; }
        .row-total td { border-top: 1px solid #ccc; font-weight: bold; color: #333; }

        .foot-note { margin-top: 14px; font-size: 8px; color: #999; line-height: 1.5; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; text-align: center; font-size: 7.5px; color: #bbb; }
    </style>
</head>
<body>

    {{-- ─── Masthead ───────────────────────────────────────────────────── --}}
    <table class="masthead">
        <tr>
            @if ($logoSrc)
                <td class="logo"><img src="{{ $logoSrc }}" alt="Logo"></td>
            @endif
            <td>
                <div class="brand-name">{{ $org->name ?? 'School Name' }}</div>
                <div class="brand-meta">
                    {{ $org->address ?? '' }}@if(!empty($org?->mobile_number)) &nbsp;·&nbsp; {{ $org->mobile_number }}@endif
                </div>
            </td>
            <td class="stmt-tag">
                <div class="t1">Account Statement</div>
                <div class="t2">Generated {{ $generatedAt->format('d M Y, g:i A') }}</div>
            </td>
        </tr>
    </table>

    <div class="rule"></div>

    {{-- ─── Account / period details ───────────────────────────────────── --}}
    <table class="info">
        <tr>
            <td>
                <table class="kv">
                    <tr><td class="k">Account Name</td><td class="v">{{ $org->bank_holder_name ?: ($org->name ?? '—') }}</td></tr>
                    @if (!empty($org?->bank_account_no))
                        <tr><td class="k">Account No.</td><td class="v">{{ $org->bank_account_no }}</td></tr>
                    @endif
                    @if (!empty($org?->bank_name))
                        <tr><td class="k">Bank</td><td class="v">{{ $org->bank_name }}@if(!empty($org?->bank_branch)), {{ $org->bank_branch }}@endif</td></tr>
                    @endif
                    @if (!empty($org?->bank_ifsc))
                        <tr><td class="k">IFSC</td><td class="v">{{ $org->bank_ifsc }}</td></tr>
                    @endif
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
                    <tr><td class="k">Currency</td><td class="v">INR (Rs.)</td></tr>
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
            <td><div class="label">Total Deposits</div><div class="value">Rs. {{ number_format($totalCredit, 2) }}</div></td>
            <td><div class="label">Total Withdrawals</div><div class="value">Rs. {{ number_format($totalExpense, 2) }}</div></td>
            <td><div class="label">Closing Balance</div><div class="value">Rs. {{ number_format($closing, 2) }}</div></td>
        </tr>
    </table>

    {{-- ─── Transactions ───────────────────────────────────────────────── --}}
    <table class="stmt">
        <thead>
            <tr>
                <th style="width: 58px;">Date</th>
                <th>Description</th>
                <th class="num" style="width: 78px;">Withdrawal</th>
                <th class="num" style="width: 78px;">Deposit</th>
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
                    <td class="num">{{ $row['type'] === 'expense' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
                    <td class="num">{{ $row['type'] === 'credit' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
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
                <td class="num">Rs. {{ number_format($totalExpense, 2) }}</td>
                <td class="num">Rs. {{ number_format($totalCredit, 2) }}</td>
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
        Net balance across all records to date: <strong>Rs. {{ number_format($netBalance, 2) }}</strong>.
        This is a system-generated statement and does not require a signature.
    </p>

    <div class="footer">
        {{ $org->name ?? 'School' }} &nbsp;·&nbsp; Account Statement &nbsp;·&nbsp; Page <span class="pagenum"></span>
    </div>

</body>
</html>
