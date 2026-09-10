<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Print Admit Cards</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        /* Four cards to a landscape A4 sheet: two columns, two rows, separated
           by dotted cut lines. Rows are added as cards arrive (grid-auto-rows),
           so a run of one card is one 98mm cell tall — not a whole blank page. */
        @page { size: A4 landscape; margin: 7mm; }

        * { box-sizing: border-box; }
        body, p, div, table, td, th { margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', 'Inter', -apple-system, 'Segoe UI', Arial, sans-serif;
            color: #16181d; background: #f1f3f6;
            -webkit-font-smoothing: antialiased;
        }

        .sheet {
            width: 283mm;
            max-width: 100%;
            margin: 0 auto 6mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(2, 141.5mm);
            grid-auto-rows: 98mm;
            page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; margin-bottom: 0; }

        /* Neighbouring cards share the dotted line, so one cut down the middle
           and one across the centre frees all four. A card with nothing beside
           or below it (a short last sheet) carries no line at all. */
        .card {
            padding: 7mm 8mm;
            border-right: 0.5pt dotted #b0b5bb;
            border-bottom: 0.5pt dotted #b0b5bb;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .card.no-r { border-right: 0; }
        .card.no-b { border-bottom: 0; }

        /* ── Head ── */
        .head { display: flex; align-items: flex-start; gap: 4mm; }
        .head .who { min-width: 0; flex: 1; }
        .head .school {
            font-size: 12pt; font-weight: 600; line-height: 1.15; letter-spacing: -0.01em;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .head .exam {
            font-size: 7.5pt; color: #6b7280; margin-top: 0.8mm;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .head .tag {
            font-size: 6.5pt; font-weight: 600; letter-spacing: 0.16em; color: #6b7280;
            text-transform: uppercase; flex: 0 0 auto; padding-top: 1.4mm;
        }
        .rule { border-top: 0.5pt solid #16181d; margin-top: 2.5mm; }

        /* ── Identity ── */
        .who-is { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3mm; }
        .who-is td {
            font-size: 8pt; padding: 0.8mm 0; vertical-align: top; line-height: 1.3;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .who-is td.k  { color: #6b7280; width: 20mm; }
        .who-is td.k2 { color: #6b7280; width: 22mm; padding-left: 5mm; }
        .who-is td.v  { font-weight: 600; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 3.5mm; }
        .papers th {
            font-size: 6.5pt; font-weight: 400; letter-spacing: 0.08em; text-transform: uppercase;
            color: #6b7280; text-align: left; padding: 0 1mm 1.4mm; border-bottom: 0.5pt solid #16181d;
        }
        .papers td {
            font-size: 7.5pt; padding: 1.4mm 1mm; border-bottom: 0.4pt solid #eef0f2; line-height: 1.2;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .papers tr:last-child td { border-bottom: 0; }
        .papers th:first-child, .papers td:first-child { padding-left: 0; }
        .papers th:last-child, .papers td:last-child { padding-right: 0; }
        .papers .c { text-align: center; }
        .papers .seat { font-weight: 600; }
        .papers .off { color: #9aa0a6; }
        .no-papers {
            font-size: 7.5pt; color: #9aa0a6; margin-top: 4mm;
            border: 0.5pt dashed #d9dce1; padding: 4mm; text-align: center;
        }

        /* A long subject list tightens rather than clipping the table. */
        .card.dense .papers th { font-size: 6pt; padding-bottom: 1mm; }
        .card.dense .papers td { font-size: 6.8pt; padding: 0.85mm 1mm; }

        /* ── Foot ── */
        .foot {
            margin-top: auto; padding-top: 2.5mm; border-top: 0.5pt solid #16181d;
            display: flex; align-items: flex-end; justify-content: space-between; gap: 4mm;
        }
        .foot .no { font-size: 6.5pt; color: #6b7280; }
        .foot .sign { font-size: 6.5pt; color: #6b7280; text-align: center; }
        .foot .sign .line { border-top: 0.5pt solid #16181d; width: 34mm; margin-bottom: 1mm; }

        /* ── Screen-only toolbar ── */
        .toolbar {
            position: sticky; top: 0; z-index: 10; width: 283mm; max-width: 100%;
            margin: 0 auto 14px; background: #fff; border: 1px solid #e4e6ea; border-radius: 10px;
            padding: 8px 10px 8px 14px; display: flex; align-items: center; gap: 8px;
        }
        .toolbar .count { font-size: 12.5px; color: #6b7280; margin-right: auto; }
        .toolbar button {
            font-family: inherit; font-size: 12.5px; font-weight: 500; line-height: 1;
            padding: 8px 14px; border-radius: 7px; cursor: pointer;
            border: 1px solid #e4e6ea; background: #fff; color: #3f4451;
        }
        .toolbar button:hover { background: #f7f8fa; }
        .toolbar .go { background: #16181d; border-color: #16181d; color: #fff; }
        .toolbar .go:hover { background: #2b2f38; }

        .empty {
            width: 283mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 48px;
            text-align: center; color: #6b7280; font-size: 14px;
        }

        body { padding: 22px 16px 48px; }

        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .sheet { margin: 0; width: auto; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <span class="count">{{ count($admitCards) }} card(s) · 4 to a landscape A4 · cut along the dotted lines</span>
    <button type="button" onclick="window.close()">Close</button>
    <button type="button" class="go" onclick="window.print()">Print</button>
</div>

@forelse($admitCards->chunk(4) as $sheet)
    @php $onSheet = $sheet->count(); @endphp
    <div class="sheet">
        @foreach($sheet->values() as $i => $admitCard)
            @php
                // Papers in the order the student sits them.
                $papers = collect($admitCard->subjects ?? [])
                    ->sortBy(fn ($p) => (($p['exam_date'] ?? '') ?: '9999-12-31') . ' ' . ($p['exam_time'] ?? ''))
                    ->values();
                $sessions = $admitCard->seating_sessions ?? [];
                $student  = $admitCard->studentDetail;

                // Cut lines only where a card actually has a neighbour, so a
                // short last sheet (one or three cards) prints clean.
                $lastRow = intdiv($i, 2) === intdiv($onSheet - 1, 2);
                $edges   = ($i % 2 === 1 || $i === $onSheet - 1 ? ' no-r' : '')
                         . ($lastRow ? ' no-b' : '');
            @endphp
            <div class="card{{ $papers->count() > 8 ? ' dense' : '' }}{{ $edges }}">
                <div class="head">
                    <div class="who">
                        <div class="school">{{ $organization->name }}</div>
                        <div class="exam">{{ $admitCard->exam_name }}@if($admitCard->academic_year) · {{ $admitCard->academic_year }}@endif</div>
                    </div>
                    <div class="tag">Admit Card</div>
                </div>
                <div class="rule"></div>

                <table class="who-is">
                    <tr>
                        <td class="k">Name</td>
                        <td class="v">{{ $admitCard->student_name }}</td>
                        <td class="k2">Roll No.</td>
                        <td class="v">{{ $admitCard->roll_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Class</td>
                        <td class="v">{{ $student?->standard?->name ?? '—' }}@if($student?->section) · {{ $student->section->name }}@endif</td>
                        <td class="k2">Adm. No.</td>
                        <td class="v">{{ $student?->admission_no ?: '—' }}</td>
                    </tr>
                    @if($admitCard->exam_roll_number || $admitCard->father_name)
                        <tr>
                            <td class="k">{{ $admitCard->exam_roll_number ? 'Exam Roll' : 'Father' }}</td>
                            <td class="v">{{ $admitCard->exam_roll_number ?: $admitCard->father_name }}</td>
                            <td class="k2"></td>
                            <td class="v"></td>
                        </tr>
                    @endif
                </table>

                @if($papers->isNotEmpty())
                    <table class="papers">
                        <thead>
                            <tr>
                                <th style="width:41mm;">Subject</th>
                                <th style="width:24mm;">Date</th>
                                <th style="width:30mm;">Time</th>
                                <th style="width:22mm;">Room</th>
                                <th style="width:12mm;" class="c">Seat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($papers as $paper)
                                @php
                                    $date = !empty($paper['exam_date']) ? \Carbon\Carbon::parse($paper['exam_date']) : null;
                                    $seat = \App\Services\Seating\SeatLocator::seatFor(
                                        $sessions,
                                        $paper['exam_date'] ?? null,
                                        $paper['shift'] ?? 1
                                    );
                                    $from = !empty($paper['exam_time']) ? \Carbon\Carbon::parse($paper['exam_time'])->format('g:i A') : '';
                                    $to   = !empty($paper['exam_end_time']) ? \Carbon\Carbon::parse($paper['exam_end_time'])->format('g:i A') : '';
                                @endphp
                                <tr>
                                    <td>{{ $paper['subject_name'] ?? '—' }}</td>
                                    <td class="{{ $date ? '' : 'off' }}">{{ $date ? $date->format('d M, D') : '—' }}</td>
                                    <td class="{{ $from ? '' : 'off' }}">{{ $from ? ($to ? $from . '–' . $to : $from) : '—' }}</td>
                                    <td class="{{ ($seat['room'] ?? null) ? '' : 'off' }}">{{ $seat['room'] ?? '—' }}</td>
                                    <td class="c seat">{{ $seat['seat'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-papers">Datesheet not published for this class yet.</div>
                @endif

                <div class="foot">
                    <div class="no">No. {{ $admitCard->admit_card_number }}</div>
                    <div class="sign">
                        <div class="line"></div>
                        Signature
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@empty
    <div class="empty">No admit cards to print.</div>
@endforelse

</body>
</html>
