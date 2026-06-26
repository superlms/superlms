<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Exam;
use App\Models\Admin\ExamSyllabusChapter;
use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * School-admin Exams module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/AddExam.php — exam CRUD, publish toggle, statistics
 * and the Exam→Class→Section→Subject syllabus mapping (grouped list, cascading
 * chapter options with exclusivity/transfer, save and group delete). Org-scoped,
 * role-gated to admin / sub-admin.
 */
class AdminExamController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

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
            'created_by'    => $e->createdBy->name ?? null,
            'created_at'    => optional($e->created_at)->toIso8601String(),
        ];
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
            ->when($request->filled('status'), function ($q) use ($request) {
                match ($request->status) {
                    'published' => $q->where('is_published', true),
                    'draft'     => $q->where('is_published', false),
                    'active'    => $q->where('start_date', '<=', now())->where('end_date', '>=', now()),
                    'upcoming'  => $q->where('start_date', '>', now()),
                    'completed' => $q->where('end_date', '<', now()),
                    default     => null,
                };
            });

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
            'published' => Exam::where('organization_id', $orgId)->where('is_published', true)->count(),
            'upcoming'  => Exam::where('organization_id', $orgId)->where('start_date', '>', now())->count(),
            'active'    => Exam::where('organization_id', $orgId)
                ->where('start_date', '<=', now())->where('end_date', '>=', now())->count(),
            'syllabus_rows' => ExamSyllabusChapter::where('organization_id', $orgId)->count(),
        ];
    }

    private function examRules(Request $request): array
    {
        $rules = [
            'exam_name'     => 'required|string|max:255',
            'term'          => 'required|in:Term-1,Term-2',
            'academic_year' => 'required|string|max:9',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after_or_equal:start_date',
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
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
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

    /** POST /admin/exams/syllabus  (exam_id, standard_id, section_id?, subject_id, chapter_ids[]) */
    public function storeSyllabus(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'exam_id'      => 'required|integer|exists:exams,id',
            'standard_id'  => 'required|integer',
            'section_id'   => 'nullable|integer',
            'subject_id'   => 'required|integer',
            'chapter_ids'  => 'required|array|min:1',
        ], [
            'chapter_ids.required' => 'Please select at least one chapter.',
            'chapter_ids.min'      => 'Please select at least one chapter.',
        ])) return $err;

        $orgId = $user->organization_id;

        try {
            DB::transaction(function () use ($request, $orgId) {
                $chapterIds = array_map('intval', $request->chapter_ids);

                // Chapter exclusivity: a chapter lives in only ONE syllabus row —
                // drop any row that mapped these chapters to a different bucket.
                ExamSyllabusChapter::where('organization_id', $orgId)
                    ->whereIn('chapter_id', $chapterIds)
                    ->where(fn ($q) => $q->where('exam_id', '!=', $request->exam_id)
                        ->orWhere('standard_id', '!=', $request->standard_id)
                        ->orWhere('subject_id', '!=', $request->subject_id))
                    ->delete();

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

        return $this->success(null, 'Syllabus saved successfully!');
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

        ExamSyllabusChapter::where('organization_id', $user->organization_id)
            ->where('exam_id', $request->exam_id)
            ->where('standard_id', $request->standard_id)
            ->where('subject_id', $request->subject_id)
            ->delete();

        return $this->success(null, 'Syllabus removed successfully!');
    }
}
