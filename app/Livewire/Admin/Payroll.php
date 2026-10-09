<?php

namespace App\Livewire\Admin;

use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\DriverDetail;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\Transportation;
use App\Models\Teacher\TeacherAttendance;
use App\Models\Teacher\TeacherDetail;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
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
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'employees')]
    public string $activeTab = 'employees';

    /**
     * The order staff are listed in across every tab: management first, then
     * teachers, then drivers, then the rest of the employees. (The app's
     * payroll API sorts by this same constant.)
     */
    public const TYPE_ORDER = ['management' => 1, 'teacher' => 2, 'driver' => 3, 'employee' => 4];

    /** The Mark Attendance panel's own order: teachers first; A to Z within a type. */
    public const MARK_ORDER = ['teacher' => 1, 'management' => 2, 'driver' => 3, 'employee' => 4];

    /** Absences a month that are a paid leave — no pay is cut for them. */
    public const PAID_LEAVES_A_MONTH = 1;

    /**
     * What a row may be marked in that panel — the same four for everyone:
     * present, absent, half day, holiday. Staff could be put on Leave as well;
     * the school does not use it, so it is off the panel, and the leave already
     * marked was turned into absent (2026_10_02_010000 migration).
     */
    private const STAFF_MARKS   = ['present', 'absent', 'half_day', 'holiday'];
    private const TEACHER_MARKS = ['present', 'absent', 'half_day', 'holiday'];
    /** teacher_attendances.status, as the Attendance module writes it. */
    private const TEACHER_CODES = ['present' => 1, 'absent' => 0, 'half_day' => 2, 'holiday' => 3];

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
    public ?int   $pendingDeleteEmpId = null; // employee waiting on the delete confirm

    // ─── Add flow ─────────────────────────────────────────────────────────────
    // Add opens on "who are you adding?". Management and Employee carry on in
    // the form below; Driver gets the driver's own form (and becomes a
    // Transport driver too); Teacher opens the Teachers page's own form.
    public bool $empTypeChosen   = false; // adding: has that question been answered
    public bool $showTeacherForm = false; // Add → Teacher: the Teacher component, form only

    // Add → Driver: the Transport driver's fields, beside the payroll ones above.
    public string $drvLicenseNo  = '';
    public string $drvVehicleNo  = '';
    public        $drvExperience = '';
    public bool   $drvActive     = true;
    public array  $drvRoutes     = []; // route ids this driver covers

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

    // ─── Mark Attendance slide-in panel (everyone at once, teachers too) ──────
    // Its date and rows live here, apart from the filters above, so every open
    // starts clean on today — as the Attendance page's panel does.
    public bool   $showMarkPanel = false;
    public string $panelDate     = '';
    public array  $panelRows     = [];    // admin_employee_id => ['status', 'remark']
    public bool   $panelExisting = false; // this date already has marks → editing

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

    // ─── Mark Salary: one person's account ────────────────────────────────────
    // The tab opens on a type and then one of its people. Their months since
    // 1 April of the session are listed — attendance, the salary it works out
    // to, what was paid — and Add Payment (in the header) records a payment:
    // amount, date, from, remark.
    public string $salaryType         = '';
    public string $salaryEmpId        = '';
    public bool   $showSalaryPayPanel = false;
    public        $spAmount           = '';
    public string $spDate             = '';
    public string $spFrom             = '';
    public string $spRemark           = '';
    // A month's own screen (the arrow on its row): its salary, attendance and
    // salary so far, then the payments kept against it; its Add Payment keeps
    // the payment against that month (spMonth), whatever day it is paid on.
    public string $salaryMonthView    = '';
    public string $spMonth            = '';

    // ─── Payments History ─────────────────────────────────────────────────────
    // Filter: a type, then one of its people, then a month (the search box is off the page).
    public string $filterPaymentType  = '';
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

        // Rows made before a person's rows were joined (a teacher or manager
        // who also drives) are joined once as the page opens.
        $this->ensurePayrollEmployees();
        $this->mergeSamePersonRows();
    }

    /**
     * Make sure every teacher and driver in the org has a payroll row, so they
     * show up here automatically without being re-added by hand. Returns
     * whether any row was added.
     */
    private function ensurePayrollEmployees(): bool
    {
        $org = $this->orgId();
        $added = false;

        // Teachers → link via teacher_detail_id
        $linkedTeachers = AdminEmployee::forOrganization($org)
            ->whereNotNull('teacher_detail_id')->pluck('teacher_detail_id')->all();
        TeacherDetail::with('user')
            ->where('organization_id', $org)
            ->when(count($linkedTeachers), fn($q) => $q->whereNotIn('id', $linkedTeachers))
            ->get()
            ->each(function ($td) use ($org, &$added) {
                if (!$td->user) return;
                $added = true;
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
            return $added;
        }
        $linkedDrivers = AdminEmployee::forOrganization($org)
            ->whereNotNull('driver_detail_id')->pluck('driver_detail_id')->all();
        DriverDetail::with('user')
            ->where('organization_id', $org)
            ->when(count($linkedDrivers), fn($q) => $q->whereNotIn('id', $linkedDrivers))
            ->get()
            ->each(function ($dd) use ($org, &$added) {
                $added = true;
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

        return $added;
    }

    /**
     * One person, one payroll row. A driver from Transport who is also a
     * teacher, a manager or an employee here (see AdminEmployee::isSamePersonAs)
     * is folded into that row, which then reads "teacher, driver" or
     * "management, driver". Returns how many driver rows were folded.
     */
    private function mergeSamePersonRows(): int
    {
        if (!Schema::hasColumn('admin_employees', 'driver_detail_id')) {
            return 0;
        }

        $rows = AdminEmployee::with(['teacherDetail.user', 'driverDetail.user'])
            ->forOrganization($this->orgId())
            ->orderBy('id')
            ->get();

        $drivers = $rows->filter(fn ($e) => $e->isLinkedDriverOnly());
        if ($drivers->isEmpty()) {
            return 0;
        }

        // A teacher's row takes the driver first, then management, then employees.
        $keepers = $rows
            ->filter(fn ($e) => $e->type !== 'driver' && !$e->driver_detail_id)
            ->sortBy(fn ($e) => [['teacher' => 0, 'management' => 1][$e->type] ?? 2, $e->id])
            ->values();

        $merged = 0;
        foreach ($drivers as $driver) {
            $keeper = $keepers->first(fn ($k) => $k->isSamePersonAs($driver));
            if (!$keeper) continue;

            $this->foldDriverInto($keeper, $driver);
            $keepers = $keepers->reject(fn ($k) => $k->id === $keeper->id)->values();
            $merged++;
        }

        return $merged;
    }

    /**
     * Move a driver row's records onto the same person's other row and drop
     * it: attendance (the kept row's own mark wins on a day both were marked),
     * salary payments, ID cards and the Transport link. The two salaries add
     * up to what was being paid across both rows, and the kept row takes any
     * contact or bank detail it was missing.
     */
    private function foldDriverInto(AdminEmployee $keeper, AdminEmployee $driver): void
    {
        DB::transaction(function () use ($keeper, $driver) {
            $keeperDays = AdminAttendance::where('admin_employee_id', $keeper->id)
                ->pluck('date')
                ->map(fn ($d) => Carbon::parse($d)->toDateString())
                ->all();
            AdminAttendance::where('admin_employee_id', $driver->id)
                ->when($keeperDays, fn ($q) => $q->whereNotIn('date', $keeperDays))
                ->update(['admin_employee_id' => $keeper->id]);
            AdminAttendance::where('admin_employee_id', $driver->id)->delete();

            AdminSalaryPayment::where('admin_employee_id', $driver->id)
                ->update(['admin_employee_id' => $keeper->id]);
            EmployeeIdCard::where('admin_employee_id', $driver->id)
                ->update(['admin_employee_id' => $keeper->id]);

            foreach (['email', 'mobile', 'address', 'bank_name', 'bank_account_no', 'bank_holder_name', 'bank_branch', 'bank_ifsc', 'photo', 'joining_date'] as $field) {
                if (blank($keeper->getAttribute($field)) && filled($driver->getAttribute($field))) {
                    $keeper->setAttribute($field, $driver->getAttribute($field));
                }
            }
            $keeper->salary           = (float) $keeper->salary + (float) $driver->salary;
            $keeper->driver_detail_id = $driver->driver_detail_id;

            $driver->delete();
            $keeper->save();
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
            // The model keeps joining_date as the column's string (no date cast):
            // calling ->format() on it broke Edit for anyone who had a joining date.
            $this->empJoiningDate     = $emp->joining_date ? Carbon::parse($emp->joining_date)->format('Y-m-d') : '';
            $this->empExistingPhoto   = $emp->photo;
            $this->empTeacherDetailId = $emp->teacher_detail_id;
            // An entry being edited already has its type.
            $this->empTypeChosen      = true;
        }

        $this->showTeacherForm = false;
        $this->showEmpModal    = true;
    }

    /**
     * The answer to "who are you adding?". Management, Employee and Driver
     * carry on in this panel; Teacher hands over to the Teachers page's own
     * form (App\Livewire\Admin\Teacher in its form-only mode), so a teacher
     * added here is added exactly as there.
     */
    public function chooseEmpType(string $type): void
    {
        if ($this->editEmpId || !in_array($type, ['management', 'teacher', 'driver', 'employee'], true)) {
            return;
        }

        $this->resetValidation();

        if ($type === 'teacher') {
            $this->showEmpModal = false;
            $this->resetEmpForm();
            $this->showTeacherForm = true;
            return;
        }

        // Picked from the teacher form (the question stays on top of it too):
        // back to this panel, on the form of the type picked.
        if ($this->showTeacherForm) {
            $this->showTeacherForm = false;
            $this->showEmpModal    = true;
        }

        $this->empType       = $type;
        $this->empTypeChosen = true;
    }

    /** The teacher form was closed without saving. */
    #[On('teacherFormClosed')]
    public function closeTeacherForm(): void
    {
        $this->showTeacherForm = false;
    }

    /** The teacher form saved: render() gives the new teacher their payroll row. */
    #[On('onTeacherAddUpdate')]
    public function teacherAdded(): void
    {
        if (!$this->showTeacherForm) {
            return;
        }

        $this->showTeacherForm = false;
        $this->notification()->info('Added to Payroll', 'Set the salary from Edit.');
    }

    public function saveEmployee(): void
    {
        // A new driver is saved with the driver's own form, as a Transport driver too.
        if (!$this->editEmpId && $this->empType === 'driver') {
            $this->saveNewDriver();
            return;
        }

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
        ], [
            // So a message under a field reads "The name field is required."
            'empName'        => 'name',
            'empEmail'       => 'email',
            'empMobile'      => 'mobile',
            'empType'        => 'type',
            'empSalary'      => 'salary',
            'empDesignation' => 'designation',
            'empAddress'     => 'address',
            'empBankName'    => 'bank name',
            'empHolderName'  => 'account holder',
            'empBranch'      => 'branch',
            'empPhoto'       => 'photo',
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

        if ($message = $this->samePersonConflict($data)) {
            $this->addError('empMobile', $message);
            return;
        }

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
        } else {
            AdminEmployee::create($data);
        }

        // Someone already listed as a Transport driver joins that row.
        $joined = $this->mergeSamePersonRows() > 0;
        $this->notification()->success(
            $this->editEmpId ? 'Employee updated!' : 'Employee added!',
            $joined ? 'Also a driver in Transport, so listed once with both types.' : null
        );

        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    /**
     * Add → Driver. The driver is made as Transport → Add Driver makes one — a
     * login (default password 123456), the driver record, the routes picked —
     * so they are listed there as well, and their payroll row is made with it,
     * carrying the salary and bank details from this form. (The steps are
     * Transport::saveDriver's, copied: change one, change both.) Someone who is
     * already on payroll as a teacher, manager or employee is joined into that
     * row, which then reads with both types.
     */
    private function saveNewDriver(): void
    {
        $this->validate([
            'empName'        => 'required|string|max:255',
            'empEmail'       => 'nullable|email|max:255|unique:users,email',
            'empMobile'      => 'required|regex:/^[6-9]\d{9}$/',
            'drvLicenseNo'   => 'nullable|string|max:50',
            'drvVehicleNo'   => 'nullable|string|max:30',
            'drvExperience'  => 'nullable|integer|min:0|max:50',
            'empSalary'      => 'required|numeric|min:0|max:99999999',
            'empJoiningDate' => 'nullable|date',
            'empAddress'     => 'nullable|string|max:500',
            'empBankName'    => 'nullable|string|max:100',
            'empHolderName'  => 'nullable|string|max:100',
            'empAccountNo'   => 'nullable|regex:/^\d{6,20}$/',
            'empBranch'      => 'nullable|string|max:100',
            'empIfsc'        => 'nullable|regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/',
            'empPhoto'       => 'nullable|image|max:1024', // 1 MB
        ], [
            'empMobile.required' => 'Mobile number is required.',
            'empMobile.regex'    => 'Enter a valid 10-digit mobile number.',
            'empAccountNo.regex' => 'Account number must be 6–20 digits.',
            'empIfsc.regex'      => 'Enter a valid IFSC code (e.g. HDFC0001234).',
            'empPhoto.max'       => 'Photo must be 1 MB or smaller.',
        ], [
            'empName'        => 'name',
            'empEmail'       => 'email',
            'empMobile'      => 'mobile',
            'drvLicenseNo'   => 'license no.',
            'drvVehicleNo'   => 'vehicle no.',
            'drvExperience'  => 'experience',
            'empSalary'      => 'salary',
            'empJoiningDate' => 'joining date',
            'empAddress'     => 'address',
        ]);

        $org = $this->orgId();

        try {
            // The photo is kept twice, once for each record, so replacing it on
            // one screen never takes it off the other.
            $driverPhoto = $payrollPhoto = null;
            if ($this->empPhoto) {
                $path = $this->empPhoto->store('admin/drivers/photos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $driverPhoto = Storage::disk('s3')->url($path);

                $path = $this->empPhoto->store('admin/payroll/photos', 's3');
                Storage::disk('s3')->setVisibility($path, 'public');
                $payrollPhoto = Storage::disk('s3')->url($path);
            }

            DB::transaction(function () use ($org, $driverPhoto, $payrollPhoto) {
                $user = User::create([
                    'name'            => $this->empName,
                    // users.email cannot be empty: a driver saved without one
                    // gets Transport's stand-in address.
                    'email'           => $this->empEmail !== '' ? $this->empEmail : $this->placeholderDriverEmail($this->empMobile),
                    'mobile_number'   => $this->empMobile,
                    'password'        => Hash::make('123456'),
                    'role'            => 'driver',
                    'organization_id' => $org,
                    'is_active'       => $this->drvActive,
                ]);

                $driver = DriverDetail::create([
                    'user_id'          => $user->id,
                    'organization_id'  => $org,
                    'image'            => $driverPhoto,
                    'phone'            => $this->empMobile,
                    'license_no'       => $this->drvLicenseNo,
                    'vehicle_no'       => $this->drvVehicleNo,
                    'address'          => $this->empAddress,
                    'experience_years' => (int) ($this->drvExperience ?: 0),
                    'is_active'        => $this->drvActive,
                ]);

                // The routes picked now run with this driver.
                $routes = array_values(array_filter(array_map('intval', $this->drvRoutes)));
                if ($routes) {
                    Transportation::where('organization_id', $org)
                        ->whereIn('id', $routes)
                        ->update(['driver_detail_id' => $driver->id]);
                }

                AdminEmployee::create([
                    'organization_id'  => $org,
                    'driver_detail_id' => $driver->id,
                    'name'             => $this->empName,
                    'email'            => $this->empEmail ?: null,
                    'mobile'           => $this->empMobile,
                    'designation'      => $this->empDesignation ?: 'Driver',
                    'type'             => 'driver',
                    'salary'           => $this->empSalary,
                    'address'          => $this->empAddress,
                    'bank_name'        => $this->empBankName,
                    'bank_account_no'  => $this->empAccountNo,
                    'bank_holder_name' => $this->empHolderName,
                    'bank_branch'      => $this->empBranch,
                    'bank_ifsc'        => $this->empIfsc ? strtoupper($this->empIfsc) : null,
                    'photo'            => $payrollPhoto,
                    'joining_date'     => $this->empJoiningDate ?: null,
                ]);
            });
        } catch (\Throwable $e) {
            logger()->error('Payroll driver save error: ' . $e->getMessage());
            $this->notification()->error('Error!', 'Failed to save driver: ' . $e->getMessage());
            return;
        }

        $joined = $this->mergeSamePersonRows() > 0;
        $this->notification()->success(
            'Driver added!',
            $joined ? 'Already on payroll, so listed once with both types.' : 'Also listed under Transport → Drivers.'
        );

        $this->showEmpModal = false;
        $this->resetEmpForm();
    }

    /** Transport's stand-in address for a driver saved without an email (users.email cannot be empty). */
    private function placeholderDriverEmail(string $phone): string
    {
        $base  = 'driver' . ($phone !== '' ? '.' . $phone : '') . '.' . $this->orgId();
        $email = $base . Transport::DRIVER_EMAIL_DOMAIN;
        $i     = 1;
        while (User::where('email', $email)->exists()) {
            $email = $base . '-' . $i++ . Transport::DRIVER_EMAIL_DOMAIN;
        }

        return $email;
    }

    /**
     * Stop a second row for someone already on payroll where a driver is
     * involved: a driver added by hand who is already a teacher, manager or
     * employee here, or anyone matching a driver who was added by hand.
     * (A Transport driver is fine — that row is joined in after saving.)
     */
    private function samePersonConflict(array $data): ?string
    {
        $editing = $this->editEmpId
            ? AdminEmployee::forOrganization($this->orgId())->find($this->editEmpId)
            : null;
        $person = $editing ? clone $editing : new AdminEmployee();
        $person->fill(array_intersect_key($data, array_flip(['name', 'mobile', 'type', 'teacher_detail_id'])));

        $twin = AdminEmployee::with(['teacherDetail.user', 'driverDetail.user'])
            ->forOrganization($this->orgId())
            ->when($editing, fn ($q) => $q->whereKeyNot($editing->id))
            ->get()
            ->first(fn ($e) => $e->isSamePersonAs($person));
        if (!$twin) {
            return null;
        }

        $as = implode(', ', array_map('ucfirst', $twin->types()));

        if ($person->type === 'driver' && !$person->driver_detail_id && $twin->type !== 'driver') {
            return "{$twin->name} is already on payroll as {$as}. Add them as a driver under Transport → Drivers and they will be listed once, with both types.";
        }
        if ($person->type !== 'driver' && $twin->type === 'driver' && !$twin->driver_detail_id) {
            return "{$twin->name} is already on payroll as {$as}. Edit that entry instead.";
        }

        return null;
    }

    /**
     * Ask before deleting, in the page's own modal: WireUI's dialog builds its
     * classes at runtime, they are not in the compiled Tailwind bundle, and it
     * never showed.
     */
    public function deleteEmployee($id): void
    {
        $this->pendingDeleteEmpId = AdminEmployee::forOrganization($this->orgId())->whereKey($id)->exists()
            ? (int) $id
            : null;
    }

    public function cancelDeleteEmployee(): void
    {
        $this->pendingDeleteEmpId = null;
    }

    public function doDeleteEmployee(): void
    {
        if ($this->pendingDeleteEmpId) {
            AdminEmployee::forOrganization($this->orgId())->find($this->pendingDeleteEmpId)?->delete();
            $this->notification()->success('Employee deleted!');
        }
        $this->pendingDeleteEmpId = null;
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
            'Type'         => implode(', ', array_map('ucfirst', $employee->types())),
            'Mobile'       => $employee->mobile ?? 'N/A',
            'Email'        => $employee->email ?? 'N/A',
            'Salary'       => '₹' . number_format($employee->salary, 0),
            'Joining Date' => $employee->joining_date ? Carbon::parse($employee->joining_date)->format('d M Y') : 'N/A',
        ];

        if ($employee->address) {
            $details['Address'] = $employee->address;
        }

        if ($employee->teacher_detail_id) {
            $details['Linked Teacher'] = $employee->teacherDetail?->user?->name ?? ('Teacher #' . $employee->teacher_detail_id);
        }
        if ($employee->driver_detail_id) {
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
            'empTypeChosen',
            'drvLicenseNo',
            'drvVehicleNo',
            'drvExperience',
            'drvActive',
            'drvRoutes',
        ]);
        $this->empType = 'employee';
    }

    public function closeEmpModal(): void
    {
        $this->showEmpModal = false;
        $this->resetEmpForm();
        $this->resetValidation();
    }

    // ─── Attendance ───────────────────────────────────────────────────────────

    /** Switching tabs always drops back to the read-only attendance view. */
    public function updatedActiveTab(): void
    {
        $this->attendanceMode  = 'view';
        $this->attendanceDraft = [];
        $this->closeMarkPanel();
        $this->showSalaryPayPanel = false;
        $this->salaryMonthView    = '';
    }

    // ─── Mark Attendance panel: everyone at once ──────────────────────────────

    /** A teacher whose days are the Attendance module's (teacher_attendances); everyone else is marked in admin_attendances. */
    private function marksAsTeacher(AdminEmployee $emp): bool
    {
        return $emp->isTeacher() && (bool) $emp->teacher_detail_id;
    }

    /** teacher_attendances.status as the panel names it (the app's 4 is a holiday too). */
    private function teacherMarkLabel($code): string
    {
        return match ((int) $code) {
            1       => 'present',
            0       => 'absent',
            2       => 'half_day',
            3, 4    => 'holiday',
            default => '',
        };
    }

    /**
     * Header "Mark Attendance" — always a fresh flow: the panel opens on today
     * and reads what is saved for it, with nothing carried in from the filters
     * or from an earlier marking run.
     */
    public function openMarkPanel(): void
    {
        $this->resetValidation();
        $this->attendanceMode = 'view';
        $this->panelDate      = now()->toDateString();
        $this->loadMarkPanel();
        $this->showMarkPanel  = true;
    }

    public function closeMarkPanel(): void
    {
        $this->showMarkPanel = false;
        $this->panelRows     = [];
        $this->panelExisting = false;
    }

    public function updatedPanelDate(): void
    {
        // Only a real calendar day, and not one still to come, is loaded.
        $d = \DateTime::createFromFormat('!Y-m-d', (string) $this->panelDate);
        if (!$d || $d->format('Y-m-d') !== $this->panelDate || $this->panelDate > now()->toDateString()) {
            $this->panelDate = now()->toDateString();
        }

        $this->loadMarkPanel();
    }

    /** What is saved for a day: staff rows by employee id, teachers' by teacher id. */
    private function savedMarks(string $date, $employees): array
    {
        $staff = AdminAttendance::forOrganization($this->orgId())
            ->where('date', $date)
            ->get()->keyBy('admin_employee_id');

        $teacherIds = $employees->filter(fn ($e) => $this->marksAsTeacher($e))->pluck('teacher_detail_id')->all();
        $teachers = $teacherIds
            ? TeacherAttendance::whereIn('teacher_detail_id', $teacherIds)
                ->whereDate('attendance_date', $date)
                ->get()->keyBy('teacher_detail_id')
            : collect();

        return [$staff, $teachers];
    }

    /**
     * Load the panel's rows for $panelDate: a row that is already marked comes
     * back with its status and remark, so the day can simply be corrected and
     * saved again; every other row starts blank.
     */
    public function loadMarkPanel(): void
    {
        $employees = AdminEmployee::forOrganization($this->orgId())->get();
        [$staff, $teachers] = $this->savedMarks($this->panelDate, $employees);

        $this->panelRows     = [];
        $this->panelExisting = false;

        foreach ($employees as $emp) {
            if ($this->marksAsTeacher($emp)) {
                $rec = $teachers->get($emp->teacher_detail_id);
                $row = ['status' => $rec ? $this->teacherMarkLabel($rec->status) : '', 'remark' => (string) ($rec->remarks ?? '')];
            } else {
                $rec = $staff->get($emp->id);
                $row = ['status' => $rec ? (string) $rec->status : '', 'remark' => (string) ($rec->note ?? '')];
            }

            $this->panelExisting  = $this->panelExisting || (bool) $rec;
            $this->panelRows[$emp->id] = $row;
        }
    }

    /**
     * Save the panel against $panelDate, everyone in one go. A teacher's row
     * goes to the Attendance module's own records (so it reads the same
     * there), everyone else's to payroll's. A row left blank is written as
     * nothing at all — and an earlier mark for it is removed — so the day stays
     * open; a row still showing exactly what is saved is left as it is.
     */
    public function saveMarkPanel(): void
    {
        $org  = $this->orgId();
        $date = $this->panelDate;

        $d = \DateTime::createFromFormat('!Y-m-d', (string) $date);
        if (!$d || $d->format('Y-m-d') !== $date || $date > now()->toDateString()) {
            $this->notification()->error('Pick a valid date', 'Attendance can be marked for today or an earlier day.');
            return;
        }

        if (count(array_filter($this->panelRows, fn ($r) => ($r['status'] ?? '') !== '')) === 0) {
            $this->notification()->error('Nothing to save', 'Mark at least one person first.');
            return;
        }

        // A holiday for everyone has to say what it is for: the one remark the
        // panel asks for goes on every row, and none may be left blank.
        $statuses = array_map(fn ($r) => (string) ($r['status'] ?? ''), $this->panelRows);
        if (array_values(array_unique($statuses)) === ['holiday']
            && array_filter($this->panelRows, fn ($r) => trim((string) ($r['remark'] ?? '')) === '')) {
            $this->notification()->error('Remark needed', 'Say what the holiday is for — the remark is compulsory when everyone is on Holiday.');
            return;
        }

        // The statuses are set in the browser, so the rows arrive as client
        // data: only this school's people, and only what their row offers.
        $employees = AdminEmployee::forOrganization($org)
            ->whereIn('id', array_keys($this->panelRows))
            ->get()->keyBy('id');
        [$staff, $teachers] = $this->savedMarks($date, $employees);
        $wasEdit = $staff->isNotEmpty() || $teachers->isNotEmpty();
        $markedBy = Auth::id();

        try {
        DB::transaction(function () use ($org, $date, $employees, $staff, $teachers, $markedBy) {
            foreach ($this->panelRows as $empId => $row) {
                $emp = $employees->get($empId);
                if (!$emp) {
                    continue;
                }
                $status = (string) ($row['status'] ?? '');
                $remark = trim((string) ($row['remark'] ?? ''));

                if ($this->marksAsTeacher($emp)) {
                    $rec = $teachers->get($emp->teacher_detail_id);
                    if ($status === '') {
                        $rec?->delete();
                        continue;
                    }
                    if (!in_array($status, self::TEACHER_MARKS, true)) {
                        continue;
                    }
                    if ($rec && $this->teacherMarkLabel($rec->status) === $status && (string) $rec->remarks === $remark) {
                        continue;
                    }
                    $values = ['status' => self::TEACHER_CODES[$status], 'remarks' => $remark, 'marked_by' => $markedBy];
                    $rec
                        ? $rec->update($values)
                        : TeacherAttendance::create($values + [
                            'teacher_detail_id' => $emp->teacher_detail_id,
                            'organization_id'   => $org,
                            'attendance_date'   => $date,
                        ]);
                    continue;
                }

                $rec = $staff->get($emp->id);
                if ($status === '') {
                    $rec?->delete();
                    continue;
                }
                if (!in_array($status, self::STAFF_MARKS, true)) {
                    continue;
                }
                if ($rec && (string) $rec->status === $status && (string) $rec->note === $remark) {
                    continue;
                }
                AdminAttendance::updateOrCreate(
                    ['admin_employee_id' => $emp->id, 'date' => $date],
                    ['organization_id' => $org, 'status' => $status, 'note' => $remark !== '' ? $remark : null]
                );
            }
        });
        } catch (\Throwable $e) {
            logger()->error('Payroll attendance save error: ' . $e->getMessage());
            $this->notification()->error('Could not save', 'The attendance was not saved. Please try again.');
            return;
        }

        // Straight to that date's attendance, whatever was being viewed before.
        $this->closeMarkPanel();
        $this->attendanceDraft      = [];
        $this->attendanceMode       = 'view';
        $this->attendanceDate       = $date;
        $this->attEmpId             = '';
        $this->attMonth             = '';
        $this->attStatus            = '';
        $this->filterAttendanceType = '';

        $this->notification()->success(
            $wasEdit ? 'Attendance updated' : 'Attendance saved',
            'Attendance ' . ($wasEdit ? 'updated' : 'saved') . ' for ' . Carbon::parse($date)->format('d M Y') . '.'
        );
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

    /** Clear the draft when the date changes; picking a date switches to date-mode: everyone, that day. */
    public function updatedAttendanceDate(): void
    {
        $this->attendanceDraft = [];
        if ($this->attendanceDate !== '') {
            $this->attEmpId             = '';
            $this->filterAttendanceType = '';
        }
    }

    /** Picking a type starts over on that type's people (and leaves the date view). */
    public function updatedFilterAttendanceType(): void
    {
        $this->attEmpId = '';
        if ($this->filterAttendanceType !== '') {
            $this->attendanceDate = '';
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

    /**
     * The Employees list's attendance column: for the running month, the days
     * each person was present out of their working days — the days they were
     * marked on, holidays left out; a half day counts as half. A teacher's come
     * from the Attendance module's records, everyone else's from payroll's.
     *
     * @return array<int, array{present: float, working: int, absent: int, half: int, leave: int}>
     */
    private function monthAttendanceSummary($employees): array
    {
        $month = now()->format('Y-m');

        $staff = AdminAttendance::forOrganization($this->orgId())->forMonth($month)->get()->groupBy('admin_employee_id');

        $teacherIds = $employees->filter(fn ($e) => $this->marksAsTeacher($e))->pluck('teacher_detail_id')->all();
        $teachers = $teacherIds
            ? TeacherAttendance::whereIn('teacher_detail_id', $teacherIds)
                ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$month])
                ->get()->groupBy('teacher_detail_id')
            : collect();

        $out = [];
        foreach ($employees as $emp) {
            if ($this->marksAsTeacher($emp)) {
                $labels = $teachers->get($emp->teacher_detail_id, collect())->map(fn ($r) => $this->teacherMarkLabel($r->status));
            } else {
                $labels = $staff->get($emp->id, collect())->map(fn ($r) => (string) $r->status);
            }
            $n = $labels->countBy();

            $present = (int) ($n['present'] ?? 0);
            $half    = (int) ($n['half_day'] ?? 0);
            $absent  = (int) ($n['absent'] ?? 0);
            $leave   = (int) ($n['leave'] ?? 0);

            $out[$emp->id] = [
                'present' => $present + 0.5 * $half,
                'working' => $present + $half + $absent + $leave,
                'absent'  => $absent,
                'half'    => $half,
                'leave'   => $leave,
            ];
        }

        return $out;
    }

    /** Everyone's status and remark on one date (null where unmarked), keyed by employee id. */
    private function dayMarks(string $date, $employees): array
    {
        [$staff, $teachers] = $this->savedMarks($date, $employees);

        $out = [];
        foreach ($employees as $emp) {
            if ($this->marksAsTeacher($emp)) {
                $rec = $teachers->get($emp->teacher_detail_id);
                $out[$emp->id] = ['status' => $rec ? ($this->teacherMarkLabel($rec->status) ?: null) : null, 'remark' => (string) ($rec->remarks ?? '')];
            } else {
                $rec = $staff->get($emp->id);
                $out[$emp->id] = ['status' => $rec ? (string) $rec->status : null, 'remark' => (string) ($rec->note ?? '')];
            }
        }

        return $out;
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
                    // The Attendance module's codes: 2 is a half day, 3 a holiday
                    // (4 is the app's holiday). 3 used to be read as a half day here.
                    $map[Carbon::parse($r->attendance_date)->format('Y-m-d')] =
                        ['0' => 'absent', '1' => 'present', '2' => 'half_day', '3' => 'holiday', '4' => 'holiday'][(string) $r->status] ?? null;
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
        $this->reset(['paymentSearch', 'filterPaymentType', 'filterPaymentEmpId', 'filterPaymentMonth']);
    }

    /** Another type starts over on its people. */
    public function updatedFilterPaymentType(): void
    {
        $this->filterPaymentEmpId = '';
    }

    // ─── Salary ───────────────────────────────────────────────────────────────

    /** Salary is payable only once the month is over. Current/future month → locked. */
    private function canPayMonth(): bool
    {
        return $this->salaryMonth < now()->format('Y-m');
    }

    /**
     * Attendance-based payable for an employee in the selected salary month.
     * Present / leave / unmarked days are paid in full. The month's first
     * absent is a paid leave and costs nothing; each absent after it is a full
     * per-day cut (eight absents cut seven days), and each half day a half cut.
     */
    private function salaryBreakdown(AdminEmployee $emp, $adminGrouped, $teacherGrouped, ?string $month = null): array
    {
        $base = (float) $emp->salary;
        // The month being worked out: the one named, else the salary tab's.
        $daysInMonth = (int) Carbon::parse(($month ?: $this->salaryMonth) . '-01')->daysInMonth;

        if ($emp->isTeacher() && isset($teacherGrouped[$emp->id])) {
            $records = $teacherGrouped[$emp->id];
            $present = $records->where('status', 1)->count();
            $absent  = $records->where('status', 0)->count();
            // Only 2 is a half day. 3 is the Attendance module's Holiday: it used
            // to be counted here too, and cost the teacher half a day's pay.
            $halfDay = $records->where('status', 2)->count();
            $leave   = 0;
        } else {
            $records = $adminGrouped->get($emp->id, collect());
            $present = $records->where('status', 'present')->count();
            $absent  = $records->where('status', 'absent')->count();
            $halfDay = $records->where('status', 'half_day')->count();
            $leave   = $records->where('status', 'leave')->count();
        }

        $perDay    = $daysInMonth > 0 ? $base / $daysInMonth : 0;
        $paidLeave = min($absent, self::PAID_LEAVES_A_MONTH);
        $cutDays   = ($absent - $paidLeave) + 0.5 * $halfDay;
        $deduction = $cutDays * $perDay;
        $payable   = max(0, round($base - $deduction));

        return compact('present', 'absent', 'halfDay', 'leave', 'payable', 'paidLeave', 'cutDays')
            + ['base' => $base, 'perDay' => $perDay, 'deduction' => max(0, $base - $payable)];
    }

    /** Another type starts over on its people. */
    public function updatedSalaryType(): void
    {
        $this->salaryEmpId = '';
        $this->salaryMonthView = '';
        $this->closeSalaryPayment();
    }

    /** Someone else picked: back to their months. */
    public function updatedSalaryEmpId(): void
    {
        $this->salaryMonthView = '';
        $this->closeSalaryPayment();
    }

    public function clearSalaryPerson(): void
    {
        $this->salaryType  = '';
        $this->salaryEmpId = '';
        $this->salaryMonthView = '';
        $this->closeSalaryPayment();
    }

    /** The arrow on a month's row: that month's own screen. */
    public function openSalaryMonth(string $ym): void
    {
        if (!$this->salaryPerson() || !preg_match('/^\d{4}-\d{2}$/', $ym)) {
            return;
        }
        $this->closeSalaryPayment();
        $this->salaryMonthView = $ym;
    }

    /** The filter bar's month: a month's screen, or All months (''). */
    public function updatedSalaryMonthView(): void
    {
        $this->closeSalaryPayment();
        if ($this->salaryMonthView !== '' && !preg_match('/^\d{4}-\d{2}$/', $this->salaryMonthView)) {
            $this->salaryMonthView = '';
        }
    }

    /** Back from a month's screen to the person's months. */
    public function closeSalaryMonth(): void
    {
        $this->closeSalaryPayment();
        $this->salaryMonthView = '';
    }

    /** The person the Mark Salary tab is on, if one is picked (and is this school's). */
    private function salaryPerson(): ?AdminEmployee
    {
        return $this->salaryEmpId !== ''
            ? AdminEmployee::forOrganization($this->orgId())->find((int) $this->salaryEmpId)
            : null;
    }

    /**
     * One person's salary account: every month from 1 April of the running
     * session (or the month they joined, when that is later) to this month —
     * the attendance marked in it, the salary it works out to (the same sum as
     * everywhere else: absent a day's cut, a half day half of one), and what
     * was paid in it — with the totals. The running month is worked out on
     * what is marked so far.
     */
    private function salaryAccount(AdminEmployee $emp): array
    {
        $start = \App\Support\AcademicYear::start()->startOfMonth();
        if ($emp->joining_date) {
            $joined = Carbon::parse($emp->joining_date)->startOfMonth();
            if ($joined->gt($start) && $joined->lte(now())) {
                $start = $joined;
            }
        }
        $now = now()->startOfMonth();

        // The period's attendance, by month.
        $staff = AdminAttendance::forOrganization($this->orgId())
            ->where('admin_employee_id', $emp->id)
            ->where('date', '>=', $start->toDateString())
            ->get()->groupBy(fn ($r) => Carbon::parse($r->date)->format('Y-m'));
        $teacher = $this->marksAsTeacher($emp)
            ? TeacherAttendance::where('teacher_detail_id', $emp->teacher_detail_id)
                ->where('attendance_date', '>=', $start->toDateString())
                ->get()->groupBy(fn ($r) => Carbon::parse($r->attendance_date)->format('Y-m'))
            : collect();

        // What was paid, by the month it is recorded against.
        $payments = AdminSalaryPayment::forOrganization($this->orgId())
            ->where('admin_employee_id', $emp->id)
            ->where('status', 'paid')
            ->orderBy('payment_date')->orderBy('id')
            ->get()->groupBy('month');

        $months = [];
        $totalPayable = $totalPaid = 0.0;
        for ($m = $start->copy(); $m->lte($now); $m->addMonthNoOverflow()) {
            $ym = $m->format('Y-m');

            $b = $this->salaryBreakdown(
                $emp,
                collect([$emp->id => $staff->get($ym, collect())]),
                $teacher->has($ym) ? [$emp->id => $teacher->get($ym)] : [],
                $ym
            );
            $paid = $payments->get($ym, collect());

            $months[] = [
                'ym'       => $ym,
                'label'    => $m->format('F Y'),
                'running'  => $m->equalTo($now),
                'base'     => $b['base'],
                'present'  => $b['present'],
                'absent'   => $b['absent'],
                'half'     => $b['halfDay'],
                'leave'    => $b['leave'],
                'payable'  => (float) $b['payable'],
                'paid_leave' => $b['paidLeave'],
                'cut_days'   => $b['cutDays'],
                'per_day'    => (float) $b['perDay'],
                'deduction'  => (float) $b['deduction'],
                'paid'     => (float) $paid->sum('amount'),
                'payments' => $paid->map(fn ($p) => [
                    'id'     => $p->id,
                    'amount' => (float) $p->amount,
                    'date'   => $p->payment_date?->format('d M Y') ?? '—',
                    'from'   => $p->paid_by,
                    'remark' => $p->remark,
                    'mode'   => $p->payment_mode,
                ])->values()->all(),
            ];

            $totalPayable += (float) $b['payable'];
            $totalPaid    += (float) $paid->sum('amount');
        }

        return [
            'months'  => array_reverse($months),   // this month on top
            'payable' => $totalPayable,
            'paid'    => $totalPaid,
            'balance' => $totalPayable - $totalPaid,
        ];
    }

    /**
     * The header's Add Payment: a blank form on today, from the person signed
     * in. From a month's screen ($month) the payment is kept against that
     * month; from the header, against the month of its date (as before).
     */
    public function openSalaryPayment(?string $month = null): void
    {
        if (!$this->salaryPerson()) {
            return;
        }

        $this->resetValidation();
        $this->spMonth  = ($month !== null && preg_match('/^\d{4}-\d{2}$/', $month) && $month <= now()->format('Y-m')) ? $month : '';
        $this->spAmount = '';
        $this->spDate   = now()->toDateString();
        $this->spFrom   = (string) (Auth::user()->name ?? '');
        $this->spRemark = '';
        $this->showSalaryPayPanel = true;
    }

    public function closeSalaryPayment(): void
    {
        $this->showSalaryPayPanel = false;
        $this->spMonth = '';
        $this->resetValidation(['spAmount', 'spDate', 'spFrom', 'spRemark']);
    }

    /**
     * Record a payment to the person the tab is on: an amount, the day it was
     * paid, who it came from and a remark. It is kept against the month of its
     * date — a person can be paid more than once in a month — and reaches the
     * Payments tab and the Ledger like every salary payment.
     */
    public function saveSalaryPayment(): void
    {
        $emp = $this->salaryPerson();
        if (!$emp) {
            $this->closeSalaryPayment();
            return;
        }

        $this->validate([
            'spAmount' => 'required|numeric|min:1|max:99999999',
            'spDate'   => 'required|date|before_or_equal:today',
            'spFrom'   => 'required|string|max:255',
            'spRemark' => 'nullable|string|max:500',
        ], [
            'spDate.before_or_equal' => 'The date cannot be after today.',
        ], [
            'spAmount' => 'amount',
            'spDate'   => 'date',
            'spFrom'   => 'from',
            'spRemark' => 'remark',
        ]);

        // A month's screen hands its own month; anything else is not one.
        if ($this->spMonth !== '' && (!preg_match('/^\d{4}-\d{2}$/', $this->spMonth) || $this->spMonth > now()->format('Y-m'))) {
            $this->spMonth = '';
        }

        AdminSalaryPayment::create([
            'admin_employee_id' => $emp->id,
            'organization_id'   => $this->orgId(),
            'month'             => $this->spMonth !== '' ? $this->spMonth : Carbon::parse($this->spDate)->format('Y-m'),
            'amount'            => $this->spAmount,
            'payment_mode'      => 'cash',
            'paid_by'           => $this->spFrom,
            'status'            => 'paid',
            'payment_date'      => $this->spDate,
            'remark'            => $this->spRemark !== '' ? $this->spRemark : null,
        ]);

        $this->closeSalaryPayment();
        $this->notification()->success('Payment added', '₹' . number_format((float) $this->spAmount, 0) . ' to ' . $emp->name . '.');
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

        // Auto-provision payroll rows for teachers/drivers, one row per person.
        if ($this->ensurePayrollEmployees()) {
            $this->mergeSamePersonRows();
        }

        // ── Employees — single query, reuse everywhere ─────────────────────────
        $allEmployees = AdminEmployee::forOrganization($orgId)->orderBy('name')->get();

        // Employees tab: search + type + sort
        $employeesList = $allEmployees
            ->when($this->empTypeFilter, fn($c) => $c->filter(fn($e) => $e->hasType($this->empTypeFilter)))
            ->when($this->empSearch, function ($c) {
                $t = mb_strtolower(trim($this->empSearch));
                return $c->filter(fn($e) => str_contains(mb_strtolower($e->name), $t)
                    || str_contains(mb_strtolower((string) $e->designation), $t)
                    || str_contains(mb_strtolower((string) $e->mobile), $t));
            });
        // Default order is management → teacher → driver → employee.
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
            $allEmployees->when($this->filterAttendanceType, fn($c) => $c->filter(fn($e) => $e->hasType($this->filterAttendanceType)))
        );

        // Marking list: teachers are never marked here — their attendance comes
        // from the Teacher Attendance module.
        $markEmployees = $attEmployees->where('type', '!=', 'teacher')->values();

        // The Mark Attendance panel: everyone, teachers included — teachers
        // first, then management, drivers and employees, A to Z within each.
        $markPeople = $this->showMarkPanel
            ? $allEmployees
                ->sortBy(fn ($e) => [self::MARK_ORDER[$e->type] ?? 9, mb_strtolower((string) $e->name)])
                ->values()
            : collect();

        // Add → Driver: the routes a driver can be put on, as Transport lists them.
        $driverRouteOptions = $this->showEmpModal && !$this->editEmpId && $this->empTypeChosen && $this->empType === 'driver'
            ? Transportation::where('organization_id', $orgId)
                ->orderBy('route_name')->orderBy('vehicle_type')
                ->get(['id', 'route_name', 'vehicle_type'])
            : collect();

        // Salary tab list: search + type, same order again
        $salaryEmployees = $this->sortByType(
            $allEmployees
                ->when($this->filterSalaryType, fn($c) => $c->filter(fn($e) => $e->hasType($this->filterSalaryType)))
                ->when($this->salarySearch, function ($c) {
                    $t = mb_strtolower(trim($this->salarySearch));
                    return $c->filter(fn($e) => str_contains(mb_strtolower($e->name), $t));
                })
        );

        $allEmployeesForFilter = $allEmployees;

        // Mark Salary tab: the people of the type picked, and the one picked's account.
        $salaryPeople  = $this->salaryType !== ''
            ? $allEmployees->filter(fn ($e) => $e->hasType($this->salaryType))->sortBy(fn ($e) => mb_strtolower((string) $e->name))->values()
            : collect();
        $salaryPerson  = $this->activeTab === 'salary' && $this->salaryEmpId !== ''
            ? $allEmployees->firstWhere('id', (int) $this->salaryEmpId)
            : null;
        $salaryAccount = $salaryPerson ? $this->salaryAccount($salaryPerson) : null;

        // Employees tab: this month's present / working days beside each person.
        $monthAttendance = $this->activeTab === 'employees' ? $this->monthAttendanceSummary($allEmployees) : [];

        // ── Stats (for employees tab header) ───────────────────────────────────
        // Someone with two types (teacher and driver) counts under both.
        $empStats = ['total' => $allEmployees->count()];
        foreach (['teacher', 'management', 'employee', 'driver'] as $t) {
            $empStats[$t] = $allEmployees->filter(fn($e) => $e->hasType($t))->count();
        }

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

        // The date view lists everyone that day, whatever type was last picked.
        $dayEmployees = $attView === 'date' ? $this->sortByType($allEmployees) : collect();
        $dayMarks     = $attView === 'date' ? $this->dayMarks($this->attendanceDate, $allEmployees) : [];

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
        // The people of the type picked on the Payments tab: the person dropdown's
        // options, and — until one of them is picked — whose payments are listed.
        $paymentPeople = $this->filterPaymentType !== ''
            ? $allEmployees->filter(fn ($e) => $e->hasType($this->filterPaymentType))->sortBy(fn ($e) => mb_strtolower((string) $e->name))->values()
            : collect();

        $payments = AdminSalaryPayment::forOrganization($orgId)
            ->with('employee')
            ->when($this->filterPaymentType !== '' && !$this->filterPaymentEmpId, fn ($q) => $q->whereIn('admin_employee_id', $paymentPeople->pluck('id')))
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
            'markPeople',
            'driverRouteOptions',
            'monthAttendance',
            'dayEmployees',
            'dayMarks',
            'salaryPeople',
            'salaryPerson',
            'salaryAccount',
            'paymentPeople',
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
