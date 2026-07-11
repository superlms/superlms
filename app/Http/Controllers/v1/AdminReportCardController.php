<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Admin\ReportCardController as WebReportCardController;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\ReportCard as ReportCardModel;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Http\Request;

/**
 * School-admin Report Card module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/ReportCard.php — a filtered listing of issued cards,
 * an "issue" flow that surfaces per-student marks-completeness before issuing to
 * the selected students, and revoke. PDF download delegates to the web controller
 * (same blade), scoped by the authenticated user's organization.
 */
class AdminReportCardController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/report-card/lookups — active classes (with sections). */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('id')->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'sections' => Section::where('standard_id', $s->id)->where('is_active', true)
                    ->orderBy('id')->get(['id', 'name'])->toArray(),
            ]);

        return $this->success(['classes' => $classes], 'Report card lookups fetched.');
    }

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/report-card/stats?standard_id=&section_id= */
    public function stats(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $studentsQuery = StudentDetail::where('organization_id', $orgId);
        $reportCardsQuery = ReportCardModel::where('organization_id', $orgId);

        if ($request->filled('standard_id')) {
            $studentsQuery->where('standard_id', $request->standard_id);
            $reportCardsQuery->where('standard_id', $request->standard_id);
        }
        if ($request->filled('section_id')) {
            $studentsQuery->where('section_id', $request->section_id);
            $reportCardsQuery->where('section_id', $request->section_id);
        }

        $totalStudents = (clone $studentsQuery)->count();
        $activeStudents = (clone $studentsQuery)
            ->whereHas('user', fn ($q) => $q->where('is_active', true))->count();
        $issued = (clone $reportCardsQuery)->where('status', 'issued')->count();
        $pending = max(0, $totalStudents - $issued);

        return $this->success([
            'total_students'  => $totalStudents,
            'active_students' => $activeStudents,
            'issued'          => $issued,
            'pending'         => $pending,
        ], 'Report card stats fetched.');
    }

    // ══════════════════════════ LIST ══════════════════════════

    /** GET /admin/report-card?search=&standard_id=&section_id=&status=&per_page=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $query = ReportCardModel::with([
            'studentDetail:id,full_name,admission_no,roll_no,standard_id,section_id',
            'studentDetail.standard:id,name',
            'studentDetail.section:id,name',
            'issuedBy:id,name',
        ])->where('organization_id', $user->organization_id);

        if ($s = $request->input('search')) {
            $query->whereHas('studentDetail', fn ($q) =>
                $q->where('full_name', 'like', "%{$s}%")->orWhere('admission_no', 'like', "%{$s}%"));
        }
        if ($request->filled('standard_id')) $query->where('standard_id', $request->standard_id);
        if ($request->filled('section_id'))  $query->where('section_id', $request->section_id);
        if ($request->filled('status'))      $query->where('status', $request->status);

        $paginator = $query->latest('issued_at')->paginate((int) $request->input('per_page', 10));
        $items = collect($paginator->items())->map(fn ($rc) => $this->present($rc));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Report cards fetched.');
    }

    private function present(ReportCardModel $rc): array
    {
        $s = $rc->studentDetail;
        return [
            'id'            => $rc->id,
            'student_id'    => $rc->student_detail_id,
            'full_name'     => $s?->full_name ?? '—',
            'admission_no'  => $s?->admission_no,
            'roll_no'       => $s?->roll_no,
            'standard'      => $s?->standard?->name,
            'section'       => $s?->section?->name,
            'academic_year' => $rc->academic_year,
            'status'        => $rc->status,
            'issued_by'     => $rc->issuedBy?->name,
            'issued_at'     => $rc->issued_at?->toIso8601String(),
            'issued_label'  => $rc->issued_at?->format('d M Y'),
            'pdf_url'       => url("/api/v1/admin/report-card/{$rc->id}/pdf"),
        ];
    }

    /** GET /admin/report-card/{id}/pdf — streams the same blade PDF as the web admin. */
    public function pdf(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        if (!ReportCardModel::where('organization_id', $user->organization_id)->whereKey($id)->exists()) {
            return $this->error('Report card not found.', 404);
        }

        return app(WebReportCardController::class)->download($request, $user->organization_id, $id);
    }

    // ══════════════════════════ ISSUE FLOW ══════════════════════════

    /**
     * GET /admin/report-card/issue-students?standard_id=&section_id=
     * Students with marks-complete + already-issued flags (mirrors Livewire).
     */
    public function issueStudents(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $standardId = (int) $request->standard_id;
        $sectionId = (int) $request->section_id;

        $students = StudentDetail::with(['standard', 'section'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->orderBy('full_name')->get();

        $exams = Exam::where('organization_id', $orgId)->where('is_published', true)->get();

        if ($exams->isEmpty()) {
            return $this->success([
                'students' => $students->map(fn ($s) => $this->studentRow($s, false, false, 'No published exams found'))->values(),
            ], 'No published exams found.');
        }

        $subjectIds = SectionSubject::where('section_id', $sectionId)
            ->where('standard_id', $standardId)
            ->where('organization_id', $orgId)
            ->pluck('subject_id')->toArray();

        if (empty($subjectIds)) {
            return $this->success([
                'students' => $students->map(fn ($s) => $this->studentRow($s, false, false, 'No subjects assigned to this section'))->values(),
            ], 'No subjects assigned to this section.');
        }

        $examIds = $exams->pluck('id')->toArray();
        $totalRequired = count($examIds) * count($subjectIds);

        $issuedStudentIds = ReportCardModel::where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->where('status', 'issued')
            ->pluck('student_detail_id')->toArray();

        $examCopyCounts = ExamCopy::where('organization_id', $orgId)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->whereIn('exam_id', $examIds)
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('student_detail_id, COUNT(DISTINCT CONCAT(exam_id, "-", subject_id)) as marks_count')
            ->groupBy('student_detail_id')
            ->pluck('marks_count', 'student_detail_id')->toArray();

        $rows = $students->map(function ($student) use ($totalRequired, $examCopyCounts, $issuedStudentIds) {
            $count = $examCopyCounts[$student->id] ?? 0;
            $marksComplete = $count >= $totalRequired;
            $missing = $marksComplete ? '' : ($totalRequired - $count) . " of {$totalRequired} exam-subject marks missing";
            return $this->studentRow($student, $marksComplete, in_array($student->id, $issuedStudentIds), $missing);
        });

        return $this->success(['students' => $rows->values()], 'Eligible students fetched.');
    }

    private function studentRow($student, bool $marksComplete, bool $alreadyIssued, string $missing): array
    {
        return [
            'id'             => $student->id,
            'full_name'      => $student->full_name,
            'admission_no'   => $student->admission_no,
            'roll_no'        => $student->roll_no ?? 'N/A',
            'marks_complete' => $marksComplete,
            'already_issued' => $alreadyIssued,
            'missing_info'   => $missing,
        ];
    }

    /** POST /admin/report-card/issue — { standard_id, section_id, student_ids: [] }. */
    public function issue(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id'  => 'required|integer',
            'section_id'   => 'required|integer',
            'student_ids'  => 'required|array|min:1',
            'student_ids.*'=> 'integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $currentYear = now()->month >= 4
            ? now()->year . '-' . (now()->year + 1)
            : (now()->year - 1) . '-' . now()->year;

        $issued = 0; $skipped = 0;
        foreach ($request->student_ids as $studentId) {
            $exists = ReportCardModel::where('organization_id', $orgId)
                ->where('student_detail_id', $studentId)
                ->where('standard_id', $request->standard_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'issued')->exists();
            if ($exists) { $skipped++; continue; }

            ReportCardModel::create([
                'organization_id'   => $orgId,
                'student_detail_id' => $studentId,
                'standard_id'       => $request->standard_id,
                'section_id'        => $request->section_id,
                'academic_year'     => $currentYear,
                'issued_at'         => now(),
                'issued_by'         => $user->id,
                'status'            => 'issued',
            ]);
            $issued++;
        }

        $message = "Successfully issued {$issued} report card(s).";
        if ($skipped > 0) $message .= " {$skipped} skipped (already issued).";

        return $this->success(['issued' => $issued, 'skipped' => $skipped], $message, 201);
    }

    /** POST /admin/report-card/{id}/revoke */
    public function revoke($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rc = ReportCardModel::where('organization_id', $user->organization_id)->find($id);
        if (!$rc) return $this->error('Report card not found.', 404);

        $rc->update(['status' => 'revoked']);
        return $this->success(null, 'Report card has been revoked.');
    }
}
