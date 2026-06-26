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
            ->orderBy('name')->get(['id', 'name'])
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

        TeacherTimeTable::where('organization_id', $user->organization_id)
            ->where('standard_id', $request->standard_id)
            ->where('section_id', $request->section_id)
            ->delete();

        return $this->success(null, 'Section timetable removed.');
    }
}
