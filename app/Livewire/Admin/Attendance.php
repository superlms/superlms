<?php

namespace App\Livewire\Admin;

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

    // ── Teacher (shared selectors across its views) ─────────────────────────
    public string $teacherView = 'by_date';    // mark | by_date | by_month | by_teacher
    public string $teacherReturnView = 'by_date'; // view to return to after Mark → Submit
    public string $tDate  = '';                // mark + by_date
    public $tTeacherId    = '';                // by_month + by_teacher
    public string $tMonth = '';                // by_month + by_teacher(monthly)
    public $tYear         = '';                // by_teacher(yearly)
    public string $tRange = 'monthly';         // by_teacher: monthly | yearly
    public string $tByDateStatus = '';         // by_date: '' | present | absent | half_day | holiday | not_marked
    public array  $teacherMark = [];           // [teacher_detail_id => ['status','remark']]

    // ── Student (shared selectors across its views) ─────────────────────────
    public string $studentView = 'by_date';    // mark | by_date | by_student
    public string $studentReturnView = 'by_date'; // view to return to after Mark → Save
    public $stStandard = '';
    public $stSection  = '';
    public $stStudentId = '';
    public string $stDate  = '';               // mark + by_date
    public string $stMonth = '';               // by_month + by_student(monthly)
    public $stYear         = '';               // by_student(yearly)
    public string $stRange = 'monthly';        // by_student: monthly | yearly
    public array  $studentMark = [];

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
        $year  = now()->format('Y');

        $this->tDate   = $today;
        $this->tMonth  = $month;
        $this->tYear   = $year;
        $this->stDate  = $today;
        $this->stMonth = $month;
        $this->stYear  = $year;

        $this->loadTeacherMark();
    }

    // ── Status helpers ───────────────────────────────────────────────────────
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

    // ═══════════════════════════════ TAB SWITCHING ═══════════════════════════
    public function switchMainTab(string $tab): void
    {
        $this->mainTab = $tab;
    }

    public function switchTeacherView(string $v): void
    {
        $this->teacherView = $v;
        if ($v === 'mark') $this->loadTeacherMark();
    }

    /** Header "Mark Attendance" — remember the current record view, then enter mark mode. */
    public function openTeacherMark(): void
    {
        if ($this->teacherView !== 'mark') {
            $this->teacherReturnView = $this->teacherView;
        }
        $this->teacherView = 'mark';
        $this->loadTeacherMark();
    }

    /** Header "Back to Records" — leave mark mode without saving. */
    public function closeTeacherMark(): void
    {
        $this->teacherView = $this->teacherReturnView ?: 'by_date';
    }

    public function switchStudentView(string $v): void
    {
        $this->studentView = $v;
        if ($v === 'mark') $this->loadStudentMark();
    }

    /** Header "Mark Attendance" — remember the current record view, then enter mark mode. */
    public function openStudentMark(): void
    {
        if ($this->studentView !== 'mark') {
            $this->studentReturnView = $this->studentView;
        }
        $this->studentView = 'mark';
        $this->loadStudentMark();
    }

    /** Header "Back to Records" — leave mark mode without saving. */
    public function closeStudentMark(): void
    {
        $this->studentView = $this->studentReturnView ?: 'by_date';
    }

    // ═══════════════════════════════ TEACHER: MARK ═══════════════════════════
    public function updatedTDate(): void
    {
        if ($this->teacherView === 'mark') $this->loadTeacherMark();
    }

    public function loadTeacherMark(): void
    {
        $orgId = Auth::user()->organization_id;
        $teachers = TeacherDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)->get();

        $existing = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->tDate)
            ->get()->keyBy('teacher_detail_id');

        $this->teacherMark = [];
        foreach ($teachers as $t) {
            $rec = $existing->get($t->id);
            $this->teacherMark[$t->id] = [
                'status' => $rec ? $this->toLabel($rec->status) : 'present',
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

    public function submitTeacherAttendance(): void
    {
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();

        DB::transaction(function () use ($orgId, $markedBy) {
            foreach ($this->teacherMark as $teacherId => $row) {
                TeacherAttendance::updateOrCreate(
                    ['teacher_detail_id' => $teacherId, 'organization_id' => $orgId, 'attendance_date' => $this->tDate],
                    ['status' => $this->toInt($row['status']), 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
                );
            }
        });

        // Return to the record view we came from ("redirect to previous screen").
        $this->teacherView = $this->teacherReturnView ?: 'by_date';

        $this->notification()->success(
            'Attendance successful',
            'Teacher attendance saved for ' . Carbon::parse($this->tDate)->format('d M Y') . '.'
        );
    }

    // ═══════════════════════════════ STUDENT: MARK ═══════════════════════════
    public function updatedStStandard(): void
    {
        $this->stSection = '';
        $this->stStudentId = '';
        $this->studentMark = [];
    }

    public function updatedStSection(): void
    {
        $this->stStudentId = '';
        if ($this->studentView === 'mark') $this->loadStudentMark();
    }

    public function updatedStDate(): void
    {
        if ($this->studentView === 'mark') $this->loadStudentMark();
    }

    public function loadStudentMark(): void
    {
        $this->studentMark = [];
        if (!$this->stStandard || !$this->stSection) return;

        $orgId = Auth::user()->organization_id;
        $students = StudentDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->stStandard)
            ->where('section_id', $this->stSection)
            ->whereNotNull('user_id')->get();

        $existing = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->stDate)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->get()->keyBy('student_detail_id');

        foreach ($students as $s) {
            $rec = $existing->get($s->id);
            $this->studentMark[$s->id] = [
                'status'  => $rec ? $this->toLabel($rec->status) : 'present',
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

    public function submitStudentAttendance(): void
    {
        if (!$this->stStandard || !$this->stSection) {
            $this->notification()->error('Select class and section first.');
            return;
        }
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();
        $notifyRows = [];

        DB::transaction(function () use ($orgId, $markedBy, &$notifyRows) {
            foreach ($this->studentMark as $studentId => $row) {
                $statusInt = $this->toInt($row['status']);
                StudentAttendance::updateOrCreate(
                    ['student_detail_id' => $studentId, 'organization_id' => $orgId, 'attendance_date' => $this->stDate],
                    ['user_id' => $row['user_id'] ?? 0, 'status' => $statusInt, 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
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

        // Return to the record view we came from ("redirect to previous screen").
        $this->studentView = $this->studentReturnView ?: 'by_date';

        $this->notification()->success(
            'Attendance successful',
            'Student attendance saved for ' . Carbon::parse($this->stDate)->format('d M Y') . '.'
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
            $this->assignSectionId = $a->section_id;
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
                'section_id'        => $this->assignSectionId ?: null,
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

    /** Switching between the "By Class" and "By Teacher" lookup methods resets the filters. */
    public function updatedCtMode(): void
    {
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
     * Month calendar grid. $records = [Y-m-d => int status].
     */
    private function buildCalendar(string $monthStr, array $records): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $daysInMonth = $start->copy()->endOfMonth()->day;

        $weeks = [];
        $week = array_fill(0, 7, null);
        $col = (int) $start->dayOfWeek; // 0=Sun..6=Sat

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

    /**
     * Yearly summary — 12 month cards + year totals. $records = [Y-m-d => int].
     */
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

    // ═══════════════════════════════ RENDER ══════════════════════════════════
    public function render()
    {
        $orgId = Auth::user()->organization_id;

        $standards = Standard::where('organization_id', $orgId)->orderBy('id')->get(['id', 'name']);
        $teachers  = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get()
            ->sortBy(fn($t) => $t->user->name ?? '')->values();

        // ── Class Teachers tab: filtered assignment list ──
        $assignments = AssignTeacherStandard::with(['teacher.user:id,name,email,image', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)
            ->when($this->ctFilterStandard, fn($q) => $q->where('standard_id', $this->ctFilterStandard))
            ->when($this->ctFilterSection,  fn($q) => $q->where('section_id', $this->ctFilterSection))
            ->when($this->ctFilterTeacher,  fn($q) => $q->where('teacher_detail_id', $this->ctFilterTeacher))
            ->latest()->get();
        $ctSections = $this->ctFilterStandard ? Section::where('standard_id', $this->ctFilterStandard)->orderBy('id')->get(['id', 'name']) : collect();

        // ── Teacher mark list ──
        $markTeachers = $teachers;

        // ── Teacher: by date ──
        $tByDateRows = collect();
        $tByDateStats = null;
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_date') {
            $recs = TeacherAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->tDate)->get()->keyBy('teacher_detail_id');
            $tByDateRows = $teachers->map(function ($t) use ($recs) {
                $rec = $recs->get($t->id);
                return [
                    'name'   => $t->user->name ?? '—',
                    'email'  => $t->user->email ?? '',
                    'image'  => $t->user->image ?? null,
                    'status' => $rec ? $this->toLabel($rec->status) : 'not_marked',
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

        // ── Teacher: by month (calendar for a teacher) ──
        $tMonthCalendar = null;
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_month' && $this->tTeacherId && $this->tMonth) {
            $recs = $this->personMonthRecords(TeacherAttendance::class, 'teacher_detail_id', $this->tTeacherId, $this->tMonth, $orgId);
            $tMonthCalendar = $this->buildCalendar($this->tMonth, $recs);
        }

        // ── Teacher: by teacher (monthly calendar OR yearly cards) ──
        $tTeacherCalendar = null; $tTeacherYearly = null;
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_teacher' && $this->tTeacherId) {
            if ($this->tRange === 'yearly' && $this->tYear) {
                $recs = TeacherAttendance::where('organization_id', $orgId)
                    ->where('teacher_detail_id', $this->tTeacherId)
                    ->whereYear('attendance_date', (int) $this->tYear)
                    ->get()->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])->toArray();
                $tTeacherYearly = $this->buildYearly((int) $this->tYear, $recs);
            } elseif ($this->tRange === 'monthly' && $this->tMonth) {
                $recs = $this->personMonthRecords(TeacherAttendance::class, 'teacher_detail_id', $this->tTeacherId, $this->tMonth, $orgId);
                $tTeacherCalendar = $this->buildCalendar($this->tMonth, $recs);
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

        // ── Student: mark list ──
        $markStudents = ($this->mainTab === 'student' && $this->studentView === 'mark') ? $stStudents : collect();

        // ── Student: by date ──
        $sByDateRows = collect();
        $sByDateStats = null;
        if ($this->mainTab === 'student' && $this->studentView === 'by_date' && $this->stStandard && $this->stSection) {
            $recs = StudentAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->stDate)
                ->whereIn('student_detail_id', $stStudents->pluck('id'))->get()->keyBy('student_detail_id');
            $sByDateRows = $stStudents->map(function ($s) use ($recs) {
                $rec = $recs->get($s->id);
                return [
                    'name'   => $s->user->name ?? ($s->full_name ?? '—'),
                    'email'  => $s->user->email ?? '',
                    'image'  => $s->user->image ?? null,
                    'status' => $rec ? $this->toLabel($rec->status) : 'not_marked',
                    'remark' => $rec->remarks ?? '',
                ];
            });
            $sByDateStats = $this->tallyLabels($sByDateRows->pluck('status'));
        }

        // ── Student: by month (calendar) ──
        $sMonthCalendar = null;
        if ($this->mainTab === 'student' && $this->studentView === 'by_month' && $this->stStudentId && $this->stMonth) {
            $recs = $this->personMonthRecords(StudentAttendance::class, 'student_detail_id', $this->stStudentId, $this->stMonth, $orgId);
            $sMonthCalendar = $this->buildCalendar($this->stMonth, $recs);
        }

        // ── Student: by student (monthly calendar OR yearly cards) ──
        $sStudentCalendar = null; $sStudentYearly = null;
        if ($this->mainTab === 'student' && $this->studentView === 'by_student' && $this->stStudentId) {
            if ($this->stRange === 'yearly' && $this->stYear) {
                $recs = StudentAttendance::where('organization_id', $orgId)
                    ->where('student_detail_id', $this->stStudentId)
                    ->whereYear('attendance_date', (int) $this->stYear)
                    ->get()->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])->toArray();
                $sStudentYearly = $this->buildYearly((int) $this->stYear, $recs);
            } elseif ($this->stRange === 'monthly' && $this->stMonth) {
                $recs = $this->personMonthRecords(StudentAttendance::class, 'student_detail_id', $this->stStudentId, $this->stMonth, $orgId);
                $sStudentCalendar = $this->buildCalendar($this->stMonth, $recs);
            }
        }

        return view('livewire.admin.attendance', compact(
            'standards', 'teachers', 'assignments', 'ctSections', 'markTeachers',
            'tByDateRows', 'tByDateStats', 'tMonthCalendar', 'tTeacherCalendar', 'tTeacherYearly',
            'stSections', 'stStudents', 'markStudents',
            'sByDateRows', 'sByDateStats', 'sMonthCalendar', 'sStudentCalendar', 'sStudentYearly'
        ));
    }

    /** Fetch a person's records for a Y-m month keyed by date => int status. */
    private function personMonthRecords(string $model, string $fk, $personId, string $monthStr, int $orgId): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();

        return $model::where('organization_id', $orgId)
            ->where($fk, $personId)
            ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => (int) $r->status])
            ->toArray();
    }
}
