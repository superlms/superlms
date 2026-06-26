<?php

namespace App\Services;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ReportCard;
use App\Models\Student\SectionSubject;
use App\Models\Student\StudentAttendance;
use App\Models\Student\Subject;

/**
 * Builds the data array consumed by the `admin.report-card-pdf` /
 * `admin.report-card-print` Blade templates. Shared by the admin panel and the
 * student v1 API so the in-app report card renders the *exact* same template
 * the admin generates.
 */
class ReportCardService
{
    /**
     * Assemble the full report-card view payload (Shreeji-style Term-1/Term-2
     * template) for an issued report card. Organisation is taken from the card
     * itself, so this works outside an admin (Auth) context.
     */
    public function buildPdfData(ReportCard $reportCard): array
    {
        $orgId      = $reportCard->organization_id;
        $studentId  = $reportCard->student_detail_id;
        $standardId = $reportCard->standard_id;
        $sectionId  = $reportCard->section_id;

        // All published exams for the school.
        $exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderBy('start_date')
            ->get();

        // ─── Split exams into Term 1 / Term 2 ────────────────────────────────
        $term1Exams = collect();
        $term2Exams = collect();
        foreach ($exams as $exam) {
            if (($exam->term ?? null) === 'Term-2') {
                $term2Exams->push($exam);
                continue;
            }
            if (($exam->term ?? null) === 'Term-1') {
                $term1Exams->push($exam);
                continue;
            }

            $name = strtolower((string) $exam->exam_name);
            if (str_contains($name, 'term 2')
                || str_contains($name, 'term-2')
                || str_contains($name, 'term2')
                || str_contains($name, 'end term')
                || str_contains($name, 'end-term')
                || str_contains($name, 'final')
                || str_contains($name, 'annual')
            ) {
                $term2Exams->push($exam);
            } elseif (str_contains($name, 'term 1')
                || str_contains($name, 'term-1')
                || str_contains($name, 'term1')
                || str_contains($name, 'mid term')
                || str_contains($name, 'mid-term')
                || str_contains($name, 'half yearly')
                || str_contains($name, 'half-yearly')
                || str_contains($name, 'unit test')
            ) {
                $term1Exams->push($exam);
            } else {
                $term1Exams->push($exam);
            }
        }
        if ($term2Exams->isEmpty() && $term1Exams->count() > 1) {
            $mid = (int) ceil($term1Exams->count() / 2);
            $term2Exams = $term1Exams->slice($mid)->values();
            $term1Exams = $term1Exams->slice(0, $mid)->values();
        }

        // Section subjects.
        $subjectIds = SectionSubject::where('section_id', $sectionId)
            ->where('standard_id', $standardId)
            ->where('organization_id', $orgId)
            ->pluck('subject_id');

        $subjects = Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

        // All exam copies for this student.
        $examCopies = ExamCopy::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy('exam_id');

        // Attendance summary (status 1 = present) split Term 1 / Term 2.
        $attendanceRows = StudentAttendance::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->orderBy('attendance_date')
            ->get(['attendance_date', 'status']);

        $overallTotal   = $attendanceRows->count();
        $overallPresent = $attendanceRows->where('status', 1)->count();

        $term1Total = $term1Present = $term2Total = $term2Present = 0;
        if ($overallTotal > 0) {
            $midIndex = intdiv($overallTotal, 2);
            $term1 = $attendanceRows->slice(0, $midIndex);
            $term2 = $attendanceRows->slice($midIndex);
            $term1Total   = $term1->count();
            $term1Present = $term1->where('status', 1)->count();
            $term2Total   = $term2->count();
            $term2Present = $term2->where('status', 1)->count();
        }

        $coScholasticSubjects = ['General Studies', 'Health & Physical Education', 'Work Behaviour'];
        $coScholastic = [
            'term1' => array_map(fn($s) => ['subject' => $s, 'grade' => 'A'], $coScholasticSubjects),
            'term2' => array_map(fn($s) => ['subject' => $s, 'grade' => 'A'], $coScholasticSubjects),
        ];

        return [
            'reportCard'   => $reportCard,
            'student'      => $reportCard->studentDetail,
            'organization' => $reportCard->organization,
            'exams'        => $exams,
            'term1Exams'   => $term1Exams,
            'term2Exams'   => $term2Exams,
            'subjects'     => $subjects,
            'examCopies'   => $examCopies,
            'attendance'   => [
                'term1'   => ['present' => $term1Present, 'total' => $term1Total],
                'term2'   => ['present' => $term2Present, 'total' => $term2Total],
                'overall' => ['present' => $overallPresent, 'total' => $overallTotal],
                'present' => $overallPresent,
                'total'   => $overallTotal,
            ],
            'coScholastic' => $coScholastic,
        ];
    }
}
