<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
use Illuminate\Http\Request;

/**
 * Filter / dropdown helper endpoints.
 *
 * Designed for client-side cascading filter UI:
 *   classes → sections → subjects → exams
 *
 * All endpoints respect the caller's role:
 *   - Student → only sees their own class/section/subjects
 *   - Teacher → only sees classes/sections/subjects they're assigned to
 *   - Admin   → sees all in their organization
 */
class FilterController extends ApiController
{
    /**
     * GET /api/v1/filters/classes
     */
    public function classes()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $orgId = $user->organization_id;

        if ($user->role === 'user') {
            $student = StudentDetail::where('user_id', $user->id)->first(['standard_id']);
            if (!$student) return $this->success([], 'No classes.');

            $std = Standard::where('id', $student->standard_id)
                ->where('organization_id', $orgId)->where('is_active', true)
                ->first(['id', 'name', 'code', 'order']);

            return $this->success($std ? [$this->fmtStd($std)] : [], 'Classes fetched.');
        }

        if ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) return $this->success([], 'No classes.');

            $stdIds = TeacherSubject::where('teacher_detail_id', $teacher->id)
                ->distinct()->pluck('standard_id')->filter()->toArray();

            $list = Standard::whereIn('id', $stdIds)
                ->where('organization_id', $orgId)->where('is_active', true)
                ->orderBy('order')
                ->get(['id', 'name', 'code', 'order'])
                ->map(fn($s) => $this->fmtStd($s));

            return $this->success($list, 'Classes fetched.');
        }

        // Admin / others — all classes in org
        $list = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('order')->get(['id', 'name', 'code', 'order'])
            ->map(fn($s) => $this->fmtStd($s));

        return $this->success($list, 'Classes fetched.');
    }

    /**
     * GET /api/v1/filters/sections?standard_id=
     */
    public function sections(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $request->validate(['standard_id' => 'required|integer']);
        $standardId = (int) $request->standard_id;

        if ($user->role === 'user') {
            $student = StudentDetail::where('user_id', $user->id)->first(['standard_id', 'section_id']);
            if (!$student || (int) $student->standard_id !== $standardId || !$student->section_id) {
                return $this->success([], 'No sections.');
            }
            $sec = Section::where('id', $student->section_id)->where('is_active', true)
                ->first(['id', 'name', 'standard_id']);
            return $this->success($sec ? [$this->fmtSec($sec)] : [], 'Sections fetched.');
        }

        $query = Section::where('standard_id', $standardId)->where('is_active', true);

        if ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) return $this->success([], 'No sections.');

            $secIds = TeacherSubject::where('teacher_detail_id', $teacher->id)
                ->where('standard_id', $standardId)
                ->distinct()->pluck('section_id')->filter()->toArray();

            if (empty($secIds)) return $this->success([], 'No sections for this class.');
            $query->whereIn('id', $secIds);
        }

        $list = $query->orderBy('name')->get(['id', 'name', 'standard_id'])
            ->map(fn($s) => $this->fmtSec($s));

        return $this->success($list, 'Sections fetched.');
    }

    /**
     * GET /api/v1/filters/subjects?standard_id=&section_id=
     */
    public function subjects(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $request->validate(['standard_id' => 'required|integer']);
        $standardId = (int) $request->standard_id;
        $sectionId  = $request->filled('section_id') ? (int) $request->section_id : null;
        $orgId      = $user->organization_id;

        $query = Subject::where('subjects.organization_id', $orgId)
            ->where('subjects.is_active', true);

        if ($sectionId) {
            $query->join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                  ->where('section_subjects.standard_id', $standardId)
                  ->where('section_subjects.section_id', $sectionId);
        } else {
            $query->join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
                  ->where('standard_subjects.standard_id', $standardId);
        }

        $query->select('subjects.id', 'subjects.name')->distinct();

        if ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) return $this->success([], 'No subjects.');

            $subQ = TeacherSubject::where('teacher_detail_id', $teacher->id)
                ->where('standard_id', $standardId);
            if ($sectionId) {
                $subQ->where(function ($q) use ($sectionId) {
                    $q->where('section_id', $sectionId)->orWhereNull('section_id');
                });
            }
            $assignedIds = $subQ->distinct()->pluck('subject_id')->filter()->toArray();
            if (empty($assignedIds)) return $this->success([], 'No subjects assigned.');
            $query->whereIn('subjects.id', $assignedIds);
        }

        $list = $query->orderBy('subjects.name')->get()
            ->map(fn($s) => ['id' => $s->id, 'name' => $s->name]);

        return $this->success($list, 'Subjects fetched.');
    }

    /**
     * GET /api/v1/filters/exams?academic_year=
     */
    public function exams(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $list = Exam::where('organization_id', $user->organization_id)
            ->where('is_published', true)
            ->when($request->filled('academic_year'), fn($q) => $q->where('academic_year', $request->academic_year))
            ->orderBy('start_date', 'desc')
            ->get(['id', 'exam_name', 'exam_type', 'academic_year', 'start_date', 'end_date'])
            ->map(fn($e) => [
                'id'            => $e->id,
                'name'          => $e->exam_name,
                'type'          => $e->exam_type,
                'academic_year' => $e->academic_year,
                'start_date'    => $e->start_date?->format('Y-m-d'),
                'end_date'      => $e->end_date?->format('Y-m-d'),
            ]);

        return $this->success($list, 'Exams fetched.');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function fmtStd($s): array
    {
        return ['id' => $s->id, 'name' => $s->name, 'code' => $s->code ?? null, 'order' => (int) ($s->order ?? 0)];
    }

    private function fmtSec($s): array
    {
        return ['id' => $s->id, 'name' => $s->name, 'standard_id' => $s->standard_id];
    }
}
