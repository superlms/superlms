<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The Fee > Analytics tab.
 *
 * Billable money is counted exactly the way the Payments screen counts it
 * (see HandlesPayments::paymentHeaderStats) — academic = each class's active
 * fee heads × the students actually in that class, transport = each rider's
 * route fee × the months they are billed for — so the two screens can never
 * disagree. Collections likewise read BOTH payment tables: fee_payments
 * (academic / transport / penalty) and transport_fee_payments.
 *
 * Everything is loaded once per filter change into small, display-shaped
 * arrays; nothing here is computed per row in Blade.
 *
 * The host provides `orgId(): int` and `openStudentLedger(int $id)` — the
 * admin tab switches to View Fee, the accounts dashboard redirects to it.
 */
trait HandlesFeeAnalytics
{
    use CountsBillableMonths;

    // ─── Filters ──────────────────────────────────────────────────────────────
    public $analyticsStandardId = '';
    public $analyticsSectionId  = '';

    // ─── Loaded data ──────────────────────────────────────────────────────────
    public array $analyticsSummary   = [];   // billable / collected / due / rate
    public array $analyticsPeriods   = [];   // today … last month
    public array $analyticsDaily     = [];   // last 14 days, for the CSS bar chart
    public array $analyticsModes     = [];   // payment-mode split, biggest first
    public array $analyticsClassRows = [];   // per-class collection table
    public array $analyticsStudentRows = []; // students, or the biggest dues
    public string $analyticsStudentScope = 'top_due'; // top_due | class

    /** Students shown in the bottom table when no class is selected. */
    private const ANALYTICS_TOP_DUE = 15;

    /** Days of history the daily query pulls — covers the chart and every period. */
    private const ANALYTICS_HISTORY_DAYS = 62;

    public function updatedAnalyticsStandardId(): void
    {
        $this->analyticsSectionId = '';
        $this->loadAnalytics();
    }

    public function updatedAnalyticsSectionId(): void
    {
        $this->loadAnalytics();
    }

    public function resetAnalyticsFilters(): void
    {
        $this->analyticsStandardId = '';
        $this->analyticsSectionId  = '';
        $this->loadAnalytics();
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function loadAnalytics(): void
    {
        $orgId = $this->orgId();
        $std   = $this->analyticsStandardId ?: null;
        $sec   = $this->analyticsSectionId ?: null;

        // ── Active academic fee heads, so a student's fee is the heads that
        //    apply to their class and (optionally) their own section ──────────
        $academicHeads = FeeStructure::where('organization_id', $orgId)
            ->where('is_active', true)->where('fee_type', 'academic')
            ->when($std, fn ($q) => $q->where('standard_id', $std))
            ->get(['standard_id', 'section_id', 'amount'])
            ->groupBy('standard_id');

        $academicFeeFor = function ($standardId, $sectionId) use ($academicHeads) {
            return (float) ($academicHeads[$standardId] ?? collect())
                ->filter(fn ($h) => $h->section_id === null || (int) $h->section_id === (int) $sectionId)
                ->sum('amount');
        };

        // ── Students in scope, and how many sit in each class/section pair ───
        $students = StudentDetail::with('user:id,name')
            ->where('organization_id', $orgId)
            ->when($std, fn ($q) => $q->where('standard_id', $std))
            ->when($sec, fn ($q) => $q->where('section_id', $sec))
            ->get(['id', 'user_id', 'full_name', 'admission_no', 'standard_id', 'section_id']);

        // ── Each rider's own transport bill: route fee × months billed ───────
        $transportPerStudent = [];
        $riderRows = DB::table('transportation_students as ts')
            ->join('transportations as t', 'ts.transportation_id', '=', 't.id')
            ->join('student_details as sd', 'ts.student_detail_id', '=', 'sd.id')
            ->where('ts.organization_id', $orgId)
            ->when($std, fn ($q) => $q->where('sd.standard_id', $std))
            ->when($sec, fn ($q) => $q->where('sd.section_id', $sec))
            ->get(['ts.student_detail_id', 'ts.billable_months', 't.monthly_fee']);

        foreach ($riderRows as $row) {
            $bill = (float) $row->monthly_fee * $this->billableMonthsCount($row->billable_months);
            $transportPerStudent[$row->student_detail_id] = ($transportPerStudent[$row->student_detail_id] ?? 0) + $bill;
        }

        // ── Collected, per student, from both payment tables ─────────────────
        $feePaidPerStudent = FeePayment::where('organization_id', $orgId)
            ->when($std, fn ($q) => $q->where('standard_id', $std))
            ->when($sec, fn ($q) => $q->where('section_id', $sec))
            ->whereIn('fee_type', ['academic', 'transport'])
            ->selectRaw('student_detail_id, SUM(amount) as total')
            ->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');

        $txPaidPerStudent = TransportFeePayment::where('organization_id', $orgId)
            ->when($std || $sec, fn ($q) => $q->whereHas('studentDetail', fn ($sq) => $sq
                ->when($std, fn ($x) => $x->where('standard_id', $std))
                ->when($sec, fn ($x) => $x->where('section_id', $sec))))
            ->selectRaw('student_detail_id, SUM(amount) as total')
            ->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');

        // ── Roll the per-student figures up into totals, classes and rows ────
        $standardNames = Standard::where('organization_id', $orgId)->pluck('name', 'id');
        $sectionNames  = Section::where('organization_id', $orgId)->pluck('name', 'id');

        $academicBillable = $transportBillable = 0.0;
        $classes = [];
        $studentRows = [];

        foreach ($students as $s) {
            $acadBill = $academicFeeFor($s->standard_id, $s->section_id);
            $txBill   = (float) ($transportPerStudent[$s->id] ?? 0);
            $paid     = (float) ($feePaidPerStudent[$s->id] ?? 0) + (float) ($txPaidPerStudent[$s->id] ?? 0);
            $billable = $acadBill + $txBill;

            $academicBillable  += $acadBill;
            $transportBillable += $txBill;

            $classes[$s->standard_id] ??= [
                'id' => $s->standard_id,
                'name' => $standardNames[$s->standard_id] ?? '—',
                'students' => 0, 'billable' => 0.0, 'collected' => 0.0,
            ];
            $classes[$s->standard_id]['students']++;
            $classes[$s->standard_id]['billable']  += $billable;
            $classes[$s->standard_id]['collected'] += $paid;

            $studentRows[] = [
                'id' => $s->id,
                'name' => $s->user->name ?? $s->full_name ?? '—',
                'admission_no' => $s->admission_no,
                'class' => $standardNames[$s->standard_id] ?? '—',
                'section' => $s->section_id ? ($sectionNames[$s->section_id] ?? null) : null,
                'billable' => $billable,
                'collected' => $paid,
                'due' => max(0, $billable - $paid),
            ];
        }

        // ── Collected totals, split by head ──────────────────────────────────
        $feeByType = FeePayment::where('organization_id', $orgId)
            ->when($std, fn ($q) => $q->where('standard_id', $std))
            ->when($sec, fn ($q) => $q->where('section_id', $sec))
            ->selectRaw('fee_type, SUM(amount) as total')
            ->groupBy('fee_type')
            ->pluck('total', 'fee_type');

        $academicCollected  = (float) ($feeByType['academic'] ?? 0);
        $penaltyCollected   = (float) ($feeByType['penalty'] ?? 0);
        $transportCollected = (float) ($feeByType['transport'] ?? 0)
            + (float) $this->analyticsTransportQuery()->sum('amount');

        $totalBillable  = $academicBillable + $transportBillable;
        $totalCollected = $academicCollected + $transportCollected;

        $this->analyticsSummary = [
            'students'            => $students->count(),
            'riders'              => count($transportPerStudent),
            'academic_billable'   => $academicBillable,
            'transport_billable'  => $transportBillable,
            'total_billable'      => $totalBillable,
            'academic_collected'  => $academicCollected,
            'transport_collected' => $transportCollected,
            'penalty_collected'   => $penaltyCollected,
            'total_collected'     => $totalCollected,
            'academic_due'        => max(0, $academicBillable - $academicCollected),
            'transport_due'       => max(0, $transportBillable - $transportCollected),
            'total_due'           => max(0, $totalBillable - $totalCollected),
            'rate'                => $totalBillable > 0 ? round($totalCollected / $totalBillable * 100, 1) : 0.0,
            'fully_paid'          => count(array_filter($studentRows, fn ($r) => $r['billable'] > 0 && $r['due'] <= 0)),
            'defaulters'          => count(array_filter($studentRows, fn ($r) => $r['due'] > 0)),
        ];

        // ── Class table, worst collection rate first so problems surface ─────
        foreach ($classes as $id => $c) {
            $classes[$id]['due']  = max(0, $c['billable'] - $c['collected']);
            $classes[$id]['rate'] = $c['billable'] > 0 ? round($c['collected'] / $c['billable'] * 100, 1) : 0.0;
        }
        usort($classes, fn ($a, $b) => $a['rate'] <=> $b['rate']);
        $this->analyticsClassRows = array_values($classes);

        // ── Student table: the whole class when one is picked, else the
        //    biggest outstanding balances in the school ────────────────────────
        usort($studentRows, fn ($a, $b) => $b['due'] <=> $a['due']);
        $this->analyticsStudentScope = $std ? 'class' : 'top_due';
        $this->analyticsStudentRows  = $std
            ? $studentRows
            : array_slice(array_filter($studentRows, fn ($r) => $r['due'] > 0), 0, self::ANALYTICS_TOP_DUE);

        $this->loadAnalyticsTrend();
        $this->loadAnalyticsModes();
    }

    /** Transport-table payments under the current analytics filters. */
    private function analyticsTransportQuery()
    {
        $std = $this->analyticsStandardId ?: null;
        $sec = $this->analyticsSectionId ?: null;

        return TransportFeePayment::where('organization_id', $this->orgId())
            ->when($std || $sec, fn ($q) => $q->whereHas('studentDetail', fn ($sq) => $sq
                ->when($std, fn ($x) => $x->where('standard_id', $std))
                ->when($sec, fn ($x) => $x->where('section_id', $sec))));
    }

    /** fee_payments under the current analytics filters. */
    private function analyticsFeeQuery()
    {
        return FeePayment::where('organization_id', $this->orgId())
            ->when($this->analyticsStandardId, fn ($q) => $q->where('standard_id', $this->analyticsStandardId))
            ->when($this->analyticsSectionId, fn ($q) => $q->where('section_id', $this->analyticsSectionId));
    }

    /**
     * One grouped-by-date query per table covers both the 14-day chart and
     * every period tile, so the tab never fans out into a dozen sum()s.
     */
    private function loadAnalyticsTrend(): void
    {
        $since = today()->subDays(self::ANALYTICS_HISTORY_DAYS);

        $byDay = [];
        foreach ([$this->analyticsFeeQuery(), $this->analyticsTransportQuery()] as $query) {
            $rows = $query->where('payment_date', '>=', $since)
                ->selectRaw('payment_date, SUM(amount) as day_amount, COUNT(*) as day_count')
                ->groupBy('payment_date')
                ->get();

            foreach ($rows as $row) {
                $key = Carbon::parse($row->payment_date)->toDateString();
                $byDay[$key]['amount'] = ($byDay[$key]['amount'] ?? 0) + (float) $row->day_amount;
                $byDay[$key]['count']  = ($byDay[$key]['count'] ?? 0) + (int) $row->day_count;
            }
        }

        $sum = function (Carbon $from, Carbon $to) use ($byDay) {
            $amount = 0.0;
            $count  = 0;
            foreach ($byDay as $date => $d) {
                if ($date >= $from->toDateString() && $date <= $to->toDateString()) {
                    $amount += $d['amount'];
                    $count  += $d['count'];
                }
            }

            return ['amount' => $amount, 'count' => $count];
        };

        $today     = today();
        $lastMonth = $today->copy()->subMonthNoOverflow();

        $this->analyticsPeriods = [
            'Today'      => $sum($today, $today),
            'Yesterday'  => $sum($today->copy()->subDay(), $today->copy()->subDay()),
            'This Week'  => $sum($today->copy()->startOfWeek(), $today->copy()->endOfWeek()),
            'This Month' => $sum($today->copy()->startOfMonth(), $today->copy()->endOfMonth()),
            'Last Month' => $sum($lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()),
        ];

        $series = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $key = $day->toDateString();
            $series[] = [
                'label'  => $day->format('d'),
                'title'  => $day->format('d M Y'),
                'amount' => (float) ($byDay[$key]['amount'] ?? 0),
                'count'  => (int) ($byDay[$key]['count'] ?? 0),
            ];
        }

        $peak = max(array_column($series, 'amount')) ?: 0;
        foreach ($series as $i => $point) {
            // Keep a hairline for zero days so the axis still reads as 14 bars.
            $series[$i]['pct'] = $peak > 0 ? max(2, round($point['amount'] / $peak * 100)) : 2;
        }

        $this->analyticsDaily = [
            'points' => $series,
            'peak'   => $peak,
            'total'  => array_sum(array_column($series, 'amount')),
            'count'  => array_sum(array_column($series, 'count')),
        ];
    }

    /**
     * Payment-mode split, read from the data rather than a hard-coded list so
     * modes that only exist on one of the two tables still show up.
     */
    private function loadAnalyticsModes(): void
    {
        $modes = [];
        foreach ([$this->analyticsFeeQuery(), $this->analyticsTransportQuery()] as $query) {
            $rows = $query
                ->selectRaw('payment_mode, SUM(amount) as mode_amount, COUNT(*) as mode_count')
                ->groupBy('payment_mode')
                ->get();

            foreach ($rows as $row) {
                $key = $row->payment_mode ?: 'other';
                $modes[$key]['amount'] = ($modes[$key]['amount'] ?? 0) + (float) $row->mode_amount;
                $modes[$key]['count']  = ($modes[$key]['count'] ?? 0) + (int) $row->mode_count;
            }
        }

        $total = array_sum(array_column($modes, 'amount'));

        $rows = [];
        foreach ($modes as $key => $m) {
            $rows[] = [
                'label'  => ucwords(str_replace('_', ' ', $key)),
                'amount' => $m['amount'],
                'count'  => $m['count'],
                'pct'    => $total > 0 ? round($m['amount'] / $total * 100, 1) : 0.0,
            ];
        }
        usort($rows, fn ($a, $b) => $b['amount'] <=> $a['amount']);

        $this->analyticsModes = $rows;
    }

    /** Sections for the analytics filter bar. */
    private function analyticsViewData(): array
    {
        return [
            'analyticsSections' => $this->analyticsStandardId
                ? Section::where('organization_id', $this->orgId())
                    ->where('standard_id', $this->analyticsStandardId)
                    ->where('is_active', true)->get()
                : collect(),
        ];
    }
}
