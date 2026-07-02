<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Timetable — {{ $standard->name }} — {{ $section->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 14mm 12mm 14mm 12mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #1f2937;
            font-size: 10pt;
            line-height: 1.4;
        }

        /* ─── HEADER ─────────────────────────────────────────── */
        .header { text-align: center; margin-bottom: 6mm; }
        .logo {
            display: block;
            margin: 0 auto 3mm auto;
            max-height: 18mm;
            max-width: 34mm;
        }
        .org-name {
            font-size: 18pt;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #111827;
        }
        .org-contact {
            font-size: 8.5pt;
            color: #6b7280;
            margin-top: 1.5mm;
        }
        .org-contact span + span { margin-left: 5mm; }

        .doc-title {
            font-size: 13pt;
            font-weight: 700;
            color: #1e3a8a;
            margin-top: 4mm;
        }
        .doc-sub {
            font-size: 8.5pt;
            color: #6b7280;
            margin-top: 1mm;
        }

        /* ─── GRID TABLE ─────────────────────────────────────── */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        thead th {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            font-size: 9.5pt;
            text-align: center;
            padding: 7px 5px;
            border: 1px solid #1e3a8a;
        }
        th.time-col { width: 15%; background: #172a63; }

        tbody td {
            border: 1px solid #d5dbe6;
            padding: 6px 6px;
            font-size: 9pt;
            vertical-align: middle;
            text-align: center;
            height: 46px;
        }
        tbody tr:nth-child(even) td { background: #f8fafc; }

        td.time {
            background: #eef2ff;
            font-weight: 600;
            color: #3730a3;
            font-size: 8.5pt;
            white-space: nowrap;
        }
        td.time .to { color: #6366f1; font-weight: 400; font-size: 7.5pt; display: block; }

        .cell-subject {
            font-weight: 700;
            color: #111827;
            display: block;
        }
        .cell-teacher {
            color: #6b7280;
            font-size: 8pt;
            display: block;
            margin-top: 1px;
        }
        .cell-empty { color: #cbd5e1; font-weight: 400; }

        .empty {
            text-align: center;
            padding: 24mm 6mm;
            color: #94a3b8;
            font-style: italic;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            margin-top: 8mm;
        }

        .footer {
            position: fixed;
            bottom: 5mm; left: 12mm; right: 12mm;
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5mm;
            font-size: 7.5pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ═══════════ HEADER ═══════════ --}}
    @php
        $logoSrc = null;
        if (!empty($organization?->logo)) {
            if (\Illuminate\Support\Str::startsWith($organization->logo, ['http://', 'https://'])) {
                $logoSrc = $organization->logo;
            } elseif (file_exists(public_path('storage/' . $organization->logo))) {
                $logoSrc = public_path('storage/' . $organization->logo);
            }
        }

        $schoolEmail  = $schoolInfo->school_email  ?? $organization?->email         ?? null;
        $schoolMobile = $schoolInfo->school_mobile ?? $organization?->mobile_number ?? null;
    @endphp

    <div class="header">
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
        @endif

        <div class="org-name">{{ $organization?->name ?? 'School' }}</div>

        @if ($schoolEmail || $schoolMobile)
            <div class="org-contact">
                @if ($schoolEmail)<span>Email: {{ $schoolEmail }}</span>@endif
                @if ($schoolMobile)<span>Phone: {{ $schoolMobile }}</span>@endif
            </div>
        @endif

        <div class="doc-title">{{ $standard->name }} &mdash; {{ $section->name }} · Weekly Timetable</div>
        <div class="doc-sub">Academic schedule · Monday to Saturday</div>
    </div>

    {{-- ═══════════ GRID ═══════════ --}}
    @if (empty($slots))
        <div class="empty">No timetable entries scheduled for this section.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="time-col">Time</th>
                    @foreach ($days as $dayNum => $dayName)
                        <th>{{ $dayName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($slots as $slot)
                    @php $key = $slot['start_time'] . '|' . $slot['end_time']; @endphp
                    <tr>
                        <td class="time">
                            {{ \Carbon\Carbon::parse($slot['start_time'])->format('h:i A') }}
                            <span class="to">to {{ \Carbon\Carbon::parse($slot['end_time'])->format('h:i A') }}</span>
                        </td>
                        @foreach ($days as $dayNum => $dayName)
                            @php $cell = $grid[$key][$dayNum] ?? null; @endphp
                            <td>
                                @if ($cell)
                                    <span class="cell-subject">{{ $cell['subject'] }}</span>
                                    <span class="cell-teacher">{{ $cell['teacher'] }}</span>
                                @else
                                    <span class="cell-empty">—</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $organization?->name ?? 'School' }} &nbsp;·&nbsp; {{ $standard->name }} {{ $section->name }} &nbsp;·&nbsp; Generated on {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}
    </div>

</body>
</html>
