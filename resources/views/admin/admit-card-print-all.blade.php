<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Admit Cards</title>
    <style>
        /* Ten cards to an A4 sheet: two columns, five rows, separated by dotted
           cut lines. Everything is sized in mm so the grid lands the same on
           screen and on paper. */
        @page { size: A4 portrait; margin: 6mm; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; background: #eceff3; }

        .sheet {
            width: 198mm;
            margin: 0 auto 6mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(2, 99mm);
            grid-template-rows: repeat(5, 57mm);
            page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; }

        /* The dotted lines are the cut guides — neighbouring cards share them,
           so one cut down the middle and one across each row frees all ten. */
        .card {
            padding: 3mm 3.5mm;
            border-right: 1px dotted #666;
            border-bottom: 1px dotted #666;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .card:nth-child(2n)   { border-right: 0; }
        .card:nth-child(n+9)  { border-bottom: 0; }

        /* ── Head ── */
        .c-head { display: flex; align-items: flex-start; gap: 2mm; border-bottom: 0.6pt solid #111; padding-bottom: 1.4mm; }
        .c-head .who { min-width: 0; flex: 1; }
        .c-head .school { font-size: 9pt; font-weight: bold; line-height: 1.1; text-transform: uppercase;
                          overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .c-head .exam { font-size: 6pt; color: #444; margin-top: 0.5mm;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .c-head .tag { font-size: 6pt; font-weight: bold; letter-spacing: 0.5pt; text-align: right; flex: 0 0 auto; padding-top: 0.5mm; }

        /* ── Identity ── */
        .kv { width: 100%; border-collapse: collapse; margin-top: 1.6mm; table-layout: fixed; }
        .kv td { font-size: 6.6pt; padding: 0.3mm 0; vertical-align: top; line-height: 1.3;
                 overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .kv td.k  { color: #666; width: 12mm; }
        .kv td.k2 { color: #666; width: 14mm; padding-left: 2mm; }
        .kv td.v  { font-weight: bold; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; margin-top: 1.6mm; table-layout: fixed; }
        .papers th { font-size: 5.6pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.2pt;
                     color: #333; text-align: left; border-bottom: 0.6pt solid #111; padding: 0.6mm 0.8mm; }
        .papers td { font-size: 6.2pt; padding: 0.7mm 0.8mm; border-bottom: 0.4pt dotted #bbb; line-height: 1.15;
                     overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .papers tr:last-child td { border-bottom: 0; }
        .papers .c { text-align: center; }
        .papers .seat { font-weight: bold; }
        .no-papers { font-size: 6pt; color: #888; margin-top: 2mm; }

        /* A long subject list gets tighter rows rather than a clipped table. */
        .card.dense .papers th { font-size: 5.2pt; padding: 0.4mm 0.6mm; }
        .card.dense .papers td { font-size: 5.6pt; padding: 0.35mm 0.6mm; }

        /* ── Foot ── */
        .c-foot { margin-top: auto; display: flex; align-items: flex-end; justify-content: space-between;
                  gap: 2mm; border-top: 0.6pt solid #111; padding-top: 1.2mm; }
        .c-foot .no { font-size: 5.6pt; color: #444; }
        .c-foot .sign { font-size: 5.4pt; color: #444; text-align: center; }
        .c-foot .sign .line { border-top: 0.6pt solid #111; width: 22mm; margin-bottom: 0.5mm; }

        .toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: #1e293b;
                   padding: 8px 16px; display: flex; align-items: center; gap: 10px; }
        .toolbar button { border: none; padding: 6px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .toolbar .go { background: #4f46e5; color: #fff; }
        .toolbar .close { background: #64748b; color: #fff; }
        .toolbar .count { color: #cbd5e1; font-size: 12px; }

        .empty { max-width: 198mm; margin: 60px auto; background: #fff; padding: 40px; text-align: center; color: #666; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { margin: 0; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button class="go" onclick="window.print()">Print</button>
    <button class="close" onclick="window.close()">Close</button>
    <span class="count">{{ count($admitCards) }} card(s) · 10 per A4 sheet · cut along the dotted lines</span>
</div>
<div style="height:44px;" class="no-print"></div>

@forelse($admitCards->chunk(10) as $sheet)
    <div class="sheet">
        @foreach($sheet as $admitCard)
            @php
                // Papers in the order the student sits them.
                $papers = collect($admitCard->subjects ?? [])
                    ->sortBy(fn ($p) => (($p['exam_date'] ?? '') ?: '9999-12-31') . ' ' . ($p['exam_time'] ?? ''))
                    ->values();
                $sessions = $admitCard->seating_sessions ?? [];
                $student  = $admitCard->studentDetail;
            @endphp
            <div class="card {{ $papers->count() > 7 ? 'dense' : '' }}">
                <div class="c-head">
                    <div class="who">
                        <div class="school">{{ $organization->name }}</div>
                        <div class="exam">{{ $admitCard->exam_name }}@if($admitCard->academic_year) · {{ $admitCard->academic_year }}@endif</div>
                    </div>
                    <div class="tag">ADMIT<br>CARD</div>
                </div>

                <table class="kv">
                    <tr>
                        <td class="k">Name</td>
                        <td class="v" colspan="3">{{ $admitCard->student_name }}</td>
                    </tr>
                    <tr>
                        <td class="k">Class</td>
                        <td class="v">{{ $student?->standard?->name ?? '—' }}@if($student?->section) · {{ $student->section->name }}@endif</td>
                        <td class="k2">Roll No.</td>
                        <td class="v">{{ $admitCard->roll_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Adm. No.</td>
                        <td class="v">{{ $student?->admission_no ?: '—' }}</td>
                        <td class="k2">{{ $admitCard->exam_roll_number ? 'Exam Roll' : 'Father' }}</td>
                        <td class="v">{{ $admitCard->exam_roll_number ?: ($admitCard->father_name ?: '—') }}</td>
                    </tr>
                </table>

                @if($papers->isNotEmpty())
                    <table class="papers">
                        <thead>
                            <tr>
                                <th style="width:26mm;">Subject</th>
                                <th style="width:16mm;">Date</th>
                                <th style="width:20mm;">Time</th>
                                <th style="width:17mm;">Room</th>
                                <th style="width:11mm;" class="c">Seat</th>
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
                                    <td>{{ $date ? $date->format('d M') . ', ' . $date->format('D') : '—' }}</td>
                                    <td>{{ $from ? ($to ? $from . '–' . $to : $from) : '—' }}</td>
                                    <td>{{ $seat['room'] ?? '—' }}</td>
                                    <td class="c seat">{{ $seat['seat'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="no-papers">Datesheet not published for this class yet.</div>
                @endif

                <div class="c-foot">
                    <div class="no">No. {{ $admitCard->admit_card_number }}</div>
                    <div class="sign">
                        <div class="line"></div>
                        Signatory
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
