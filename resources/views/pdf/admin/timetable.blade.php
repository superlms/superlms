<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Timetable — {{ $standard->name }} — {{ $section->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 14mm 12mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #111827;
            font-size: 10pt;
        }

        /* ─── Minimal header: name + title only ─── */
        .title {
            font-size: 13pt;
            font-weight: 700;
            margin-bottom: 1mm;
        }
        .subtitle {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 5mm;
        }

        /* ─── Plain table, all days as columns ─── */
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #9ca3af;
            padding: 6px 5px;
            text-align: center;
            vertical-align: middle;
        }
        thead th {
            font-weight: 700;
            font-size: 9.5pt;
            background: #f3f4f6;
        }
        th.time-col { width: 16%; }

        tbody td {
            font-size: 9pt;
            height: 44px;
        }
        td.time {
            font-weight: 600;
            font-size: 8.5pt;
            white-space: nowrap;
        }
        td.time .to { color: #6b7280; font-weight: 400; font-size: 7.5pt; display: block; }

        .subject { font-weight: 700; display: block; }
        .teacher { color: #6b7280; font-size: 8pt; display: block; margin-top: 1px; }
        .lunch { color: #9ca3af; font-style: italic; }

        .empty {
            text-align: center;
            padding: 20mm 6mm;
            color: #9ca3af;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="title">{{ $organization?->name ?? 'School' }}</div>
    <div class="subtitle">{{ $standard->name }} — {{ $section->name }} · Weekly Timetable (Mon–Sat)</div>

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
                                    <span class="subject">{{ $cell['subject'] }}</span>
                                    <span class="teacher">{{ $cell['teacher'] }}</span>
                                @else
                                    {{-- No class scheduled in this slot → free period / lunch --}}
                                    <span class="lunch">Lunch</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>
