<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ReportCard;
use App\Models\Student\SectionSubject;
use App\Models\Student\StudentAttendance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportCardController extends Controller
{
    /**
     * Download report card as PDF.
     */
    public function download(Request $request, $organization, $id)
    {
        $reportCard = $this->getReportCard($id);
        $data = $this->buildReportCardData($reportCard);

        $pdf = Pdf::loadView('admin.report-card-pdf', $data)
            ->setPaper('a4', 'portrait');

        $studentName = str_replace(' ', '_', $reportCard->studentDetail->full_name ?? 'student');

        return $pdf->download("report_card_{$studentName}.pdf");
    }

    /**
     * Print report card (view in browser).
     */
    public function print(Request $request, $organization, $id)
    {
        $reportCard = $this->getReportCard($id);
        $data = $this->buildReportCardData($reportCard);

        return view('admin.report-card-print', $data);
    }

    /**
     * Get report card with authorization check.
     */
    private function getReportCard($id)
    {
        return ReportCard::with([
            'studentDetail',
            'studentDetail.standard',
            'studentDetail.section',
            'standard',
            'section',
            'organization',
            'issuedBy',
        ])
            ->where('organization_id', Auth::user()->organization_id)
            ->where('status', 'issued')
            ->findOrFail($id);
    }

    /**
     * Build data array for report card views.
     */
    private function buildReportCardData(ReportCard $reportCard)
    {
        $orgId = $reportCard->organization_id;
        $studentId = $reportCard->student_detail_id;
        $standardId = $reportCard->standard_id;
        $sectionId = $reportCard->section_id;

        // Get all published exams
        $exams = Exam::where('organization_id', $orgId)
            ->where('is_published', true)
            ->orderBy('start_date')
            ->get();

        // ─── Split exams into Term 1 and Term 2 ──────────────────────────
        // The Shreeji-style template groups marks under Term-1 and Term-2
        // column groups, each with its own Total. We split by exam_name
        // first (preferred — schools usually name exams "Term 1 …" / "Term
        // 2 …" / "Mid Term" / "End Term"), then fall back to a midpoint
        // chronological split so the template still renders cleanly when
        // exams aren't explicitly named.
        $term1Exams = collect();
        $term2Exams = collect();
        foreach ($exams as $exam) {
            // Prefer the explicit term chosen when the exam was created. Only
            // fall back to name-based guessing when the term wasn't set.
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
                // Unclassifiable — defer to midpoint split below.
                $term1Exams->push($exam);
            }
        }
        // If the name-based split produced an empty Term 2 (common when
        // every exam is named "Unit Test N"), fall back to a chronological
        // midpoint split so the template still has two balanced groups.
        if ($term2Exams->isEmpty() && $term1Exams->count() > 1) {
            $mid = (int) ceil($term1Exams->count() / 2);
            $term2Exams = $term1Exams->slice($mid)->values();
            $term1Exams = $term1Exams->slice(0, $mid)->values();
        }

        // Get section subjects
        $subjectIds = SectionSubject::where('section_id', $sectionId)
            ->where('standard_id', $standardId)
            ->where('organization_id', $orgId)
            ->pluck('subject_id');

        $subjects = \App\Models\Student\Subject::whereIn('id', $subjectIds)->orderBy('name')->get();

        // Get all exam copies for this student
        $examCopies = ExamCopy::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->whereIn('exam_id', $exams->pluck('id'))
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->groupBy('exam_id');

        // Attendance summary (status 1 = present) — split into Term 1 / Term 2
        // by the midpoint date of all attendance records for this student.
        // student_attendances column is `attendance_date`, not `date`.
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

        // Co-Scholastic Areas — fixed default subjects, default grade "A" for everyone.
        // Per spec: same set shown for Term 1 and Term 2, both default to A.
        $coScholasticSubjects = ['General Studies', 'Health & Physical Education', 'Work Behaviour'];
        $coScholastic = [
            'term1' => array_map(fn($s) => ['subject' => $s, 'grade' => 'A'], $coScholasticSubjects),
            'term2' => array_map(fn($s) => ['subject' => $s, 'grade' => 'A'], $coScholasticSubjects),
        ];

        return [
            'reportCard'   => $reportCard,
            'student'      => $reportCard->studentDetail,
            'organization' => $reportCard->organization ?? Auth::user()->organization,
            'exams'        => $exams,
            'term1Exams'   => $term1Exams,
            'term2Exams'   => $term2Exams,
            'subjects'     => $subjects,
            'examCopies'   => $examCopies,
            'attendance'   => [
                'term1'   => ['present' => $term1Present, 'total' => $term1Total],
                'term2'   => ['present' => $term2Present, 'total' => $term2Total],
                'overall' => ['present' => $overallPresent, 'total' => $overallTotal],
                // legacy keys (kept for backwards compatibility with old templates)
                'present' => $overallPresent,
                'total'   => $overallTotal,
            ],
            'coScholastic' => $coScholastic,
        ];
    }
}
