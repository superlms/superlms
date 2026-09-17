<?php

namespace App\Http\Controllers\v1\Teacher;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    // ── Upload Copies, as the admin panel does it ────────────────────────────
    //
    // The app's Upload Copies walks exam → class → students, the admin panel's
    // Upload Copies panel step for step: copies go only where the exam's marks
    // are already saved for that class, section and subject; a copy is one
    // PDF of up to 5 MB, kept on S3 under admin/exam-copies by its key; a new
    // PDF replaces the old one (whose file is deleted), and removing a copy
    // clears the PDF but keeps the marks.

    /** Largest copy the admin panel takes, in kilobytes. */
    private const COPY_MAX_KB = 5120;

    /**
     * GET /api/v1/teacher/exam-copies/classes?exam_id=X
     *
     * Every class, section and subject the teacher teaches, with the exam's
     * progress there: students, how many have marks saved (absent included),
     * how many copies are up, and whether marks exist at all.
     */
    public function examClasses(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, ['exam_id' => 'required|integer'])) return $err;

        $orgId = $user->organization_id;
        $exam  = Exam::where('organization_id', $orgId)->find((int) $request->exam_id);
        if (!$exam) return $this->error('Exam not found.', 404);

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->success([], 'No teacher profile.');

        $rows = TeacherTimeTable::with(['standard:id,name,order', 'section:id,name', 'subject:id,name'])
            ->where('teacher_detail_id', $teacher->id)
            ->where('organization_id', $orgId)
            ->get(['id', 'standard_id', 'section_id', 'subject_id'])
            ->filter(fn($r) => $r->standard && $r->section && $r->subject)
            ->unique(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id)
            ->sortBy([
                fn($a, $b) => [(int) $a->standard->order, $a->standard_id] <=> [(int) $b->standard->order, $b->standard_id],
                fn($a, $b) => strnatcasecmp($a->section->name, $b->section->name),
                fn($a, $b) => strnatcasecmp($a->subject->name, $b->subject->name),
            ])
            ->values();

        if ($rows->isEmpty()) {
            return $this->success([], 'You do not teach any class yet.');
        }

        $students = StudentDetail::where('organization_id', $orgId)
            ->whereIn('standard_id', $rows->pluck('standard_id')->unique())
            ->whereIn('section_id', $rows->pluck('section_id')->unique())
            ->selectRaw('standard_id, section_id, COUNT(*) as total')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->mapWithKeys(fn($r) => [$r->standard_id . '-' . $r->section_id => (int) $r->total]);

        $counts = ExamCopy::where('organization_id', $orgId)
            ->where('exam_id', $exam->id)
            ->selectRaw('standard_id, section_id, subject_id,
                SUM(CASE WHEN marks_obtained IS NOT NULL THEN 1 ELSE 0 END) as marked,
                SUM(CASE WHEN pdf_path IS NOT NULL AND pdf_path <> \'\' THEN 1 ELSE 0 END) as uploaded')
            ->groupBy('standard_id', 'section_id', 'subject_id')
            ->get()
            ->keyBy(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id);

        $items = $rows->map(function ($r) use ($students, $counts) {
            $c = $counts->get($r->standard_id . '-' . $r->section_id . '-' . $r->subject_id);
            return [
                'standard_id'   => $r->standard_id,
                'standard_name' => $r->standard->name,
                'section_id'    => $r->section_id,
                'section_name'  => $r->section->name,
                'subject_id'    => $r->subject_id,
                'subject_name'  => $r->subject->name,
                'subject_image' => $r->subject->iconUrl(),
                'students'      => (int) ($students[$r->standard_id . '-' . $r->section_id] ?? 0),
                'marked'        => (int) ($c->marked ?? 0),
                'uploaded'      => (int) ($c->uploaded ?? 0),
                'has_marks'     => (int) ($c->marked ?? 0) > 0,
            ];
        });

        return $this->success($items->all(), 'Classes fetched successfully.');
    }

    /**
     * GET /api/v1/teacher/exam-copies/sheet?exam_id=&standard_id=&section_id=&subject_id=
     *
     * The class's students for one exam and subject, each with their saved
     * marks and their copy, if one is up. `has_marks` false means the
     * exam's marks aren't in for this subject yet, so nothing can be uploaded.
     */
    public function sheet(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        [$ctx, $err] = $this->copyContext($request, $user);
        if ($err) return $err;

        return $this->success($this->formatSheet($ctx, $user->organization_id), 'Exam copies fetched successfully.');
    }

    /**
     * POST /api/v1/teacher/exam-copies/sheet/upload   (multipart/form-data)
     *
     * Body: exam_id, standard_id, section_id, subject_id, student_detail_id,
     *       pdf (PDF, up to 5 MB), remarks?
     *
     * Puts up — or replaces — one student's copy. Returns the sheet.
     */
    public function uploadCopy(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'student_detail_id' => 'required|integer',
            'pdf'               => 'required|file|mimes:pdf|max:' . self::COPY_MAX_KB,
            'remarks'           => 'nullable|string|max:500',
        ], [
            'pdf.required' => 'Pick the copy\'s PDF.',
            'pdf.mimes'    => 'The copy must be a PDF.',
            'pdf.max'      => 'The copy must be 5 MB or smaller.',
        ])) return $err;

        [$ctx, $err] = $this->copyContext($request, $user);
        if ($err) return $err;

        if (!$ctx['has_marks']) {
            return $this->error('Upload this subject\'s marks for the exam first.', 422);
        }

        $studentId = (int) $request->student_detail_id;
        if (!$ctx['students']->contains('id', $studentId)) {
            return $this->error('This student is not in the class.', 422);
        }

        $key = $request->file('pdf')->store('admin/exam-copies', 's3');
        Storage::disk('s3')->setVisibility($key, 'public');

        try {
            $old = DB::transaction(function () use ($ctx, $studentId, $key, $request, $user) {
                $copy = ExamCopy::firstOrNew($this->copyKey($ctx, $studentId));
                $old  = $copy->exists && $copy->pdf_path ? clone $copy : null;

                $copy->fill([
                    'organization_id' => $user->organization_id,
                    'uploaded_by'     => $user->id,
                    'pdf_path'        => $key,
                ]);
                if ($request->has('remarks')) {
                    $copy->remarks = (string) $request->input('remarks', '');
                }
                $copy->save();

                return $old;
            });
        } catch (\Throwable $e) {
            Storage::disk('s3')->delete($key);
            logger()->error('Teacher exam-copy upload: ' . $e->getMessage());
            return $this->error('The copy could not be saved. Please try again.', 500);
        }

        // The replaced PDF goes once the new one is saved.
        $old?->deletePdfFile();

        return $this->success($this->formatSheet($ctx, $user->organization_id), 'Exam copy uploaded.');
    }

    /**
     * POST /api/v1/teacher/exam-copies/sheet/remove
     *
     * Body: exam_id, standard_id, section_id, subject_id, student_detail_id
     *
     * Takes one student's copy down; their marks stay. Returns the sheet.
     */
    public function removeCopy(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'student_detail_id' => 'required|integer',
        ])) return $err;

        [$ctx, $err] = $this->copyContext($request, $user);
        if ($err) return $err;

        $copy = ExamCopy::where($this->copyKey($ctx, (int) $request->student_detail_id))->first();
        if ($copy && $copy->pdf_path) {
            $copy->deletePdfFile();
            $copy->pdf_path = null;
            $copy->save();
        }

        return $this->success($this->formatSheet($ctx, $user->organization_id), 'Exam copy removed.');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Checks an exam, class, section and subject the teacher teaches, and
     * gathers the roster (roll number order) and whether the exam's marks are
     * saved there — the admin panel's condition for uploading copies.
     *
     * @return array{0: ?array, 1: ?\Illuminate\Http\JsonResponse}
     */
    private function copyContext(Request $request, $user): array
    {
        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|integer',
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'subject_id'  => 'required|integer',
        ])) return [null, $err];

        $orgId = $user->organization_id;
        $exam  = Exam::where('organization_id', $orgId)->find((int) $request->exam_id);
        if (!$exam) return [null, $this->error('Exam not found.', 404)];

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return [null, $this->error('No teacher profile.', 404)];

        $standardId = (int) $request->standard_id;
        $sectionId  = (int) $request->section_id;
        $subjectId  = (int) $request->subject_id;

        if (!$this->teacherTeachesTriple($teacher->id, $orgId, $standardId, $sectionId, $subjectId)) {
            return [null, $this->error('You do not teach this class+subject.', 403)];
        }

        // Roll number order (2 before 10), then name; no roll number goes last.
        $students = StudentDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->get(['id', 'user_id', 'full_name', 'roll_no', 'admission_no'])
            ->sort(function ($a, $b) {
                $ra = trim((string) $a->roll_no);
                $rb = trim((string) $b->roll_no);
                if (($ra === '') !== ($rb === '')) return $ra === '' ? 1 : -1;
                return strnatcasecmp($ra, $rb)
                    ?: strnatcasecmp((string) ($a->full_name ?? $a->user?->name), (string) ($b->full_name ?? $b->user?->name));
            })
            ->values();

        $ctx = [
            'exam'        => $exam,
            'standard_id' => $standardId,
            'section_id'  => $sectionId,
            'subject_id'  => $subjectId,
            'students'    => $students,
        ];

        $ctx['has_marks'] = ExamCopy::where('organization_id', $orgId)
            ->where($this->copyKey($ctx))
            ->whereNotNull('marks_obtained')
            ->exists();

        return [$ctx, null];
    }

    /** The row a copy lives on — the admin panel's key. */
    private function copyKey(array $ctx, ?int $studentId = null): array
    {
        return array_filter([
            'exam_id'           => $ctx['exam']->id,
            'standard_id'       => $ctx['standard_id'],
            'section_id'        => $ctx['section_id'],
            'subject_id'        => $ctx['subject_id'],
            'student_detail_id' => $studentId,
        ], fn($v) => $v !== null);
    }

    private function formatSheet(array $ctx, int $orgId): array
    {
        $rows = ExamCopy::where('organization_id', $orgId)
            ->where($this->copyKey($ctx))
            ->whereIn('student_detail_id', $ctx['students']->pluck('id'))
            ->get(['id', 'student_detail_id', 'marks_obtained', 'max_marks', 'grade', 'is_absent', 'pdf_path', 'remarks', 'updated_at'])
            ->keyBy('student_detail_id');

        $students = $ctx['students']->map(function ($s) use ($rows) {
            $row    = $rows->get($s->id);
            $marked = $row && $row->marks_obtained !== null;
            $absent = $marked && (bool) $row->is_absent;
            return [
                'student_detail_id' => $s->id,
                'name'              => $s->full_name ?? $s->user?->name,
                'roll_no'           => $s->roll_no,
                'admission_no'      => $s->admission_no,
                'marked'            => $marked,
                'is_absent'         => $absent,
                'marks_obtained'    => $marked && !$absent ? (float) $row->marks_obtained : null,
                'max_marks'         => $marked && $row->max_marks !== null ? (float) $row->max_marks : null,
                'grade'             => $marked ? $row->grade : null,
                'has_copy'          => (bool) $row?->pdf_path,
                'pdf_url'           => $row?->pdfUrl(),
                'remarks'           => $row?->remarks ?: null,
                'uploaded_at'       => $row?->pdf_path ? $row->updated_at?->toIso8601String() : null,
            ];
        });

        return [
            'exam' => [
                'id'          => $ctx['exam']->id,
                'name'        => $ctx['exam']->exam_name,
                'total_marks' => $ctx['exam']->total_marks !== null ? (float) $ctx['exam']->total_marks : null,
            ],
            'standard_id' => $ctx['standard_id'],
            'section_id'  => $ctx['section_id'],
            'subject_id'  => $ctx['subject_id'],
            'has_marks'   => $ctx['has_marks'],
            'max_kb'      => self::COPY_MAX_KB,
            'students'    => $students->values()->all(),
        ];
    }

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
