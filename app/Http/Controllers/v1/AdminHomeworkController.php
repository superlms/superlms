<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\HomeWork;
use App\Models\Admin\HomeWorkCompletion;
use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use App\Models\Teacher\TeacherSubject;
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
    // Homework older than this is purged nightly, so the status register and
    // the date strip stop there (the panel's STATUS_DATE_WINDOW_DAYS).
    private const STATUS_WINDOW_DAYS = 30;

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
            ->inClassOrder()->get(['id', 'name'])
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

    /**
     * GET /admin/homework?search=&teacher_id=&standard_id=&section_id=&subject_id=&per_page=&page=
     *
     * Also, as the panel's Homework tab filters: `date` (Y-m-d, the day it was
     * assigned) and `teacher` (a teacher's user id, read as the panel reads its
     * teacher filter — what they entered, and the homework for the subjects
     * they teach in the class and section). `teacher_id` keeps its old meaning.
     */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = HomeWork::with(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name,role'])
            ->where('organization_id', $orgId);

        $this->applyListFilters($query, $request, $orgId);

        if ($request->filled('date')) $query->whereDate('created_at', $request->input('date'));

        $paginator = $query->orderBy('created_at', 'desc')->paginate((int) $request->input('per_page', 15));

        $items = collect($paginator->items())->map(fn ($h) => $this->present($h));

        return $this->paginated($items, $this->paginationMeta($paginator), 'Homework fetched.');
    }

    /**
     * GET /admin/homework/days?standard_id=&section_id=&subject_id=&teacher=&search=
     *
     * How many homework each of the last 30 days holds under the same filters
     * as the list (all but the day) — the days the app's date strip marks.
     * Homework older than that is purged nightly, so there is nothing further.
     */
    public function days(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $query = HomeWork::where('organization_id', $orgId)
            ->whereDate('created_at', '>=', Carbon::today()->subDays(self::STATUS_WINDOW_DAYS)->toDateString());

        $this->applyListFilters($query, $request, $orgId);

        $dates = $query->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($n) => (int) $n);

        return $this->success(['dates' => (object) $dates->all()], 'Homework days fetched.');
    }

    /** The list's filters, bar the day: search, teacher, class, section, subject. */
    private function applyListFilters($query, Request $request, int $orgId): void
    {
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

        // The panel's teacher filter: what the teacher entered, and the
        // homework for the subjects they are assigned in this class/section,
        // whoever entered it.
        if ($request->filled('teacher')) {
            $teacherId  = (int) $request->input('teacher');
            $subjectIds = $this->subjectsAssignedTo(
                $orgId,
                $teacherId,
                $request->filled('standard_id') ? (int) $request->standard_id : null,
                $request->filled('section_id') ? (int) $request->section_id : null,
            );
            $query->where(function ($q) use ($teacherId, $subjectIds) {
                $q->where('user_id', $teacherId);
                if ($subjectIds) $q->orWhereIn('subject_id', $subjectIds);
            });
        }
    }

    /**
     * The subjects a teacher teaches in a class and section — from the
     * timetable and from their subject assignment, as the panel's
     * Homework::subjectsAssignedTo() reads them.
     *
     * @return array<int,int>
     */
    private function subjectsAssignedTo(int $orgId, int $teacherUserId, ?int $standardId, ?int $sectionId): array
    {
        $teacherDetailId = TeacherDetail::where('organization_id', $orgId)
            ->where('user_id', $teacherUserId)
            ->value('id');

        if (!$teacherDetailId) return [];

        $fromTimetable = TeacherTimeTable::where('organization_id', $orgId)
            ->where('teacher_detail_id', $teacherDetailId)
            ->when($standardId, fn ($q) => $q->where('standard_id', $standardId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->pluck('subject_id');

        $fromSubjects = TeacherSubject::where('teacher_detail_id', $teacherDetailId)
            ->when($standardId, fn ($q) => $q->where(fn ($w) =>
                $w->whereNull('standard_id')->orWhere('standard_id', $standardId)))
            ->when($sectionId, fn ($q) => $q->where(fn ($w) =>
                $w->whereNull('section_id')->orWhere('section_id', $sectionId)))
            ->pluck('subject_id');

        return $fromTimetable->merge($fromSubjects)
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
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
            // The panel's "Set by": a teacher (and who), or the school's admin.
            'set_by'      => $h->user?->name,
            'set_by_role' => $h->user?->role,
            'created_time_label' => $h->created_at?->format('d M Y, h:i A'),
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
            $homework->load(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name,role']);

            return $this->success(['homework' => $this->present($homework)], 'Homework added successfully!', 201);
        } catch (\Throwable $e) {
            return $this->error('Error saving homework: ' . $e->getMessage(), 500);
        }
    }

    /**
     * "All subjects" bulk create — one row per filled-in subject. Each may
     * carry its own attachment (≤1 MB) as `files[<subject_id>]` in a multipart
     * request, as each subject can on the panel; a JSON request has none.
     */
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

        // Each subject's attachment is checked before anything is saved, with
        // the panel's words.
        $fileRules = [];
        $fileMessages = [];
        foreach ($items as $row) {
            $sid = (int) ($row['subject_id'] ?? 0);
            if ($sid && $request->hasFile("files.{$sid}")) {
                $name = Subject::whereKey($sid)->value('name') ?? 'Subject';
                $fileRules["files.{$sid}"] = self::FILE_RULE;
                $fileMessages["files.{$sid}.max"] = "{$name}: attachment must be 1 MB (1024 KB) or smaller.";
            }
        }
        if ($fileRules && ($err = $this->validateWith($request, $fileRules, $fileMessages))) return $err;

        try {
            $created = DB::transaction(function () use ($items, $request, $user) {
                $count = 0;
                foreach ($items as $row) {
                    $sid  = (int) ($row['subject_id'] ?? 0);
                    $data = [
                        'title'           => trim((string) $row['title']),
                        'standard_id'     => $request->standard_id,
                        'section_id'      => $request->section_id ?: 0,
                        'subject_id'      => $sid,
                        'description'     => trim((string) ($row['description'] ?? '')),
                        'user_id'         => $user->id,
                        'organization_id' => $user->organization_id,
                    ];
                    if ($sid && $request->hasFile("files.{$sid}")) {
                        $data['file'] = $this->storeFile($request->file("files.{$sid}"));
                    }
                    HomeWork::create($data);
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
            $homework->load(['standard:id,name', 'section:id,name', 'subject:id,name', 'user:id,name,role']);

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
            app(\App\Services\TeacherPushNotifier::class)->homeworkBySchool($homework, 'deleted');
            return $this->success(null, 'Homework deleted successfully!');
        } catch (\Throwable $e) {
            return $this->error('Error deleting homework: ' . $e->getMessage(), 500);
        }
    }

    // ══════════════════════════ STATUS REGISTER ══════════════════════════

    /**
     * GET /admin/homework/status?standard_id=&section_id=&student_id=&days=&date=&subject_id=
     * Per-student day-by-day completion register (today → `days` days back).
     *
     * As the panel's Homework Status tab: only the class and section are
     * needed. With a student it reads day by day (mode by_day, as before);
     * without one, student by student across the scope (mode by_student). A
     * `date` narrows it to that day (within the last 30), a `subject_id` to
     * that subject. The answer also carries the section's students and
     * subjects for the pickers, and the scope in words.
     */
    public function status(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'student_id'  => 'nullable|integer',
            'subject_id'  => 'nullable|integer',
            'date'        => 'nullable|date',
        ])) return $err;

        $orgId = $user->organization_id;
        $days  = max(1, min(60, (int) $request->input('days', 14)));

        // One day, when asked — kept inside the last 30 days, as the panel's
        // picker is (older homework has been purged); otherwise the recent window.
        $date = null;
        if ($request->filled('date')) {
            $date = Carbon::parse($request->input('date'))->startOfDay();
            $min  = Carbon::today()->subDays(self::STATUS_WINDOW_DAYS);
            if ($date->lt($min)) $date = $min;
            if ($date->gt(Carbon::today())) $date = Carbon::today();
        }

        $subjectId = $request->filled('subject_id') ? (int) $request->subject_id : null;

        // The section's students and subjects — the panel's pickers.
        $students = StudentDetail::where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->whereNotNull('user_id')
            ->orderBy('full_name')
            ->get(['id', 'user_id', 'full_name', 'roll_no']);
        $subjects = $this->subjectsFor($orgId, (int) $request->standard_id, (int) $request->section_id);

        $scope = $date ? $date->format('l, d M Y') : 'Last ' . $days . ' days';
        if ($subjectId && ($subject = $subjects->firstWhere('id', $subjectId))) $scope .= ' · ' . $subject->name;

        $pickers = [
            'scope'    => $scope,
            'date'     => $date?->toDateString(),
            'window_start' => Carbon::today()->subDays(self::STATUS_WINDOW_DAYS)->toDateString(),
            'students' => $students->map(fn ($s) => ['id' => $s->id, 'name' => $s->full_name, 'roll_no' => $s->roll_no])->values(),
            'subjects' => $subjects->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ];

        $startDate = Carbon::today()->subDays($days);

        $homeworks = HomeWork::with('subject:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->when($subjectId, fn ($q) => $q->where('subject_id', $subjectId))
            ->when(
                $date,
                fn ($q) => $q->whereDate('created_at', $date->toDateString()),
                fn ($q) => $q->whereDate('created_at', '>=', $startDate->toDateString()),
            )
            ->orderBy('created_at')->get();

        // No student: the panel's register student by student, across the scope.
        if (!$request->filled('student_id')) {
            $done = [];
            foreach (HomeWorkCompletion::whereIn('home_work_id', $homeworks->pluck('id'))->get(['user_id', 'home_work_id']) as $c) {
                $done[$c->user_id][$c->home_work_id] = true;
            }

            $rows = $students->map(function ($s) use ($homeworks, $done) {
                $mine  = $done[$s->user_id] ?? [];
                $items = $homeworks->map(fn ($h) => [
                    'subject'  => $h->subject->name ?? 'General',
                    'title'    => $h->title,
                    'date'     => Carbon::parse($h->created_at)->format('d M'),
                    'complete' => isset($mine[$h->id]),
                ])->values()->all();

                return [
                    'student_id' => $s->id,
                    'name'       => $s->full_name,
                    'roll_no'    => $s->roll_no,
                    'items'      => $items,
                    'completed'  => count(array_filter($items, fn ($i) => $i['complete'])),
                    'total'      => count($items),
                ];
            })->values()->all();

            return $this->success(
                ['mode' => 'by_student', 'student' => null, 'days' => $days, 'rows' => $rows] + $pickers,
                'Homework status fetched.'
            );
        }

        $student = StudentDetail::where('organization_id', $orgId)->find($request->student_id);
        if (!$student) return $this->error('Student not found.', 404);

        $completedSet = [];
        if ($student->user_id) {
            $completedSet = HomeWorkCompletion::where('user_id', $student->user_id)
                ->whereIn('home_work_id', $homeworks->pluck('id'))
                ->pluck('home_work_id')->flip()->toArray();
        }

        $byDate = $homeworks->groupBy(fn ($h) => Carbon::parse($h->created_at)->toDateString());

        // A single day shows just that day; otherwise the window, today first.
        $dates = $date ? [$date] : array_map(fn ($i) => Carbon::today()->subDays($i), range(0, $days));

        $rows = [];
        foreach ($dates as $day) {
            $items = $byDate->get($day->toDateString(), collect())->map(fn ($h) => [
                'subject'  => $h->subject->name ?? 'General',
                'title'    => $h->title,
                'complete' => isset($completedSet[$h->id]),
            ])->values()->all();

            $rows[] = [
                'date'  => $day->format('d M Y'),
                'day'   => $day->format('l'),
                'items' => $items,
            ];
        }

        return $this->success([
            'student' => ['id' => $student->id, 'name' => $student->full_name, 'roll_no' => $student->roll_no],
            'days'    => $days,
            'rows'    => $rows,
            'mode'    => 'by_day',
        ] + $pickers, 'Homework status fetched.');
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
