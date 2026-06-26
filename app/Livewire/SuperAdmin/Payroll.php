<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin\SuperAdminAttendance;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class Payroll extends Component
{
    use WireUiActions, WithFileUploads;

    // ─── Active Tab ───────────────────────────────────────────────────────────
    public string $activeTab = 'employees';

    // ─── Employee Form ────────────────────────────────────────────────────────
    public bool   $showEmpModal     = false;
    public        $editEmpId        = null;
    public string $empName          = '';
    public string $empEmail         = '';
    public string $empMobile        = '';
    public string $empDesignation   = '';
    public string $empType          = 'user';
    public        $empSalary        = '';
    public string $empAddress       = '';
    public string $empBankName      = '';
    public string $empAccountNo     = '';
    public string $empHolderName    = '';
    public string $empBranch        = '';
    public string $empIfsc          = '';
    public string $empJoiningDate   = '';
    public        $empPhoto;
    public        $empExistingPhoto = null;

    // ─── Employee Delete Confirm ──────────────────────────────────────────────
    public $pendingDeleteEmpId = null;

    // ─── Employee View Modal ──────────────────────────────────────────────────
    public bool $showEmpDetailModal = false;
    public      $selectedEmployeeId = null;

    // ─── Attendance ───────────────────────────────────────────────────────────
    public string $attendanceDate          = '';
    public string $filterAttendanceType    = '';
    public string $attendanceViewMode      = 'today';   // today | employee
    public string $attendanceViewEmpId     = '';
    public string $attendanceViewType      = '';
    public string $attendanceViewMonth     = '';

    // ─── Salary ───────────────────────────────────────────────────────────────
    public string $salaryMonth      = '';
    public string $filterSalaryType = '';
    public bool   $showPayModal     = false;
    public        $payEmployeeId    = null;
    public        $payAmount        = '';
    public string $payMode          = 'cash';
    public string $payDate          = '';
    public string $payChequeId      = '';
    public string $payRemark        = '';
    public        $payExistingId    = null;

    // ─── Payments History ─────────────────────────────────────────────────────
    public string $filterPaymentEmpId  = '';
    public string $filterPaymentMonth  = '';

    // ─── Employee Types ───────────────────────────────────────────────────────
    const EMP_TYPES = ['user', 'counsellor', 'team', 'management', 'other'];

    public function mount(): void
    {
        $this->attendanceDate      = now()->format('Y-m-d');
        $this->attendanceViewMonth = now()->format('Y-m');
        $this->salaryMonth         = now()->format('Y-m');
        $this->payDate             = now()->format('Y-m-d');
        $this->filterPaymentMonth  = now()->format('Y-m');
    }

    // ─── Employee CRUD ────────────────────────────────────────────────────────

    public function openEmpModal($id = null): void
    {
        $this->resetEmpForm();
        if ($id) {
            $emp = SuperAdminEmployee::find($id);
            if (!$emp) return;
            $this->editEmpId        = $emp->id;
            $this->empName          = $emp->name;
            $this->empEmail         = $emp->email ?? '';
            $this->empMobile        = $emp->mobile ?? '';
            $this->empDesignation   = $emp->designation ?? '';
            $this->empType          = $emp->type;
            $this->empSalary        = $emp->salary;
            $this->empAddress       = $emp->address ?? '';
            $this->empBankName      = $emp->bank_name ?? '';
            $this->empAccountNo     = $emp->bank_account_no ?? '';
            $this->empHolderName    = $emp->bank_holder_name ?? '';
            $this->empBranch        = $emp->bank_branch ?? '';
            $this->empIfsc          = $emp->bank_ifsc ?? '';
            $this->empJoiningDate   = $emp->joining_date?->format('Y-m-d') ?? '';
            $this->empExistingPhoto = $emp->photo;
        }
        $this->showEmpModal = true;
    }

    public function saveEmployee(): void
    {
        $this->validate([
            'empName'        => 'required|string|max:255',
            'empEmail'       => 'nullable|email|max:255|unique:super_admin_employees,email,' . $this->editEmpId,
            'empMobile'      => 'nullable|string|max:15',
            'empType'        => 'required|in:user,counsellor,team,management,other',
            'empSalary'      => 'required|numeric|min:0',
            'empDesignation' => 'nullable|string|max:255',
            'empPhoto'       => 'nullable|image|max:2048',
        ]);

        $data = [
            'name'             => $this->empName,
            'email'            => $this->empEmail ?: null,
            'mobile'           => $this->empMobile,
            'designation'      => $this->empDesignation,
            'type'             => $this->empType,
            'salary'           => $this->empSalary,
            'address'          => $this->empAddress,
            'bank_name'        => $this->empBankName,
            'bank_account_no'  => $this->empAccountNo,
            'bank_holder_name' => $this->empHolderName,
            'bank_branch'      => $this->empBranch,
            'bank_ifsc'        => $this->empIfsc ? strtoupper($this->empIfsc) : null,
            'joining_date'     => $this->empJoiningDate ?: null,
        ];

        if ($this->empPhoto) {
            if ($this->empExistingPhoto) {
                Storage::disk('s3')->delete(ltrim(parse_url($this->empExistingPhoto, PHP_URL_PATH), '/'));
            }
            $path = $this->empPhoto->store('superadmin/payroll/photos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['photo'] = Storage::disk('s3')->url($path);
        } elseif ($this->empExistingPhoto) {
            $data['photo'] = $this->empExistingPhoto;
        }

        if ($this->editEmpId) {
            SuperAdminEmployee::find($this->editEmpId)->update($data);
            $this->notification()->success('Employee updated!');
        } else {
            SuperAdminEmployee::create($data);
            $this->notification()->success('Employee added!');
        }

        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    public function confirmDeleteEmployee($id): void
    {
        $this->pendingDeleteEmpId = $id;
    }

    public function executeDeleteEmployee(): void
    {
        if ($this->pendingDeleteEmpId) {
            $emp = SuperAdminEmployee::find($this->pendingDeleteEmpId);
            if ($emp?->photo) {
                Storage::disk('s3')->delete(ltrim(parse_url($emp->photo, PHP_URL_PATH), '/'));
            }
            $emp?->delete();
            $this->notification()->success('Employee deleted!');
        }
        $this->pendingDeleteEmpId = null;
    }

    public function cancelDeleteEmployee(): void
    {
        $this->pendingDeleteEmpId = null;
    }

    public function viewEmployee($id): void
    {
        $this->selectedEmployeeId = $id;
        $this->showEmpDetailModal = true;
    }

    public function closeEmpDetailModal(): void
    {
        $this->showEmpDetailModal = false;
        $this->selectedEmployeeId = null;
    }

    private function resetEmpForm(): void
    {
        $this->reset([
            'editEmpId', 'empName', 'empEmail', 'empMobile', 'empDesignation',
            'empSalary', 'empAddress', 'empBankName', 'empAccountNo',
            'empHolderName', 'empBranch', 'empIfsc', 'empJoiningDate',
            'empPhoto', 'empExistingPhoto',
        ]);
        $this->empType = 'user';
    }

    public function closeEmpModal(): void
    {
        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    // ─── Attendance ───────────────────────────────────────────────────────────

    public function markAttendance($empId, string $status): void
    {
        SuperAdminAttendance::updateOrCreate(
            ['super_admin_employee_id' => $empId, 'date' => $this->attendanceDate],
            ['status' => $status]
        );
    }

    public function getAttendanceStatus($empId): ?string
    {
        return SuperAdminAttendance::where('super_admin_employee_id', $empId)
            ->where('date', $this->attendanceDate)
            ->value('status');
    }

    // ─── Salary ───────────────────────────────────────────────────────────────

    public function openPayModal($empId): void
    {
        $emp = SuperAdminEmployee::find($empId);
        if (!$emp) return;

        $existing = SuperAdminSalaryPayment::where('super_admin_employee_id', $empId)
            ->where('month', $this->salaryMonth)
            ->first();

        // Calculate net salary if no existing payment
        $netSalary = $this->computeNetSalary($emp->id, (float) $emp->salary, $this->salaryMonth);

        $this->payEmployeeId = $empId;
        $this->payAmount     = $existing?->amount ?? $netSalary;
        $this->payMode       = $existing?->payment_mode ?? 'cash';
        $this->payDate       = $existing?->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->payChequeId   = $existing?->transaction_id ?? '';
        $this->payRemark     = $existing?->remark ?? '';
        $this->payExistingId = $existing?->id;
        $this->showPayModal  = true;
    }

    public function closePayModal(): void
    {
        $this->showPayModal = false;
        $this->reset(['payEmployeeId', 'payAmount', 'payChequeId', 'payRemark', 'payExistingId']);
        $this->payMode = 'cash';
        $this->payDate = now()->format('Y-m-d');
    }

    public function savePayment(): void
    {
        $rules = [
            'payAmount' => 'required|numeric|min:0',
            'payMode'   => 'required|string',
            'payDate'   => 'required|date',
        ];
        if ($this->payMode === 'cheque') {
            $rules['payChequeId'] = 'required|string|max:100';
        }
        $this->validate($rules);

        SuperAdminSalaryPayment::updateOrCreate(
            ['super_admin_employee_id' => $this->payEmployeeId, 'month' => $this->salaryMonth],
            [
                'amount'         => $this->payAmount,
                'payment_mode'   => $this->payMode,
                'status'         => 'paid',
                'payment_date'   => $this->payDate,
                'transaction_id' => $this->payMode === 'cheque' ? ($this->payChequeId ?: null) : null,
                'remark'         => $this->payRemark ?: null,
            ]
        );

        $this->closePayModal();
        $this->notification()->success('Salary payment recorded!');
    }

    // ─── Net Salary Calculation ───────────────────────────────────────────────

    private function computeNetSalary(int $empId, float $baseSalary, string $month): float
    {
        $daysInMonth = Carbon::parse($month . '-01')->daysInMonth;
        $perDay      = $daysInMonth > 0 ? $baseSalary / $daysInMonth : 0;

        $attendance = SuperAdminAttendance::where('super_admin_employee_id', $empId)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])
            ->pluck('status');

        $absentDays = $attendance->filter(fn($s) => $s === 'absent')->count();
        $halfDays   = $attendance->filter(fn($s) => $s === 'half_day')->count();
        $deduction  = ($absentDays * $perDay) + ($halfDays * $perDay * 0.5);

        return max(0, round($baseSalary - $deduction, 2));
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        // All employees
        $employees = SuperAdminEmployee::orderBy('name')->get();

        // Employee stats by type
        $empStats = ['total' => $employees->count()];
        foreach (self::EMP_TYPES as $type) {
            $empStats[$type] = $employees->where('type', $type)->count();
        }

        // Attendance – today view
        $presentCount = SuperAdminAttendance::where('date', $this->attendanceDate)->where('status', 'present')->count();
        $absentCount  = SuperAdminAttendance::where('date', $this->attendanceDate)->where('status', 'absent')->count();
        $halfDayCount = SuperAdminAttendance::where('date', $this->attendanceDate)->where('status', 'half_day')->count();
        $leaveCount   = SuperAdminAttendance::where('date', $this->attendanceDate)->where('status', 'leave')->count();

        $attendanceEmployees = $employees->when(
            $this->filterAttendanceType,
            fn($c) => $c->where('type', $this->filterAttendanceType)
        );

        // Attendance – by-employee calendar
        $calendarAttendance       = [];
        $calendarEmployee         = null;
        $calendarSummary          = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'holiday' => 0];
        $attendanceFilteredEmps   = $employees->when(
            $this->attendanceViewType,
            fn($c) => $c->where('type', $this->attendanceViewType)
        );

        if ($this->attendanceViewMode === 'employee' && $this->attendanceViewEmpId) {
            $calendarEmployee = $employees->firstWhere('id', (int) $this->attendanceViewEmpId);
            if ($calendarEmployee) {
                $records = SuperAdminAttendance::where('super_admin_employee_id', $this->attendanceViewEmpId)
                    ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->attendanceViewMonth])
                    ->pluck('status', 'date')
                    ->toArray();
                $calendarAttendance = $records;

                $monthDate   = Carbon::parse($this->attendanceViewMonth . '-01');
                $daysInMonth = $monthDate->daysInMonth;
                // For current month count only till today; for past months use full month
                $countableDays = ($this->attendanceViewMonth === now()->format('Y-m'))
                    ? now()->day
                    : $daysInMonth;
                foreach ($records as $status) {
                    if (isset($calendarSummary[$status])) $calendarSummary[$status]++;
                }
                $calendarSummary['holiday'] = max(0, $countableDays - count($records));
            }
        }

        // Salary tab
        $salaryEmployees = SuperAdminEmployee::when(
            $this->filterSalaryType, fn($q) => $q->where('type', $this->filterSalaryType)
        )->orderBy('name')->get();

        $monthSalaryPayments = SuperAdminSalaryPayment::where('month', $this->salaryMonth)
            ->get()
            ->keyBy('super_admin_employee_id');

        // Bulk attendance for salary calculation (1 query)
        $monthAttBulk = SuperAdminAttendance::whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->salaryMonth])
            ->whereIn('super_admin_employee_id', $salaryEmployees->pluck('id'))
            ->get()
            ->groupBy('super_admin_employee_id');

        $daysInSalaryMonth = Carbon::parse($this->salaryMonth . '-01')->daysInMonth;

        $salaryCalculations = [];
        foreach ($salaryEmployees as $emp) {
            $base    = (float) $emp->salary;
            $perDay  = $daysInSalaryMonth > 0 ? $base / $daysInSalaryMonth : 0;
            $empAtt  = $monthAttBulk->get($emp->id, collect());
            $absent  = $empAtt->where('status', 'absent')->count();
            $half    = $empAtt->where('status', 'half_day')->count();
            $ded     = round(($absent * $perDay) + ($half * $perDay * 0.5), 2);
            $salaryCalculations[$emp->id] = [
                'base'      => $base,
                'net'       => max(0, round($base - $ded, 2)),
                'deduction' => $ded,
                'absent'    => $absent,
                'half'      => $half,
            ];
        }

        $totalEmployees   = $salaryEmployees->count();
        $paidThisMonth    = SuperAdminSalaryPayment::where('month', $this->salaryMonth)->where('status', 'paid')->count();
        $remainingEmp     = max(0, $totalEmployees - $paidThisMonth);
        $totalPaidAmount  = SuperAdminSalaryPayment::where('month', $this->salaryMonth)->where('status', 'paid')->sum('amount');
        $totalSalaryToPay = array_sum(array_column($salaryCalculations, 'net'));

        // Payments history
        $payments = SuperAdminSalaryPayment::with('employee')
            ->when($this->filterPaymentEmpId, fn($q) => $q->where('super_admin_employee_id', $this->filterPaymentEmpId))
            ->when($this->filterPaymentMonth, fn($q) => $q->where('month', $this->filterPaymentMonth))
            ->latest()
            ->get();

        // Selected employee for view modal
        $selectedEmployee = $this->selectedEmployeeId
            ? $employees->firstWhere('id', (int) $this->selectedEmployeeId)
            : null;

        // Pay modal employee
        $payEmp = $this->payEmployeeId
            ? $employees->firstWhere('id', (int) $this->payEmployeeId)
            : null;

        return view('livewire.super-admin.payroll', compact(
            'employees',
            'empStats',
            'attendanceEmployees',
            'presentCount',
            'absentCount',
            'halfDayCount',
            'leaveCount',
            'calendarAttendance',
            'calendarEmployee',
            'calendarSummary',
            'attendanceFilteredEmps',
            'salaryEmployees',
            'monthSalaryPayments',
            'salaryCalculations',
            'totalEmployees',
            'paidThisMonth',
            'remainingEmp',
            'totalPaidAmount',
            'totalSalaryToPay',
            'payments',
            'employees',
            'selectedEmployee',
            'payEmp'
        ));
    }
}
