<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Fee Structure</title>
    <style>
        @page { margin: 26px 30px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }

        .head { border-bottom: 2px solid #15355f; padding-bottom: 10px; margin-bottom: 14px; }
        .head table { width: 100%; border-collapse: collapse; }
        .head .logo { width: 60px; vertical-align: middle; }
        .head .logo img { height: 56px; width: 56px; object-fit: contain; }
        .head .mid { text-align: center; vertical-align: middle; }
        .head .school { font-family: "Times New Roman", Times, serif; font-size: 20px; font-weight: bold; color: #15355f; text-transform: uppercase; }
        .head .meta { font-size: 9px; color: #4b5563; margin-top: 3px; }
        .doc-title { text-align: center; font-size: 13px; font-weight: bold; letter-spacing: 1px; margin: 4px 0 2px; }
        .sub { text-align: center; font-size: 10px; color: #6b7280; margin-bottom: 14px; }

        .grp { margin-bottom: 14px; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; }
        .grp-head { background: #15355f; color: #fff; padding: 7px 11px; font-size: 11px; font-weight: bold; }
        .grp-head span { float: right; font-weight: normal; color: #e6cd7e; }
        table.fs { width: 100%; border-collapse: collapse; }
        table.fs th { background: #f3f4f6; color: #374151; font-size: 8.5px; text-transform: uppercase; letter-spacing: .4px;
            text-align: left; padding: 6px 10px; border-bottom: 1px solid #e5e7eb; }
        table.fs td { padding: 6px 10px; border-bottom: 1px solid #f0f1f4; font-size: 10.5px; }
        table.fs td.num, table.fs th.num { text-align: right; }
        .grp-total td { background: #f7f9fc; font-weight: bold; border-top: 1px solid #d8def0; }

        .grand { margin-top: 8px; background: #15355f; color: #fff; padding: 9px 12px; border-radius: 6px;
            font-size: 12px; font-weight: bold; }
        .grand span { float: right; }

        .foot { margin-top: 16px; font-size: 8.5px; color: #9ca3af; text-align: center; }
        .toolbar { text-align: center; margin: 18px; }
        .toolbar button { padding: 9px 22px; background: #15355f; color: #fff; border: 0; border-radius: 7px; cursor: pointer; font-size: 13px; }
        @media print { .toolbar { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    @php
        $logo = $org->logo ?? null;
        $mono = strtoupper(mb_substr($org->name ?? 'S', 0, 1));
    @endphp

    <div class="head">
        <table>
            <tr>
                <td class="logo">@if ($logo)<img src="{{ $logo }}" alt="logo">@endif</td>
                <td class="mid">
                    <div class="school">{{ $org->name ?? 'School' }}</div>
                    <div class="meta">
                        {{ $org->address ?? '' }}
                        @if (!empty($org?->mobile_number)) · {{ $org->mobile_number }} @endif
                        @if (!empty($org?->email)) · {{ $org->email }} @endif
                    </div>
                </td>
                <td class="logo"></td>
            </tr>
        </table>
    </div>

    <div class="doc-title">ACADEMIC FEE STRUCTURE</div>
    <div class="sub">
        {{ $filterLabel ? $filterLabel : 'All Classes' }} · Generated {{ $generatedAt->format('d M Y, g:i A') }}
    </div>

    @forelse ($groups as $g)
        <div class="grp">
            <div class="grp-head">
                {{ $g['class'] }} — {{ $g['section'] }}
                <span>{{ $g['year'] }}</span>
            </div>
            <table class="fs">
                <thead>
                    <tr>
                        <th style="width:42px;">Sl.</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Fee Name</th>
                        <th class="num" style="width:90px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($g['rows'] as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $r->standard->name ?? '—' }}</td>
                            <td>{{ $r->section->name ?? 'All Sections' }}</td>
                            <td>{{ $r->fee_name }}</td>
                            <td class="num">₹{{ number_format($r->amount, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="grp-total">
                        <td colspan="4" class="num">Total</td>
                        <td class="num">₹{{ number_format($g['total'], 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center; color:#9ca3af; padding:30px;">No academic fee structures found.</p>
    @endforelse

    @if ($groups->isNotEmpty())
        <div class="grand">Grand Total <span>₹{{ number_format($grandTotal, 2) }}</span></div>
    @endif

    <div class="foot">This is a system-generated fee structure from {{ $org->name ?? 'the school' }}.</div>

    @if (!empty($printable))
        <div class="toolbar"><button onclick="window.print()">🖨 Print / Save as PDF</button></div>
    @endif
</body>
</html>
