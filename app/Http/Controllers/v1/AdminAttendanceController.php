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
 */
class AdminAttendanceController extends ApiController
{
    private const ADMIN_ROLES = ['admin', 'sub-admin'];

    private const S_PRESENT = 1;
    private const S_ABSENT  = 0;
    private const S_HALF    = 2;
    private const S_HOLIDAY = 3;

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
            self::S_HOLIDAY => 'holiday',
            default         => 'absent',
        };
    }

    // ══════════════════════════ LOOKUPS ══════════════════════════

    /** GET /admin/attendance/lookups — classes (with sections) + teachers. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = $user->organization_id;

        $classes = Standard::where('organization_id', $orgId)->orderBy('id')->get(['id', 'name'])
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
            ->sortBy(fn ($s) => $s->user->name ?? '')->values();
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

        $rows = $teachers->map(function ($t) use ($existing) {
            $rec = $existing->get($t->id);
            return [
                'teacher_detail_id' => $t->id,
                'name'   => $t->user->name ?? '—',
                'image'  => $t->user->image ?? null,
                'status' => $rec ? $this->toLabel($rec->status) : 'present',
                'remark' => $rec->remarks ?? '',
            ];
        });

        return $this->success(['date' => $date, 'rows' => $rows], 'Teacher mark list fetched.');
    }

    /** POST /admin/attendance/teacher/mark — {date, marks:[{teacher_detail_id,status,remark}]} */
    public function submitTeacherAttendance(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        if ($err = $this->validateWith($request, [
            'date'                  => 'required|date',
            'marks'                 => 'required|array',
            'marks.*.teacher_detail_id' => 'required|integer',
            'marks.*.status'        => 'required|string',
        ])) return $err;

        $orgId = $user->organization_id;

        DB::transaction(function () use ($request, $orgId, $user) {
            foreach ($request->marks as $row) {
                TeacherAttendance::updateOrCreate(
                    ['teacher_detail_id' => $row['teacher_detail_id'], 'organization_id' => $orgId, 'attendance_date' => $request->date],
                    ['status' => $this->toInt($row['status']), 'remarks' => $row['remark'] ?? '', 'marked_by' => $user->id]
                );
            }
        });

        return $this->success(null, 'Teacher attendance saved for ' . Carbon::parse($request->date)->format('d M Y') . '.');
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

        $rows = $teachers->map(function ($t) use ($recs) {
            $rec = $recs->get($t->id);
            return [
                'name'   => $t->user->name ?? '—',
                'image'  => $t->user->image ?? null,
                'status' => $rec ? $this->toLabel($rec->status) : 'not_marked',
                'remark' => $rec->remarks ?? '',
            ];
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

        return $this->personCalendar($request, $user->organization_id, TeacherAttendance::class, 'teacher_detail_id', (int) $request->teacher_id);
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

        $rows = $students->map(function ($s) use ($existing) {
            $rec = $existing->get($s->id);
            return [
                'student_detail_id' => $s->id,
                'user_id' => $s->user_id,
                'name'    => $s->user->name ?? ($s->full_name ?? '—'),
                'roll_no' => $s->roll_no,
                'image'   => $s->user->image ?? null,
                'status'  => $rec ? $this->toLabel($rec->status) : 'present',
                'remark'  => $rec->remarks ?? '',
            ];
        });

        return $this->success(['date' => $date, 'rows' => $rows], 'Student mark list fetched.');
    }

    /** POST /admin/attendance/student/mark — {standard_id,section_id,date,marks:[{student_detail_id,user_id,status,remark}]} */
    public function submitStudentAttendance(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
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

        DB::transaction(function () use ($request, $orgId, $user, &$notifyRows) {
            foreach ($request->marks as $row) {
                $statusInt = $this->toInt($row['status']);
                StudentAttendance::updateOrCreate(
                    ['student_detail_id' => $row['student_detail_id'], 'organization_id' => $orgId, 'attendance_date' => $request->date],
                    ['user_id' => $row['user_id'] ?? 0, 'status' => $statusInt, 'remarks' => $row['remark'] ?? '', 'marked_by' => $user->id]
                );
                if (!empty($row['user_id'])) {
                    $notifyRows[] = ['user_id' => $row['user_id'], 'status' => $statusInt];
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

        $rows = $students->map(function ($s) use ($recs) {
            $rec = $recs->get($s->id);
            return [
                'name'    => $s->user->name ?? ($s->full_name ?? '—'),
                'roll_no' => $s->roll_no,
                'image'   => $s->user->image ?? null,
                'status'  => $rec ? $this->toLabel($rec->status) : 'not_marked',
                'remark'  => $rec->remarks ?? '',
            ];
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

        return $this->personCalendar($request, $user->organization_id, StudentAttendance::class, 'student_detail_id', (int) $request->student_id);
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

        $assignments = AssignTeacherStandard::with(['teacher.user:id,name,email,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($mode === 'by_class' && $request->filled('standard_id'), fn ($q) => $q->where('standard_id', $request->standard_id))
            ->when($mode === 'by_class' && $request->filled('section_id'),  fn ($q) => $q->where('section_id', $request->section_id))
            ->when($mode === 'by_teacher' && $request->filled('teacher_id'), fn ($q) => $q->where('teacher_detail_id', $request->teacher_id))
            ->latest()->get()
            ->map(fn ($a) => [
                'id'           => $a->id,
                'teacher_id'   => $a->teacher_detail_id,
                'teacher_name' => $a->teacher?->user?->name ?? '—',
                'teacher_image'=> $a->teacher?->user?->image ?? null,
                'standard_id'  => $a->standard_id,
                'section_id'   => $a->section_id,
                'standard'     => $a->standard?->name ?? '—',
                'section'      => $a->section?->name ?? null,
            ]);

        return $this->success(['assignments' => $assignments], 'Class teachers fetched.');
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
                'section_id'        => $request->section_id ?: null,
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
