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

    /**
     * GET /admin/report-card/lookups — active classes (with sections). Each
     * class and section also says how many students it has and how many hold
     * an issued card — the panel's Total / Issued for that class or section,
     * as its header counts them with the filter set.
     */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $students = StudentDetail::where('organization_id', $orgId)
            ->selectRaw('standard_id, section_id, COUNT(*) as c')
            ->groupBy('standard_id', 'section_id')->get();
        $issued = ReportCardModel::where('organization_id', $orgId)->where('status', 'issued')
            ->selectRaw('standard_id, section_id, COUNT(*) as c')
            ->groupBy('standard_id', 'section_id')->get();
        $count = fn ($rows, $classId, $sectionId = null) => (int) $rows
            ->where('standard_id', $classId)
            ->when($sectionId !== null, fn ($c) => $c->where('section_id', $sectionId))
            ->sum('c');

        $classes = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->inClassOrder()->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'students' => $count($students, $s->id),
                'issued'   => $count($issued, $s->id),
                'sections' => Section::where('standard_id', $s->id)->where('is_active', true)
                    ->orderBy('id')->get(['id', 'name'])
                    ->map(fn ($sec) => [
                        'id'       => $sec->id,
                        'name'     => $sec->name,
                        'students' => $count($students, $s->id, $sec->id),
                        'issued'   => $count($issued, $s->id, $sec->id),
                    ])->toArray(),
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
            // What the issue form put on the card (blank = worked out from the marks).
            'standard_id'   => $rc->standard_id,
            'section_id'    => $rc->section_id,
            'regd_no'       => $rc->regd_no,
            'remark'        => $rc->remark,
            'result'        => $rc->result,
        ];
    }

    /** GET /admin/report-card/{id} — one card, as the list shows it. */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rc = ReportCardModel::with([
            'studentDetail:id,full_name,admission_no,roll_no,standard_id,section_id',
            'studentDetail.standard:id,name',
            'studentDetail.section:id,name',
            'issuedBy:id,name',
        ])->where('organization_id', $user->organization_id)->find($id);
        if (!$rc) return $this->error('Report card not found.', 404);

        return $this->success($this->present($rc), 'Report card fetched.');
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

        $students = StudentDetail::with(['standard', 'section', 'user:id,image'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->orderBy('full_name')->get();

        // Each student's issued card here, newest first, so a row can open it.
        $cards = ReportCardModel::where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->where('status', 'issued')
            ->latest('issued_at')
            ->get(['id', 'student_detail_id', 'issued_at'])
            ->unique('student_detail_id')
            ->keyBy('student_detail_id');

        $exams = Exam::where('organization_id', $orgId)->where('is_published', true)->get();

        if ($exams->isEmpty()) {
            return $this->success([
                'students' => $students->map(fn ($s) => $this->studentRow($s, false, false, 'No published exams found', $cards->get($s->id)))->values(),
            ], 'No published exams found.');
        }

        $subjectIds = SectionSubject::where('section_id', $sectionId)
            ->where('standard_id', $standardId)
            ->where('organization_id', $orgId)
            ->pluck('subject_id')->toArray();

        if (empty($subjectIds)) {
            return $this->success([
                'students' => $students->map(fn ($s) => $this->studentRow($s, false, false, 'No subjects assigned to this section', $cards->get($s->id)))->values(),
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

        $rows = $students->map(function ($student) use ($totalRequired, $examCopyCounts, $issuedStudentIds, $cards) {
            $count = $examCopyCounts[$student->id] ?? 0;
            $marksComplete = $count >= $totalRequired;
            $missing = $marksComplete ? '' : ($totalRequired - $count) . " of {$totalRequired} exam-subject marks missing";
            return $this->studentRow($student, $marksComplete, in_array($student->id, $issuedStudentIds), $missing, $cards->get($student->id));
        });

        return $this->success(['students' => $rows->values()], 'Eligible students fetched.');
    }

    private function studentRow($student, bool $marksComplete, bool $alreadyIssued, string $missing, ?ReportCardModel $card = null): array
    {
        return [
            'id'             => $student->id,
            'full_name'      => $student->full_name,
            'admission_no'   => $student->admission_no,
            'roll_no'        => $student->roll_no ?? 'N/A',
            'marks_complete' => $marksComplete,
            'already_issued' => $alreadyIssued,
            'missing_info'   => $missing,
            // For the app's rows: the photo, the Regd. No the issue form
            // starts from, and the card already issued here, to open it.
            'image'               => $student->user?->image,
            'registration_number' => $student->registration_number,
            'report_card_id'      => $card?->id,
            'issued_label'        => $card?->issued_at?->format('d M Y'),
        ];
    }

    /**
     * POST /admin/report-card/issue — { standard_id, section_id, student_ids: [] },
     * and, as the panel's Issue Report Cards slide-in asks it, optionally
     * issue_date (printed as the card's Issue Date) and details: [{ student_id,
     * regd_no, remark, result: PASSED|FAILED }]. A blank remark or result is
     * stored as null, so the card works it out from the marks; without
     * issue_date the card is dated now, as before.
     */
    public function issue(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id'  => 'required|integer',
            'section_id'   => 'required|integer',
            'student_ids'  => 'required|array|min:1',
            'student_ids.*'=> 'integer',
            'issue_date'           => 'nullable|date',
            'details'              => 'nullable|array',
            'details.*.student_id' => 'required_with:details|integer',
            'details.*.regd_no'    => 'nullable|string|max:50',
            'details.*.remark'     => 'nullable|string|max:500',
            'details.*.result'     => 'nullable|in:PASSED,FAILED',
        ], [
            'details.*.remark.max' => 'A remark may not be longer than 500 characters.',
        ])) return $err;

        $orgId = $user->organization_id;
        $currentYear = now()->month >= 4
            ? now()->year . '-' . (now()->year + 1)
            : (now()->year - 1) . '-' . now()->year;

        // The panel dates the batch on the day picked, at the time it is issued.
        $issuedAt = $request->filled('issue_date')
            ? \Carbon\Carbon::parse($request->issue_date)->setTimeFrom(now())
            : now();
        $details = collect($request->input('details', []))->keyBy('student_id');

        // Only this school's students are issued a card.
        $ownIds = StudentDetail::where('organization_id', $orgId)
            ->whereIn('id', $request->student_ids)->pluck('id')->all();

        $issued = 0; $skipped = 0;
        foreach ($request->student_ids as $studentId) {
            if (!in_array((int) $studentId, $ownIds, true)) { $skipped++; continue; }

            $exists = ReportCardModel::where('organization_id', $orgId)
                ->where('student_detail_id', $studentId)
                ->where('standard_id', $request->standard_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'issued')->exists();
            if ($exists) { $skipped++; continue; }

            $row = $details->get($studentId) ?? $details->get((string) $studentId) ?? [];

            ReportCardModel::create([
                'organization_id'   => $orgId,
                'student_detail_id' => $studentId,
                'standard_id'       => $request->standard_id,
                'section_id'        => $request->section_id,
                'academic_year'     => $currentYear,
                'regd_no'           => trim((string) ($row['regd_no'] ?? '')) ?: null,
                'remark'            => trim((string) ($row['remark'] ?? '')) ?: null,
                'result'            => ($row['result'] ?? '') ?: null,
                'issued_at'         => $issuedAt,
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
