<?php

namespace App\Http\Controllers\v1\Teacher;

use App\Http\Controllers\v1\ApiController;
use App\Models\Admin\Exam;
use App\Models\Admin\ExamCopy;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Teacher → Marks management.
 *
 * Scope: the authenticated teacher can only see / write marks for the
 * (class, section, subject) triples they actually teach, derived from
 * `teacher_time_tables`.
 *
 * Endpoints (all under /api/v1/teacher/marks):
 *
 *   GET  /classes-subjects   → dropdown source for "select class+subject"
 *   GET  /students           → list students in a chosen (class, section)
 *   GET  /                   → list marks rows the teacher can see
 *   GET  /{id}               → single marks row
 *   POST /                   → create marks for a student in a (class, section, subject)
 *   PUT  /{id}               → update marks
 *   DELETE /{id}             → delete marks
 */
class MarksController extends ApiController
{
    /**
     * GET /api/v1/teacher/classes-subjects
     *
     * Returns the list of (class, section, subject) triples the teacher
     * teaches — exactly the data the front-end dropdown needs.
     *
     * Each row carries `label` (e.g. "Class 8 · A · Mathematics") for direct
     * use as the dropdown option text.
     */
    public function classesSubjects()
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) {
            return $this->success([], 'No teacher profile.');
        }

        $rows = TeacherTimeTable::with(['standard:id,name', 'section:id,name', 'subject:id,name'])
            ->where('teacher_detail_id', $teacher->id)
            ->where('organization_id', $user->organization_id)
            ->get(['id', 'standard_id', 'section_id', 'subject_id'])
            // dedupe — the timetable repeats the same (class, section, subject)
            // across days of the week; the dropdown only needs each once.
            ->unique(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id)
            ->filter(fn($r) => $r->standard && $r->subject)
            ->values()
            ->map(fn($r) => [
                'standard_id'   => $r->standard_id,
                'standard_name' => $r->standard?->name,
                'section_id'    => $r->section_id,
                'section_name'  => $r->section?->name,
                'subject_id'    => $r->subject_id,
                'subject_name'  => $r->subject?->name,
                'label'         => trim(
                    ($r->standard?->name ?? '—')
                    . ($r->section?->name ? ' · ' . $r->section->name : '')
                    . ' · '
                    . ($r->subject?->name ?? '—')
                ),
            ]);

        return $this->success($rows->values()->all(), 'Classes & subjects fetched successfully.');
    }

    /**
     * GET /api/v1/teacher/marks/students?standard_id=X&section_id=Y
     *
     * Students roster for the chosen (class, section). Only allowed if the
     * teacher teaches at least one subject in that class+section.
     */
    public function students(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer|exists:standards,id',
            'section_id'  => 'required|integer|exists:sections,id',
        ])) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->success([], 'No teacher profile.');

        if (!$this->teacherTeachesClass($teacher->id, $user->organization_id, (int) $request->standard_id, (int) $request->section_id)) {
            return $this->error('You do not teach this class+section.', 403);
        }

        $students = StudentDetail::with('user:id,name,email')
            ->where('organization_id', $user->organization_id)
            ->where('standard_id', (int) $request->standard_id)
            ->where('section_id', (int) $request->section_id)
            ->get(['id', 'user_id', 'full_name', 'roll_no', 'admission_no']);

        return $this->success(
            $students->map(fn($s) => [
                'student_detail_id' => $s->id,
                'name'              => $s->full_name ?? $s->user?->name,
                'roll_no'           => $s->roll_no,
                'admission_no'      => $s->admission_no,
                'email'             => $s->user?->email,
            ])->values()->all(),
            'Students fetched successfully.'
        );
    }

    /**
     * GET /api/v1/teacher/marks
     *
     * Optional filters: exam_id, standard_id, section_id, subject_id,
     * student_detail_id, search.
     *
     * Auto-scoped to the (class, section, subject) triples the teacher
     * teaches.
     */
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
            ->whereIn(\DB::raw('CONCAT(standard_id, "-", section_id, "-", subject_id)'), $triples);

        if ($request->filled('exam_id'))           $query->where('exam_id', (int) $request->exam_id);
        if ($request->filled('standard_id'))       $query->where('standard_id', (int) $request->standard_id);
        if ($request->filled('section_id'))        $query->where('section_id', (int) $request->section_id);
        if ($request->filled('subject_id'))        $query->where('subject_id', (int) $request->subject_id);
        if ($request->filled('student_detail_id')) $query->where('student_detail_id', (int) $request->student_detail_id);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('studentDetail', fn($q) => $q->where('full_name', 'like', "%$s%")->orWhere('roll_no', 'like', "%$s%"));
        }

        $perPage = (int) $request->get('per_page', 20);
        $paginator = $query->latest()->paginate($perPage);

        $items = $paginator->getCollection()->map(fn($m) => $this->formatMark($m));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Marks fetched successfully.');
    }

    /**
     * GET /api/v1/teacher/marks/{id}
     */
    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $mark = ExamCopy::with([
            'exam:id,exam_name',
            'standard:id,name',
            'section:id,name',
            'subject:id,name',
            'studentDetail:id,user_id,full_name,roll_no',
            'studentDetail.user:id,name',
        ])
            ->where('organization_id', $user->organization_id)
            ->find($id);

        if (!$mark) return $this->error('Marks record not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $mark->standard_id, $mark->section_id, $mark->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        return $this->success($this->formatMark($mark), 'Marks fetched successfully.');
    }

    /**
     * POST /api/v1/teacher/marks
     *
     * Body:
     *   exam_id, student_detail_id, standard_id, section_id, subject_id,
     *   marks_obtained, max_marks, grade, is_absent?, is_recheck?, remarks?
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
            'marks_obtained'    => 'required|numeric|min:0',
            'max_marks'         => 'required|numeric|min:1',
            'grade'             => 'nullable|string|max:10',
            'is_absent'         => 'nullable|boolean',
            'is_recheck'        => 'nullable|boolean',
            'remarks'           => 'nullable|string|max:500',
        ])) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, (int) $request->standard_id, (int) $request->section_id, (int) $request->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        // Already saved?
        $exists = ExamCopy::where('organization_id', $user->organization_id)
            ->where('exam_id', (int) $request->exam_id)
            ->where('student_detail_id', (int) $request->student_detail_id)
            ->where('subject_id', (int) $request->subject_id)
            ->first();

        if ($exists) {
            return $this->error('Marks already uploaded for this student + exam + subject. Use update.', 409);
        }

        $percentage = ((float) $request->marks_obtained / (float) $request->max_marks) * 100;

        $mark = ExamCopy::create([
            'organization_id'   => $user->organization_id,
            'user_id'           => $user->id,
            'uploaded_by'       => $user->id,
            'teacher_detail_id' => $teacher->id,
            'student_detail_id' => (int) $request->student_detail_id,
            'standard_id'       => (int) $request->standard_id,
            'section_id'        => (int) $request->section_id,
            'subject_id'        => (int) $request->subject_id,
            'exam_id'           => (int) $request->exam_id,
            'marks_obtained'    => (float) $request->marks_obtained,
            'max_marks'         => (float) $request->max_marks,
            'percentage'        => round($percentage, 2),
            'grade'             => $request->grade,
            'remarks'           => $request->remarks,
            'is_absent'         => (bool) $request->boolean('is_absent'),
            'is_recheck'        => (bool) $request->boolean('is_recheck'),
        ]);

        $mark->load(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'subject:id,name', 'studentDetail:id,user_id,full_name,roll_no', 'studentDetail.user:id,name']);

        return $this->success($this->formatMark($mark), 'Marks uploaded successfully.', 201);
    }

    /**
     * PUT /api/v1/teacher/marks/{id}
     */
    public function update(Request $request, int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $mark = ExamCopy::where('organization_id', $user->organization_id)->find($id);
        if (!$mark) return $this->error('Marks record not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $mark->standard_id, $mark->section_id, $mark->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        if ($err = $this->validateWith($request, [
            'marks_obtained' => 'sometimes|numeric|min:0',
            'max_marks'      => 'sometimes|numeric|min:1',
            'grade'          => 'sometimes|nullable|string|max:10',
            'is_absent'      => 'sometimes|boolean',
            'is_recheck'     => 'sometimes|boolean',
            'remarks'        => 'sometimes|nullable|string|max:500',
        ])) return $err;

        $marksObtained = $request->has('marks_obtained') ? (float) $request->marks_obtained : (float) $mark->marks_obtained;
        $maxMarks      = $request->has('max_marks') ? (float) $request->max_marks : (float) $mark->max_marks;
        $percentage    = $maxMarks > 0 ? ($marksObtained / $maxMarks) * 100 : 0;

        $mark->fill([
            'marks_obtained' => $marksObtained,
            'max_marks'      => $maxMarks,
            'percentage'     => round($percentage, 2),
            'grade'          => $request->has('grade')     ? $request->grade     : $mark->grade,
            'remarks'        => $request->has('remarks')   ? $request->remarks   : $mark->remarks,
            'is_absent'      => $request->has('is_absent') ? $request->boolean('is_absent') : $mark->is_absent,
            'is_recheck'     => $request->has('is_recheck')? $request->boolean('is_recheck'): $mark->is_recheck,
        ])->save();

        $mark->load(['exam:id,exam_name', 'standard:id,name', 'section:id,name', 'subject:id,name', 'studentDetail:id,user_id,full_name,roll_no', 'studentDetail.user:id,name']);

        return $this->success($this->formatMark($mark), 'Marks updated successfully.');
    }

    /**
     * DELETE /api/v1/teacher/marks/{id}
     */
    public function destroy(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->error('No teacher profile.', 404);

        $mark = ExamCopy::where('organization_id', $user->organization_id)->find($id);
        if (!$mark) return $this->error('Marks record not found.', 404);

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $mark->standard_id, $mark->section_id, $mark->subject_id)) {
            return $this->error('You do not teach this class+subject.', 403);
        }

        // Marks and exam-copy PDFs share the same row. If a PDF is still
        // attached, only clear the marks fields so the copy survives;
        // otherwise remove the row entirely.
        if (!empty($mark->pdf_path)) {
            $mark->fill([
                'marks_obtained' => null,
                'max_marks'      => null,
                'percentage'     => null,
                'grade'          => null,
                'remarks'        => null,
                'is_absent'      => false,
                'is_recheck'     => false,
            ])->save();

            return $this->success(null, 'Marks deleted successfully.');
        }

        \App\Models\Admin\ExamSubjectMark::where('exam_copy_id', $mark->id)->delete();
        $mark->delete();

        return $this->success(null, 'Marks deleted successfully.');
    }

    /**
     * GET /api/v1/teacher/marks/classes?exam_id=X
     *
     * The app's Upload Marks, step two: every (class, section, subject) the
     * teacher teaches, with how far the chosen exam's marks have got there —
     * `saved` counts the students with marks or marked absent.
     */
    public function examClasses(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'exam_id' => 'required|integer',
        ])) return $err;

        $exam = $this->findExam((int) $request->exam_id, $user->organization_id);
        if (!$exam) return $this->error('Exam not found.', 404);

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return $this->success([], 'No teacher profile.');

        $rows = TeacherTimeTable::with(['standard:id,name,order', 'section:id,name', 'subject:id,name'])
            ->where('teacher_detail_id', $teacher->id)
            ->where('organization_id', $user->organization_id)
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

        $students = StudentDetail::where('organization_id', $user->organization_id)
            ->whereIn('standard_id', $rows->pluck('standard_id')->unique())
            ->whereIn('section_id', $rows->pluck('section_id')->unique())
            ->selectRaw('standard_id, section_id, COUNT(*) as total')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->mapWithKeys(fn($r) => [$r->standard_id . '-' . $r->section_id => (int) $r->total]);

        $marks = ExamCopy::where('organization_id', $user->organization_id)
            ->where('exam_id', $exam->id)
            ->where(fn($q) => $q->whereNotNull('marks_obtained')->orWhere('is_absent', true))
            ->selectRaw('standard_id, section_id, subject_id, COUNT(*) as saved, SUM(CASE WHEN is_absent = 1 THEN 1 ELSE 0 END) as absent')
            ->groupBy('standard_id', 'section_id', 'subject_id')
            ->get()
            ->keyBy(fn($r) => $r->standard_id . '-' . $r->section_id . '-' . $r->subject_id);

        $items = $rows->map(function ($r) use ($students, $marks) {
            $saved = $marks->get($r->standard_id . '-' . $r->section_id . '-' . $r->subject_id);
            return [
                'standard_id'   => $r->standard_id,
                'standard_name' => $r->standard->name,
                'section_id'    => $r->section_id,
                'section_name'  => $r->section->name,
                'subject_id'    => $r->subject_id,
                'subject_name'  => $r->subject->name,
                'subject_image' => $r->subject->iconUrl(),
                'students'      => (int) ($students[$r->standard_id . '-' . $r->section_id] ?? 0),
                'saved'         => (int) ($saved->saved ?? 0),
                'absent'        => (int) ($saved->absent ?? 0),
            ];
        });

        return $this->success($items->all(), 'Classes fetched successfully.');
    }

    /**
     * GET /api/v1/teacher/marks/sheet?exam_id=&standard_id=&section_id=&subject_id=
     *
     * One exam's marks for one class, section and subject: every student of
     * the section with what is saved for them, as the web's Upload Marks
     * panel loads it. An absent student carries no marks.
     */
    public function sheet(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        [$ctx, $err] = $this->sheetContext($request, $user);
        if ($err) return $err;

        return $this->success($this->formatSheet($ctx, $user->organization_id), 'Marks fetched successfully.');
    }

    /**
     * POST /api/v1/teacher/marks/sheet
     *
     * Body: exam_id, standard_id, section_id, subject_id,
     *       marks: [{ student_detail_id, marks_obtained|null, is_absent? }]
     *
     * The web's Upload Marks save: every student of the section gets a row.
     * A typed mark is kept (held between 0 and the exam's total marks) and
     * graded; a student left blank, or flagged absent, is saved as absent
     * (0 marks, grade AB). Nothing is saved unless at least one student has
     * marks or is flagged, so an untouched sheet cannot mark a class absent.
     */
    public function saveSheet(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;
        if ($err = $this->requireRole('teacher')) return $err;

        if ($err = $this->validateWith($request, [
            'marks'                     => 'required|array',
            'marks.*.student_detail_id' => 'required|integer',
            'marks.*.marks_obtained'    => 'nullable|numeric|min:0',
            'marks.*.is_absent'         => 'nullable|boolean',
        ])) return $err;

        [$ctx, $err] = $this->sheetContext($request, $user);
        if ($err) return $err;

        $input = collect($request->input('marks'))->keyBy(fn($m) => (int) $m['student_detail_id']);

        $hasMarks = fn($m) => isset($m['marks_obtained']) && $m['marks_obtained'] !== '' && is_numeric($m['marks_obtained']);
        $flagged  = fn($m) => filter_var($m['is_absent'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $anyInput = $ctx['students']->contains(function ($s) use ($input, $hasMarks, $flagged) {
            $m = $input->get($s->id);
            return $m && ($hasMarks($m) || $flagged($m));
        });
        if (!$anyInput) {
            return $this->error('Enter marks for at least one student — the rest will be marked absent.', 422);
        }

        $max     = $ctx['max_marks'];
        $grading = app(GradingService::class);
        $saved   = 0;
        $absent  = 0;

        DB::transaction(function () use ($ctx, $input, $hasMarks, $flagged, $max, $grading, $user, &$saved, &$absent) {
            foreach ($ctx['students'] as $student) {
                $m = $input->get($student->id, []);

                $row = ExamCopy::firstOrNew([
                    'exam_id'           => $ctx['exam']->id,
                    'standard_id'       => $ctx['standard_id'],
                    'section_id'        => $ctx['section_id'],
                    'subject_id'        => $ctx['subject_id'],
                    'student_detail_id' => $student->id,
                ]);

                if (!$row->exists) {
                    $row->fill([
                        'user_id'           => $user->id,
                        'uploaded_by'       => $user->id,
                        'teacher_detail_id' => $ctx['teacher_id'],
                    ]);
                }

                if ($hasMarks($m) && !$flagged($m)) {
                    $obt = max(0, min($max, (float) $m['marks_obtained']));
                    $pct = $max > 0 ? ($obt / $max) * 100 : 0;
                    $row->fill([
                        'organization_id' => $user->organization_id,
                        'marks_obtained'  => $obt,
                        'max_marks'       => $max,
                        'percentage'      => round($pct, 2),
                        'grade'           => $grading->gradeLetter($pct) ?? 'F',
                        'is_absent'       => false,
                    ]);
                    $saved++;
                } else {
                    $row->fill([
                        'organization_id' => $user->organization_id,
                        'marks_obtained'  => 0,
                        'max_marks'       => $max,
                        'percentage'      => 0,
                        'grade'           => 'AB',
                        'is_absent'       => true,
                    ]);
                    $absent++;
                }

                $row->save();
            }
        });

        $message = "Marks for {$saved} student(s) saved.";
        if ($absent > 0) {
            $message .= " {$absent} student(s) marked absent.";
        }

        return $this->success($this->formatSheet($ctx, $user->organization_id), $message);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function findExam(int $examId, int $orgId): ?Exam
    {
        return Exam::where('organization_id', $orgId)->find($examId);
    }

    /**
     * Checks a sheet request (exam, class, section, subject the teacher
     * teaches) and gathers what reading or saving it needs. The total marks
     * are the exam's, 100 when it has none — as on the web.
     *
     * @return array{0: ?array, 1: ?\Illuminate\Http\JsonResponse}
     */
    private function sheetContext(Request $request, $user): array
    {
        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|integer',
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'subject_id'  => 'required|integer',
        ])) return [null, $err];

        $exam = $this->findExam((int) $request->exam_id, $user->organization_id);
        if (!$exam) return [null, $this->error('Exam not found.', 404)];

        $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
        if (!$teacher) return [null, $this->error('No teacher profile.', 404)];

        $standardId = (int) $request->standard_id;
        $sectionId  = (int) $request->section_id;
        $subjectId  = (int) $request->subject_id;

        if (!$this->teacherTeachesTriple($teacher->id, $user->organization_id, $standardId, $sectionId, $subjectId)) {
            return [null, $this->error('You do not teach this class+subject.', 403)];
        }

        // Roll number order (2 before 10), then name; no roll number goes last.
        $students = StudentDetail::with('user:id,name')
            ->where('organization_id', $user->organization_id)
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

        return [[
            'exam'        => $exam,
            'teacher_id'  => $teacher->id,
            'standard_id' => $standardId,
            'section_id'  => $sectionId,
            'subject_id'  => $subjectId,
            'max_marks'   => max(1, (int) ($exam->total_marks ?: 100)),
            'students'    => $students,
        ], null];
    }

    private function formatSheet(array $ctx, int $orgId): array
    {
        $rows = ExamCopy::where('organization_id', $orgId)
            ->where('exam_id', $ctx['exam']->id)
            ->where('standard_id', $ctx['standard_id'])
            ->where('section_id', $ctx['section_id'])
            ->where('subject_id', $ctx['subject_id'])
            ->whereIn('student_detail_id', $ctx['students']->pluck('id'))
            ->get(['id', 'student_detail_id', 'marks_obtained', 'max_marks', 'percentage', 'grade', 'is_absent'])
            ->keyBy('student_detail_id');

        $students = $ctx['students']->map(function ($s) use ($rows) {
            $row    = $rows->get($s->id);
            $absent = (bool) ($row?->is_absent);
            // A row with neither marks nor absence only holds an exam-copy PDF.
            $saved  = $row && ($absent || $row->marks_obtained !== null);
            return [
                'student_detail_id' => $s->id,
                'name'              => $s->full_name ?? $s->user?->name,
                'roll_no'           => $s->roll_no,
                'admission_no'      => $s->admission_no,
                'mark_id'           => $saved ? $row->id : null,
                'saved'             => $saved,
                'is_absent'         => $saved && $absent,
                'marks_obtained'    => $saved && !$absent ? (float) $row->marks_obtained : null,
                'max_marks'         => $saved && $row->max_marks !== null ? (float) $row->max_marks : null,
                'percentage'        => $saved && !$absent ? (float) $row->percentage : null,
                'grade'             => $saved ? $row->grade : null,
            ];
        });

        return [
            'exam' => [
                'id'            => $ctx['exam']->id,
                'name'          => $ctx['exam']->exam_name,
                'total_marks'   => $ctx['max_marks'],
                'passing_marks' => $ctx['exam']->passing_marks !== null ? (float) $ctx['exam']->passing_marks : null,
            ],
            'standard_id' => $ctx['standard_id'],
            'section_id'  => $ctx['section_id'],
            'subject_id'  => $ctx['subject_id'],
            'uploaded'    => $students->contains('saved', true),
            'students'    => $students->values()->all(),
        ];
    }

    /**
     * Returns the set of "standard_id-section_id-subject_id" triples the
     * teacher teaches. Used to whereIn-scope DB queries.
     */
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

    private function teacherTeachesClass(int $teacherId, int $orgId, int $standardId, int $sectionId): bool
    {
        return TeacherTimeTable::where('teacher_detail_id', $teacherId)
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->exists();
    }

    private function formatMark(ExamCopy $m): array
    {
        return [
            'id'              => $m->id,
            'exam'            => $m->exam     ? ['id' => $m->exam->id,     'name' => $m->exam->exam_name] : null,
            'standard'        => $m->standard ? ['id' => $m->standard->id, 'name' => $m->standard->name]  : null,
            'section'         => $m->section  ? ['id' => $m->section->id,  'name' => $m->section->name]   : null,
            'subject'         => $m->subject  ? ['id' => $m->subject->id,  'name' => $m->subject->name]   : null,
            'student'         => $m->studentDetail ? [
                'id'           => $m->studentDetail->id,
                'name'         => $m->studentDetail->full_name ?? $m->studentDetail->user?->name,
                'roll_no'      => $m->studentDetail->roll_no,
            ] : null,
            'marks_obtained'  => (float) $m->marks_obtained,
            'max_marks'       => (float) $m->max_marks,
            'percentage'      => (float) $m->percentage,
            'grade'           => $m->grade,
            'is_absent'       => (bool) $m->is_absent,
            'is_recheck'      => (bool) $m->is_recheck,
            'remarks'         => $m->remarks,
            'updated_at'      => $m->updated_at,
        ];
    }
}
