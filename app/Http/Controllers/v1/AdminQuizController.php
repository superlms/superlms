<?php

namespace App\Http\Controllers\v1;

use App\Models\Mcq\McqOption;
use App\Models\Mcq\McqQuestion;
use App\Models\Mcq\McqUserAnswer;
use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * School-admin Quiz (MCQ) module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Quiz.php — list the chapter→topic tree with MCQ
 * counts, list MCQs for a chapter/topic, bulk create, bulk update and bulk
 * delete questions (with their options). Org-scoped, role-gated.
 */
class AdminQuizController extends ApiController
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

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/quiz/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        return $this->success([
            'questions' => McqQuestion::where('organization_id', $user->organization_id)->count(),
        ], 'Quiz stats fetched.');
    }

    // ══════════════════════════ TREE WITH COUNTS ══════════════════════════

    /** GET /admin/quiz?standard_id=&section_id=&subject_id= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if (!$request->filled('standard_id') || !$request->filled('subject_id')) {
            return $this->success(['chapters' => []], 'Select class and subject.');
        }

        $chapters = Chapter::with(['topics' => fn ($q) => $q->orderBy('id')])
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->where('subject_id', $request->subject_id)
            ->orderBy('order')
            ->get();

        $chapterIds = $chapters->pluck('id')->all();
        $topicIds   = $chapters->flatMap(fn ($ch) => $ch->topics->pluck('id'))->all();

        $chapterCounts = McqQuestion::where('organization_id', $orgId)
            ->whereIn('chapter_id', $chapterIds)->whereNull('topic_id')
            ->selectRaw('chapter_id, count(*) as c')->groupBy('chapter_id')
            ->pluck('c', 'chapter_id')->toArray();

        $topicCounts = McqQuestion::where('organization_id', $orgId)
            ->whereIn('topic_id', $topicIds)
            ->selectRaw('topic_id, count(*) as c')->groupBy('topic_id')
            ->pluck('c', 'topic_id')->toArray();

        $data = $chapters->map(fn ($c) => [
            'id'        => $c->id,
            'name'      => $c->name,
            'mcq_count' => (int) ($chapterCounts[$c->id] ?? 0),
            'topics'    => $c->topics->map(fn ($t) => [
                'id'        => $t->id,
                'name'      => $t->topic_name,
                'mcq_count' => (int) ($topicCounts[$t->id] ?? 0),
            ]),
        ]);

        return $this->success(['chapters' => $data], 'Quiz tree fetched.');
    }

    // ══════════════════════════ LIST MCQs ══════════════════════════

    /** GET /admin/quiz/{type}/{id} — MCQs (with options) for a chapter or topic. */
    public function mcqs($type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = $type === 'topic' ? 'topic' : 'chapter';
        $orgId = $user->organization_id;

        $query = McqQuestion::with('options')->where('organization_id', $orgId);
        if ($type === 'chapter') $query->where('chapter_id', $id)->whereNull('topic_id');
        else $query->where('topic_id', $id);

        $name = $type === 'chapter'
            ? (Chapter::where('organization_id', $orgId)->find($id)->name ?? '')
            : (Topic::where('organization_id', $orgId)->find($id)->topic_name ?? '');

        $mcqs = $query->orderBy('id')->get()->map(fn ($q) => [
            'id'            => $q->id,
            'question_text' => $q->question_text,
            'time_limit'    => $q->time_limit ?? 30,
            'options'       => $q->options->map(fn ($o) => [
                'id'         => $o->id,
                'text'       => $o->option_text,
                'is_correct' => (bool) $o->is_correct,
            ]),
        ]);

        return $this->success(['name' => $name, 'mcqs' => $mcqs], 'MCQs fetched.');
    }

    // ══════════════════════════ CREATE ══════════════════════════

    /** POST /admin/quiz/{type}/{id}  (mcqs:[{question_text, time_limit, options:[{text,is_correct}]}]) */
    public function store(Request $request, $type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = $type === 'topic' ? 'topic' : 'chapter';
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'mcqs'                      => 'required|array|min:1',
            'mcqs.*.question_text'      => 'required|string',
            'mcqs.*.time_limit'         => 'nullable|integer|min:5',
            'mcqs.*.options'            => 'required|array|size:4',
            'mcqs.*.options.*.text'     => 'required|string',
            'mcqs.*.options.*.is_correct' => 'nullable|boolean',
        ])) return $err;

        // Each question must have exactly one correct option.
        foreach ($request->mcqs as $i => $row) {
            if (!collect($row['options'])->contains(fn ($o) => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                return $this->error('Q' . ($i + 1) . ': Select the correct answer.', 422);
            }
        }

        // Resolve chapter/topic for standard/section.
        if ($type === 'chapter') {
            $chapter = Chapter::where('organization_id', $orgId)->find($id);
            $chapterId = $chapter?->id;
            $topicId = null;
        } else {
            $topic = Topic::with('chapter')->where('organization_id', $orgId)->find($id);
            $chapter = $topic?->chapter;
            $chapterId = $chapter?->id;
            $topicId = $topic?->id;
        }
        if (!$chapter) return $this->error('Chapter not found.', 404);

        try {
            DB::transaction(function () use ($request, $orgId, $user, $chapter, $chapterId, $topicId) {
                foreach ($request->mcqs as $row) {
                    $question = McqQuestion::create([
                        'organization_id' => $orgId,
                        'standard_id'     => $chapter->standard_id,
                        'section_id'      => $chapter->section_id,
                        'chapter_id'      => $chapterId,
                        'topic_id'        => $topicId,
                        'created_by'      => $user->id,
                        'question_text'   => $row['question_text'],
                        'time_limit'      => $row['time_limit'] ?? 30,
                        'is_active'       => true,
                    ]);
                    foreach ($row['options'] as $opt) {
                        McqOption::create([
                            'organization_id' => $orgId,
                            'mcq_question_id' => $question->id,
                            'option_text'     => $opt['text'],
                            'is_correct'      => filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Error saving MCQs: ' . $e->getMessage(), 500);
        }

        return $this->success(['created' => count($request->mcqs)], 'Created ' . count($request->mcqs) . ' MCQ(s)!');
    }

    // ══════════════════════════ UPDATE ══════════════════════════

    /** PUT /admin/quiz/mcqs  (mcqs:[{id, question_text, time_limit, options:[{id,text,is_correct}]}]) */
    public function update(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'mcqs'                   => 'required|array|min:1',
            'mcqs.*.id'              => 'required|integer',
            'mcqs.*.question_text'   => 'required|string',
            'mcqs.*.options'         => 'required|array|min:2',
            'mcqs.*.options.*.id'    => 'required|integer',
            'mcqs.*.options.*.text'  => 'required|string',
        ])) return $err;

        foreach ($request->mcqs as $i => $mcq) {
            if (!collect($mcq['options'])->contains(fn ($o) => filter_var($o['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))) {
                return $this->error('Q' . ($i + 1) . ': Select the correct answer.', 422);
            }
        }

        try {
            DB::transaction(function () use ($request, $orgId) {
                foreach ($request->mcqs as $mcq) {
                    // Scope updates to this org so cross-org ids can't be touched.
                    McqQuestion::where('id', $mcq['id'])->where('organization_id', $orgId)->update([
                        'question_text' => $mcq['question_text'],
                        'time_limit'    => $mcq['time_limit'] ?? 30,
                    ]);
                    foreach ($mcq['options'] as $opt) {
                        McqOption::where('id', $opt['id'])->where('organization_id', $orgId)->update([
                            'option_text' => $opt['text'],
                            'is_correct'  => filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Error updating MCQs: ' . $e->getMessage(), 500);
        }

        return $this->success(null, 'MCQs updated!');
    }

    // ══════════════════════════ DELETE ══════════════════════════

    /** DELETE /admin/quiz/mcqs  (ids:[]) */
    public function destroy(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, [
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ], ['ids.required' => 'Select at least one MCQ to delete.'])) return $err;

        // Only delete questions that belong to this org.
        $ids = McqQuestion::where('organization_id', $orgId)->whereIn('id', $request->ids)->pluck('id')->all();
        if (empty($ids)) return $this->error('No matching MCQs found.', 404);

        try {
            DB::transaction(function () use ($ids) {
                McqOption::whereIn('mcq_question_id', $ids)->delete();
                McqUserAnswer::whereIn('mcq_question_id', $ids)->delete();
                McqQuestion::whereIn('id', $ids)->delete();
            });
        } catch (\Throwable $e) {
            return $this->error('Error deleting MCQs: ' . $e->getMessage(), 500);
        }

        return $this->success(['deleted' => count($ids)], 'Deleted ' . count($ids) . ' MCQ(s)!');
    }
}
