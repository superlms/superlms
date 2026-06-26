<?php

namespace App\Http\Controllers\v1\Student;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\ExamCopy;
use App\Models\Student\StudentDetail;
use Illuminate\Http\Request;

/**
 * Student → View own marks + overall performance.
 *
 * Read-only. Always scoped to the authenticated student — no way to peek at
 * another student's data.
 *
 * Endpoints (all under /api/v1/student/marks):
 *
 *   GET /                       → list of marks rows (exam, subject, marks)
 *   GET /overall-performance    → aggregate stats (overall %, subject-wise,
 *                                 trend, grade distribution)
 */
class MarksController extends ApiController
{
    /**
     * GET /api/v1/student/marks
     *
     * Optional filters: exam_id, subject_id, per_page
     *
     * Each row carries exactly what a "marks list" card needs:
     *   exam_name, subject_name, marks_obtained, max_marks, percentage, grade
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $student = StudentDetail::where('user_id', $user->id)->first(['id', 'organization_id']);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $query = ExamCopy::with([
            'exam:id,exam_name',
            'subject:id,name',
        ])
            ->where('organization_id', $student->organization_id)
            ->where('student_detail_id', $student->id);

        if ($request->filled('exam_id'))    $query->where('exam_id',    (int) $request->exam_id);
        if ($request->filled('subject_id')) $query->where('subject_id', (int) $request->subject_id);

        $perPage = (int) $request->get('per_page', 50);
        $paginator = $query->latest()->paginate($perPage);

        $items = $paginator->getCollection()->map(fn($m) => [
            'id'             => $m->id,
            'exam_id'        => $m->exam_id,
            'exam_name'      => $m->exam?->exam_name,
            'subject_id'     => $m->subject_id,
            'subject_name'   => $m->subject?->name,
            'marks_obtained' => $m->marks_obtained !== null ? (float) $m->marks_obtained : null,
            'max_marks'      => $m->max_marks      !== null ? (float) $m->max_marks      : null,
            'percentage'     => $m->percentage     !== null ? (float) $m->percentage     : null,
            'grade'          => $m->grade,
            'is_absent'      => (bool) $m->is_absent,
            'remarks'        => $m->remarks,
        ]);

        return $this->paginated($items, $this->paginationMeta($paginator), 'Marks fetched successfully.');
    }

    /**
     * GET /api/v1/student/marks/overall-performance
     *
     * Aggregated performance view. Returns:
     *   - overall_percentage
     *   - subject_wise_performance (per subject: avg %, exam count, avg marks)
     *   - performance_trend (last 5 exams: name, %, date)
     *   - grade_distribution (count of each grade)
     *   - total_exams_given
     */
    public function overallPerformance()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $student = StudentDetail::where('user_id', $user->id)->first(['id', 'organization_id', 'full_name', 'roll_no', 'admission_no', 'standard_id', 'section_id']);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $orgId = $student->organization_id;
        $sid   = $student->id;

        $totalExams = ExamCopy::where('student_detail_id', $sid)
            ->where('organization_id', $orgId)
            ->count();

        // Overall % across all graded exams
        $totals = ExamCopy::where('student_detail_id', $sid)
            ->where('organization_id', $orgId)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks')
            ->selectRaw('SUM(marks_obtained) as total_obtained, SUM(max_marks) as total_max')
            ->first();

        $totalObtained = (float) ($totals?->total_obtained ?? 0);
        $totalMax      = (float) ($totals?->total_max ?? 0);
        $overallPct    = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 2) : 0;

        // Subject-wise rollup
        $subjectWise = ExamCopy::with('subject:id,name')
            ->where('student_detail_id', $sid)
            ->where('organization_id', $orgId)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks')
            ->selectRaw('subject_id, SUM(marks_obtained) as total_obtained, SUM(max_marks) as total_max, COUNT(*) as exam_count')
            ->groupBy('subject_id')
            ->get()
            ->map(function ($row) {
                $pct = $row->total_max > 0 ? round(($row->total_obtained / $row->total_max) * 100, 2) : 0;
                $avg = $row->exam_count > 0 ? round($row->total_obtained / $row->exam_count, 2) : 0;
                return [
                    'subject_id'     => $row->subject_id,
                    'subject_name'   => $row->subject?->name,
                    'percentage'     => $pct,
                    'exam_count'     => (int) $row->exam_count,
                    'average_marks'  => $avg,
                ];
            });

        // Performance trend — last 5 exams
        $trend = ExamCopy::with('exam:id,exam_name')
            ->where('student_detail_id', $sid)
            ->where('organization_id', $orgId)
            ->whereNotNull('marks_obtained')
            ->whereNotNull('max_marks')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($e) => [
                'exam_id'        => $e->exam_id,
                'exam_name'      => $e->exam?->exam_name,
                'percentage'     => $e->max_marks > 0 ? round(($e->marks_obtained / $e->max_marks) * 100, 2) : 0,
                'marks_obtained' => (float) $e->marks_obtained,
                'max_marks'      => (float) $e->max_marks,
                'date'           => $e->created_at?->format('Y-m-d'),
            ]);

        $grades = ExamCopy::where('student_detail_id', $sid)
            ->where('organization_id', $orgId)
            ->whereNotNull('grade')
            ->selectRaw('grade, COUNT(*) as count')
            ->groupBy('grade')
            ->pluck('count', 'grade')
            ->toArray();

        return $this->success([
            'student' => [
                'id'           => $student->id,
                'name'         => $student->full_name,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
            ],
            'total_exams_given'        => $totalExams,
            'overall_percentage'       => $overallPct,
            'total_marks_obtained'     => $totalObtained,
            'total_max_marks'          => $totalMax,
            'subject_wise_performance' => $subjectWise->values()->all(),
            'performance_trend'        => $trend->values()->all(),
            'grade_distribution'       => $grades,
        ], 'Overall performance fetched successfully.');
    }
}
