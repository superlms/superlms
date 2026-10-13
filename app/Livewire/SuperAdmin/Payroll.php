<?php

namespace App\Livewire\SuperAdmin;

use App\Models\SuperAdmin\SuperAdminAttendance;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use WireUi\Traits\WireUiActions;

class Payroll extends Component
{
    use WireUiActions, WithFileUploads;

    // ─── Employee Types (superadmin-specific) ─────────────────────────────────
    public const EMP_TYPES = ['user', 'counsellor', 'team', 'management', 'other'];

    /**
     * As the school Payroll: the month's first absent is a paid leave and
     * costs nothing; each absent after it cuts a day, a half day half of one.
     */
    public const PAID_LEAVES_A_MONTH = 1;

    /** What the Mark Attendance panel offers on every row (a saved Leave still shows). */
    private const STAFF_MARKS = ['present', 'absent', 'half_day', 'holiday'];

    // ─── Active Tab ───────────────────────────────────────────────────────────
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'employees')]
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

    // ─── Employee list filters ────────────────────────────────────────────────
    public string $empSearch     = '';
    public string $empTypeFilter = '';
    public string $empSort       = 'name_asc';

    // ─── Employee Delete Confirm ──────────────────────────────────────────────
    public $pendingDeleteEmpId = null;

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
    public array  $attendanceDraft      = []; // super_admin_employee_id => status
    public string $attendanceMode       = 'view'; // 'view' | 'mark'

    // ─── Mark Attendance panel (the school Payroll's): everyone at once ───────
    public bool   $showMarkPanel = false;
    public string $panelDate     = '';
    public array  $panelRows     = [];    // super_admin_employee_id => ['status', 'remark']
    public bool   $panelExisting = false; // this date already has marks → editing

    // ─── Salary ───────────────────────────────────────────────────────────────
    public string $salaryMonth      = '';
    public string $filterSalaryType = '';
    public string $salarySearch     = '';
    public bool   $showPayModal     = false;
    public        $payEmployeeId    = null;
    public        $payAmount        = '';
    public string $payMode          = 'cash';
    public string $payPaidBy        = '';
    public string $payDate          = '';
    public string $payTransactionId = '';
    public string $payRemark        = '';
    public        $payExistingId    = null;

    // ─── Mark Salary: one person's account (the school Payroll's) ─────────────
    // A type, then one of its people; the arrow on a month (or the month
    // filter) opens that month's own screen. Add Payment records one payment.
    public string $salaryType         = '';
    public string $salaryEmpId        = '';
    public string $salaryMonthView    = '';
    public bool   $showSalaryPayPanel = false;
    public        $spAmount           = '';
    public string $spDate             = '';
    public string $spFrom             = '';
    public string $spRemark           = '';
    public string $spMonth            = ''; // the month a payment from a month's screen is kept against

    // ─── Payments History ─────────────────────────────────────────────────────
    public string $filterPaymentType  = ''; // type → person → month, as the school Payroll
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
            'empMobile'      => 'nullable|regex:/^[6-9]\d{9}$/',
            'empType'        => 'required|in:' . implode(',', self::EMP_TYPES),
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
                Storage::disk('s3')->delete(
                    ltrim(parse_url($this->empExistingPhoto, PHP_URL_PATH), '/')
                );
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
        $this->selectedEmployee   = SuperAdminEmployee::find($id);
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
        ]);
        $this->empType = 'user';
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
        $this->closeMarkPanel();
        $this->showSalaryPayPanel = false;
        $this->salaryMonthView    = '';
    }

    // ─── Mark Attendance panel: everyone at once (the school Payroll's) ───────

    /**
     * Header "Mark Attendance" — always a fresh flow: the panel opens on today
     * and reads what is saved for it.
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

    /** What is saved for a day, keyed by employee id. */
    private function savedMarks(string $date)
    {
        return SuperAdminAttendance::whereDate('date', $date)->get()->keyBy('super_admin_employee_id');
    }

    /**
     * Load the panel's rows for $panelDate: a row already marked comes back
     * with its status and remark, so the day can be corrected and saved again;
     * every other row starts blank.
     */
    public function loadMarkPanel(): void
    {
        $saved = $this->savedMarks($this->panelDate);

        $this->panelRows     = [];
        $this->panelExisting = false;

        foreach (SuperAdminEmployee::all() as $emp) {
            $rec = $saved->get($emp->id);
            $this->panelRows[$emp->id] = ['status' => $rec ? (string) $rec->status : '', 'remark' => (string) ($rec->note ?? '')];
            $this->panelExisting = $this->panelExisting || (bool) $rec;
        }
    }

    /**
     * Save the panel against $panelDate, everyone in one go. A row left blank
     * is written as nothing at all — and an earlier mark for it is removed —
     * so the day stays open; a row still showing what is saved is left alone.
     */
    public function saveMarkPanel(): void
    {
        $date = $this->panelDate;

        $d = \DateTime::createFromFormat('!Y-m-d', (string) $date);
        if (!$d || $d->format('Y-m-d') !== $date || $date > now()->toDateString()) {
            $this->notification()->error('Pick a valid date', 'Attendance can be marked for today or an earlier day.');
            return;
        }

        $marked = count(array_filter($this->panelRows, fn ($r) => ($r['status'] ?? '') !== ''));
        if ($marked === 0) {
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

        // The statuses are set in the browser: only real employees, and only
        // what the panel offers.
        $employees = SuperAdminEmployee::whereIn('id', array_keys($this->panelRows))->get()->keyBy('id');
        $saved     = $this->savedMarks($date);
        $wasEdit   = $saved->isNotEmpty();
        $changed   = 0;

        try {
            DB::transaction(function () use ($date, $employees, $saved, &$changed) {
                foreach ($this->panelRows as $empId => $row) {
                    $emp = $employees->get($empId);
                    if (!$emp) {
                        continue;
                    }
                    $status = (string) ($row['status'] ?? '');
                    $remark = trim((string) ($row['remark'] ?? ''));
                    $rec    = $saved->get($emp->id);

                    if ($status === '') {
                        if ($rec) {
                            $rec->delete();
                            $changed++;
                        }
                        continue;
                    }
                    if (!in_array($status, self::STAFF_MARKS, true)) {
                        continue;
                    }
                    if ($rec && (string) $rec->status === $status && (string) $rec->note === $remark) {
                        continue;
                    }

                    $values = ['status' => $status, 'note' => $remark !== '' ? $remark : null];
                    $rec
                        ? $rec->update($values)
                        : SuperAdminAttendance::create($values + ['super_admin_employee_id' => $emp->id, 'date' => $date]);
                    $changed++;
                }
            });
        } catch (\Throwable $e) {
            logger()->error('Super Admin payroll attendance save error: ' . $e->getMessage());
            $this->notification()->error('Could not save', 'The attendance was not saved. Please try again.');
            return;
        }

        $dateLabel = Carbon::parse($date)->format('d M Y');

        // One summary notification per save, as before — a per-row model hook
        // would flood the bell when a whole day is marked at once.
        if ($changed > 0) {
            \App\Services\ActivityNotifier::toSuperAdmins(
                'Payroll attendance marked',
                "Attendance marked for {$marked} employee(s) for {$dateLabel}.",
                ['type' => 'payroll_attendance', 'date' => $date]
            );
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
            'Attendance ' . ($wasEdit ? 'updated' : 'saved') . ' for ' . $dateLabel . '.'
        );
    }

    /**
     * The Employees list's attendance column: for the running month, the days
     * each person was present out of their working days — the days they were
     * marked on, holidays left out; a half day counts as half.
     *
     * @return array<int, array{present: float, working: int, absent: int, half: int, leave: int}>
     */
    private function monthAttendanceSummary($employees): array
    {
        $byEmp = SuperAdminAttendance::forMonth(now()->format('Y-m'))->get()->groupBy('super_admin_employee_id');

        $out = [];
        foreach ($employees as $emp) {
            $n = $byEmp->get($emp->id, collect())->map(fn ($r) => (string) $r->status)->countBy();

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
        $saved = $this->savedMarks($date);

        $out = [];
        foreach ($employees as $emp) {
            $rec = $saved->get($emp->id);
            $out[$emp->id] = ['status' => $rec ? (string) $rec->status : null, 'remark' => (string) ($rec->note ?? '')];
        }

        return $out;
    }

    /** Picking a type starts over on that type's people (and leaves the date view). */
    public function updatedFilterAttendanceType(): void
    {
        $this->attEmpId = '';
        if ($this->filterAttendanceType !== '') {
            $this->attendanceDate = '';
        }
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

    /** Clear the draft when the date changes; picking a date switches to date-mode: everyone, that day. */
    public function updatedAttendanceDate(): void
    {
        $this->attendanceDraft = [];
        if ($this->attendanceDate !== '') {
            $this->attEmpId             = '';
            $this->filterAttendanceType = '';
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

    /** Persist all drafted attendance, then return to the view screen. */
    public function submitAttendance(): void
    {
        $count = 0;

        foreach ($this->attendanceDraft as $empId => $status) {
            $emp = SuperAdminEmployee::find($empId);
            if (!$emp) continue;

            SuperAdminAttendance::updateOrCreate(
                ['super_admin_employee_id' => $empId, 'date' => $this->attendanceDate],
                ['status' => $status]
            );
            $count++;
        }

        if ($count === 0) {
            $this->notification()->error('Nothing to submit — pick a status for at least one employee.');
            return;
        }

        $this->attendanceDraft = [];
        $this->attendanceMode  = 'view';

        $dateLabel = Carbon::parse($this->attendanceDate)->format('d M Y');

        // One summary notification per submit — a per-row model hook would
        // flood the bell when a full day's attendance is marked at once.
        \App\Services\ActivityNotifier::toSuperAdmins(
            'Payroll attendance marked',
            "Attendance marked for {$count} employee(s) for {$dateLabel}.",
            ['type' => 'payroll_attendance', 'date' => $this->attendanceDate]
        );

        $this->notification()->success('Attendance marked successfully', "{$count} employee(s) updated for {$dateLabel}.");
    }

    /** Saved status for an employee on the current date. */
    public function getAttendanceStatus($empId): ?string
    {
        return SuperAdminAttendance::where('super_admin_employee_id', $empId)
            ->where('date', $this->attendanceDate)
            ->value('status');
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
    private function buildEmployeeDays(SuperAdminEmployee $emp): array
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

        $counts  = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'holiday' => 0, 'marked' => 0];
        $byMonth = [];
        if ($start->gt($end)) {
            return ['counts' => $counts, 'byMonth' => $byMonth];
        }

        // Load the period's records once, keyed by Y-m-d.
        $map = [];
        SuperAdminAttendance::where('super_admin_employee_id', $emp->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->each(function ($r) use (&$map) {
                $map[Carbon::parse($r->date)->format('Y-m-d')] = $r->status;
            });

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

    /**
     * The school Payroll's view of one person's attendance: for the active
     * period (a single month if attMonth is set, otherwise the whole attYear up
     * to today) a real calendar per month — seven columns from Sunday, every
     * day of the month, days outside the period blank — with its own counts.
     * Days with no record read as "holiday".
     */
    private function buildEmployeeMonths(SuperAdminEmployee $emp): array
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

        $blank  = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'holiday' => 0, 'marked' => 0];
        $counts = $blank;
        $months = [];
        if ($start->gt($end)) {
            return ['counts' => $counts, 'months' => $months];
        }

        // Load the period's records once, keyed by Y-m-d.
        $map = [];
        SuperAdminAttendance::where('super_admin_employee_id', $emp->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->get()->each(function ($r) use (&$map) {
                $map[Carbon::parse($r->date)->format('Y-m-d')] = $r->status;
            });

        $cursor = $start->copy()->startOfMonth();
        $last   = $end->copy()->startOfMonth();

        while ($cursor->lte($last)) {
            $ym          = $cursor->format('Y-m');
            $monthStart  = $cursor->copy()->startOfMonth();
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
                    'dim'       => $this->attStatus !== '' && $status !== $this->attStatus,
                ];
            }

            $months[$ym] = [
                'label'  => $monthStart->format('F Y'),
                'lead'   => (int) $monthStart->dayOfWeek,
                'cells'  => $cells,
                'counts' => $mCounts,
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
     * Attendance-based payable for an employee in the selected salary month
     * (or the month named), as the school Payroll works it out. Present /
     * leave / holiday / unmarked days are paid in full. The month's first
     * absent is a paid leave and costs nothing; each absent after it is a full
     * per-day cut (eight absents cut seven days), and each half day a half cut.
     */
    private function salaryBreakdown(SuperAdminEmployee $emp, $grouped, ?string $month = null): array
    {
        $base        = (float) $emp->salary;
        $daysInMonth = (int) Carbon::parse(($month ?: $this->salaryMonth) . '-01')->daysInMonth;

        $records = $grouped->get($emp->id, collect());
        $present = $records->where('status', 'present')->count();
        $absent  = $records->where('status', 'absent')->count();
        $halfDay = $records->where('status', 'half_day')->count();
        $leave   = $records->where('status', 'leave')->count();

        $perDay    = $daysInMonth > 0 ? $base / $daysInMonth : 0;
        $paidLeave = min($absent, self::PAID_LEAVES_A_MONTH);
        $cutDays   = ($absent - $paidLeave) + 0.5 * $halfDay;
        $deduction = $cutDays * $perDay;
        $payable   = max(0, round($base - $deduction));

        return compact('present', 'absent', 'halfDay', 'leave', 'payable', 'paidLeave', 'cutDays')
            + ['base' => $base, 'perDay' => $perDay, 'deduction' => max(0, $base - $payable)];
    }

    // ─── Mark Salary: one person's account (the school Payroll's) ─────────────

    /** Another type starts over on its people. */
    public function updatedSalaryType(): void
    {
        $this->salaryEmpId     = '';
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
        $this->salaryType      = '';
        $this->salaryEmpId     = '';
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

    /** The person the Mark Salary tab is on, if one is picked. */
    private function salaryPerson(): ?SuperAdminEmployee
    {
        return $this->salaryEmpId !== '' ? SuperAdminEmployee::find((int) $this->salaryEmpId) : null;
    }

    /**
     * One person's salary account: every month from 1 April of the running
     * session (or the month they joined, when that is later) to this month —
     * the attendance marked in it, the salary it works out to, and what was
     * paid against it — with the totals. The running month is worked out on
     * what is marked so far.
     */
    private function salaryAccount(SuperAdminEmployee $emp): array
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
        $attendance = SuperAdminAttendance::where('super_admin_employee_id', $emp->id)
            ->whereDate('date', '>=', $start->toDateString())
            ->get()->groupBy(fn ($r) => Carbon::parse($r->date)->format('Y-m'));

        // What was paid, by the month it is recorded against.
        $payments = SuperAdminSalaryPayment::where('super_admin_employee_id', $emp->id)
            ->where('status', 'paid')
            ->orderBy('payment_date')->orderBy('id')
            ->get()->groupBy('month');

        $months = [];
        $totalPayable = $totalPaid = 0.0;
        for ($m = $start->copy(); $m->lte($now); $m->addMonthNoOverflow()) {
            $ym   = $m->format('Y-m');
            $b    = $this->salaryBreakdown($emp, collect([$emp->id => $attendance->get($ym, collect())]), $ym);
            $paid = $payments->get($ym, collect());

            $months[] = [
                'ym'         => $ym,
                'label'      => $m->format('F Y'),
                'running'    => $m->equalTo($now),
                'base'       => $b['base'],
                'present'    => $b['present'],
                'absent'     => $b['absent'],
                'half'       => $b['halfDay'],
                'leave'      => $b['leave'],
                'payable'    => (float) $b['payable'],
                'paid_leave' => $b['paidLeave'],
                'cut_days'   => $b['cutDays'],
                'per_day'    => (float) $b['perDay'],
                'deduction'  => (float) $b['deduction'],
                'paid'       => (float) $paid->sum('amount'),
                'payments'   => $paid->map(fn ($p) => [
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
     * Add Payment (page header, on a month's screen): a blank form on today,
     * from the person signed in, kept against that month.
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
     * paid, who it came from and a remark. Each one is its own row — a person
     * can be paid more than once in a month — and reaches the Payments tab.
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

        SuperAdminSalaryPayment::create([
            'super_admin_employee_id' => $emp->id,
            'month'                   => $this->spMonth !== '' ? $this->spMonth : Carbon::parse($this->spDate)->format('Y-m'),
            'amount'                  => $this->spAmount,
            'payment_mode'            => 'cash',
            'paid_by'                 => $this->spFrom,
            'status'                  => 'paid',
            'payment_date'            => $this->spDate,
            'remark'                  => $this->spRemark !== '' ? $this->spRemark : null,
        ]);

        $amount = (float) $this->spAmount;
        $this->closeSalaryPayment();
        $this->notification()->success('Payment added', '₹' . number_format($amount, 0) . ' to ' . $emp->name . '.');
    }

    public function openPayModal($empId): void
    {
        if (!$this->canPayMonth()) {
            $this->notification()->error('Salary for ' . Carbon::parse($this->salaryMonth . '-01')->format('M Y') . ' can be paid only after the month ends.');
            return;
        }

        $emp = SuperAdminEmployee::find($empId);
        if (!$emp) return;

        // Attendance-based payable for this month.
        $grouped = SuperAdminAttendance::forMonth($this->salaryMonth)
            ->where('super_admin_employee_id', $empId)
            ->get()->groupBy('super_admin_employee_id');
        $breakdown = $this->salaryBreakdown($emp, $grouped);

        $existing = SuperAdminSalaryPayment::where('super_admin_employee_id', $empId)
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
        SuperAdminSalaryPayment::updateOrCreate(
            [
                'super_admin_employee_id' => $this->payEmployeeId,
                'month'                   => $this->salaryMonth,
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
        // ── Employees — single query, reuse everywhere ─────────────────────────
        $allEmployees = SuperAdminEmployee::orderBy('name')->get();

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
        $empStats = ['total' => $allEmployees->count()];
        foreach (self::EMP_TYPES as $type) {
            $empStats[$type] = $allEmployees->where('type', $type)->count();
        }

        // ── Attendance view (only renders once a filter is chosen) ─────────────
        //   attView === 'date'     → everyone's status on attendanceDate
        //   attView === 'employee' → the picked employee's month / whole year
        $attView        = null;
        $attEmp         = null;
        $attByMonth     = [];
        $attMonths      = [];
        $attCounts      = [];
        $attPeriodLabel = '';

        if ($this->attendanceMode === 'view') {
            if ($this->attEmpId) {
                $attEmp = $allEmployees->firstWhere('id', (int) $this->attEmpId);
                if ($attEmp) {
                    $attView    = 'employee';
                    // The school Payroll's month calendars (buildEmployeeDays'
                    // day list is kept, no longer shown).
                    $built      = $this->buildEmployeeMonths($attEmp);
                    $attMonths  = $built['months'];
                    $attCounts  = $built['counts'];
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
        $salaryGrouped = SuperAdminAttendance::forMonth($this->salaryMonth)
            ->get()->groupBy('super_admin_employee_id');

        $salaryBreakdowns = [];
        foreach ($salaryEmployees as $emp) {
            $salaryBreakdowns[$emp->id] = $this->salaryBreakdown($emp, $salaryGrouped);
        }

        $monthSalaryPayments = SuperAdminSalaryPayment::forMonth($this->salaryMonth)
            ->get()->keyBy('super_admin_employee_id');

        $canPaySalaryMonth = $this->canPayMonth();
        $totalPayable      = collect($salaryBreakdowns)->sum('payable');
        $totalPaidAmount   = (float) ($monthSalaryPayments->where('status', 'paid')->sum('amount'));

        // ── As the school Payroll: type order, then A to Z ─────────────────────
        $typeRank       = array_flip(self::EMP_TYPES);
        $byTypeThenName = fn ($c) => $c->sortBy(fn ($e) => [$typeRank[$e->type] ?? 99, mb_strtolower((string) $e->name)])->values();
        $byName         = fn ($c) => $c->sortBy(fn ($e) => mb_strtolower((string) $e->name))->values();

        // Employees tab: this month's present / working days beside each person.
        $monthAttendance = $this->activeTab === 'employees' ? $this->monthAttendanceSummary($allEmployees) : [];

        // The Mark Attendance panel: everyone at once.
        $markPeople = $this->showMarkPanel ? $byTypeThenName($allEmployees) : collect();

        // The date view lists everyone that day, whatever type was last picked.
        $dayEmployees = $attView === 'date' ? $byTypeThenName($allEmployees) : collect();
        $dayMarks     = $attView === 'date' ? $this->dayMarks($this->attendanceDate, $allEmployees) : [];

        // Mark Salary tab: the people of the type picked, and the one picked's account.
        $salaryPeople  = $this->salaryType !== '' ? $byName($allEmployees->where('type', $this->salaryType)) : collect();
        $salaryPerson  = $this->activeTab === 'salary' && $this->salaryEmpId !== ''
            ? $allEmployees->firstWhere('id', (int) $this->salaryEmpId)
            : null;
        $salaryAccount = $salaryPerson ? $this->salaryAccount($salaryPerson) : null;

        // Payments tab: the people of the type picked — the person dropdown's
        // options and, until one of them is picked, whose payments are listed.
        $paymentPeople = $this->filterPaymentType !== '' ? $byName($allEmployees->where('type', $this->filterPaymentType)) : collect();

        // ── Payments History ──────────────────────────────────────────────────
        $payments = SuperAdminSalaryPayment::with('employee')
            ->when($this->filterPaymentType !== '' && !$this->filterPaymentEmpId, fn ($q) => $q->whereIn('super_admin_employee_id', $paymentPeople->pluck('id')))
            ->when($this->filterPaymentEmpId, fn($q) => $q->where('super_admin_employee_id', $this->filterPaymentEmpId))
            ->when($this->filterPaymentMonth,  fn($q) => $q->forMonth($this->filterPaymentMonth))
            ->latest()->get()
            ->when($this->paymentSearch, function ($c) {
                $t = mb_strtolower(trim($this->paymentSearch));
                return $c->filter(fn($p) => str_contains(mb_strtolower((string) $p->employee?->name), $t));
            })->values();

        return view('livewire.super-admin.payroll', compact(
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
            'attMonths',
            'monthAttendance',
            'markPeople',
            'dayEmployees',
            'dayMarks',
            'salaryPeople',
            'salaryPerson',
            'salaryAccount',
            'paymentPeople',
        ));
    }
}
