<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamPaper;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * School-admin Exams module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/AddExam.php — exam CRUD, publish toggle, statistics,
 * the Exam→Class→Section→Subject syllabus mapping (grouped list, cascading
 * chapter options with exclusivity/transfer, save and group delete) and the
 * Exam Papers tab (a question paper PDF per exam, class, section and subject).
 * Org-scoped, role-gated to admin / sub-admin.
 */
class AdminExamController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    /** A paper filed under no class subject — the panel's "Other". */
    private const PAPER_SUBJECT_OTHER = 'other';

    private const EXAM_TYPES = [
        'quarterly'   => 'Quarterly',
        'half_yearly' => 'Half Yearly',
        'annual'      => 'Annual',
        'unit_test'   => 'Unit Test',
        'pre_board'   => 'Pre Board',
    ];

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

    private function academicYears(): array
    {
        $y = (int) date('Y');
        return [($y) . '-' . ($y + 1), ($y + 1) . '-' . ($y + 2)];
    }

    // ══════════════════════════ EXAMS ══════════════════════════

    private function shapeExam(Exam $e): array
    {
        $grading = (bool) ($e->uses_grading_system ?? false);
        return [
            'id'            => $e->id,
            'exam_name'     => $e->exam_name,
            'term'          => $e->term,
            'academic_year' => $e->academic_year,
            'start_date'    => optional($e->start_date)->format('Y-m-d'),
            'end_date'      => optional($e->end_date)->format('Y-m-d'),
            'description'   => $e->description,
            'exam_type'     => $e->exam_type,
            'exam_type_label' => self::EXAM_TYPES[$e->exam_type] ?? $e->exam_type,
            'total_marks'   => $grading ? null : $e->total_marks,
            'passing_marks' => $grading ? null : $e->passing_marks,
            'uses_grading_system' => $grading,
            'is_published'  => (bool) $e->is_published,
            'is_completed'  => $e->isCompleted(),
            'status'        => $e->currentStatus(),
            'status_label'  => $e->statusLabel(),
            'created_by'    => $e->createdBy->name ?? null,
            'created_at'    => optional($e->created_at)->toIso8601String(),
            // The panel's view shows when it was last changed.
            'updated_at'    => optional($e->updated_at)->toIso8601String(),
        ];
    }

    /** GET /admin/exams/{id} — one exam, as the panel's view shows it. */
    public function show($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $exam = Exam::with('createdBy')->where('organization_id', $user->organization_id)->find($id);
        if (!$exam) return $this->error('Exam not found.', 404);

        return $this->success($this->shapeExam($exam), 'Exam fetched.');
    }

    /** GET /admin/exams */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = Exam::with(['createdBy'])->where('organization_id', $orgId)
            ->when($request->filled('search'), fn ($q) => $q->where('exam_name', 'like', "%{$request->search}%"))
            ->when($request->filled('academic_year'), fn ($q) => $q->where('academic_year', $request->academic_year))
            ->when($request->filled('exam_type'), fn ($q) => $q->where('exam_type', $request->exam_type))
            ->when($request->filled('term'), fn ($q) => $q->where('term', $request->term))
            ->when($request->filled('status'), fn ($q) => $q->withStatus($request->status));

        $paginator = $query->orderByRaw('start_date IS NULL, start_date ASC')->orderBy('id')
            ->paginate((int) $request->input('per_page', 10));

        return $this->success([
            'exams'      => collect($paginator->items())->map(fn ($e) => $this->shapeExam($e)),
            'pagination' => $this->paginationMeta($paginator),
            'stats'      => $this->stats($orgId),
            'options'    => [
                'academic_years' => $this->academicYears(),
                'exam_types'     => self::EXAM_TYPES,
                'terms'          => ['Term-1', 'Term-2'],
            ],
        ], 'Exams fetched.');
    }

    private function stats(int $orgId): array
    {
        return [
            'total'     => Exam::where('organization_id', $orgId)->count(),
            'published' => Exam::where('organization_id', $orgId)->withStatus(Exam::STATUS_PUBLISHED)->count(),
            'upcoming'  => Exam::where('organization_id', $orgId)->withStatus(Exam::STATUS_UPCOMING)->count(),
            'active'    => Exam::where('organization_id', $orgId)->withStatus(Exam::STATUS_ACTIVE)->count(),
            'completed' => Exam::where('organization_id', $orgId)->withStatus(Exam::STATUS_COMPLETED)->count(),
            'syllabus_rows' => ExamSyllabusChapter::where('organization_id', $orgId)->count(),
        ];
    }

    private function examRules(Request $request): array
    {
        $rules = [
            'exam_name'     => 'required|string|max:255',
            'term'          => 'required|in:Term-1,Term-2',
            'academic_year' => 'required|string|max:9',
            // The panel's rule: both dates may be left out, and the end is only
            // held to the start when there is one.
            'start_date'    => 'nullable|date',
            'end_date'      => 'nullable|date' . ($request->filled('start_date') ? '|after_or_equal:start_date' : ''),
            'exam_type'     => 'required|string',
            'description'   => 'nullable|string',
            'is_published'  => 'nullable|boolean',
            'uses_grading_system' => 'nullable|boolean',
        ];
        if (!$request->boolean('uses_grading_system')) {
            $rules['total_marks']   = 'required|integer|min:1';
            $rules['passing_marks'] = 'required|integer|min:1|lt:total_marks';
        }
        return $rules;
    }

    private function examPayload(Request $request, int $orgId, int $userId, bool $isNew): array
    {
        $grading = $request->boolean('uses_grading_system');
        $data = [
            'organization_id' => $orgId,
            'exam_name'       => $request->exam_name,
            'term'            => $request->term,
            'academic_year'   => $request->academic_year,
            'start_date'      => $request->start_date ?: null,
            'end_date'        => $request->end_date ?: null,
            'description'     => $request->description,
            'is_published'    => $request->boolean('is_published'),
            'exam_type'       => $request->exam_type,
            'total_marks'     => $grading ? null : $request->total_marks,
            'passing_marks'   => $grading ? null : $request->passing_marks,
            'updated_by'      => $userId,
        ];
        if ($isNew) $data['created_by'] = $userId;
        if (Schema::hasColumn('exams', 'uses_grading_system')) {
            $data['uses_grading_system'] = $grading;
        }
        return $data;
    }

    /** POST /admin/exams */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, $this->examRules($request))) return $err;

        $exam = Exam::create($this->examPayload($request, $user->organization_id, $user->id, true));
        return $this->success($this->shapeExam($exam->load('createdBy')), 'Exam created successfully!');
    }

    /** PUT /admin/exams/{id} */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $exam = Exam::where('organization_id', $user->organization_id)->find($id);
        if (!$exam) return $this->error('Exam not found.', 404);
        if ($err = $this->validateWith($request, $this->examRules($request))) return $err;

        $exam->update($this->examPayload($request, $user->organization_id, $user->id, false));

        return $this->success($this->shapeExam($exam->fresh('createdBy')), 'Exam updated successfully!');
    }

    /** POST /admin/exams/{id}/toggle-publish */
    public function togglePublish($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $exam = Exam::where('organization_id', $user->organization_id)->find($id);
        if (!$exam) return $this->error('Exam not found.', 404);

        $exam->update(['is_published' => !$exam->is_published, 'updated_by' => $user->id]);
        return $this->success(['is_published' => (bool) $exam->is_published],
            'Exam ' . ($exam->is_published ? 'published' : 'unpublished') . ' successfully!');
    }

    /** DELETE /admin/exams/{id} — cascades its syllabus rows. */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $exam = Exam::where('organization_id', $user->organization_id)->find($id);
        if (!$exam) return $this->error('Exam not found.', 404);

        // Its teachers hear first — its syllabus says who they are.
        app(\App\Services\TeacherPushNotifier::class)->examDeleting($exam);
        ExamSyllabusChapter::where('exam_id', $exam->id)->delete();
        $exam->delete();
        return $this->success(null, 'Exam deleted successfully!');
    }

    // ══════════════════════════ SYLLABUS ══════════════════════════

    /** GET /admin/exams/syllabus?exam_id=&standard_id=&section_id=&subject_id= */
    public function syllabus(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        // Detail mode — full chain selected (section optional for legacy rows).
        if ($request->filled('exam_id') && $request->filled('standard_id') && $request->filled('subject_id')) {
            $chapterIds = ExamSyllabusChapter::where('organization_id', $orgId)
                ->where('exam_id', $request->exam_id)
                ->where('standard_id', $request->standard_id)
                ->where('subject_id', $request->subject_id)
                ->pluck('chapter_id')->toArray();

            $chapters = Chapter::with('topics:id,chapter_id,topic_name')
                ->whereIn('id', $chapterIds)->orderBy('order')
                ->get(['id', 'name', 'description', 'order'])
                ->map(fn ($c) => [
                    'id'          => $c->id,
                    'name'        => $c->name,
                    'description' => $c->description,
                    'topics'      => $c->topics->pluck('topic_name')->toArray(),
                ]);

            return $this->success([
                'mode'          => 'detail',
                'exam_name'     => Exam::where('id', $request->exam_id)->value('exam_name'),
                'standard_name' => Standard::where('id', $request->standard_id)->value('name'),
                'subject_name'  => Subject::where('id', $request->subject_id)->value('name'),
                'section_name'  => $request->filled('section_id') ? Section::where('id', $request->section_id)->value('name') : null,
                'chapters'      => $chapters,
            ], 'Syllabus detail fetched.');
        }

        // Grouped overview.
        $rows = ExamSyllabusChapter::with(['exam:id,exam_name', 'standard:id,name', 'subject:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($request->filled('exam_id'), fn ($q) => $q->where('exam_id', $request->exam_id))
            ->when($request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->get()
            ->groupBy(fn ($r) => $r->exam_id . '-' . $r->standard_id . '-' . ($r->section_id ?? 0) . '-' . $r->subject_id)
            ->map(fn ($g) => [
                'exam_id'       => $g->first()->exam_id,
                'exam_name'     => $g->first()->exam->exam_name ?? 'N/A',
                'standard_id'   => $g->first()->standard_id,
                'standard_name' => $g->first()->standard->name ?? 'N/A',
                'section_id'    => $g->first()->section_id,
                'section_name'  => $g->first()->section->name ?? null,
                'subject_id'    => $g->first()->subject_id,
                'subject_name'  => $g->first()->subject->name ?? 'N/A',
                'chapter_count' => $g->count(),
            ])->values();

        return $this->success(['mode' => 'list', 'groups' => $rows], 'Syllabus fetched.');
    }

    /**
     * GET /admin/exams/syllabus/options — cascading dropdown data.
     * Pass any subset of exam_id, standard_id, section_id, subject_id.
     */
    public function syllabusOptions(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $out = [
            'exams'     => Exam::where('organization_id', $orgId)
                ->orderByRaw('start_date IS NULL, start_date ASC')->orderBy('id')
                ->get(['id', 'exam_name', 'academic_year']),
            'standards' => Standard::where('organization_id', $orgId)->where('is_active', true)
                ->orderBy('order')->get(['id', 'name', 'code']),
            'sections'  => [],
            'subjects'  => [],
            'chapters'  => [],
        ];

        if ($request->filled('standard_id')) {
            $out['sections'] = Section::where('organization_id', $orgId)
                ->where('standard_id', $request->standard_id)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name']);
        }

        if ($request->filled('standard_id') && $request->filled('section_id')) {
            $subjectIds = DB::table('section_subjects')
                ->where('section_id', $request->section_id)
                ->where('standard_id', $request->standard_id)
                ->pluck('subject_id')->toArray();
            if (empty($subjectIds)) {
                $subjectIds = DB::table('standard_subjects')
                    ->where('standard_id', $request->standard_id)->pluck('subject_id')->toArray();
            }
            $out['subjects'] = Subject::where('organization_id', $orgId)
                ->whereIn('id', $subjectIds)->where('is_active', true)
                ->orderBy('name')->get(['id', 'name']);
        }

        if ($request->filled('standard_id') && $request->filled('subject_id')) {
            $chapterQuery = Chapter::with('topics:id,chapter_id,topic_name')
                ->where('organization_id', $orgId)
                ->where('standard_id', $request->standard_id)
                ->where('subject_id', $request->subject_id);
            if ($request->filled('section_id')) {
                $chapterQuery->where(fn ($q) => $q->where('section_id', $request->section_id)->orWhereNull('section_id'));
            }
            $chapters = $chapterQuery->orderBy('order')->get(['id', 'name', 'description', 'order']);

            $ownership = ExamSyllabusChapter::with('exam:id,exam_name')
                ->where('organization_id', $orgId)
                ->whereIn('chapter_id', $chapters->pluck('id'))
                ->get()->keyBy('chapter_id');

            $out['chapters'] = $chapters->map(fn ($c) => [
                'id'               => $c->id,
                'name'             => $c->name,
                'description'      => $c->description,
                'topics'           => $c->topics->pluck('topic_name')->toArray(),
                'owning_exam_id'   => $ownership[$c->id]->exam_id ?? null,
                'owning_exam_name' => $ownership[$c->id]->exam->exam_name ?? null,
            ]);

            // Pre-select chapters already owned by the chosen exam.
            if ($request->filled('exam_id')) {
                $out['selected_chapter_ids'] = ExamSyllabusChapter::where('organization_id', $orgId)
                    ->where('exam_id', $request->exam_id)
                    ->where('standard_id', $request->standard_id)
                    ->where('subject_id', $request->subject_id)
                    ->pluck('chapter_id')->toArray();
            }
        }

        return $this->success($out, 'Syllabus options fetched.');
    }

    /**
     * POST /admin/exams/syllabus  (exam_id, standard_id, section_id?, subject_id, chapter_ids[], edit?)
     *
     * With edit — the panel's Edit Exam Syllabus — every chapter may be left
     * unticked, which removes that syllabus; a new one needs at least one.
     */
    public function storeSyllabus(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $editing = $request->boolean('edit');
        if ($err = $this->validateWith($request, [
            'exam_id'      => 'required|integer|exists:exams,id',
            'standard_id'  => 'required|integer',
            'section_id'   => 'nullable|integer',
            'subject_id'   => 'required|integer',
            'chapter_ids'  => $editing ? 'present|array' : 'required|array|min:1',
        ], [
            'exam_id.required'     => 'Please select an exam.',
            'standard_id.required' => 'Please select a class.',
            'subject_id.required'  => 'Please select a subject.',
            'chapter_ids.required' => 'Please select at least one chapter.',
            'chapter_ids.min'      => 'Please select at least one chapter.',
        ])) return $err;

        $orgId = $user->organization_id;
        $chapterIds = array_map('intval', (array) $request->input('chapter_ids', []));
        // The syllabus as it was — its teachers hear when it changes (not when it is first set).
        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->examSyllabusSnapshot((int) $orgId, (int) $request->exam_id, (int) $request->standard_id,
            (int) $request->subject_id, $chapterIds);

        try {
            DB::transaction(function () use ($request, $orgId, $chapterIds) {
                // Chapter exclusivity: a chapter lives in only ONE syllabus row —
                // drop any row that mapped these chapters to a different bucket.
                if (!empty($chapterIds)) {
                    ExamSyllabusChapter::where('organization_id', $orgId)
                        ->whereIn('chapter_id', $chapterIds)
                        ->where(fn ($q) => $q->where('exam_id', '!=', $request->exam_id)
                            ->orWhere('standard_id', '!=', $request->standard_id)
                            ->orWhere('subject_id', '!=', $request->subject_id))
                        ->delete();
                }

                // Replace this exam/class/subject bucket entirely.
                ExamSyllabusChapter::where('organization_id', $orgId)
                    ->where('exam_id', $request->exam_id)
                    ->where('standard_id', $request->standard_id)
                    ->where('subject_id', $request->subject_id)
                    ->delete();

                foreach ($chapterIds as $chapterId) {
                    ExamSyllabusChapter::create([
                        'organization_id' => $orgId,
                        'exam_id'         => (int) $request->exam_id,
                        'standard_id'     => (int) $request->standard_id,
                        'subject_id'      => (int) $request->subject_id,
                        'section_id'      => $request->filled('section_id') ? (int) $request->section_id : null,
                        'chapter_id'      => $chapterId,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Error saving syllabus: ' . $e->getMessage(), 500);
        }
        $push->examSyllabusSaved($before);

        return $this->success(null, empty($chapterIds)
            ? 'Syllabus removed — every chapter was deselected.'
            : ($editing ? 'Syllabus updated successfully!' : 'Syllabus saved successfully!'));
    }

    /** DELETE /admin/exams/syllabus  (exam_id, standard_id, subject_id) */
    public function deleteSyllabus(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'exam_id'     => 'required|integer',
            'standard_id' => 'required|integer',
            'subject_id'  => 'required|integer',
        ])) return $err;

        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->examSyllabusSnapshot((int) $user->organization_id, (int) $request->exam_id,
            (int) $request->standard_id, (int) $request->subject_id);

        ExamSyllabusChapter::where('organization_id', $user->organization_id)
            ->where('exam_id', $request->exam_id)
            ->where('standard_id', $request->standard_id)
            ->where('subject_id', $request->subject_id)
            ->delete();

        $push->examSyllabusSaved($before);

        return $this->success(null, 'Syllabus removed successfully!');
    }

    // ══════════════════════════ EXAM PAPERS ══════════════════════════

    /**
     * Subjects taught in a class — narrowed to the section when one is given,
     * falling back to the class's own list, and every active subject when no
     * class is chosen (the panel's subjectsForClass).
     */
    private function subjectsForClass(int $orgId, $standardId, $sectionId = null): array
    {
        if (!$standardId) {
            return Subject::where('organization_id', $orgId)->where('is_active', true)
                ->orderBy('id')->get(['id', 'name'])->toArray();
        }

        $subjectIds = [];
        if ($sectionId) {
            $subjectIds = DB::table('section_subjects')
                ->where('section_id', $sectionId)->where('standard_id', $standardId)
                ->pluck('subject_id')->toArray();
        }
        if (empty($subjectIds)) {
            $subjectIds = DB::table('standard_subjects')
                ->where('standard_id', $standardId)->pluck('subject_id')->toArray();
        }

        return Subject::where('organization_id', $orgId)->whereIn('id', $subjectIds)->where('is_active', true)
            ->orderBy('id')->get(['id', 'name'])->toArray();
    }

    private function shapePaper(ExamPaper $p): array
    {
        return [
            'id'            => $p->id,
            'title'         => $p->title,
            'description'   => $p->description,
            'exam_id'       => $p->exam_id,
            'exam_name'     => $p->exam->exam_name ?? null,
            'academic_year' => $p->exam->academic_year ?? null,
            'standard_id'   => $p->standard_id,
            'standard_name' => $p->standard->name ?? null,
            'section_id'    => $p->section_id,
            'section_name'  => $p->section->name ?? null,
            // No subject: filed under "Other".
            'subject_id'    => $p->subject_id,
            'subject_name'  => $p->subjectLabel(),
            'has_file'      => (bool) $p->file_path,
            'created_at'    => optional($p->created_at)->toIso8601String(),
            'updated_at'    => optional($p->updated_at)->toIso8601String(),
        ];
    }

    private function findPaper(int $orgId, $id): ?ExamPaper
    {
        return ExamPaper::with(['exam:id,exam_name,academic_year', 'standard:id,name', 'section:id,name', 'subject:id,name'])
            ->where('organization_id', $orgId)->find($id);
    }

    /**
     * GET /admin/exams/papers/options?standard_id=&section_id=
     *
     * The pickers of the panel's Exam Papers: the school's exams (earliest
     * first), its active classes, a class's sections, and the subjects of the
     * class (and section) — every active subject when no class is given.
     */
    public function paperOptions(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        return $this->success([
            'exams'     => Exam::where('organization_id', $orgId)
                ->orderByRaw('start_date IS NULL, start_date ASC')->orderBy('id')
                ->get(['id', 'exam_name', 'academic_year']),
            'standards' => Standard::where('organization_id', $orgId)->where('is_active', true)
                ->inClassOrder()->get(['id', 'name', 'code']),
            'sections'  => $request->filled('standard_id')
                ? Section::where('organization_id', $orgId)->where('standard_id', $request->standard_id)
                    ->where('is_active', true)->orderBy('id')->get(['id', 'name'])
                : [],
            'subjects'  => $this->subjectsForClass($orgId, $request->input('standard_id') ?: null, $request->input('section_id') ?: null),
        ], 'Exam paper options fetched.');
    }

    /**
     * GET /admin/exams/papers?exam_id=&standard_id=&section_id=&subject_id=(id|other)&page=&per_page=
     *
     * The school's question papers, newest first, narrowed as the panel's
     * filters narrow them. With a page, per_page (10) to a page; without, the
     * latest 200.
     */
    public function papers(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $q = ExamPaper::with(['exam:id,exam_name,academic_year', 'standard:id,name', 'section:id,name', 'subject:id,name'])
            ->where('organization_id', $user->organization_id)
            ->when($request->filled('exam_id'), fn ($q) => $q->where('exam_id', $request->exam_id))
            ->when($request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->filled('subject_id'), fn ($q) => $request->subject_id === self::PAPER_SUBJECT_OTHER
                ? $q->whereNull('subject_id')
                : $q->where('subject_id', $request->subject_id))
            ->orderByDesc('created_at');

        $pagination = null;
        if ($request->filled('page')) {
            $page       = $q->paginate((int) $request->input('per_page', 10));
            $items      = collect($page->items())->map(fn ($p) => $this->shapePaper($p))->values();
            $pagination = $this->paginationMeta($page);
        } else {
            $items = $q->limit(200)->get()->map(fn ($p) => $this->shapePaper($p))->values();
        }

        return $this->success([
            'papers'     => $items,
            'pagination' => $pagination,
            'total'      => ExamPaper::where('organization_id', $user->organization_id)->count(),
        ], 'Exam papers fetched.');
    }

    /** POST /admin/exams/papers — multipart: exam_id, standard_id, section_id?, subject_id (id|other), title, description?, file (PDF, 1 MB). */
    public function storePaper(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->savePaper($request, $user, null);
    }

    /** POST /admin/exams/papers/{id} — multipart, as storePaper; the file may be left out to keep the one it has. */
    public function updatePaper(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $paper = $this->findPaper($user->organization_id, $id);
        if (!$paper) return $this->error('Exam paper not found.', 404);

        return $this->savePaper($request, $user, $paper);
    }

    private function savePaper(Request $request, $user, ?ExamPaper $paper)
    {
        $orgId  = $user->organization_id;
        $isEdit = (bool) $paper;
        $other  = $request->input('subject_id') === self::PAPER_SUBJECT_OTHER;

        // The panel's rules, with the exam, class, section and subject held to the school's own.
        if ($err = $this->validateWith($request, [
            'exam_id'     => ['required', Rule::exists('exams', 'id')->where('organization_id', $orgId)],
            'standard_id' => ['required', Rule::exists('standards', 'id')->where('organization_id', $orgId)],
            'section_id'  => ['nullable', Rule::exists('sections', 'id')->where('organization_id', $orgId)],
            // Either one of the class's subjects, or the explicit "Other" bucket.
            'subject_id'  => $other
                ? ['required', 'string']
                : ['required', Rule::exists('subjects', 'id')->where('organization_id', $orgId)],
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:3000',
            'file'        => ($isEdit ? 'nullable' : 'required') . '|file|mimes:pdf|max:1024', // 1 MB
        ], [
            'exam_id.required'     => 'Please select an exam.',
            'exam_id.exists'       => 'Please select a valid exam.',
            'standard_id.required' => 'Please select a class.',
            'standard_id.exists'   => 'Please select a valid class.',
            'section_id.exists'    => 'Please select a valid section.',
            'subject_id.required'  => 'Please select a subject (or choose Other).',
            'subject_id.exists'    => 'Please select a valid subject.',
            'title.required'       => 'Please enter a title.',
            'description.max'      => 'Description may not be longer than 3000 characters.',
            'file.required'        => 'Please choose a PDF file.',
            'file.mimes'           => 'The paper must be a PDF file.',
            'file.max'             => 'The PDF must be 1 MB or smaller.',
        ])) return $err;

        $data = [
            'exam_id'     => (int) $request->exam_id,
            'standard_id' => (int) $request->standard_id,
            'section_id'  => $request->filled('section_id') ? (int) $request->section_id : null,
            'subject_id'  => $other ? null : (int) $request->subject_id,
            'title'       => $request->title,
            'description' => $request->filled('description') ? $request->description : null,
        ];

        try {
            if ($isEdit) {
                if ($request->hasFile('file')) {
                    if ($paper->file_path) Storage::disk('s3')->delete($paper->file_path);
                    $data['file_path']   = $request->file('file')->store('admin/exam-papers/' . $orgId, 's3');
                    $data['uploaded_by'] = $user->id;
                }
                $paper->update($data);
                $message = 'Exam paper updated successfully.';
            } else {
                $data['organization_id'] = $orgId;
                $data['uploaded_by']     = $user->id;
                $data['file_path']       = $request->file('file')->store('admin/exam-papers/' . $orgId, 's3');
                $paper   = ExamPaper::create($data);
                $message = 'Exam paper uploaded successfully.';
            }
        } catch (\Throwable $e) {
            logger()->error('ExamPaper save error: ' . $e->getMessage());
            return $this->error($e->getMessage(), 500);
        }

        return $this->success($this->shapePaper($this->findPaper($orgId, $paper->id)), $message);
    }

    /** DELETE /admin/exams/papers/{id} — the paper and its PDF. */
    public function destroyPaper($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $paper = ExamPaper::where('organization_id', $user->organization_id)->find($id);
        if (!$paper) return $this->error('Exam paper not found.', 404);

        if ($paper->file_path) Storage::disk('s3')->delete($paper->file_path);
        $paper->delete();

        return $this->success(null, 'Exam paper deleted.');
    }

    /** GET /admin/exams/papers/{id}/file — a short-lived link that downloads the PDF, as the panel's Download. */
    public function paperFile($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $paper = ExamPaper::where('organization_id', $user->organization_id)->find($id);
        if (!$paper || !$paper->file_path) return $this->error('Paper file not found.', 404);

        if (!Storage::disk('s3')->exists($paper->file_path)) {
            return $this->error('File missing on storage. Please re-upload this paper.', 404);
        }

        $filename = trim(str_replace(['"', '\\', '/'], ' ', $paper->title ?: 'exam-paper')) . '.pdf';
        $url = Storage::disk('s3')->temporaryUrl($paper->file_path, now()->addMinutes(30), [
            'ResponseContentDisposition' => 'attachment; filename="' . $filename . '"',
            'ResponseContentType'        => 'application/pdf',
        ]);

        return $this->success(['url' => $url, 'file_name' => $filename], 'Exam paper link ready.');
    }
}
