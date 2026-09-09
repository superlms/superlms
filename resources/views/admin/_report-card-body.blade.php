{{--
    Shared report-card body. Included by:
      - resources/views/admin/report-card-pdf.blade.php   (DomPDF, A4 portrait)
      - resources/views/admin/report-card-print.blade.php (browser print)

    The layout is the school's own template: a navy-framed A4 sheet, centred
    masthead, bordered student-info table, a three-deep marks header split into
    Term-1 / Term-2 / Final Result / Total, two co-scholastic tables side by
    side, attendance and remark, then issue date, result and signatures. Only
    the wrapping <style> differs between the two mediums.

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
    // Each row carries a Term-1 sub-total, a Term-2 sub-total, a Final-Result
    // split (Practical / Theory) and a grand Total. The maths happens here so
    // the markup below stays flat and reads top-to-bottom.

    $t1Exams = $term1Exams ?? collect();
    $t2Exams = $term2Exams ?? collect();

    // Max marks for each term header row.
    $t1MaxTotal = (int) $t1Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $t2MaxTotal = (int) $t2Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $grandMaxTotal = $t1MaxTotal + $t2MaxTotal;

    $t1ColumnTotal = 0; $t1ColumnMax = 0;
    $t2ColumnTotal = 0; $t2ColumnMax = 0;
    $grandObtained = 0;
    $grandMax = 0;
    $passed = true;

    // Helper to look up a student's exam copy for a given (exam, subject).
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

    // Column counts used by the colspan-heavy header rows.
    $t1Cols = $t1Exams->count() + 1; // sub-exams + Total
    $t2Cols = $t2Exams->count() + 1;
    $tableCols = 1 /*subject*/ + $t1Cols + $t2Cols + 2 /*practical+theory*/ + 1 /*total*/;

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

    $contactBits = array_filter([
        $organization->mobile_number ?? null,
        $organization->email ?? null,
    ]);

    // Website prints the way a school writes it on its letterhead: no
    // protocol, no trailing slash, always a www.
    $website = trim((string) ($organization->website ?? ''));
    if ($website !== '') {
        $website = rtrim(preg_replace('#^https?://#i', '', $website), '/');
        if (!\Illuminate\Support\Str::startsWith(strtolower($website), 'www.')) {
            $website = 'www.' . $website;
        }
    }

    $a1 = $attendance['term1']   ?? ['present' => 0, 'total' => 0];
    $a2 = $attendance['term2']   ?? ['present' => 0, 'total' => 0];
    $ao = $attendance['overall'] ?? ['present' => 0, 'total' => 0];
@endphp

<div class="page">

    {{-- ─── Top corners: affiliation number left, website right ─── --}}
    <table class="topbar">
        <tr>
            <td>@if (!empty($organization->affiliation_no))Affiliation No: {{ $organization->affiliation_no }}@endif</td>
            <td class="right">{{ $website }}</td>
        </tr>
    </table>

    {{-- ─── Masthead ─── --}}
    <div class="header">
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="School Logo">
        @endif
        <div class="school-name">{{ $organization->name ?? 'School Name' }}</div>
        <div class="school-address">{{ $organization->address ?? '' }}</div>
        @if (!empty($contactBits))
            <div class="school-address">{{ implode(', ', $contactBits) }}</div>
        @endif
        <div class="rule"></div>
        <div class="doc-title">Record of Academic Performance</div>
        <div class="doc-session">Session: {{ $reportCard->academic_year ?? 'N/A' }}</div>
    </div>

    {{-- ─── Student info ─── --}}
    <table class="info">
        <tr>
            <td class="label">Student Name:</td>
            <td class="value">{{ $student->full_name ?? 'N/A' }}</td>
            <td class="label">&nbsp;</td>
            <td class="value">&nbsp;</td>
        </tr>
        <tr>
            <td class="label">Mother's Name:</td>
            <td class="value">{{ $student->mother_name ?: '—' }}</td>
            <td class="label">Father's Name:</td>
            <td class="value">{{ $student->father_name ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Admission No:</td>
            <td class="value">{{ $student->admission_no ?: '—' }}</td>
            <td class="label">Class/Section:</td>
            <td class="value">{{ $student->standard->name ?? '' }}@if (!empty($student->section->name)) / Section-{{ $student->section->name }}@endif</td>
        </tr>
        <tr>
            <td class="label">Date of Birth:</td>
            <td class="value">{{ $student->dob ? $student->dob->format('d/m/Y') : '—' }}</td>
            <td class="label">Regd. No:</td>
            <td class="value">{{ $regdNo ?: '—' }}</td>
        </tr>
    </table>

    {{-- ─── Scholastic marks ─────────────────────────────────
         Row 1: Scholastic Area | Term-1 | Term-2 | Final Result | Total
         Row 2: Subject Name    | <exam names…> Total | <exam names…> Total | Marks Obtained
         Row 3: (blank)         | <max marks…> total  | <max marks…> total  | Practical | Theory
    ──────────────────────────────────────────────────────── --}}
    <table class="marks">
        <thead>
            <tr>
                <th class="subj-head" rowspan="3">Scholastic Area<br><span>Subject Name</span></th>
                <th colspan="{{ $t1Cols }}">Term-1</th>
                <th colspan="{{ $t2Cols }}">Term-2</th>
                <th colspan="2" rowspan="2">Final Result<br>Marks Obtained</th>
                <th rowspan="3">Total</th>
            </tr>
            <tr>
                @foreach ($t1Exams as $exam)
                    <th>{{ $exam->exam_name }}</th>
                @endforeach
                <th>Total</th>
                @foreach ($t2Exams as $exam)
                    <th>{{ $exam->exam_name }}</th>
                @endforeach
                <th>Total</th>
            </tr>
            <tr>
                @foreach ($t1Exams as $exam)
                    <th>{{ $exam->total_marks ?? '—' }}</th>
                @endforeach
                <th>{{ $t1MaxTotal }}</th>
                @foreach ($t2Exams as $exam)
                    <th>{{ $exam->total_marks ?? '—' }}</th>
                @endforeach
                <th>{{ $t2MaxTotal }}</th>
                <th>Practical</th>
                <th>Theory</th>
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

            <tr class="totrow">
                <td class="subj">Total</td>
                @if ($t1Exams->count())<td colspan="{{ $t1Exams->count() }}"></td>@endif
                <td>{{ $num($t1ColumnTotal) }}</td>
                @if ($t2Exams->count())<td colspan="{{ $t2Exams->count() }}"></td>@endif
                <td>{{ $num($t2ColumnTotal) }}</td>
                <td colspan="2"></td>
                <td>{{ $num($grandObtained) }}</td>
            </tr>

            <tr class="pctrow">
                <td class="subj">Percentage</td>
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
                    <tr><th colspan="2">Co-Scholastic Areas: Term 1 (A-E)</th></tr>
                    @foreach ($coScholastic['term1'] ?? [] as $row)
                        <tr>
                            <td>{{ $row['subject'] }}</td>
                            <td class="grade">{{ $row['grade'] }}</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td class="right">
                <table class="co">
                    <tr><th colspan="2">Co-Scholastic Areas: Term 2 (A-E)</th></tr>
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
            <td class="label" style="width:14%;">Attendance</td>
            <td style="width:24%;">Term 1: {{ $a1['present'] }}/{{ $a1['total'] }}</td>
            <td style="width:24%;">Term 2: {{ $a2['present'] }}/{{ $a2['total'] }}</td>
            <td>Overall Attendance: {{ $ao['present'] }}/{{ $ao['total'] }}</td>
        </tr>
        <tr>
            <td class="label">Remark</td>
            <td colspan="3">{{ $remark }}</td>
        </tr>
    </table>

    {{-- ─── Issue date / result ─── --}}
    <table class="issue-row">
        <tr>
            <td>Issue Date: {{ $reportCard->issued_at ? $reportCard->issued_at->format('d/m/Y') : now()->format('d/m/Y') }}</td>
            <td class="result">RESULT: {{ $result }}</td>
        </tr>
    </table>

    {{-- ─── Signatures + footnote ───────────────────────────────────────
         Both sit at the foot of the sheet however tall the marks table is.
         The browser gets there with `margin-top:auto` in a flex column;
         dompdf, which has no flex, pins this block with position:fixed.
    ──────────────────────────────────────────────────────────────────── --}}
    <div class="page-foot">
        <table class="sign-row">
            <tr>
                <td><span>Class Teacher</span></td>
                <td class="right"><span>Principal</span></td>
            </tr>
        </table>

        <div class="footer-note">This is a computer-generated report card and does not require a physical signature unless specified.</div>
    </div>
</div>
