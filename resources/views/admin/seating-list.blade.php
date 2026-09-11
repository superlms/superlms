<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seating list – {{ $heading }}</title>
    <style>
        {{-- Poppins for the PDF; empty in the browser, which has its own. --}}
        {!! $fontCss ?? '' !!}

        /* A plain landscape page: the school, what the session is, and who sits
           where. The browser copy and the PDF run off this same sheet. */
        @page { size: A4 landscape; margin: 12mm 14mm; }

        * { box-sizing: border-box; }
        body, h1, p, table, td, th, div { margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', 'Segoe UI', 'DejaVu Sans', Arial, sans-serif;
            color: #000; background: #fff; font-size: 10pt; line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        .head { text-align: center; border-bottom: 1px solid #000; padding-bottom: 7px; }
        .head .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Segoe UI', sans-serif; font-weight: 600;
            font-size: 15pt; letter-spacing: -0.01em;
        }
        .head .contacts { font-size: 8pt; margin-top: 2px; }
        .head .title {
            font-family: 'Poppins SemiBold', 'Poppins', 'Segoe UI', sans-serif; font-weight: 600;
            font-size: 11pt; margin-top: 8px; text-transform: uppercase; letter-spacing: 0.08em;
        }

        /* What this list is for, on one line under the rule. */
        .facts { width: 100%; border-collapse: collapse; margin-top: 7px; }
        .facts td { font-size: 9pt; padding: 1px 0; vertical-align: top; }
        .facts .k {
            font-family: 'Poppins SemiBold', 'Poppins', 'Segoe UI', sans-serif; font-weight: 600;
            width: 18mm; white-space: nowrap;
        }
        .facts .right { text-align: right; }

        table.list { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.list th {
            font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.05em; text-align: left;
            font-family: 'Poppins SemiBold', 'Poppins', 'Segoe UI', sans-serif; font-weight: 600;
            padding: 5px 6px; border-bottom: 1px solid #000; border-top: 1px solid #000;
        }
        table.list td { font-size: 9.5pt; padding: 4px 6px; border-bottom: 0.5pt solid #b9b9b9; }
        table.list tr:last-child td { border-bottom: 1px solid #000; }
        table.list th:first-child, table.list td:first-child { padding-left: 0; }
        table.list th:last-child, table.list td:last-child { padding-right: 0; }
        table.list .no { width: 12mm; }
        table.list .c { text-align: center; }
        table.list .seat {
            font-family: 'Poppins SemiBold', 'Poppins', 'Segoe UI', sans-serif; font-weight: 600;
            text-align: center;
        }

        .foot { width: 100%; margin-top: 18px; }
        .foot td { font-size: 9pt; vertical-align: bottom; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 1px solid #000; width: 50mm; margin: 0 0 3px auto; height: 0; }

        .empty { margin-top: 26px; text-align: center; font-size: 10pt; }

        @if($isPdf ?? false)
        /* dompdf builds a line box from the font's own metrics, and Poppins
           declares an em box of about 1.7 — so the browser's leading prints
           half again as tall and a landscape page would hold a dozen rows.
           Dividing it by that keeps the printed page and the PDF the same. */
        body, .head .school, .head .contacts, .head .title,
        .facts td, table.list th, table.list td, .foot td { line-height: 0.82; }
        @endif

        @unless($isPdf ?? false)
        /* Screen only — a plain ground and the two buttons, never printed. */
        body { background: #f1f3f6; padding: 20px 16px 44px; }
        .sheet {
            width: 297mm; max-width: 100%; min-height: 210mm; margin: 0 auto;
            background: #fff; padding: 12mm 14mm;
            box-shadow: 0 1px 3px rgba(16,24,40,.08), 0 8px 28px rgba(16,24,40,.10);
        }
        .toolbar {
            width: 297mm; max-width: 100%; margin: 0 auto 14px;
            display: flex; align-items: center; gap: 8px;
            background: #fff; border: 1px solid #e4e6ea; border-radius: 10px; padding: 8px 10px 8px 14px;
        }
        .toolbar .who { font-size: 13px; font-weight: 600; color: #16181d; margin-right: auto; }
        .toolbar button, .toolbar a {
            font-family: inherit; font-size: 12.5px; font-weight: 500; line-height: 1;
            padding: 8px 14px; border-radius: 7px; cursor: pointer; text-decoration: none;
            border: 1px solid #e4e6ea; background: #fff; color: #3f4451;
        }
        .toolbar button:hover, .toolbar a:hover { background: #f7f8fa; }
        .toolbar .go { background: #16181d; border-color: #16181d; color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
        @endunless
    </style>
</head>
<body>

@unless($isPdf ?? false)
    <div class="toolbar no-print">
        <span class="who">{{ $heading }}</span>
        <button type="button" onclick="window.opener ? window.close() : history.back()">Close</button>
        <button type="button" class="go" onclick="window.print()">Print</button>
    </div>
@endunless

<div class="sheet">
    <div class="head">
        <div class="school">{{ $organization->name ?? '' }}</div>
        @php
            $contacts = collect([
                $organization->address ?? null,
                $organization->mobile_number ?? null,
                $organization->email ?? null,
            ])->filter()->implode('  ·  ');
        @endphp
        @if ($contacts)
            <div class="contacts">{{ $contacts }}</div>
        @endif
        <div class="title">Seating List</div>
    </div>

    <table class="facts">
        <tr>
            <td class="k">Exam</td>
            <td>{{ $plan->exam->exam_name ?? '—' }}@if ($plan->exam?->academic_year) · {{ $plan->exam->academic_year }}@endif</td>
            <td class="k">Date</td>
            <td>{{ $plan->exam_date?->format('d M Y, l') ?? '—' }}{{ $plan->session ? ' · ' . $plan->session : '' }}</td>
        </tr>
        <tr>
            <td class="k">Subject</td>
            <td>{{ $subject !== '' ? $subject : '—' }}</td>
            <td class="k">{{ $room ? 'Room' : 'Class' }}</td>
            <td>
                @if ($room)
                    {{ $room->room_name }}{{ $room->building ? ' · ' . $room->building : '' }}
                    ({{ $room->rows }} × {{ $room->columns }}@if (($room->seat_capacity ?? 1) > 1) × {{ $room->seat_capacity }} @endif)
                @else
                    {{ $standard->name ?? 'All classes' }}{{ $section ? ' - ' . $section->name : '' }}
                @endif
            </td>
        </tr>
    </table>

    @if ($rows->isEmpty())
        <p class="empty">No candidates seated for this session yet.</p>
    @else
        <table class="list">
            <thead>
                <tr>
                    <th class="no">S. No.</th>
                    <th>Name of Student</th>
                    <th>Admission No.</th>
                    <th>Roll No.</th>
                    @if ($standard === null)
                        <th>Class</th>
                    @endif
                    @if ($showRoom)
                        <th>Room</th>
                    @endif
                    <th class="c">Seat No.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $row)
                    <tr>
                        <td class="no">{{ $i + 1 }}</td>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['admission'] }}</td>
                        <td>{{ $row['roll'] }}</td>
                        @if ($standard === null)
                            <td>{{ $row['class'] }}</td>
                        @endif
                        @if ($showRoom)
                            <td>{{ $row['room'] }}</td>
                        @endif
                        <td class="seat">{{ $row['seat'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="foot">
        <tr>
            <td>
                {{ $rows->count() }} candidate(s) ·
                Generated {{ now()->format('d M Y, g:i A') }}
            </td>
            <td class="sign">
                <div class="line"></div>
                Invigilator / Examination In-charge
            </td>
        </tr>
    </table>
</div>

@unless($isPdf ?? false)
<script>
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.addEventListener('load', function () { window.print(); });
    }
</script>
@endunless
</body>
</html>
