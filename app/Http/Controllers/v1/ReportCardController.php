<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ReportCard;
use App\Models\Student\StudentDetail;
use App\Services\GradingService;
use App\Services\ReportCardService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportCardController extends ApiController
{
    /**
     * GET /api/v1/report-card
     *
     * Returns the student's report cards (list, latest first).
     * Filters: academic_year
     * Only students (role=user) can access their own report card.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $cards = ReportCard::with(['standard:id,name', 'section:id,name', 'issuedBy:id,name'])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->when($request->filled('academic_year'), fn($q) => $q->where('academic_year', $request->academic_year))
            ->latest('issued_at')
            ->paginate((int) $request->get('per_page', 10));

        $items = $cards->getCollection()->map(fn($c) => [
            'id'            => $c->id,
            'academic_year' => $c->academic_year,
            'class'         => ($c->standard?->name ?? '') . ($c->section ? ' - ' . $c->section->name : ''),
            'issued_at'     => $c->issued_at?->format('Y-m-d'),
            'issued_by'     => $c->issuedBy?->name,
            'status'        => $c->status,
        ]);

        return $this->paginated($items, $this->paginationMeta($cards), 'Report cards fetched successfully.');
    }

    /**
     * GET /api/v1/report-card/{id}
     *
     * Full report card — includes all exam results for the academic year.
     */
    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $card = ReportCard::with(['standard:id,name', 'section:id,name', 'issuedBy:id,name'])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->find($id);

        if (!$card) {
            return $this->error('Report card not found.', 404);
        }

        $grading = app(GradingService::class);

        // All exam copies for this student in the report card's academic year.
        $copies = ExamCopy::with(['subject:id,name,code', 'exam:id,exam_name,exam_type'])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->whereHas('exam', fn($q) => $q->where('academic_year', $card->academic_year))
            ->get();

        // Per exam × subject rows (kept for detailed breakdowns).
        $results = $copies->map(fn($ec) => [
            'exam'           => $ec->exam?->exam_name,
            'exam_type'      => $ec->exam?->exam_type,
            'subject'        => $ec->subject?->name,
            'subject_code'   => $ec->subject?->code,
            'marks_obtained' => $ec->marks_obtained,
            'max_marks'      => $ec->max_marks,
            'percentage'     => $ec->percentage,
            'grade'          => $ec->grade,
            'is_absent'      => (bool) $ec->is_absent,
            'remarks'        => $ec->remarks,
        ])->values();

        // Annual per-subject aggregate (summed across the year's exams), each
        // with a grade derived from the central grading system.
        $subjects = $copies->groupBy('subject_id')->map(function ($group) use ($grading) {
            $obtained = (float) $group->sum('marks_obtained');
            $max      = (float) $group->sum('max_marks');
            $pct      = $max > 0 ? round($obtained / $max * 100, 2) : null;
            $first    = $group->first();

            return [
                'subject_id'   => $first->subject_id,
                'subject'      => $first->subject?->name,
                'subject_code' => $first->subject?->code,
                'obtained'     => $obtained,
                'total'        => $max,
                'percentage'   => $pct,
                'grade'        => $grading->gradeLetter($pct),
            ];
        })->values();

        // Overall result.
        $totalObtained = (float) $copies->sum('marks_obtained');
        $totalMax      = (float) $copies->sum('max_marks');
        $overallPct    = $totalMax > 0 ? round($totalObtained / $totalMax * 100, 2) : null;
        $overallGrade  = $grading->gradeFor($overallPct);

        [$rank, $rankTotal] = $this->computeRank($card, $user->organization_id, $overallPct);

        return $this->success([
            'id'            => $card->id,
            'title'         => 'Annual Report Card',
            'academic_year' => $card->academic_year,
            'class'         => ($card->standard?->name ?? '') . ($card->section ? ' - ' . $card->section->name : ''),
            'issued_at'     => $card->issued_at?->format('Y-m-d'),
            'issued_by'     => $card->issuedBy?->name,
            'status'        => $card->status,

            'student' => [
                'full_name'    => $student->full_name,
                'roll_no'      => $student->roll_no,
                'admission_no' => $student->admission_no,
                'image_url'    => $student->image ? Storage::url($student->image) : null,
            ],

            'summary' => [
                'total_obtained' => $totalObtained,
                'total_max'      => $totalMax,
                'percentage'     => $overallPct,
                'grade'          => $overallGrade['grade'] ?? null,
                'grade_remark'   => $overallGrade['remark'] ?? null,
                'rank'           => $rank,
                'rank_total'     => $rankTotal,
                'result'         => $grading->isPass($overallPct) ? 'Pass' : ($overallPct === null ? null : 'Fail'),
            ],

            'subjects'      => $subjects,
            'results'       => $results,
            'grading_scale' => $grading->scale(),
            'pdf_url'       => url("/api/v1/report-card/{$card->id}/pdf"),
        ], 'Report card fetched successfully.');
    }

    /**
     * GET /api/v1/report-card/{id}/pdf
     *
     * Streams the issued report card as a PDF using the *same* dompdf template
     * the admin panel generates (admin.report-card-pdf), so the in-app preview
     * and download mirror the official document exactly.
     */
    public function pdf(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('user')) return $err;

        $student = StudentDetail::where('user_id', $user->id)
            ->where('organization_id', $user->organization_id)
            ->first();

        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $card = ReportCard::with([
            'studentDetail.standard',
            'studentDetail.section',
            'standard',
            'section',
            'organization',
            'issuedBy',
        ])
            ->where('student_detail_id', $student->id)
            ->where('organization_id', $user->organization_id)
            ->where('status', 'issued')
            ->find($id);

        if (!$card) {
            return $this->error('Report card not found.', 404);
        }

        $data = app(ReportCardService::class)->buildPdfData($card);

        $pdf = Pdf::loadView('admin.report-card-pdf', $data)->setPaper('a4', 'portrait');
        $name = str_replace(' ', '_', $student->full_name ?? 'student');

        return $pdf->stream("Report_Card_{$name}.pdf");
    }

    /**
     * Rank the student within their class+section for the academic year, by
     * aggregate percentage across that year's exams. Uses standard competition
     * ranking (students with a strictly higher percentage rank above).
     *
     * @return array{0:?int,1:int}  [rank, totalStudents]
     */
    private function computeRank(ReportCard $card, int $orgId, ?float $myPct): array
    {
        $yearExamIds = Exam::where('organization_id', $orgId)
            ->where('academic_year', $card->academic_year)
            ->pluck('id');

        if ($yearExamIds->isEmpty()) {
            return [null, 0];
        }

        $rows = ExamCopy::where('organization_id', $orgId)
            ->where('standard_id', $card->standard_id)
            ->where('section_id', $card->section_id)
            ->whereIn('exam_id', $yearExamIds)
            ->get(['student_detail_id', 'marks_obtained', 'max_marks']);

        $byStudent = $rows->groupBy('student_detail_id')->map(function ($group) {
            $max = (float) $group->sum('max_marks');
            return $max > 0 ? (float) $group->sum('marks_obtained') / $max * 100 : 0.0;
        });

        $total = $byStudent->count();
        if ($total === 0 || $myPct === null) {
            return [null, $total];
        }

        $higher = $byStudent->filter(fn($p) => $p > $myPct + 0.0001)->count();

        return [$higher + 1, $total];
    }
}
