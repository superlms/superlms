<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * School-admin Homework module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Homework.php — filtered listing, single/all-subjects
 * create, edit, delete, statistics and the per-student completion "status" register.
 * Org-scoped, role-gated to admin / sub-admin.
 */
class AdminHomeworkController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];
    private const FILE_RULE = 'file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png|max:1024';

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

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/homework/lookups — classes (with sections) + active teachers. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->orderBy('id')->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'sections' => Section::where('standard_id', $s->id)->where('is_active', true)
                    ->orderBy('id')->get(['id', 'name'])->toArray(),
            ]);

        $teachers = User::where('organization_id', $orgId)
            ->where('role', 'teacher')->where('is_active', true)
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name]);

        return $this->success(['classes' => $classes, 'teachers' => $teachers], 'Homework lookups fetched.');
    }

    /** GET /admin/homework/subjects?standard_id=&section_id= — subjects for a class/section. */
    public function subjects(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['standard_id' => 'required|integer'])) return $err;

        $subjects = $this->subjectsFor($user->organization_id, (int) $request->standard_id, $request->section_id ? (int) $request->section_id : null);

        return $this->success(['subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values()], 'Subjects fetched.');
    }

    private function subjectsFor(int $orgId, int $standardId, ?int $sectionId)
    {
        if ($sectionId) {
            return Subject::join('section_subjects', 'subjects.id', '=', 'section_subjects.subject_id')
                ->where('section_subjects.section_id', $sectionId)
                ->where('section_subjects.standard_id', $standardId)
                ->where('subjects.organization_id', $orgId)
                ->where('subjects.is_active', true)
                ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
        }
        return Subject::join('standard_subjects', 'subjects.id', '=', 'standard_subjects.subject_id')
            ->where('standard_subjects.standard_id', $standardId)
            ->where('subjects.organization_id', $orgId)
            ->where('subjects.is_active', true)
            ->select('subjects.*')->distinct()->orderBy('subjects.name')->get();
    }

    // ══════════════════════════ STATS ══════════════════════════

    /** GET /admin/homework/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $startOfWeek = Carbon::now()->startOfWeek();

        return $this->success([
            'total'      => HomeWork::where('organization_id', $orgId)->count(),
            'this_week'  => HomeWork::where('organization_id', $orgId)->where('created_at', '>=', $startOfWeek)->count(),
            'by_teacher' => User::where('organization_id', $orgId)->whereHas('homeworks')->distinct()->count('id'),
            'by_class'   => Standard::where('organization_id', $orgId)->whereHas('homeworks')->distinct()->count('id'),
        ], 'Homework stats fetched.');
    }

    // ══════════════════════════ LIST ══════════════════════════

    /** GET /admin/homework?search=&teacher_id=&standard_id=&section_id=&subject_id=&per_page=&page= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = HomeWork::with(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name'])
            ->where('organization_id', $orgId);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('standard', fn ($st) => $st->where('name', 'like', "%{$s}%"))
                    ->orWhereHas('subject', fn ($su) => $su->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('teacher_id'))  $query->where('user_id', $request->teacher_id);
        if ($request->filled('standard_id')) $query->where('standard_id', $request->standard_id);
        if ($request->filled('section_id'))  $query->where('section_id', $request->section_id);
        if ($request->filled('subject_id'))  $query->where('subject_id', $request->subject_id);

        $paginator = $query->orderBy('created_at', 'desc')->paginate((int) $request->input('per_page', 15));

        $items = collect($paginator->items())->map(fn ($h) => $this->present($h));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Homework fetched.');
    }

    private function present(HomeWork $h): array
    {
        return [
            'id'          => $h->id,
            'title'       => $h->title,
            'description' => $h->description,
            'file'        => $h->file,
            'standard_id' => $h->standard_id,
            'section_id'  => $h->section_id ?: null,
            'subject_id'  => $h->subject_id ?: null,
            'standard'    => $h->standard?->name ?? '—',
            'section'     => $h->section?->name ?? null,
            'subject'     => $h->subject?->name ?? 'General',
            'teacher'     => $h->user?->name ?? '—',
            'created_at'  => $h->created_at?->toIso8601String(),
            'created_label' => $h->created_at?->format('d M Y'),
        ];
    }

    // ══════════════════════════ CREATE / UPDATE ══════════════════════════

    /**
     * POST /admin/homework (multipart)
     * Single: title, standard_id, section_id?, subject_id, description, file?
     * All subjects: mode=all, standard_id, section_id?, items=[{subject_id,title,description}] (JSON string or array).
     */
    public function store(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        if ($request->input('mode') === 'all') {
            return $this->storeAll($request, $user);
        }

        if ($err = $this->validateWith($request, [
            'title'       => 'required|string|max:255',
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'description' => 'required|string',
            'file'        => 'nullable|' . self::FILE_RULE,
        ], ['file.max' => 'Attachment must be 1 MB (1024 KB) or smaller.'])) return $err;

        try {
            // section_id / subject_id are NOT NULL (default 0) on home_works, so
            // "none" is stored as 0 — passing null would violate the constraint.
            $data = [
                'title'           => $request->title,
                'standard_id'     => $request->standard_id,
                'section_id'      => $request->section_id ?: 0,
                'subject_id'      => $request->subject_id,
                'description'     => $request->description,
                'user_id'         => $user->id,
                'organization_id' => $orgId,
            ];

            if ($request->hasFile('file')) {
                $data['file'] = $this->storeFile($request->file('file'));
            }

            $homework = HomeWork::create($data);
            $homework->load(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name']);

            return $this->success(['homework' => $this->present($homework)], 'Homework added successfully!', 201);
        } catch (\Throwable $e) {
            return $this->error('Error saving homework: ' . $e->getMessage(), 500);
        }
    }

    /** "All subjects" bulk create — one row per filled-in subject (no per-row files). */
    private function storeAll(Request $request, $user)
    {
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
        ])) return $err;

        $items = $request->input('items');
        if (is_string($items)) $items = json_decode($items, true) ?: [];
        $items = collect((array) $items)
            ->filter(fn ($r) => trim((string) ($r['title'] ?? '')) !== '')
            ->values();

        if ($items->isEmpty()) {
            return $this->error('Please fill homework for at least one subject.', 422);
        }

        try {
            $created = DB::transaction(function () use ($items, $request, $user) {
                $count = 0;
                foreach ($items as $row) {
                    HomeWork::create([
                        'title'           => trim((string) $row['title']),
                        'standard_id'     => $request->standard_id,
                        'section_id'      => $request->section_id ?: 0,
                        'subject_id'      => (int) ($row['subject_id'] ?? 0),
                        'description'     => trim((string) ($row['description'] ?? '')),
                        'user_id'         => $user->id,
                        'organization_id' => $user->organization_id,
                    ]);
                    $count++;
                }
                return $count;
            });

            return $this->success(['created' => $created], "Homework added for {$created} subject(s)!", 201);
        } catch (\Throwable $e) {
            return $this->error('Error saving homework: ' . $e->getMessage(), 500);
        }
    }

    /** POST /admin/homework/{id} (multipart) — update a single homework. */
    public function update(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $homework = HomeWork::where('organization_id', $user->organization_id)->find($id);
        if (!$homework) return $this->error('Homework not found.', 404);

        if ($err = $this->validateWith($request, [
            'title'       => 'required|string|max:255',
            'standard_id' => 'required|exists:standards,id',
            'section_id'  => 'nullable|exists:sections,id',
            'subject_id'  => 'required|exists:subjects,id',
            'description' => 'required|string',
            'file'        => 'nullable|' . self::FILE_RULE,
        ], ['file.max' => 'Attachment must be 1 MB (1024 KB) or smaller.'])) return $err;

        try {
            $data = [
                'title'       => $request->title,
                'standard_id' => $request->standard_id,
                'section_id'  => $request->section_id ?: 0,
                'subject_id'  => $request->subject_id,
                'description' => $request->description,
            ];

            if ($request->hasFile('file')) {
                if ($homework->file) $this->deleteFile($homework->file);
                $data['file'] = $this->storeFile($request->file('file'));
            }

            $homework->update($data);
            $homework->load(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name']);

            return $this->success(['homework' => $this->present($homework)], 'Homework updated successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error updating homework: ' . $e->getMessage(), 500);
        }
    }

    /** DELETE /admin/homework/{id} */
    public function destroy($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $homework = HomeWork::where('organization_id', $user->organization_id)->find($id);
        if (!$homework) return $this->error('Homework not found.', 404);

        try {
            if ($homework->file) $this->deleteFile($homework->file);
            $homework->delete();
            return $this->success(null, 'Homework deleted successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error deleting homework: ' . $e->getMessage(), 500);
        }
    }

    // ══════════════════════════ STATUS REGISTER ══════════════════════════

    /**
     * GET /admin/homework/status?standard_id=&section_id=&student_id=&days=
     * Per-student day-by-day completion register (today → `days` days back).
     */
    public function status(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'student_id'  => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $days  = max(1, min(60, (int) $request->input('days', 14)));

        $student = StudentDetail::where('organization_id', $orgId)->find($request->student_id);
        if (!$student) return $this->error('Student not found.', 404);

        $startDate = Carbon::today()->subDays($days);

        $homeworks = HomeWork::with('subject:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->whereDate('created_at', '>=', $startDate->toDateString())
            ->orderBy('created_at')->get();

        $completedSet = [];
        if ($student->user_id) {
            $completedSet = HomeWorkCompletion::where('user_id', $student->user_id)
                ->whereIn('home_work_id', $homeworks->pluck('id'))
                ->pluck('home_work_id')->flip()->toArray();
        }

        $byDate = $homeworks->groupBy(fn ($h) => Carbon::parse($h->created_at)->toDateString());

        $rows = [];
        for ($i = 0; $i <= $days; $i++) {
            $date = Carbon::today()->subDays($i);
            $items = $byDate->get($date->toDateString(), collect())->map(fn ($h) => [
                'subject'  => $h->subject->name ?? 'General',
                'title'    => $h->title,
                'complete' => isset($completedSet[$h->id]),
            ])->values()->all();

            $rows[] = [
                'date'  => $date->format('d M Y'),
                'day'   => $date->format('l'),
                'items' => $items,
            ];
        }

        return $this->success([
            'student' => ['id' => $student->id, 'name' => $student->full_name, 'roll_no' => $student->roll_no],
            'days'    => $days,
            'rows'    => $rows,
        ], 'Homework status fetched.');
    }

    // ══════════════════════════ FILE HELPERS ══════════════════════════

    private function storeFile($file): string
    {
        $path = $file->store('admin/homework/files', 's3');
        Storage::disk('s3')->setVisibility($path, 'public');
        return Storage::disk('s3')->url($path);
    }

    private function deleteFile(string $url): void
    {
        try {
            Storage::disk('s3')->delete(ltrim(parse_url($url, PHP_URL_PATH), '/'));
        } catch (\Throwable $e) {
            logger()->warning('Homework file delete failed: ' . $e->getMessage());
        }
    }
}
