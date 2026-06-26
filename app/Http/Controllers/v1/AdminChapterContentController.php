<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\Chapter;
use App\Models\Student\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * School-admin Content module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Content.php — attach learning content (text / url /
 * image / pdf) to a chapter or topic, list the chapter→topic tree with content
 * flags, and clear content. Org-scoped, role-gated to admin / sub-admin.
 */
class AdminChapterContentController extends ApiController
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

    private function chapterContent(Chapter $c): array
    {
        return ['text' => $c->description, 'url' => $c->file_path, 'image' => $c->image_path, 'pdf' => $c->pdf_path];
    }

    private function topicContent(Topic $t): array
    {
        return ['text' => $t->topic_content, 'url' => null, 'image' => $t->image_path, 'pdf' => $t->pdf_path];
    }

    private function hasContent(array $c): bool
    {
        return !empty($c['text']) || !empty($c['url']) || !empty($c['image']) || !empty($c['pdf']);
    }

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/content/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $withContent = Chapter::where('organization_id', $orgId)
            ->where(fn ($q) => $q->whereNotNull('file_path')->orWhereNotNull('image_path')->orWhereNotNull('pdf_path')->orWhereNotNull('description'))
            ->count()
            + Topic::where('organization_id', $orgId)
            ->where(fn ($q) => $q->whereNotNull('topic_content')->orWhereNotNull('image_path')->orWhereNotNull('pdf_path'))
            ->count();

        return $this->success([
            'chapters'     => Chapter::where('organization_id', $orgId)->count(),
            'topics'       => Topic::where('organization_id', $orgId)->count(),
            'with_content' => $withContent,
        ], 'Content stats fetched.');
    }

    // ══════════════════════════ LIST ══════════════════════════

    /** GET /admin/content?standard_id=&section_id=&subject_id= */
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
            ->get()
            ->map(function ($c) {
                $cc = $this->chapterContent($c);
                return [
                    'id'           => $c->id,
                    'name'         => $c->name,
                    'has_content'  => $this->hasContent($cc),
                    'content'      => $cc,
                    'topics'       => $c->topics->map(function ($t) {
                        $tc = $this->topicContent($t);
                        return [
                            'id'          => $t->id,
                            'name'        => $t->topic_name,
                            'has_content' => $this->hasContent($tc),
                            'content'     => $tc,
                        ];
                    }),
                ];
            });

        return $this->success(['chapters' => $chapters], 'Content fetched.');
    }

    // ══════════════════════════ SAVE ══════════════════════════

    /**
     * POST /admin/content (multipart)
     * target_type=chapter|topic, target_id, content_type=text|url|image|pdf|all,
     * text?, url?, image?(file), pdf?(file)
     */
    public function save(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'target_type'  => 'required|in:chapter,topic',
            'target_id'    => 'required|integer',
            'content_type' => 'required|in:text,url,image,pdf,all',
            'text'         => 'nullable|string',
            'url'          => 'nullable|string',
            'image'        => 'nullable|image|max:5120',
            'pdf'          => 'nullable|file|mimes:pdf|max:10240',
        ])) return $err;

        $orgId = $user->organization_id;

        try {
            if ($request->target_type === 'chapter') {
                $record = Chapter::where('organization_id', $orgId)->find($request->target_id);
                if (!$record) return $this->error('Chapter not found.', 404);
                $record->update($this->buildChapterData($request, $record));
            } else {
                $record = Topic::where('organization_id', $orgId)->find($request->target_id);
                if (!$record) return $this->error('Topic not found.', 404);
                $record->update($this->buildTopicData($request, $record));
            }
        } catch (\Throwable $e) {
            return $this->error('Error saving content: ' . $e->getMessage(), 500);
        }

        return $this->success(null, 'Content saved successfully!');
    }

    private function uploadFile(Request $request, string $field, string $dir): string
    {
        $path = $request->file($field)->store($dir, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        return Storage::disk('s3')->url($path);
    }

    private function buildChapterData(Request $request, Chapter $chapter): array
    {
        $type = $request->content_type;
        $data = [];

        if ($type === 'all') {
            if ($request->filled('text')) $data['description'] = $request->text;
            if ($request->filled('url'))  $data['file_path']   = $request->url;
            if ($request->hasFile('image')) {
                if ($chapter->image_path) $this->deleteS3($chapter->image_path);
                $data['image_path'] = $this->uploadFile($request, 'image', 'admin/content/chapter-images');
            }
            if ($request->hasFile('pdf')) {
                if ($chapter->pdf_path) $this->deleteS3($chapter->pdf_path);
                $data['pdf_path'] = $this->uploadFile($request, 'pdf', 'admin/content/chapter-pdfs');
            }
            return $data;
        }

        switch ($type) {
            case 'text':
                $data['description'] = $request->text;
                $data['file_path']   = null;
                $data['image_path']  = $this->clearFile($chapter->image_path);
                $data['pdf_path']    = $this->clearFile($chapter->pdf_path);
                break;
            case 'url':
                $data['file_path']  = $request->url;
                $data['image_path'] = $this->clearFile($chapter->image_path);
                $data['pdf_path']   = $this->clearFile($chapter->pdf_path);
                break;
            case 'image':
                if ($request->hasFile('image')) {
                    if ($chapter->image_path) $this->deleteS3($chapter->image_path);
                    $data['image_path'] = $this->uploadFile($request, 'image', 'admin/content/chapter-images');
                }
                $data['file_path'] = null;
                $data['pdf_path']  = $this->clearFile($chapter->pdf_path);
                break;
            case 'pdf':
                if ($request->hasFile('pdf')) {
                    if ($chapter->pdf_path) $this->deleteS3($chapter->pdf_path);
                    $data['pdf_path'] = $this->uploadFile($request, 'pdf', 'admin/content/chapter-pdfs');
                }
                $data['file_path']  = null;
                $data['image_path'] = $this->clearFile($chapter->image_path);
                break;
        }
        return $data;
    }

    private function buildTopicData(Request $request, Topic $topic): array
    {
        $type = $request->content_type;
        $data = [];

        if ($type === 'all') {
            if ($request->filled('text')) $data['topic_content'] = $request->text;
            if ($request->hasFile('image')) {
                if ($topic->image_path) $this->deleteS3($topic->image_path);
                $data['image_path'] = $this->uploadFile($request, 'image', 'admin/content/topic-images');
            }
            if ($request->hasFile('pdf')) {
                if ($topic->pdf_path) $this->deleteS3($topic->pdf_path);
                $data['pdf_path'] = $this->uploadFile($request, 'pdf', 'admin/content/topic-pdfs');
            }
            if ($request->filled('url') && !$request->filled('text')) {
                $data['topic_content'] = $request->url;
            }
            return $data;
        }

        switch ($type) {
            case 'text':
                $data['topic_content'] = $request->text;
                $data['image_path']    = $this->clearFile($topic->image_path);
                $data['pdf_path']      = $this->clearFile($topic->pdf_path);
                break;
            case 'url':
                $data['topic_content'] = $request->url;
                $data['image_path']    = $this->clearFile($topic->image_path);
                $data['pdf_path']      = $this->clearFile($topic->pdf_path);
                break;
            case 'image':
                if ($request->hasFile('image')) {
                    if ($topic->image_path) $this->deleteS3($topic->image_path);
                    $data['image_path'] = $this->uploadFile($request, 'image', 'admin/content/topic-images');
                }
                $data['pdf_path'] = $this->clearFile($topic->pdf_path);
                break;
            case 'pdf':
                if ($request->hasFile('pdf')) {
                    if ($topic->pdf_path) $this->deleteS3($topic->pdf_path);
                    $data['pdf_path'] = $this->uploadFile($request, 'pdf', 'admin/content/topic-pdfs');
                }
                $data['image_path'] = $this->clearFile($topic->image_path);
                break;
        }
        return $data;
    }

    private function clearFile(?string $url): ?string
    {
        if ($url) $this->deleteS3($url);
        return null;
    }

    private function deleteS3(string $fileUrl): void
    {
        try {
            $path = str_replace(Storage::disk('s3')->url(''), '', $fileUrl);
            Storage::disk('s3')->delete($path);
        } catch (\Throwable $e) {
            logger()->warning('AdminContent deleteS3 failed: ' . $fileUrl);
        }
    }

    // ══════════════════════════ CLEAR ══════════════════════════

    /** DELETE /admin/content/{type}/{id} — wipes all content on the chapter/topic. */
    public function clear($type, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $type = $type === 'topic' ? 'topic' : 'chapter';
        $orgId = $user->organization_id;

        if ($type === 'chapter') {
            $c = Chapter::where('organization_id', $orgId)->find($id);
            if (!$c) return $this->error('Chapter not found.', 404);
            if ($c->image_path) $this->deleteS3($c->image_path);
            if ($c->pdf_path)   $this->deleteS3($c->pdf_path);
            $c->update(['description' => null, 'file_path' => null, 'image_path' => null, 'pdf_path' => null]);
        } else {
            $t = Topic::where('organization_id', $orgId)->find($id);
            if (!$t) return $this->error('Topic not found.', 404);
            if ($t->image_path) $this->deleteS3($t->image_path);
            if ($t->pdf_path)   $this->deleteS3($t->pdf_path);
            $t->update(['topic_content' => null, 'image_path' => null, 'pdf_path' => null]);
        }

        return $this->success(null, 'Content removed!');
    }
}
