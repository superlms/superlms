<?php

namespace App\Livewire\Accounts;

use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\AssignTeacherStandard;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use WireUi\Traits\WireUiActions;

class Attendance extends Component
{
    use WireUiActions;

    // Status codes stored in *_attendances.status (tinyint):
    //   1 = present, 0 = absent, 2 = half day, 3 = holiday
    private const S_PRESENT = 1;
    private const S_ABSENT  = 0;
    private const S_HALF    = 2;
    private const S_HOLIDAY = 3;

    // ── Tabs ─────────────────────────────────────────────────────────────────
    public string $mainTab = 'teacher';        // teacher | student | class_teachers

    // ── Teacher (shared selectors across its record views) ──────────────────
    public string $teacherView = 'by_date';    // by_date | by_month | by_teacher
    public string $tDate  = '';                // by_date
    public $tTeacherId    = '';                // by_month + by_teacher
    public string $tMonth = '';                // by_month + by_teacher(monthly)
    public $tYear         = '';                // by_teacher(yearly)
    public string $tRange = 'monthly';         // by_teacher: monthly | yearly
    public string $tByDateStatus = '';         // by_date: '' | present | absent | half_day | holiday | not_marked

    // ── Teacher: mark slide-in panel ────────────────────────────────────────
    // Its date lives here, NOT in $tDate, so the record filters can never leak
    // into the marking flow — every open starts clean on today.
    public bool   $showTeacherMarkPanel = false;
    public string $tMarkDate = '';
    public array  $teacherMark = [];           // [teacher_detail_id => ['status','remark']]
    public bool   $teacherMarkExisting = false; // this date is already submitted → editing

    // ── Student (shared selectors across its record views) ──────────────────
    public string $studentView = 'by_date';    // by_date | by_student
    public $stStandard = '';
    public $stSection  = '';
    public $stStudentId = '';
    public string $stDate  = '';               // by_date
    public string $stMonth = '';               // by_student(monthly)
    public $stYear         = '';               // by_student(yearly)
    public string $stRange = 'monthly';        // by_student: monthly | yearly

    // ── Student: mark slide-in panel (own class/section/date, see above) ────
    public bool   $showStudentMarkPanel = false;
    public $sMarkStandard = '';
    public $sMarkSection  = '';
    public string $sMarkDate = '';
    public array  $studentMark = [];
    public bool   $studentMarkExisting = false;

    // ── Assign class teacher panel ───────────────────────────────────────────
    public bool $showAssignPanel = false;
    public ?int $assignEditId = null;
    public $assignTeacherId = '';
    public $assignStandardId = '';
    public $assignSectionId = '';
    public ?int $pendingDeleteAssignId = null;

    // ── Class Teachers tab filters ───────────────────────────────────────────
    public string $ctMode    = 'by_class';     // by_class | by_teacher
    public $ctFilterStandard = '';
    public $ctFilterSection  = '';
    public $ctFilterTeacher  = '';

    public function mount(): void
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');
        // The school year runs April → March, so "2026" means Apr 2026–Mar 2027.
        $year  = (string) self::academicYearOf(now());

        $this->tDate     = $today;
        $this->tMarkDate = $today;
        $this->tMonth    = $month;
        $this->tYear     = $year;
        $this->stDate    = $today;
        $this->sMarkDate = $today;
        $this->stMonth   = $month;
        $this->stYear    = $year;
    }

    /** Which April→March year a date falls in (Jan–Mar belong to the previous one). */
    private static function academicYearOf(Carbon $d): int
    {
        return $d->month >= 4 ? (int) $d->year : (int) $d->year - 1;
    }

    // ── Status helpers ───────────────────────────────────────────────────────

    /** The only statuses a row may carry; anything else is not written. */
    private const STATUSES = ['present', 'absent', 'half_day', 'holiday'];

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

    /** Sundays are a standing holiday — nothing has to be marked for them. */
    private function isSunday($date): bool
    {
        return (int) Carbon::parse($date)->dayOfWeek === Carbon::SUNDAY;
    }

    /**
     * What a row starts on in the mark panel. Sunday is the standing holiday;
     * every other day starts BLANK — an untouched row is never silently saved
     * as present, so a day nobody marked simply stays unmarked and can still
     * be filled in whenever.
     */
    private function defaultStatusFor($date): string
    {
        return $this->isSunday($date) ? 'holiday' : '';
    }

    /** Rows carrying an actual status — blank ones are not saved at all. */
    private function markedCount(array $rows): int
    {
        return count(array_filter($rows, fn($r) => ($r['status'] ?? '') !== ''));
    }

    /** What an unmarked day *reads* as in the records: Sunday → holiday, else nothing. */
    private function unmarkedStatusFor($date): string
    {
        return $this->isSunday($date) ? 'holiday' : 'not_marked';
    }

    // ═══════════════════════════════ TAB SWITCHING ═══════════════════════════
    public function switchMainTab(string $tab): void
    {
        $this->mainTab = $tab;
    }

    public function switchTeacherView(string $v): void
    {
        $this->teacherView = $v;
    }

    public function switchStudentView(string $v): void
    {
        $this->studentView = $v;
    }

    // ═══════════════════════════════ TEACHER: MARK ═══════════════════════════
    /**
     * Header "Mark Attendance" — always a fresh flow: the panel resets to today
     * and re-reads from the database, so nothing from a previous marking run
     * (or from the record filters) is carried in.
     */
    public function openTeacherMark(): void
    {
        $this->teacherMark = [];
        $this->tMarkDate = now()->toDateString();
        $this->loadTeacherMark();
        $this->showTeacherMarkPanel = true;
    }

    public function closeTeacherMark(): void
    {
        $this->showTeacherMarkPanel = false;
        $this->teacherMark = [];
        $this->teacherMarkExisting = false;
    }

    public function updatedTMarkDate(): void
    {
        $this->loadTeacherMark();
    }

    /**
     * Load the panel rows for $tMarkDate. A date that was already submitted
     * comes back with its saved statuses so it can simply be edited and saved
     * again; anything else starts on the day's default.
     */
    public function loadTeacherMark(): void
    {
        $orgId = Auth::user()->organization_id;
        $teachers = TeacherDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)->get();

        $existing = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->tMarkDate)
            ->get()->keyBy('teacher_detail_id');

        $this->teacherMarkExisting = $existing->isNotEmpty();
        $default = $this->defaultStatusFor($this->tMarkDate);

        $this->teacherMark = [];
        foreach ($teachers as $t) {
            $rec = $existing->get($t->id);
            $this->teacherMark[$t->id] = [
                'status' => $rec ? $this->toLabel($rec->status) : $default,
                'remark' => $rec->remarks ?? '',
            ];
        }
    }

    public function setTeacherStatus($teacherId, string $status): void
    {
        if (isset($this->teacherMark[$teacherId])) {
            $this->teacherMark[$teacherId]['status'] = $status;
        }
    }

    public function markAllTeachers(string $status): void
    {
        foreach ($this->teacherMark as $id => $row) {
            $this->teacherMark[$id]['status'] = $status;
        }
    }

    /** How many rows in the panel actually carry a status. */
    public function teacherMarkedCount(): int
    {
        return $this->markedCount($this->teacherMark);
    }

    /**
     * Persist $teacherMark against $tMarkDate. A row left blank is written as
     * nothing at all (and any earlier record for it is removed), so the day
     * stays genuinely unmarked rather than defaulting to present.
     */
    private function persistTeacherMark(): void
    {
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();

        // The panel sets statuses in the browser now, so the rows arrive as
        // client data: take only ids that really are this school's teachers,
        // and only the four statuses the panel offers.
        $valid = TeacherDetail::where('organization_id', $orgId)
            ->whereIn('id', array_keys($this->teacherMark))
            ->pluck('id')->flip();

        DB::transaction(function () use ($orgId, $markedBy, $valid) {
            $clear = [];

            foreach ($this->teacherMark as $teacherId => $row) {
                if (!$valid->has($teacherId)) {
                    continue;
                }
                if (($row['status'] ?? '') === '') {
                    $clear[] = $teacherId;
                    continue;
                }
                if (!in_array($row['status'], self::STATUSES, true)) {
                    continue;
                }
                TeacherAttendance::updateOrCreate(
                    ['teacher_detail_id' => $teacherId, 'organization_id' => $orgId, 'attendance_date' => $this->tMarkDate],
                    ['status' => $this->toInt($row['status']), 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
                );
            }

            if ($clear) {
                TeacherAttendance::where('organization_id', $orgId)
                    ->whereIn('teacher_detail_id', $clear)
                    ->whereDate('attendance_date', $this->tMarkDate)->delete();
            }
        });
    }

    public function submitTeacherAttendance(): void
    {
        if ($this->teacherMarkedCount() === 0) {
            $this->notification()->error('Nothing to save', 'Mark at least one teacher first.');
            return;
        }

        $wasEdit = $this->teacherMarkExisting;
        $date = $this->tMarkDate;

        $this->persistTeacherMark();

        // Show the day that was just marked, then close the panel.
        $this->teacherView = 'by_date';
        $this->tDate = $date;
        $this->closeTeacherMark();

        $this->notification()->success(
            $wasEdit ? 'Attendance updated' : 'Attendance successful',
            'Teacher attendance ' . ($wasEdit ? 'updated' : 'saved') . ' for ' . Carbon::parse($date)->format('d M Y') . '.'
        );
    }

    /** Panel header button — the whole day becomes a holiday, saved right away. */
    public function markTeacherDayHoliday(): void
    {
        foreach ($this->teacherMark as $id => $row) {
            $this->teacherMark[$id]['status'] = 'holiday';
        }

        $date = $this->tMarkDate;
        $this->persistTeacherMark();

        $this->teacherView = 'by_date';
        $this->tDate = $date;
        $this->closeTeacherMark();

        $this->notification()->success(
            'Holiday marked',
            Carbon::parse($date)->format('d M Y') . ' is now a holiday for all teachers.'
        );
    }

    // ═══════════════════════════════ STUDENT: MARK ═══════════════════════════
    public function updatedStStandard(): void
    {
        $this->stSection = '';
        $this->stStudentId = '';
    }

    public function updatedStSection(): void
    {
        $this->stStudentId = '';
    }

    /** Header "Mark Attendance" — fresh flow on today, nothing carried over. */
    public function openStudentMark(): void
    {
        $this->studentMark = [];
        $this->studentMarkExisting = false;
        $this->sMarkStandard = '';
        $this->sMarkSection = '';
        $this->sMarkDate = now()->toDateString();
        $this->showStudentMarkPanel = true;
    }

    public function closeStudentMark(): void
    {
        $this->showStudentMarkPanel = false;
        $this->studentMark = [];
        $this->studentMarkExisting = false;
    }

    public function updatedSMarkStandard(): void
    {
        $this->sMarkSection = '';
        $this->studentMark = [];
        $this->studentMarkExisting = false;
    }

    public function updatedSMarkSection(): void
    {
        $this->loadStudentMark();
    }

    public function updatedSMarkDate(): void
    {
        $this->loadStudentMark();
    }

    /**
     * Load the panel rows for the chosen class/section on $sMarkDate. An already
     * submitted date comes back with its saved statuses so it can be edited.
     */
    public function loadStudentMark(): void
    {
        $this->studentMark = [];
        $this->studentMarkExisting = false;
        if (!$this->sMarkStandard || !$this->sMarkSection) return;

        $orgId = Auth::user()->organization_id;
        $students = StudentDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->sMarkStandard)
            ->where('section_id', $this->sMarkSection)
            ->whereNotNull('user_id')->get();

        $existing = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->sMarkDate)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->get()->keyBy('student_detail_id');

        $this->studentMarkExisting = $existing->isNotEmpty();
        $default = $this->defaultStatusFor($this->sMarkDate);

        foreach ($students as $s) {
            $rec = $existing->get($s->id);
            $this->studentMark[$s->id] = [
                'status'  => $rec ? $this->toLabel($rec->status) : $default,
                'remark'  => $rec->remarks ?? '',
                'user_id' => $s->user_id,
            ];
        }
    }

    public function setStudentStatus($studentId, string $status): void
    {
        if (isset($this->studentMark[$studentId])) {
            $this->studentMark[$studentId]['status'] = $status;
        }
    }

    public function markAllStudents(string $status): void
    {
        foreach ($this->studentMark as $id => $row) {
            $this->studentMark[$id]['status'] = $status;
        }
    }

    /** How many rows in the panel actually carry a status. */
    public function studentMarkedCount(): int
    {
        return $this->markedCount($this->studentMark);
    }

    /**
     * Persist $studentMark against $sMarkDate and push the students a notice.
     * Blank rows are left unwritten (and any earlier record removed) so the day
     * stays unmarked for them instead of defaulting to present.
     */
    private function persistStudentMark(): void
    {
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();
        $notifyRows = [];

        $valid = StudentDetail::where('organization_id', $orgId)
            ->whereIn('id', array_keys($this->studentMark))
            ->pluck('id')->flip();

        DB::transaction(function () use ($orgId, $markedBy, $valid, &$notifyRows) {
            $clear = [];

            foreach ($this->studentMark as $studentId => $row) {
                if (!$valid->has($studentId)) {
                    continue;
                }
                if (($row['status'] ?? '') === '') {
                    $clear[] = $studentId;
                    continue;
                }
                if (!in_array($row['status'], self::STATUSES, true)) {
                    continue;
                }
                $statusInt = $this->toInt($row['status']);
                StudentAttendance::updateOrCreate(
                    ['student_detail_id' => $studentId, 'organization_id' => $orgId, 'attendance_date' => $this->sMarkDate],
                    ['user_id' => $row['user_id'] ?? 0, 'status' => $statusInt, 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
                );
                if (!empty($row['user_id'])) {
                    $notifyRows[] = ['user_id' => $row['user_id'], 'status' => $statusInt];
                }
            }

            if ($clear) {
                StudentAttendance::where('organization_id', $orgId)
                    ->whereIn('student_detail_id', $clear)
                    ->whereDate('attendance_date', $this->sMarkDate)->delete();
            }
        });

        try {
            app(\App\Services\AppPushNotifier::class)->attendanceMarked($notifyRows);
        } catch (\Throwable $e) {
            logger()->warning('attendanceMarked push failed: ' . $e->getMessage());
        }
    }

    /** After saving, point the record view at exactly what was just marked. */
    private function showMarkedStudentDay(string $date): void
    {
        $this->studentView = 'by_date';
        $this->stStandard  = $this->sMarkStandard;
        $this->stSection   = $this->sMarkSection;
        $this->stDate      = $date;
        $this->closeStudentMark();
    }

    public function submitStudentAttendance(): void
    {
        if (!$this->sMarkStandard || !$this->sMarkSection) {
            $this->notification()->error('Select class and section first.');
            return;
        }
        if ($this->studentMarkedCount() === 0) {
            $this->notification()->error('Nothing to save', 'Mark at least one student first.');
            return;
        }

        $wasEdit = $this->studentMarkExisting;
        $date = $this->sMarkDate;

        $this->persistStudentMark();
        $this->showMarkedStudentDay($date);

        $this->notification()->success(
            $wasEdit ? 'Attendance updated' : 'Attendance successful',
            'Student attendance ' . ($wasEdit ? 'updated' : 'saved') . ' for ' . Carbon::parse($date)->format('d M Y') . '.'
        );
    }

    /** Panel header button — the whole day becomes a holiday for this section. */
    public function markStudentDayHoliday(): void
    {
        if (!$this->sMarkStandard || !$this->sMarkSection) {
            $this->notification()->error('Select class and section first.');
            return;
        }

        foreach ($this->studentMark as $id => $row) {
            $this->studentMark[$id]['status'] = 'holiday';
        }

        $date = $this->sMarkDate;
        $this->persistStudentMark();
        $this->showMarkedStudentDay($date);

        $this->notification()->success(
            'Holiday marked',
            Carbon::parse($date)->format('d M Y') . ' is now a holiday for this class.'
        );
    }

    // Cascading resets for student view selectors
    public function updatedStStudentId(): void {}

    // ═══════════════════════════ ASSIGN CLASS TEACHER ════════════════════════
    public function openAssignPanel(): void
    {
        $this->resetErrorBag();
        $this->assignEditId = null;
        $this->assignTeacherId = '';
        $this->assignStandardId = '';
        $this->assignSectionId = '';
        $this->showAssignPanel = true;
    }

    public function editAssign(int $id): void
    {
        $a = AssignTeacherStandard::find($id);
        if ($a) {
            $this->assignEditId = $id;
            $this->assignTeacherId = $a->teacher_detail_id;
            $this->assignStandardId = $a->standard_id;
            $this->assignSectionId = $a->section_id ?: '';
            $this->showAssignPanel = true;
        }
    }

    public function closeAssignPanel(): void
    {
        $this->showAssignPanel = false;
    }

    public function saveAssign(): void
    {
        $this->validate([
            'assignTeacherId'  => 'required|exists:teacher_details,id',
            'assignStandardId' => 'required|exists:standards,id',
            'assignSectionId'  => 'nullable|exists:sections,id',
        ]);

        $orgId = Auth::user()->organization_id;

        $dup = AssignTeacherStandard::where('organization_id', $orgId)
            ->where('teacher_detail_id', $this->assignTeacherId)
            ->where('standard_id', $this->assignStandardId)
            ->when($this->assignSectionId, fn($q) => $q->where('section_id', $this->assignSectionId))
            ->when($this->assignEditId, fn($q) => $q->where('id', '!=', $this->assignEditId))
            ->exists();

        if ($dup) {
            $this->notification()->error('This teacher is already assigned to this class/section.');
            return;
        }

        AssignTeacherStandard::updateOrCreate(
            ['id' => $this->assignEditId],
            [
                'organization_id'   => $orgId,
                'teacher_detail_id' => $this->assignTeacherId,
                'standard_id'       => $this->assignStandardId,
                // section_id is NOT NULL default 0 (foreignIdFor ->default(0));
                // writing null 500s, so use 0 for "no section".
                'section_id'        => $this->assignSectionId ?: 0,
            ]
        );

        $this->notification()->success($this->assignEditId ? 'Assignment updated.' : 'Class teacher assigned.');
        $this->closeAssignPanel();
    }

    public function confirmDeleteAssign(int $id): void { $this->pendingDeleteAssignId = $id; }
    public function cancelDeleteAssign(): void { $this->pendingDeleteAssignId = null; }
    public function executeDeleteAssign(): void
    {
        if ($this->pendingDeleteAssignId) {
            AssignTeacherStandard::where('id', $this->pendingDeleteAssignId)
                ->where('organization_id', Auth::user()->organization_id)->delete();
            $this->notification()->success('Assignment removed.');
        }
        $this->pendingDeleteAssignId = null;
    }

    public function updatedCtFilterStandard(): void { $this->ctFilterSection = ''; }

    /**
     * Switch the class-teacher lookup method. Always clears every filter so a
     * class picked in "By Class" can't linger and silently narrow the "By
     * Teacher" results (which was hiding a selected teacher's assignment).
     */
    public function setCtMode(string $mode): void
    {
        $this->ctMode = in_array($mode, ['by_class', 'by_teacher'], true) ? $mode : 'by_class';
        $this->clearCtFilters();
    }

    public function clearCtFilters(): void
    {
        $this->ctFilterStandard = '';
        $this->ctFilterSection = '';
        $this->ctFilterTeacher = '';
    }

    // ═══════════════════════════════ BUILDERS ════════════════════════════════
    /**
     * Build one small calendar card per month between $start and $end — the
     * same shape the payroll screen renders: a Sunday-first grid with a leading
     * blank count, per-month counts and a present-%, plus the period totals.
     *
     * $records = [Y-m-d => int status]. A day with no record reads as a holiday
     * when it is a Sunday (standing school holiday) and as "not marked"
     * otherwise. Days beyond today are left out of the counts entirely.
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
                // Sunday-first grid: how many blank cells sit before the 1st.
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

    /** Month cards for a single Y-m month. */
    private function monthCardsFor(string $model, string $fk, $personId, string $monthStr, int $orgId): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $recs  = $this->personRangeRecords($model, $fk, $personId, $start, $start->copy()->endOfMonth(), $orgId);

        return $this->buildMonthCards($recs, $start, $start->copy()->endOfMonth());
    }

    /** Month cards for a whole school year — April of $year → March of $year+1. */
    private function yearCardsFor(string $model, string $fk, $personId, int $year, int $orgId): array
    {
        $start = Carbon::create($year, 4, 1)->startOfDay();
        $end   = Carbon::create($year + 1, 3, 31)->endOfDay();
        $recs  = $this->personRangeRecords($model, $fk, $personId, $start, $end, $orgId);

        return $this->buildMonthCards($recs, $start, $end);
    }

    /** "Apr 2026 – Mar 2027" for the year picker. */
    private function academicYearLabel(int $year): string
    {
        return 'Apr ' . $year . ' – Mar ' . ($year + 1);
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

    // ═══════════════════════════════ RENDER ══════════════════════════════════
    public function render()
    {
        $orgId = Auth::user()->organization_id;

        $standards = Standard::where('organization_id', $orgId)->orderBy('id')->get(['id', 'name']);
        $teachers  = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get()
            ->sortBy(fn($t) => $t->user->name ?? '')->values();

        // School years to choose from, newest first — each runs April → March.
        $thisAy = self::academicYearOf(now());
        $academicYears = range($thisAy, $thisAy - 5);

        // ── Class Teachers tab: filtered assignment list ──
        // Mode-aware: "By Class" only filters on class/section, "By Teacher" only
        // on the teacher — so a filter from one method never leaks into the other.
        $assignments = AssignTeacherStandard::with(['teacher.user:id,name,email,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($this->ctMode === 'by_class' && $this->ctFilterStandard, fn($q) => $q->where('standard_id', $this->ctFilterStandard))
            ->when($this->ctMode === 'by_class' && $this->ctFilterSection,  fn($q) => $q->where('section_id', $this->ctFilterSection))
            ->when($this->ctMode === 'by_teacher' && $this->ctFilterTeacher, fn($q) => $q->where('teacher_detail_id', $this->ctFilterTeacher))
            ->latest()->get();
        $ctSections = $this->ctFilterStandard ? Section::where('standard_id', $this->ctFilterStandard)->orderBy('id')->get(['id', 'name']) : collect();

        // ── Teacher mark list (the slide-in panel) ──
        $markTeachers = $this->showTeacherMarkPanel ? $teachers : collect();

        // ── Teacher: by date ──
        $tByDateRows = collect();
        $tByDateStats = null;
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_date') {
            $recs = TeacherAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->tDate)->get()->keyBy('teacher_detail_id');
            $unmarked = $this->unmarkedStatusFor($this->tDate);
            $tByDateRows = $teachers->map(function ($t) use ($recs, $unmarked) {
                $rec = $recs->get($t->id);
                return [
                    'name'   => $t->user->name ?? '—',
                    'email'  => $t->user->email ?? '',
                    'image'  => $t->user->image ?? null,
                    'status' => $rec ? $this->toLabel($rec->status) : $unmarked,
                    'remark' => $rec->remarks ?? '',
                ];
            });
            $tByDateStats = $this->tallyLabels($tByDateRows->pluck('status'));
            // Present/absent (etc.) filter narrows the displayed rows; the stats
            // above stay computed on the full list so the totals remain meaningful.
            if ($this->tByDateStatus !== '') {
                $tByDateRows = $tByDateRows->where('status', $this->tByDateStatus)->values();
            }
        }

        // ── Teacher: month cards (by_month, and by_teacher monthly/yearly) ──
        $tCards = null; $tCardsTitle = ''; $tCardsPerson = '';
        if ($this->mainTab === 'teacher' && $this->tTeacherId) {
            $tCardsPerson = $teachers->firstWhere('id', (int) $this->tTeacherId)?->user?->name ?? '';

            if ($this->teacherView === 'by_month' && $this->tMonth) {
                $tCards = $this->monthCardsFor(TeacherAttendance::class, 'teacher_detail_id', $this->tTeacherId, $this->tMonth, $orgId);
                $tCardsTitle = Carbon::createFromFormat('Y-m-d', $this->tMonth . '-01')->format('F Y');
            } elseif ($this->teacherView === 'by_teacher') {
                if ($this->tRange === 'yearly' && $this->tYear) {
                    $tCards = $this->yearCardsFor(TeacherAttendance::class, 'teacher_detail_id', $this->tTeacherId, (int) $this->tYear, $orgId);
                    $tCardsTitle = $this->academicYearLabel((int) $this->tYear);
                } elseif ($this->tRange === 'monthly' && $this->tMonth) {
                    $tCards = $this->monthCardsFor(TeacherAttendance::class, 'teacher_detail_id', $this->tTeacherId, $this->tMonth, $orgId);
                    $tCardsTitle = Carbon::createFromFormat('Y-m-d', $this->tMonth . '-01')->format('F Y');
                }
            }
        }

        // ── Student sections + students dropdown ──
        $stSections = $this->stStandard ? Section::where('standard_id', $this->stStandard)->orderBy('id')->get(['id', 'name']) : collect();
        $stStudents = collect();
        if ($this->stStandard && $this->stSection) {
            $stStudents = StudentDetail::with('user:id,name,email,image')
                ->where('organization_id', $orgId)->where('standard_id', $this->stStandard)
                ->where('section_id', $this->stSection)->whereNotNull('user_id')
                ->get()->sortBy(fn($s) => $s->user->name ?? '')->values();
        }

        // ── Student mark panel: its own class → section → student list ──
        $sMarkSections = $this->sMarkStandard
            ? Section::where('standard_id', $this->sMarkStandard)->orderBy('id')->get(['id', 'name'])
            : collect();
        $markStudents = collect();
        if ($this->showStudentMarkPanel && $this->sMarkStandard && $this->sMarkSection) {
            $markStudents = StudentDetail::with('user:id,name,email,image')
                ->where('organization_id', $orgId)->where('standard_id', $this->sMarkStandard)
                ->where('section_id', $this->sMarkSection)->whereNotNull('user_id')
                ->get()->sortBy(fn($s) => $s->user->name ?? '')->values();
        }

        // ── Student: by date ──
        $sByDateRows = collect();
        $sByDateStats = null;
        if ($this->mainTab === 'student' && $this->studentView === 'by_date' && $this->stStandard && $this->stSection) {
            $recs = StudentAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->stDate)
                ->whereIn('student_detail_id', $stStudents->pluck('id'))->get()->keyBy('student_detail_id');
            $unmarked = $this->unmarkedStatusFor($this->stDate);
            $sByDateRows = $stStudents->map(function ($s) use ($recs, $unmarked) {
                $rec = $recs->get($s->id);
                return [
                    'name'   => $s->user->name ?? ($s->full_name ?? '—'),
                    'email'  => $s->user->email ?? '',
                    'image'  => $s->user->image ?? null,
                    'status' => $rec ? $this->toLabel($rec->status) : $unmarked,
                    'remark' => $rec->remarks ?? '',
                ];
            });
            $sByDateStats = $this->tallyLabels($sByDateRows->pluck('status'));
        }

        // ── Student: month cards (by_student monthly / yearly) ──
        $sCards = null; $sCardsTitle = ''; $sCardsPerson = '';
        if ($this->mainTab === 'student' && $this->studentView === 'by_student' && $this->stStudentId) {
            $picked = $stStudents->firstWhere('id', (int) $this->stStudentId);
            $sCardsPerson = $picked ? ($picked->user?->name ?? $picked->full_name ?? '') : '';

            if ($this->stRange === 'yearly' && $this->stYear) {
                $sCards = $this->yearCardsFor(StudentAttendance::class, 'student_detail_id', $this->stStudentId, (int) $this->stYear, $orgId);
                $sCardsTitle = $this->academicYearLabel((int) $this->stYear);
            } elseif ($this->stRange === 'monthly' && $this->stMonth) {
                $sCards = $this->monthCardsFor(StudentAttendance::class, 'student_detail_id', $this->stStudentId, $this->stMonth, $orgId);
                $sCardsTitle = Carbon::createFromFormat('Y-m-d', $this->stMonth . '-01')->format('F Y');
            }
        }

        return view('livewire.accounts.attendance', compact(
            'standards', 'teachers', 'assignments', 'ctSections', 'markTeachers', 'academicYears',
            'tByDateRows', 'tByDateStats', 'tCards', 'tCardsTitle', 'tCardsPerson',
            'stSections', 'stStudents', 'markStudents', 'sMarkSections',
            'sByDateRows', 'sByDateStats', 'sCards', 'sCardsTitle', 'sCardsPerson'
        ));
    }

    /** Fetch a person's records between two dates, keyed by date => int status. */
    private function personRangeRecords(string $model, string $fk, $personId, Carbon $start, Carbon $end, int $orgId): array
    {
        return $model::where('organization_id', $orgId)
            ->where($fk, $personId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])
            ->toArray();
    }
}
