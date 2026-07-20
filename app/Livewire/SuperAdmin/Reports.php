<?php

namespace App\Livewire\SuperAdmin;

use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Organization;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\User;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use Carbon\Carbon;
use Livewire\Component;

class Reports extends Component
{
    /** 'Y-m' key of the fiscal-year month currently selected. */
    public string $selectedMonth;

    /** ['2026-04' => 'Apr 2026', ...] — the 12 months of the current fiscal year (Apr → Mar). */
    public array $monthOptions = [];

    /** Today's activity, independent of whatever month is selected below. */
    public array $today = [];

    /** All-time totals (snapshot cards). */
    public array $snapshot = [];

    /** Day-by-day rows within the selected month, oldest first. */
    public array $rows = [];

    /** Column totals across the selected month. */
    public array $totals = [];

    /** Peak revenue/fees within the month, used to scale the inline bars. */
    public array $peaks = [];

    /** Day + metric drill-down modal. */
    public bool $detailOpen = false;
    public string $detailLabel = '';
    public array $detailRows = [];

    /** Metric keys rendered in the table, in order. */
    public const METRICS = ['students', 'teachers', 'schools', 'revenue', 'fees', 'credit', 'support', 'enquiries'];

    /** Safety cap on rows loaded into a whole-month drill-down. */
    private const MONTH_DETAIL_LIMIT = 500;

    private const METRIC_LABELS = [
        'students'  => 'New Students',
        'teachers'  => 'New Teachers',
        'schools'   => 'New Schools',
        'revenue'   => 'Platform Revenue',
        'fees'      => 'Fees Collected',
        'credit'    => 'Credit Applications',
        'support'   => 'Support Tickets',
        'enquiries' => 'Enquiries',
    ];

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

        $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $end   = $start->copy()->endOfMonth();
        $label = $this->monthOptions[$this->selectedMonth] ?? $this->selectedMonth;

        $this->detailLabel = self::METRIC_LABELS[$metric] . ' — ' . $label;
        $this->detailRows  = $this->safeList(fn() => $this->fetchDetail(
            $metric,
            fn($query, string $col) => $query->whereBetween($col, [$start, $end])->limit(self::MONTH_DETAIL_LIMIT),
        ));
        $this->detailOpen  = true;
    }

    public function closeDetail(): void
    {
        $this->detailOpen = false;
    }

    private function loadData(): void
    {
        $this->loadSnapshot();
        $this->loadToday();
        $this->loadMonth();
        $this->finalise();
    }

    // ── Fiscal-year month list (Apr → Mar) ────────────────────────────────────
    private function buildMonthOptions(): array
    {
        $fyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
        $start       = Carbon::create($fyStartYear, 4, 1);

        $opts = [];
        for ($i = 0; $i < 12; $i++) {
            $m               = $start->copy()->addMonthsNoOverflow($i);
            $opts[$m->format('Y-m')] = $m->format('M Y');
        }

        return $opts;
    }

    // ── All-time snapshot ────────────────────────────────────────────────────
    private function loadSnapshot(): void
    {
        $this->snapshot = [
            'students'  => $this->safe(fn() => User::where('role', 'user')->count()),
            'teachers'  => $this->safe(fn() => User::where('role', 'teacher')->count()),
            'schools'   => $this->safe(fn() => Organization::count()),
            'revenue'   => $this->safe(fn() => SuperAdminFeePayment::paid()->sum('amount')),
            'fees'      => $this->safe(fn() => FeePayment::sum('amount')),
            'credit'    => $this->safe(fn() => CreditQuery::count()),
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
    }

    /** One day's totals across every metric — shared by "Today" and the month table. */
    private function dayTotals(string $day): array
    {
        return [
            'students'  => $this->safe(fn() => User::where('role', 'user')->whereDate('created_at', $day)->count()),
            'teachers'  => $this->safe(fn() => User::where('role', 'teacher')->whereDate('created_at', $day)->count()),
            'schools'   => $this->safe(fn() => Organization::whereDate('created_at', $day)->count()),
            'revenue'   => $this->safe(fn() => (float) SuperAdminFeePayment::paid()->whereDate('created_at', $day)->sum('amount')),
            'fees'      => $this->safe(fn() => (float) FeePayment::whereDate('payment_date', $day)->sum('amount')),
            'credit'    => $this->safe(fn() => CreditQuery::whereDate('created_at', $day)->count()),
            'support'   => $this->safe(fn() => ContactSuperAdmin::whereDate('created_at', $day)->count()),
            'enquiries' => $this->safe(fn() => WebsiteDemo::whereDate('created_at', $day)->count())
                         + $this->safe(fn() => WebsiteContact::whereDate('created_at', $day)->count()),
        ];
    }

    // ── Selected month, day by day ─────────────────────────────────────────────
    private function loadMonth(): void
    {
        $start = Carbon::createFromFormat('Y-m', $this->selectedMonth)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $data = [
            'students'  => $this->grouped(User::where('role', 'user'),    'created_at',   'COUNT(*)', $start, $end),
            'teachers'  => $this->grouped(User::where('role', 'teacher'), 'created_at',   'COUNT(*)', $start, $end),
            'schools'   => $this->grouped(Organization::query(),          'created_at',   'COUNT(*)', $start, $end),
            'revenue'   => $this->grouped(SuperAdminFeePayment::paid(),   'created_at',   'SUM(amount)', $start, $end),
            'fees'      => $this->grouped(FeePayment::query(),            'payment_date', 'SUM(amount)', $start, $end),
            'credit'    => $this->grouped(CreditQuery::query(),           'created_at',   'COUNT(*)', $start, $end),
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
    }

    // ── Aggregation helpers (one grouped query each, fail-open) ───────────────

    private function grouped($query, string $col, string $aggExpr, Carbon $start, Carbon $end): array
    {
        try {
            return $query->whereNotNull($col)
                ->whereBetween($col, [$start, $end])
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
                ->with('organization:id,name')->latest()->get()
                ->map(fn($u) => [
                    'title'    => $u->name,
                    'subtitle' => $u->organization?->name ?? 'Unknown school',
                    'meta'     => $u->email,
                    'time'     => $u->created_at?->format('h:i A'),
                ])->toArray(),

            'teachers' => $scope(User::where('role', 'teacher'), 'created_at')
                ->with('organization:id,name')->latest()->get()
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

            'revenue' => $scope(SuperAdminFeePayment::paid(), 'created_at')
                ->with('organization:id,name')->latest()->get()
                ->map(fn($p) => [
                    'title'    => $p->organization?->name ?? 'Unknown school',
                    'subtitle' => $p->payment_mode ? ucwords(str_replace('_', ' ', $p->payment_mode)) : '—',
                    'meta'     => $p->receipt_number,
                    'amount'   => (float) $p->amount,
                    'time'     => $p->created_at?->format('h:i A'),
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

            'credit' => $scope(CreditQuery::query(), 'created_at')
                ->with('organization:id,name')->latest()->get()
                ->map(fn($c) => [
                    'title'    => $c->organization?->name ?? 'Unknown school',
                    'subtitle' => $c->heading ?? ucfirst($c->status),
                    'meta'     => ucfirst($c->status),
                    'amount'   => (float) $c->amount,
                    'time'     => $c->created_at?->format('h:i A'),
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
