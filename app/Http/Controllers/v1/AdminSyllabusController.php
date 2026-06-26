<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\Chapter;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Student\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * School-admin Syllabus module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Syllabus.php — the Subject → Chapter → Topic tree
 * with bulk chapter/topic creation, inline edit and delete. Also exposes the
 * shared curriculum lookups (subjects-by-class/section, chapters-by-subject)
 * reused by the Content and Quiz screens. Org-scoped, role-gated.
 */
class AdminSyllabusController extends ApiController
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

    /** Subjects mapped to a class (or section, when given). */
    private function subjectsFor(int $orgId, $standardId, $sectionId = null)
    {
        $query = Subject::where('organization_id', $orgId)->where('is_active', true);
        if ($sectionId) {
            $query->whereHas('sections', fn ($q) => $q->where('sections.id', $sectionId));
        } else {
            $query->whereHas('standards', fn ($q) => $q->where('standards.id', $standardId));
        }
        return $query->orderBy('name')->get(['id', 'name', 'code']);
    }

    // ══════════════════════════ SHARED CURRICULUM LOOKUPS ══════════════════════════

    /** GET /admin/curriculum/subjects?standard_id=&section_id= */
    public function subjects(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!$request->filled('standard_id')) {
            return $this->success(['subjects' => []], 'Subjects fetched.');
        }
        return $this->success([
            'subjects' => $this->subjectsFor($user->organization_id, $request->standard_id, $request->section_id),
        ], 'Subjects fetched.');
    }

    /** GET /admin/curriculum/chapters?subject_id= — chapters (with topics) for a subject. */
    public function chapters(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!$request->filled('subject_id')) {
            return $this->success(['chapters' => []], 'Chapters fetched.');
        }

        $chapters = Chapter::with('topics:id,chapter_id,topic_name')
            ->where('organization_id', $user->organization_id)
            ->where('subject_id', $request->subject_id)
            ->orderBy('order')
            ->get(['id', 'name', 'order'])
            ->map(fn ($c) => [
                'id'     => $c->id,
                'name'   => $c->name,
                'order'  => $c->order,
                'topics' => $c->topics->map(fn ($t) => ['id' => $t->id, 'name' => $t->topic_name]),
            ]);

        return $this->success(['chapters' => $chapters], 'Chapters fetched.');
    }

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/syllabus/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        return $this->success([
            'standards' => Standard::where('organization_id', $orgId)->where('is_active', true)->count(),
            'subjects'  => Subject::where('organization_id', $orgId)->where('is_active', true)->count(),
            'chapters'  => Chapter::where('organization_id', $orgId)->count(),
            'topics'    => Topic::where('organization_id', $orgId)->count(),
        ], 'Syllabus stats fetched.');
    }

    // ══════════════════════════ TREE VIEW ══════════════════════════

    /** GET /admin/syllabus?standard_id=&section_id=&subject_id=&search= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        // Gated: require class + subject (section optional) — same as web.
        if (!$request->filled('standard_id') || !$request->filled('subject_id')) {
            return $this->success(['chapters' => []], 'Select class and subject.');
        }

        $query = Chapter::with(['topics' => fn ($q) => $q->orderBy('id')])
            ->where('organization_id', $orgId)
            ->where('subject_id', $request->subject_id)
            ->where('standard_id', $request->standard_id)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->search . '%';
                $q->where(fn ($q) => $q->where('name', 'like', $term)
                    ->orWhereHas('topics', fn ($tq) => $tq->where('topic_name', 'like', $term)));
            })
            ->orderBy('order');

        $chapters = $query->get(['id', 'name', 'description', 'order'])->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'description' => $c->description,
            'order'       => $c->order,
            'topics'      => $c->topics->map(fn ($t) => ['id' => $t->id, 'name' => $t->topic_name]),
        ]);

        return $this->success(['chapters' => $chapters], 'Syllabus fetched.');
    }

    // ══════════════════════════ CHAPTERS ══════════════════════════

    /** POST /admin/syllabus/chapters  (standard_id, section_id?, subject_id, chapters:[{name,description,order}]) */
    public function storeChapters(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id'         => 'required|integer|exists:standards,id',
            'section_id'          => 'nullable|integer|exists:sections,id',
            'subject_id'          => 'required|integer|exists:subjects,id',
            'chapters'            => 'required|array|min:1',
            'chapters.*.name'     => 'required|string|max:255',
            'chapters.*.description' => 'nullable|string',
            'chapters.*.order'    => 'nullable|integer',
        ], ['chapters.required' => 'Please add at least one chapter.'])) return $err;

        $orgId = $user->organization_id;
        try {
            DB::transaction(function () use ($request, $orgId, $user) {
                foreach ($request->chapters as $row) {
                    Chapter::create([
                        'organization_id' => $orgId,
                        'standard_id'     => $request->standard_id,
                        'section_id'      => $request->section_id ?: null,
                        'subject_id'      => $request->subject_id,
                        'user_id'         => $user->id,
                        'name'            => $row['name'],
                        'description'     => $row['description'] ?? null,
                        'order'           => $row['order'] ?? 1,
                        'is_published'    => true,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Error saving chapters: ' . $e->getMessage(), 500);
        }

        return $this->success(['created' => count($request->chapters)], 'Created ' . count($request->chapters) . ' chapter(s) successfully!');
    }

    /** PUT /admin/syllabus/chapters/{id}  (name, description?, order?) */
    public function updateChapter(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $chapter = Chapter::where('organization_id', $user->organization_id)->find($id);
        if (!$chapter) return $this->error('Chapter not found.', 404);
        if ($err = $this->validateWith($request, [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'order'       => 'nullable|integer',
        ])) return $err;

        $chapter->update([
            'name'        => $request->name,
            'description' => $request->description ?: null,
            'order'       => $request->filled('order') ? (int) $request->order : $chapter->order,
        ]);

        return $this->success(['id' => $chapter->id], 'Chapter updated!');
    }

    /** DELETE /admin/syllabus/chapters/{id} — also deletes its topics. */
    public function deleteChapter($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $chapter = Chapter::where('organization_id', $user->organization_id)->with('topics')->find($id);
        if (!$chapter) return $this->error('Chapter not found.', 404);

        try {
            DB::transaction(function () use ($chapter) {
                $chapter->topics()->delete();
                $chapter->delete();
            });
        } catch (\Throwable $e) {
            return $this->error('Error deleting chapter: ' . $e->getMessage(), 500);
        }

        return $this->success(null, 'Chapter deleted!');
    }

    // ══════════════════════════ TOPICS ══════════════════════════

    /** POST /admin/syllabus/topics  (chapter_id, topics:[{name}]) */
    public function storeTopics(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'chapter_id'    => 'required|integer|exists:chapters,id',
            'topics'        => 'required|array|min:1',
            'topics.*.name' => 'required|string|max:255',
        ], ['topics.required' => 'Please add at least one topic.'])) return $err;

        $orgId = $user->organization_id;
        $chapter = Chapter::where('organization_id', $orgId)->find($request->chapter_id);
        if (!$chapter) return $this->error('Chapter not found.', 404);

        try {
            DB::transaction(function () use ($request, $orgId) {
                foreach ($request->topics as $row) {
                    Topic::create([
                        'organization_id' => $orgId,
                        'chapter_id'      => $request->chapter_id,
                        'topic_name'      => $row['name'],
                    ]);
                }
            });
        } catch (\Throwable $e) {
            return $this->error('Error saving topics: ' . $e->getMessage(), 500);
        }

        return $this->success(['created' => count($request->topics)], 'Created ' . count($request->topics) . ' topic(s) successfully!');
    }

    /** PUT /admin/syllabus/topics/{id}  (name) */
    public function updateTopic(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $topic = Topic::where('organization_id', $user->organization_id)->find($id);
        if (!$topic) return $this->error('Topic not found.', 404);
        if ($err = $this->validateWith($request, ['name' => 'required|string|max:255'])) return $err;

        $topic->update(['topic_name' => $request->name]);
        return $this->success(['id' => $topic->id], 'Topic updated!');
    }

    /** DELETE /admin/syllabus/topics/{id} */
    public function deleteTopic($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $topic = Topic::where('organization_id', $user->organization_id)->find($id);
        if (!$topic) return $this->error('Topic not found.', 404);

        $topic->delete();
        return $this->success(null, 'Topic deleted!');
    }
}
