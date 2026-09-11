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
        table.list { border-collapse: collapse; width: 100%; }
        table.list th {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;
            font-weight: 700; padding: 6px 8px; border-bottom: 1px solid #111827; border-top: 1px solid #111827;
            color: #374151;
        }
        table.list td { font-size: 11px; padding: 5px 8px; border-bottom: 0.5px solid #e5e7eb; }
        table.list tr:last-child td { border-bottom: 1px solid #111827; }
        table.list .no { width: 40px; color: #6b7280; }
        table.list .seat { font-weight: 700; color: #111827; }
        table.list tr.conflict td { background: #fef2f2; }
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
            // Seat order, occupied desks only — one row per candidate.
            $roomAssignments = $assignments->where('room_id', $room->id)
                ->whereNotNull('student_id')
                ->sortBy(fn ($a) => sprintf(
                    '%04d|%04d|%03d',
                    (int) ($a->seat?->row_no ?? 0),
                    (int) ($a->seat?->col_no ?? 0),
                    (int) ($a->seat_position ?? 1),
                ))->values();
            $filled = $roomAssignments->count();
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

            <table class="list">
                <thead>
                    <tr>
                        <th class="no">S. No.</th>
                        <th>Seat</th>
                        <th>Roll No.</th>
                        <th>Name</th>
                        <th>Class</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roomAssignments as $i => $a)
                        @php $sd = $students[$a->student_id] ?? null; @endphp
                        <tr class="{{ $a->has_conflict ? 'conflict' : '' }}">
                            <td class="no">{{ $i + 1 }}</td>
                            <td class="seat">{{ \App\Support\SeatLabel::full($room->room_name, $a->seat?->row_no, $a->seat?->col_no, $a->seat_position) }}</td>
                            <td>{{ $sd->roll_no ?? '—' }}</td>
                            <td>{{ $sd->full_name ?? ($a->student->name ?? '—') }}</td>
                            <td>{{ $a->class_label }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">No candidates seated in this room.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <div class="summary">
                <span>Capacity: <strong>{{ $room->capacity }}</strong></span>
                <span>Assigned: <strong>{{ $filled }}</strong></span>
                <span>Empty: <strong>{{ $empty }}</strong></span>
                <span>Layout: <strong>{{ $room->rows }} × {{ $room->columns }}</strong> @if(($room->seat_capacity ?? 1) > 1)<strong>× {{ $room->seat_capacity }} per desk</strong>@endif</span>
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
