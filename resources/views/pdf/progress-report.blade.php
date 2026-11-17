{{--
    Admin → Performance → Progress Report: one exam's report card, a card to
    each A5 page (App\Support\ProgressReport). The school at the head, the
    title band with the exam and its session, the student, every subject of
    the exam with its marks and grade, the total, then percentage / grade /
    rank / result, the remark — and at the foot of every page the grading
    scale and the three signatures.

    Tables throughout, because dompdf has no flexbox. The frame and the foot
    are position:fixed, so they sit on every page of a class's PDF alike. A
    card with many subjects takes a tighter step (d1, d2) to stay on its page.

    In scope: $head (ProgressReport::head), $cards (ProgressReport::cards), $fontCss.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Progress Report — {{ $head['exam'] }}</title>
    <style>
        {!! $fontCss ?? '' !!}

        /* No `* { margin: 0 }` here — that wipes the @page margins. */
        @page { size: A5 portrait; margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; font-size: 9px; color: #1f2937; line-height: .9; }
        table { width: 100%; border-collapse: collapse; }
        /* dompdf keeps one weight per family, so each weight is its own family (App\Support\PdfFonts). */
        .sb { font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }

        /* A fine double rule 5mm in from the paper's edge, on every page. */
        .frame    { position: fixed; top: 5mm; left: 5mm; right: 5mm; bottom: 5mm; border: 1.1px solid #1e3a5f; }
        .frame-in { position: fixed; top: 6.1mm; left: 6.1mm; right: 6.1mm; bottom: 6.1mm; border: 0.5px solid #b8c4d6; }

        .card      { padding: 10mm 11mm 0; }
        .card.next { page-break-before: always; }

        /* ─── Masthead: the logo at the left, the school centred on the sheet ─── */
        table.mast { table-layout: fixed; }
        table.mast td { padding: 0; vertical-align: middle; }
        table.mast td.mid { text-align: center; }
        .logo { height: 15mm; width: auto; }
        .school { font-family: 'Merriweather Bold', 'PT Serif Bold', serif; font-size: 14px; color: #1e3a5f; line-height: .85; letter-spacing: .2px; }
        .sub { font-size: 7.3px; color: #4b5563; margin-top: 1.2px; }

        /* ─── Title band ─── */
        .title { margin-top: 3mm; padding: 1.5mm 0 1.4mm; text-align: center;
                 border-top: 0.9px solid #1e3a5f; border-bottom: 0.9px solid #1e3a5f; }
        .title .t { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 11.5px; letter-spacing: 2.6px; color: #1e3a5f; }
        .title .s { font-size: 8.2px; color: #374151; margin-top: .3mm; }

        /* ─── Student: label / value, two pairs to a row ─── */
        table.info { margin-top: 3mm; table-layout: fixed; }
        table.info td { padding: 1.15mm 0; border-bottom: 0.5px solid #e5e7eb; vertical-align: top; }
        table.info td.k { width: 21%; font-size: 6.6px; letter-spacing: .5px; text-transform: uppercase; color: #6b7280; padding-top: 1.45mm; }
        table.info td.v { width: 29%; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-size: 9px; color: #111827; padding-right: 2.5mm; }

        /* ─── Marks ─── */
        table.marks { margin-top: 3.5mm; table-layout: fixed; }
        table.marks th { background: #1e3a5f; color: #ffffff; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; font-weight: normal;
                         font-size: 6.9px; letter-spacing: .6px; text-transform: uppercase; padding: 1.7mm 2mm; text-align: left; }
        table.marks td { padding: 1.55mm 2mm; border-bottom: 0.5px solid #e5e7eb; font-size: 9px; color: #111827; }
        table.marks .c { text-align: center; }
        table.marks tr.alt td { background: #f6f8fb; }
        table.marks td.sl { color: #9ca3af; }
        table.marks td.ab { color: #b91c1c; }
        table.marks td.ne { color: #9ca3af; }
        table.marks tr.total td { background: #e9eef6; border-top: 0.9px solid #1e3a5f; border-bottom: 0.9px solid #1e3a5f;
                                  font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }

        /* ─── Percentage / grade / rank / result ─── */
        table.sum { margin-top: 3.5mm; table-layout: fixed; border: 0.7px solid #c7d2e0; }
        table.sum td { width: 25%; text-align: center; padding: 1.8mm 1mm 1.6mm; border-left: 0.7px solid #c7d2e0; }
        table.sum td.first { border-left: 0; }
        .lbl { font-size: 6.3px; letter-spacing: .8px; text-transform: uppercase; color: #6b7280; }
        .val { font-family: 'Poppins Bold', 'Poppins', sans-serif; font-size: 12.5px; color: #1e3a5f; line-height: .85; margin-top: .4mm; }
        .val.pass { color: #047857; }
        .val.fail { color: #b91c1c; }
        .val .of { font-family: 'Poppins', sans-serif; font-size: 7.5px; color: #6b7280; }

        /* ─── Remark and the date ─── */
        table.rem { margin-top: 3.2mm; }
        table.rem td { padding: 0 0 .8mm; font-size: 8.4px; vertical-align: bottom; }
        table.rem td.k { width: 17mm; color: #6b7280; }
        table.rem td.v { border-bottom: 0.6px dotted #9ca3af; font-family: 'Poppins SemiBold', 'Poppins', sans-serif; }
        table.rem td.d { width: 37mm; text-align: right; color: #6b7280; }

        /* ─── Foot of every page: the grading scale, then the signatures ─── */
        .foot { position: fixed; left: 11mm; right: 11mm; bottom: 9.5mm; }
        .scale { text-align: center; font-size: 6.8px; color: #6b7280; padding-bottom: 10mm; }
        .scale .sb { color: #374151; }
        table.sign { table-layout: fixed; }
        table.sign td { text-align: center; font-size: 7.6px; color: #374151; padding: 0 3mm; }
        .sign-line { border-top: 0.7px solid #374151; padding-top: 1mm; }

        /* ─── Tighter steps, for a card with many subjects ─── */
        .d1 table.info td { padding-top: .8mm; padding-bottom: .8mm; }
        .d1 table.info td.k { padding-top: 1.1mm; }
        .d1 table.marks td { padding-top: 1.05mm; padding-bottom: 1.05mm; font-size: 8.6px; }
        .d1 table.marks, .d1 table.sum { margin-top: 2.8mm; }

        .d2 .title { margin-top: 2.2mm; }
        .d2 table.info { margin-top: 2.2mm; }
        .d2 table.info td { padding-top: .5mm; padding-bottom: .5mm; }
        .d2 table.info td.k { padding-top: .8mm; }
        .d2 table.marks th { padding-top: 1.1mm; padding-bottom: 1.1mm; }
        .d2 table.marks td { padding-top: .55mm; padding-bottom: .55mm; font-size: 8.1px; }
        .d2 table.marks, .d2 table.sum { margin-top: 2.2mm; }
        .d2 table.sum td { padding-top: 1.2mm; padding-bottom: 1.1mm; }
        .d2 table.rem { margin-top: 2.4mm; }
    </style>
</head>
<body>
    <div class="frame"></div>
    <div class="frame-in"></div>

    <div class="foot">
        <div class="scale"><span class="sb">Grading Scale:</span> {{ $head['scale'] }}  ·  AB Absent</div>
        <table class="sign">
            <tr>
                <td><div class="sign-line">Class Teacher</div></td>
                <td><div class="sign-line">Parent / Guardian</div></td>
                <td><div class="sign-line">Principal</div></td>
            </tr>
        </table>
    </div>

    @php
        $classLabel = $head['class'] . ($head['section'] !== '' ? ' - ' . $head['section'] : '');
        $subLine = implode('  ·  ', array_filter([$head['exam'], $head['session'] !== '' ? 'Session ' . $head['session'] : null]));
        $or = fn ($v) => trim((string) $v) !== '' ? $v : '—';
    @endphp

    @foreach ($cards as $card)
        @php
            $n = count($card['rows']);
            $density = $n > 13 ? 'd2' : ($n > 10 ? 'd1' : '');
        @endphp
        <div class="card {{ $density }} {{ $loop->first ? '' : 'next' }}">

            {{-- ─── Masthead ─── --}}
            {{-- Widths inline and in %: dompdf's fixed layout reads them off the first row. --}}
            <table class="mast">
                <tr>
                    <td style="width: 15%;">
                        @if ($head['logo'])
                            <img class="logo" src="{{ $head['logo'] }}" alt="">
                        @endif
                    </td>
                    <td class="mid" style="width: 70%;">
                        <div class="school">{{ $head['school'] }}</div>
                        @foreach ($head['lines'] as $line)
                            <div class="sub">{{ $line }}</div>
                        @endforeach
                    </td>
                    <td style="width: 15%;"></td>
                </tr>
            </table>

            {{-- ─── Title band ─── --}}
            <div class="title">
                <div class="t">PROGRESS REPORT</div>
                <div class="s">{{ $subLine }}</div>
            </div>

            {{-- ─── Student ─── --}}
            <table class="info">
                <tr>
                    <td class="k">Student Name</td><td class="v">{{ $or($card['name']) }}</td>
                    <td class="k">Class</td><td class="v">{{ $or($classLabel) }}</td>
                </tr>
                <tr>
                    <td class="k">Father's Name</td><td class="v">{{ $or($card['father_name']) }}</td>
                    <td class="k">Roll No</td><td class="v">{{ $or($card['roll_no']) }}</td>
                </tr>
                <tr>
                    <td class="k">Mother's Name</td><td class="v">{{ $or($card['mother_name']) }}</td>
                    <td class="k">Admission No</td><td class="v">{{ $or($card['admission_no']) }}</td>
                </tr>
                <tr>
                    <td class="k">Date of Birth</td><td class="v">{{ $or($card['dob']) }}</td>
                    <td class="k">Exam Dates</td><td class="v">{{ $or($head['dates']) }}</td>
                </tr>
            </table>

            {{-- ─── Marks, subject by subject ─── --}}
            <table class="marks">
                <thead>
                    <tr>
                        <th style="width: 9%;">S.No</th>
                        <th style="width: 41%;">Subject</th>
                        <th class="c" style="width: 16%;">Max Marks</th>
                        <th class="c" style="width: 19%;">Marks Obtained</th>
                        <th class="c" style="width: 15%;">Grade</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($card['rows'] as $row)
                        <tr class="{{ $loop->even ? 'alt' : '' }}">
                            <td class="sl">{{ $loop->iteration }}</td>
                            <td>{{ $row['subject'] }}</td>
                            @if (!$row['entered'])
                                <td class="c ne">—</td>
                                <td class="c ne">—</td>
                                <td class="c ne">—</td>
                            @elseif ($row['absent'])
                                <td class="c">{{ $row['max'] }}</td>
                                <td class="c ab">Absent</td>
                                <td class="c ab">AB</td>
                            @else
                                <td class="c">{{ $row['max'] }}</td>
                                <td class="c sb">{{ $row['obtained'] }}</td>
                                <td class="c">{{ $row['grade'] }}</td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="total">
                        <td></td>
                        <td>Grand Total</td>
                        <td class="c">{{ $card['max'] }}</td>
                        <td class="c">{{ $card['obtained'] }}</td>
                        <td class="c">{{ $card['grade'] }}</td>
                    </tr>
                </tbody>
            </table>

            {{-- ─── Percentage / grade / rank / result ─── --}}
            <table class="sum">
                <tr>
                    <td class="first">
                        <div class="lbl">Percentage</div>
                        <div class="val">{{ number_format($card['pct'], 2) }}%</div>
                    </td>
                    <td>
                        <div class="lbl">Grade</div>
                        <div class="val">{{ $card['grade'] }}</div>
                    </td>
                    <td>
                        <div class="lbl">Rank in Class</div>
                        <div class="val">{{ $card['rank'] }} <span class="of">of {{ $card['of'] }}</span></div>
                    </td>
                    <td>
                        <div class="lbl">Result</div>
                        <div class="val {{ $card['result'] === 'Pass' ? 'pass' : ($card['result'] === 'Fail' ? 'fail' : '') }}">{{ strtoupper($card['result']) }}</div>
                    </td>
                </tr>
            </table>

            {{-- ─── Remark and the date ─── --}}
            <table class="rem">
                <tr>
                    <td class="k">Remarks:</td>
                    <td class="v">{{ $card['remark'] }}</td>
                    <td class="d">Date: {{ $head['issued'] }}</td>
                </tr>
            </table>
        </div>
    @endforeach
</body>
</html>
