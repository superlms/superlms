<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: 'DejaVu Sans', sans-serif; box-sizing: border-box; }
    body { margin: 0; color: #111827; }
    .head { text-align: center; margin-bottom: 8px; }
    .head h1 { font-size: 16px; margin: 0; }
    .head p { font-size: 11px; color: #4b5563; margin: 2px 0 0; }
    table.list { width: 100%; border-collapse: collapse; }
    table.list th {
        font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;
        font-weight: bold; padding: 6px 8px; border-bottom: 1px solid #111827; border-top: 1px solid #111827;
        color: #374151;
    }
    table.list td { font-size: 11px; padding: 5px 8px; border-bottom: 0.5px solid #e5e7eb; }
    table.list tr:last-child td { border-bottom: 1px solid #111827; }
    table.list .no { width: 40px; color: #6b7280; }
    table.list .seat { font-weight: bold; color: #111827; }
    .foot { margin-top: 10px; font-size: 10px; color: #6b7280; }
</style>
</head>
<body>
    <div class="head">
        <h1>{{ $room->room_name }} @if($room->building)<span style="font-weight:normal;color:#6b7280;">· {{ $room->building }}</span>@endif</h1>
        <p>
            {{ $plan->name }} · {{ $plan->exam->exam_name ?? '' }}
            @if($plan->exam_date) · {{ $plan->exam_date->format('d M Y') }}@endif
            @if($plan->session) · {{ ucfirst($plan->session) }}@endif
            · {{ $room->rows }} × {{ $room->columns }} desks @if(($room->seat_capacity ?? 1) > 1)× {{ $room->seat_capacity }} @endif= {{ $room->capacity }} seats
        </p>
    </div>

    <table class="list">
        <thead>
            <tr>
                <th class="no">S. No.</th>
                <th>Seat</th>
                <th>Roll No.</th>
                <th>Admission No.</th>
                <th>Class</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($assignments as $i => $a)
                @php $sd = $students[$a->student_id] ?? null; @endphp
                <tr>
                    <td class="no">{{ $i + 1 }}</td>
                    <td class="seat">{{ \App\Support\SeatLabel::full($room->room_name, $a->seat?->row_no, $a->seat?->col_no, $a->seat_position) }}</td>
                    <td>{{ $sd->roll_no ?? '—' }}</td>
                    <td>{{ $sd->admission_no ?? '—' }}</td>
                    <td>{{ $sd ? (($sd->standard->name ?? '') . ($sd->section ? '-' . $sd->section->name : '')) : ($a->class_label ?? '—') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#9ca3af;padding:16px;">No candidates seated in this room.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">Generated {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
