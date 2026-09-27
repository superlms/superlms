<?php

namespace App\Http\Controllers\v1;

use App\Models\Admin\TeacherTimeTable;
use App\Models\Student\Section;
use App\Models\Student\SectionSubject;
use App\Models\Student\Standard;
use App\Models\Student\Subject;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * School-admin Timetable module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/TimeTable.php — class/teacher views, the per-section
 * builder (one row per subject with a teacher per weekday), conflict checks and
 * wipe-and-recreate save. Org-scoped, role-gated to admin / sub-admin.
 */
class AdminTimetableController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];
    private const DAYS = [1, 2, 3, 4, 5, 6];
    private const DAY_NAMES = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];

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

    /** GET /admin/timetable/lookups — classes (with sections) + active teachers. */
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
                    ->orderBy('name')->get(['id', 'name'])->toArray(),
            ]);

        $teachers = TeacherDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn ($q) => $q->where('is_active', 1))
            ->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? '—']);

        return $this->success(['classes' => $classes, 'teachers' => $teachers, 'days' => self::DAY_NAMES], 'Timetable lookups fetched.');
    }

    /** GET /admin/timetable/stats */
    public function stats()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        return $this->success([
            'schedules' => TeacherTimeTable::where('organization_id', $orgId)->count(),
            'teachers'  => TeacherDetail::where('organization_id', $orgId)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count(),
            'classes'   => Standard::where('organization_id', $orgId)->where('is_active', true)->count(),
            'subjects'  => Subject::where('organization_id', $orgId)->where('is_active', true)->count(),
        ], 'Timetable stats fetched.');
    }

    // ══════════════════════════ VIEW (class / teacher) ══════════════════════════

    /** GET /admin/timetable?view=class|teacher&standard_id=&section_id=&teacher_id=&days[]= */
    public function index(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $view  = $request->input('view') === 'teacher' ? 'teacher' : 'class';
        $days  = (array) $request->input('days', []);

        $entries = collect();
        if ($view === 'class' && $request->filled('standard_id') && $request->filled('section_id')) {
            $entries = TeacherTimeTable::with(['teacher.user:id,name', 'standard:id,name', 'section:id,name', 'subject:id,name,code'])
                ->where('organization_id', $orgId)
                ->where('standard_id', $request->standard_id)
                ->where('section_id', $request->section_id)
                ->when(!empty($days), fn ($q) => $q->whereIn('day_of_week', $days))
                ->get();
        } elseif ($view === 'teacher' && $request->filled('teacher_id')) {
            $entries = TeacherTimeTable::with(['teacher.user:id,name', 'standard:id,name', 'section:id,name', 'subject:id,name,code'])
                ->where('organization_id', $orgId)
                ->where('teacher_detail_id', $request->teacher_id)
                ->when(!empty($days), fn ($q) => $q->whereIn('day_of_week', $days))
                ->get();
        }

        $cards = collect();
        if ($entries->isNotEmpty()) {
            $cards = $entries->groupBy(fn ($e) => $e->standard_id . '|' . ($e->section_id ?? ''))
                ->map(function ($items) {
                    $first = $items->first();
                    $groups = $items->groupBy(fn ($e) => $e->subject_id . '|' . $e->start_time . '|' . $e->end_time)
                        ->map(function ($g) {
                            $byTeacher = $g->groupBy('teacher_detail_id')->map(function ($items) {
                                $f = $items->first();
                                return [
                                    'teacher_name' => $f->teacher?->user?->name ?? '—',
                                    'days'         => $items->pluck('day_of_week')->map(fn ($d) => (int) $d)->sort()->values()->all(),
                                ];
                            })->sortByDesc(fn ($t) => count($t['days']))->values()->all();
                            $f = $g->first();
                            return [
                                'subject'    => $f->subject?->name ?? '—',
                                'start_time' => substr((string) $f->start_time, 0, 5),
                                'end_time'   => substr((string) $f->end_time, 0, 5),
                                'teachers'   => $byTeacher,
                                'days'       => $g->pluck('day_of_week')->map(fn ($d) => (int) $d)->unique()->sort()->values()->all(),
                            ];
                        })->sortBy('start_time')->values();
                    return [
                        'standard_id' => $first->standard_id,
                        'section_id'  => $first->section_id,
                        'standard'    => $first->standard?->name ?? '—',
                        'section'     => $first->section?->name ?? '—',
                        'subject_groups' => $groups,
                    ];
                })->values();
        }

        return $this->success(['view' => $view, 'cards' => $cards, 'day_names' => self::DAY_NAMES], 'Timetable fetched.');
    }

    // ══════════════════════════ BUILDER ══════════════════════════

    /**
     * GET /admin/timetable/builder?standard_id=&section_id=
     * Returns one row per subject (with any existing per-day teacher assignments)
     * plus is_edit flag — mirrors buildScheduleRowsFromSection + prefill.
     */
    public function builder(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;

        // Subjects mapped to this section, else fall back to all active subjects.
        $subjects = SectionSubject::with('subject')
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->get()->pluck('subject')->filter()->unique('id')->values();
        if ($subjects->isEmpty()) {
            $subjects = Subject::where('organization_id', $orgId)->where('is_active', true)->orderBy('name')->get();
        }

        $rows = [];
        foreach ($subjects as $s) {
            $rows[(int) $s->id] = [
                'subject_id'   => (int) $s->id,
                'subject_name' => $s->name,
                'start_time'   => '09:00',
                'end_time'     => '10:00',
                'day_teachers' => array_fill_keys(self::DAYS, null),
            ];
        }

        // Prefill from existing entries.
        $existing = TeacherTimeTable::with('subject:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->get();

        $isEdit = $existing->isNotEmpty();
        foreach ($existing->groupBy(fn ($r) => $r->subject_id . '|' . substr((string) $r->start_time, 0, 5) . '|' . substr((string) $r->end_time, 0, 5)) as $group) {
            $first = $group->first();
            $sid = (int) $first->subject_id;
            $dayTeachers = array_fill_keys(self::DAYS, null);
            foreach ($group as $entry) {
                $day = (int) $entry->day_of_week;
                if (array_key_exists($day, $dayTeachers)) {
                    $dayTeachers[$day] = (int) $entry->teacher_detail_id;
                }
            }
            $rows[$sid] = [
                'subject_id'   => $sid,
                'subject_name' => $first->subject?->name ?? ($rows[$sid]['subject_name'] ?? 'Subject'),
                'start_time'   => substr((string) $first->start_time, 0, 5),
                'end_time'     => substr((string) $first->end_time, 0, 5),
                'day_teachers' => $dayTeachers,
            ];
        }

        return $this->success(['is_edit' => $isEdit, 'rows' => array_values($rows), 'days' => self::DAY_NAMES], 'Builder loaded.');
    }

    // ══════════════════════════ SAVE ══════════════════════════

    /**
     * POST /admin/timetable
     * standard_id, section_id, is_edit?, rows:[{subject_id, start_time, end_time, day_teachers:{1:teacherId|null,...}}]
     */
    public function save(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id'             => 'required|integer',
            'section_id'              => 'required|integer',
            'is_edit'                 => 'nullable|boolean',
            'rows'                    => 'required|array',
            'rows.*.subject_id'       => 'required|integer',
            'rows.*.start_time'       => 'required|string',
            'rows.*.end_time'         => 'required|string',
        ])) return $err;

        $orgId  = $user->organization_id;
        $isEdit = $request->boolean('is_edit');

        // Keep only rows with at least one teacher chosen.
        $rows = collect($request->rows)->map(function ($r, $i) {
            $r['__idx'] = $i;
            return $r;
        })->filter(function ($r) {
            $dt = $r['day_teachers'] ?? [];
            return collect(self::DAYS)->contains(fn ($d) => !empty($dt[$d] ?? null));
        })->values();

        if ($rows->isEmpty() && !$isEdit) {
            return $this->error('Assign at least one teacher to save the timetable.', 422);
        }

        // Validate time ranges + conflicts.
        foreach ($rows as $row) {
            $name = $row['subject_name'] ?? 'Subject';
            $start = substr((string) $row['start_time'], 0, 5);
            $end   = substr((string) $row['end_time'], 0, 5);
            if (!$start || !$end || $start >= $end) {
                return $this->error("{$name}: invalid time range.", 422);
            }
            foreach (self::DAYS as $day) {
                $teacherId = (int) ($row['day_teachers'][$day] ?? 0);
                if (!$teacherId) continue;
                if ($conflict = $this->cellConflict($orgId, $request->standard_id, $request->section_id, $teacherId, $start, $end, $day, $rows, $row['__idx'])) {
                    return $this->error("{$name} (" . (self::DAY_NAMES[$day] ?? $day) . "): {$conflict}", 422);
                }
            }
        }

        // The class's timetable as it was, so each teacher hears what changed.
        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->timetableSnapshot((int) $orgId, (int) $request->standard_id, (int) $request->section_id);

        try {
            $created = DB::transaction(function () use ($request, $orgId, $user, $rows, $isEdit) {
                if ($isEdit) {
                    TeacherTimeTable::where('organization_id', $orgId)
                        ->where('standard_id', $request->standard_id)
                        ->where('section_id', $request->section_id)
                        ->delete();
                }
                $seen = [];
                $count = 0;
                foreach ($rows as $row) {
                    $start = substr((string) $row['start_time'], 0, 5);
                    $end   = substr((string) $row['end_time'], 0, 5);
                    foreach (self::DAYS as $day) {
                        $teacherId = (int) ($row['day_teachers'][$day] ?? 0);
                        if (!$teacherId) continue;
                        $key = $row['subject_id'] . '|' . $day . '|' . $start . '|' . $end;
                        if (isset($seen[$key])) continue;
                        $seen[$key] = true;
                        TeacherTimeTable::create([
                            'organization_id'   => $orgId,
                            'assigned_by'       => $user->id,
                            'teacher_detail_id' => $teacherId,
                            'standard_id'       => $request->standard_id,
                            'section_id'        => $request->section_id,
                            'subject_id'        => (int) $row['subject_id'],
                            'day_of_week'       => $day,
                            'start_time'        => $start,
                            'end_time'          => $end,
                            'is_active'         => true,
                        ]);
                        $count++;
                    }
                }
                return $count;
            });
        } catch (\Throwable $e) {
            return $this->error('Error saving timetable: ' . $e->getMessage(), 500);
        }
        $push->timetableSaved($before);

        return $this->success(['created' => $created], "{$created} timetable entries " . ($isEdit ? 'updated.' : 'created.'));
    }

    /** Returns a conflict reason for a teacher/day/time, or null when free. */
    private function cellConflict(int $orgId, $standardId, $sectionId, int $teacherId, string $start, string $end, int $day, $rows, $rowIdx): ?string
    {
        // 1) Teacher busy in another class/section at this time on this day.
        $clash = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->where('teacher_detail_id', $teacherId)
            ->where('day_of_week', $day)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(fn ($q) => $q->where('standard_id', '!=', $standardId)->orWhere('section_id', '!=', $sectionId))
            ->first();
        if ($clash) {
            $where = trim(($clash->standard?->name ?? '') . ' ' . ($clash->section?->name ?? ''));
            return 'Busy with ' . ($where !== '' ? $where : 'another class');
        }

        // 2) Class clash within the payload (another subject overlapping this day).
        foreach ($rows as $other) {
            if (($other['__idx'] ?? null) === $rowIdx) continue;
            if (empty($other['day_teachers'][$day] ?? null)) continue;
            $os = substr((string) ($other['start_time'] ?? ''), 0, 5);
            $oe = substr((string) ($other['end_time'] ?? ''), 0, 5);
            if (!$os || !$oe) continue;
            if ($os >= $end || $oe <= $start) continue;
            return 'Class clash with ' . ($other['subject_name'] ?? 'another subject');
        }

        return null;
    }

    // ══════════════════════════ DELETE ══════════════════════════

    /** DELETE /admin/timetable?standard_id=&section_id= */
    public function destroy(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->timetableSnapshot((int) $user->organization_id, (int) $request->standard_id, (int) $request->section_id);

        TeacherTimeTable::where('organization_id', $user->organization_id)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->delete();

        $push->timetableSaved($before);

        return $this->success(null, 'Section timetable removed.');
    }

    // ══════════════════════════ APP: CLASS → SECTION → TIMETABLE ══════════════════════════
    //
    // The app's Timetable (from 2026-09-27) opens on the school's classes, a class
    // on its sections, a section on its week, and adds or edits one the way the
    // panel's form does now: a row per subject · time slot · teacher, with the
    // days it runs. The endpoints above stay as they are for older builds.

    private const DAY_SHORT = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

    /** The class and section of this school, or an error response. */
    private function classAndSection(int $orgId, $standardId, $sectionId): array
    {
        $standard = Standard::where('organization_id', $orgId)->find($standardId);
        if (!$standard) return [null, null, $this->error('Please select a class.', 422)];
        $section = Section::where('standard_id', $standard->id)->find($sectionId);
        if (!$section) return [null, null, $this->error('Please select a section.', 422)];
        return [$standard, $section, null];
    }

    /**
     * GET /admin/timetable/overview — the panel's counts (Total Classes, Total
     * Sections, Timetable Created, Remaining) and each class with its sections,
     * each saying whether it has a timetable and how much of one.
     */
    public function overview()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $standards = Standard::where('organization_id', $orgId)->where('is_active', true)
            ->inClassOrder()->get(['id', 'name']);
        $sections = Section::whereIn('standard_id', $standards->pluck('id'))->where('is_active', true)
            ->orderBy('id')->get(['id', 'name', 'standard_id'])->groupBy('standard_id');

        // Per (class, section): its entries, and its rows as the panel's card counts
        // them — one per subject · time slot.
        $counts = TeacherTimeTable::where('organization_id', $orgId)
            ->get(['standard_id', 'section_id', 'subject_id', 'start_time', 'end_time'])
            ->groupBy(fn ($e) => $e->standard_id . '|' . $e->section_id)
            ->map(fn ($items) => [
                'entries'  => $items->count(),
                'subjects' => $items->unique(fn ($e) => $e->subject_id . '|' . $e->start_time . '|' . $e->end_time)->count(),
            ]);

        $totalSections = $sections->flatten(1)->count();
        $created       = $counts->count(); // distinct (class, section) pairs, as the panel counts them

        $classes = $standards->map(fn ($s) => [
            'id'       => $s->id,
            'name'     => $s->name,
            'sections' => ($sections[$s->id] ?? collect())->map(function ($sec) use ($s, $counts) {
                $c = $counts[$s->id . '|' . $sec->id] ?? null;
                return [
                    'id'            => $sec->id,
                    'name'          => $sec->name,
                    'has_timetable' => (bool) $c,
                    'entries'       => $c['entries'] ?? 0,
                    'subjects'      => $c['subjects'] ?? 0,
                ];
            })->values(),
        ])->values();

        // The panel's Teacher View: its active teachers, each with how many
        // periods a week the timetable gives them.
        $periods = TeacherTimeTable::where('organization_id', $orgId)
            ->selectRaw('teacher_detail_id, COUNT(*) as n')->groupBy('teacher_detail_id')
            ->pluck('n', 'teacher_detail_id');
        $teachers = TeacherDetail::with('user:id,name,image,is_active')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn ($q) => $q->where('is_active', 1))
            ->get()
            ->map(fn ($t) => [
                'id'      => $t->id,
                'name'    => $t->user->name ?? '—',
                'image'   => $t->user->image ?? null,
                'periods' => (int) ($periods[$t->id] ?? 0),
            ])
            ->sortBy(fn ($t) => mb_strtolower($t['name']))
            ->values();

        return $this->success([
            'stats' => [
                'classes'   => $standards->count(),
                'sections'  => $totalSections,
                'created'   => $created,
                'remaining' => max(0, $totalSections - $created),
            ],
            'classes'  => $classes,
            'teachers' => $teachers,
        ], 'Timetable overview fetched.');
    }

    /**
     * GET /admin/timetable/section?standard_id=&section_id= — one section's week:
     * every entry with its day, time, subject and teacher (and the teacher's photo),
     * and how many subject rows the panel's card shows for it.
     */
    public function section(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;
        $orgId = $user->organization_id;

        [$standard, $section, $err] = $this->classAndSection($orgId, $request->standard_id, $request->section_id);
        if ($err) return $err;

        $entries = TeacherTimeTable::with(['teacher.user:id,name,image', 'subject:id,name,code'])
            ->where('organization_id', $orgId)
            ->where('standard_id', $standard->id)
            ->where('section_id', $section->id)
            ->get()
            ->sortBy(fn ($e) => sprintf('%d|%s', $e->day_of_week, $e->start_time))
            ->values();

        return $this->success([
            'standard' => ['id' => $standard->id, 'name' => $standard->name],
            'section'  => ['id' => $section->id, 'name' => $section->name],
            'subjects' => $entries->unique(fn ($e) => $e->subject_id . '|' . $e->start_time . '|' . $e->end_time)->count(),
            'entries'  => $entries->map(fn ($e) => [
                'id'            => $e->id,
                'day'           => (int) $e->day_of_week,
                'start_time'    => substr((string) $e->start_time, 0, 5),
                'end_time'      => substr((string) $e->end_time, 0, 5),
                'subject_id'    => $e->subject_id,
                'subject'       => $e->subject?->name ?? '—',
                'teacher_id'    => $e->teacher_detail_id,
                'teacher'       => $e->teacher?->user?->name ?? '—',
                'teacher_image' => $e->teacher?->user?->image,
            ])->values(),
        ], 'Section timetable fetched.');
    }

    /**
     * GET /admin/timetable/form?standard_id=&section_id= — the panel's form for a
     * section: the subjects it can schedule (its mapped subjects, else every
     * active subject), its rows — prefilled from what is saved, one per subject ·
     * time slot · teacher with the days it runs, which makes it an edit; else one
     * blank row per subject — the teachers, and when each teacher is already busy
     * in another class, for the form's live checks.
     */
    public function form(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;
        $orgId = $user->organization_id;

        [$standard, $section, $err] = $this->classAndSection($orgId, $request->standard_id, $request->section_id);
        if ($err) return $err;

        // loadSectionSubjects
        $subjects = SectionSubject::with('subject')
            ->where('organization_id', $orgId)
            ->where('standard_id', $standard->id)
            ->where('section_id', $section->id)
            ->get()->pluck('subject')->filter()->unique('id')->values();
        if ($subjects->isEmpty()) {
            $subjects = Subject::where('organization_id', $orgId)->where('is_active', true)->orderBy('id')->get();
        }
        $subjects = $subjects->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values();

        // prefillRowsFromExisting, else buildScheduleRowsFromSection
        $existing = TeacherTimeTable::with('subject:id,name')
            ->where('organization_id', $orgId)
            ->where('standard_id', $standard->id)
            ->where('section_id', $section->id)
            ->get();

        $rows = [];
        if ($existing->isNotEmpty()) {
            $groups = $existing->groupBy(fn ($r) =>
                $r->subject_id . '|' . substr((string) $r->start_time, 0, 5) . '|' . substr((string) $r->end_time, 0, 5) . '|' . $r->teacher_detail_id
            );
            foreach ($groups as $group) {
                $first = $group->first();
                $rows[] = [
                    'subject_id'   => (int) $first->subject_id,
                    'subject_name' => $first->subject?->name ?? 'Subject',
                    'start_time'   => substr((string) $first->start_time, 0, 5),
                    'end_time'     => substr((string) $first->end_time, 0, 5),
                    'teacher_id'   => (int) $first->teacher_detail_id,
                    'days'         => $group->pluck('day_of_week')->map(fn ($d) => (int) $d)->unique()->sort()->values()->all(),
                ];
            }
        } else {
            foreach ($subjects as $s) {
                $rows[] = [
                    'subject_id'   => $s['id'],
                    'subject_name' => $s['name'],
                    'start_time'   => '09:00',
                    'end_time'     => '10:00',
                    'teacher_id'   => null,
                    'days'         => [],
                ];
            }
        }

        $teachers = TeacherDetail::with('user:id,name,image,is_active')
            ->where('organization_id', $orgId)
            ->whereHas('user', fn ($q) => $q->where('is_active', 1))
            ->get()
            ->map(fn ($t) => ['id' => $t->id, 'name' => $t->user->name ?? '—', 'image' => $t->user->image ?? null])
            ->sortBy(fn ($t) => mb_strtolower($t['name']))
            ->values();

        // Where teachers already are, outside this section — it is wiped and
        // recreated on save, so its own entries never count against it.
        $busy = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->where(fn ($q) => $q->where('standard_id', '!=', $standard->id)->orWhere('section_id', '!=', $section->id))
            ->orderBy('id')
            ->get()
            ->map(fn ($e) => [
                'teacher_id' => (int) $e->teacher_detail_id,
                'day'        => (int) $e->day_of_week,
                'start_time' => substr((string) $e->start_time, 0, 5),
                'end_time'   => substr((string) $e->end_time, 0, 5),
                'where'      => trim(($e->standard?->name ?? '') . ' ' . ($e->section?->name ?? '')),
            ])->values();

        return $this->success([
            'is_edit'  => $existing->isNotEmpty(),
            'standard' => ['id' => $standard->id, 'name' => $standard->name],
            'section'  => ['id' => $section->id, 'name' => $section->name],
            'subjects' => $subjects,
            'rows'     => $rows,
            'teachers' => $teachers,
            'busy'     => $busy,
            'days'     => self::DAY_NAMES,
        ], 'Timetable form loaded.');
    }

    /**
     * POST /admin/timetable/schedule — the panel's save (onSaveTimetable).
     * standard_id, section_id, is_edit?, rows:[{subject_id, start_time, end_time, teacher_id, days:[1..6]}]
     *
     * Rows without a subject, a teacher or a day are left out; each kept row needs
     * a start before its end, days the class is free at that time, and a teacher
     * free then — not busy in another class, nor on another row. An edit wipes the
     * section's timetable and writes it again; an edit with no rows left clears it.
     */
    public function saveSchedule(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if (!$request->filled('standard_id')) return $this->error('Please select a class.', 422);
        if (!$request->filled('section_id')) return $this->error('Please select a section.', 422);
        if ($err = $this->validateWith($request, [
            'standard_id'       => 'required|integer',
            'section_id'        => 'required|integer',
            'is_edit'           => 'nullable|boolean',
            'rows'              => 'nullable|array',
            'rows.*.subject_id' => 'nullable|integer',
            'rows.*.start_time' => 'nullable|string',
            'rows.*.end_time'   => 'nullable|string',
            'rows.*.teacher_id' => 'nullable|integer',
            'rows.*.days'       => 'nullable|array',
        ])) return $err;

        $orgId = (int) $user->organization_id;
        [$standard, $section, $err] = $this->classAndSection($orgId, $request->standard_id, $request->section_id);
        if ($err) return $err;

        // A section that already has a timetable is always an edit, as the panel's
        // form turns into one as soon as it finds the saved rows.
        $hasSaved = TeacherTimeTable::where('organization_id', $orgId)
            ->where('standard_id', $standard->id)->where('section_id', $section->id)->exists();
        $isEdit = $request->boolean('is_edit') || $hasSaved;

        $input      = array_values((array) $request->input('rows', []));
        $subjectIds = collect($input)->pluck('subject_id')->filter()->map(fn ($v) => (int) $v)->unique()->all();
        $teacherIds = collect($input)->pluck('teacher_id')->filter()->map(fn ($v) => (int) $v)->unique()->all();
        $names      = Subject::where('organization_id', $orgId)->whereIn('id', $subjectIds ?: [0])->pluck('name', 'id');
        $ownTeachers = TeacherDetail::where('organization_id', $orgId)->whereIn('id', $teacherIds ?: [0])->pluck('id')->map(fn ($v) => (int) $v)->all();

        $time = fn ($t) => preg_match('/^\d{2}:\d{2}/', (string) $t) ? substr((string) $t, 0, 5) : '';
        $all = [];
        foreach ($input as $i => $r) {
            $sid = (int) ($r['subject_id'] ?? 0);
            $tid = (int) ($r['teacher_id'] ?? 0);
            $all[] = [
                '__idx'        => $i,
                'subject_id'   => isset($names[$sid]) ? $sid : 0,
                'subject_name' => $names[$sid] ?? null,
                'start_time'   => $time($r['start_time'] ?? ''),
                'end_time'     => $time($r['end_time'] ?? ''),
                'teacher_id'   => in_array($tid, $ownTeachers, true) ? $tid : 0,
                'days'         => array_values(array_unique(array_map('intval', (array) ($r['days'] ?? [])))),
            ];
        }

        // Keep only rows that have a subject, a teacher and at least one day.
        $rowsToSave = array_values(array_filter($all, fn ($r) => $r['subject_id'] > 0 && $r['teacher_id'] > 0 && !empty($r['days'])));

        if (empty($rowsToSave) && !$isEdit) {
            return $this->error('Add at least one row with a subject, teacher and days to save.', 422);
        }

        foreach ($rowsToSave as $row) {
            $n = $row['subject_name'] ?? ('Subject ' . ($row['__idx'] + 1));
            if (!$row['start_time'] || !$row['end_time'] || $row['start_time'] >= $row['end_time']) {
                return $this->error("{$n}: invalid time range.", 422);
            }
            $occupiedClash = array_intersect($row['days'], $this->occupiedDaysForRow($all, $row['__idx']));
            if (!empty($occupiedClash)) {
                $dn = self::DAY_NAMES[(int) reset($occupiedClash)] ?? reset($occupiedClash);
                return $this->error("{$n} ({$dn}): the class is already scheduled at this time.", 422);
            }
            if ($conflict = $this->rowConflict($orgId, $standard->id, $section->id, $all, $row['__idx'])) {
                return $this->error("{$n}: {$conflict}", 422);
            }
        }

        // The class's timetable as it was, so each teacher hears what changed.
        $push = app(\App\Services\TeacherPushNotifier::class);
        $before = $push->timetableSnapshot($orgId, (int) $standard->id, (int) $section->id);

        try {
            $created = DB::transaction(function () use ($orgId, $user, $standard, $section, $rowsToSave, $isEdit) {
                if ($isEdit) {
                    TeacherTimeTable::where('organization_id', $orgId)
                        ->where('standard_id', $standard->id)
                        ->where('section_id', $section->id)
                        ->delete();
                }
                $created = 0;
                $seen = []; // no duplicate (subject, day, start, end)
                foreach ($rowsToSave as $row) {
                    foreach ($row['days'] as $day) {
                        if (!in_array($day, self::DAYS, true)) continue;
                        $key = $row['subject_id'] . '|' . $day . '|' . $row['start_time'] . '|' . $row['end_time'];
                        if (isset($seen[$key])) continue;
                        $seen[$key] = true;
                        TeacherTimeTable::create([
                            'organization_id'   => $orgId,
                            'assigned_by'       => $user->id,
                            'teacher_detail_id' => $row['teacher_id'],
                            'standard_id'       => $standard->id,
                            'section_id'        => $section->id,
                            'subject_id'        => $row['subject_id'],
                            'day_of_week'       => $day,
                            'start_time'        => $row['start_time'],
                            'end_time'          => $row['end_time'],
                            'is_active'         => true,
                        ]);
                        $created++;
                    }
                }
                return $created;
            });
        } catch (\Throwable $e) {
            logger()->error('Timetable save error: ' . $e->getMessage());
            return $this->error('Error saving timetable: ' . $e->getMessage(), 500);
        }
        $push->timetableSaved($before);

        return $this->success(['created' => $created, 'is_edit' => $isEdit], "{$created} timetable entries " . ($isEdit ? 'updated.' : 'created.'));
    }

    /** Days another row overlapping this row's time already takes (occupiedDaysForRow). */
    private function occupiedDaysForRow(array $rows, int $rowIndex): array
    {
        $row = collect($rows)->firstWhere('__idx', $rowIndex);
        if (!$row) return [];
        $start = $row['start_time'];
        $end   = $row['end_time'];
        if (!$start || !$end || $start >= $end) return [];

        $occupied = [];
        foreach ($rows as $other) {
            if ($other['__idx'] === $rowIndex) continue;
            $os = $other['start_time'];
            $oe = $other['end_time'];
            if (!$os || !$oe || $os >= $oe) continue;
            if ($os >= $end || $oe <= $start) continue;
            foreach ($other['days'] as $d) $occupied[(int) $d] = true;
        }
        return array_keys($occupied);
    }

    /** The panel's getRowConflict: the row's teacher busy in another class, or on another row, then. */
    private function rowConflict(int $orgId, int $standardId, int $sectionId, array $rows, int $rowIndex): ?string
    {
        $row = collect($rows)->firstWhere('__idx', $rowIndex);
        if (!$row) return null;
        $teacherId = $row['teacher_id'];
        if (!$teacherId) return null;
        $start = $row['start_time'];
        $end   = $row['end_time'];
        if (!$start || !$end || $start >= $end) return null;
        $days = $row['days'];
        if (empty($days)) return null;

        // 1) Already booked in another class/section at this time.
        $clash = TeacherTimeTable::with(['standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->where('teacher_detail_id', $teacherId)
            ->whereIn('day_of_week', $days)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->where(fn ($q) => $q->where('standard_id', '!=', $standardId)->orWhere('section_id', '!=', $sectionId))
            ->orderBy('id')
            ->first();
        if ($clash) {
            $where = trim(($clash->standard?->name ?? '') . ' ' . ($clash->section?->name ?? ''));
            $dn    = self::DAY_SHORT[(int) $clash->day_of_week] ?? $clash->day_of_week;
            return 'Busy with ' . ($where !== '' ? $where : 'another class') . " ({$dn})";
        }

        // 2) The same teacher on another row at an overlapping time and day.
        foreach ($rows as $other) {
            if ($other['__idx'] === $rowIndex) continue;
            if ($other['teacher_id'] !== $teacherId) continue;
            $os = $other['start_time'];
            $oe = $other['end_time'];
            if (!$os || !$oe) continue;
            if ($os >= $end || $oe <= $start) continue;
            $shared = array_intersect($days, $other['days']);
            if (!empty($shared)) {
                $dn = self::DAY_SHORT[(int) reset($shared)] ?? reset($shared);
                return "Teacher already on another subject at this time ({$dn})";
            }
        }
        return null;
    }

    /** GET /admin/timetable/pdf?standard_id=&section_id= — the panel's Download: the week as a grid, A4 landscape. */
    public function pdf(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        return app(\App\Http\Controllers\Admin\TimetablePdfController::class)
            ->download((int) $user->organization_id, (int) $request->standard_id, (int) $request->section_id);
    }

    /** GET /admin/timetable/teacher-pdf?teacher_id= — the panel's Teacher View Download: a teacher's week as a grid. */
    public function teacherPdf(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['teacher_id' => 'required|integer'])) return $err;

        return app(\App\Http\Controllers\Admin\TimetablePdfController::class)
            ->downloadTeacher((int) $user->organization_id, (int) $request->teacher_id);
    }
}
