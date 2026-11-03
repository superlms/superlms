<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Admin\AdminEmployee;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentAttendance;
use App\Models\Student\StudentDetail;
use App\Models\Teacher\TeacherDetail;
use App\Support\AcademicYear;
use App\Support\NameOrder;
use App\Support\SchoolFeeTotals;
use App\Support\StudentExport;
use App\Support\TeacherExport;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use WireUi\Traits\WireUiActions;

/**
 * Super Admin → School Data: one school's full data in one place. Pick a
 * school and see its students, teachers, staff, classes and fees — the
 * figures up top (with how they moved this month), then each list with a
 * search and an Excel export of it.
 *
 * A sub super-admin limited to one school sees only that school.
 */
class SchoolData extends Component
{
    use WithPagination, WireUiActions;

    public const TABS = [
        'students' => 'Students',
        'teachers' => 'Teachers',
        'staff'    => 'Staff',
        'classes'  => 'Classes',
        'fees'     => 'Fees',
    ];

    private const PER_PAGE = 20;

    /** Months on the cards' sparklines, this one included. */
    private const SPARK_MONTHS = 6;

    #[Url(except: null)]
    public ?int $school = null;

    #[Url(except: 'students')]
    public string $tab = 'students';

    public string $search = '';

    public function mount(): void
    {
        if ($locked = $this->lockedSchool()) {
            $this->school = $locked;
        } elseif (!$this->school || !Organization::whereKey($this->school)->exists()) {
            $this->school = Organization::orderBy('name')->value('id');
        }

        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'students';
        }
    }

    public function updatedSchool(): void
    {
        if ($locked = $this->lockedSchool()) {
            $this->school = $locked;
        } elseif (!$this->school || !Organization::whereKey($this->school)->exists()) {
            $this->school = Organization::orderBy('name')->value('id');
        }

        $this->search = '';
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        if (!array_key_exists($tab, self::TABS)) {
            return;
        }

        $this->tab    = $tab;
        $this->search = '';
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->setTab(array_key_exists($this->tab, self::TABS) ? $this->tab : 'students');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /** The open list as an Excel file — the whole school's, not just this page. */
    public function export()
    {
        $org = $this->org();
        if (!$org) {
            return null;
        }

        try {
            [$headings, $rows] = match ($this->tab) {
                'students' => array_slice(StudentExport::data($org->id), 0, 2),
                'teachers' => array_slice(TeacherExport::data($org->id), 0, 2),
                'staff'    => $this->staffSheet($org->id),
                'classes'  => $this->classesSheet($org->id),
                'fees'     => $this->feesSheet($org->id),
            };
            $bytes = StudentExport::xlsx($headings, $rows);
        } catch (\Throwable $e) {
            report($e);
            $this->notification()->error('Export failed', 'The Excel file could not be made. Please try again.');
            return null;
        }

        $name = Str::slug($org->name) . '-' . $this->tab . '-' . now()->format('Y-m-d') . '.xlsx';

        return response()->streamDownload(fn () => print($bytes), $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ─── The school and its figures ───────────────────────────────────────────

    private function lockedSchool(): ?int
    {
        $user = auth()->user();

        return $user && $user->isSubSuperAdmin() ? $user->allowedOrganizationId() : null;
    }

    /** The school on show — always their own for a sub super-admin limited to one. */
    private function org(): ?Organization
    {
        $id = $this->lockedSchool() ?? $this->school;

        return $id ? Organization::find($id) : null;
    }

    /** The cards up top: counts, today's attendance, the fee, and how each moved. */
    private function summary(int $org): array
    {
        $students = StudentDetail::where('organization_id', $org);
        $teachers = TeacherDetail::where('organization_id', $org);

        $today   = StudentAttendance::where('organization_id', $org)->whereDate('attendance_date', now()->toDateString());
        $marked  = (clone $today)->count();
        $present = (clone $today)->where('status', 1)->count();

        $standardIds = Standard::where('organization_id', $org)->pluck('id');

        return [
            'students'        => (clone $students)->count(),
            'activeStudents'  => (clone $students)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count(),
            'teachers'        => (clone $teachers)->count(),
            'activeTeachers'  => (clone $teachers)->whereHas('user', fn ($q) => $q->where('is_active', 1))->count(),
            'staff'           => $this->staffQuery($org)->count(),
            'classes'         => $standardIds->count(),
            'sections'        => Section::whereIn('standard_id', $standardIds)->count(),
            'marked'          => $marked,
            'present'         => $present,
            'presentPct'      => $marked > 0 ? round($present / $marked * 100, 1) : null,
            'fee'             => SchoolFeeTotals::summary($org),
            'admissions'      => $this->monthly(fn ($from, $to) => StudentDetail::where('organization_id', $org)->whereBetween('created_at', [$from, $to])->count()),
            'teacherJoins'    => $this->monthly(fn ($from, $to) => TeacherDetail::where('organization_id', $org)->whereBetween('created_at', [$from, $to])->count()),
            'feeByMonth'      => $this->monthly(fn ($from, $to) => (float) FeePayment::where('organization_id', $org)
                ->whereDate('payment_date', '>=', $from->toDateString())->whereDate('payment_date', '<=', $to->toDateString())->sum('amount')),
            'attendanceWeek'  => $this->attendanceWeek($org),
        ];
    }

    /** One figure per month over the last SPARK_MONTHS months, oldest first. */
    private function monthly(callable $figure): array
    {
        $out = [];
        for ($i = self::SPARK_MONTHS - 1; $i >= 0; $i--) {
            $from  = now()->startOfMonth()->subMonthsNoOverflow($i);
            $out[] = $figure($from, $from->copy()->endOfMonth());
        }

        return $out;
    }

    /** Present % for each of the last 7 days (0 where nothing was marked). */
    private function attendanceWeek(int $org): array
    {
        $rows = StudentAttendance::where('organization_id', $org)
            ->whereDate('attendance_date', '>=', now()->subDays(6)->toDateString())
            ->selectRaw('DATE(attendance_date) as d, COUNT(*) as total, SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $out = [];
        for ($i = 6; $i >= 0; $i--) {
            $row   = $rows[now()->subDays($i)->toDateString()] ?? null;
            $out[] = $row && $row->total > 0 ? round($row->present / $row->total * 100, 1) : 0;
        }

        return $out;
    }

    // ─── The lists ────────────────────────────────────────────────────────────

    private function staffQuery(int $org)
    {
        return AdminEmployee::where('organization_id', $org)
            ->where(fn ($q) => $q->whereNull('type')->orWhere('type', '!=', 'teacher'));
    }

    private function rows(int $org)
    {
        $term = trim($this->search);
        $like = '%' . $term . '%';

        return match ($this->tab) {
            'students' => StudentDetail::with(['user:id,email,is_active', 'standard:id,name', 'section:id,name'])
                ->where('organization_id', $org)
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('full_name', 'like', $like)
                    ->orWhere('admission_no', 'like', $like)
                    ->orWhere('roll_no', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('father_name', 'like', $like)))
                ->tap(fn ($q) => NameOrder::students($q))
                ->paginate(self::PER_PAGE),

            'teachers' => TeacherDetail::with('user:id,name,email,mobile_number,is_active')
                ->where('organization_id', $org)
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('phone', 'like', $like)
                    ->orWhere('employee_id', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))))
                ->tap(fn ($q) => NameOrder::teachers($q))
                ->paginate(self::PER_PAGE),

            'staff' => $this->staffQuery($org)
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', $like)
                    ->orWhere('designation', 'like', $like)
                    ->orWhere('mobile', 'like', $like)
                    ->orWhere('email', 'like', $like)))
                ->orderBy('name')
                ->paginate(self::PER_PAGE),

            'fees' => FeePayment::with(['studentDetail:id,full_name,admission_no', 'standard:id,name', 'section:id,name'])
                ->where('organization_id', $org)
                ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                    ->where('receipt_number', 'like', $like)
                    ->orWhereHas('studentDetail', fn ($s) => $s->where('full_name', 'like', $like)->orWhere('admission_no', 'like', $like))))
                ->orderByDesc('payment_date')->orderByDesc('id')
                ->paginate(self::PER_PAGE),

            default => null,
        };
    }

    /** Classes in class order, each section with its students. */
    private function classes(int $org): array
    {
        $counts = StudentDetail::where('organization_id', $org)
            ->selectRaw('standard_id, section_id, COUNT(*) as c')
            ->groupBy('standard_id', 'section_id')
            ->get()
            ->groupBy('standard_id');

        $term = mb_strtolower(trim($this->search));

        return Standard::with(['sections' => fn ($q) => $q->orderBy('name')])
            ->where('organization_id', $org)
            ->inClassOrder()
            ->get()
            ->filter(fn ($s) => $term === '' || str_contains(mb_strtolower($s->name), $term))
            ->map(function ($standard) use ($counts) {
                $bySection = ($counts[$standard->id] ?? collect())->keyBy(fn ($r) => (int) $r->section_id);
                $sections  = $standard->sections->map(fn ($sec) => [
                    'name'     => $sec->name,
                    'students' => (int) ($bySection[(int) $sec->id]->c ?? 0),
                ])->values()->all();

                return [
                    'name'     => $standard->name,
                    'board'    => $standard->board,
                    'sections' => $sections,
                    'students' => (int) ($counts[$standard->id] ?? collect())->sum('c'),
                ];
            })
            ->values()
            ->all();
    }

    // ─── Excel sheets for the lists without an export of their own ───────────

    private function staffSheet(int $org): array
    {
        $rows = $this->staffQuery($org)->orderBy('name')->get()->values()->map(fn ($e, $i) => [
            'S.No'         => $i + 1,
            'Name'         => $e->name,
            'Type'         => Str::headline((string) $e->type) ?: '-',
            'Designation'  => $e->designation ?: '-',
            'Mobile'       => $e->mobile ?: '-',
            'Email'        => $e->email ?: '-',
            'Salary'       => (float) $e->salary,
            'Joining Date' => $e->joining_date ? Carbon::parse($e->joining_date)->format('d-m-Y') : '-',
            'Status'       => $e->is_active ? 'Active' : 'Inactive',
        ])->all();

        return [['S.No', 'Name', 'Type', 'Designation', 'Mobile', 'Email', 'Salary', 'Joining Date', 'Status'], $rows];
    }

    private function classesSheet(int $org): array
    {
        $this->search = '';
        $rows = [];
        foreach ($this->classes($org) as $class) {
            foreach ($class['sections'] ?: [['name' => '-', 'students' => $class['students']]] as $section) {
                $rows[] = ['Class' => $class['name'], 'Section' => $section['name'], 'Students' => $section['students']];
            }
        }

        return [['Class', 'Section', 'Students'], $rows];
    }

    private function feesSheet(int $org): array
    {
        $rows = FeePayment::with(['studentDetail:id,full_name,admission_no', 'standard:id,name', 'section:id,name'])
            ->where('organization_id', $org)
            ->whereDate('payment_date', '>=', AcademicYear::start()->toDateString())
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get()
            ->values()
            ->map(fn ($p, $i) => [
                'S.No'         => $i + 1,
                'Date'         => $p->payment_date ? Carbon::parse($p->payment_date)->format('d-m-Y') : '-',
                'Receipt No.'  => $p->receipt_number ?: '-',
                'Student'      => $p->studentDetail?->full_name ?? '-',
                'Admission No.' => $p->studentDetail?->admission_no ?? '-',
                'Class'        => trim(($p->standard?->name ?? '') . ' ' . ($p->section?->name ?? '')) ?: '-',
                'Fee'          => Str::headline((string) $p->fee_type) ?: '-',
                'Mode'         => Str::headline((string) $p->payment_mode) ?: '-',
                'Amount'       => (float) $p->amount,
            ])->all();

        return [['S.No', 'Date', 'Receipt No.', 'Student', 'Admission No.', 'Class', 'Fee', 'Mode', 'Amount'], $rows];
    }

    public function render()
    {
        if (!array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'students';
        }

        $org = $this->org();

        return view('livewire.super-admin.school-data', [
            'schools'  => $this->lockedSchool() ? collect() : Organization::orderBy('name')->get(['id', 'name']),
            'locked'   => (bool) $this->lockedSchool(),
            'org'      => $org,
            'summary'  => $org ? $this->summary($org->id) : null,
            'rows'     => $org && $this->tab !== 'classes' ? $this->rows($org->id) : null,
            'classes'  => $org && $this->tab === 'classes' ? $this->classes($org->id) : [],
        ]);
    }
}
