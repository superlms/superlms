<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\v1\Concerns\RunsPanelActions;
use App\Livewire\Admin\Payroll as PayrollPage;
use App\Models\Admin\AdminAttendance;
use App\Models\Admin\AdminEmployee;
use App\Models\Admin\AdminSalaryPayment;
use App\Models\Admin\Transportation;
use App\Models\Teacher\TeacherAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * The admin app's Payroll — app/Livewire/Admin/Payroll.php over the API.
 *
 * Every rule is the panel's own: this runs the panel component's methods
 * (the salary breakdown, the attendance calendars, the rows made for every
 * teacher and driver, one row per person) rather than a copy of them, so the
 * app, the admin panel and the accounts panel can never disagree.
 *
 *   Employees   list (search, type, sort), detail, add / edit / delete
 *   Attendance  a date's statuses, one employee's month or year, marking a
 *               date for everyone but teachers (theirs is the Teacher module's)
 *   Salary      a month's payable per employee, paying it (after the month ends)
 *   Payments    the salary history
 */
class AdminPayrollController extends ApiController
{
    use RunsPanelActions;

    private const ADMIN_ROLES = ['admin', 'sub-admin'];
    private const TYPES = ['teacher', 'management', 'employee', 'driver'];
    private const MARK_STATUSES = ['present', 'absent', 'half_day', 'leave'];
    private const PAY_MODES = ['cash', 'online', 'bank_transfer', 'cheque'];

    private function guard(): array
    {
        [$user, $err] = $this->authUser();
        if ($err) return [null, $err];
        if ($err = $this->requireRole(self::ADMIN_ROLES)) return [null, $err];
        if (!$user->organization_id) {
            return [null, $this->error('No organization assigned to this account.', 403)];
        }
        return [$user, null];
    }

    /** The panel's component, with the filters it reads set. */
    private function page(array $props = []): PayrollPage
    {
        $page = new PayrollPage();
        foreach ($props as $k => $v) {
            $page->{$k} = $v;
        }
        return $page;
    }

    /** Run one of the panel component's own (private) methods. */
    private function run(PayrollPage $page, string $method, ...$args)
    {
        return (fn () => $this->{$method}(...$args))->call($page);
    }

    /** As the panel does on opening: every teacher and driver has a row, one per person. */
    private function provision(): void
    {
        $page = $this->page();
        $this->run($page, 'ensurePayrollEmployees');
        $this->run($page, 'mergeSamePersonRows');
    }

    private function orderByType($employees)
    {
        return $employees
            ->sortBy(fn ($e) => [PayrollPage::TYPE_ORDER[$e->type] ?? 9, mb_strtolower((string) $e->name)])
            ->values();
    }

    private function shape(AdminEmployee $e): array
    {
        return [
            'id'                => $e->id,
            'name'              => $e->name,
            'designation'       => $e->designation,
            'type'              => $e->type,
            'types'             => $e->types(),
            'mobile'            => $e->mobile,
            'email'             => $e->email,
            'salary'            => (float) $e->salary,
            'photo'             => $e->photo,
            // The model keeps joining_date uncast, so it comes back as the
            // column's string; read it as a date whichever way it arrives.
            'joining_date'      => $e->joining_date ? Carbon::parse($e->joining_date)->format('Y-m-d') : null,
            'is_teacher'        => $e->isTeacher(),
            'teacher_detail_id' => $e->teacher_detail_id,
            'driver_detail_id'  => $e->driver_detail_id ?? null,
        ];
    }

    // ══════════════════════════ EMPLOYEES ══════════════════════════

    /** GET /admin/payroll/employees?search=&type=&sort=type_order|name_asc|name_desc|salary_asc|salary_desc */
    public function employees(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $this->provision();

        $all = AdminEmployee::forOrganization($orgId)->orderBy('name')->get();

        $list = $all
            ->when($request->filled('type'), fn ($c) => $c->filter(fn ($e) => $e->hasType($request->type)))
            ->when($request->filled('search'), function ($c) use ($request) {
                $t = mb_strtolower(trim($request->search));
                return $c->filter(fn ($e) => str_contains(mb_strtolower($e->name), $t)
                    || str_contains(mb_strtolower((string) $e->designation), $t)
                    || str_contains(mb_strtolower((string) $e->mobile), $t));
            });
        $list = match ($request->input('sort', 'type_order')) {
            'name_asc'    => $list->sortBy('name')->values(),
            'name_desc'   => $list->sortByDesc('name')->values(),
            'salary_asc'  => $list->sortBy('salary')->values(),
            'salary_desc' => $list->sortByDesc('salary')->values(),
            default       => $this->orderByType($list),
        };

        // Someone with two types (teacher and driver) counts under both.
        $stats = ['total' => $all->count()];
        foreach (self::TYPES as $t) {
            $stats[$t] = $all->filter(fn ($e) => $e->hasType($t))->count();
        }

        // This month's present days out of working days, as the panel's list shows.
        $att = $this->run($this->page(), 'monthAttendanceSummary', $all);

        return $this->success([
            'employees' => $list->map(fn ($e) => $this->shape($e) + ['month_attendance' => $att[$e->id] ?? null])->values(),
            'stats'     => $stats,
            'month'     => now()->format('M'),
        ], 'Employees fetched.');
    }

    /** GET /admin/payroll/employees/{id} — the panel's detail panel. */
    public function employee($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $e = AdminEmployee::with(['teacherDetail.user', 'driverDetail.user'])
            ->forOrganization($user->organization_id)->find($id);
        if (!$e) return $this->error('Employee not found.', 404);

        return $this->success($this->shape($e) + [
            'address'          => $e->address,
            'bank_name'        => $e->bank_name,
            'bank_account_no'  => $e->bank_account_no,
            'bank_holder_name' => $e->bank_holder_name,
            'bank_branch'      => $e->bank_branch,
            'bank_ifsc'        => $e->bank_ifsc,
            'linked_teacher'   => $e->teacher_detail_id ? ($e->teacherDetail?->user?->name ?? ('Teacher #' . $e->teacher_detail_id)) : null,
            'linked_driver'    => ($e->driver_detail_id ?? null) ? ($e->driverDetail?->user?->name ?? ('Driver #' . $e->driver_detail_id)) : null,
        ], 'Employee fetched.');
    }

    /** POST /admin/payroll/employees (multipart) and POST /admin/payroll/employees/{id} */
    public function saveEmployee(Request $request, $id = null)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $editing = $id ? AdminEmployee::forOrganization($orgId)->find($id) : null;
        if ($id && !$editing) return $this->error('Employee not found.', 404);

        if ($err = $this->validateWith($request, [
            'name'             => 'required|string|max:255',
            'email'            => 'nullable|email|max:255',
            'mobile'           => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'type'             => 'required|in:teacher,management,employee,driver',
            'salary'           => 'required|numeric|min:0|max:99999999',
            'designation'      => 'nullable|string|max:100',
            'address'          => 'nullable|string|max:500',
            'bank_name'        => 'nullable|string|max:100',
            'bank_holder_name' => 'nullable|string|max:100',
            'bank_account_no'  => ['nullable', 'regex:/^\d{6,20}$/'],
            'bank_branch'      => 'nullable|string|max:100',
            'bank_ifsc'        => ['nullable', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'joining_date'     => 'nullable|date',
            'photo'            => 'nullable|image|max:1024',
        ], [
            'mobile.regex'          => 'Enter a valid 10-digit mobile number.',
            'bank_account_no.regex' => 'Account number must be 6–20 digits.',
            'bank_ifsc.regex'       => 'Enter a valid IFSC code (e.g. HDFC0001234).',
            'photo.max'             => 'Photo must be 1 MB or smaller.',
        ])) return $err;

        $type = $request->type;
        $data = [
            'organization_id'   => $orgId,
            'teacher_detail_id' => $type === 'teacher' ? ($editing?->teacher_detail_id ?: null) : null,
            'name'              => $request->name,
            'email'             => $request->email ?: null,
            'mobile'            => $request->mobile,
            'designation'       => $request->designation,
            'type'              => $type,
            'salary'            => $request->salary,
            'address'           => $request->address,
            'bank_name'         => $request->bank_name,
            'bank_account_no'   => $request->bank_account_no,
            'bank_holder_name'  => $request->bank_holder_name,
            'bank_branch'       => $request->bank_branch,
            'bank_ifsc'         => $request->bank_ifsc ? strtoupper($request->bank_ifsc) : null,
            'joining_date'      => $request->joining_date ?: null,
        ];

        // The panel's same-person check: no second row for someone a driver row covers.
        $page = $this->page(['editEmpId' => $editing?->id]);
        if ($message = $this->run($page, 'samePersonConflict', $data)) {
            return $this->error($message, 422);
        }

        if ($request->hasFile('photo')) {
            if ($editing?->photo) {
                Storage::disk('s3')->delete(ltrim((string) parse_url($editing->photo, PHP_URL_PATH), '/'));
            }
            $path = $request->file('photo')->store('admin/payroll/photos', 's3');
            Storage::disk('s3')->setVisibility($path, 'public');
            $data['photo'] = Storage::disk('s3')->url($path);
        } elseif ($editing?->photo) {
            $data['photo'] = $editing->photo;
        }

        $editing ? $editing->update($data) : AdminEmployee::create($data);

        // Someone already listed as a Transport driver joins that row.
        $joined = $this->run($this->page(), 'mergeSamePersonRows') > 0;

        return $this->success(
            ['joined_driver' => $joined],
            ($editing ? 'Employee updated!' : 'Employee added!') . ($joined ? ' Also a driver in Transport, so listed once with both types.' : '')
        );
    }

    /** DELETE /admin/payroll/employees/{id} */
    public function deleteEmployee($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $e = AdminEmployee::forOrganization($user->organization_id)->find($id);
        if (!$e) return $this->error('Employee not found.', 404);
        $e->delete();

        return $this->success(null, 'Employee deleted!');
    }

    // ══════════════════════════ ATTENDANCE ══════════════════════════

    /**
     * GET /admin/payroll/attendance/date?date=YYYY-MM-DD&type=&status=
     * Everyone's status on a date (a teacher's from the Teacher module), and
     * whether they can be marked here.
     */
    public function attendanceDate(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $date = $request->filled('date') ? Carbon::parse($request->date)->toDateString() : now()->toDateString();
        $this->provision();

        $employees = $this->orderByType(
            AdminEmployee::forOrganization($orgId)->orderBy('name')->get()
                ->when($request->filled('type'), fn ($c) => $c->filter(fn ($e) => $e->hasType($request->type)))
        );

        $counts = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'leave' => 0, 'not_marked' => 0];
        $rows = $employees->map(function ($e) use ($date, &$counts) {
            $status = $e->getAttendanceStatusForDate($date);
            $counts[$status && isset($counts[$status]) ? $status : 'not_marked']++;
            return $this->shape($e) + ['status' => $status, 'markable' => !$e->isTeacher()];
        })->when($request->filled('status'), fn ($c) => $c->filter(fn ($r) => $r['status'] === $request->status))->values();

        return $this->success([
            'date'      => $date,
            'employees' => $rows,
            'counts'    => $counts,
            'marked'    => AdminAttendance::forOrganization($orgId)->where('date', $date)->exists(),
            'statuses'  => self::MARK_STATUSES,
        ], 'Attendance fetched.');
    }

    /**
     * GET /admin/payroll/attendance/employee/{id}?month=YYYY-MM | ?year=YYYY &status=
     * One employee's month, or academic year (April → March, up to today), as
     * the panel's calendars: each month's counts, present-%, leading blanks and days.
     */
    public function attendanceEmployee(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $e = AdminEmployee::forOrganization($user->organization_id)->find($id);
        if (!$e) return $this->error('Employee not found.', 404);

        $year  = (string) ($request->input('year') ?: (now()->month >= 4 ? now()->year : now()->year - 1));
        $month = (string) ($request->input('month') ?: '');

        $built = $this->run($this->page([
            'attMonth'  => $month,
            'attYear'   => $year,
            'attStatus' => (string) ($request->input('status') ?: ''),
        ]), 'buildEmployeeDays', $e);

        $label = $month
            ? Carbon::parse($month . '-01')->format('F Y')
            : Carbon::create((int) $year, 4, 1)->format('M Y') . ' – ' . Carbon::create((int) $year + 1, 3, 1)->format('M Y');

        return $this->success([
            'employee' => $this->shape($e),
            'period'   => $label,
            'counts'   => $built['counts'],
            'months'   => array_values(array_map(fn ($m, $key) => $m + ['key' => $key], $built['months'], array_keys($built['months']))),
        ], 'Attendance fetched.');
    }

    /**
     * POST /admin/payroll/attendance {date, marks:[{id, status}]}
     * The panel's Submit: each non-teacher's status for the date, overwriting
     * a day already marked (keyed on employee + date).
     */
    public function markAttendance(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        if ($err = $this->validateWith($request, [
            'date'           => 'required|date|before_or_equal:today',
            'marks'          => 'array',
            'marks.*.id'     => 'required|integer',
            'marks.*.status' => 'required|in:' . implode(',', self::MARK_STATUSES),
        ])) return $err;

        $date  = Carbon::parse($request->date)->toDateString();
        $count = 0;

        foreach ((array) $request->input('marks', []) as $m) {
            $emp = AdminEmployee::forOrganization($orgId)->find($m['id']);
            // Teachers are marked from the Teacher Attendance module — never here.
            if (!$emp || $emp->isTeacher()) continue;

            AdminAttendance::updateOrCreate(
                ['admin_employee_id' => $emp->id, 'date' => $date],
                ['organization_id' => $orgId, 'status' => $m['status']]
            );
            $count++;
        }

        $already = AdminAttendance::forOrganization($orgId)->where('date', $date)->exists();
        if ($count === 0 && !$already) {
            return $this->error('Nothing to submit — pick a status for at least one employee.', 422);
        }

        return $this->success(
            ['updated' => $count, 'date' => $date],
            $count === 0
                ? 'Nothing changed — showing the attendance already saved for ' . Carbon::parse($date)->format('d M Y') . '.'
                : "{$count} employee(s) updated for " . Carbon::parse($date)->format('d M Y') . '.'
        );
    }

    // ══════════════════════════ SALARY ══════════════════════════

    /** The month's attendance, grouped as the panel's salaryBreakdown() reads it. */
    private function monthAttendance(int $orgId, string $month, $employees): array
    {
        $admin = AdminAttendance::forOrganization($orgId)->forMonth($month)->get()->groupBy('admin_employee_id');

        $teacher = [];
        $map = $employees->whereNotNull('teacher_detail_id')->pluck('id', 'teacher_detail_id');
        $ids = $employees->where('type', 'teacher')->whereNotNull('teacher_detail_id')->pluck('teacher_detail_id');
        if ($ids->isNotEmpty()) {
            TeacherAttendance::whereIn('teacher_detail_id', $ids)
                ->whereRaw("DATE_FORMAT(attendance_date, '%Y-%m') = ?", [$month])
                ->get()->groupBy('teacher_detail_id')
                ->each(function ($records, $tdId) use (&$teacher, $map) {
                    if ($empId = $map->get($tdId)) $teacher[$empId] = $records;
                });
        }

        return [$admin, $teacher];
    }

    private function month(Request $request): string
    {
        $m = (string) $request->input('month', '');
        // Default: the previous month — the payable, fully-attended one.
        return preg_match('/^\d{4}-\d{2}$/', $m) ? $m : now()->subMonthNoOverflow()->format('Y-m');
    }

    private function shapePayment(?AdminSalaryPayment $p): ?array
    {
        return $p ? [
            'id'             => $p->id,
            'amount'         => (float) $p->amount,
            'mode'           => $p->payment_mode,
            'paid_by'        => $p->paid_by,
            'status'         => $p->status,
            'date'           => $p->payment_date?->format('Y-m-d'),
            'transaction_id' => $p->transaction_id,
            'remark'         => $p->remark,
            'month'          => $p->month,
        ] : null;
    }

    /** GET /admin/payroll/salary?month=YYYY-MM&type=&search= */
    public function salary(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;
        $month = $this->month($request);

        $this->provision();

        $all = AdminEmployee::forOrganization($orgId)->orderBy('name')->get();
        $employees = $this->orderByType(
            $all->when($request->filled('type'), fn ($c) => $c->filter(fn ($e) => $e->hasType($request->type)))
                ->when($request->filled('search'), function ($c) use ($request) {
                    $t = mb_strtolower(trim($request->search));
                    return $c->filter(fn ($e) => str_contains(mb_strtolower($e->name), $t));
                })
        );

        [$admin, $teacher] = $this->monthAttendance($orgId, $month, $all);
        $page = $this->page(['salaryMonth' => $month]);
        $payments = AdminSalaryPayment::forOrganization($orgId)->forMonth($month)->get()->keyBy('admin_employee_id');

        $rows = $employees->map(function ($e) use ($page, $admin, $teacher, $payments) {
            $b = $this->run($page, 'salaryBreakdown', $e, $admin, $teacher);
            return $this->shape($e) + [
                'breakdown' => [
                    'base'     => (float) $b['base'],
                    'present'  => $b['present'],
                    'absent'   => $b['absent'],
                    'half_day' => $b['halfDay'],
                    'leave'    => $b['leave'],
                    'payable'  => (float) $b['payable'],
                ],
                'payment' => $this->shapePayment($payments->get($e->id)),
            ];
        })->values();

        return $this->success([
            'month'       => $month,
            'month_label' => Carbon::parse($month . '-01')->format('F Y'),
            'can_pay'     => $this->run($page, 'canPayMonth'),
            'employees'   => $rows,
            'totals'      => [
                'payable' => (float) $rows->sum(fn ($r) => $r['breakdown']['payable']),
                'paid'    => (float) $payments->where('status', 'paid')->sum('amount'),
            ],
            'pay_modes'   => self::PAY_MODES,
        ], 'Salary fetched.');
    }

    /** GET /admin/payroll/salary/{empId}?month= — what the pay panel opens with. */
    public function salaryFor(Request $request, $empId)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;
        $month = $this->month($request);

        $e = AdminEmployee::forOrganization($orgId)->find($empId);
        if (!$e) return $this->error('Employee not found.', 404);

        [$admin, $teacher] = $this->monthAttendance($orgId, $month, collect([$e]));
        $page = $this->page(['salaryMonth' => $month]);
        $b = $this->run($page, 'salaryBreakdown', $e, $admin, $teacher);

        $existing = AdminSalaryPayment::where('admin_employee_id', $e->id)
            ->where('organization_id', $orgId)->where('month', $month)->first();

        return $this->success([
            'employee'  => $this->shape($e),
            'month'     => $month,
            'can_pay'   => $this->run($page, 'canPayMonth'),
            'breakdown' => [
                'base' => (float) $b['base'], 'present' => $b['present'], 'absent' => $b['absent'],
                'half_day' => $b['halfDay'], 'leave' => $b['leave'], 'payable' => (float) $b['payable'],
            ],
            'form'      => [
                'amount'         => (float) ($existing?->amount ?? $b['payable']),
                'mode'           => $existing?->payment_mode ?? 'cash',
                'paid_by'        => $existing?->paid_by ?? ($user->name ?? ''),
                'date'           => $existing?->payment_date?->format('Y-m-d') ?? now()->format('Y-m-d'),
                'transaction_id' => $existing?->transaction_id ?? '',
                'remark'         => $existing?->remark ?? '',
            ],
            'existing'  => $this->shapePayment($existing),
            'pay_modes' => self::PAY_MODES,
        ], 'Salary fetched.');
    }

    /** POST /admin/payroll/salary/{empId} {month, amount, mode, paid_by, date, transaction_id?, remark?} */
    public function pay(Request $request, $empId)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;
        $month = $this->month($request);

        $e = AdminEmployee::forOrganization($orgId)->find($empId);
        if (!$e) return $this->error('Employee not found.', 404);

        if (!$this->run($this->page(['salaryMonth' => $month]), 'canPayMonth')) {
            return $this->error('Salary for ' . Carbon::parse($month . '-01')->format('M Y') . ' can be paid only after the month ends.', 422);
        }

        if ($err = $this->validateWith($request, [
            'amount'         => 'required|numeric|min:0|max:99999999',
            'mode'           => 'required|in:' . implode(',', self::PAY_MODES),
            'paid_by'        => 'required|string|max:255',
            'date'           => 'required|date',
            'transaction_id' => 'nullable|string|max:100',
            'remark'         => 'nullable|string|max:500',
        ])) return $err;

        AdminSalaryPayment::updateOrCreate(
            ['admin_employee_id' => $e->id, 'organization_id' => $orgId, 'month' => $month],
            [
                'amount'         => $request->amount,
                'payment_mode'   => $request->mode,
                'paid_by'        => $request->paid_by,
                'status'         => 'paid',
                'payment_date'   => $request->date,
                'transaction_id' => $request->transaction_id ?: null,
                'remark'         => $request->remark ?: null,
            ]
        );

        return $this->success(null, in_array($request->mode, ['online', 'bank_transfer'], true)
            ? 'Salary credited to employee account!'
            : 'Salary payment recorded!');
    }

    // ══════════════════════════ PAYMENTS ══════════════════════════

    /** GET /admin/payroll/payments?type=&employee_id=&month=&search= */
    public function payments(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        // A type picked and none of its people: every payment of that type's people.
        $people = $request->filled('type') && !$request->filled('employee_id')
            ? AdminEmployee::forOrganization($user->organization_id)->get()->filter(fn ($e) => $e->hasType($request->type))->pluck('id')
            : null;

        $rows = AdminSalaryPayment::forOrganization($user->organization_id)
            ->with('employee')
            ->when($people !== null, fn ($q) => $q->whereIn('admin_employee_id', $people))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('admin_employee_id', $request->employee_id))
            ->when($request->filled('month'), fn ($q) => $q->forMonth($request->month))
            ->latest()->get()
            ->when($request->filled('search'), function ($c) use ($request) {
                $t = mb_strtolower(trim($request->search));
                return $c->filter(fn ($p) => str_contains(mb_strtolower((string) $p->employee?->name), $t));
            })->values();

        return $this->success([
            'payments' => $rows->map(fn ($p) => $this->shapePayment($p) + [
                'employee' => $p->employee ? [
                    'id' => $p->employee->id, 'name' => $p->employee->name,
                    'designation' => $p->employee->designation, 'photo' => $p->employee->photo,
                    'types' => $p->employee->types(),
                ] : null,
            ]),
            'total' => (float) $rows->where('status', 'paid')->sum('amount'),
        ], 'Payments fetched.');
    }

    // ══════════════════════════ THE PANEL'S NEWER SCREENS ══════════════════════════
    // Mark Attendance for everyone at once (teachers too, in the Attendance
    // module's own records), a date's statuses with their remarks, the Mark
    // Salary tab (one person's months since April, and Add Payment against a
    // month), and Add → Driver.

    /** A real calendar day, not one still to come. */
    private function pastDate(?string $date): ?string
    {
        $d = \DateTime::createFromFormat('!Y-m-d', (string) $date);
        return $d && $d->format('Y-m-d') === $date && $date <= now()->toDateString() ? $date : null;
    }

    /**
     * GET /admin/payroll/attendance/day?date=YYYY-MM-DD
     * The Attendance tab's date view: everyone that day — a teacher's from the
     * Attendance module — with the status and remark, and the day's counts.
     */
    public function attendanceDay(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $date = $this->pastDate($request->input('date')) ?? now()->toDateString();
        $this->provision();

        $employees = $this->orderByType(AdminEmployee::forOrganization($orgId)->orderBy('name')->get());
        $marks     = $this->run($this->page(), 'dayMarks', $date, $employees);

        $counts = ['present' => 0, 'absent' => 0, 'half_day' => 0, 'holiday' => 0, 'leave' => 0, 'not_marked' => 0];
        $rows = $employees->map(function ($e) use ($marks, &$counts) {
            $m = $marks[$e->id] ?? ['status' => null, 'remark' => ''];
            $counts[$m['status'] && isset($counts[$m['status']]) ? $m['status'] : 'not_marked']++;
            return $this->shape($e) + ['status' => $m['status'], 'remark' => (string) $m['remark']];
        })->values();

        return $this->success([
            'date'      => $date,
            'label'     => Carbon::parse($date)->format('l, d M Y'),
            'employees' => $rows,
            'counts'    => $counts,
        ], 'Attendance fetched.');
    }

    /**
     * GET /admin/payroll/attendance/sheet?date=YYYY-MM-DD
     * The Mark Attendance panel for a day: everyone — teachers first, then
     * management, drivers and employees, A to Z — each with what is saved for
     * them (status and remark), and whether the day is already marked.
     */
    public function attendanceSheet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;
        $orgId = (int) $user->organization_id;

        $date = $this->pastDate($request->input('date')) ?? now()->toDateString();
        $this->provision();

        $page = $this->page(['panelDate' => $date]);
        $this->run($page, 'loadMarkPanel');

        $people = AdminEmployee::forOrganization($orgId)->get()
            ->sortBy(fn ($e) => [PayrollPage::MARK_ORDER[$e->type] ?? 9, mb_strtolower((string) $e->name)])
            ->values();

        return $this->success([
            'date'     => $date,
            'label'    => Carbon::parse($date)->format('l, d M Y'),
            'existing' => (bool) $page->panelExisting,
            'rows'     => $people->map(fn ($e) => $this->shape($e) + [
                'status' => (string) ($page->panelRows[$e->id]['status'] ?? ''),
                'remark' => (string) ($page->panelRows[$e->id]['remark'] ?? ''),
            ])->values(),
            'statuses' => ['present', 'absent', 'half_day', 'holiday'],
        ], 'Attendance sheet fetched.');
    }

    /**
     * POST /admin/payroll/attendance/sheet {date, rows: {employee id: {status, remark}}}
     * The panel's Save: a teacher's row to the Attendance module, everyone
     * else's to payroll's; a blank row saves nothing (and clears an earlier
     * mark); everyone on Holiday needs the remark.
     */
    public function saveAttendanceSheet(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $rows = [];
        foreach ((array) $request->input('rows', []) as $id => $r) {
            $rows[(int) $id] = [
                'status' => (string) ($r['status'] ?? ''),
                'remark' => trim((string) ($r['remark'] ?? '')),
            ];
        }

        $page = $this->page([
            'panelDate' => (string) $request->input('date', ''),
            'panelRows' => $rows,
        ]);

        return $this->act($page, 'saveMarkPanel', [], [], 'Attendance saved.');
    }

    /**
     * GET /admin/payroll/account/{id}
     * The Mark Salary tab for one person: who they are, and each month from 1
     * April (or the month they joined) to this one — the attendance marked,
     * the salary it works out to, what was paid against it — with the totals.
     */
    public function account($id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $e = AdminEmployee::forOrganization($user->organization_id)->find($id);
        if (!$e) return $this->error('Employee not found.', 404);

        $account = $this->run($this->page(['salaryEmpId' => (string) $e->id]), 'salaryAccount', $e);

        return $this->success([
            'employee' => $this->shape($e) + [
                'bank_name'        => $e->bank_name,
                'bank_account_no'  => $e->bank_account_no,
                'bank_ifsc'        => $e->bank_ifsc,
                'bank_holder_name' => $e->bank_holder_name,
            ],
            'months'   => $account['months'],
            'payable'  => (float) $account['payable'],
            'paid'     => (float) $account['paid'],
            'balance'  => (float) $account['balance'],
            'paid_leaves_a_month' => PayrollPage::PAID_LEAVES_A_MONTH,
        ], 'Salary account fetched.');
    }

    /**
     * POST /admin/payroll/account/{id}/payments {amount, date, from, remark?, month?}
     * Add Payment: kept against the month whose screen it was added from, else
     * the month of its date.
     */
    public function addAccountPayment(Request $request, $id)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $e = AdminEmployee::forOrganization($user->organization_id)->find($id);
        if (!$e) return $this->error('Employee not found.', 404);

        $page = $this->page([
            'salaryEmpId' => (string) $e->id,
            'spMonth'     => (string) $request->input('month', ''),
            'spAmount'    => trim((string) $request->input('amount', '')),
            'spDate'      => (string) $request->input('date', ''),
            'spFrom'      => trim((string) $request->input('from', '')),
            'spRemark'    => trim((string) $request->input('remark', '')),
        ]);

        return $this->act($page, 'saveSalaryPayment', [], [
            'spAmount' => 'amount',
            'spDate'   => 'date',
            'spFrom'   => 'from',
            'spRemark' => 'remark',
        ], 'Payment added.');
    }

    /** GET /admin/payroll/lookups — the routes a new driver can be put on, as Transport lists them. */
    public function lookups()
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        return $this->success([
            'routes' => Transportation::where('organization_id', $user->organization_id)
                ->orderBy('route_name')->orderBy('vehicle_type')
                ->get(['id', 'route_name', 'vehicle_type'])
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->route_name, 'vehicle_type' => $r->vehicle_type])
                ->values(),
        ], 'Lookups fetched.');
    }

    /**
     * POST /admin/payroll/drivers (multipart) — Add → Driver: made as Transport
     * makes a driver (a login on password 123456, the driver record, the routes
     * picked), with their payroll row carrying the salary and bank details.
     * {name, mobile, email?, license_no?, vehicle_no?, experience?, is_active?, route_ids[]?,
     *  salary, designation?, joining_date?, address?, bank_*?, photo?}
     */
    public function addDriver(Request $request)
    {
        [$user, $err] = $this->guard();
        if ($err) return $err;

        $page = $this->page([
            'empType'        => 'driver',
            'empName'        => trim((string) $request->input('name', '')),
            'empEmail'       => trim((string) $request->input('email', '')),
            'empMobile'      => trim((string) $request->input('mobile', '')),
            'empDesignation' => trim((string) $request->input('designation', '')),
            'empSalary'      => trim((string) $request->input('salary', '')),
            'empAddress'     => trim((string) $request->input('address', '')),
            'empBankName'    => trim((string) $request->input('bank_name', '')),
            'empAccountNo'   => trim((string) $request->input('bank_account_no', '')),
            'empHolderName'  => trim((string) $request->input('bank_holder_name', '')),
            'empBranch'      => trim((string) $request->input('bank_branch', '')),
            'empIfsc'        => trim((string) $request->input('bank_ifsc', '')),
            'empJoiningDate' => (string) $request->input('joining_date', ''),
            'drvLicenseNo'   => trim((string) $request->input('license_no', '')),
            'drvVehicleNo'   => trim((string) $request->input('vehicle_no', '')),
            'drvExperience'  => trim((string) $request->input('experience', '')),
            'drvActive'      => $request->boolean('is_active', true),
            'drvRoutes'      => array_values(array_map('intval', (array) $request->input('route_ids', []))),
        ]);
        $page->empPhoto = $request->file('photo');

        return $this->act($page, 'saveEmployee', [], [
            'empName'        => 'name',
            'empEmail'       => 'email',
            'empMobile'      => 'mobile',
            'empDesignation' => 'designation',
            'empSalary'      => 'salary',
            'empAddress'     => 'address',
            'empBankName'    => 'bank_name',
            'empAccountNo'   => 'bank_account_no',
            'empHolderName'  => 'bank_holder_name',
            'empBranch'      => 'bank_branch',
            'empIfsc'        => 'bank_ifsc',
            'empJoiningDate' => 'joining_date',
            'empPhoto'       => 'photo',
            'drvLicenseNo'   => 'license_no',
            'drvVehicleNo'   => 'vehicle_no',
            'drvExperience'  => 'experience',
        ], 'Driver added!');
    }
}
