<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Student\StudentDetail;
use App\Models\Student\Standard;
use App\Models\Student\Section;
use App\Models\Student\StudentAttendance;
use App\Models\Admin\Announcement;
// use App\Models\Admin\Homework;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TeacherArrangement;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\WebsiteContact;

class Analytics extends Component
{
    // ─── Filters ─────────────────────────────────────────────────────────────
    public $attendanceFilter = '7';
    public $teacherAttFilter = '7';
    public $performerClass   = '';
    public $performerSection = '';

    // ─── Arrangement ─────────────────────────────────────────────────────────
    public $selectedTeachers  = [];
    public $availableTeachers = [];

    // ─── Data ─────────────────────────────────────────────────────────────────
    public $statsData                = [];
    public $studentMonthlyAttendance = [];
    public $studentPieData           = [];
    public $teacherMonthlyAttendance = [];
    public $teacherPieData           = [];
    public $attendanceMonths         = [];
    public $topStudents              = [];
    public $classDistribution        = [];
    public $adminEnquiries           = [];
    public $arrangements             = [];
    public $announcements            = [];
    public $recentActivities         = [];
    public $todayHomework            = [];
    public $feeStats                 = [];
    public $feeClassData             = [];
    public $standards                = [];
    public $sections                 = [];

    protected $queryString = ['attendanceFilter', 'performerClass', 'performerSection'];

    // ─── DB status values ─────────────────────────────────────────────────────
    // student_attendances.status  → tinyint:  1 = present, 0 = absent
    // teacher_attendances.status  → boolean:  1 = present, 0 = absent
    const STU_PRESENT = 1;
    const STU_ABSENT  = 0;
    const TCH_PRESENT = 1;
    const TCH_ABSENT  = 0;

    public function mount(): void
    {
        $this->standards = Standard::where('organization_id', $this->orgId())
            ->orderBy('order')->get();
        $this->loadAll();
    }

    public function updatedAttendanceFilter(): void
    {
        $this->loadStudentAttendance();
        $this->loadStudentPie();
    }

    public function updatedTeacherAttFilter(): void
    {
        $this->loadTeacherAttendance();
        $this->loadTeacherPie();
    }

    public function updatedPerformerClass(): void
    {
        $this->loadSections();
        $this->performerSection = '';
        $this->loadTopStudents();
    }

    public function updatedPerformerSection(): void
    {
        $this->loadTopStudents();
    }

    public function saveArrangement(int $arrangementId): void
    {
        $teacherId = $this->selectedTeachers[$arrangementId] ?? null;
        if ($teacherId) {
            TeacherArrangement::where('id', $arrangementId)
                ->update(['substitute_teacher_id' => $teacherId]);
            $this->loadArrangements();
            session()->flash('arrangement_saved', true);
        }
    }

    // ─── Master load ──────────────────────────────────────────────────────────

    protected function loadAll(): void
    {
        $this->loadStats();
        $this->buildAttendanceMonths();
        $this->loadStudentAttendance();
        $this->loadStudentPie();
        $this->loadTeacherAttendance();
        $this->loadTeacherPie();
        $this->loadSections();
        $this->loadTopStudents();
        $this->loadClassDistribution();
        $this->loadAdminEnquiries();
        $this->loadArrangements();
        $this->loadAnnouncements();
        $this->loadRecentActivities();
        $this->loadTodayHomework();
        $this->loadFeeStatsStatic();
        $this->loadFeeClassDataStatic();
    }

    // ─── Stats Cards ──────────────────────────────────────────────────────────

    protected function loadStats(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();

        $totalStudents  = StudentDetail::where('organization_id', $orgId)->count();
        $activeStudents = StudentDetail::where('organization_id', $orgId)->count();

        // Column: attendance_date  |  status tinyint 1=present 0=absent
        $presentToday = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_PRESENT)
            ->count();

        $absentToday = StudentAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::STU_ABSENT)
            ->count();

        $newAdmissions = StudentDetail::where('organization_id', $orgId)
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $this->statsData = compact(
            'totalStudents',
            'activeStudents',
            'presentToday',
            'absentToday',
            'newAdmissions'
        );
    }

    // ─── Academic-year months (Apr – Mar) ─────────────────────────────────────

    protected function buildAttendanceMonths(): void
    {
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);

        $this->attendanceMonths = [];
        for ($i = 0; $i < 12; $i++) {
            $this->attendanceMonths[] = $yearStart->copy()->addMonths($i)->format('M Y');
        }
    }

    // ─── Student Attendance Bar ───────────────────────────────────────────────

    protected function loadStudentAttendance(): void
    {
        $orgId     = $this->orgId();
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);

        $present = [];
        $absent  = [];

        for ($i = 0; $i < 12; $i++) {
            $mStart = $yearStart->copy()->addMonths($i)->startOfMonth();
            $mEnd   = $yearStart->copy()->addMonths($i)->endOfMonth();

            $present[] = StudentAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::STU_PRESENT)
                ->count();

            $absent[] = StudentAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::STU_ABSENT)
                ->count();
        }

        $this->studentMonthlyAttendance = compact('present', 'absent');
    }

    // ─── Student Pie ──────────────────────────────────────────────────────────

    protected function loadStudentPie(): void
    {
        $orgId = $this->orgId();
        $from  = Carbon::now()->subDays((int) $this->attendanceFilter - 1)->startOfDay();

        $present = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::STU_PRESENT)
            ->count();

        $absent = StudentAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::STU_ABSENT)
            ->count();

        $this->studentPieData = compact('present', 'absent');
    }

    // ─── Teacher Attendance Bar ───────────────────────────────────────────────

    protected function loadTeacherAttendance(): void
    {
        $orgId     = $this->orgId();
        $now       = Carbon::now();
        $yearStart = $now->month >= 4
            ? Carbon::create($now->year, 4, 1)
            : Carbon::create($now->year - 1, 4, 1);

        $present = [];
        $absent  = [];

        for ($i = 0; $i < 12; $i++) {
            $mStart = $yearStart->copy()->addMonths($i)->startOfMonth();
            $mEnd   = $yearStart->copy()->addMonths($i)->endOfMonth();

            // Column: attendance_date  |  status boolean 1=present 0=absent
            $present[] = TeacherAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::TCH_PRESENT)
                ->count();

            $absent[] = TeacherAttendance::where('organization_id', $orgId)
                ->whereBetween('attendance_date', [$mStart, $mEnd])
                ->where('status', self::TCH_ABSENT)
                ->count();
        }

        $this->teacherMonthlyAttendance = compact('present', 'absent');
    }

    // ─── Teacher Pie ──────────────────────────────────────────────────────────

    protected function loadTeacherPie(): void
    {
        $orgId = $this->orgId();
        $from  = Carbon::now()->subDays((int) $this->teacherAttFilter - 1)->startOfDay();

        $present = TeacherAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::TCH_PRESENT)
            ->count();

        $absent = TeacherAttendance::where('organization_id', $orgId)
            ->where('attendance_date', '>=', $from)
            ->where('status', self::TCH_ABSENT)
            ->count();

        $this->teacherPieData = compact('present', 'absent');
    }

    // ─── Sections ─────────────────────────────────────────────────────────────

    protected function loadSections(): void
    {
        $this->sections = $this->performerClass
            ? Section::where('standard_id', $this->performerClass)->get()
            : [];
    }

    // ─── Top 3 Students ───────────────────────────────────────────────────────

    protected function loadTopStudents(): void
    {
        $query = StudentDetail::with(['user', 'standard', 'section'])
            ->where('organization_id', $this->orgId())
            ->withCount([
                'studentAttendances as present_count' => fn($q) =>
                $q->where('status', self::STU_PRESENT),
                'studentAttendances as total_count',
            ]);

        if ($this->performerClass)   $query->where('standard_id', $this->performerClass);
        if ($this->performerSection) $query->where('section_id', $this->performerSection);

        $this->topStudents = $query
            ->orderByDesc('present_count')
            ->take(3)
            ->get()
            ->map(fn($s, $i) => [
                'rank'    => $i + 1,
                'name'    => $s->user?->name ?? $s->full_name ?? 'N/A',
                'class'   => $s->standard?->name ?? '—',
                'section' => $s->section?->name ?? '—',
                'photo'   => $s->user?->profile_photo_url ?? null,
                'score'   => $s->total_count > 0
                    ? round(($s->present_count / $s->total_count) * 100, 1)
                    : 0,
            ])
            ->toArray();
    }

    // ─── Class Distribution ───────────────────────────────────────────────────

    protected function loadClassDistribution(): void
    {
        $orgId     = $this->orgId();
        $today     = Carbon::today();
        $standards = Standard::where('organization_id', $orgId)->orderBy('order')->get();

        $labels = $present = $absent = [];

        foreach ($standards as $std) {
            $studentIds = StudentDetail::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->pluck('id');

            $labels[] = $std->name;

            $present[] = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', self::STU_PRESENT)
                ->count();

            $absent[] = StudentAttendance::whereIn('student_detail_id', $studentIds)
                ->whereDate('attendance_date', $today)
                ->where('status', self::STU_ABSENT)
                ->count();
        }

        $this->classDistribution = compact('labels', 'present', 'absent');
    }

    // ─── Admin Enquiries ──────────────────────────────────────────────────────

    protected function loadAdminEnquiries(): void
    {
        $this->adminEnquiries = WebsiteContact::latest()->take(3)->get()
            ->map(fn($e) => [
                'id'     => $e->id,
                'name'   => $e->full_name,
                'email'  => $e->email,
                'time'   => $e->created_at->diffForHumans(),
                'status' => $e->remark ? 'Responded' : 'Pending',
            ])->toArray();
    }

    // ─── Arrangements ─────────────────────────────────────────────────────────
    // Schema: original_teacher_id, substitute_teacher_id, teacher_time_table_id, date, reason
    // Class/section/time info resolved through TeacherTimeTable relationship

    protected function loadArrangements(): void
    {
        $orgId = $this->orgId();
        $today = Carbon::today();

        // Find teachers absent today
        $absentTeacherIds = TeacherAttendance::where('organization_id', $orgId)
            ->whereDate('attendance_date', $today)
            ->where('status', self::TCH_ABSENT)
            ->pluck('teacher_detail_id');

        // Load arrangements for absent teachers today
        $this->arrangements = TeacherArrangement::with([
            'originalTeacher',
            'substituteTeacher',
            'teacherTimeTable.standard',
            'teacherTimeTable.section',
        ])
            ->whereDate('date', $today)
            ->whereIn('original_teacher_id', $absentTeacherIds)
            ->get()
            ->map(fn($a) => [
                'id'                    => $a->id,
                'class'                 => $a->teacherTimeTable?->standard?->name ?? '—',
                'section'               => $a->teacherTimeTable?->section?->name  ?? '—',
                'absent_teacher'        => $a->originalTeacher?->name ?? '—',
                'time'                  => $a->teacherTimeTable?->time
                    ?? $a->teacherTimeTable?->period
                    ?? '—',
                'substitute_teacher_id' => $a->substitute_teacher_id,
                'status'                => $a->substitute_teacher_id ? 'assigned' : 'pending',
            ])->toArray();

        // Available teachers = not absent today
        $this->availableTeachers = TeacherDetail::where('organization_id', $orgId)
            ->whereNotIn('id', $absentTeacherIds)
            ->get()
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            ->toArray();
    }

    // ─── Announcements ────────────────────────────────────────────────────────

    protected function loadAnnouncements(): void
    {
        $this->announcements = Announcement::where('organization_id', $this->orgId())
            ->latest()->take(5)->get()
            ->map(fn($a) => [
                'title'  => $a->title,
                'body'   => $a->description ?? $a->body ?? '',
                'type'   => $a->type ?? 'general',
                'pinned' => (bool) ($a->is_pinned ?? false),
                'time'   => $a->created_at->diffForHumans(),
            ])->toArray();
    }

    // ─── Recent Activities ────────────────────────────────────────────────────

    protected function loadRecentActivities(): void
    {
        $orgId      = $this->orgId();
        $activities = [];

        foreach (StudentDetail::with('user')->where('organization_id', $orgId)->latest()->take(3)->get() as $s) {
            $activities[] = [
                'title'       => 'New Admission',
                'description' => ($s->user?->name ?? $s->full_name ?? 'Student') . ' enrolled',
                'time'        => $s->created_at->diffForHumans(),
                'color'       => 'green',
                'ts'          => $s->created_at->timestamp,
            ];
        }

        foreach (FeePayment::with('studentDetail.user')->where('organization_id', $orgId)->latest()->take(3)->get() as $f) {
            $name = $f->studentDetail?->user?->name ?? 'Student';
            $activities[] = [
                'title'       => 'Fee Paid',
                'description' => '₹' . number_format($f->amount ?? 0) . " from {$name}",
                'time'        => $f->created_at->diffForHumans(),
                'color'       => 'blue',
                'ts'          => $f->created_at->timestamp,
            ];
        }

        usort($activities, fn($a, $b) => $b['ts'] - $a['ts']);
        $this->recentActivities = array_slice($activities, 0, 6);
    }

    // ─── Today's Homework ─────────────────────────────────────────────────────

    protected function loadTodayHomework(): void
    {
        $this->todayHomework = [];
    }

    // ─── Fee – STATIC (dynamic implementation pending) ───────────────────────

    protected function loadFeeStatsStatic(): void
    {
        $orgId         = $this->orgId();
        $totalFee      = FeeStructure::where('organization_id', $orgId)->where('is_active', true)->sum('amount');
        $collected     = FeePayment::where('organization_id', $orgId)->sum('amount');
        $transportFee  = FeeStructure::where('organization_id', $orgId)->where('is_active', true)->where('fee_type', 'transport')->sum('amount');

        $this->feeStats = [
            'totalFee'     => $totalFee,
            'collected'    => $collected,
            'remaining'    => max(0, $totalFee - $collected),
            'transportFee' => $transportFee,
        ];
    }

    protected function loadFeeClassDataStatic(): void
    {
        $orgId     = $this->orgId();
        $standards = Standard::where('organization_id', $orgId)->orderBy('order')->get();
        $labels    = $standards->pluck('name')->toArray();

        $collected = [];
        $remaining = [];

        foreach ($standards as $std) {
            $classFeeTotal = FeeStructure::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->where('is_active', true)->sum('amount');
            $classCollected = FeePayment::where('organization_id', $orgId)
                ->where('standard_id', $std->id)->sum('amount');

            $collected[] = (float) $classCollected;
            $remaining[] = (float) max(0, $classFeeTotal - $classCollected);
        }

        $this->feeClassData = [
            'labels'    => $labels,
            'collected' => $collected,
            'remaining' => $remaining,
        ];
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    protected function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    public function render()
    {
        return view('livewire.admin.analytics');
    }
}
