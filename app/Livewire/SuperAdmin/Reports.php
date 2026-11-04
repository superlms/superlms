<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
use App\Models\User;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Super Admin → Reports, in the Home's look: today against yesterday, the
 * chosen month's figures against the month before (each with its days as a
 * sparkline), the money and registrations day by day as charts, where the
 * money came from (payment mode, school), support and enquiries, the
 * all-time totals and the day-by-day table (also as a CSV). Every figure
 * opens who or what made it up.
 */
class Reports extends Component
{
    /** 'Y-m' key of the fiscal-year month currently selected. */
    public string $selectedMonth;

    /** ['2026-04' => 'Apr 2026', ...] — every month from April 2026 to this one. */
    public array $monthOptions = [];

    /** Today's activity, independent of whatever month is selected below. */
    public array $today = [];

    /** Yesterday's, for Today's "vs yesterday". */
    public array $yesterday = [];

    /** All-time totals (snapshot cards). */
    public array $snapshot = [];

    /** Day-by-day rows within the selected month, oldest first. */
    public array $rows = [];

    /** Column totals across the selected month. */
    public array $totals = [];

    /** The month before over the same days (all of it for a past month), and what it is. */
    public array $prevTotals = [];
    public string $compareLabel = '';

    /** Peak revenue/fees within the month, used to scale the inline bars. */
    public array $peaks = [];

    /** Where the month's money and people came from (payment modes, schools, support, enquiries). */
    public array $breakdowns = [];

    /** The month in a few lines: active days, best days, averages. */
    public array $monthStats = [];

    /** Day + metric drill-down modal. */
    public bool $detailOpen = false;
    public string $detailLabel = '';
    public array $detailRows = [];

    /** Metric keys rendered in the table, in order (Credit is off the Super Admin, so off here too). */
    public const METRICS = ['students', 'teachers', 'schools', 'revenue', 'fees', 'support', 'enquiries'];

    /** Safety cap on rows loaded into a whole-month drill-down. */
    private const MONTH_DETAIL_LIMIT = 500;

    private const METRIC_LABELS = [
        'students'  => 'New Students',
        'teachers'  => 'New Teachers',
        'schools'   => 'New Schools',
        'revenue'   => 'Platform Revenue',
        'fees'      => 'Fees Collected',
        'support'   => 'Support Tickets',
        'enquiries' => 'Enquiries',
    ];

    /** Columns that hold a date (no time): compared as dates in every database. */
    private const DATE_ONLY = ['payment_date'];

    public function mount(): void
    {
        $this->monthOptions = $this->buildMonthOptions();

        $current             = now()->format('Y-m');
        $this->selectedMonth = array_key_exists($current, $this->monthOptions)
            ? $current
            : (array_key_first($this->monthOptions) ?? $current);

        $this->loadData();
    }

    public function setMonth(string $month): void
    {
        if (array_key_exists($month, $this->monthOptions)) {
            $this->selectedMonth = $month;
            $this->loadMonth();
            $this->finalise();
        }
    }

    /** Open the drill-down for one day + one metric — who actually did what. */
    public function openDetail(string $date, string $metric): void
    {
        if (!array_key_exists($metric, self::METRIC_LABELS)) {
            return;
        }

        $day = Carbon::parse($date);

        $this->detailLabel = self::METRIC_LABELS[$metric] . ' — ' . $day->format('d M Y');
        $this->detailRows  = $this->safeList(fn() => $this->fetchDetail(
            $metric,
            fn($query, string $col) => $query->whereDate($col, $day),
        ));
        $this->detailOpen  = true;
    }

    /**
     * Open the drill-down for one metric across the whole selected month — every
     * student / teacher / enquiry / payment etc. that made up that Month Total.
     */
    public function openMonthDetail(string $metric): void
    {
        if (!array_key_exists($metric, self::METRIC_LABELS)) {
            return;
        }

        [$start, $end] = $this->monthRange();
        $label = $this->monthOptions[$this->selectedMonth] ?? $this->selectedMonth;

        $this->detailLabel = self::METRIC_LABELS[$metric] . ' — ' . $label;
        $this->detailRows  = $this->safeList(fn() => $this->fetchDetail(
            $metric,
            fn($query, string $col) => $this->between($query, $col, $start, $end)->limit(self::MONTH_DETAIL_LIMIT),
        ));
        $this->detailOpen  = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    /** The selected month's day-by-day figures as a CSV (days still to come left out). */
    public function exportCsv()
    {
        $rows   = array_values(array_filter($this->rows, fn($r) => !$r['isFuture']));
        $totals = $this->totals;
        $name   = 'superlms-report-' . $this->selectedMonth . '.csv';

        return response()->streamDownload(function () use ($rows, $totals) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads ₹ and names right
            fputcsv($out, ['Date', 'Day', ...array_values(self::METRIC_LABELS)], ',', '"', '');
            foreach ($rows as $r) {
                fputcsv($out, [$r['date'], $r['sub'], ...array_map(fn($m) => $r[$m], self::METRICS)], ',', '"', '');
            }
            fputcsv($out, ['Total', '', ...array_map(fn($m) => $totals[$m] ?? 0, self::METRICS)], ',', '"', '');
            fclose($out);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function loadData(): void
    {
        $this->loadSnapshot();
        $this->loadToday();
        $this->loadMonth();
        $this->finalise();
    }

    /** The first month Reports offers. */
    private const FIRST_MONTH = '2026-04';

    // ── Month list: April 2026 → this month ───────────────────────────────────
    private function buildMonthOptions(): array
    {
        $month = Carbon::createFromFormat('Y-m-d', self::FIRST_MONTH . '-01')->startOfMonth();
        $last  = now()->startOfMonth();

        $opts = [];
        while ($month->lte($last)) {
            $opts[$month->format('Y-m')] = $month->format('M Y');
            $month->addMonthNoOverflow();
        }

        return $opts ?: [$last->format('Y-m') => $last->format('M Y')];
    }

    /** [first moment, last moment] of the selected month. */
    private function monthRange(): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $this->selectedMonth . '-01')->startOfMonth();

        return [$start, $start->copy()->endOfMonth()];
    }

    // ── All-time snapshot ────────────────────────────────────────────────────
    private function loadSnapshot(): void
    {
        $this->snapshot = [
            'students'  => $this->safe(fn() => User::where('role', 'user')->count()),
            'teachers'  => $this->safe(fn() => User::where('role', 'teacher')->count()),
            'schools'   => $this->safe(fn() => Organization::count()),
            'revenue'   => $this->safe(fn() => (float) SuperAdminFeeReceipt::sum('amount')),
            'fees'      => $this->safe(fn() => FeePayment::sum('amount')),
            'support'   => $this->safe(fn() => ContactSuperAdmin::count()),
            'enquiries' => $this->safe(fn() => WebsiteDemo::count())
                         + $this->safe(fn() => WebsiteContact::count()),
        ];
    }

    // ── Today ──────────────────────────────────────────────────────────────
    private function loadToday(): void
    {
        $this->today = [
            'label' => now()->format('l, d M Y'),
            'date'  => now()->toDateString(),
        ] + $this->dayTotals(now()->toDateString());

        $this->yesterday = $this->dayTotals(now()->subDay()->toDateString());
    }

    /** One day's totals across every metric — shared by "Today" and the month table. */
    private function dayTotals(string $day): array
    {
        return [
            'students'  => $this->safe(fn() => User::where('role', 'user')->whereDate('created_at', $day)->count()),
            'teachers'  => $this->safe(fn() => User::where('role', 'teacher')->whereDate('created_at', $day)->count()),
            'schools'   => $this->safe(fn() => Organization::whereDate('created_at', $day)->count()),
            // Money received on the day (every receipt, part payments too).
            'revenue'   => $this->safe(fn() => (float) SuperAdminFeeReceipt::whereDate('payment_date', $day)->sum('amount')),
            'fees'      => $this->safe(fn() => (float) FeePayment::whereDate('payment_date', $day)->sum('amount')),
            'support'   => $this->safe(fn() => ContactSuperAdmin::whereDate('created_at', $day)->count()),
            'enquiries' => $this->safe(fn() => WebsiteDemo::whereDate('created_at', $day)->count())
                         + $this->safe(fn() => WebsiteContact::whereDate('created_at', $day)->count()),
        ];
    }

    /** Every metric's total between two moments. */
    private function rangeTotals(Carbon $from, Carbon $to): array
    {
        return [
            'students'  => $this->safe(fn() => $this->between(User::where('role', 'user'), 'created_at', $from, $to)->count()),
            'teachers'  => $this->safe(fn() => $this->between(User::where('role', 'teacher'), 'created_at', $from, $to)->count()),
            'schools'   => $this->safe(fn() => $this->between(Organization::query(), 'created_at', $from, $to)->count()),
            'revenue'   => $this->safe(fn() => (float) $this->between(SuperAdminFeeReceipt::query(), 'payment_date', $from, $to)->sum('amount')),
            'fees'      => $this->safe(fn() => (float) $this->between(FeePayment::query(), 'payment_date', $from, $to)->sum('amount')),
            'support'   => $this->safe(fn() => $this->between(ContactSuperAdmin::query(), 'created_at', $from, $to)->count()),
            'enquiries' => $this->safe(fn() => $this->between(WebsiteDemo::query(), 'created_at', $from, $to)->count())
                         + $this->safe(fn() => $this->between(WebsiteContact::query(), 'created_at', $from, $to)->count()),
        ];
    }

    // ── Selected month, day by day ─────────────────────────────────────────────
    private function loadMonth(): void
    {
        [$start, $end] = $this->monthRange();

        $data = [
            'students'  => $this->grouped(User::where('role', 'user'),    'created_at',   'COUNT(*)', $start, $end),
            'teachers'  => $this->grouped(User::where('role', 'teacher'), 'created_at',   'COUNT(*)', $start, $end),
            'schools'   => $this->grouped(Organization::query(),          'created_at',   'COUNT(*)', $start, $end),
            'revenue'   => $this->grouped(SuperAdminFeeReceipt::query(),  'payment_date', 'SUM(amount)', $start, $end),
            'fees'      => $this->grouped(FeePayment::query(),            'payment_date', 'SUM(amount)', $start, $end),
            'support'   => $this->grouped(ContactSuperAdmin::query(),     'created_at',   'COUNT(*)', $start, $end),
            'enquiries' => $this->mergeCounts(
                $this->grouped(WebsiteDemo::query(),    'created_at', 'COUNT(*)', $start, $end),
                $this->grouped(WebsiteContact::query(), 'created_at', 'COUNT(*)', $start, $end),
            ),
        ];

        $today = now()->toDateString();
        $rows  = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $key = $d->format('Y-m-d');
            $row = [
                'date'     => $key,
                'label'    => $d->format('d M'),
                'sub'      => $d->format('D'),
                'isToday'  => $key === $today,
                'isFuture' => $d->isFuture(),
            ];
            foreach (self::METRICS as $m) {
                $value   = $data[$m][$key] ?? 0;
                $row[$m] = in_array($m, ['revenue', 'fees'], true) ? (float) $value : (int) $value;
            }
            $rows[] = $row;
        }

        $this->rows = $rows;

        $this->loadPrevious($start);
        $this->loadBreakdowns($start, $end);
    }

    /**
     * The month before, to set the chosen one against: for this month only
     * its first as many days as have gone by, so half a month is never put
     * against a whole one.
     */
    private function loadPrevious(Carbon $start): void
    {
        $prevStart = $start->copy()->subMonthNoOverflow()->startOfMonth();
        $prevEnd   = $prevStart->copy()->endOfMonth();

        if ($this->selectedMonth === now()->format('Y-m')) {
            $prevEnd = $prevStart->copy()->addDays(now()->day - 1)->endOfDay()->min($prevStart->copy()->endOfMonth());
            $this->compareLabel = 'vs ' . $prevStart->format('j') . '–' . $prevEnd->format('j M');
        } else {
            $this->compareLabel = 'vs ' . $prevStart->format('M Y');
        }

        $this->prevTotals = $this->rangeTotals($prevStart, $prevEnd);
    }

    /** Where the month's money and people came from. */
    private function loadBreakdowns(Carbon $start, Carbon $end): void
    {
        $bySchool = fn($query, string $col, string $agg) => $this->safeList(fn() => $this->between($query, $col, $start, $end)
            ->whereNotNull('organization_id')
            ->selectRaw("organization_id as org, $agg as agg, COUNT(*) as n")
            ->groupBy('organization_id')->orderByDesc('agg')->limit(6)->get()
            ->map(fn($r) => ['org' => (int) $r->org, 'value' => (float) $r->agg, 'count' => (int) $r->n])->all());

        $byMode = fn($query) => $this->safeList(fn() => $this->between($query, 'payment_date', $start, $end)
            ->selectRaw("COALESCE(payment_mode, 'other') as mode, SUM(amount) as agg, COUNT(*) as n")
            ->groupBy('mode')->orderByDesc('agg')->get()
            ->map(fn($r) => ['label' => self::modeLabel($r->mode), 'value' => (float) $r->agg, 'count' => (int) $r->n])->all());

        $feesBySchool     = $bySchool(FeePayment::query(), 'payment_date', 'SUM(amount)');
        $revenueBySchool  = $bySchool(SuperAdminFeeReceipt::query(), 'payment_date', 'SUM(amount)');
        $studentsBySchool = $bySchool(User::where('role', 'user'), 'created_at', 'COUNT(*)');

        $ids   = array_unique(array_column(array_merge($feesBySchool, $revenueBySchool, $studentsBySchool), 'org'));
        $names = $ids ? $this->safeList(fn() => Organization::whereIn('id', $ids)->pluck('name', 'id')->all()) : [];
        $named = fn(array $list) => array_map(fn($r) => $r + ['name' => $names[$r['org']] ?? 'School #' . $r['org']], $list);

        $tickets = fn() => $this->between(ContactSuperAdmin::query(), 'created_at', $start, $end);
        $support = [
            'total'   => $this->safe(fn() => $tickets()->count()),
            'replied' => $this->safe(fn() => $tickets()->where('super_admin_reply', true)->count()),
            'topics'  => $this->safeList(fn() => $tickets()->whereNotNull('topic')->where('topic', '!=', '')
                ->selectRaw('topic, COUNT(*) as n')->groupBy('topic')->orderByDesc('n')->limit(4)->get()
                ->map(fn($r) => ['label' => (string) $r->topic, 'count' => (int) $r->n])->all()),
        ];
        $support['pending'] = max(0, $support['total'] - $support['replied']);

        $this->breakdowns = [
            'feesByMode'       => $byMode(FeePayment::query()),
            'revenueByMode'    => $byMode(SuperAdminFeeReceipt::query()),
            'feesBySchool'     => $named($feesBySchool),
            'revenueBySchool'  => $named($revenueBySchool),
            'studentsBySchool' => $named($studentsBySchool),
            'support'          => $support,
            'enquiries'        => [
                'demo'    => $this->safe(fn() => $this->between(WebsiteDemo::query(), 'created_at', $start, $end)->count()),
                'contact' => $this->safe(fn() => $this->between(WebsiteContact::query(), 'created_at', $start, $end)->count()),
            ],
            'counts'           => [
                'receipts' => $this->safe(fn() => $this->between(SuperAdminFeeReceipt::query(), 'payment_date', $start, $end)->count()),
                'payments' => $this->safe(fn() => $this->between(FeePayment::query(), 'payment_date', $start, $end)->count()),
            ],
        ];
    }

    /** "cash" → "Cash", "bank_transfer" → "Bank Transfer", "upi" → "UPI". */
    private static function modeLabel(?string $mode): string
    {
        $mode = strtolower(trim((string) $mode));

        return match ($mode) {
            '', 'other' => 'Other',
            'upi'       => 'UPI',
            'neft', 'rtgs', 'imps' => strtoupper($mode),
            default     => ucwords(str_replace(['_', '-'], ' ', $mode)),
        };
    }

    // ── Aggregation helpers (one grouped query each, fail-open) ───────────────

    /** A date column is compared as dates, a timestamp as moments — right in MySQL and SQLite alike. */
    private function between($query, string $col, Carbon $from, Carbon $to)
    {
        return in_array($col, self::DATE_ONLY, true)
            ? $query->whereBetween($col, [$from->toDateString(), $to->toDateString()])
            : $query->whereBetween($col, [$from, $to]);
    }

    private function grouped($query, string $col, string $aggExpr, Carbon $start, Carbon $end): array
    {
        try {
            return $this->between($query->whereNotNull($col), $col, $start, $end)
                ->selectRaw("DATE($col) as bkt, $aggExpr as agg")
                ->groupBy('bkt')
                ->pluck('agg', 'bkt')
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function mergeCounts(array $a, array $b): array
    {
        foreach ($b as $k => $v) {
            $a[$k] = ($a[$k] ?? 0) + $v;
        }

        return $a;
    }

    private function finalise(): void
    {
        $this->totals = [];
        foreach (self::METRICS as $m) {
            $this->totals[$m] = array_sum(array_column($this->rows, $m));
        }

        $this->peaks = [
            'revenue' => max(1.0, (float) max(array_column($this->rows, 'revenue') ?: [0])),
            'fees'    => max(1.0, (float) max(array_column($this->rows, 'fees') ?: [0])),
        ];

        // The month in a few lines.
        $gone   = array_values(array_filter($this->rows, fn($r) => !$r['isFuture']));
        $best   = function (callable $value) use ($gone) {
            $top = null;
            foreach ($gone as $r) {
                if ($value($r) > 0 && ($top === null || $value($r) > $value($top))) {
                    $top = $r;
                }
            }

            return $top ? ['label' => $top['label'] . ' · ' . $top['sub'], 'date' => $top['date'], 'value' => $value($top)] : null;
        };
        $active = count(array_filter($gone, function ($r) {
            foreach (self::METRICS as $m) {
                if ($r[$m] > 0) {
                    return true;
                }
            }

            return false;
        }));
        $days = max(1, count($gone));

        $this->monthStats = [
            'days'        => count($this->rows),
            'daysGone'    => count($gone),
            'activeDays'  => $active,
            'bestFees'    => $best(fn($r) => (float) $r['fees']),
            'bestRevenue' => $best(fn($r) => (float) $r['revenue']),
            'busiest'     => $best(fn($r) => (int) $r['students'] + (int) $r['teachers']),
            'avgFees'     => round(($this->totals['fees'] ?? 0) / $days),
            'avgStudents' => round(($this->totals['students'] ?? 0) / $days, 1),
        ];
    }

    // ── Day + metric drill-down — who actually did what ───────────────────────

    /**
     * Resolve the record list for a metric. `$scope($query, $dateColumn)` applies
     * the date filter — a single day (day drill-down) or a whole-month range
     * (Month-Totals drill-down) — so both share this mapping.
     */
    private function fetchDetail(string $metric, \Closure $scope): array
    {
        return match ($metric) {
            'students' => $scope(User::where('role', 'user'), 'created_at')
                ->with('organization:id,name')->orderBy('name')->orderBy('id')->get()
                ->map(fn($u) => [
                    'title'    => $u->name,
                    'subtitle' => $u->organization?->name ?? 'Unknown school',
                    'meta'     => $u->email,
                    'time'     => $u->created_at?->format('h:i A'),
                ])->toArray(),

            'teachers' => $scope(User::where('role', 'teacher'), 'created_at')
                ->with('organization:id,name')->orderBy('name')->orderBy('id')->get()
                ->map(fn($u) => [
                    'title'    => $u->name,
                    'subtitle' => $u->organization?->name ?? 'Unknown school',
                    'meta'     => $u->email,
                    'time'     => $u->created_at?->format('h:i A'),
                ])->toArray(),

            'schools' => $scope(Organization::query(), 'created_at')->latest()->get()
                ->map(fn($o) => [
                    'title'    => $o->name,
                    'subtitle' => $o->education_board ?? '—',
                    'meta'     => $o->email,
                    'time'     => $o->created_at?->format('h:i A'),
                ])->toArray(),

            // One row per time money came — an "Amount Received" for many
            // students is one row, with what it covered under the school.
            'revenue' => $scope(SuperAdminFeeReceipt::query(), 'payment_date')
                ->with('organization:id,name')->latest()->latest('id')->get()
                ->map(fn($r) => [
                    'title'    => $r->organization?->name ?? 'Unknown school',
                    'subtitle' => implode(' · ', array_filter([
                        $r->payment_mode ? ucwords(str_replace('_', ' ', $r->payment_mode)) : null,
                        $r->label ?: null,
                    ])) ?: '—',
                    'meta'     => $r->receipt_number,
                    'amount'   => (float) $r->amount,
                    'time'     => $r->created_at?->format('h:i A'),
                ])->toArray(),

            'fees' => $scope(FeePayment::query(), 'payment_date')
                ->with(['organization:id,name', 'studentDetail:id,full_name'])->latest()->get()
                ->map(fn($p) => [
                    'title'    => $p->studentDetail?->full_name ?? 'Unknown student',
                    'subtitle' => $p->organization?->name ?? '—',
                    'meta'     => $p->receipt_number,
                    'amount'   => (float) $p->amount,
                    'time'     => $p->created_at?->format('h:i A'),
                ])->toArray(),

            'support' => $scope(ContactSuperAdmin::query(), 'created_at')
                ->with(['organization:id,name', 'user:id,name'])->latest()->get()
                ->map(fn($t) => [
                    'title'    => $t->user?->name ?? ($t->organization?->name ?? 'Unknown'),
                    'subtitle' => ($t->organization?->name ?? '—') . ($t->topic ? ' — ' . $t->topic : ''),
                    'meta'     => $t->super_admin_reply ? 'Replied' : 'Pending',
                    'time'     => $t->created_at?->format('h:i A'),
                ])->toArray(),

            'enquiries' => collect()
                ->merge($scope(WebsiteDemo::query(), 'created_at')->latest()->get()->map(fn($d) => [
                    'title'    => $d->full_name,
                    'subtitle' => $d->school_name ? $d->school_name . ' — Demo request' : 'Demo request',
                    'meta'     => $d->email,
                    'time'     => $d->created_at?->format('h:i A'),
                ]))
                ->merge($scope(WebsiteContact::query(), 'created_at')->latest()->get()->map(fn($c) => [
                    'title'    => $c->full_name,
                    'subtitle' => $c->subject ?? 'Contact enquiry',
                    'meta'     => $c->email,
                    'time'     => $c->created_at?->format('h:i A'),
                ]))
                ->sortByDesc('time')
                ->values()
                ->toArray(),

            default => [],
        };
    }

    private function safeList(\Closure $fn): array
    {
        try {
            return $fn() ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Run a closure, returning 0 (instead of crashing) if the table/column is absent. */
    private function safe(\Closure $fn): float|int
    {
        try {
            return $fn() ?? 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.super-admin.reports');
    }
}
