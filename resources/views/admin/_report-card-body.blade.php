{{--
    Shared report-card body. Included by:
      - resources/views/admin/report-card-pdf.blade.php   (DomPDF, A4 portrait)
      - resources/views/admin/report-card-print.blade.php (browser print)

    Identical layout in both; the wrapping <style> only differs to suit each
    medium (DomPDF doesn't understand all browser CSS, browsers want a screen
    fallback). The markup below is the canonical design — modelled after
    the Shreeji Public School template the school provided.

    Variables in scope:
      $organization, $student, $reportCard,
      $exams, $term1Exams, $term2Exams, $subjects, $examCopies,
      $attendance['term1'|'term2'|'overall' => ['present', 'total']],
      $coScholastic['term1'|'term2' => [['subject', 'grade'], ...]]
--}}

@php
    // ── Pre-compute per-subject and per-exam aggregates ────────────────
    // The marks table mirrors the Shreeji template exactly: each row carries
    // a Term-1 sub-total, a Term-2 sub-total, a Final-Result split (Practical
    // / Theory) and a grand Total. We do the math once here so the markup
    // below stays flat and reads top-to-bottom.

    $t1Exams = $term1Exams ?? collect();
    $t2Exams = $term2Exams ?? collect();

    // Max marks for each term header row.
    $t1MaxTotal = (int) $t1Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $t2MaxTotal = (int) $t2Exams->sum(fn($e) => (int) ($e->total_marks ?? 0));
    $grandMaxTotal = $t1MaxTotal + $t2MaxTotal;

    // Per-exam totals (used in the bottom Total / Percentage summary rows).
    $examTotals = [];
    foreach ($exams as $exam) {
        $examTotals[$exam->id] = ['obt' => 0, 'max' => 0];
    }

    $t1ColumnTotal = 0; $t1ColumnMax = 0;
    $t2ColumnTotal = 0; $t2ColumnMax = 0;
    $practicalColumnTotal = 0; $theoryColumnTotal = 0;
    $grandObtained = 0;
    $grandMax = 0;
    $passed = true;

    // Helper to look up a student's exam copy for a given (exam, subject).
    $copyFor = function ($examId, $subjectId) use ($examCopies) {
        if (!isset($examCopies[$examId])) return null;
        return $examCopies[$examId]->firstWhere('subject_id', $subjectId);
    };

    // Helper to detect a practical-type exam. Falls back to "theory" when
    // exam_type isn't set — most schools track only theory marks.
    $isPractical = function ($exam) {
        $t = strtolower((string) ($exam->exam_type ?? ''));
        return str_contains($t, 'practical');
    };

    // Pre-compute per-row data so the markup below is a simple foreach.
    $subjectRows = [];
    foreach ($subjects as $subject) {
        $t1Obt = 0; $t1Max = 0;
        $t2Obt = 0; $t2Max = 0;
        $practicalObt = 0; $theoryObt = 0;
        $rowCells = ['t1' => [], 't2' => []];

        foreach ($t1Exams as $exam) {
            $copy = $copyFor($exam->id, $subject->id);
            $cellVal = '-'; $obt = 0; $max = 0;
            if ($copy) {
                $max = (float) ($copy->max_marks ?? $exam->total_marks ?? 0);
                if (!empty($copy->is_absent)) {
                    $cellVal = 'AB';
                } else {
                    $obt = (float) ($copy->marks_obtained ?? 0);
                    $cellVal = rtrim(rtrim(number_format($obt, 2, '.', ''), '0'), '.');
                    $t1Obt += $obt; $t1Max += $max;
                    $examTotals[$exam->id]['obt'] += $obt;
                    $examTotals[$exam->id]['max'] += $max;
                    if ($isPractical($exam)) $practicalObt += $obt; else $theoryObt += $obt;
                }
            } else {
                $max = (float) ($exam->total_marks ?? 0);
            }
            $rowCells['t1'][] = $cellVal;
        }

        foreach ($t2Exams as $exam) {
            $copy = $copyFor($exam->id, $subject->id);
            $cellVal = '-'; $obt = 0; $max = 0;
            if ($copy) {
                $max = (float) ($copy->max_marks ?? $exam->total_marks ?? 0);
                if (!empty($copy->is_absent)) {
                    $cellVal = 'AB';
                } else {
                    $obt = (float) ($copy->marks_obtained ?? 0);
                    $cellVal = rtrim(rtrim(number_format($obt, 2, '.', ''), '0'), '.');
                    $t2Obt += $obt; $t2Max += $max;
                    $examTotals[$exam->id]['obt'] += $obt;
                    $examTotals[$exam->id]['max'] += $max;
                    if ($isPractical($exam)) $practicalObt += $obt; else $theoryObt += $obt;
                }
            } else {
                $max = (float) ($exam->total_marks ?? 0);
            }
            $rowCells['t2'][] = $cellVal;
        }

        $rowTotal = $t1Obt + $t2Obt;
        $rowMax   = $t1Max + $t2Max;
        $rowPct   = $rowMax > 0 ? round(($rowTotal / $rowMax) * 100, 2) : 0;
        if ($rowMax > 0 && $rowPct < 33) { $passed = false; }
        $grandObtained += $rowTotal;
        $grandMax      += $rowMax;
        $t1ColumnTotal += $t1Obt; $t1ColumnMax += $t1Max;
        $t2ColumnTotal += $t2Obt; $t2ColumnMax += $t2Max;
        $practicalColumnTotal += $practicalObt;
        $theoryColumnTotal    += $theoryObt;

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

    // Column counts used by the colspan-heavy header row.
    $t1Cols = $t1Exams->count() + 1; // sub-exams + Total
    $t2Cols = $t2Exams->count() + 1;
    $tableCols = 1 /*subject*/ + $t1Cols + $t2Cols + 2 /*practical+theory*/ + 1 /*total*/;
@endphp

<div class="sheet">

    {{-- ─── Top bar (Affiliation No left, website right) ─────────────────── --}}
    <table class="topbar">
        <tr>
            <td>Affiliation No: {{ $organization->affiliation_no ?? '—' }}</td>
            <td class="right">
                @if (!empty($organization->website))
                    website:{{ $organization->website }}
                @elseif (!empty($organization->email))
                    {{ $organization->email }}
                @endif
            </td>
        </tr>
    </table>

    {{-- ─── Brand / school header ──────────────────────────── --}}
    @php
        $logoSrc = null;
        if (!empty($organization?->logo)) {
            if (\Illuminate\Support\Str::startsWith($organization->logo, ['http://', 'https://'])) {
                $logoSrc = $organization->logo;
            } elseif (file_exists(public_path('storage/' . $organization->logo))) {
                $logoSrc = public_path('storage/' . $organization->logo);
            }
        }
    @endphp
    <div class="brand">
        @if ($logoSrc)
            <img src="{{ $logoSrc }}" alt="Logo">
        @endif
        <div class="school-name">{{ $organization->name ?? 'School Name' }}</div>
        <div class="address">{{ $organization->address ?? '' }}</div>
        @php
            $contactBits = [];
            if (!empty($organization->mobile_number)) $contactBits[] = $organization->mobile_number;
            if (!empty($organization->website))       $contactBits[] = $organization->website;
        @endphp
        @if (!empty($contactBits))
            <div class="contact">{{ implode(' / ', $contactBits) }}</div>
        @endif
        <div class="doc-title">Record of Academic Performance</div>
        <div class="session">Session: {{ $reportCard->academic_year ?? 'N/A' }}</div>
    </div>

    {{-- ─── Student info ───────────────────────────────────── --}}
    <table class="info">
        <tr>
            <td class="label">Student Name:</td>
            <td class="value" colspan="3">{{ $student->full_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Mother's Name:</td>
            <td class="value">{{ $student->mother_name ?? 'N/A' }}</td>
            <td class="label">Father's Name:</td>
            <td class="value">{{ $student->father_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Admission No:</td>
            <td class="value">{{ $student->admission_no ?? 'N/A' }}</td>
            <td class="label">Class/Section:</td>
            <td class="value">
                {{ $student->standard->name ?? '' }}{{ !empty($student->section->name) ? ' / Section-' . $student->section->name : '' }}
            </td>
        </tr>
        <tr>
            <td class="label">Date of Birth:</td>
            <td class="value">{{ $student->dob ? $student->dob->format('d/m/Y') : 'N/A' }}</td>
            <td class="label">Regd. No:</td>
            <td class="value">{{ $student->registration_number ?? 'N/A' }}</td>
        </tr>
    </table>

    {{-- ─── Scholastic marks ─────────────────────────────────
         3-row header:
           Row 1: Scholastic Area | Term-1 | Term-2 | Final Result | Total
           Row 2: Subject Name    | <exam names...> Total | <exam names...> Total | Marks Obtained | 250
           Row 3: (blank)         | <max marks...> $t1MaxTotal | <max marks...> $t2MaxTotal | Practical | Theory | (blank)
    ──────────────────────────────────────────────────────── --}}
    <table class="marks">
        <thead>
            {{-- Row 1: Scholastic Area | Term-1 | Term-2 | Final Result | Total --}}
            <tr>
                <th class="subj grp">Scholastic Area</th>
                <th class="grp" colspan="{{ $t1Cols }}">Term- 1</th>
                <th class="grp" colspan="{{ $t2Cols }}">Term- 2</th>
                <th class="grp" colspan="2">Final Result</th>
                <th class="grp">Total</th>
            </tr>
            {{-- Row 2: Subject Name | <exam names + Total> | <exam names + Total> | Marks Obtained | 250 --}}
            <tr>
                <th class="subj">Subject Name</th>
                @foreach ($t1Exams as $exam)
                    <th>{{ $exam->exam_name }}</th>
                @endforeach
                <th>Total</th>
                @foreach ($t2Exams as $exam)
                    <th>{{ $exam->exam_name }}</th>
                @endforeach
                <th>Total</th>
                <th colspan="2">Marks Obtained</th>
                <th rowspan="2" class="bigtotal">{{ $grandMaxTotal }}</th>
            </tr>
            {{-- Row 3: blank | max marks + term-1 total | max marks + term-2 total | Practical | Theory --}}
            <tr>
                <th></th>
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
                    <td><strong>{{ rtrim(rtrim(number_format($row['t1_total'], 2, '.', ''), '0'), '.') ?: '0' }}</strong></td>
                    @foreach ($row['cells_t2'] as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                    <td><strong>{{ rtrim(rtrim(number_format($row['t2_total'], 2, '.', ''), '0'), '.') ?: '0' }}</strong></td>
                    <td>{{ $row['practical'] > 0 ? rtrim(rtrim(number_format($row['practical'], 2, '.', ''), '0'), '.') : '-' }}</td>
                    <td>{{ $row['theory'] > 0 ? rtrim(rtrim(number_format($row['theory'], 2, '.', ''), '0'), '.') : '-' }}</td>
                    <td><strong>{{ rtrim(rtrim(number_format($row['row_total'], 2, '.', ''), '0'), '.') ?: '0' }}</strong></td>
                </tr>
            @empty
                <tr><td class="subj" colspan="{{ $tableCols }}">No subjects found for this section.</td></tr>
            @endforelse

            {{-- Total row --}}
            <tr class="totrow">
                <td class="subj">Total</td>
                @foreach ($t1Exams as $exam)
                    <td></td>
                @endforeach
                <td>{{ rtrim(rtrim(number_format($t1ColumnTotal, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                @foreach ($t2Exams as $exam)
                    <td></td>
                @endforeach
                <td>{{ rtrim(rtrim(number_format($t2ColumnTotal, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                <td colspan="2"></td>
                <td>{{ rtrim(rtrim(number_format($grandObtained, 2, '.', ''), '0'), '.') ?: '0' }}</td>
            </tr>

            {{-- Percentage row --}}
            <tr class="pctrow">
                <td class="subj">Percentage</td>
                @foreach ($t1Exams as $exam)
                    <td></td>
                @endforeach
                <td>{{ $t1OverallPct }}%</td>
                @foreach ($t2Exams as $exam)
                    <td></td>
                @endforeach
                <td>{{ $t2OverallPct }}%</td>
                <td colspan="2"></td>
                <td>{{ $overallPct }}%</td>
            </tr>
        </tbody>
    </table>

    {{-- ─── Co-Scholastic Areas (Term 1 + Term 2 side by side) ─── --}}
    <table class="cosch">
        <tr>
            <td class="cell-left">
                <table class="cosch-inner">
                    <thead>
                        <tr>
                            <th>Co-Scholastic Areas: Term 1 (A-E)</th>
                            <th class="gd">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coScholastic['term1'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                <td class="gd">{{ $row['grade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
            <td class="cell-right">
                <table class="cosch-inner">
                    <thead>
                        <tr>
                            <th>Co-Scholastic Areas: Term 2 (A-E)</th>
                            <th class="gd">Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($coScholastic['term2'] ?? [] as $row)
                            <tr>
                                <td>{{ $row['subject'] }}</td>
                                <td class="gd">{{ $row['grade'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- ─── Attendance + Remark ─────────────────────────────── --}}
    @php
        $a1 = $attendance['term1']   ?? ['present' => 0, 'total' => 0];
        $a2 = $attendance['term2']   ?? ['present' => 0, 'total' => 0];
        $ao = $attendance['overall'] ?? ['present' => 0, 'total' => 0];
    @endphp
    <table class="foot">
        <tr>
            <td class="label">Attendance</td>
            <td>Term 1: {{ $a1['present'] }}/{{ $a1['total'] }}</td>
            <td>Term 2: {{ $a2['present'] }}/{{ $a2['total'] }}</td>
            <td>Overall Attendance: {{ $ao['present'] }}/{{ $ao['total'] }}</td>
        </tr>
        <tr>
            <td class="label">Remark</td>
            <td colspan="3">
                {{ $overallPct >= 75 ? 'Excellent performance' : ($overallPct >= 50 ? 'Good, keep improving' : ($overallPct >= 33 ? 'Need improvement' : 'Requires serious attention')) }}
            </td>
        </tr>
    </table>

    {{-- ─── Result row ──────────────────────────────────────── --}}
    <table class="result">
        <tr>
            <td>Issue Date: {{ $reportCard->issued_at ? $reportCard->issued_at->format('d/m/Y') : now()->format('d/m/Y') }}</td>
            <td class="r">RESULT: <strong>{{ $passed && $grandMax > 0 ? 'PASSED' : ($grandMax > 0 ? 'FAILED' : 'N/A') }}</strong></td>
        </tr>
    </table>

    {{-- ─── Signatures ──────────────────────────────────────── --}}
    <table class="sign">
        <tr>
            <td>Class Teacher</td>
            <td class="r">Principal</td>
        </tr>
    </table>

</div>
