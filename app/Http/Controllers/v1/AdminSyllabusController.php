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
        $query->taughtIn($standardId, $sectionId ?: null);
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

    /** The panel's four counts: classes, subjects, chapters and topics. */
    private function statsFor(int $orgId): array
    {
        return [
            'standards' => Standard::where('organization_id', $orgId)->where('is_active', true)->count(),
            'subjects'  => Subject::where('organization_id', $orgId)->where('is_active', true)->count(),
            'chapters'  => Chapter::where('organization_id', $orgId)->count(),
            'topics'    => Topic::where('organization_id', $orgId)->count(),
        ];
    }

    /**
     * GET /admin/syllabus/outline?standard_id=&section_id=&subject_id=
     *
     * The panel's Syllabus list, as its render() reads it: the picked subject —
     * one the class (or section) is taught — with every chapter it has, in
     * their order, each with its topics in the order they were added; and the
     * panel's counts. Nothing until a class and a subject are picked.
     */
    public function outline(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $stats = $this->statsFor($orgId);
        if (!$request->filled('standard_id') || !$request->filled('subject_id')) {
            return $this->success(['subject' => null, 'chapters' => [], 'stats' => $stats], 'Select class and subject.');
        }

        $subject = Subject::with([
            'chapters' => fn ($q) => $q->where('organization_id', $orgId)->orderBy('order')->with([
                'topics' => fn ($tq) => $tq->orderBy('id'),
            ]),
        ])
            ->where('organization_id', $orgId)
            ->where('is_active', true)
            ->taughtIn($request->standard_id, $request->section_id ?: null)
            ->where('id', $request->subject_id)
            ->first();

        return $this->success([
            'subject'  => $subject ? ['id' => $subject->id, 'name' => $subject->name, 'image' => $subject->iconUrl()] : null,
            'chapters' => $subject ? $subject->chapters->map(fn ($c) => [
                'id'          => $c->id,
                'name'        => $c->name,
                'description' => $c->description,
                'order'       => (int) $c->order,
                'topics'      => $c->topics->map(fn ($t) => [
                    'id'    => $t->id,
                    'name'  => $t->topic_name,
                    'order' => (int) $t->order,
                ])->values(),
            ])->values() : [],
            'stats'    => $stats,
        ], 'Syllabus fetched.');
    }

    /** "3 added. 1 updated. 2 deleted." — or "No changes.", as the panel says it. */
    private function savedLine(int $created, int $updated, int $deleted): string
    {
        return trim(($created ? "{$created} added. " : '') . ($updated ? "{$updated} updated. " : '') . ($deleted ? "{$deleted} deleted." : '')) ?: 'No changes.';
    }

    /**
     * POST /admin/syllabus/chapters/set
     *   {standard_id, section_id?, subject_id, rows:[{id?, name, order}], deleted_ids:[]}
     *
     * The panel's chapter manager's Save, as one: removed chapters go with
     * their topics, saved ones are renamed and reordered, new ones are added
     * to the class (and section) — in one transaction — and the subject's
     * teachers hear what changed.
     */
    public function saveChapterSet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $org = (int) $user->organization_id;

        if (!$request->filled('standard_id') || !$request->filled('subject_id')) {
            return $this->error('Please select class and subject.', 422);
        }
        if (!Subject::where('organization_id', $org)->whereKey($request->subject_id)->exists()
            || !Standard::where('organization_id', $org)->whereKey($request->standard_id)->exists()) {
            return $this->error('Subject not found.', 404);
        }

        $rows    = array_values(array_filter((array) $request->input('rows', []), 'is_array'));
        $deleted = array_values(array_unique(array_map('intval', (array) $request->input('deleted_ids', []))));

        if (empty($rows) && empty($deleted)) {
            return $this->error('Please add at least one chapter.', 422);
        }
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                return $this->error('Chapter ' . ($i + 1) . ': Name is required.', 422);
            }
        }

        // The subject's chapters as they were, so its teachers hear what changed.
        $push   = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->outlineSnapshot($org, (int) $request->subject_id);

        $created = 0;
        $updated = 0;
        try {
            DB::beginTransaction();

            // Removed existing chapters — their topics go too.
            if (!empty($deleted)) {
                Topic::whereIn('chapter_id', $deleted)->where('organization_id', $org)->delete();
                Chapter::whereIn('id', $deleted)->where('organization_id', $org)->delete();
            }

            foreach ($rows as $row) {
                if (!empty($row['id'])) {
                    Chapter::where('id', $row['id'])->where('organization_id', $org)->update([
                        'name'  => trim($row['name']),
                        'order' => (int) ($row['order'] ?? 1),
                    ]);
                    $updated++;
                } else {
                    Chapter::create([
                        'organization_id' => $org,
                        'standard_id'     => $request->standard_id,
                        'section_id'      => $request->section_id ?: null,
                        'subject_id'      => $request->subject_id,
                        'user_id'         => $user->id,
                        'name'            => trim($row['name']),
                        'order'           => (int) ($row['order'] ?? 1),
                        'is_published'    => true,
                    ]);
                    $created++;
                }
            }
            DB::commit();
            $push->outlineSaved($before);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Chapter save error: ' . $e->getMessage());
            return $this->error('Error: ' . $e->getMessage(), 500);
        }

        $count = count($deleted);
        return $this->success(
            ['created' => $created, 'updated' => $updated, 'deleted' => $count],
            'Chapters saved! ' . $this->savedLine($created, $updated, $count)
        );
    }

    /**
     * POST /admin/syllabus/topics/set {chapter_id, rows:[{id?, name, order}], deleted_ids:[]}
     *
     * The panel's topic manager's Save for one chapter: removed topics go,
     * saved ones are renamed and reordered, new ones are added — in one
     * transaction — and the subject's teachers hear what changed.
     */
    public function saveTopicSet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $org = (int) $user->organization_id;

        $chapter = $request->filled('chapter_id')
            ? Chapter::where('organization_id', $org)->find($request->chapter_id)
            : null;
        if (!$chapter) {
            return $this->error('Please select class, subject and chapter.', 422);
        }

        $rows    = array_values(array_filter((array) $request->input('rows', []), 'is_array'));
        $deleted = array_values(array_unique(array_map('intval', (array) $request->input('deleted_ids', []))));

        if (empty($rows) && empty($deleted)) {
            return $this->error('Please add at least one topic.', 422);
        }
        foreach ($rows as $i => $row) {
            if (trim((string) ($row['name'] ?? '')) === '') {
                return $this->error('Topic ' . ($i + 1) . ': Name is required.', 422);
            }
        }

        $push   = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->outlineSnapshotOfChapter((int) $chapter->id);

        $created = 0;
        $updated = 0;
        try {
            DB::beginTransaction();

            if (!empty($deleted)) {
                Topic::whereIn('id', $deleted)->where('organization_id', $org)->delete();
            }

            foreach ($rows as $row) {
                if (!empty($row['id'])) {
                    Topic::where('id', $row['id'])->where('organization_id', $org)->update([
                        'topic_name' => trim($row['name']),
                        'order'      => (int) ($row['order'] ?? 1),
                    ]);
                    $updated++;
                } else {
                    Topic::create([
                        'organization_id' => $org,
                        'chapter_id'      => $chapter->id,
                        'topic_name'      => trim($row['name']),
                        'order'           => (int) ($row['order'] ?? 1),
                    ]);
                    $created++;
                }
            }
            DB::commit();
            $push->outlineSaved($before);
        } catch (\Exception $e) {
            DB::rollBack();
            logger()->error('Topic save error: ' . $e->getMessage());
            return $this->error('Error: ' . $e->getMessage(), 500);
        }

        $count = count($deleted);
        return $this->success(
            ['created' => $created, 'updated' => $updated, 'deleted' => $count],
            'Topics saved! ' . $this->savedLine($created, $updated, $count)
        );
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
