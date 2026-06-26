<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\Book;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;

class BookController extends ApiController
{
    /**
     * GET /api/v1/books
     *
     * Returns books auto-scoped by user role. The response payload is shaped
     * to match the front-end book card directly — every field needed to render
     * a card AND to open the PDF is included in a single call (no separate
     * detail request required for "tap to read").
     *
     * Student (role=user) — books for their class + their section (or
     * section-less / shared books for that class). Each item carries:
     *   id, title, cover_url, pdf_url, subject{ id, name }
     *   plus standard/section for completeness.
     *
     * Teacher — books for the (class, subject) pairs they teach via the
     * timetable. Each item carries:
     *   id, title, cover_url, pdf_url, subject{ id, name },
     *   standard{ id, name }, section{ id, name }
     *
     * Optional filters: standard_id, section_id, subject_id, search.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $query = Book::with(['standard:id,name', 'section:id,name', 'subject:id,name,image'])
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true);

        $this->scopeByRole($query, $user);

        if ($request->filled('standard_id')) {
            $query->where('standard_id', $request->standard_id);
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $books = $query->latest()->paginate((int) $request->get('per_page', 20));

        // List response carries pdf_url too — the card "open" action goes
        // straight to the PDF without an extra round-trip.
        $items = $books->getCollection()->map(fn($b) => $this->formatBook($b, withPdf: true));

        return $this->paginated($items, $this->paginationMeta($books), 'Books fetched successfully.');
    }

    /**
     * GET /api/v1/books/{id}
     *
     * Returns a single book with PDF URL (role-scoped).
     */
    public function show(int $id)
    {
        [$user, $err] = $this->authUser();
        if ($err) return $err;

        $query = Book::with(['standard:id,name', 'section:id,name', 'subject:id,name,image'])
            ->where('organization_id', $user->organization_id)
            ->where('is_active', true);

        $this->scopeByRole($query, $user);

        $book = $query->find($id);

        if (!$book) {
            return $this->error('Book not found.', 404);
        }

        return $this->success($this->formatBook($book, withPdf: true), 'Book fetched successfully.');
    }

    /**
     * Apply role-based access scope:
     *   - Student → standard + (section_id matches OR section_id is null/all)
     *   - Teacher → matches any of their (standard_id, subject_id) assignments
     */
    private function scopeByRole($query, $user): void
    {
        if ($user->role === 'user') {
            $student = StudentDetail::where('user_id', $user->id)->first(['standard_id', 'section_id']);
            if (!$student) {
                $query->whereRaw('1 = 0'); // no class → no books
                return;
            }
            $query->where('standard_id', $student->standard_id);
            if ($student->section_id) {
                $query->where(function ($q) use ($student) {
                    $q->where('section_id', $student->section_id)
                      ->orWhereNull('section_id');
                });
            }
        } elseif ($user->role === 'teacher') {
            $teacher = TeacherDetail::where('user_id', $user->id)->first(['id']);
            if (!$teacher) {
                $query->whereRaw('1 = 0');
                return;
            }

            // Teachers are assigned (class, subject) via the timetable. The old
            // code read standard_id from teacher_subjects, which has no such
            // column — causing a 500. Derive the pairs from the timetable.
            $pairs = TeacherTimeTable::where('teacher_detail_id', $teacher->id)
                ->get(['standard_id', 'subject_id'])
                ->filter(fn($r) => $r->standard_id && $r->subject_id)
                ->map(fn($r) => $r->standard_id . '-' . $r->subject_id)
                ->unique()
                ->values()
                ->toArray();

            if (empty($pairs)) {
                $query->whereRaw('1 = 0');
                return;
            }
            $query->whereIn(\DB::raw('CONCAT(standard_id, "-", subject_id)'), $pairs);
        }
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Build the JSON payload for a book row.
     *
     * Field reference for front-end cards:
     *   title      → "book name" on the card
     *   cover_url  → "cover image" on the card (alias of book_logo column)
     *   subject    → { id, name } — subject name on the card
     *   standard   → { id, name } — class name (shown on teacher card)
     *   section    → { id, name } — section name (shown on teacher card)
     *   pdf_url    → tap-to-open PDF target
     *
     * `logo_url` is retained as a backwards-compat alias for `cover_url`
     * so old clients don't break while new clients migrate to the
     * clearer field name.
     */
    private function formatBook(Book $book, bool $withPdf = false): array
    {
        $cover = $book->book_logo;

        $data = [
            'id'        => $book->id,
            'title'     => $book->title,
            'cover_url' => $cover,
            'logo_url'  => $cover, // alias — back-compat
            'standard'  => $book->standard ? ['id' => $book->standard->id, 'name' => $book->standard->name] : null,
            'section'   => $book->section  ? ['id' => $book->section->id,  'name' => $book->section->name]  : null,
            'subject'   => $book->subject  ? [
                'id'    => $book->subject->id,
                'name'  => $book->subject->name,
                // Subject icon image (full S3 URL or null). Shown on the book card.
                'image' => $book->subject->image ?? null,
            ] : null,
        ];

        if ($withPdf) {
            $data['pdf_url'] = $book->pdf_file;
        }

        return $data;
    }
}
