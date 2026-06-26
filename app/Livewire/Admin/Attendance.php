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

    // present = 1, absent = 0
    public string $mainTab = 'teacher';        // teacher | student
    public string $teacherView = 'mark';       // mark | by_date | by_teacher
    public string $studentView = 'by_date';    // by_date | by_student | by_class

    // ── Teacher: Mark ────────────────────────────────────────────────────────
    public string $markDate = '';
    public array $teacherMark = [];            // [teacher_detail_id => ['status'=>'present'|'absent','remark'=>'']]

    // ── Teacher: By date ──────────────────────────────────────────────────────
    public string $byDateTeacherDate = '';

    // ── Teacher: By teacher ──────────────────────────────────────────────────
    public string $byTeacherMonth = '';
    public $byTeacherId = '';

    // ── Student: By date (markable list) ─────────────────────────────────────
    public string $sdDate = '';
    public $sdStandard = '';
    public $sdSection = '';
    public array $studentMark = [];

    // ── Student: By student (calendar) ───────────────────────────────────────
    public string $ssMonth = '';
    public $ssStandard = '';
    public $ssSection = '';
    public $ssStudentId = '';

    // ── Student: By class ────────────────────────────────────────────────────
    public $scStandard = '';
    public $scSection = '';
    public string $scDate = '';

    // ── Assign class teacher panel ───────────────────────────────────────────
    public bool $showAssignPanel = false;
    public ?int $assignEditId = null;
    public $assignTeacherId = '';
    public $assignStandardId = '';
    public $assignSectionId = '';
    public ?int $pendingDeleteAssignId = null;

    public function mount(): void
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');
        $this->markDate = $today;
        $this->byDateTeacherDate = $today;
        $this->byTeacherMonth = $month;
        $this->sdDate = $today;
        $this->ssMonth = $month;
        $this->scDate = $today;
        $this->loadTeacherMark();
    }

    // ═══════════════════════════════ TAB SWITCHING ═══════════════════════════
    public function switchMainTab(string $tab): void { $this->mainTab = $tab; }
    public function switchTeacherView(string $v): void
    {
        $this->teacherView = $v;
        if ($v === 'mark') $this->loadTeacherMark();
    }
    public function switchStudentView(string $v): void { $this->studentView = $v; }

    // ═══════════════════════════════ TEACHER: MARK ═══════════════════════════
    public function updatedMarkDate(): void { $this->loadTeacherMark(); }

    public function loadTeacherMark(): void
    {
        $orgId = Auth::user()->organization_id;
        $teachers = TeacherDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)
            ->get();

        $existing = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->markDate)
            ->get()->keyBy('teacher_detail_id');

        $this->teacherMark = [];
        foreach ($teachers as $t) {
            $rec = $existing->get($t->id);
            $this->teacherMark[$t->id] = [
                'status' => $rec ? ((int) $rec->status === 1 ? 'present' : 'absent') : 'present', // default present
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

    public function submitTeacherAttendance(): void
    {
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();

        DB::transaction(function () use ($orgId, $markedBy) {
            foreach ($this->teacherMark as $teacherId => $row) {
                TeacherAttendance::updateOrCreate(
                    ['teacher_detail_id' => $teacherId, 'organization_id' => $orgId, 'attendance_date' => $this->markDate],
                    ['status' => $row['status'] === 'present' ? 1 : 0, 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
                );
            }
        });

        $this->notification()->success('Teacher attendance saved for ' . Carbon::parse($this->markDate)->format('d M Y'));
    }

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

    public function closeAssignPanel(): void { $this->showAssignPanel = false; }

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

    // ═══════════════════════════════ STUDENT: BY DATE ════════════════════════
    public function updatedSdStandard(): void { $this->sdSection = ''; $this->studentMark = []; }
    public function updatedSdSection(): void { $this->loadStudentMark(); }
    public function updatedSdDate(): void { $this->loadStudentMark(); }

    public function loadStudentMark(): void
    {
        $this->studentMark = [];
        if (!$this->sdStandard || !$this->sdSection) return;

        $orgId = Auth::user()->organization_id;
        $students = StudentDetail::with('user:id,name,email,image')
            ->where('organization_id', $orgId)
            ->where('standard_id', $this->sdStandard)
            ->where('section_id', $this->sdSection)
            ->whereNotNull('user_id')
            ->get();

        $existing = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $this->sdDate)
            ->whereIn('student_detail_id', $students->pluck('id'))
            ->get()->keyBy('student_detail_id');

        foreach ($students as $s) {
            $rec = $existing->get($s->id);
            $this->studentMark[$s->id] = [
                'status' => $rec ? ((int) $rec->status === 1 ? 'present' : 'absent') : 'present',
                'remark' => $rec->remarks ?? '',
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

    public function submitStudentAttendance(): void
    {
        if (!$this->sdStandard || !$this->sdSection) {
            $this->notification()->error('Select class and section first.');
            return;
        }
        $orgId = Auth::user()->organization_id;
        $markedBy = Auth::id();

        $notifyRows = [];

        DB::transaction(function () use ($orgId, $markedBy, &$notifyRows) {
            foreach ($this->studentMark as $studentId => $row) {
                $status = $row['status'] === 'present' ? 1 : 0;
                StudentAttendance::updateOrCreate(
                    ['student_detail_id' => $studentId, 'organization_id' => $orgId, 'attendance_date' => $this->sdDate],
                    ['user_id' => $row['user_id'] ?? 0, 'status' => $status, 'remarks' => $row['remark'] ?? '', 'marked_by' => $markedBy]
                );
                if (!empty($row['user_id'])) {
                    $notifyRows[] = ['user_id' => $row['user_id'], 'status' => $status];
                }
            }
        });

        // Notify each student about their marked attendance (mobile app push).
        app(\App\Services\AppPushNotifier::class)->attendanceMarked($notifyRows);

        $this->notification()->success('Student attendance saved for ' . Carbon::parse($this->sdDate)->format('d M Y'));
    }

    // Cascading section resets for other student views
    public function updatedSsStandard(): void { $this->ssSection = ''; $this->ssStudentId = ''; }
    public function updatedSsSection(): void { $this->ssStudentId = ''; }
    public function updatedScStandard(): void { $this->scSection = ''; }

    // ═══════════════════════════════ CALENDAR HELPER ═════════════════════════
    /**
     * Build a month calendar grid.
     * @param string $monthStr Y-m
     * @param array  $records  [Y-m-d => status(int 1/0)]
     * @return array ['weeks'=>[[cell...]], 'totals'=>[...]]
     */
    private function buildCalendar(string $monthStr, array $records): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $monthStr . '-01')->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $daysInMonth = $end->day;

        $weeks = [];
        $week = array_fill(0, 7, null);
        // Carbon dayOfWeek: 0=Sun..6=Sat
        $firstDow = (int) $start->dayOfWeek;

        $present = 0; $absent = 0; $working = 0;
        $dayPtr = 1;
        $col = $firstDow;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = $start->copy()->day($d)->toDateString();
            $status = $records[$date] ?? null; // null => holiday
            $cell = ['day' => $d, 'date' => $date, 'status' => 'holiday'];
            if ($status !== null) {
                $working++;
                if ((int) $status === 1) { $cell['status'] = 'present'; $present++; }
                else { $cell['status'] = 'absent'; $absent++; }
            }
            $week[$col] = $cell;
            $col++;
            if ($col === 7) { $weeks[] = $week; $week = array_fill(0, 7, null); $col = 0; }
        }
        if ($col !== 0) $weeks[] = $week;

        return [
            'weeks'  => $weeks,
            'totals' => [
                'total_days'   => $daysInMonth,
                'working_days' => $working,
                'present_days' => $present,
                'absent_days'  => $absent,
            ],
        ];
    }

    public function render()
    {
        $orgId = Auth::user()->organization_id;

        $standards = Standard::where('organization_id', $orgId)->orderBy('order')->get(['id', 'name']);
        $teachers  = TeacherDetail::with('user:id,name,email,image')->where('organization_id', $orgId)->get();

        // Assign-teacher list
        $assignments = AssignTeacherStandard::with(['teacher.user:id,name,email', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $orgId)->latest()->get();

        // ── Teacher mark list (ordered) ──
        $markTeachers = $teachers->sortBy(fn($t) => $t->user->name ?? '')->values();

        // ── Teacher: by date ──
        $byDateTeacherRows = collect();
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_date') {
            $recs = TeacherAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->byDateTeacherDate)->get()->keyBy('teacher_detail_id');
            $byDateTeacherRows = $teachers->sortBy(fn($t) => $t->user->name ?? '')->values()->map(function ($t) use ($recs) {
                $rec = $recs->get($t->id);
                return [
                    'name'   => $t->user->name ?? '—',
                    'email'  => $t->user->email ?? '',
                    'image'  => $t->user->image ?? null,
                    'status' => $rec ? ((int) $rec->status === 1 ? 'present' : 'absent') : 'holiday',
                    'remark' => $rec->remarks ?? '',
                ];
            });
        }

        // ── Teacher: by teacher (calendar) ──
        $teacherCalendar = null;
        if ($this->mainTab === 'teacher' && $this->teacherView === 'by_teacher' && $this->byTeacherId) {
            $start = Carbon::createFromFormat('Y-m-d', $this->byTeacherMonth . '-01')->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $recs = TeacherAttendance::where('organization_id', $orgId)
                ->where('teacher_detail_id', $this->byTeacherId)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => $r->status]);
            $teacherCalendar = $this->buildCalendar($this->byTeacherMonth, $recs->toArray());
        }

        // ── Student: by date sections ──
        $sdSections = ($this->sdStandard) ? Section::where('standard_id', $this->sdStandard)->orderBy('name')->get(['id', 'name']) : collect();
        $ssSections = ($this->ssStandard) ? Section::where('standard_id', $this->ssStandard)->orderBy('name')->get(['id', 'name']) : collect();
        $scSections = ($this->scStandard) ? Section::where('standard_id', $this->scStandard)->orderBy('name')->get(['id', 'name']) : collect();

        // student list for by_student dropdown
        $ssStudents = collect();
        if ($this->ssStandard && $this->ssSection) {
            $ssStudents = StudentDetail::with('user:id,name')
                ->where('organization_id', $orgId)->where('standard_id', $this->ssStandard)
                ->where('section_id', $this->ssSection)->whereNotNull('user_id')->get();
        }

        // ── Student: by student (calendar) ──
        $studentCalendar = null;
        if ($this->mainTab === 'student' && $this->studentView === 'by_student' && $this->ssStudentId) {
            $start = Carbon::createFromFormat('Y-m-d', $this->ssMonth . '-01')->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $recs = StudentAttendance::where('organization_id', $orgId)
                ->where('student_detail_id', $this->ssStudentId)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()->mapWithKeys(fn($r) => [Carbon::parse($r->attendance_date)->toDateString() => $r->status]);
            $studentCalendar = $this->buildCalendar($this->ssMonth, $recs->toArray());
        }

        // ── Student: by class ──
        $byClassRows = collect();
        if ($this->mainTab === 'student' && $this->studentView === 'by_class' && $this->scStandard && $this->scSection) {
            $students = StudentDetail::with('user:id,name,email,image')
                ->where('organization_id', $orgId)->where('standard_id', $this->scStandard)
                ->where('section_id', $this->scSection)->whereNotNull('user_id')->get();
            $recs = StudentAttendance::where('organization_id', $orgId)
                ->whereDate('attendance_date', $this->scDate)
                ->whereIn('student_detail_id', $students->pluck('id'))->get()->keyBy('student_detail_id');
            $byClassRows = $students->sortBy(fn($s) => $s->user->name ?? '')->values()->map(function ($s) use ($recs) {
                $rec = $recs->get($s->id);
                return [
                    'name'   => $s->user->name ?? ($s->full_name ?? '—'),
                    'email'  => $s->user->email ?? '',
                    'image'  => $s->user->image ?? null,
                    'status' => $rec ? ((int) $rec->status === 1 ? 'present' : 'absent') : 'holiday',
                    'remark' => $rec->remarks ?? '',
                ];
            });
        }

        // student mark list (by_date)
        $sdStudents = collect();
        if ($this->mainTab === 'student' && $this->studentView === 'by_date' && $this->sdStandard && $this->sdSection) {
            $sdStudents = StudentDetail::with('user:id,name,email,image')
                ->where('organization_id', $orgId)->where('standard_id', $this->sdStandard)
                ->where('section_id', $this->sdSection)->whereNotNull('user_id')
                ->get()->sortBy(fn($s) => $s->user->name ?? '')->values();
        }

        return view('livewire.admin.attendance', compact(
            'standards', 'teachers', 'assignments', 'markTeachers',
            'byDateTeacherRows', 'teacherCalendar',
            'sdSections', 'ssSections', 'scSections', 'ssStudents', 'sdStudents',
            'studentCalendar', 'byClassRows'
        ));
    }
}
