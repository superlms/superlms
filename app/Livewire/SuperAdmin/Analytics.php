<?php

namespace App\Livewire\SuperAdmin;

use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\Organization;
use App\Models\User;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\SuperAdmin\SuperAdminEmployee;
use App\Models\SuperAdmin\SuperAdminSalaryPayment;
use App\Models\SuperAdmin\SuperAdminCalendarEvent;
use App\Models\Admin\RateLms;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Models\WebsiteVisit;
use App\Support\SuperAdminFeeTotals;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

class Analytics extends Component
{
    // A tab switch is one step for the header's Back.
    #[Url(history: true, except: 'overview')]
    public string $activeTab = 'overview';

    /** Chart window in months — 6 / 12 / 24, switchable from the header. */
    public int $months = 12;

    public array $overviewStats        = [];
    public array $topByStudents        = [];
    public array $topByTeachers        = [];
    public array $schoolBuckets        = [];
    public array $monthlyRegistrations = [];
    public array $genderSplit          = [];
    public array $ratingStats          = [];

    public array $creditStats       = [];
    public array $creditMonthly     = [];
    public array $topCreditSchools  = [];

    public array $feeStats      = [];
    public array $feeMonthly    = [];
    public array $schoolFeeList = [];
    public array $feeModes      = [];

    public array $payrollStats   = [];
    public array $payrollMonthly = [];
    public array $payrollModes   = [];
    public array $payrollPending = [];
    public array $payrollRecent  = [];

    public array $enquiryStats   = [];
    public array $enquiryMonthly = [];

    public array $supportStats   = [];
    public array $supportMonthly = [];

    // ── More detail on every section (user's ask of 2026-11-19) ──────────────
    /** Schools: the newest ones, how they split by state / board / medium, and a few ratios. */
    public array $schoolExtra   = [];
    public array $schoolGroups  = [];
    public array $newestSchools = [];
    /** Website: today, the period before, the busiest day and the weekdays. */
    public array $websiteExtra = [];
    /** Fee: schools by payment status, the biggest dues and the latest payments. */
    public array $feeExtra  = [];
    public array $feeRecent = [];
    /** Payroll: active against inactive, payments this year, the month's progress. */
    public array $payrollExtra = [];
    /** Calendar: what is coming up in the next seven days. */
    public array $calendarUpcoming = [];
    /** Enquiries: demo statuses, meetings booked, cities and the latest ones. */
    public array $enquiryExtra  = [];
    public array $enquiryRecent = [];
    /** Support: last month, the oldest waiting, the schools and topics, the latest tickets. */
    public array $supportExtra  = [];
    public array $supportRecent = [];

    // The signed-in person's own Calendar; empty (and off the page) for a
    // sub-super-admin who was not given Calendar.
    public array $calendarStats   = [];
    public array $calendarMonthly = [];

    // ── Website traffic (date range within the last 90 days) ──────────────────
    public string $visitFrom = '';
    public string $visitTo   = '';
    public array  $websiteStats = [];
    public array  $websitePages = [];
    public array  $websiteDaily = [];

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        // Charts live inside @if blocks — after the tab's canvases morph in,
        // the frontend re-initialises every visible chart from the payload.
        $this->dispatch('analytics-refresh');
    }

    public function updatedMonths(): void
    {
        $this->months = in_array((int) $this->months, [6, 12, 24], true) ? (int) $this->months : 12;
        $this->loadAll();
        $this->dispatch('analytics-refresh');
    }

    public function mount(): void
    {
        // Default website window: the last 30 days (within the 90-day cap).
        $this->visitTo   = now()->format('Y-m-d');
        $this->visitFrom = now()->subDays(29)->format('Y-m-d');
        $this->loadAll();
    }

    public function setVisitRange(int $days): void
    {
        $days = max(1, min(90, $days));
        $this->visitTo   = now()->format('Y-m-d');
        $this->visitFrom = now()->subDays($days - 1)->format('Y-m-d');
        $this->loadWebsite();
        $this->dispatch('analytics-refresh');
    }

    public function updatedVisitFrom(): void
    {
        $this->loadWebsite();
        $this->dispatch('analytics-refresh');
    }

    public function updatedVisitTo(): void
    {
        $this->loadWebsite();
        $this->dispatch('analytics-refresh');
    }

    private function loadAll(): void
    {
        $this->loadOverview();
        $this->loadCredit();
        $this->loadFee();
        $this->loadPayroll();
        $this->loadEnquiries();
        $this->loadSupport();
        $this->loadCalendar();
        $this->loadWebsite();
    }

    // ─── Monthly-series helpers ───────────────────────────────────────────────

    /** Month keys for the selected window, oldest → newest: ['2025-08', …]. */
    private function monthKeys(): array
    {
        $keys = [];
        for ($i = $this->months - 1; $i >= 0; $i--) {
            $keys[] = now()->subMonths($i)->format('Y-m');
        }

        return $keys;
    }

    /** Human labels aligned with monthKeys(): ['Aug 2025', …]. */
    private function monthLabels(): array
    {
        return array_map(
            fn($k) => \Carbon\Carbon::parse($k . '-01')->format('M y'),
            $this->monthKeys()
        );
    }

    /**
     * One aggregate query for a whole monthly series (replaces the old
     * 12-queries-per-line loops). $agg: 'count' or a column to SUM.
     */
    private function monthlySeries($query, string $agg = 'count', string $dateColumn = 'created_at'): array
    {
        $start = now()->subMonths($this->months - 1)->startOfMonth();

        $select = $agg === 'count' ? 'COUNT(*)' : "COALESCE(SUM({$agg}), 0)";

        $rows = $query
            ->where($dateColumn, '>=', $start)
            ->selectRaw("DATE_FORMAT({$dateColumn}, '%Y-%m') as ym, {$select} as v")
            ->groupBy('ym')
            ->pluck('v', 'ym');

        return array_map(fn($k) => (float) ($rows[$k] ?? 0), $this->monthKeys());
    }

    // ─── Overview ─────────────────────────────────────────────────────────────

    private function loadOverview(): void
    {
        $totalSchools    = Organization::count();
        $activeSchools   = Organization::where('status', true)->count();
        $totalStudents   = User::where('role', 'user')->count();
        $activeStudents  = User::where('role', 'user')->where('is_active', 1)->count();
        $totalTeachers   = User::where('role', 'teacher')->count();
        $activeTeachers  = User::where('role', 'teacher')->where('is_active', 1)->count();
        $avgStudents     = $totalSchools > 0 ? round($totalStudents / $totalSchools, 1) : 0;

        $studentsThisMonth = User::where('role', 'user')->where('created_at', '>=', now()->startOfMonth())->count();
        $studentsLastMonth = User::where('role', 'user')
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();
        $growthPct = $studentsLastMonth > 0
            ? round((($studentsThisMonth - $studentsLastMonth) / $studentsLastMonth) * 100, 1)
            : ($studentsThisMonth > 0 ? 100 : 0);

        $this->overviewStats = [
            'totalSchools'         => $totalSchools,
            'activeSchools'        => $activeSchools,
            'inactiveSchools'      => max(0, $totalSchools - $activeSchools),
            'newSchoolsThisMonth'  => Organization::where('created_at', '>=', now()->startOfMonth())->count(),
            'totalStudents'        => $totalStudents,
            'activeStudents'       => $activeStudents,
            'totalTeachers'        => $totalTeachers,
            'activeTeachers'       => $activeTeachers,
            'avgStudentsPerSchool' => $avgStudents,
            'studentsThisMonth'    => $studentsThisMonth,
            'studentsLastMonth'    => $studentsLastMonth,
            'studentGrowthPct'     => $growthPct,
        ];

        // Top 5 by students / teachers — one query, reused for both boards.
        $withCounts = fn() => Organization::select('id', 'name', 'logo')->withCount([
            'users as students_count' => fn($q) => $q->where('role', 'user'),
            'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
        ]);

        $mapTop = fn($rows) => $rows->values()->map(fn($org, $i) => [
            'rank'     => $i + 1,
            'id'       => $org->id,
            'name'     => $org->name,
            'logo'     => $org->logo,
            'students' => $org->students_count,
            'teachers' => $org->teachers_count,
        ])->toArray();

        $this->topByStudents = $mapTop($withCounts()->orderByDesc('students_count')->limit(5)->get());
        $this->topByTeachers = $mapTop($withCounts()->orderByDesc('teachers_count')->limit(5)->get());

        // School size buckets
        $schoolsWithCounts = Organization::withCount([
            'users as students_count' => fn($q) => $q->where('role', 'user'),
        ])->get();

        $this->schoolBuckets = [
            '<500'  => $schoolsWithCounts->where('students_count', '<', 500)->count(),
            '500+'  => $schoolsWithCounts->where('students_count', '>=', 500)->count(),
            '1000+' => $schoolsWithCounts->where('students_count', '>=', 1000)->count(),
            '1500+' => $schoolsWithCounts->where('students_count', '>=', 1500)->count(),
            '2000+' => $schoolsWithCounts->where('students_count', '>=', 2000)->count(),
        ];

        // A few more figures on the schools (2026-11-19).
        $lastMonthRange = [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];
        $this->schoolExtra = [
            'newLastMonth'       => Organization::whereBetween('created_at', $lastMonthRange)->count(),
            'avgTeachers'        => $totalSchools > 0 ? round($totalTeachers / $totalSchools, 1) : 0,
            'studentsPerTeacher' => $totalTeachers > 0 ? round($totalStudents / $totalTeachers, 1) : 0,
            'emptySchools'       => $schoolsWithCounts->where('students_count', 0)->count(),
            'largest'            => (int) ($schoolsWithCounts->max('students_count') ?? 0),
            'teachersThisMonth'  => User::where('role', 'teacher')->where('created_at', '>=', now()->startOfMonth())->count(),
            'teachersLastMonth'  => User::where('role', 'teacher')->whereBetween('created_at', $lastMonthRange)->count(),
        ];

        // Schools by state, board and medium: the biggest few, then the rest.
        $this->schoolGroups = [
            'state'  => $this->groupSchools('state'),
            'board'  => $this->groupSchools('education_board'),
            'medium' => $this->groupSchools('medium'),
        ];

        // The schools that joined last, with their size.
        $this->newestSchools = Organization::select('id', 'name', 'logo', 'status', 'created_at')
            ->withCount([
                'users as students_count' => fn($q) => $q->where('role', 'user'),
                'users as teachers_count' => fn($q) => $q->where('role', 'teacher'),
            ])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn($o) => [
                'name'     => $o->name,
                'logo'     => $o->logo,
                'active'   => (bool) $o->status,
                'students' => (int) $o->students_count,
                'teachers' => (int) $o->teachers_count,
                'joined'   => $o->created_at?->format('d M Y') ?? '—',
            ])->toArray();

        // Monthly registrations + schools onboarded (aggregate queries)
        $this->monthlyRegistrations = [
            'labels'   => $this->monthLabels(),
            'students' => $this->monthlySeries(User::where('role', 'user')),
            'teachers' => $this->monthlySeries(User::where('role', 'teacher')),
            'schools'  => $this->monthlySeries(Organization::query()),
        ];

        $this->genderSplit = $this->studentGenderSplit();

        // Platform ratings (Rate LMS)
        $ratingRows = RateLms::selectRaw('rating, COUNT(*) as c')->groupBy('rating')->pluck('c', 'rating');
        $totalRatings = (int) $ratingRows->sum();
        $dist = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $count = (int) ($ratingRows[$star] ?? 0);
            $dist[$star] = [
                'count' => $count,
                'pct'   => $totalRatings > 0 ? round($count / $totalRatings * 100, 1) : 0,
            ];
        }

        $this->ratingStats = [
            'total'        => $totalRatings,
            'avg'          => $totalRatings > 0 ? round((float) RateLms::avg('rating'), 1) : 0,
            'fiveStar'     => (int) ($ratingRows[5] ?? 0),
            'distribution' => $dist,
        ];
    }

    /**
     * The schools counted by one of their own fields (state, board, medium):
     * the six biggest by name — spelt the same whatever its case — then the
     * rest as "Others", and the ones with nothing filled in as "Not set".
     */
    private function groupSchools(string $column): array
    {
        // A board's short name (CBSE, ICSE…) or a two-letter state (UP, MP) in
        // capitals; any other name in title case.
        $acronyms = ['cbse', 'icse', 'isc', 'ib', 'igcse', 'nios', 'cisce', 'ssc', 'hsc', 'bseb', 'rbse', 'mpbse', 'cgbse', 'jac',
                     'pseb', 'hbse', 'gseb', 'wbbse', 'upmsp', 'jkbose', 'tbse', 'bse', 'cie', 'ncert'];
        $label = function (string $v) use ($acronyms): string {
            if ($v === '') {
                return 'Not set';
            }
            $lower = mb_strtolower($v);

            return mb_strlen($v) <= 2 || in_array($lower, $acronyms, true) ? mb_strtoupper($v) : \Illuminate\Support\Str::title($lower);
        };

        $counts = Organization::query()->pluck($column)
            ->map(fn($v) => $label(trim((string) $v)))
            ->countBy()
            ->sortDesc();

        $notSet = (int) ($counts['Not set'] ?? 0);
        $named  = $counts->except('Not set');
        $rows   = $named->take(6)->map(fn($c, $label) => ['label' => (string) $label, 'count' => (int) $c])->values()->all();
        $rest   = (int) $named->slice(6)->sum();
        if ($rest > 0) {
            $rows[] = ['label' => 'Others', 'count' => $rest];
        }
        if ($notSet > 0) {
            $rows[] = ['label' => 'Not set', 'count' => $notSet];
        }

        return $rows;
    }

    /**
     * Students by gender, from student_details.
     */
    private function studentGenderSplit(): array
    {
        // Student gender split. A student's gender is kept on student_details
        // (the panel and app forms save male / female / other there); the
        // users.gender column is the sub-super-admins' and is empty for
        // students, so reading it put every student under "Not set".
        $genders = StudentDetail::query()
            ->selectRaw("LOWER(COALESCE(NULLIF(TRIM(gender), ''), 'unknown')) as g, COUNT(*) as c")
            ->groupBy('g')
            ->pluck('c', 'g');

        return [
            'male'    => (int) ($genders['male'] ?? 0),
            'female'  => (int) ($genders['female'] ?? 0),
            // Anything else written into the field counts as other.
            'other'   => (int) $genders->except(['male', 'female', 'unknown'])->sum(),
            'unknown' => (int) ($genders['unknown'] ?? 0),
        ];
    }

    // ─── Credit ───────────────────────────────────────────────────────────────

    private function loadCredit(): void
    {
        $total    = CreditQuery::count();
        $approved = CreditQuery::approved()->count();

        $totalAmountLeased = (float) CreditQuery::approved()->sum('amount');
        $amountCollected   = (float) CreditQuery::approved()->whereNotNull('collected_at')->sum('amount');

        $this->creditStats = [
            'total'             => $total,
            'pending'           => CreditQuery::pending()->count(),
            'processing'        => CreditQuery::processing()->count(),
            'approved'          => $approved,
            'denied'            => CreditQuery::denied()->count(),
            'totalAmountLeased' => $totalAmountLeased,
            'totalPending'      => (float) CreditQuery::pending()->sum('amount'),
            'activeCredits'     => CreditQuery::activeCredit()->count(),
            // Extra detail
            'approvalRate'      => $total > 0 ? round($approved / $total * 100, 1) : 0,
            'avgApproved'       => $approved > 0 ? round($totalAmountLeased / $approved, 0) : 0,
            'collectedCount'    => CreditQuery::approved()->whereNotNull('collected_at')->count(),
            'amountCollected'   => $amountCollected,
            'amountOutstanding' => max(0, $totalAmountLeased - $amountCollected),
        ];

        $this->creditMonthly = [
            'labels'       => $this->monthLabels(),
            'applications' => $this->monthlySeries(CreditQuery::query()),
            'approved'     => $this->monthlySeries(CreditQuery::approved()),
            'amount'       => $this->monthlySeries(CreditQuery::approved(), 'amount'),
        ];

        // Top 5 schools by approved credit amount
        $this->topCreditSchools = CreditQuery::approved()
            ->selectRaw('organization_id, COUNT(*) as queries, SUM(amount) as total_amount')
            ->groupBy('organization_id')
            ->orderByDesc('total_amount')
            ->limit(5)
            ->get()
            ->map(function ($row, $i) {
                return [
                    'rank'    => $i + 1,
                    'name'    => Organization::find($row->organization_id)?->name ?? 'Unknown',
                    'queries' => (int) $row->queries,
                    'amount'  => (float) $row->total_amount,
                ];
            })->values()->toArray();
    }

    // ─── Fee ──────────────────────────────────────────────────────────────────

    /**
     * The Super Admin's fee: the year's total from the fee structure set for
     * each school on the Fees screen against the money recorded on its
     * payments (`SuperAdminFeeTotals`, as the Fees screen and the home count
     * it — part payments are money received too); months by payment date.
     */
    private function loadFee(): void
    {
        $year              = SuperAdminFeeTotals::summary();
        $totalFeeToCollect = $year['expected'];
        $totalCollected    = $year['collected'];
        $totalRemaining    = $year['remaining'];
        $allStudents       = StudentDetail::count();

        $thisMonth = now()->startOfMonth();
        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();

        $this->feeStats = [
            'year'               => $year['year'],
            'totalSchools'       => $year['schools'],
            'totalStudents'      => $year['payments'],
            'totalFeeToCollect'  => $totalFeeToCollect,
            'totalCollected'     => $totalCollected,
            'totalRemaining'     => $totalRemaining,
            // As the Fees screen's header: the fee to collect over every student.
            'avgFeePerStudent'   => $allStudents > 0 ? round($totalFeeToCollect / $allStudents) : 0,
            // Extra detail
            'collectionRate'     => $year['pct'],
            'collectedThisMonth' => (float) SuperAdminFeeReceipt::whereDate('payment_date', '>=', $thisMonth->toDateString())
                ->whereDate('payment_date', '<=', now()->toDateString())
                ->sum('amount'),
            'collectedLastMonth' => (float) SuperAdminFeeReceipt::whereDate('payment_date', '>=', $lastMonth->toDateString())
                ->whereDate('payment_date', '<=', $lastMonth->copy()->endOfMonth()->toDateString())
                ->sum('amount'),
            'paidRecords'        => $year['payments'],
            'unpaidRecords'      => $year['schools_due'],
            'schoolsDue'         => $year['schools_due'],
            'payments'           => $year['payments'],
        ];

        // School-wise breakdown — the 10 biggest fees of the year.
        $this->schoolFeeList = $year['by_school']->take(10)->map(fn ($s) => [
            'name'      => $s['name'],
            'plan'      => $s['plan'],
            'toCollect' => $s['expected'],
            'collected' => $s['collected'],
            'remaining' => $s['remaining'],
            'pct'       => $s['pct'],
        ])->values()->toArray();

        // Schools by how much of the year's fee they have paid, the biggest
        // dues, and the payments themselves (2026-11-19).
        $bySchool     = $year['by_school'];
        $receiptCount = SuperAdminFeeReceipt::where('amount', '>', 0)->count();
        $receiptSum   = (float) SuperAdminFeeReceipt::where('amount', '>', 0)->sum('amount');
        $this->feeExtra = [
            'status' => [
                'full' => $bySchool->filter(fn($r) => $r['expected'] > 0 && $r['remaining'] <= 0.005)->count(),
                'part' => $bySchool->filter(fn($r) => $r['collected'] > 0 && $r['remaining'] > 0.005)->count(),
                'none' => $bySchool->filter(fn($r) => $r['expected'] > 0 && $r['collected'] <= 0)->count(),
            ],
            'topDues' => $bySchool->filter(fn($r) => $r['remaining'] > 0.005)
                ->sortByDesc('remaining')->take(5)
                ->map(fn($r) => ['name' => $r['name'], 'remaining' => (float) $r['remaining'], 'expected' => (float) $r['expected'], 'pct' => $r['pct']])
                ->values()->all(),
            'receipts'          => $receiptCount,
            'avgPayment'        => $receiptCount > 0 ? round($receiptSum / $receiptCount) : 0,
            'paymentsThisMonth' => SuperAdminFeeReceipt::where('amount', '>', 0)
                ->whereDate('payment_date', '>=', $thisMonth->toDateString())->count(),
            'largestPayment'    => (float) (SuperAdminFeeReceipt::max('amount') ?? 0),
        ];
        $this->feeRecent = SuperAdminFeeReceipt::with('organization:id,name')
            ->where('amount', '>', 0)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn($r) => [
                'name'   => $r->organization?->name ?? 'Unknown',
                'amount' => (float) $r->amount,
                'mode'   => $r->payment_mode ? ucwords(str_replace('_', ' ', $r->payment_mode)) : '—',
                'date'   => $r->payment_date?->format('d M Y') ?? '—',
                'number' => $r->receipt_number,
            ])->toArray();

        $this->feeMonthly = [
            'labels'    => $this->monthLabels(),
            'collected' => $this->monthlySeries(SuperAdminFeeReceipt::query(), 'amount', 'payment_date'),
            'payments'  => $this->monthlySeries(SuperAdminFeeReceipt::where('amount', '>', 0), 'count', 'payment_date'),
        ];

        // Collected amount by payment mode
        $modes = SuperAdminFeeReceipt::query()
            ->selectRaw("COALESCE(NULLIF(payment_mode, ''), 'other') as mode, SUM(amount) as total")
            ->groupBy('mode')
            ->orderByDesc('total')
            ->pluck('total', 'mode');
        $modes = $modes->filter(fn($v) => (float) $v > 0);   // a mode whose corrections cancel it out

        $this->feeModes = [
            'labels' => $modes->keys()->map(fn($m) => ucwords(str_replace('_', ' ', $m)))->toArray(),
            'data'   => $modes->values()->map(fn($v) => (float) $v)->toArray(),
        ];
    }

    // ─── Payroll ──────────────────────────────────────────────────────────────

    private function loadPayroll(): void
    {
        $totalEmployees         = SuperAdminEmployee::count();
        $totalMonthlySalaryBill = (float) SuperAdminEmployee::where('is_active', true)->sum('salary');

        // Salary is payable for the PREVIOUS month (current month is locked in payroll).
        $payableMonth     = now()->subMonthNoOverflow()->format('Y-m');
        // People paid, not payments: Payroll's Mark Salary keeps each payment as
        // its own row, so one person can have several in a month.
        $paidThisMonth    = SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->distinct()->count('super_admin_employee_id');
        $totalPaidAmount  = (float) SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->sum('amount');

        // Employee types come from the payroll module so new types show up automatically.
        $typeRows   = SuperAdminEmployee::selectRaw('type, COUNT(*) as c')->groupBy('type')->pluck('c', 'type');
        $salaryRows = SuperAdminEmployee::selectRaw('type, SUM(salary) as s')->groupBy('type')->pluck('s', 'type');
        $byType     = [];
        foreach (Payroll::EMP_TYPES as $type) {
            $byType[$type] = [
                'count'  => (int) ($typeRows[$type] ?? 0),
                'salary' => (float) ($salaryRows[$type] ?? 0),
            ];
        }
        // Legacy/unexpected types still show instead of silently disappearing.
        foreach ($typeRows as $type => $count) {
            if (!isset($byType[$type])) {
                $byType[$type] = ['count' => (int) $count, 'salary' => (float) ($salaryRows[$type] ?? 0)];
            }
        }

        // Payment-mode split (mirrors the Fee tab's mode breakdown)
        $modes = SuperAdminSalaryPayment::paid()
            ->selectRaw("COALESCE(NULLIF(payment_mode, ''), 'other') as mode, SUM(amount) as total")
            ->groupBy('mode')
            ->orderByDesc('total')
            ->pluck('total', 'mode');

        $this->payrollModes = [
            'labels' => $modes->keys()->map(fn($m) => ucwords(str_replace('_', ' ', $m)))->toArray(),
            'data'   => $modes->values()->map(fn($v) => (float) $v)->toArray(),
        ];

        // Employees still unpaid for the payable month — who needs paying, right now.
        $paidEmployeeIds = SuperAdminSalaryPayment::forMonth($payableMonth)->paid()->pluck('super_admin_employee_id');
        $this->payrollPending = SuperAdminEmployee::where('is_active', true)
            ->whereNotIn('id', $paidEmployeeIds)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'designation', 'salary'])
            ->map(fn($e) => [
                'name'        => $e->name,
                'type'        => $e->type,
                'designation' => $e->designation,
                'salary'      => (float) $e->salary,
            ])->toArray();

        // Most recent payouts, whatever month they were for.
        $this->payrollRecent = SuperAdminSalaryPayment::paid()
            ->with('employee:id,name,type')
            ->orderByDesc('payment_date')
            ->limit(8)
            ->get()
            ->map(fn($p) => [
                'name'   => $p->employee?->name ?? 'Unknown',
                'type'   => $p->employee?->type ?? '—',
                'month'  => $p->month ? \Carbon\Carbon::parse($p->month . '-01')->format('M Y') : '—',
                'amount' => (float) $p->amount,
                'mode'   => $p->payment_mode ? ucwords(str_replace('_', ' ', $p->payment_mode)) : '—',
                'date'   => $p->payment_date?->format('d M Y') ?? '—',
            ])->toArray();

        $this->payrollStats = [
            'totalEmployees'         => $totalEmployees,
            'totalMonthlySalaryBill' => $totalMonthlySalaryBill,
            'payableMonth'           => \Carbon\Carbon::parse($payableMonth . '-01')->format('M Y'),
            'paidThisMonth'          => $paidThisMonth,
            'pendingThisMonth'       => max(0, $totalEmployees - $paidThisMonth),
            'totalPaidAmount'        => $totalPaidAmount,
            'byType'                 => $byType,
            // Extra detail
            'avgSalary'              => $totalEmployees > 0 ? round((float) SuperAdminEmployee::avg('salary'), 0) : 0,
            'highestSalary'          => (float) (SuperAdminEmployee::max('salary') ?? 0),
            'lowestSalary'           => (float) (SuperAdminEmployee::where('is_active', true)->min('salary') ?? 0),
            'paidThisYear'           => (float) SuperAdminSalaryPayment::paid()
                ->where('month', 'like', now()->format('Y') . '-%')
                ->sum('amount'),
        ];

        // Salary paid per payment_date month (payment_date reflects when money moved)
        $this->payrollMonthly = [
            'labels' => $this->monthLabels(),
            'paid'   => $this->monthlySeries(
                SuperAdminSalaryPayment::paid()->whereNotNull('payment_date'),
                'amount',
                'payment_date'
            ),
        ];

        // Active against inactive, payments this year, and how far the
        // payable month's bill has been paid (2026-11-19).
        $activeEmployees = SuperAdminEmployee::where('is_active', true)->count();
        $paidMonths      = array_values(array_filter($this->payrollMonthly['paid'], fn($v) => $v > 0));
        $this->payrollExtra = [
            'active'           => $activeEmployees,
            'inactive'         => max(0, $totalEmployees - $activeEmployees),
            'paymentsThisYear' => SuperAdminSalaryPayment::paid()->where('month', 'like', now()->format('Y') . '-%')->count(),
            'paidPct'          => $totalMonthlySalaryBill > 0 ? min(100, round($totalPaidAmount / $totalMonthlySalaryBill * 100, 1)) : 0,
            'leftToPay'        => max(0, $totalMonthlySalaryBill - $totalPaidAmount),
            'avgMonth'         => $paidMonths ? round(array_sum($paidMonths) / count($paidMonths)) : 0,
        ];
    }

    // ─── Enquiries ────────────────────────────────────────────────────────────

    private function loadEnquiries(): void
    {
        // NB: the pending check groups the OR properly — a bare orWhere here used
        // to leak every row with remark = '' into the count regardless of role.
        $pendingScope = fn($q) => $q->where(fn($q) => $q->whereNull('remark')->orWhere('remark', ''));
        $repliedScope = fn($q) => $q->whereNotNull('remark')->where('remark', '!=', '');

        $demoTotal    = WebsiteDemo::count();
        $demoReplied  = $repliedScope(WebsiteDemo::query())->count();
        $demoPending  = $pendingScope(WebsiteDemo::query())->count();

        $contactTotal   = WebsiteContact::count();
        $contactReplied = $repliedScope(WebsiteContact::query())->count();
        $contactPending = $pendingScope(WebsiteContact::query())->count();

        $this->enquiryStats = [
            'demo' => [
                'total'     => $demoTotal,
                'pending'   => $demoPending,
                'replied'   => $demoReplied,
                'thisMonth' => WebsiteDemo::where('created_at', '>=', now()->startOfMonth())->count(),
                'thisWeek'  => WebsiteDemo::where('created_at', '>=', now()->startOfWeek())->count(),
                'replyRate' => $demoTotal > 0 ? round($demoReplied / $demoTotal * 100, 1) : 0,
            ],
            'contact' => [
                'total'     => $contactTotal,
                'pending'   => $contactPending,
                'replied'   => $contactReplied,
                'thisMonth' => WebsiteContact::where('created_at', '>=', now()->startOfMonth())->count(),
                'thisWeek'  => WebsiteContact::where('created_at', '>=', now()->startOfWeek())->count(),
                'replyRate' => $contactTotal > 0 ? round($contactReplied / $contactTotal * 100, 1) : 0,
            ],
            'combined' => [
                'total'   => $demoTotal + $contactTotal,
                'pending' => $demoPending + $contactPending,
                'replied' => $demoReplied + $contactReplied,
            ],
        ];

        $this->enquiryMonthly = [
            'labels'  => $this->monthLabels(),
            'demo'    => $this->monthlySeries(WebsiteDemo::query()),
            'contact' => $this->monthlySeries(WebsiteContact::query()),
        ];

        // Demo requests by status, meetings booked, where they come from, and
        // the latest enquiries of both kinds (2026-11-19).
        $statusRows = WebsiteDemo::query()
            ->selectRaw("LOWER(COALESCE(NULLIF(TRIM(status), ''), 'pending')) as s, COUNT(*) as c")
            ->groupBy('s')
            ->pluck('c', 's');
        $status = array_fill_keys(array_keys(WebsiteDemo::STATUSES), 0);
        foreach ($statusRows as $key => $count) {
            $status[array_key_exists($key, $status) ? $key : 'pending'] += (int) $count;
        }
        $lastMonthRange = [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()];
        $this->enquiryExtra = [
            'status'           => $status,
            'upcomingMeetings' => WebsiteDemo::where('meeting_at', '>=', now())->count(),
            'demoLastMonth'    => WebsiteDemo::whereBetween('created_at', $lastMonthRange)->count(),
            'contactLastMonth' => WebsiteContact::whereBetween('created_at', $lastMonthRange)->count(),
            'cities'           => WebsiteDemo::query()->pluck('city')
                ->map(fn($c) => trim((string) $c))->filter()
                ->map(fn($c) => \Illuminate\Support\Str::title(mb_strtolower($c)))
                ->countBy()->sortDesc()->take(5)
                ->map(fn($c, $city) => ['label' => (string) $city, 'count' => (int) $c])->values()->all(),
        ];

        $replied = fn($row) => trim((string) $row->remark) !== '';
        $demos = WebsiteDemo::orderByDesc('created_at')->limit(6)
            ->get(['id', 'full_name', 'school_name', 'city', 'remark', 'status', 'created_at'])
            ->map(fn($d) => [
                'kind' => 'Demo', 'name' => $d->full_name ?: 'Someone', 'school' => $d->school_name ?: ($d->city ?: '—'),
                'status' => $d->statusLabel(), 'replied' => $replied($d), 'at' => $d->created_at,
            ]);
        $contacts = WebsiteContact::orderByDesc('created_at')->limit(6)
            ->get(['id', 'full_name', 'school_name', 'subject', 'remark', 'created_at'])
            ->map(fn($c) => [
                'kind' => 'Contact', 'name' => $c->full_name ?: 'Someone', 'school' => $c->school_name ?: ($c->subject ?: '—'),
                'status' => $replied($c) ? 'Replied' : 'Pending', 'replied' => $replied($c), 'at' => $c->created_at,
            ]);
        $this->enquiryRecent = $demos->concat($contacts)
            ->sortByDesc(fn($r) => $r['at']?->timestamp ?? 0)
            ->take(6)
            ->map(fn($r) => array_merge($r, ['ago' => $r['at']?->diffForHumans() ?? '—', 'at' => null]))
            ->values()->all();
    }

    // ─── Support ──────────────────────────────────────────────────────────────

    private function loadSupport(): void
    {
        $total   = ContactSuperAdmin::count();
        $replied = ContactSuperAdmin::whereNotNull('super_admin_reply')
            ->where('super_admin_reply', '!=', '')
            ->count();

        $monthsActive = max(1, min(
            $this->months,
            (int) ceil(now()->diffInMonths(ContactSuperAdmin::min('created_at') ?? now()) + 1)
        ));

        $this->supportStats = [
            'total'       => $total,
            'pending'     => $total - $replied,
            'replied'     => $replied,
            'thisMonth'   => ContactSuperAdmin::where('created_at', '>=', now()->startOfMonth())->count(),
            'thisWeek'    => ContactSuperAdmin::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'avgPerMonth' => round($total / $monthsActive, 1),
        ];

        // Last month, the oldest ticket still waiting, the schools that write
        // most, the topics and the latest tickets (2026-11-19).
        $oldestPending = ContactSuperAdmin::where(fn($q) => $q->whereNull('super_admin_reply')->orWhere('super_admin_reply', ''))
            ->min('created_at');
        $topSchools = ContactSuperAdmin::query()
            ->selectRaw("organization_id, COUNT(*) as c, SUM(CASE WHEN super_admin_reply IS NULL OR super_admin_reply = '' THEN 1 ELSE 0 END) as p")
            ->groupBy('organization_id')
            ->orderByDesc('c')
            ->limit(5)
            ->get();
        $schoolNames = Organization::whereIn('id', $topSchools->pluck('organization_id')->filter())->pluck('name', 'id');
        $this->supportExtra = [
            'lastMonth'         => ContactSuperAdmin::whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->count(),
            'oldestPendingDays' => $oldestPending ? (int) floor(Carbon::parse($oldestPending)->diffInDays(now())) : 0,
            'schools'           => $topSchools->map(fn($r) => [
                'name'    => $schoolNames[$r->organization_id] ?? 'Unknown school',
                'count'   => (int) $r->c,
                'pending' => (int) $r->p,
            ])->values()->all(),
            'topics'            => ContactSuperAdmin::query()
                ->selectRaw("COALESCE(NULLIF(TRIM(topic), ''), 'Other') as t, COUNT(*) as c")
                ->groupBy('t')
                ->orderByDesc('c')
                ->limit(6)
                ->get()
                ->map(fn($r) => ['label' => \Illuminate\Support\Str::headline((string) $r->t), 'count' => (int) $r->c])
                ->values()->all(),
        ];
        $this->supportRecent = ContactSuperAdmin::with('organization:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->map(fn($t) => [
                'school'  => $t->organization?->name ?? 'Unknown school',
                'topic'   => $t->topic ? \Illuminate\Support\Str::headline((string) $t->topic) : 'Query',
                'text'    => \Illuminate\Support\Str::limit(trim(strip_tags((string) $t->admin_query)), 70),
                'replied' => trim((string) $t->super_admin_reply) !== '',
                'ago'     => $t->created_at?->diffForHumans() ?? '—',
            ])->toArray();

        $this->supportMonthly = [
            'labels'  => $this->monthLabels(),
            'total'   => $this->monthlySeries(ContactSuperAdmin::query()),
            'replied' => $this->monthlySeries(
                ContactSuperAdmin::whereNotNull('super_admin_reply')->where('super_admin_reply', '!=', '')
            ),
        ];
    }

    // ─── Calendar ─────────────────────────────────────────────────────────────

    /**
     * The signed-in person's own Calendar, as Super Admin → Calendar keeps it
     * (every row is the user's who added it — nobody else's is counted):
     * this month's meetings, tasks and events, today, the next seven days,
     * the tasks left open on earlier days, and month by month over the
     * window picked in the header. Left empty for a sub-super-admin who was
     * not given Calendar, and the section is then off the page.
     *
     * The window's rows are few (one person's diary), so they are read once
     * and counted here rather than grouped in SQL.
     */
    private function loadCalendar(): void
    {
        $this->calendarStats = $this->calendarMonthly = $this->calendarUpcoming = [];

        $user = auth()->user();
        if (! $user || ! Route::has('super-admin.calendar') || ! $user->canAccessSuperAdminRoute('super-admin.calendar')) {
            return;
        }

        $mine  = fn () => SuperAdminCalendarEvent::query()->ownedBy($user->id);
        $today = now()->toDateString();

        // The window: from the first of its first month (as monthlySeries
        // takes it) to the end of this month.
        $rows = $mine()
            ->whereDate('date', '>=', now()->subMonths($this->months - 1)->startOfMonth()->toDateString())
            ->whereDate('date', '<=', now()->endOfMonth()->toDateString())
            ->get(['type', 'date', 'is_done']);

        $ofMonth = fn (string $ym) => $rows->filter(fn ($e) => $e->date->format('Y-m') === $ym);
        $count   = fn ($set, string $type) => $set->where('type', $type)->count();

        $thisMonth = $ofMonth(now()->format('Y-m'));
        $lastMonth = $ofMonth(now()->subMonthNoOverflow()->format('Y-m'));
        $todayRows = $rows->filter(fn ($e) => $e->date->toDateString() === $today);

        $monthTasks     = $thisMonth->where('type', 'task');
        $monthTasksDone = $monthTasks->where('is_done', true)->count();

        // Tasks over the window: done, still to do (today or later) and
        // overdue (an earlier day, not done).
        $windowTasks   = $rows->where('type', 'task');
        $windowDone    = $windowTasks->where('is_done', true)->count();
        $windowOverdue = $windowTasks->filter(fn ($e) => ! $e->is_done && $e->date->toDateString() < $today)->count();

        $this->calendarStats = [
            'month'          => now()->format('M Y'),
            'meetings'       => $count($thisMonth, 'meeting'),
            'meetingsLast'   => $count($lastMonth, 'meeting'),
            'tasks'          => $monthTasks->count(),
            'tasksDone'      => $monthTasksDone,
            'tasksPct'       => $monthTasks->count() > 0 ? round($monthTasksDone / $monthTasks->count() * 100, 1) : 0,
            'events'         => $count($thisMonth, 'event'),
            'eventsLast'     => $count($lastMonth, 'event'),
            'today'          => $todayRows->count(),
            'todayMeetings'  => $count($todayRows, 'meeting'),
            'todayTasks'     => $count($todayRows, 'task'),
            'todayEvents'    => $count($todayRows, 'event'),
            // As the Calendar's "tasks still open": any earlier day, not just the window.
            'overdue'        => $mine()->where('type', 'task')->where('is_done', false)->whereDate('date', '<', $today)->count(),
            'next7'          => $mine()->whereDate('date', '>', $today)->whereDate('date', '<=', now()->addDays(7)->toDateString())->count(),
            'windowTotal'    => $rows->count(),
            'windowTasks'    => $windowTasks->count(),
            'windowDone'     => $windowDone,
            'windowOverdue'  => $windowOverdue,
            'windowToDo'     => max(0, $windowTasks->count() - $windowDone - $windowOverdue),
            'windowPct'      => $windowTasks->count() > 0 ? round($windowDone / $windowTasks->count() * 100, 1) : 0,
        ];

        $byMonth = $rows->groupBy(fn ($e) => $e->date->format('Y-m') . '|' . $e->type)->map->count();
        $series  = fn (string $type) => array_map(fn ($k) => (int) ($byMonth[$k . '|' . $type] ?? 0), $this->monthKeys());

        $this->calendarMonthly = [
            'meetings' => $series('meeting'),
            'tasks'    => $series('task'),
            'events'   => $series('event'),
        ];

        // What is coming up: today and the next seven days, tasks still to do
        // (2026-11-19).
        $this->calendarUpcoming = $mine()
            ->whereDate('date', '>=', $today)
            ->whereDate('date', '<=', now()->addDays(7)->toDateString())
            ->where(fn($q) => $q->where('type', '!=', 'task')->orWhere('is_done', false))
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(6)
            ->get(['type', 'title', 'date', 'start_time', 'with_whom', 'location'])
            ->map(function ($e) use ($today) {
                $time = null;
                if ($e->start_time) {
                    try {
                        $time = Carbon::parse($e->start_time)->format('g:i A');
                    } catch (\Throwable $ex) {
                        $time = null;
                    }
                }

                return [
                    'type'  => (string) $e->type,
                    'title' => $e->title ?: ucfirst((string) $e->type),
                    'when'  => ($e->date->toDateString() === $today ? 'Today' : $e->date->format('D, d M')) . ($time ? ' · ' . $time : ''),
                    'where' => $e->with_whom ?: $e->location,
                ];
            })->toArray();
    }

    // ─── Website traffic ──────────────────────────────────────────────────────

    private function loadWebsite(): void
    {
        // Clamp the chosen range to the last 90 days (inclusive of today).
        $earliest = now()->subDays(89)->startOfDay();

        $from = $this->visitFrom ? Carbon::parse($this->visitFrom)->startOfDay() : now()->subDays(29)->startOfDay();
        $to   = $this->visitTo   ? Carbon::parse($this->visitTo)->endOfDay()     : now()->endOfDay();

        if ($from->lt($earliest))        $from = $earliest->copy();
        if ($to->gt(now()->endOfDay()))  $to   = now()->endOfDay();
        if ($from->gt($to))              $from = $to->copy()->startOfDay();

        // Reflect the clamped values back to the date inputs.
        $this->visitFrom = $from->format('Y-m-d');
        $this->visitTo   = $to->format('Y-m-d');

        $base = fn() => WebsiteVisit::whereBetween('created_at', [$from, $to]);

        $totalVisits    = $base()->count();
        $uniqueVisitors = $base()->distinct('visitor_id')->count('visitor_id');
        $days           = $from->diffInDays($to) + 1;

        // Per-page breakdown (grouped by friendly label).
        $pages = $base()
            ->selectRaw("COALESCE(NULLIF(page, ''), path) as pg, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as uniques")
            ->groupBy('pg')
            ->orderByDesc('visits')
            ->get()
            ->map(fn($r) => [
                'page'    => $r->pg,
                'visits'  => (int) $r->visits,
                'uniques' => (int) $r->uniques,
                'pct'     => $totalVisits > 0 ? round($r->visits / $totalVisits * 100, 1) : 0,
            ])->values()->toArray();

        // Daily series for the trend chart.
        $daily = $base()
            ->selectRaw('DATE(created_at) as d, COUNT(*) as visits, COUNT(DISTINCT visitor_id) as uniques')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $labels = $visitsSeries = $uniqueSeries = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key            = $cursor->format('Y-m-d');
            $labels[]       = $cursor->format('d M');
            $visitsSeries[] = (int) ($daily[$key]->visits ?? 0);
            $uniqueSeries[] = (int) ($daily[$key]->uniques ?? 0);
            $cursor->addDay();
        }

        // The period just before, of the same length; today; the busiest day;
        // and the visits by weekday (2026-11-19).
        $wholeDays  = max(1, (int) floor($days));
        $prevVisits = WebsiteVisit::whereBetween('created_at', [$from->copy()->subDays($wholeDays), $from->copy()->subSecond()])->count();
        $weekdays   = array_fill(1, 7, 0);
        $busiest    = null;
        foreach ($daily as $d => $row) {
            $weekdays[Carbon::parse($d)->dayOfWeekIso] += (int) $row->visits;
            if (!$busiest || (int) $row->visits > $busiest['visits']) {
                $busiest = ['label' => Carbon::parse($d)->format('D, d M'), 'visits' => (int) $row->visits];
            }
        }
        $this->websiteExtra = [
            'today'        => WebsiteVisit::where('created_at', '>=', now()->startOfDay())->count(),
            'prevVisits'   => $prevVisits,
            'changePct'    => $prevVisits > 0 ? round(($totalVisits - $prevVisits) / $prevVisits * 100, 1) : null,
            'returnVisits' => max(0, $totalVisits - $uniqueVisitors),
            'perVisitor'   => $uniqueVisitors > 0 ? round($totalVisits / $uniqueVisitors, 1) : 0,
            'busiest'      => $busiest,
            'weekdays'     => array_combine(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'], array_values($weekdays)),
        ];

        $this->websiteStats = [
            'totalVisits'    => $totalVisits,
            'uniqueVisitors' => $uniqueVisitors,
            'pagesTracked'   => count($pages),
            'avgPerDay'      => $days > 0 ? (int) round($totalVisits / $days) : 0,
            'topPage'        => $pages[0]['page'] ?? '—',
            'from'           => $from->format('d M Y'),
            'to'             => $to->format('d M Y'),
            'days'           => $days,
        ];
        $this->websitePages = $pages;
        $this->websiteDaily = [
            'labels'  => $labels,
            'visits'  => $visitsSeries,
            'uniques' => $uniqueSeries,
        ];
    }

    /** Min/max the date inputs are allowed to pick (last 90 days). */
    public function getVisitMinDateProperty(): string
    {
        return now()->subDays(89)->format('Y-m-d');
    }

    public function getVisitMaxDateProperty(): string
    {
        return now()->format('Y-m-d');
    }

    /**
     * Everything the charts need, serialised into one payload the blade drops
     * into a data attribute. The frontend re-reads it after every Livewire
     * morph, so charts stay in sync when the tab or month window changes.
     */
    public function chartPayload(): array
    {
        return [
            'labels'   => $this->monthLabels(),
            'overview' => [
                'students' => $this->monthlyRegistrations['students'] ?? [],
                'teachers' => $this->monthlyRegistrations['teachers'] ?? [],
                'schools'  => $this->monthlyRegistrations['schools'] ?? [],
            ],
            'gender'   => $this->genderSplit,
            'credit'   => [
                'applications' => $this->creditMonthly['applications'] ?? [],
                'approved'     => $this->creditMonthly['approved'] ?? [],
                'amount'       => $this->creditMonthly['amount'] ?? [],
            ],
            'fee'      => [
                'count'     => $this->feeMonthly['payments'] ?? [],
                'collected' => $this->feeMonthly['collected'] ?? [],
                'payments'  => $this->feeMonthly['payments'] ?? [],
                'modes'     => $this->feeModes,
            ],
            'payroll'  => [
                'paid'  => $this->payrollMonthly['paid'] ?? [],
                'modes' => $this->payrollModes,
            ],
            'enquiry'  => [
                'demo'    => $this->enquiryMonthly['demo'] ?? [],
                'contact' => $this->enquiryMonthly['contact'] ?? [],
            ],
            'support'  => [
                'total'   => $this->supportMonthly['total'] ?? [],
                'replied' => $this->supportMonthly['replied'] ?? [],
            ],
            'calendar' => [
                'meetings' => $this->calendarMonthly['meetings'] ?? [],
                'tasks'    => $this->calendarMonthly['tasks'] ?? [],
                'events'   => $this->calendarMonthly['events'] ?? [],
            ],
            'website'  => [
                'labels'  => $this->websiteDaily['labels'] ?? [],
                'visits'  => $this->websiteDaily['visits'] ?? [],
                'uniques' => $this->websiteDaily['uniques'] ?? [],
            ],
        ];
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.analytics', [
            'chartPayload' => $this->chartPayload(),
        ]);
    }
}
