{{--
    Shared report-card body. Included by:
      - resources/views/admin/report-card-pdf.blade.php   (DomPDF, A4 portrait)
      - resources/views/admin/report-card-print.blade.php (browser print)

    The layout is a direct copy of the school's own report card. Every
    measurement below was read off that PDF rather than eyeballed:

      page          A4, 210 x 297mm
      frame         inset 5.9mm left/right, 9.8mm top, 6.4mm bottom;
                    2px solid #428eb8
      topbar        ABOVE the frame - affiliation number left, website right
      content       8.7mm inside the frame, so 14.6mm from the paper edge
      marks table   subject column 22.1%, every other column 7.08%
      info table    25.1% / 33.2% / 18.4% / 23.5%
      attendance    14.3% / 24.1% / 24.7% / 36.9%
      co-scholastic two tables of 49%, 1.9% apart, grade column 18.1%
      table borders 1px #aaaaaa - grey, not black
      signatures    bold, no rule above them, 14mm off the bottom

    Wording matches the school's card as printed, typos and all
    ("Affliation", "Performnace", "Attandance", "Principle").

    Variables in scope:
      $organization, $student, $reportCard,
      $exams, $term1Exams, $term2Exams, $subjects, $examCopies,
      $attendance['term1'|'term2'|'overall' => ['present', 'total']],
      $coScholastic['term1'|'term2' => [['subject', 'grade'], ...]]

    Regd. No, the remark and the result are typed on the issue form and stored
    on the card; each falls back to what it used to be derived from when a card
    predates that form.
--}}

@php
    // ── Pre-compute per-subject and per-exam aggregates ────────────────
    $t1Exams = $term1Exams ?? collect();
    $t2Exams = $term2Exams ?? collect();

    $t1MaxTotal = (int) $t1Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $t2MaxTotal = (int) $t2Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $grandMaxTotal = $t1MaxTotal + $t2MaxTotal;

    $t1ColumnTotal = 0; $t1ColumnMax = 0;
    $t2ColumnTotal = 0; $t2ColumnMax = 0;
    $grandObtained = 0;
    $grandMax = 0;
    $passed = true;

    $copyFor = function ($examId, $subjectId) use ($examCopies) {
        if (!isset($examCopies[$examId])) return null;
        return $examCopies[$examId]->firstWhere('subject_id', $subjectId);
    };

    // Practical-type exams feed the Practical column; everything else is
    // theory, which is what most schools actually track.
    $isPractical = function ($exam) {
        $t = strtolower((string) ($exam->exam_type ?? ''));
        return str_contains($t, 'practical');
    };

    $num = fn($v) => rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.') ?: '0';

    $subjectRows = [];
    foreach ($subjects as $subject) {
        $t1Obt = 0; $t1Max = 0;
        $t2Obt = 0; $t2Max = 0;
        $practicalObt = 0; $theoryObt = 0;
        $rowCells = ['t1' => [], 't2' => []];

        foreach (['t1' => $t1Exams, 't2' => $t2Exams] as $termKey => $termExams) {
            foreach ($termExams as $exam) {
                $copy = $copyFor($exam->id, $subject->id);
                $cellVal = '-';

                if ($copy) {
                    if (!empty($copy->is_absent)) {
                        $cellVal = 'AB';
                    } else {
                        $obt = (float) ($copy->marks_obtained ?? 0);
                        $max = (float) ($copy->max_marks ?? $exam->total_marks ?? 0);
                        $cellVal = $num($obt);

                        if ($termKey === 't1') { $t1Obt += $obt; $t1Max += $max; }
                        else                   { $t2Obt += $obt; $t2Max += $max; }

                        if ($isPractical($exam)) $practicalObt += $obt; else $theoryObt += $obt;
                    }
                }

                $rowCells[$termKey][] = $cellVal;
            }
        }

        $rowTotal = $t1Obt + $t2Obt;
        $rowMax   = $t1Max + $t2Max;
        $rowPct   = $rowMax > 0 ? round(($rowTotal / $rowMax) * 100, 2) : 0;
        if ($rowMax > 0 && $rowPct < 33) { $passed = false; }

        $grandObtained += $rowTotal;
        $grandMax      += $rowMax;
        $t1ColumnTotal += $t1Obt; $t1ColumnMax += $t1Max;
        $t2ColumnTotal += $t2Obt; $t2ColumnMax += $t2Max;

        $subjectRows[] = [
            'subject'   => $subject,
            'cells_t1'  => $rowCells['t1'],
            'cells_t2'  => $rowCells['t2'],
            't1_total'  => $t1Obt,
            't2_total'  => $t2Obt,
            'practical' => $practicalObt,
            'theory'    => $theoryObt,
            'row_total' => $rowTotal,
        ];
    }

    $t1OverallPct = $t1ColumnMax > 0 ? round(($t1ColumnTotal / $t1ColumnMax) * 100, 2) : 0;
    $t2OverallPct = $t2ColumnMax > 0 ? round(($t2ColumnTotal / $t2ColumnMax) * 100, 2) : 0;
    $overallPct   = $grandMax > 0 ? round(($grandObtained / $grandMax) * 100, 2) : 0;

    $t1Cols = $t1Exams->count() + 1; // sub-exams + Total
    $t2Cols = $t2Exams->count() + 1;
    $tableCols = 1 + $t1Cols + $t2Cols + 2 + 1;

    // ── Fields typed on the issue form, with their old derivations as a
    //    fallback for cards issued before that form existed ──
    $regdNo = $reportCard->regd_no ?: ($student->registration_number ?? '');
    $remark = $reportCard->remark ?: (
        $overallPct >= 75 ? 'Excellent performance'
            : ($overallPct >= 50 ? 'Good, keep improving'
            : ($overallPct >= 33 ? 'Need improvement' : 'Requires serious attention'))
    );
    $result = $reportCard->result ?: ($grandMax > 0 ? ($passed ? 'PASSED' : 'FAILED') : 'N/A');

    // Logo: a URL goes straight in, a stored path is resolved on disk.
    $logoSrc = null;
    if (!empty($organization?->logo)) {
        if (\Illuminate\Support\Str::startsWith($organization->logo, ['http://', 'https://'])) {
            $logoSrc = $organization->logo;
        } elseif (file_exists(public_path('storage/' . $organization->logo))) {
            $logoSrc = public_path('storage/' . $organization->logo);
        }
    }

    // The card prints the full address on one line and "phone / website" on
    // the next, the way the school's own does.
    $website = trim((string) ($organization->website ?? ''));
    if ($website !== '') {
        $bare = rtrim(preg_replace('#^https?://#i', '', $website), '/');
        if (!\Illuminate\Support\Str::startsWith(strtolower($bare), 'www.')) {
            $bare = 'www.' . $bare;
        }
        $website = 'https://' . $bare;
    }

    $contactLine = implode(' / ', array_filter([
        $organization->mobile_number ?? null,
        $website ?: null,
    ]));

    $classSection = trim('Class- ' . ($student->standard->name ?? ''))
        . (!empty($student->section->name) ? '/  Section-' . $student->section->name : '');

    $a1 = $attendance['term1']   ?? ['present' => 0, 'total' => 0];
    $a2 = $attendance['term2']   ?? ['present' => 0, 'total' => 0];
    $ao = $attendance['overall'] ?? ['present' => 0, 'total' => 0];
@endphp

<div class="sheet">

    {{-- ─── Above the frame: affiliation number left, website right ─── --}}
    <table class="topbar">
        <tr>
            <td>@if (!empty($organization->affiliation_no))Affliation No: {{ $organization->affiliation_no }}@endif</td>
            <td class="right">@if ($website)website:{{ $website }}@endif</td>
        </tr>
    </table>

    {{-- The blue rule around the whole sheet, drawn as its own box so it
         frames the page rather than hugging the content. --}}
    <div class="frame"></div>

    <div class="page">

        {{-- ─── Masthead ─── --}}
        <div class="header">
            @if ($logoSrc)
                <img class="logo" src="{{ $logoSrc }}" alt="School Logo">
            @endif
            <div class="school-name">{{ $organization->name ?? 'School Name' }}</div>
            <div class="school-address">{{ $organization->address ?? '' }}</div>
            @if ($contactLine !== '')
                <div class="school-address">{{ $contactLine }}</div>
            @endif
            <div class="doc-title">Record of Academic Performnace</div>
            <div class="doc-session">Session:{{ $reportCard->academic_year ?? 'N/A' }}</div>
        </div>

        {{-- ─── Student info ─── --}}
        <table class="info">
            <tr>
                <td class="label">Student Name:</td>
                <td class="value" colspan="3">{{ $student->full_name ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">Mother's Name:</td>
                <td class="value">{{ $student->mother_name }}</td>
                <td class="label2">Father's Name:</td>
                <td class="value2">{{ $student->father_name }}</td>
            </tr>
            <tr>
                <td class="label">Admission No:</td>
                <td class="value">{{ $student->admission_no }}</td>
                <td class="label2">Class/Section:</td>
                <td class="value2">{{ $classSection }}</td>
            </tr>
            <tr>
                <td class="label">Date of Birth:</td>
                <td class="value">{{ $student->dob ? $student->dob->format('d/m/Y') : '' }}</td>
                <td class="label2">Regd. No:</td>
                <td class="value2">{{ $regdNo }}</td>
            </tr>
        </table>

        {{-- ─── Scholastic marks ───────────────────────────────────
             Row 1: Scholastic Area | Term- 1 | Term- 2 | Final Result | Total
             Row 2: Subject Name    | exam names… Total | … | Marks Obtained | 250
             Row 3:                 | max marks… total  | … | Practical | Theory
        ──────────────────────────────────────────────────────── --}}
        <table class="marks">
            <thead>
                <tr>
                    <th class="subj">Scholastic Area</th>
                    <th colspan="{{ $t1Cols }}">Term- 1</th>
                    <th colspan="{{ $t2Cols }}">Term- 2</th>
                    <th colspan="2">Final Result</th>
                    <th>Total</th>
                </tr>
                <tr>
                    <th class="subj" rowspan="2">Subject Name</th>
                    @foreach ($t1Exams as $exam)
                        <th class="tiny">{{ $exam->exam_name }}</th>
                    @endforeach
                    <th class="mid">Total</th>
                    @foreach ($t2Exams as $exam)
                        <th class="tiny">{{ $exam->exam_name }}</th>
                    @endforeach
                    <th class="mid">Total</th>
                    <th class="mid" colspan="2">Marks Obtained</th>
                    <th class="big" rowspan="2">{{ $grandMaxTotal }}</th>
                </tr>
                <tr>
                    @foreach ($t1Exams as $exam)
                        <th class="tiny">{{ $exam->total_marks }}</th>
                    @endforeach
                    <th class="mid">{{ $t1MaxTotal }}</th>
                    @foreach ($t2Exams as $exam)
                        <th class="tiny">{{ $exam->total_marks }}</th>
                    @endforeach
                    <th class="mid">{{ $t2MaxTotal }}</th>
                    <th class="mid">Practical</th>
                    <th class="mid">Theory</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($subjectRows as $row)
                    <tr>
                        <td class="subj">{{ $row['subject']->name }}</td>
                        @foreach ($row['cells_t1'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td>{{ $num($row['t1_total']) }}</td>
                        @foreach ($row['cells_t2'] as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        <td>{{ $num($row['t2_total']) }}</td>
                        <td>{{ $row['practical'] > 0 ? $num($row['practical']) : '-' }}</td>
                        <td>{{ $row['theory'] > 0 ? $num($row['theory']) : '-' }}</td>
                        <td>{{ $num($row['row_total']) }}</td>
                    </tr>
                @empty
                    <tr><td class="subj" colspan="{{ $tableCols }}">No subjects found for this section.</td></tr>
                @endforelse

                <tr>
                    <td class="subj foot-label">Total</td>
                    @if ($t1Exams->count())<td colspan="{{ $t1Exams->count() }}"></td>@endif
                    <td>{{ $num($t1ColumnTotal) }}</td>
                    @if ($t2Exams->count())<td colspan="{{ $t2Exams->count() }}"></td>@endif
                    <td>{{ $num($t2ColumnTotal) }}</td>
                    <td colspan="2"></td>
                    <td>{{ $num($grandObtained) }}</td>
                </tr>

                <tr>
                    <td class="subj foot-label">Percentage</td>
                    @if ($t1Exams->count())<td colspan="{{ $t1Exams->count() }}"></td>@endif
                    <td>{{ $t1OverallPct }}%</td>
                    @if ($t2Exams->count())<td colspan="{{ $t2Exams->count() }}"></td>@endif
                    <td>{{ $t2OverallPct }}%</td>
                    <td colspan="2"></td>
                    <td>{{ $overallPct }}%</td>
                </tr>
            </tbody>
        </table>

        {{-- ─── Co-scholastic areas, Term 1 beside Term 2 ─── --}}
        <table class="co-wrap">
            <tr>
                <td class="left">
                    <table class="co">
                        <tr>
                            <th>Co-Scholastic Areas: Term 1 (A-E)</th>
                            <th class="grade-head">Grade</th>
                        </tr>
                        @foreach ($coScholastic['term1'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                <td class="grade">{{ $row['grade'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
                <td class="gap"></td>
                <td class="right">
                    <table class="co">
                        <tr>
                            <th>Co-Scholastic Areas: Term 2 (A-E)</th>
                            <th class="grade-head">Grade</th>
                        </tr>
                        @foreach ($coScholastic['term2'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                <td class="grade">{{ $row['grade'] }}</td>
                            </tr>
                        @endforeach
                    </table>
                </td>
            </tr>
        </table>

        {{-- ─── Attendance + remark ─── --}}
        <table class="bottom-info">
            <tr>
                <td class="label">Attandance</td>
                <td class="c2">Term 1: {{ $a1['present'] }}/{{ $a1['total'] }}</td>
                <td class="c3">Term 2: {{ $a2['present'] }}/{{ $a2['total'] }}</td>
                <td class="c4">Overall Attandance: {{ $ao['present'] }}/{{ $ao['total'] }}</td>
            </tr>
            <tr>
                <td class="label">Remark</td>
                <td colspan="3">{{ $remark }}</td>
            </tr>
        </table>

        {{-- ─── Issue date / result (no borders) ─── --}}
        <table class="issue-row">
            <tr>
                <td>Issue Date: {{ $reportCard->issued_at ? $reportCard->issued_at->format('d/m/Y') : now()->format('d/m/Y') }}</td>
                <td class="result">RESULT: <span>{{ $result }}</span></td>
            </tr>
        </table>

    </div>{{-- /page --}}

    {{-- ─── Signatures: bold, no rule above them, near the bottom ─── --}}
    <div class="page-foot">
        <table class="sign-row">
            <tr>
                <td>Class Teacher</td>
                <td class="right">Principle</td>
            </tr>
        </table>
    </div>
</div>
