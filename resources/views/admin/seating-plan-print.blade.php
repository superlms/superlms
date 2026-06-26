<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seating Plan — {{ $plan->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #fff; padding: 24px; }
        .toolbar { text-align: center; margin-bottom: 16px; }
        .toolbar button {
            background: #111827; color: #fff; border: 0; padding: 8px 18px;
            border-radius: 6px; font-size: 13px; cursor: pointer; font-weight: 600;
        }
        .room { page-break-after: always; margin-bottom: 32px; }
        .room:last-child { page-break-after: auto; }
        .head {
            border: 2px solid #111827; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px;
            display: flex; justify-content: space-between; align-items: flex-start; gap: 16px;
        }
        .head h1 { font-size: 18px; }
        .head h2 { font-size: 15px; color: #374151; margin-top: 2px; }
        .head .meta { font-size: 12px; color: #6b7280; margin-top: 4px; line-height: 1.5; }
        .head .right { text-align: right; font-size: 12px; }
        .head .right strong { display: block; font-size: 13px; color: #111827; }
        .board { text-align: center; font-size: 11px; letter-spacing: 1px; color: #6b7280;
            border: 1px dashed #9ca3af; border-radius: 4px; padding: 4px; margin-bottom: 10px; }
        table.grid { border-collapse: collapse; width: 100%; }
        table.grid td {
            border: 1px solid #d1d5db; width: 90px; height: 58px; vertical-align: middle;
            text-align: center; font-size: 10px; padding: 3px; line-height: 1.3;
        }
        td.seat .num { font-weight: 700; font-size: 11px; color: #111827; }
        td.seat .name { display: block; color: #374151; }
        td.seat .cls { display: block; color: #2563eb; font-weight: 600; font-size: 9px; }
        td.empty { background: #f9fafb; color: #9ca3af; }
        td.conflict { background: #fef2f2; }
        .summary { display: flex; gap: 18px; margin-top: 10px; font-size: 12px; color: #374151; }
        .summary span strong { color: #111827; }
        .footer { margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 8px;
            font-size: 11px; color: #6b7280; display: flex; justify-content: space-between; }
        .sign { margin-top: 26px; display: flex; justify-content: space-between; font-size: 12px; }
        .sign div { border-top: 1px solid #9ca3af; padding-top: 4px; width: 200px; text-align: center; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            .room { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button onclick="window.print()">🖨 Print / Save as PDF</button>
    </div>

    @foreach ($rooms as $room)
        @php
            $roomAssignments = $assignments->where('room_id', $room->id);
            $cells = [];
            foreach ($roomAssignments as $a) {
                if ($a->seat) $cells[$a->seat->row_no][$a->seat->col_no] = $a;
            }
            $filled = $roomAssignments->whereNotNull('student_id')->count();
            $empty  = $room->capacity - $filled;
            $roomInvs = $invigilators->where('room_id', $room->id)
                ->map(fn($i) => $i->invigilator->name ?? null)->filter()->values();
        @endphp
        <div class="room">
            <div class="head">
                <div>
                    <h1>{{ $plan->exam->exam_name ?? 'Examination' }}</h1>
                    <h2>{{ $room->room_name }}{{ $room->building ? ' — ' . $room->building : '' }}</h2>
                    <div class="meta">
                        Plan: {{ $plan->name }}<br>
                        Date: {{ $plan->exam_date?->format('d M Y') }}{{ $plan->session ? ' · ' . ucfirst($plan->session) : '' }}
                    </div>
                </div>
                <div class="right">
                    <strong>Invigilator(s)</strong>
                    {{ $roomInvs->isNotEmpty() ? $roomInvs->implode(', ') : '—' }}
                </div>
            </div>

            <div class="board">⬆ FRONT — BOARD / INVIGILATOR</div>

            <table class="grid">
                @for ($r = 1; $r <= $room->rows; $r++)
                    <tr>
                        @for ($c = 1; $c <= $room->columns; $c++)
                            @php $cell = $cells[$r][$c] ?? null; @endphp
                            @if ($cell && $cell->student_id)
                                <td class="seat {{ $cell->has_conflict ? 'conflict' : '' }}">
                                    <span class="num">{{ $cell->seat->seat_number ?? '' }}</span>
                                    <span class="name">{{ $cell->student->name ?? '' }}</span>
                                    <span class="cls">{{ $cell->class_label }}</span>
                                </td>
                            @else
                                <td class="empty">
                                    {{ $cell->seat->seat_number ?? '' }}<br>—
                                </td>
                            @endif
                        @endfor
                    </tr>
                @endfor
            </table>

            <div class="summary">
                <span>Capacity: <strong>{{ $room->capacity }}</strong></span>
                <span>Assigned: <strong>{{ $filled }}</strong></span>
                <span>Empty: <strong>{{ $empty }}</strong></span>
                <span>Layout: <strong>{{ $room->rows }} × {{ $room->columns }}</strong></span>
            </div>

            <div class="sign">
                <div>Invigilator Signature</div>
                <div>Examination Controller</div>
            </div>

            <div class="footer">
                <span>Generated: {{ $plan->generated_at?->format('d M Y H:i') ?? now()->format('d M Y H:i') }}</span>
                <span>{{ $plan->name }}</span>
            </div>
        </div>
    @endforeach

    @if ($rooms->isEmpty())
        <p style="text-align:center;color:#6b7280;padding:40px;">No seat assignments found for this plan.</p>
    @endif

    <script>window.addEventListener('load', () => { /* auto-print disabled; user clicks button */ });</script>
</body>
</html>
