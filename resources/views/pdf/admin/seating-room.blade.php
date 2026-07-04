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
    .board { text-align: center; font-size: 10px; color: #6b7280; letter-spacing: 2px; margin: 6px 0; text-transform: uppercase; }
    table.grid { width: 100%; border-collapse: collapse; }
    table.grid td { border: 1px solid #d1d5db; width: {{ $room->columns > 0 ? floor(100 / $room->columns) : 20 }}%; height: 56px; vertical-align: top; padding: 3px 4px; }
    .seatno { font-size: 9px; font-weight: bold; color: #374151; }
    .cls { font-size: 10px; font-weight: bold; color: #1d4ed8; }
    .adm { font-size: 10px; color: #111827; }
    .empty td, .empty { color: #9ca3af; }
    .empty-cell { color: #d1d5db; font-size: 9px; }
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
            · {{ $room->rows }} × {{ $room->columns }} = {{ $room->capacity }} seats
        </p>
    </div>

    <div class="board">⬆ Front (Board)</div>

    <table class="grid">
        @for ($r = 1; $r <= $room->rows; $r++)
            <tr>
                @for ($c = 1; $c <= $room->columns; $c++)
                    @php $cell = $cells[$r][$c] ?? null; @endphp
                    @if ($cell && $cell->student_id)
                        @php $sd = $students[$cell->student_id] ?? null; @endphp
                        <td>
                            <div class="seatno">{{ $cell->seat->seat_number ?? ($r . '-' . $c) }}</div>
                            <div class="cls">{{ $sd ? (($sd->standard->name ?? '') . ($sd->section ? '-' . $sd->section->name : '')) : $cell->class_label }}</div>
                            <div class="adm">Adm: {{ $sd->admission_no ?? '—' }}</div>
                        </td>
                    @else
                        <td class="empty">
                            <div class="seatno">{{ $cell->seat->seat_number ?? ($r . '-' . $c) }}</div>
                            <div class="empty-cell">— empty —</div>
                        </td>
                    @endif
                @endfor
            </tr>
        @endfor
    </table>

    <div class="foot">Generated {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
