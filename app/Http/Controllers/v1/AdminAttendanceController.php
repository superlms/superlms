<?php

namespace App\Http\Controllers\v1;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * School-admin Attendance module for the mobile app.
 *
 * Mirrors app/Livewire/Admin/Attendance.php — teacher & student marking, by-date
 * registers, monthly calendars, yearly summaries and class-teacher assignment.
 * Status codes stored in *_attendances.status (tinyint):
 *   1 = present, 0 = absent, 2 = half day, 3 = holiday
 * Org-scoped, role-gated to admin / sub-admin.
 *
 * A request carrying v=2 gets the panel's rules as they are now: a day's rows
 * start blank (Sunday on Holiday) instead of Present, a row left blank saves
 * nothing and clears what was saved, only this school's people are written,
 * an unmarked Sunday reads as a holiday, and a person's months are the
 * panel's month cards over the April → March school year. Without it, every
 * endpoint answers as it always has, for the builds already on phones.
 */
class AdminAttendanceController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private const S_PRESENT = 1;
    private const S_ABSENT  = 0;
    private const S_HALF    = 2;
    private const S_HOLIDAY = 3;
    // The teacher app saves a student's holiday as 4 (its codes: 0 absent,
    // 1 present, 4 holiday). Read it as a holiday too, never as absent.
    private const S_APP_HOLIDAY = 4;

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

    private function toInt(string $label): int
    {
        return match ($label) {
            'present'  => self::S_PRESENT,
            'absent'   => self::S_ABSENT,
            'half_day' => self::S_HALF,
            'holiday'  => self::S_HOLIDAY,
            default    => self::S_ABSENT,
        };
    }

    private function toLabel($int): string
    {
        return match ((int) $int) {
            self::S_PRESENT => 'present',
            self::S_ABSENT  => 'absent',
            self::S_HALF    => 'half_day',
            self::S_HOLIDAY, self::S_APP_HOLIDAY => 'holiday',
            default         => 'absent',
        };
    }

    /**
     * A submitted row that still carries what is saved for it (its remark is
     * only compared when one was sent). Such rows are left exactly as saved,
     * so editing one person never rewrites the rest of the day.
     */
    private function unchanged($rec, array $row): bool
    {
        return $rec
            && $this->toLabel($rec->status) === (string) ($row['status'] ?? '')
            && (!array_key_exists('remark', $row) || (string) $rec->remarks === (string) $row['remark']);
    }

    // ── The panel's rules, for v=2 ───────────────────────────────────────────

    /** The only statuses a row may carry; anything else is not written. */
    private const STATUSES = ['present', 'absent', 'half_day', 'holiday'];

    /** Whether the request asked for the panel's rules as they are now. */
    private function panel(Request $request): bool
    {
        return (int) $request->input('v') >= 2;
    }

    /** Sundays are a standing holiday — nothing has to be marked for them. */
    private function isSunday($date): bool
    {
        return (int) Carbon::parse($date)->dayOfWeek === Carbon::SUNDAY;
    }

    /** What a row starts on: Sunday the holiday, any other day blank. */
    private function defaultStatusFor($date): string
    {
        return $this->isSunday($date) ? 'holiday' : '';
    }

    /** What an unmarked day reads as in the records: Sunday a holiday, else nothing. */
    private function unmarkedStatusFor($date): string
    {
        return $this->isSunday($date) ? 'holiday' : 'not_marked';
    }

    /** Which April → March year a date falls in (Jan–Mar belong to the previous one). */
    private static function academicYearOf(Carbon $d): int
    {
        return $d->month >= 4 ? (int) $d->year : (int) $d->year - 1;
    }

    /** A real calendar day, Y-m-d, or null. */
    private function realDate($value): ?string
    {
        $d = \DateTime::createFromFormat('!Y-m-d', (string) $value);

        return $d && $d->format('Y-m-d') === (string) $value ? (string) $value : null;
    }

    /**
     * Save a day's rows as the panel's mark panel does. Rows arrive as
     * [person_id => ['status', 'remark', 'user_id'?]]; ids that are not the
     * school's are dropped, a blank row clears whatever was saved for it, and
     * a row still showing what is saved is left as it is.
     *
     * @return array the rows that were written, for a notice
     */
    private function persistDay(string $model, string $fk, array $rows, array $validIds, int $orgId, string $date, int $markedBy, bool $withUser): array
    {
        $valid = array_flip($validIds);
        $saved = $model::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)
            ->whereIn($fk, array_keys($rows))
            ->get()->keyBy($fk);
        $written = [];

        DB::transaction(function () use ($model, $fk, $rows, $valid, $saved, $orgId, $date, $markedBy, $withUser, &$written) {
            $clear = [];

            foreach ($rows as $id => $row) {
                if (!isset($valid[$id])) {
                    continue;
                }
                $rec = $saved->get($id);
                $status = (string) ($row['status'] ?? '');
                if ($status === '') {
                    if ($rec) $clear[] = $id;
                    continue;
                }
                if (!in_array($status, self::STATUSES, true)) {
                    continue;
                }
                $row = ['status' => $status, 'remark' => (string) ($row['remark'] ?? '')] + $row;
                if ($this->unchanged($rec, $row)) {
                    continue;
                }
                $values = ['status' => $this->toInt($status), 'remarks' => $row['remark'], 'marked_by' => $markedBy];
                if ($withUser) {
                    $values['user_id'] = $row['user_id'] ?? 0;
                }
                $model::updateOrCreate([$fk => $id, 'organization_id' => $orgId, 'attendance_date' => $date], $values);
                $written[] = ['id' => $id, 'status' => $values['status'], 'user_id' => $row['user_id'] ?? null];
            }

            if ($clear) {
                $model::where('organization_id', $orgId)
                    ->whereIn($fk, $clear)
                    ->whereDate('attendance_date', $date)->delete();
            }
        });

        return $written;
    }

    /** The rows of a v=2 submit as [id => row], blank statuses included. */
    private function dayRows(Request $request, string $idKey): array
    {
        $rows = [];
        foreach ((array) $request->input('marks', []) as $row) {
            if (!isset($row[$idKey])) continue;
            $rows[(int) $row[$idKey]] = [
                'status' => (string) ($row['status'] ?? ''),
                'remark' => (string) ($row['remark'] ?? ''),
            ];
        }

        return $rows;
    }

    /** "Teacher attendance saved for 24 Sep 2026." — or updated, or a holiday. */
    private function dayMessage(string $who, bool $holiday, bool $wasEdit, string $date): array
    {
        $day = Carbon::parse($date)->format('d M Y');

        return $holiday
            ? ['title' => 'Holiday marked', 'message' => $day . ' is now a holiday for ' . ($who === 'Teacher' ? 'all teachers' : 'this class') . '.']
            : [
                'title'   => $wasEdit ? 'Attendance updated' : 'Attendance successful',
                'message' => $who . ' attendance ' . ($wasEdit ? 'updated' : 'saved') . ' for ' . $day . '.',
            ];
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/attendance/lookups — classes (with sections) + teachers. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->inClassOrder()->get(['id', 'name'])
            ->map(fn ($s) => [
                'id'       => $s->id,
                'name'     => $s->name,
                'sections' => Section::where('standard_id', $s->id)->orderBy('id')->get(['id', 'name'])->toArray(),
            ]);

        $teachers = TeacherDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)->get()
            ->sortBy(fn ($t) => $t->user->name ?? '')
            ->map(fn ($t) => [
                'id'    => $t->id,
                'name'  => $t->user->name ?? '—',
                'email' => $t->user->email ?? '',
                'image' => $t->user->image ?? null,
            ])->values();

        return $this->success(['classes' => $classes, 'teachers' => $teachers], 'Attendance lookups fetched.');
    }

    /** GET /admin/attendance/students?standard_id=&section_id= */
    public function students(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        return $this->success(['students' => $this->sectionStudents($user->organization_id, (int) $request->standard_id, (int) $request->section_id)
            ->map(fn ($s) => [
                'id'      => $s->id,
                'name'    => $s->user->name ?? ($s->full_name ?? '—'),
                'roll_no' => $s->roll_no,
                'image'   => $s->user->image ?? null,
            ])->values()], 'Students fetched.');
    }

    private function sectionStudents(int $orgId, int $standardId, int $sectionId)
    {
        return StudentDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)
            ->where('standard_id', $standardId)
            ->where('section_id', $sectionId)
            ->whereNotNull('user_id')->get()
            ->sortBy(fn ($s) => mb_strtolower(trim($s->user->name ?? '')))->values();
    }

    // ══════════════════════════ TEACHER: MARK ══════════════════════════

    /** GET /admin/attendance/teacher/mark?date= */
    public function teacherMarkList(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $date  = $request->input('date', now()->toDateString());

        $teachers = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get()
            ->sortBy(fn ($t) => $t->user->name ?? '')->values();

        $existing = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)->get()->keyBy('teacher_detail_id');

        // v=2: an unsaved row starts blank (Sunday on Holiday), as the panel's.
        $panel   = $this->panel($request);
        $default = $panel ? $this->defaultStatusFor($date) : 'present';

        $rows = $teachers->map(function ($t) use ($existing, $default, $panel) {
            $rec = $existing->get($t->id);
            return [
                'teacher_detail_id' => $t->id,
                'name'   => $t->user->name ?? '—',
                'image'  => $t->user->image ?? null,
                'status' => $rec ? $this->toLabel($rec->status) : $default,
                'remark' => $rec->remarks ?? '',
            ] + ($panel ? ['email' => $t->user->email ?? ''] : []);
        });

        return $this->success(
            ['date' => $date, 'rows' => $rows] + ($panel ? ['existing' => $existing->isNotEmpty()] : []),
            'Teacher mark list fetched.'
        );
    }

    /** POST /admin/attendance/teacher/mark — {date, marks:[{teacher_detail_id,status,remark}]} */
    public function submitTeacherAttendance(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($this->panel($request)) {
            return $this->submitTeacherDay($request, $user);
        }
        if ($err = $this->validateWith($request, [
            'date'                  => 'required|date',
            'marks'                 => 'required|array',
            'marks.*.teacher_detail_id' => 'required|integer',
            'marks.*.status'        => 'required|string',
        ])) return $err;

        $orgId = $user->organization_id;

        $saved = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $request->date)
            ->whereIn('teacher_detail_id', collect($request->marks)->pluck('teacher_detail_id'))
            ->get()->keyBy('teacher_detail_id');

        DB::transaction(function () use ($request, $orgId, $user, $saved) {
            foreach ($request->marks as $row) {
                if ($this->unchanged($saved->get($row['teacher_detail_id']), $row)) {
                    continue;
                }
                TeacherAttendance::updateOrCreate(
                    ['teacher_detail_id' => $row['teacher_detail_id'], 'organization_id' => $orgId, 'attendance_date' => $request->date],
                    ['status' => $this->toInt($row['status']), 'remarks' => $row['remark'] ?? '', 'marked_by' => $user->id]
                );
            }
        });

        return $this->success(null, 'Teacher attendance saved for ' . Carbon::parse($request->date)->format('d M Y') . '.');
    }

    /**
     * v=2 — the panel's Save (or, with holiday=1, its "Mark this day as
     * holiday"): {date, holiday?, marks:[{teacher_detail_id, status, remark}]},
     * a status left '' to leave that teacher unmarked.
     */
    private function submitTeacherDay(Request $request, $user)
    {
        if ($err = $this->validateWith($request, [
            'date'                      => 'required|string',
            'marks'                     => 'required|array',
            'marks.*.teacher_detail_id' => 'required|integer',
            'marks.*.status'            => 'nullable|string',
            'marks.*.remark'            => 'nullable|string|max:255',
        ])) return $err;

        $date = $this->realDate($request->date);
        if (!$date) return $this->error('Pick a valid date.', 422);

        $orgId   = $user->organization_id;
        $holiday = $request->boolean('holiday');
        $rows    = $this->dayRows($request, 'teacher_detail_id');
        if ($holiday) {
            foreach ($rows as $id => $row) $rows[$id]['status'] = 'holiday';
        }
        if (!array_filter($rows, fn ($r) => $r['status'] !== '')) {
            return $this->error('Mark at least one teacher first.', 422);
        }

        $wasEdit = TeacherAttendance::where('organization_id', $orgId)->whereDate('attendance_date', $date)->exists();
        $valid   = TeacherDetail::where('organization_id', $orgId)->whereIn('id', array_keys($rows))->pluck('id')->all();

        $this->persistDay(TeacherAttendance::class, 'teacher_detail_id', $rows, $valid, $orgId, $date, $user->id, false);

        $said = $this->dayMessage('Teacher', $holiday, $wasEdit, $date);

        return $this->success(['date' => $date, 'updated' => $wasEdit, 'title' => $said['title']], $said['message']);
    }

    /** GET /admin/attendance/teacher/by-date?date=&status= */
    public function teacherByDate(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $date  = $request->input('date', now()->toDateString());

        $teachers = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get()
            ->sortBy(fn ($t) => $t->user->name ?? '')->values();
        $recs = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)->get()->keyBy('teacher_detail_id');

        // v=2: an unmarked Sunday reads as the standing holiday.
        $panel    = $this->panel($request);
        $unmarked = $panel ? $this->unmarkedStatusFor($date) : 'not_marked';

        $rows = $teachers->map(function ($t) use ($recs, $unmarked, $panel) {
            $rec = $recs->get($t->id);
            return [
                'name'   => $t->user->name ?? '—',
                'image'  => $t->user->image ?? null,
                'status' => $rec ? $this->toLabel($rec->status) : $unmarked,
                'remark' => $rec->remarks ?? '',
            ] + ($panel ? ['id' => $t->id, 'email' => $t->user->email ?? ''] : []);
        });

        $stats  = $this->tallyLabels($rows->pluck('status'));
        $filter = $request->input('status', '');
        if ($filter !== '') $rows = $rows->where('status', $filter)->values();

        return $this->success(['date' => $date, 'rows' => $rows, 'stats' => $stats], 'Teacher attendance fetched.');
    }

    /** GET /admin/attendance/teacher/calendar?teacher_id=&month=Y-m  (or ?teacher_id=&year=YYYY) */
    public function teacherCalendar(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['teacher_id' => 'required|integer'])) return $err;

        if ($this->panel($request)) {
            $teacher = TeacherDetail::with('user:id,name')->where('organization_id', $user->organization_id)->find($request->teacher_id);
            if (!$teacher) return $this->error('Teacher not found.', 404);

            return $this->personCards($request, $user->organization_id, TeacherAttendance::class, 'teacher_detail_id', $teacher->id, $teacher->user->name ?? '');
        }

        return $this->personCalendar($request, $user->organization_id, TeacherAttendance::class, 'teacher_detail_id', (int) $request->teacher_id);
    }

    /**
     * GET /admin/attendance/teacher/month-grid?month=Y-m&teacher_id= — the
     * panel's By Month: every date of the month down the side and a column per
     * teacher (or the one picked), each cell that teacher's status that day.
     * An unmarked Sunday is the standing holiday, any other past day without a
     * record not marked, and days still to come are left out (null). Each
     * teacher's totals come with it.
     */
    public function teacherMonthGrid(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $month = (string) $request->input('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month) || !$this->realDate($month . '-01')) {
            return $this->error('Pick a valid month.', 422);
        }

        $orgId = $user->organization_id;
        $teachers = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get()
            ->sortBy(fn ($t) => $t->user->name ?? '')->values();
        $columns = $request->filled('teacher_id')
            ? $teachers->where('id', (int) $request->teacher_id)->values()
            : $teachers;

        $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfDay();
        $end   = $start->copy()->endOfMonth();
        $today = Carbon::today();

        $saved = [];
        TeacherAttendance::where('organization_id', $orgId)
            ->whereIn('teacher_detail_id', $columns->pluck('id'))
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get(['teacher_detail_id', 'attendance_date', 'status'])
            ->each(function ($r) use (&$saved) {
                $saved[$r->teacher_detail_id][Carbon::parse($r->attendance_date)->toDateString()] = $this->toLabel($r->status);
            });

        $blank  = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'not_marked' => 0];
        $totals = [];
        foreach ($columns as $t) {
            $totals[$t->id] = $blank;
        }

        $rows = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $ds    = $d->toDateString();
            $cells = [];
            foreach ($columns as $t) {
                if ($d->gt($today)) {
                    $cells[(string) $t->id] = null;
                    continue;
                }
                $status = $saved[$t->id][$ds] ?? $this->unmarkedStatusFor($ds);
                $cells[(string) $t->id] = $status;
                $totals[$t->id][$status]++;
            }
            $rows[] = [
                'date'   => $ds,
                'label'  => $d->format('d M'),
                'dow'    => $d->format('D'),
                'sunday' => $d->isSunday(),
                'today'  => $d->isSameDay($today),
                'cells'  => $cells,
            ];
        }

        return $this->success([
            'month'    => $month,
            'title'    => $start->format('F Y'),
            'teachers' => $columns->map(fn ($t) => [
                'id'     => $t->id,
                'name'   => $t->user->name ?? '—',
                'image'  => $t->user->image ?? null,
                'totals' => $totals[$t->id],
            ])->values(),
            'rows'     => $rows,
        ], 'Teacher month fetched.');
    }

    // ══════════════════════════ STUDENT: MARK ══════════════════════════

    /** GET /admin/attendance/student/mark?standard_id=&section_id=&date= */
    public function studentMarkList(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $date  = $request->input('date', now()->toDateString());
        $students = $this->sectionStudents($orgId, (int) $request->standard_id, (int) $request->section_id);

        $existing = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)
            ->whereIn('student_detail_id', $students->pluck('id'))->get()->keyBy('student_detail_id');

        // v=2: an unsaved row starts blank (Sunday on Holiday), as the panel's.
        $panel   = $this->panel($request);
        $default = $panel ? $this->defaultStatusFor($date) : 'present';

        $rows = $students->map(function ($s) use ($existing, $default, $panel) {
            $rec = $existing->get($s->id);
            return [
                'student_detail_id' => $s->id,
                'user_id' => $s->user_id,
                'name'    => $s->user->name ?? ($s->full_name ?? '—'),
                'roll_no' => $s->roll_no,
                'image'   => $s->user->image ?? null,
                'status'  => $rec ? $this->toLabel($rec->status) : $default,
                'remark'  => $rec->remarks ?? '',
            ] + ($panel ? ['email' => $s->user->email ?? ''] : []);
        });

        return $this->success(
            ['date' => $date, 'rows' => $rows] + ($panel ? ['existing' => $existing->isNotEmpty()] : []),
            'Student mark list fetched.'
        );
    }

    /** POST /admin/attendance/student/mark — {standard_id,section_id,date,marks:[{student_detail_id,user_id,status,remark}]} */
    public function submitStudentAttendance(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($this->panel($request)) {
            return $this->submitStudentDay($request, $user);
        }
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
            'date'        => 'required|date',
            'marks'       => 'required|array',
            'marks.*.student_detail_id' => 'required|integer',
            'marks.*.status' => 'required|string',
        ])) return $err;

        $orgId = $user->organization_id;
        $notifyRows = [];

        $saved = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $request->date)
            ->whereIn('student_detail_id', collect($request->marks)->pluck('student_detail_id'))
            ->get()->keyBy('student_detail_id');

        // Only the students whose attendance changed are written — and told.
        DB::transaction(function () use ($request, $orgId, $user, $saved, &$notifyRows) {
            foreach ($request->marks as $row) {
                if ($this->unchanged($saved->get($row['student_detail_id']), $row)) {
                    continue;
                }
                $statusInt = $this->toInt($row['status']);
                StudentAttendance::updateOrCreate(
                    ['student_detail_id' => $row['student_detail_id'], 'organization_id' => $orgId, 'attendance_date' => $request->date],
                    ['user_id' => $row['user_id'] ?? 0, 'status' => $statusInt, 'remarks' => $row['remark'] ?? '', 'marked_by' => $user->id]
                );
                if (!empty($row['user_id'])) {
                    $notifyRows[] = ['user_id' => $row['user_id'], 'status' => $statusInt, 'date' => $request->date];
                }
            }
        });

        try {
            app(\App\Services\AppPushNotifier::class)->attendanceMarked($notifyRows);
        } catch (\Throwable $e) {
            logger()->warning('attendanceMarked push failed: ' . $e->getMessage());
        }

        return $this->success(null, 'Student attendance saved for ' . Carbon::parse($request->date)->format('d M Y') . '.');
    }

    /**
     * v=2 — the panel's Save for a section (or, with holiday=1, its "Mark this
     * day as holiday"): {standard_id, section_id, date, holiday?, marks:
     * [{student_detail_id, status, remark}]}, a status left '' to leave that
     * student unmarked. Only the section's own students are written, each
     * against the account the school has for them, and only those whose day
     * changed are told.
     */
    private function submitStudentDay(Request $request, $user)
    {
        if ($err = $this->validateWith($request, [
            'standard_id'               => 'required|integer',
            'section_id'                => 'required|integer',
            'date'                      => 'required|string',
            'marks'                     => 'required|array',
            'marks.*.student_detail_id' => 'required|integer',
            'marks.*.status'            => 'nullable|string',
            'marks.*.remark'            => 'nullable|string|max:255',
        ], ['standard_id.required' => 'Select class and section first.', 'section_id.required' => 'Select class and section first.'])) return $err;

        $date = $this->realDate($request->date);
        if (!$date) return $this->error('Pick a valid date.', 422);

        $orgId   = $user->organization_id;
        $holiday = $request->boolean('holiday');
        $rows    = $this->dayRows($request, 'student_detail_id');
        if ($holiday) {
            foreach ($rows as $id => $row) $rows[$id]['status'] = 'holiday';
        }
        if (!array_filter($rows, fn ($r) => $r['status'] !== '')) {
            return $this->error('Mark at least one student first.', 422);
        }

        $students = $this->sectionStudents($orgId, (int) $request->standard_id, (int) $request->section_id);
        $userOf   = $students->pluck('user_id', 'id');
        foreach ($rows as $id => $row) {
            $rows[$id]['user_id'] = $userOf[$id] ?? null;
        }

        $wasEdit = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)
            ->whereIn('student_detail_id', $students->pluck('id'))->exists();

        $written = $this->persistDay(StudentAttendance::class, 'student_detail_id', $rows, $students->pluck('id')->all(), $orgId, $date, $user->id, true);

        try {
            app(\App\Services\AppPushNotifier::class)->attendanceMarked(collect($written)
                ->filter(fn ($w) => !empty($w['user_id']))
                ->map(fn ($w) => ['user_id' => $w['user_id'], 'status' => $w['status'], 'date' => $date])
                ->values()->all());
        } catch (\Throwable $e) {
            logger()->warning('attendanceMarked push failed: ' . $e->getMessage());
        }

        $said = $this->dayMessage('Student', $holiday, $wasEdit, $date);

        return $this->success(['date' => $date, 'updated' => $wasEdit, 'title' => $said['title']], $said['message']);
    }

    /** GET /admin/attendance/student/by-date?standard_id=&section_id=&date= */
    public function studentByDate(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'standard_id' => 'required|integer',
            'section_id'  => 'required|integer',
        ])) return $err;

        $orgId = $user->organization_id;
        $date  = $request->input('date', now()->toDateString());
        $students = $this->sectionStudents($orgId, (int) $request->standard_id, (int) $request->section_id);

        $recs = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $date)
            ->whereIn('student_detail_id', $students->pluck('id'))->get()->keyBy('student_detail_id');

        // v=2: an unmarked Sunday reads as the standing holiday.
        $panel    = $this->panel($request);
        $unmarked = $panel ? $this->unmarkedStatusFor($date) : 'not_marked';

        $rows = $students->map(function ($s) use ($recs, $unmarked, $panel) {
            $rec = $recs->get($s->id);
            return [
                'name'    => $s->user->name ?? ($s->full_name ?? '—'),
                'roll_no' => $s->roll_no,
                'image'   => $s->user->image ?? null,
                'status'  => $rec ? $this->toLabel($rec->status) : $unmarked,
                'remark'  => $rec->remarks ?? '',
            ] + ($panel ? ['id' => $s->id, 'email' => $s->user->email ?? ''] : []);
        });

        $stats  = $this->tallyLabels($rows->pluck('status'));
        $filter = $request->input('status', '');
        if ($filter !== '') $rows = $rows->where('status', $filter)->values();

        return $this->success(['date' => $date, 'rows' => $rows, 'stats' => $stats], 'Student attendance fetched.');
    }

    /** GET /admin/attendance/student/calendar?student_id=&month=Y-m  (or ?student_id=&year=YYYY) */
    public function studentCalendar(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, ['student_id' => 'required|integer'])) return $err;

        if ($this->panel($request)) {
            $student = StudentDetail::with('user:id,name')->where('organization_id', $user->organization_id)->find($request->student_id);
            if (!$student) return $this->error('Student not found.', 404);

            return $this->personCards($request, $user->organization_id, StudentAttendance::class, 'student_detail_id', $student->id, $student->user->name ?? ($student->full_name ?? ''));
        }

        return $this->personCalendar($request, $user->organization_id, StudentAttendance::class, 'student_detail_id', (int) $request->student_id);
    }

    /**
     * v=2 — a person's attendance as the panel's month cards: ?month=Y-m for
     * one month, or ?year=YYYY for the school year April YYYY → March YYYY+1.
     * Each month is a Sunday-first calendar with its counts and present-%; an
     * unmarked Sunday is a holiday, any other unmarked day not marked, and days
     * still to come are left out.
     */
    private function personCards(Request $request, int $orgId, string $model, string $fk, int $personId, string $person)
    {
        if ($request->filled('year')) {
            $year  = (int) $request->year;
            $start = Carbon::create($year, 4, 1)->startOfDay();
            $end   = Carbon::create($year + 1, 3, 31)->endOfDay();
            $title = 'Apr ' . $year . ' – Mar ' . ($year + 1);
        } else {
            $month = (string) $request->input('month', now()->format('Y-m'));
            if (!preg_match('/^\d{4}-\d{2}$/', $month) || !$this->realDate($month . '-01')) {
                return $this->error('Pick a valid month.', 422);
            }
            $start = Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $title = $start->format('F Y');
        }

        $records = $model::where('organization_id', $orgId)
            ->where($fk, $personId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn ($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])
            ->toArray();

        $cards = $this->buildMonthCards($records, $start, $end);

        return $this->success([
            'type'   => 'cards',
            'person' => $person,
            'title'  => $title,
            'counts' => $cards['counts'],
            'months' => collect($cards['months'])->map(fn ($m, $key) => ['key' => $key] + $m)->values(),
        ], 'Attendance fetched.');
    }

    /**
     * The panel's month cards between $start and $end: per month a Sunday-first
     * grid (a leading blank count and a cell per day), its counts and present-%,
     * plus the period's totals. $records = [Y-m-d => int status]. Days beyond
     * today are left out of the counts.
     */
    private function buildMonthCards(array $records, Carbon $start, Carbon $end): array
    {
        $today = Carbon::today();
        if ($end->gt($today)) $end = $today->copy();

        $blank  = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'not_marked' => 0, 'working' => 0];
        $counts = $blank;
        $months = [];

        if ($start->gt($end)) {
            return ['counts' => $counts + ['percent' => 0], 'months' => $months];
        }

        $cursor = $start->copy()->startOfMonth();
        $last   = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $monthStart  = $cursor->copy()->startOfMonth();
            $daysInMonth = (int) $monthStart->daysInMonth;

            $mCounts = $blank;
            $cells   = [];

            for ($n = 1; $n <= $daysInMonth; $n++) {
                $d  = $monthStart->copy()->day($n);
                $ds = $d->toDateString();

                if ($d->lt($start) || $d->gt($end)) {
                    $cells[] = ['day' => $n, 'date' => $ds, 'status' => null, 'in_period' => false];
                    continue;
                }

                $status = array_key_exists($ds, $records)
                    ? $this->toLabel($records[$ds])
                    : $this->unmarkedStatusFor($ds);

                $counts[$status]  = ($counts[$status] ?? 0) + 1;
                $mCounts[$status] = ($mCounts[$status] ?? 0) + 1;
                if (in_array($status, ['present', 'absent', 'half_day'], true)) {
                    $counts['working']++;
                    $mCounts['working']++;
                }

                $cells[] = ['day' => $n, 'date' => $ds, 'status' => $status, 'in_period' => true];
            }

            $months[$monthStart->format('Y-m')] = [
                'label'  => $monthStart->format('F Y'),
                'lead'   => (int) $monthStart->dayOfWeek,
                'cells'  => $cells,
                'counts' => $mCounts,
                'pct'    => $mCounts['working'] > 0
                    ? (int) round(($mCounts['present'] + 0.5 * $mCounts['half_day']) / $mCounts['working'] * 100)
                    : 0,
            ];

            $cursor->addMonthNoOverflow();
        }

        $counts['percent'] = $counts['working'] > 0
            ? round(($counts['present'] + 0.5 * $counts['half_day']) / $counts['working'] * 100, 1)
            : 0;

        return ['counts' => $counts, 'months' => $months];
    }

    /** Shared monthly-calendar / yearly-summary builder for a teacher or student. */
    private function personCalendar(Request $request, int $orgId, string $model, string $fk, int $personId)
    {
        if ($request->filled('year')) {
            $year = (int) $request->year;
            $recs = $model::where('organization_id', $orgId)
                ->where($fk, $personId)
                ->whereYear('attendance_date', $year)->get()
                ->mapWithKeys(fn ($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])->toArray();
            return $this->success(['type' => 'yearly', 'yearly' => $this->buildYearly($year, $recs)], 'Yearly summary fetched.');
        }

        $month = $request->input('month', now()->format('Y-m'));
        $recs  = $this->personMonthRecords($model, $fk, $personId, $month, $orgId);
        return $this->success(['type' => 'monthly', 'calendar' => $this->buildCalendar($month, $recs)], 'Monthly calendar fetched.');
    }

    // ══════════════════════════ CLASS TEACHERS ══════════════════════════

    /** GET /admin/attendance/class-teachers?mode=by_class|by_teacher&standard_id=&section_id=&teacher_id= */
    public function classTeachers(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;
        $mode  = $request->input('mode') === 'by_teacher' ? 'by_teacher' : 'by_class';

        // In class order, as the Standard page lists the classes.
        $assignments = AssignTeacherStandard::with(['teacher.user:id,name,email,image', 'standard:id,name,order', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($mode === 'by_class' && $request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->when($mode === 'by_class' && $request->filled('section_id'),  fn ($q) => $q->where('section_id', $request->section_id))
            ->when($mode === 'by_teacher' && $request->filled('teacher_id'), fn ($q) => $q->where('teacher_detail_id', $request->teacher_id))
            ->get();
        $assignments = AssignTeacherStandard::sortInClassOrder($assignments)
            ->map(fn ($a) => [
                'id'           => $a->id,
                'teacher_id'   => $a->teacher_detail_id,
                'teacher_name' => $a->teacher?->user?->name ?? '—',
                'teacher_email'=> $a->teacher?->user?->email ?? '',
                'teacher_image'=> $a->teacher?->user?->image ?? null,
                'standard_id'  => $a->standard_id,
                // 0 is how a whole-class assignment (no one section) is kept.
                'section_id'   => $a->section_id ?: null,
                'standard'     => $a->standard?->name ?? '—',
                'section'      => $a->section?->name ?? null,
            ]);

        // Every teacher who is class teacher of something, whatever the filter —
        // the assign form offers only the others.
        $taken = AssignTeacherStandard::where('organization_id', $orgId)->get(['id', 'teacher_detail_id'])
            ->map(fn ($a) => ['id' => $a->id, 'teacher_id' => $a->teacher_detail_id])->values();

        return $this->success(['assignments' => $assignments, 'taken' => $taken], 'Class teachers fetched.');
    }

    /** POST /admin/attendance/class-teachers — {id?,teacher_detail_id,standard_id,section_id?} */
    public function saveClassTeacher(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'id'                => 'nullable|integer',
            'teacher_detail_id' => 'required|exists:teacher_details,id',
            'standard_id'       => 'required|exists:standards,id',
            'section_id'        => 'nullable|exists:sections,id',
        ])) return $err;

        $orgId = $user->organization_id;

        // As the panel: a teacher is class teacher of one class only — though
        // of as many of its sections as the school gives them. An assignment
        // being edited may keep its own teacher.
        $editing = $request->id
            ? AssignTeacherStandard::where('organization_id', $orgId)->find($request->id)
            : null;
        if (!$editing || (int) $editing->teacher_detail_id !== (int) $request->teacher_detail_id) {
            $taken = AssignTeacherStandard::with(['standard:id,name', 'section:id,name'])
                ->where('organization_id', $orgId)
                ->where('teacher_detail_id', $request->teacher_detail_id)
                ->where('standard_id', '!=', $request->standard_id)
                ->when($request->id, fn ($q) => $q->where('id', '!=', $request->id))
                ->first();
            if ($taken) {
                $class = trim(($taken->standard->name ?? '') . ($taken->section ? ' · ' . $taken->section->name : ''));
                return $this->error('This teacher is already a class teacher' . ($class !== '' ? ' of ' . $class : '') . '.', 422);
            }
        }

        $dup = AssignTeacherStandard::where('organization_id', $orgId)
            ->where('teacher_detail_id', $request->teacher_detail_id)
            ->where('standard_id', $request->standard_id)
            ->when($request->section_id, fn ($q) => $q->where('section_id', $request->section_id))
            ->when($request->id, fn ($q) => $q->where('id', '!=', $request->id))
            ->exists();

        if ($dup) {
            return $this->error('This teacher is already assigned to this class/section.', 422);
        }

        AssignTeacherStandard::updateOrCreate(
            ['id' => $request->id],
            [
                'organization_id'   => $orgId,
                'teacher_detail_id' => $request->teacher_detail_id,
                'standard_id'       => $request->standard_id,
                // section_id is NOT NULL default 0: 0 is "no one section" —
                // writing null failed the save.
                'section_id'        => $request->section_id ?: 0,
            ]
        );

        return $this->success(null, $request->id ? 'Assignment updated.' : 'Class teacher assigned.');
    }

    /** DELETE /admin/attendance/class-teachers/{id} */
    public function deleteClassTeacher($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        AssignTeacherStandard::where('id', $id)
            ->where('organization_id', $user->organization_id)->delete();

        return $this->success(null, 'Assignment removed.');
    }

    // ══════════════════════════ BUILDERS ══════════════════════════

    /** Fetch a person's records for a Y-m month keyed by date => int status. */
    private function personMonthRecords(string $model, string $fk, $personId, string $monthStr, int $orgId): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        return $model::where('organization_id', $orgId)
            ->where($fk, $personId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn ($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])
            ->toArray();
    }

    /** Month calendar grid. $records = [Y-m-d => int status]. */
    private function buildCalendar(string $monthStr, array $records): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $daysInMonth = $start->copy()->endOfMonth()->day;

        $weeks = [];
        $week = array_fill(0, 7, null);
        $col = (int) $start->dayOfWeek;

        $c = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $start->copy()->day($d)->toDateString();
            $cell = ['day' => $d, 'date' => $date, 'status' => 'off'];
            if (array_key_exists($date, $records)) {
                $lbl = $this->toLabel($records[$date]);
                $cell['status'] = $lbl;
                $c[$lbl] = ($c[$lbl] ?? 0) + 1;
            }
            $week[$col] = $cell;
            $col++;
            if ($col === 7) { $weeks[] = $week; $week = array_fill(0, 7, null); $col = 0; }
        }
        if ($col !== 0) $weeks[] = $week;

        $working = $c['present'] + $c['absent'] + $c['half_day'];
        $percent = $working > 0 ? round(($c['present'] + 0.5 * $c['half_day']) / $working * 100, 1) : 0;

        return [
            'month'  => $monthStr,
            'weeks'  => $weeks,
            'totals' => [
                'total_days'   => $daysInMonth,
                'working_days' => $working,
                'present_days' => $c['present'],
                'absent_days'  => $c['absent'],
                'half_days'    => $c['half_day'],
                'holidays'     => $c['holiday'],
                'percent'      => $percent,
            ],
        ];
    }

    /** Yearly summary — 12 month cards + year totals. $records = [Y-m-d => int]. */
    private function buildYearly(int $year, array $records): array
    {
        $months = [];
        $yt = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'working' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $c = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0];
            foreach ($records as $date => $st) {
                $dt = Carbon::parse($date);
                if ((int) $dt->year === $year && (int) $dt->month === $m) {
                    $lbl = $this->toLabel($st);
                    $c[$lbl] = ($c[$lbl] ?? 0) + 1;
                }
            }
            $working = $c['present'] + $c['absent'] + $c['half_day'];
            $percent = $working > 0 ? round(($c['present'] + 0.5 * $c['half_day']) / $working * 100, 1) : 0;
            $months[] = [
                'label'    => Carbon::create($year, $m, 1)->format('M'),
                'present'  => $c['present'],
                'absent'   => $c['absent'],
                'half_day' => $c['half_day'],
                'holiday'  => $c['holiday'],
                'working'  => $working,
                'percent'  => $percent,
            ];
            foreach (['present', 'absent', 'half_day', 'holiday'] as $k) $yt[$k] += $c[$k];
            $yt['working'] += $working;
        }
        $yt['percent'] = $yt['working'] > 0 ? round(($yt['present'] + 0.5 * $yt['half_day']) / $yt['working'] * 100, 1) : 0;

        return ['year' => $year, 'months' => $months, 'totals' => $yt];
    }

    /** Count present/absent/half_day/holiday/not_marked across a list of label strings. */
    private function tallyLabels($labels): array
    {
        $t = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'not_marked' => 0, 'total' => 0];
        foreach ($labels as $l) {
            $t[$l] = ($t[$l] ?? 0) + 1;
            $t['total']++;
        }
        return $t;
    }
}
