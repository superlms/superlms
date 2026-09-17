<?php

namespace App\Http\Controllers\v1\Student;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Student\SectionSubject;
use App\Models\Student\StandardSubject;
use App\Models\Student\StudentDetail;
use Illuminate\Http\Request;

/**
 * Student → View own exam-copy PDFs.
 *
 * Read-only. The student gets back the same set of marks rows they have,
 * but only those where a teacher has uploaded a PDF copy — `pdf_url` is
 * always populated.
 *
 * Endpoints (all under /api/v1/student/exam-copies):
 *
 *   GET /                  → list copies the student has access to
 *   GET /{id}              → single copy with pdf_url
 */
class ExamCopyController extends ApiController
{
    /**
     * GET /api/v1/student/exam-copies
     *
     * Optional filters: exam_id, subject_id, per_page
     *
     * Only rows that actually have a PDF attached are returned — the
     * front-end can render "tap to open" cards without further checks.
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
            'standard:id,name',
            'section:id,name',
        ])
            ->where('organization_id', $student->organization_id)
            ->where('student_detail_id', $student->id)
            ->whereNotNull('pdf_path'); // only copies with an uploaded PDF

        if ($request->filled('exam_id'))    $query->where('exam_id',    (int) $request->exam_id);
        if ($request->filled('subject_id')) $query->where('subject_id', (int) $request->subject_id);

        $perPage = (int) $request->get('per_page', 50);
        $paginator = $query->latest()->paginate($perPage);

        $items = $paginator->getCollection()->map(fn($c) => $this->formatCopy($c));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Exam copies fetched successfully.');
    }

    /**
     * GET /api/v1/student/exam-copies/{id}
     */
    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $student = StudentDetail::where('user_id', $user->id)->first(['id', 'organization_id']);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $copy = ExamCopy::with([
            'exam:id,exam_name',
            'subject:id,name',
            'standard:id,name',
            'section:id,name',
        ])
            ->where('organization_id', $student->organization_id)
            ->where('student_detail_id', $student->id)
            ->find($id);

        if (!$copy) {
            return $this->error('Exam copy not found.', 404);
        }

        return $this->success($this->formatCopy($copy), 'Exam copy fetched successfully.');
    }

    /**
     * GET /api/v1/student/exam-copies/exams/{examId}
     *
     * One exam's copies, subject by subject: the student's subjects (the
     * section's, else the class's) plus any other subject with a copy or marks
     * in that exam, each with its marks and its copy's link, or `has_copy`
     * false while none is up.
     */
    public function exam(int $examId)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $student = StudentDetail::where('user_id', $user->id)->first(['id', 'organization_id', 'standard_id', 'section_id']);
        if (!$student) {
            return $this->error('Student profile not found.', 404);
        }

        $orgId = $student->organization_id;
        $exam  = Exam::where('organization_id', $orgId)->where('is_published', true)->find($examId);
        if (!$exam) {
            return $this->error('Exam not found.', 404);
        }

        $subjects = collect();
        if ($student->section_id) {
            $subjects = SectionSubject::with('subject')
                ->where('organization_id', $orgId)
                ->where('standard_id', $student->standard_id)
                ->where('section_id', $student->section_id)
                ->get()
                ->pluck('subject');
        }
        if ($subjects->filter()->isEmpty()) {
            $subjects = StandardSubject::with('subject')
                ->where('organization_id', $orgId)
                ->where('standard_id', $student->standard_id)
                ->get()
                ->pluck('subject');
        }
        $subjects = $subjects->filter()->unique('id')->values();

        $rows = ExamCopy::with('subject')
            ->where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('exam_id', $exam->id)
            ->where(fn($q) => $q->whereNotNull('pdf_path')->orWhereNotNull('marks_obtained'))
            ->orderByRaw('pdf_path IS NULL')
            ->latest()
            ->get()
            ->unique('subject_id')
            ->keyBy('subject_id');

        $rows->each(function ($r) use (&$subjects) {
            if ($r->subject && !$subjects->contains('id', $r->subject_id)) {
                $subjects->push($r->subject);
            }
        });

        $items = $subjects->map(function ($subject) use ($rows) {
            $r      = $rows->get($subject->id);
            $marked = $r && $r->marks_obtained !== null;
            $absent = $marked && (bool) $r->is_absent;
            return [
                'subject_id'     => $subject->id,
                'subject_name'   => $subject->name,
                'subject_image'  => $subject->iconUrl(),
                'has_copy'       => (bool) $r?->pdf_path,
                'pdf_url'        => $r?->pdfUrl(),
                'marked'         => $marked,
                'is_absent'      => $absent,
                'marks_obtained' => $marked && !$absent ? (float) $r->marks_obtained : null,
                'max_marks'      => $marked && $r->max_marks !== null ? (float) $r->max_marks : null,
                'grade'          => $marked ? $r->grade_letter : null,
                'remarks'        => $r?->remarks ?: null,
                'uploaded_at'    => $r?->pdf_path ? $r->updated_at?->toIso8601String() : null,
            ];
        });

        return $this->success([
            'exam' => [
                'id'          => $exam->id,
                'name'        => $exam->exam_name,
                'total_marks' => $exam->total_marks !== null ? (float) $exam->total_marks : null,
            ],
            'subjects' => $items->values()->all(),
            'copies'   => $items->where('has_copy', true)->count(),
        ], 'Exam copies fetched successfully.');
    }

    private function formatCopy(ExamCopy $c): array
    {
        return [
            'id'             => $c->id,
            'exam'           => $c->exam     ? ['id' => $c->exam->id,     'name' => $c->exam->exam_name] : null,
            'subject'        => $c->subject  ? ['id' => $c->subject->id,  'name' => $c->subject->name]   : null,
            'standard'       => $c->standard ? ['id' => $c->standard->id, 'name' => $c->standard->name]  : null,
            'section'        => $c->section  ? ['id' => $c->section->id,  'name' => $c->section->name]   : null,
            'pdf_url'        => $c->pdfUrl(),
            'marks_obtained' => $c->marks_obtained !== null ? (float) $c->marks_obtained : null,
            'max_marks'      => $c->max_marks      !== null ? (float) $c->max_marks      : null,
            'percentage'     => $c->percentage     !== null ? (float) $c->percentage     : null,
            'grade'          => $c->grade,
            'remarks'        => $c->remarks,
            'uploaded_at'    => $c->updated_at,
        ];
    }
}
