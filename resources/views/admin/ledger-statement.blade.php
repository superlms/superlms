<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ledger Statement</title>
    <style>
        @page { margin: 24px 28px 40px 28px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #1f2937; }

        /* ─── School header ─────────────────────────────────────────────── */
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .header .logo { width: 70px; vertical-align: middle; }
        .header .logo img { height: 64px; width: 64px; object-fit: contain; }
        .header .school { text-align: center; vertical-align: middle; }
        .header .school-name { font-family: "Times New Roman", Times, serif; font-size: 20px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px; }
        .header .school-meta { font-size: 9px; color: #4b5563; margin-top: 3px; }
        .header .spacer { width: 70px; }

        .doc-title { text-align: center; font-size: 13px; font-weight: bold; color: #111827; margin: 6px 0 2px; letter-spacing: 1px; }
        .period { text-align: center; font-size: 10px; color: #4b5563; margin-bottom: 12px; }

        /* ─── Summary band ──────────────────────────────────────────────── */
        .summary { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .summary td { width: 25%; border: 1px solid #e5e7eb; padding: 7px 9px; }
        .summary .label { font-size: 8px; text-transform: uppercase; letter-spacing: 0.5px; color: #6b7280; }
        .summary .value { font-size: 12px; font-weight: bold; color: #111827; margin-top: 2px; }
        .summary .credit { color: #047857; }
        .summary .expense { color: #b91c1c; }

        /* ─── Statement table ───────────────────────────────────────────── */
        table.stmt { width: 100%; border-collapse: collapse; }
        table.stmt thead th {
            background: #1e3a8a; color: #fff; font-size: 9px; text-transform: uppercase;
            letter-spacing: 0.4px; padding: 7px 8px; text-align: left; border: 1px solid #1e3a8a;
        }
        table.stmt tbody td { padding: 6px 8px; border: 1px solid #e5e7eb; font-size: 9.5px; vertical-align: top; }
        table.stmt tbody tr:nth-child(even) { background: #f9fafb; }
        .num { text-align: right; white-space: nowrap; }
        .src { font-size: 7.5px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px; }
        .credit-amt { color: #047857; font-weight: bold; }
        .expense-amt { color: #b91c1c; font-weight: bold; }
        .opening-row td { background: #eef2ff; font-weight: bold; }
        .total-row td { background: #f3f4f6; font-weight: bold; font-size: 10px; border-top: 2px solid #9ca3af; }
        .closing-row td { background: #1e3a8a; color: #fff; font-weight: bold; font-size: 11px; }

        .footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 8px; color: #9ca3af; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>

    {{-- ─── Header ─────────────────────────────────────────────────────── --}}
    <div class="header">
        <table>
            <tr>
                <td class="logo">
                    @if ($logoSrc)
                        <img src="{{ $logoSrc }}" alt="Logo">
                    @endif
                </td>
                <td class="school">
                    <div class="school-name">{{ $org->name ?? 'School Name' }}</div>
                    @if (!empty($org?->address))
                        <div class="school-meta">{{ $org->address }}</div>
                    @endif
                    <div class="school-meta">
                        @if (!empty($org?->mobile_number)) Phone: {{ $org->mobile_number }} @endif
                        @if (!empty($org?->email)) &nbsp;|&nbsp; {{ $org->email }} @endif
                        @if (!empty($org?->affiliation_no)) &nbsp;|&nbsp; Affiliation: {{ $org->affiliation_no }} @endif
                    </div>
                </td>
                <td class="spacer"></td>
            </tr>
        </table>
    </div>

    <div class="doc-title">ACCOUNT STATEMENT</div>
    <div class="period">
        Period: {{ $start->format('d M Y') }} &nbsp;to&nbsp; {{ $end->format('d M Y') }}
        &nbsp;·&nbsp; Generated {{ $generatedAt->format('d M Y, g:i A') }}
    </div>

    {{-- ─── Summary band ───────────────────────────────────────────────── --}}
    <table class="summary">
        <tr>
            <td>
                <div class="label">Opening Balance</div>
                <div class="value">Rs. {{ number_format($opening, 2) }}</div>
            </td>
            <td>
                <div class="label">Total Credit</div>
                <div class="value credit">Rs. {{ number_format($totalCredit, 2) }}</div>
            </td>
            <td>
                <div class="label">Total Expense</div>
                <div class="value expense">Rs. {{ number_format($totalExpense, 2) }}</div>
            </td>
            <td>
                <div class="label">Closing Balance</div>
                <div class="value">Rs. {{ number_format($closing, 2) }}</div>
            </td>
        </tr>
    </table>

    {{-- ─── Statement ──────────────────────────────────────────────────── --}}
    <table class="stmt">
        <thead>
            <tr>
                <th style="width: 62px;">Date</th>
                <th>Particulars</th>
                <th style="width: 90px;">By</th>
                <th class="num" style="width: 70px;">Credit</th>
                <th class="num" style="width: 70px;">Expense</th>
                <th class="num" style="width: 78px;">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="opening-row">
                <td colspan="5">Opening Balance b/f</td>
                <td class="num">Rs. {{ number_format($opening, 2) }}</td>
            </tr>

            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['date']->format('d M Y') }}</td>
                    <td>
                        {{ $row['reason'] }}
                        <div class="src">{{ $row['source'] }}</div>
                    </td>
                    <td>{{ $row['party'] }}</td>
                    <td class="num">{{ $row['type'] === 'credit' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
                    <td class="num">{{ $row['type'] === 'expense' ? 'Rs. ' . number_format($row['amount'], 2) : '' }}</td>
                    <td class="num">Rs. {{ number_format($row['balance'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center; padding: 18px; color:#9ca3af;">
                        No transactions in this period.
                    </td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="3" class="num">Period Totals</td>
                <td class="num credit-amt">Rs. {{ number_format($totalCredit, 2) }}</td>
                <td class="num expense-amt">Rs. {{ number_format($totalExpense, 2) }}</td>
                <td class="num"></td>
            </tr>
            <tr class="closing-row">
                <td colspan="5">Closing Balance c/f</td>
                <td class="num">Rs. {{ number_format($closing, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <p class="muted" style="margin-top: 14px; font-size: 8.5px;">
        Net balance across all records to date: <strong>Rs. {{ number_format($netBalance, 2) }}</strong>.
        Credits include academic &amp; transport fee collections and manual credit entries.
        Expenses include paid staff salaries and manual expense entries.
        This is a system-generated statement and does not require a signature.
    </p>

    <div class="footer">
        {{ $org->name ?? 'School' }} — Account Statement — Page <span class="pagenum"></span>
    </div>

</body>
</html>
