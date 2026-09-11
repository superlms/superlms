<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\DriverDetail;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class Payroll extends Component
{
    use WireUiActions, WithFileUploads;

    // ─── Org ──────────────────────────────────────────────────────────────────
    private function orgId(): int
    {
        return Auth::user()->organization_id;
    }

    // ─── Active Tab ───────────────────────────────────────────────────────────
    public string $activeTab = 'employees';

    /**
     * The order staff are listed in across every tab: management first, then
     * drivers, then the rest of the employees, with teachers last (their
     * attendance lives in the Teacher module, so they read as an appendix here).
     */
    public const TYPE_ORDER = ['management' => 1, 'driver' => 2, 'employee' => 3, 'teacher' => 4];

    /** Apply that order, then name, to any employee collection. */
    private function sortByType($employees)
    {
        return $employees
            ->sortBy(fn ($e) => [self::TYPE_ORDER[$e->type] ?? 9, mb_strtolower((string) $e->name)])
            ->values();
    }

    // ─── Employee Form ────────────────────────────────────────────────────────
    public bool   $showEmpModal       = false;
    public        $editEmpId          = null;
    public string $empName            = '';
    public string $empEmail           = '';
    public string $empMobile          = '';
    public string $empDesignation     = '';
    public string $empType            = 'employee';
    public        $empSalary          = '';
    public string $empAddress         = '';
    public string $empBankName        = '';
    public string $empAccountNo       = '';
    public string $empHolderName      = '';
    public string $empBranch          = '';
    public string $empIfsc            = '';
    public string $empJoiningDate     = '';
    public        $empPhoto;
    public        $empExistingPhoto   = null;
    public        $empTeacherDetailId = null;

    // ─── Employee list filters ────────────────────────────────────────────────
    public string $empSearch     = '';
    public string $empTypeFilter = '';
    public string $empSort       = 'type_order';

    // ─── Employee Detail Modal ────────────────────────────────────────────────
    public bool $showEmpDetailModal = false;
    public      $selectedEmployee   = null;
    public array $employeeDetails   = [];

    // ─── Attendance ───────────────────────────────────────────────────────────
    // Three view modes, inferred from the filters that are set:
    //   • date only              → everyone's status on that date
    //   • employee (+ month)     → that employee's chosen month
    //   • employee (no month)    → that employee's whole year, day by day
    public string $attendanceDate       = ''; // date-mode filter + the date being marked
    public string $filterAttendanceType = ''; // employee type filter (narrows the dropdown)
    public string $attEmpId             = ''; // selected employee (employee-mode)
    public string $attMonth             = ''; // optional month for employee-mode
    public string $attYear              = ''; // year for the whole-year employee view
    public string $attStatus            = ''; // status filter: present|absent|half_day|leave|holiday
    public array  $attendanceDraft      = []; // admin_employee_id => status (non-teacher only)
    // 'view' → the filters and read-only views
    // 'pick_date' → step 1 of marking: choose the day
    // 'mark' → step 2: mark everyone (teachers excluded, they come from their own module)
    public string $attendanceMode       = 'view';
    public string $markDate             = ''; // the date chosen in step 1

    // ─── Salary ───────────────────────────────────────────────────────────────
    public string $salaryMonth        = '';
    public string $filterSalaryType   = '';
    public string $salarySearch       = '';
    public bool   $showPayModal       = false;
    public        $payEmployeeId      = null;
    public        $payAmount          = '';
    public string $payMode            = 'cash';
    public string $payPaidBy          = '';
    public string $payDate            = '';
    public string $payTransactionId   = '';
    public string $payRemark          = '';
    public        $payExistingId      = null;

    // ─── Payments History ─────────────────────────────────────────────────────
    public string $filterPaymentEmpId = '';
    public string $filterPaymentMonth = '';
    public string $paymentSearch      = '';

    // ─────────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        // Default to the current academic year's start (April → March).
        $this->attYear = (string) (now()->month >= 4 ? now()->year : now()->year - 1);
        // Salary defaults to the PREVIOUS month — that's the payable, fully-attended month.
        $this->salaryMonth = now()->subMonthNoOverflow()->format('Y-m');
        $this->payDate     = now()->format('Y-m-d');
    }

    /**
     * Make sure every teacher and driver in the org has a payroll row, so they
     * show up here automatically without being re-added by hand.
     */
    private function ensurePayrollEmployees(): void
    {
        $org = $this->orgId();

        // Teachers → link via teacher_detail_id
        $linkedTeachers = AdminEmployee::forOrganization($org)
            ->whereNotNull('teacher_detail_id')->pluck('teacher_detail_id')->all();
        TeacherDetail::with('user')
            ->where('organization_id', $org)
            ->when(count($linkedTeachers), fn($q) => $q->whereNotIn('id', $linkedTeachers))
            ->get()
            ->each(function ($td) use ($org) {
                if (!$td->user) return;
                AdminEmployee::create([
                    'organization_id'   => $org,
                    'teacher_detail_id' => $td->id,
                    'name'              => $td->user->name,
                    'email'             => $td->user->email ?? null,
                    'mobile'            => $td->phone ?? null,
                    'designation'       => 'Teacher',
                    'type'              => 'teacher',
                    'salary'            => 0,
                    'joining_date'      => $td->date_of_joining,
                ]);
            });

        // Drivers → link via driver_detail_id (skip gracefully until the migration lands)
        if (!Schema::hasColumn('admin_employees', 'driver_detail_id')) {
            return;
        }
        $linkedDrivers = AdminEmployee::forOrganization($org)
            ->whereNotNull('driver_detail_id')->pluck('driver_detail_id')->all();
        DriverDetail::with('user')
            ->where('organization_id', $org)
            ->when(count($linkedDrivers), fn($q) => $q->whereNotIn('id', $linkedDrivers))
            ->get()
            ->each(function ($dd) use ($org) {
                AdminEmployee::create([
                    'organization_id'  => $org,
                    'driver_detail_id' => $dd->id,
                    'name'             => $dd->user->name ?? ('Driver #' . $dd->id),
                    'email'            => $dd->user->email ?? null,
                    'mobile'           => $dd->phone ?? null,
                    'designation'      => 'Driver',
                    'type'             => 'driver',
                    'salary'           => 0,
                ]);
            });
    }

    // ─── Employee CRUD ────────────────────────────────────────────────────────

    public function openEmpModal($id = null): void
    {
        $this->resetEmpForm();

        if ($id) {
            $emp = AdminEmployee::forOrganization($this->orgId())->find($id);
            if (!$emp) return;

            $this->editEmpId          = $emp->id;
            $this->empName            = $emp->name;
            $this->empEmail           = $emp->email ?? '';
            $this->empMobile          = $emp->mobile ?? '';
            $this->empDesignation     = $emp->designation ?? '';
            $this->empType            = $emp->type;
            $this->empSalary          = $emp->salary;
            $this->empAddress         = $emp->address ?? '';
            $this->empBankName        = $emp->bank_name ?? '';
            $this->empAccountNo       = $emp->bank_account_no ?? '';
            $this->empHolderName      = $emp->bank_holder_name ?? '';
            $this->empBranch          = $emp->bank_branch ?? '';
            $this->empIfsc            = $emp->bank_ifsc ?? '';
            $this->empJoiningDate     = $emp->joining_date?->format('Y-m-d') ?? '';
            $this->empExistingPhoto   = $emp->photo;
            $this->empTeacherDetailId = $emp->teacher_detail_id;
        }

        $this->showEmpModal = true;
    }

    public function saveEmployee(): void
    {
        $this->validate([
            'empName'        => 'required|string|max:255',
            'empEmail'       => 'nullable|email|max:255',
            'empMobile'      => 'nullable|regex:/^[6-9]\d{9}$/',
            'empType'        => 'required|in:teacher,management,employee,driver',
            'empSalary'      => 'required|numeric|min:0|max:99999999',
            'empDesignation' => 'nullable|string|max:100',
            'empAddress'     => 'nullable|string|max:500',
            'empBankName'    => 'nullable|string|max:100',
            'empHolderName'  => 'nullable|string|max:100',
            'empAccountNo'   => 'nullable|regex:/^\d{6,20}$/',
            'empBranch'      => 'nullable|string|max:100',
            'empIfsc'        => 'nullable|regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/',
            'empPhoto'       => 'nullable|image|max:1024', // 1 MB
        ], [
            'empMobile.regex'    => 'Enter a valid 10-digit mobile number.',
            'empAccountNo.regex' => 'Account number must be 6–20 digits.',
            'empIfsc.regex'      => 'Enter a valid IFSC code (e.g. HDFC0001234).',
            'empPhoto.max'       => 'Photo must be 1 MB or smaller.',
        ]);

        $data = [
            'organization_id'   => $this->orgId(),
            'teacher_detail_id' => $this->empType === 'teacher' ? ($this->empTeacherDetailId ?: null) : null,
            'name'              => $this->empName,
            'email'             => $this->empEmail ?: null,
            'mobile'            => $this->empMobile,
            'designation'       => $this->empDesignation,
            'type'              => $this->empType,
            'salary'            => $this->empSalary,
            'address'           => $this->empAddress,
            'bank_name'         => $this->empBankName,
            'bank_account_no'   => $this->empAccountNo,
            'bank_holder_name'  => $this->empHolderName,
            'bank_branch'       => $this->empBranch,
            'bank_ifsc'         => $this->empIfsc ? strtoupper($this->empIfsc) : null,
            'joining_date'      => $this->empJoiningDate ?: null,
        ];

        if ($this->empPhoto) {
            if ($this->empExistingPhoto) {
                Storage::disk('s3')->delete(
                    ltrim(parse_url($this->empExistingPhoto, PHP_URL_PATH), '/')
                );
            }
            $path = $this->empPhoto->store('admin/payroll/photos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['photo'] = Storage::disk('s3')->url($path);
        } elseif ($this->empExistingPhoto) {
            $data['photo'] = $this->empExistingPhoto;
        }

        if ($this->editEmpId) {
            AdminEmployee::find($this->editEmpId)->update($data);
            $this->notification()->success('Employee updated!');
        } else {
            AdminEmployee::create($data);
            $this->notification()->success('Employee added!');
        }

        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    public function deleteEmployee($id): void
    {
        $this->dialog()->confirm([
            'title'       => 'Delete Employee?',
            'description' => 'This will delete the employee and all their records.',
            'icon'        => 'exclamation-circle',
            'iconColor'   => 'text-red-500',
            'accept'      => [
                'label'  => 'Yes, delete',
                'method' => 'doDeleteEmployee',
                'params' => $id,
                'color'  => 'negative',
            ],
            'reject' => ['label' => 'No'],
        ]);
    }

    public function doDeleteEmployee($id): void
    {
        AdminEmployee::forOrganization($this->orgId())->find($id)?->delete();
        $this->notification()->success('Employee deleted!');
    }

    public function viewEmployee($id): void
    {
        $employee = AdminEmployee::with(['teacherDetail.user', 'driverDetail.user'])
            ->forOrganization($this->orgId())
            ->find($id);

        if (!$employee) {
            return;
        }

        $details = [
            'Designation'  => $employee->designation ?? 'N/A',
            'Type'         => ucfirst($employee->type),
            'Mobile'       => $employee->mobile ?? 'N/A',
            'Email'        => $employee->email ?? 'N/A',
            'Salary'       => '₹' . number_format($employee->salary, 0),
            'Joining Date' => $employee->joining_date?->format('d M Y') ?? 'N/A',
        ];

        if ($employee->address) {
            $details['Address'] = $employee->address;
        }

        if ($employee->isTeacher() && $employee->teacher_detail_id) {
            $details['Linked Teacher'] = $employee->teacherDetail?->user?->name ?? ('Teacher #' . $employee->teacher_detail_id);
        } elseif ($employee->type === 'driver' && $employee->driver_detail_id) {
            $details['Linked Driver'] = $employee->driverDetail?->user?->name ?? ('Driver #' . $employee->driver_detail_id);
        }

        if ($employee->bank_name) {
            $details['Bank']    = $employee->bank_name;
            $details['Holder']  = $employee->bank_holder_name ?? 'N/A';
            $details['Account'] = $employee->bank_account_no ?? 'N/A';
            $details['IFSC']    = $employee->bank_ifsc ?? 'N/A';
            $details['Branch']  = $employee->bank_branch ?? 'N/A';
        }

        $this->selectedEmployee   = $employee;
        $this->employeeDetails    = $details;
        $this->showEmpDetailModal = true;
    }

    public function closeEmpDetailModal(): void
    {
        $this->showEmpDetailModal = false;
        $this->selectedEmployee   = null;
        $this->employeeDetails    = [];
    }

    private function resetEmpForm(): void
    {
        $this->reset([
            'editEmpId',
            'empName',
            'empEmail',
            'empMobile',
            'empDesignation',
            'empSalary',
            'empAddress',
            'empBankName',
            'empAccountNo',
            'empHolderName',
            'empBranch',
            'empIfsc',
            'empJoiningDate',
            'empPhoto',
            'empExistingPhoto',
            'empTeacherDetailId',
        ]);
        $this->empType = 'employee';
    }

    public function closeEmpModal(): void
    {
        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    // ─── Attendance ───────────────────────────────────────────────────────────

    /** Switching tabs always drops back to the read-only attendance view. */
    public function updatedActiveTab(): void
    {
        $this->attendanceMode  = 'view';
        $this->attendanceDraft = [];
    }

    /** Step 1 of marking: ask which date is being marked. */
    public function startMarking(): void
    {
        $this->resetValidation();
        $this->markDate        = $this->attendanceDate ?: now()->format('Y-m-d');
        $this->attendanceMode  = 'pick_date';
        $this->attendanceDraft = [];
    }

    /** Step 2: the date is settled, now mark the staff for it. */
    public function confirmMarkDate(): void
    {
        $this->validate([
            'markDate' => 'required|date|before_or_equal:today',
        ], [], ['markDate' => 'date']);

        $this->attendanceDate  = $this->markDate;
        $this->attendanceDraft = [];
        $this->attendanceMode  = 'mark';
    }

    /** Back from the marking list to the date step. */
    public function backToDatePick(): void
    {
        $this->attendanceMode  = 'pick_date';
        $this->attendanceDraft = [];
    }

    /** Leave the marking screens without saving. */
    public function cancelMarking(): void
    {
        $this->attendanceMode  = 'view';
        $this->attendanceDraft = [];
    }

    /** Clear the draft when the date changes; picking a date switches to date-mode. */
    public function updatedAttendanceDate(): void
    {
        $this->attendanceDraft = [];
        if ($this->attendanceDate !== '') {
            $this->attEmpId = '';
        }
    }

    /** Picking an employee switches to employee-mode (clears the date filter). */
    public function updatedAttEmpId(): void
    {
        if ($this->attEmpId !== '') {
            $this->attendanceDate = '';
        }
    }

    /** Pick a status for an employee in the draft; nothing is saved until Submit. */
    public function setDraft(int $empId, string $status): void
    {
        $this->attendanceDraft[$empId] = $status;
    }

    /**
     * Persist all drafted (non-teacher) attendance and land on that date's view.
     *
     * Marking a date that was already marked overwrites it — updateOrCreate is
     * keyed on employee + date, so the same day can be corrected as often as
     * needed without ever doubling up.
     */
    public function submitAttendance(): void
    {
        $org  = $this->orgId();
        $date = $this->attendanceDate;
        $count = 0;

        foreach ($this->attendanceDraft as $empId => $status) {
            $emp = AdminEmployee::forOrganization($org)->find($empId);
            // Teachers are marked from the Teacher Attendance module — never here.
            if (!$emp || $emp->isTeacher()) continue;

            AdminAttendance::updateOrCreate(
                ['admin_employee_id' => $empId, 'date' => $date],
                ['organization_id' => $org, 'status' => $status]
            );
            $count++;
        }

        $alreadyMarked = AdminAttendance::forOrganization($org)->where('date', $date)->exists();

        if ($count === 0 && !$alreadyMarked) {
            $this->notification()->error('Nothing to submit — pick a status for at least one employee.');
            return;
        }

        // Straight to that date's attendance, whatever was being viewed before.
        $this->attendanceDraft      = [];
        $this->attendanceMode       = 'view';
        $this->attendanceDate       = $date;
        $this->attEmpId             = '';
        $this->attMonth             = '';
        $this->attStatus            = '';
        $this->filterAttendanceType = '';

        if ($count === 0) {
            $this->notification()->success('Nothing changed', 'Showing the attendance already saved for ' . Carbon::parse($date)->format('d M Y') . '.');
            return;
        }

        $this->notification()->success('Attendance saved', "{$count} employee(s) updated for " . Carbon::parse($date)->format('d M Y') . '.');
    }

    /** Saved status for a non-teacher on the current date (teachers read from teacher module). */
    public function getAttendanceStatus($empId): ?string
    {
        $emp = AdminEmployee::find($empId);
        if (!$emp) return null;
        return $emp->getAttendanceStatusForDate($this->attendanceDate);
    }

    // ─── Filter clears (student-style bars) ────────────────────────────────────
    public function clearEmpFilters(): void
    {
        $this->reset(['empSearch', 'empTypeFilter']);
        $this->empSort = 'type_order';
    }

    public function clearAttFilters(): void
    {
        $this->reset(['filterAttendanceType', 'attEmpId', 'attMonth', 'attStatus', 'attendanceDate']);
        $this->attYear = (string) now()->year;
    }

    /**
     * Build the selected employee's attendance for the active period (a single
     * month if attMonth is set, otherwise the whole attYear up to today) as real
     * month calendars: seven columns starting on Sunday, every day of the month
     * present, and the days outside the period rendered blank.
     *
     * Returns the overall counts plus, per month, its own counts, present-%,
     * leading blank cells and day cells. Days with no record read as "holiday".
     */
    private function buildEmployeeDays(AdminEmployee $emp): array
    {
        $today = Carbon::today();

        if ($this->attMonth) {
            $start = Carbon::parse($this->attMonth . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        } else {
            // Academic year: April (attYear) → March (attYear + 1).
            $year  = (int) ($this->attYear ?: now()->year);
            $start = Carbon::create($year, 4, 1)->startOfDay();
            $end   = Carbon::create($year + 1, 3, 31)->endOfDay();
        }
        if ($end->gt($today)) $end = $today->copy();

        $blank   = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'holiday' => 0, 'marked' => 0];
        $counts  = $blank;
        $months  = [];
        if ($start->gt($end)) {
            return ['counts' => $counts, 'months' => $months];
        }

        // Load the period's records once, keyed by Y-m-d.
        $map = [];
        if ($emp->isTeacher() && $emp->teacher_detail_id) {
            TeacherAttendance::where('teacher_detail_id', $emp->teacher_detail_id)
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->get()->each(function ($r) use (&$map) {
                    $map[Carbon::parse($r->attendance_date)->format('Y-m-d')] =
                        ['0' => 'absent', '1' => 'present', '2' => 'half_day', '3' => 'half_day'][(string) $r->status] ?? null;
                });
        } else {
            AdminAttendance::forOrganization($this->orgId())->where('admin_employee_id', $emp->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->get()->each(function ($r) use (&$map) {
                    $map[Carbon::parse($r->date)->format('Y-m-d')] = $r->status;
                });
        }

        // Walk whole months so each calendar is complete, and mark the days that
        // fall outside the period (before it started, or still to come) as blank.
        $cursor = $start->copy()->startOfMonth();
        $last   = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $ym         = $cursor->format('Y-m');
            $monthStart = $cursor->copy()->startOfMonth();
            $daysInMonth = (int) $monthStart->daysInMonth;

            $mCounts = $blank;
            $cells   = [];

            for ($n = 1; $n <= $daysInMonth; $n++) {
                $d  = $monthStart->copy()->day($n);
                $ds = $d->format('Y-m-d');

                if ($d->lt($start) || $d->gt($end)) {
                    $cells[] = ['day' => $n, 'date' => $ds, 'status' => null, 'in_period' => false, 'dim' => false];
                    continue;
                }

                $status = $map[$ds] ?? 'holiday';

                if ($status === 'holiday') {
                    $counts['holiday']++;
                    $mCounts['holiday']++;
                } else {
                    $counts[$status]  = ($counts[$status] ?? 0) + 1;
                    $mCounts[$status] = ($mCounts[$status] ?? 0) + 1;
                    $counts['marked']++;
                    $mCounts['marked']++;
                }

                $cells[] = [
                    'day'       => $n,
                    'date'      => $ds,
                    'status'    => $status,
                    'in_period' => true,
                    // The status filter fades the days that don't match rather
                    // than pulling them out and breaking the calendar grid.
                    'dim'       => $this->attStatus !== '' && $status !== $this->attStatus,
                ];
            }

            $months[$ym] = [
                'label'  => $monthStart->format('F Y'),
                // Sunday-first grid: how many blank cells before the 1st.
                'lead'   => (int) $monthStart->dayOfWeek,
                'cells'  => $cells,
                'counts' => $mCounts,
                'pct'    => $mCounts['marked'] > 0
                    ? (int) round(($mCounts['present'] + 0.5 * $mCounts['half_day']) / $mCounts['marked'] * 100)
                    : 0,
            ];

            $cursor->addMonthNoOverflow();
        }

        return ['counts' => $counts, 'months' => $months];
    }

    public function clearSalaryFilters(): void
    {
        $this->reset(['salarySearch', 'filterSalaryType']);
    }

    public function clearPaymentFilters(): void
    {
        $this->reset(['paymentSearch', 'filterPaymentEmpId', 'filterPaymentMonth']);
    }

    // ─── Salary ───────────────────────────────────────────────────────────────

    /** Salary is payable only once the month is over. Current/future month → locked. */
    private function canPayMonth(): bool
    {
        return $this->salaryMonth < now()->format('Y-m');
    }

    /**
     * Attendance-based payable for an employee in the selected salary month.
     * Present / leave / unmarked days are paid in full; each absent is a full
     * per-day cut and each half day a half cut.
     */
    private function salaryBreakdown(AdminEmployee $emp, $adminGrouped, $teacherGrouped): array
    {
        $base = (float) $emp->salary;
        $daysInMonth = (int) Carbon::parse($this->salaryMonth . '-01')->daysInMonth;

        if ($emp->isTeacher() && isset($teacherGrouped[$emp->id])) {
            $records = $teacherGrouped[$emp->id];
            $present = $records->where('status', 1)->count();
            $absent  = $records->where('status', 0)->count();
            $halfDay = $records->whereIn('status', [2, 3])->count();
            $leave   = 0;
        } else {
            $records = $adminGrouped->get($emp->id, collect());
            $present = $records->where('status', 'present')->count();
            $absent  = $records->where('status', 'absent')->count();
            $halfDay = $records->where('status', 'half_day')->count();
            $leave   = $records->where('status', 'leave')->count();
        }

        $perDay    = $daysInMonth > 0 ? $base / $daysInMonth : 0;
        $deduction = ($absent + 0.5 * $halfDay) * $perDay;
        $payable   = max(0, round($base - $deduction));

        return compact('present', 'absent', 'halfDay', 'leave', 'payable') + ['base' => $base];
    }

    public function openPayModal($empId): void
    {
        if (!$this->canPayMonth()) {
            $this->notification()->error('Salary for ' . Carbon::parse($this->salaryMonth . '-01')->format('M Y') . ' can be paid only after the month ends.');
            return;
        }

        $emp = AdminEmployee::forOrganization($this->orgId())->find($empId);
        if (!$emp) return;

        // Attendance-based payable for this month.
        $adminGrouped = AdminAttendance::forOrganization($this->orgId())
            ->forMonth($this->salaryMonth)->where('admin_employee_id', $empId)
            ->get()->groupBy('admin_employee_id');
        $teacherGrouped = [];
        if ($emp->isTeacher() && $emp->teacher_detail_id) {
            $recs = TeacherAttendance::where('teacher_detail_id', $emp->teacher_detail_id)
                ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$this->salaryMonth])->get();
            $teacherGrouped[$emp->id] = $recs;
        }
        $breakdown = $this->salaryBreakdown($emp, $adminGrouped, $teacherGrouped);

        $existing = AdminSalaryPayment::where('admin_employee_id', $empId)
            ->where('organization_id', $this->orgId())
            ->where('month', $this->salaryMonth)
            ->first();

        $this->payEmployeeId    = $empId;
        $this->payAmount        = $existing?->amount ?? $breakdown['payable'];
        $this->payMode          = $existing?->payment_mode ?? 'cash';
        $this->payPaidBy        = $existing?->paid_by ?? (Auth::user()->name ?? '');
        $this->payDate          = $existing?->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->payTransactionId = $existing?->transaction_id ?? '';
        $this->payRemark        = $existing?->remark ?? '';
        $this->payExistingId    = $existing?->id;
        $this->showPayModal     = true;
    }

    public function closePayModal(): void
    {
        $this->showPayModal = false;
        $this->reset([
            'payEmployeeId',
            'payAmount',
            'payMode',
            'payPaidBy',
            'payDate',
            'payTransactionId',
            'payRemark',
            'payExistingId',
        ]);
        $this->payDate = now()->format('Y-m-d');
    }

    public function savePayment(): void
    {
        if (!$this->canPayMonth()) {
            $this->notification()->error('This month is not payable yet.');
            return;
        }

        $this->validate([
            'payAmount'        => 'required|numeric|min:0|max:99999999',
            'payMode'          => 'required|in:cash,online,bank_transfer,cheque',
            'payPaidBy'        => 'required|string|max:255',
            'payDate'          => 'required|date',
            'payTransactionId' => 'nullable|string|max:100',
            'payRemark'        => 'nullable|string|max:500',
        ], [], ['payPaidBy' => 'paid by', 'payTransactionId' => 'transaction id']);

        // For online / bank transfer the money moves to the employee's account and
        // the payment is credited immediately; other modes are recorded as paid too.
        AdminSalaryPayment::updateOrCreate(
            [
                'admin_employee_id' => $this->payEmployeeId,
                'organization_id'   => $this->orgId(),
                'month'             => $this->salaryMonth,
            ],
            [
                'amount'         => $this->payAmount,
                'payment_mode'   => $this->payMode,
                'paid_by'        => $this->payPaidBy,
                'status'         => 'paid',
                'payment_date'   => $this->payDate,
                'transaction_id' => $this->payTransactionId ?: null,
                'remark'         => $this->payRemark ?: null,
            ]
        );

        $this->closePayModal();

        if (in_array($this->payMode, ['online', 'bank_transfer'])) {
            $this->notification()->success('Salary credited to employee account!');
        } else {
            $this->notification()->success('Salary payment recorded!');
        }
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    /**
     * The accounts panel runs this very component (see App\Livewire\Accounts\Payroll),
     * so both panels work the same records with the same logic and the same
     * screen. Only what genuinely differs per panel is overridden below.
     */
    protected function viewName(): string { return 'livewire.admin.payroll'; }

    public function render()
    {
        $orgId = $this->orgId();

        // Auto-provision payroll rows for teachers/drivers.
        $this->ensurePayrollEmployees();

        // ── Employees — single query, reuse everywhere ─────────────────────────
        $allEmployees = AdminEmployee::forOrganization($orgId)->orderBy('name')->get();

        // Employees tab: search + type + sort
        $employeesList = $allEmployees
            ->when($this->empTypeFilter, fn($c) => $c->where('type', $this->empTypeFilter))
            ->when($this->empSearch, function ($c) {
                $t = mb_strtolower(trim($this->empSearch));
                return $c->filter(fn($e) => str_contains(mb_strtolower($e->name), $t)
                    || str_contains(mb_strtolower((string) $e->designation), $t)
                    || str_contains(mb_strtolower((string) $e->mobile), $t));
            });
        // Default order is management → driver → employee → teacher.
        $employeesList = match ($this->empSort) {
            'name_asc'    => $employeesList->sortBy('name')->values(),
            'name_desc'   => $employeesList->sortByDesc('name')->values(),
            'salary_asc'  => $employeesList->sortBy('salary')->values(),
            'salary_desc' => $employeesList->sortByDesc('salary')->values(),
            default       => $this->sortByType($employeesList),
        };

        // Attendance tab list (type filter) — used by the date-mode view and as
        // the options for the employee dropdown, in the same type order.
        $attEmployees = $this->sortByType(
            $allEmployees->when($this->filterAttendanceType, fn($c) => $c->where('type', $this->filterAttendanceType))
        );

        // Marking list: teachers are never marked here — their attendance comes
        // from the Teacher Attendance module.
        $markEmployees = $attEmployees->where('type', '!=', 'teacher')->values();

        // Salary tab list: search + type, same order again
        $salaryEmployees = $this->sortByType(
            $allEmployees
                ->when($this->filterSalaryType, fn($c) => $c->where('type', $this->filterSalaryType))
                ->when($this->salarySearch, function ($c) {
                    $t = mb_strtolower(trim($this->salarySearch));
                    return $c->filter(fn($e) => str_contains(mb_strtolower($e->name), $t));
                })
        );

        $allEmployeesForFilter = $allEmployees;

        // ── Stats (for employees tab header) ───────────────────────────────────
        $empStats = [
            'total'      => $allEmployees->count(),
            'teacher'    => $allEmployees->where('type', 'teacher')->count(),
            'management' => $allEmployees->where('type', 'management')->count(),
            'employee'   => $allEmployees->where('type', 'employee')->count(),
            'driver'     => $allEmployees->where('type', 'driver')->count(),
        ];

        // ── Teacher maps ───────────────────────────────────────────────────────
        $teacherIds = $allEmployees->where('type', 'teacher')->whereNotNull('teacher_detail_id')->pluck('teacher_detail_id');
        $teacherEmpMap = $allEmployees->whereNotNull('teacher_detail_id')->pluck('id', 'teacher_detail_id');

        // ── Attendance view (only renders once a filter is chosen) ─────────────
        //   attView === 'date'     → everyone's status on attendanceDate
        //   attView === 'employee' → the picked employee's month / whole year
        $attView        = null;
        $attEmp         = null;
        $attMonths      = [];
        $attCounts      = [];
        $attPeriodLabel = '';

        if ($this->attendanceMode === 'view') {
            if ($this->attEmpId) {
                $attEmp = $allEmployees->firstWhere('id', (int) $this->attEmpId);
                if ($attEmp) {
                    $attView        = 'employee';
                    $built          = $this->buildEmployeeDays($attEmp);
                    $attMonths      = $built['months'];
                    $attCounts      = $built['counts'];
                    if ($this->attMonth) {
                        $attPeriodLabel = Carbon::parse($this->attMonth . '-01')->format('F Y');
                    } else {
                        $ay = (int) ($this->attYear ?: now()->year);
                        $attPeriodLabel = Carbon::create($ay, 4, 1)->format('M Y') . ' – ' . Carbon::create($ay + 1, 3, 1)->format('M Y');
                    }
                }
            } elseif ($this->attendanceDate) {
                $attView        = 'date';
                $attPeriodLabel = Carbon::parse($this->attendanceDate)->format('d M Y');
            }
        }

        // ── Salary month attendance → breakdowns ───────────────────────────────
        $salaryAdminGrouped = AdminAttendance::forOrganization($orgId)
            ->forMonth($this->salaryMonth)->get()->groupBy('admin_employee_id');
        $salaryTeacherGrouped = [];
        if ($teacherIds->isNotEmpty()) {
            TeacherAttendance::whereIn('teacher_detail_id', $teacherIds)
                ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$this->salaryMonth])
                ->get()->groupBy('teacher_detail_id')
                ->each(function ($records, $tdId) use (&$salaryTeacherGrouped, $teacherEmpMap) {
                    if ($empId = $teacherEmpMap->get($tdId)) $salaryTeacherGrouped[$empId] = $records;
                });
        }

        $salaryBreakdowns = [];
        foreach ($salaryEmployees as $emp) {
            $salaryBreakdowns[$emp->id] = $this->salaryBreakdown($emp, $salaryAdminGrouped, $salaryTeacherGrouped);
        }

        $monthSalaryPayments = AdminSalaryPayment::forOrganization($orgId)
            ->forMonth($this->salaryMonth)->get()->keyBy('admin_employee_id');

        $canPaySalaryMonth   = $this->canPayMonth();
        $totalPayable        = collect($salaryBreakdowns)->sum('payable');
        $totalPaidAmount     = (float) ($monthSalaryPayments->where('status', 'paid')->sum('amount'));

        // ── Payments History ──────────────────────────────────────────────────
        $payments = AdminSalaryPayment::forOrganization($orgId)
            ->with('employee')
            ->when($this->filterPaymentEmpId, fn($q) => $q->where('admin_employee_id', $this->filterPaymentEmpId))
            ->when($this->filterPaymentMonth,  fn($q) => $q->forMonth($this->filterPaymentMonth))
            ->latest()->get()
            ->when($this->paymentSearch, function ($c) {
                $t = mb_strtolower(trim($this->paymentSearch));
                return $c->filter(fn($p) => str_contains(mb_strtolower((string) $p->employee?->name), $t));
            })->values();

        return view($this->viewName(), compact(
            'employeesList',
            'attEmployees',
            'markEmployees',
            'salaryEmployees',
            'empStats',
            'attView',
            'attEmp',
            'attMonths',
            'attCounts',
            'attPeriodLabel',
            'salaryBreakdowns',
            'monthSalaryPayments',
            'canPaySalaryMonth',
            'totalPayable',
            'totalPaidAmount',
            'payments',
            'allEmployeesForFilter',
        ));
    }
}
