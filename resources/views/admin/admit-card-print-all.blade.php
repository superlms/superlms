<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Admit Cards</title>
    <style>
        /* Ten cards to an A4 sheet: two columns, five rows. Each card is sized
           in mm so the grid lands the same on screen and on paper. */
        @page { size: A4 portrait; margin: 7mm; }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #111; background: #eceff3; }

        .sheet {
            width: 196mm;
            margin: 0 auto 6mm;
            background: #fff;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(5, 54mm);
            gap: 3mm;
            page-break-after: always;
        }
        .sheet:last-child { page-break-after: auto; }

        .card {
            border: 1px solid #111;
            padding: 3mm 3.5mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Head: school on the left, the words Admit Card on the right ── */
        .c-head { display: flex; align-items: center; gap: 2.5mm; border-bottom: 1px solid #111; padding-bottom: 1.6mm; }
        .c-head .logo { height: 9mm; width: 9mm; object-fit: contain; flex: 0 0 9mm; }
        .c-head .who { min-width: 0; flex: 1; }
        .c-head .school { font-size: 9.5pt; font-weight: bold; line-height: 1.15; text-transform: uppercase;
                          overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .c-head .exam { font-size: 6.5pt; color: #333; margin-top: 0.4mm;
                        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .c-head .tag { font-size: 6pt; font-weight: bold; letter-spacing: 0.4pt; text-align: right; flex: 0 0 auto; }

        /* ── Body: photo beside the student's details ── */
        .c-body { display: flex; gap: 2.5mm; padding-top: 1.8mm; flex: 1; min-height: 0; }
        .c-photo { width: 16mm; height: 20mm; border: 1px solid #999; object-fit: cover; flex: 0 0 16mm; }
        .c-photo-blank { width: 16mm; height: 20mm; border: 1px dashed #bbb; flex: 0 0 16mm;
                         font-size: 5.5pt; color: #aaa; display: flex; align-items: center; justify-content: center; text-align: center; }
        .c-name { font-size: 9pt; font-weight: bold; line-height: 1.15;
                  overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        .kv { width: 100%; border-collapse: collapse; margin-top: 1mm; }
        .kv td { font-size: 6.8pt; padding: 0.5mm 0; vertical-align: top; line-height: 1.25; }
        .kv td.k { color: #555; width: 15mm; white-space: nowrap; }
        .kv td.v { font-weight: bold; }

        /* ── Foot: card number and a line to sign ── */
        .c-foot { display: flex; align-items: flex-end; justify-content: space-between;
                  gap: 2mm; border-top: 1px solid #111; padding-top: 1.2mm; }
        .c-foot .no { font-size: 6pt; color: #333; }
        .c-foot .sign { font-size: 5.5pt; color: #333; text-align: center; }
        .c-foot .sign .line { border-top: 1px solid #111; width: 24mm; margin-bottom: 0.6mm; }

        .toolbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; background: #1e293b;
                   padding: 8px 16px; display: flex; align-items: center; gap: 10px; }
        .toolbar button { border: none; padding: 6px 18px; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; }
        .toolbar .go { background: #4f46e5; color: #fff; }
        .toolbar .close { background: #64748b; color: #fff; }
        .toolbar .count { color: #cbd5e1; font-size: 12px; }

        .empty { max-width: 196mm; margin: 60px auto; background: #fff; padding: 40px; text-align: center; color: #666; }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { margin: 0; width: auto; }
            .card { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <button class="go" onclick="window.print()">Print</button>
    <button class="close" onclick="window.close()">Close</button>
    <span class="count">{{ count($admitCards) }} card(s) · 10 per A4 sheet</span>
</div>
<div style="height:44px;" class="no-print"></div>

@forelse($admitCards->chunk(10) as $sheet)
    <div class="sheet">
        @foreach($sheet as $admitCard)
            <div class="card">
                <div class="c-head">
                    @if($organization->logo)
                        <img class="logo" src="{{ $organization->logo }}" alt="">
                    @endif
                    <div class="who">
                        <div class="school">{{ $organization->name }}</div>
                        <div class="exam">{{ $admitCard->exam_name }}@if($admitCard->academic_year) · {{ $admitCard->academic_year }}@endif</div>
                    </div>
                    <div class="tag">ADMIT<br>CARD</div>
                </div>

                <div class="c-body">
                    @if($admitCard->student_photo)
                        <img class="c-photo" src="{{ $admitCard->student_photo }}" alt="">
                    @else
                        <div class="c-photo-blank">Photo</div>
                    @endif

                    <div style="min-width:0; flex:1;">
                        <div class="c-name">{{ $admitCard->student_name }}</div>
                        <table class="kv">
                            <tr>
                                <td class="k">Class</td>
                                <td class="v">{{ $admitCard->studentDetail?->standard?->name ?? '—' }}@if($admitCard->studentDetail?->section) · {{ $admitCard->studentDetail->section->name }}@endif</td>
                            </tr>
                            <tr>
                                <td class="k">Roll No.</td>
                                <td class="v">{{ $admitCard->roll_number ?: '—' }}</td>
                            </tr>
                            @if($admitCard->exam_roll_number)
                                <tr>
                                    <td class="k">Exam Roll</td>
                                    <td class="v">{{ $admitCard->exam_roll_number }}</td>
                                </tr>
                            @endif
                            @if($admitCard->father_name)
                                <tr>
                                    <td class="k">Father</td>
                                    <td class="v">{{ $admitCard->father_name }}</td>
                                </tr>
                            @endif
                            @if($admitCard->seating_label)
                                <tr>
                                    <td class="k">Seat</td>
                                    <td class="v">{{ $admitCard->seating_label }}</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>

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
