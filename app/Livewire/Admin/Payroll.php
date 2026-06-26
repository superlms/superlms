<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Teacher\TeacherAttendance;
use Illuminate\Support\Facades\Auth;
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

    // ─── Employee Detail Modal ────────────────────────────────────────────────
    public bool $showEmpDetailModal = false;
    public      $selectedEmployee   = null;

    // ─── Attendance ───────────────────────────────────────────────────────────
    public string $attendanceMonth      = '';
    public string $attendanceDate       = '';
    public string $filterAttendanceType = '';

    // ─── Salary ───────────────────────────────────────────────────────────────
    public string $salaryMonth        = '';
    public string $filterSalaryType   = '';
    public bool   $showPayModal       = false;
    public        $payEmployeeId      = null;
    public        $payAmount          = '';
    public string $payMode            = 'cash';
    public string $payDate            = '';
    public string $payTransactionId   = '';
    public string $payRemark          = '';
    public        $payExistingId      = null;

    // ─── Payments History ─────────────────────────────────────────────────────
    public string $filterPaymentEmpId = '';
    public string $filterPaymentMonth = '';

    // ─────────────────────────────────────────────────────────────────────────
    public function mount(): void
    {
        $this->attendanceMonth = now()->format('Y-m');
        $this->attendanceDate  = now()->format('Y-m-d');
        $this->salaryMonth     = now()->format('Y-m');
        $this->payDate         = now()->format('Y-m-d');
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
            'empMobile'      => 'nullable|string|max:15',
            'empType'        => 'required|in:teacher,management,employee,driver',
            'empSalary'      => 'required|numeric|min:0',
            'empDesignation' => 'nullable|string|max:255',
            'empPhoto'       => 'nullable|image|max:2048',
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
        $this->selectedEmployee   = AdminEmployee::with('teacherDetail')
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

    public function markAttendance($empId, string $status): void
    {
        $emp = AdminEmployee::find($empId);
        if (!$emp) return;

        if ($emp->isTeacher() && $emp->teacher_detail_id) {
            $intStatus = AdminEmployee::TEACHER_STATUS_MAP[$status] ?? 1;
            TeacherAttendance::updateOrCreate(
                [
                    'teacher_detail_id' => $emp->teacher_detail_id,
                    'attendance_date'   => $this->attendanceDate,
                ],
                [
                    'organization_id' => $this->orgId(),
                    'status'          => $intStatus,
                    'marked_by'       => Auth::id(),
                ]
            );
        } else {
            AdminAttendance::updateOrCreate(
                [
                    'admin_employee_id' => $empId,
                    'date'              => $this->attendanceDate,
                ],
                [
                    'organization_id' => $this->orgId(),
                    'status'          => $status,
                ]
            );
        }

        $this->notification()->success('Attendance marked!');
    }

    public function getAttendanceStatus($empId): ?string
    {
        $emp = AdminEmployee::find($empId);
        if (!$emp) return null;
        return $emp->getAttendanceStatusForDate($this->attendanceDate);
    }

    // ─── Salary ───────────────────────────────────────────────────────────────

    public function openPayModal($empId): void
    {
        $emp = AdminEmployee::forOrganization($this->orgId())->find($empId);
        if (!$emp) return;

        $existing = AdminSalaryPayment::where('admin_employee_id', $empId)
            ->where('organization_id', $this->orgId())
            ->where('month', $this->salaryMonth)
            ->first();

        $this->payEmployeeId    = $empId;
        $this->payAmount        = $existing?->amount ?? $emp->salary;
        $this->payMode          = $existing?->payment_mode ?? 'cash';
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
            'payDate',
            'payTransactionId',
            'payRemark',
            'payExistingId',
        ]);
        $this->payDate = now()->format('Y-m-d');
    }

    public function savePayment(): void
    {
        $this->validate([
            'payAmount' => 'required|numeric|min:0',
            'payMode'   => 'required|string',
            'payDate'   => 'required|date',
        ]);

        AdminSalaryPayment::updateOrCreate(
            [
                'admin_employee_id' => $this->payEmployeeId,
                'organization_id'   => $this->orgId(),
                'month'             => $this->salaryMonth,
            ],
            [
                'amount'         => $this->payAmount,
                'payment_mode'   => $this->payMode,
                'status'         => 'paid',
                'payment_date'   => $this->payDate,
                'transaction_id' => $this->payTransactionId ?: null,
                'remark'         => $this->payRemark ?: null,
            ]
        );

        $this->closePayModal();
        $this->notification()->success('Salary payment recorded!');
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $orgId = $this->orgId();

        // ── Employees — single query, reuse everywhere ─────────────────────────
        $allEmployees = AdminEmployee::forOrganization($orgId)
            ->orderBy('name')
            ->get();

        // Filter for attendance tab
        $employees = $this->filterAttendanceType
            ? $allEmployees->where('type', $this->filterAttendanceType)->values()
            : $allEmployees;

        // Filter for salary tab (reuse same collection — no extra query)
        $salaryEmployees = $this->filterSalaryType
            ? $allEmployees->where('type', $this->filterSalaryType)->values()
            : $allEmployees;

        // Reuse for payment filter dropdown
        $allEmployeesForFilter = $allEmployees;

        // ── Employee Stats — single grouped query ──────────────────────────────
        $empTypeCounts = AdminEmployee::forOrganization($orgId)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $empStats = [
            'total'      => $empTypeCounts->sum(),
            'teacher'    => $empTypeCounts->get('teacher', 0),
            'management' => $empTypeCounts->get('management', 0),
            'employee'   => $empTypeCounts->get('employee', 0),
            'driver'     => $empTypeCounts->get('driver', 0),
        ];

        // ── Teacher IDs — reused in both attendance sections ───────────────────
        $teacherIds = $allEmployees
            ->where('type', 'teacher')
            ->whereNotNull('teacher_detail_id')
            ->pluck('teacher_detail_id');

        // teacher_detail_id → employee_id map (no N+1)
        $teacherEmpMap = $allEmployees
            ->whereNotNull('teacher_detail_id')
            ->pluck('id', 'teacher_detail_id');

        // ── Attendance Counts — 1 query each for admin + teacher ───────────────
        $attCounts = AdminAttendance::forOrganization($orgId)
            ->where('date', $this->attendanceDate)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $presentCount = $attCounts->get('present', 0);
        $absentCount  = $attCounts->get('absent', 0);
        $halfDayCount = $attCounts->get('half_day', 0);
        $leaveCount   = $attCounts->get('leave', 0);

        if ($teacherIds->isNotEmpty()) {
            $teacherAttCounts = TeacherAttendance::whereIn('teacher_detail_id', $teacherIds)
                ->whereDate('attendance_date', $this->attendanceDate)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $presentCount += $teacherAttCounts->get(1, 0);
            $absentCount  += $teacherAttCounts->get(0, 0);
            $halfDayCount += $teacherAttCounts->get(2, 0) + $teacherAttCounts->get(3, 0);
        }

        // ── Monthly Attendance ────────────────────────────────────────────────
        $monthAttendance = AdminAttendance::forOrganization($orgId)
            ->forMonth($this->attendanceMonth)
            ->get()
            ->groupBy('admin_employee_id');

        // Teacher monthly attendance — no N+1
        $teacherMonthAttendance = [];
        if ($teacherIds->isNotEmpty()) {
            $raw = TeacherAttendance::whereIn('teacher_detail_id', $teacherIds)
                ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$this->attendanceMonth])
                ->get()
                ->groupBy('teacher_detail_id');

            foreach ($raw as $tdId => $records) {
                $empId = $teacherEmpMap->get($tdId);
                if ($empId) {
                    $teacherMonthAttendance[$empId] = $records;
                }
            }
        }

        // ── Salary Stats — single grouped query ───────────────────────────────
        $salaryStats = AdminSalaryPayment::forOrganization($orgId)
            ->forMonth($this->salaryMonth)
            ->selectRaw('status, count(*) as cnt, sum(amount) as total')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $paidThisMonth    = (int) ($salaryStats->get('paid')?->cnt ?? 0);
        $totalPaidAmount  = (float) ($salaryStats->get('paid')?->total ?? 0);
        $pendingThisMonth = $allEmployees->count() - $paidThisMonth;

        $monthSalaryPayments = AdminSalaryPayment::forOrganization($orgId)
            ->forMonth($this->salaryMonth)
            ->get()
            ->keyBy('admin_employee_id');

        // ── Payments History ──────────────────────────────────────────────────
        $payments = AdminSalaryPayment::forOrganization($orgId)
            ->with('employee')
            ->when($this->filterPaymentEmpId, fn($q) => $q->where('admin_employee_id', $this->filterPaymentEmpId))
            ->when($this->filterPaymentMonth,  fn($q) => $q->forMonth($this->filterPaymentMonth))
            ->latest()
            ->get();

        return view('livewire.admin.payroll', compact(
            'employees',
            'empStats',
            'presentCount',
            'absentCount',
            'halfDayCount',
            'leaveCount',
            'monthAttendance',
            'teacherMonthAttendance',
            'salaryEmployees',
            'paidThisMonth',
            'pendingThisMonth',
            'totalPaidAmount',
            'payments',
            'monthSalaryPayments',
            'allEmployeesForFilter',
        ));
    }
}
