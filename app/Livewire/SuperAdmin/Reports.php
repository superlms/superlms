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
    /** '30d' = last 30 days (day-by-day) | 'monthly' = last 12 months. */
    public string $range = '30d';

    /** All-time totals (snapshot cards). */
    public array $snapshot = [];

    /** Per-period rows, newest first. */
    public array $rows = [];

    /** Column totals across the visible period. */
    public array $totals = [];

    /** Peak revenue/fees, used to scale the inline bars. */
    public array $peaks = [];

    /** Metric keys rendered in the table, in order. */
    public const METRICS = ['students', 'teachers', 'schools', 'revenue', 'fees', 'credit', 'support', 'enquiries'];

    public function mount(): void
    {
        $this->loadData();
    }

    public function setRange(string $range): void
    {
        $this->range = in_array($range, ['30d', 'monthly'], true) ? $range : '30d';
        $this->loadData();
    }

    private function loadData(): void
    {
        $this->loadSnapshot();
        $this->loadPeriod();
        $this->finalise();
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

    // ── Period breakdown (30 days or 12 months) ──────────────────────────────
    private function loadPeriod(): void
    {
        $monthly = $this->range === 'monthly';
        $unit    = $monthly ? 'MONTH' : 'DATE';
        $start   = $monthly
            ? now()->subMonths(11)->startOfMonth()
            : now()->subDays(29)->startOfDay();

        $data = [
            'students'  => $this->bucketCount(User::where('role', 'user'),    'created_at',   $unit, $start),
            'teachers'  => $this->bucketCount(User::where('role', 'teacher'), 'created_at',   $unit, $start),
            'schools'   => $this->bucketCount(Organization::query(),          'created_at',   $unit, $start),
            'revenue'   => $this->bucketSum(SuperAdminFeePayment::paid(),      'created_at',   'amount', $unit, $start),
            'fees'      => $this->bucketSum(FeePayment::query(),               'payment_date', 'amount', $unit, $start),
            'credit'    => $this->bucketCount(CreditQuery::query(),            'created_at',   $unit, $start),
            'support'   => $this->bucketCount(ContactSuperAdmin::query(),      'created_at',   $unit, $start),
            'enquiries' => $this->mergeCounts(
                $this->bucketCount(WebsiteDemo::query(),    'created_at', $unit, $start),
                $this->bucketCount(WebsiteContact::query(), 'created_at', $unit, $start),
            ),
        ];

        $rows  = [];
        $count = $monthly ? 12 : 30;
        for ($i = 0; $i < $count; $i++) {
            if ($monthly) {
                $period = now()->subMonths($i)->startOfMonth();
                $label  = $period->format('M Y');
                $sub    = '';
                $key    = $period->format('Y-m');
            } else {
                $period = now()->subDays($i)->startOfDay();
                $label  = $period->format('d M');
                $sub    = $period->format('D');
                $key    = $period->format('Y-m-d');
            }

            $row = ['label' => $label, 'sub' => $sub];
            foreach (self::METRICS as $m) {
                $value = $data[$m][$key] ?? 0;
                $row[$m] = in_array($m, ['revenue', 'fees'], true) ? (float) $value : (int) $value;
            }
            $rows[] = $row;
        }

        $this->rows = $rows; // already newest-first
    }

    // ── Aggregation helpers (one grouped query each, fail-open) ───────────────

    private function bucketCount($query, string $col, string $unit, Carbon $start): array
    {
        return $this->grouped($query, $col, 'COUNT(*)', $unit, $start);
    }

    private function bucketSum($query, string $col, string $amountCol, string $unit, Carbon $start): array
    {
        return $this->grouped($query, $col, "SUM($amountCol)", $unit, $start);
    }

    private function grouped($query, string $col, string $aggExpr, string $unit, Carbon $start): array
    {
        try {
            $bucket = $unit === 'MONTH' ? "DATE_FORMAT($col, '%Y-%m')" : "DATE($col)";

            return $query->whereNotNull($col)
                ->where($col, '>=', $start)
                ->selectRaw("$bucket as bkt, $aggExpr as agg")
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
