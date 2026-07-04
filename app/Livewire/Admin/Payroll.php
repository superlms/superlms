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
    public string $empSort       = 'name_asc';

    // ─── Employee Detail Modal ────────────────────────────────────────────────
    public bool $showEmpDetailModal = false;
    public      $selectedEmployee   = null;

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
    public string $attendanceMode       = 'view'; // 'view' | 'mark'

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
        $this->attYear = (string) now()->year;
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
        $this->selectedEmployee   = AdminEmployee::with(['teacherDetail', 'driverDetail'])
            ->forOrganization($this->orgId())
            ->find($id);
        $this->showEmpDetailModal = true;
    }

    public function closeEmpDetailModal(): void
    {
        $this->showEmpDetailModal = false;
        $this->selectedEmployee   = null;
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

    /** Open the marking screen (defaults the mark date to today). */
    public function startMarking(): void
    {
        if ($this->attendanceDate === '') {
            $this->attendanceDate = now()->format('Y-m-d');
        }
        $this->attendanceMode  = 'mark';
        $this->attendanceDraft = [];
    }

    /** Leave the marking screen without saving. */
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

    /** Persist all drafted (non-teacher) attendance, then return to the view screen. */
    public function submitAttendance(): void
    {
        $org = $this->orgId();
        $count = 0;

        foreach ($this->attendanceDraft as $empId => $status) {
            $emp = AdminEmployee::forOrganization($org)->find($empId);
            // Teachers are marked from the Teacher Attendance module — never here.
            if (!$emp || $emp->isTeacher()) continue;

            AdminAttendance::updateOrCreate(
                ['admin_employee_id' => $empId, 'date' => $this->attendanceDate],
                ['organization_id' => $org, 'status' => $status]
            );
            $count++;
        }

        if ($count === 0) {
            $this->notification()->error('Nothing to submit — pick a status for at least one employee.');
            return;
        }

        $this->attendanceDraft = [];
        $this->attendanceMode  = 'view';
        $this->notification()->success('Attendance marked successfully', "{$count} employee(s) updated for " . Carbon::parse($this->attendanceDate)->format('d M Y') . '.');
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
        $this->empSort = 'name_asc';
    }

    public function clearAttFilters(): void
    {
        $this->reset(['filterAttendanceType', 'attEmpId', 'attMonth', 'attStatus', 'attendanceDate']);
        $this->attYear = (string) now()->year;
    }

    /**
     * Build the selected employee's day-by-day attendance for the active period
     * (a single month if attMonth is set, otherwise the whole attYear up to today).
     * Returns per-month day chips plus overall counts. Days with no record are
     * treated as "holiday".
     */
    private function buildEmployeeDays(AdminEmployee $emp): array
    {
        $today = Carbon::today();

        if ($this->attMonth) {
            $start = Carbon::parse($this->attMonth . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        } else {
            $year  = (int) ($this->attYear ?: now()->year);
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end   = Carbon::create($year, 12, 31)->endOfDay();
        }
        if ($end->gt($today)) $end = $today->copy();

        $counts  = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'holiday' => 0, 'marked' => 0];
        $byMonth = [];
        if ($start->gt($end)) {
            return ['counts' => $counts, 'byMonth' => $byMonth];
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

        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $ds     = $d->format('Y-m-d');
            $status = $map[$ds] ?? 'holiday';

            if ($status === 'holiday') {
                $counts['holiday']++;
            } else {
                $counts[$status] = ($counts[$status] ?? 0) + 1;
                $counts['marked']++;
            }

            // Status filter narrows only what's displayed, not the counts.
            if ($this->attStatus && $status !== $this->attStatus) continue;

            $byMonth[$d->format('Y-m')][] = [
                'date'   => $ds,
                'day'    => (int) $d->format('j'),
                'status' => $status,
                'dow'    => $d->format('D'),
            ];
        }

        return ['counts' => $counts, 'byMonth' => $byMonth];
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
        $employeesList = match ($this->empSort) {
            'name_desc'   => $employeesList->sortByDesc('name'),
            'salary_asc'  => $employeesList->sortBy('salary'),
            'salary_desc' => $employeesList->sortByDesc('salary'),
            'type'        => $employeesList->sortBy('type'),
            default       => $employeesList->sortBy('name'),
        };
        $employeesList = $employeesList->values();

        // Attendance tab list (type filter) — used by the mark screen and the
        // date-mode view, and as the options for the employee dropdown.
        $attEmployees = $allEmployees
            ->when($this->filterAttendanceType, fn($c) => $c->where('type', $this->filterAttendanceType))
            ->values();

        // Salary tab list: search + type
        $salaryEmployees = $allEmployees
            ->when($this->filterSalaryType, fn($c) => $c->where('type', $this->filterSalaryType))
            ->when($this->salarySearch, function ($c) {
                $t = mb_strtolower(trim($this->salarySearch));
                return $c->filter(fn($e) => str_contains(mb_strtolower($e->name), $t));
            })->values();

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
        $attByMonth     = [];
        $attCounts      = [];
        $attPeriodLabel = '';

        if ($this->attendanceMode === 'view') {
            if ($this->attEmpId) {
                $attEmp = $allEmployees->firstWhere('id', (int) $this->attEmpId);
                if ($attEmp) {
                    $attView        = 'employee';
                    $built          = $this->buildEmployeeDays($attEmp);
                    $attByMonth     = $built['byMonth'];
                    $attCounts      = $built['counts'];
                    $attPeriodLabel = $this->attMonth
                        ? Carbon::parse($this->attMonth . '-01')->format('F Y')
                        : ('Year ' . ($this->attYear ?: now()->year));
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

        return view('livewire.admin.payroll', compact(
            'employeesList',
            'attEmployees',
            'salaryEmployees',
            'empStats',
            'attView',
            'attEmp',
            'attByMonth',
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
