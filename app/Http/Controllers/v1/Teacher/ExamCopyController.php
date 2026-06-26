<?php

namespace App\Http\Controllers\v1\Teacher;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Teacher → Exam-copy PDF management.
 *
 * Same scoping as Teacher\MarksController: a teacher can only upload /
 * view / edit / delete exam copies for the (class, section, subject)
 * triples they teach via the timetable.
 *
 * The PDF itself is stored on S3 under `admin/exam-copies/`; the public
 * URL is written back into the `pdf_path` column of `exam_copies`.
 *
 * Endpoints (all under /api/v1/teacher/exam-copies):
 *
 *   GET    /        → list copies the teacher can see
 *   GET    /{id}    → single copy (with pdf_url)
 *   POST   /        → upload PDF + metadata (multipart/form-data)
 *   POST   /{id}    → update (replace PDF or update metadata)
 *   DELETE /{id}    → delete (also wipes the S3 file)
 */
class ExamCopyController extends ApiController
{
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->success([], 'No teacher profile.');

        $triples = $this->teacherTriples($teacher->id, $user->organization_id);
        if (empty($triples)) {
            return $this->success([], 'You do not teach any class yet.');
        }

        $query = ExamCopy::with([
            'exam:id,exam_name',
            'standard:id,name',
            'section:id,name',
            'subject:id,name',
            'studentDetail:id,user_id,full_name,roll_no',
            'studentDetail.user:id,name',
        ])
            ->where('organization_id', $user->organization_id)
            ->whereNotNull('pdf_path') // only rows that have a PDF
            ->whereIn(\DB::raw('CONCAT(standard_id, "-", section_id, "-", subject_id)'), $triples);

        if ($request->filled('exam_id'))           $query->where('exam_id', (int) $request->exam_id);
        if ($request->filled('student_detail_id')) $query->where('student_detail_id', (int) $request->student_detail_id);
        if ($request->filled('standard_id'))       $query->where('standard_id', (int) $request->standard_id);
        if ($request->filled('section_id'))        $query->where('section_id', (int) $request->section_id);
        if ($request->filled('subject_id'))        $query->where('subject_id', (int) $request->subject_id);

        $perPage = (int) $request->get('per_page', 20);
        $paginator = $query->latest()->paginate($perPage);

        $items = $paginator->getCollection()->map(fn($c) => $this->formatCopy($c));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Exam copies fetched successfully.');
    }

    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $copy = ExamCopy::with([
            'exam:id,exam_name',
            'standard:id,name',
            'section:id,name',
            'subject:id,name',
            'studentDetail:id,user_id,full_name,roll_no',
            'studentDetail.user:id,name',
        ])
            ->where('organization_id', $user->organization_id)
            ->find($id);

        if (!$copy) return $this->error('Exam copy not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $copy->standard_id, $copy->section_id, $copy->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        return $this->success($this->formatCopy($copy), 'Exam copy fetched successfully.');
    }

    /**
     * POST /api/v1/teacher/exam-copies   (multipart/form-data)
     *
     * Fields:
     *   exam_id, student_detail_id, standard_id, section_id, subject_id,
     *   pdf       — file (required, .pdf/.jpg/.jpeg/.png, max 2 MB)
     *   marks_obtained, max_marks, grade  — optional, set when marks are
     *                                       being uploaded together with the
     *                                       PDF in one go (otherwise leave
     *                                       to the marks endpoint).
     */
    public function store(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'exam_id'           => 'required|integer|exists:exams,id',
            'student_detail_id' => 'required|integer|exists:student_details,id',
            'standard_id'       => 'required|integer|exists:standards,id',
            'section_id'        => 'required|integer|exists:sections,id',
            'subject_id'        => 'required|integer|exists:subjects,id',
            'pdf'               => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048', // max 2 MB
            'marks_obtained'    => 'nullable|numeric|min:0',
            'max_marks'         => 'nullable|numeric|min:1',
            'grade'             => 'nullable|string|max:10',
            'remarks'           => 'nullable|string|max:500',
        ])) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, (int) $request->standard_id, (int) $request->section_id, (int) $request->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        // Find existing row for the same (student, exam, subject) — if marks
        // were uploaded first, we just attach the PDF to that row.
        $copy = ExamCopy::where('organization_id', $user->organization_id)
            ->where('exam_id', (int) $request->exam_id)
            ->where('student_detail_id', (int) $request->student_detail_id)
            ->where('subject_id', (int) $request->subject_id)
            ->first();

        // Upload the PDF first.
        $path = $request->file('pdf')->store('admin/exam-copies', 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        $pdfUrl = Storage::disk('s3')->url($path);

        $payload = [
            'organization_id'   => $user->organization_id,
            'user_id'           => $user->id,
            'uploaded_by'       => $user->id,
            'teacher_detail_id' => $teacher->id,
            'student_detail_id' => (int) $request->student_detail_id,
            'standard_id'       => (int) $request->standard_id,
            'section_id'        => (int) $request->section_id,
            'subject_id'        => (int) $request->subject_id,
            'exam_id'           => (int) $request->exam_id,
            'pdf_path'          => $pdfUrl,
        ];

        if ($request->filled('marks_obtained') && $request->filled('max_marks')) {
            $mo = (float) $request->marks_obtained;
            $mx = (float) $request->max_marks;
            $payload['marks_obtained'] = $mo;
            $payload['max_marks']      = $mx;
            $payload['percentage']     = $mx > 0 ? round(($mo / $mx) * 100, 2) : 0;
        }
        if ($request->filled('grade'))   $payload['grade']   = $request->grade;
        if ($request->filled('remarks')) $payload['remarks'] = $request->remarks;

        if ($copy) {
            // Replace any prior PDF on this row.
            $this->deleteOldPdf($copy->pdf_path);
            $copy->fill($payload)->save();
        } else {
            $copy = ExamCopy::create($payload);
        }

        $copy->load(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'subject:id,name', 'studentDetail:id,user_id,full_name,roll_no', 'studentDetail.user:id,name']);

        return $this->success($this->formatCopy($copy), 'Exam copy uploaded successfully.', 201);
    }

    /**
     * POST /api/v1/teacher/exam-copies/{id}   (multipart/form-data, _method=PUT also OK)
     *
     * Update an existing copy. PDF is optional — omit to keep the existing
     * file, include to replace it.
     */
    public function update(Request $request, int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $copy = ExamCopy::where('organization_id', $user->organization_id)->find($id);
        if (!$copy) return $this->error('Exam copy not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $copy->standard_id, $copy->section_id, $copy->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        if ($err = $this->validateWith($request, [
            'pdf'            => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // max 2 MB
            'marks_obtained' => 'sometimes|numeric|min:0',
            'max_marks'      => 'sometimes|numeric|min:1',
            'grade'          => 'sometimes|nullable|string|max:10',
            'remarks'        => 'sometimes|nullable|string|max:500',
        ])) return $err;

        if ($request->hasFile('pdf')) {
            $this->deleteOldPdf($copy->pdf_path);
            $path = $request->file('pdf')->store('admin/exam-copies', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $copy->pdf_path = Storage::disk('s3')->url($path);
        }

        if ($request->has('marks_obtained') || $request->has('max_marks')) {
            $mo = $request->has('marks_obtained') ? (float) $request->marks_obtained : (float) $copy->marks_obtained;
            $mx = $request->has('max_marks')      ? (float) $request->max_marks      : (float) $copy->max_marks;
            $copy->marks_obtained = $mo;
            $copy->max_marks      = $mx;
            $copy->percentage     = $mx > 0 ? round(($mo / $mx) * 100, 2) : 0;
        }
        if ($request->has('grade'))   $copy->grade   = $request->grade;
        if ($request->has('remarks')) $copy->remarks = $request->remarks;

        $copy->save();
        $copy->load(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'subject:id,name', 'studentDetail:id,user_id,full_name,roll_no', 'studentDetail.user:id,name']);

        return $this->success($this->formatCopy($copy), 'Exam copy updated successfully.');
    }

    public function destroy(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $copy = ExamCopy::where('organization_id', $user->organization_id)->find($id);
        if (!$copy) return $this->error('Exam copy not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $copy->standard_id, $copy->section_id, $copy->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        $this->deleteOldPdf($copy->pdf_path);

        // Marks and exam-copy PDFs share the same row. If marks are still
        // recorded, only drop the PDF so the marks survive; otherwise remove
        // the row entirely.
        if ($copy->marks_obtained !== null) {
            $copy->pdf_path = null;
            $copy->save();

            return $this->success(null, 'Exam copy deleted successfully.');
        }

        \App\Models\Admin\ExamSubjectMark::where('exam_copy_id', $copy->id)->delete();
        $copy->delete();

        return $this->success(null, 'Exam copy deleted successfully.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function teacherTriples(int $teacherId, int $orgId): array
    {
        return TeacherTimeTable::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $orgId)
            ->get(['standard_id', 'section_id', 'subject_id'])
            ->filter(fn($r) => $r->standard_id && $r->section_id && $r->subject_id)
            ->map(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id)
            ->unique()
            ->values()
            ->toArray();
    }

    private function teacherTeachesTriple(int $teacherId, int $orgId, ?int $standardId, ?int $sectionId, ?int $subjectId): bool
    {
        if (!$standardId || !$sectionId || !$subjectId) return false;
        return TeacherTimeTable::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->where('subject_id', $subjectId)
            ->exists();
    }

    private function deleteOldPdf(?string $url): void
    {
        if (!$url) return;
        try {
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) Storage::disk('s3')->delete(ltrim($path, '/'));
        } catch (\Throwable $e) {
            logger()->warning('exam-copy old PDF delete failed: ' . $e->getMessage());
        }
    }

    private function formatCopy(ExamCopy $c): array
    {
        return [
            'id'             => $c->id,
            'exam'           => $c->exam     ? ['id' => $c->exam->id,     'name' => $c->exam->exam_name] : null,
            'standard'       => $c->standard ? ['id' => $c->standard->id, 'name' => $c->standard->name]  : null,
            'section'        => $c->section  ? ['id' => $c->section->id,  'name' => $c->section->name]   : null,
            'subject'        => $c->subject  ? ['id' => $c->subject->id,  'name' => $c->subject->name]   : null,
            'student'        => $c->studentDetail ? [
                'id'      => $c->studentDetail->id,
                'name'    => $c->studentDetail->full_name ?? $c->studentDetail->user?->name,
                'roll_no' => $c->studentDetail->roll_no,
            ] : null,
            'pdf_url'        => $c->pdf_path,
            'marks_obtained' => $c->marks_obtained !== null ? (float) $c->marks_obtained : null,
            'max_marks'      => $c->max_marks      !== null ? (float) $c->max_marks      : null,
            'percentage'     => $c->percentage     !== null ? (float) $c->percentage     : null,
            'grade'          => $c->grade,
            'remarks'        => $c->remarks,
            'uploaded_at'    => $c->updated_at,
        ];
    }
}
