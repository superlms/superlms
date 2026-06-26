<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Book;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * School-admin Book (library) module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Book.php — class-gated listing with filters/stats,
 * CRUD with cover + PDF uploads, and per class/section uniqueness. Org-scoped,
 * role-gated to admin / sub-admin.
 */
class AdminBookController extends ApiController
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

    private function shape(Book $b): array
    {
        return [
            'id'           => $b->id,
            'title'        => $b->title,
            'standard_id'  => $b->standard_id,
            'class'        => $b->standard->name ?? null,
            'section_id'   => $b->section_id,
            'section'      => $b->section->name ?? null,
            'subject_id'   => $b->subject_id,
            'subject'      => $b->subject->name ?? null,
            'book_logo'    => $b->book_logo,
            'pdf_file'     => $b->pdf_file,
            'is_active'    => (bool) $b->is_active,
            'created_at'   => $b->created_at?->toIso8601String(),
        ];
    }

    /** Subjects mapped to a class (or section) via the pivot tables. */
    private function subjectsFor(int $orgId, $standardId, $sectionId = null)
    {
        if ($sectionId) {
            return Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.id', 'subjects.name')->distinct()->orderBy('subjects.name')->get();
        }
        return Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
            ->where('standard_subjects.standard_id', $standardId)
            ->where('subjects.organization_id', $orgId)
            ->where('subjects.is_active', true)
            ->select('subjects.id', 'subjects.name')->distinct()->orderBy('subjects.name')->get();
    }

    /** GET /admin/books/options?standard_id=&section_id= — sections + subjects for the form/filters. */
    public function options(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $sections = $request->filled('standard_id')
            ? Section::where('standard_id', $request->standard_id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
            : collect();
        $subjects = $request->filled('standard_id')
            ? $this->subjectsFor($orgId, $request->standard_id, $request->section_id)
            : collect();

        return $this->success(['sections' => $sections, 'subjects' => $subjects], 'Book options fetched.');
    }

    /** GET /admin/books?standard_id(required)&section_id=&subject_id=&search=&status=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $stats = $this->stats($orgId);

        // Class-gated like the web — no class, no list (but still return stats + classes).
        if (!$request->filled('standard_id')) {
            return $this->success([
                'books' => [], 'pagination' => null, 'stats' => $stats,
                'classes' => $this->classes($orgId),
            ], 'Select a class.');
        }

        $query = Book::with(['standard', 'section', 'subject'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->when($request->filled('section_id'), fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->subject_id))
            ->when($request->filled('status') && $request->status !== '', fn ($q) => $q->where('is_active', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', "%{$request->search}%")
                ->orWhereHas('standard', fn ($sq) => $sq->where('name', 'like', "%{$request->search}%"))
                ->orWhereHas('subject', fn ($sq) => $sq->where('name', 'like', "%{$request->search}%"))));

        $paginator = $query->orderByDesc('created_at')->paginate((int) $request->input('per_page', 10));

        return $this->success([
            'books'      => collect($paginator->items())->map(fn ($b) => $this->shape($b)),
            'pagination' => $this->paginationMeta($paginator),
            'stats'      => $stats,
            'classes'    => $this->classes($orgId),
        ], 'Books fetched.');
    }

    private function classes(int $orgId)
    {
        return Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('order')->get(['id', 'name']);
    }

    private function stats(int $orgId): array
    {
        $s = Book::where('organization_id', $orgId)->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN pdf_file IS NOT NULL AND pdf_file != "" THEN 1 ELSE 0 END) as with_pdf
        ')->first();

        return [
            'total'    => (int) ($s->total ?? 0),
            'active'   => (int) ($s->active ?? 0),
            'inactive' => (int) ($s->inactive ?? 0),
            'with_pdf' => (int) ($s->with_pdf ?? 0),
        ];
    }

    private function rules(int $orgId, Request $request, ?int $ignoreId): array
    {
        return [
            'title' => ['required', 'string', 'max:255',
                Rule::unique('books', 'title')->where(fn ($q) => $q
                    ->where('organization_id', $orgId)
                    ->where('standard_id', $request->standard_id)
                    ->where('section_id', $request->section_id ?: null))
                    ->ignore($ignoreId)],
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'book_logo'   => 'nullable|image|max:2048',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:10240',
            'is_active'   => 'nullable|boolean',
        ];
    }

    /** POST /admin/books (multipart) */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($err = $this->validateWith($request, $this->rules($orgId, $request, null), [
            'title.unique' => 'A book with this name already exists for this class and section.',
        ])) return $err;

        try {
            $data = [
                'title'           => $request->title,
                'standard_id'     => $request->standard_id,
                'section_id'      => $request->section_id ?: null,
                'subject_id'      => $request->subject_id,
                'is_active'       => $request->boolean('is_active', true),
                'organization_id' => $orgId,
            ];
            if ($request->hasFile('book_logo')) $data['book_logo'] = $this->upload($request, 'book_logo', 'admin/library/covers');
            if ($request->hasFile('pdf_file'))  $data['pdf_file']  = $this->upload($request, 'pdf_file', 'admin/library/pdfs');

            $book = Book::create($data);
            return $this->success($this->shape($book->load(['standard', 'section', 'subject'])), 'Book added successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error Saving Book: ' . $e->getMessage(), 500);
        }
    }

    /** POST /admin/books/{id} (multipart update) */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $book = Book::where('organization_id', $orgId)->find($id);
        if (!$book) return $this->error('Book not found.', 404);

        if ($err = $this->validateWith($request, $this->rules($orgId, $request, (int) $id), [
            'title.unique' => 'A book with this name already exists for this class and section.',
        ])) return $err;

        try {
            $data = [
                'title'       => $request->title,
                'standard_id' => $request->standard_id,
                'section_id'  => $request->section_id ?: null,
                'subject_id'  => $request->subject_id,
                'is_active'   => $request->boolean('is_active', $book->is_active),
            ];
            if ($request->hasFile('book_logo')) {
                if ($book->book_logo) $this->deleteS3($book->book_logo);
                $data['book_logo'] = $this->upload($request, 'book_logo', 'admin/library/covers');
            }
            if ($request->hasFile('pdf_file')) {
                if ($book->pdf_file) $this->deleteS3($book->pdf_file);
                $data['pdf_file'] = $this->upload($request, 'pdf_file', 'admin/library/pdfs');
            }

            $book->update($data);
            return $this->success($this->shape($book->fresh(['standard', 'section', 'subject'])), 'Book updated successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error Saving Book: ' . $e->getMessage(), 500);
        }
    }

    /** DELETE /admin/books/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $book = Book::where('organization_id', $user->organization_id)->find($id);
        if (!$book) return $this->error('Book not found.', 404);

        if ($book->book_logo) $this->deleteS3($book->book_logo);
        if ($book->pdf_file)  $this->deleteS3($book->pdf_file);
        $book->delete();

        return $this->success(null, 'Book deleted successfully!');
    }

    private function upload(Request $request, string $field, string $dir): string
    {
        $path = $request->file($field)->store($dir, 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        return Storage::disk('s3')->url($path);
    }

    private function deleteS3(string $url): void
    {
        try {
            Storage::disk('s3')->delete(parse_url($url, PHP_URL_PATH));
        } catch (\Throwable $e) {
            logger()->warning('AdminBook deleteS3 failed: ' . $url);
        }
    }
}
