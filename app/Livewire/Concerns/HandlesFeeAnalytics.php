<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\Section;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Support\FeeDues;
use App\Support\TransportBilling;
use Illuminate\Support\Carbon;
use App\Support\NameOrder;

/**
 * The Fee > Analytics tab.
 *
 * Each student is counted the way their View Fee counts them
 * (HandlesStudentFeeView), so a student View Fee shows as paid up is paid up
 * here too: the academic fee is their class's active heads (whole class or
 * their section) and their own Last Year Dues, the bus fee their route's fee ×
 * the months they are billed for, on the one route View Fee reads — each net
 * of concession — against every academic payment and every bus payment
 * (transport_fee_payments, and fee_payments' transport rows), whatever class
 * the payment was booked under. This year's academic fee and the dues are
 * kept apart as FeeDues splits them.
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
    public array $analyticsMonthly   = [];   // the last six months, for the monthly chart
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

        $studentIds = $students->pluck('id')->all();

        // ── Each student's own academic rows (Last Year Dues) ────────────────
        $ownAcademic = FeeStructure::ownTotals($orgId, $studentIds);

        // ── Concessions on the base fee (penalty waivers are their own pool) ─
        $concessions = $studentIds
            ? FeeConcession::where('organization_id', $orgId)->where('is_penalty', false)
                ->whereIn('student_detail_id', $studentIds)->get()->groupBy('student_detail_id')
            : collect();

        // ── Each rider's bus fee: route fee × months billed, on the one route
        //    View Fee reads (the active one first) ───────────────────────────
        $transportPerStudent = TransportBilling::yearTotals($orgId, $studentIds);

        // ── Paid, per student — every academic payment (dues included) and
        //    every bus payment, whatever class the payment was booked under ───
        $academicPaidPerStudent = $studentIds
            ? FeePayment::where('organization_id', $orgId)->where('fee_type', 'academic')
                ->whereIn('student_detail_id', $studentIds)
                ->selectRaw('student_detail_id, SUM(amount) as total')
                ->groupBy('student_detail_id')
                ->pluck('total', 'student_detail_id')
            : collect();
        $paidAsDuesPerStudent = $studentIds && FeeDues::tracked()
            ? FeePayment::where('organization_id', $orgId)->where('fee_type', 'academic')->where('applies_to', 'dues')
                ->whereIn('student_detail_id', $studentIds)
                ->selectRaw('student_detail_id, SUM(amount) as total')
                ->groupBy('student_detail_id')
                ->pluck('total', 'student_detail_id')
            : collect();
        $transportPaidPerStudent = TransportBilling::paidByStudent($orgId, $studentIds);

        // ── Roll the per-student figures up into totals, classes and rows ────
        $standardNames = Standard::where('organization_id', $orgId)->pluck('name', 'id');
        $sectionNames  = Section::where('organization_id', $orgId)->pluck('name', 'id');

        // Each fee: what is billed (net of concession), what came in, what is left.
        $sum = ['year' => [0.0, 0.0, 0.0], 'dues' => [0.0, 0.0, 0.0], 'transport' => [0.0, 0.0, 0.0]];
        $academicPaidTotal = $transportPaidTotal = 0.0;
        $classes = [];
        $studentRows = [];

        foreach ($students as $s) {
            // As the student's View Fee works it out: the academic fee (their
            // class's heads and their Last Year Dues) and the bus fee, each net
            // of concession — this year's fee and the dues apart (FeeDues).
            $duesGross = (float) ($ownAcademic[$s->id] ?? 0);
            $acadGross = $academicFeeFor($s->standard_id, $s->section_id) + $duesGross;
            $own       = $concessions->get($s->id, collect());
            $acadOff   = FeeConcession::takeOff($own, 'academic', $acadGross, $duesGross);
            $acadNet   = max(0, $acadGross - $acadOff['taken']);
            $txGross   = (float) ($transportPerStudent[$s->id] ?? 0);
            $txNet     = max(0, $txGross - FeeConcession::takeOff($own, 'transport', $txGross)['taken']);

            $acadPaid = (float) ($academicPaidPerStudent[$s->id] ?? 0);
            $txPaid   = (float) ($transportPaidPerStudent[$s->id] ?? 0);
            $split    = FeeDues::split($acadNet, max(0, $duesGross - $acadOff['dues']), $acadPaid, (float) ($paidAsDuesPerStudent[$s->id] ?? 0));

            foreach ([
                'year'      => [$split['cycle_total'], $split['cycle_paid']],
                'dues'      => [$split['dues_total'], $split['dues_paid']],
                'transport' => [$txNet, min($txPaid, $txNet)],
            ] as $fee => [$billed, $in]) {
                $sum[$fee][0] += $billed;
                $sum[$fee][1] += $in;
                $sum[$fee][2] += max(0, $billed - $in);
            }
            $academicPaidTotal  += $acadPaid;
            $transportPaidTotal += $txPaid;

            $billable = $acadNet + $txNet;
            $paid     = $acadPaid + $txPaid;

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
                'due' => max(0, round($billable - $paid, 2)),
            ];
        }

        $penaltyCollected = $studentIds
            ? (float) FeePayment::where('organization_id', $orgId)->where('fee_type', 'penalty')
                ->whereIn('student_detail_id', $studentIds)->sum('amount')
            : 0.0;

        $totalBillable  = $sum['year'][0] + $sum['dues'][0] + $sum['transport'][0];
        $totalCollected = $academicPaidTotal + $transportPaidTotal;

        $this->analyticsSummary = [
            'students'            => $students->count(),
            'riders'              => count($transportPerStudent),
            // Academic is this year's fee and the Last Year Dues together, as
            // the admin app reads it; year_* and dues_* are each part alone.
            'academic_billable'   => $sum['year'][0] + $sum['dues'][0],
            'year_billable'       => $sum['year'][0],
            'dues_billable'       => $sum['dues'][0],
            'transport_billable'  => $sum['transport'][0],
            // Academic, transport and dues, each net of concession.
            'total_billable'      => $totalBillable,
            'academic_collected'  => $academicPaidTotal,
            'year_collected'      => $sum['year'][1],
            'dues_collected'      => $sum['dues'][1],
            'transport_collected' => $transportPaidTotal,
            'penalty_collected'   => $penaltyCollected,
            'total_collected'     => $totalCollected,
            'academic_due'        => $sum['year'][2] + $sum['dues'][2],
            'year_due'            => $sum['year'][2],
            'dues_due'            => $sum['dues'][2],
            'transport_due'       => $sum['transport'][2],
            // What every student still owes, each counted on their own.
            'total_due'           => array_sum(array_column($studentRows, 'due')),
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
        // A class reads A to Z by name; the school-wide list stays the
        // biggest balances first (it is a top-dues list).
        $this->analyticsStudentRows  = $std
            ? NameOrder::sortArray($studentRows, fn ($r) => $r['name'])
            : array_slice(array_filter($studentRows, fn ($r) => $r['due'] > 0), 0, self::ANALYTICS_TOP_DUE);

        $this->loadAnalyticsTrend();
        $this->loadAnalyticsModes();
        $this->loadAnalyticsMonthly();
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

    /**
     * Collections month by month — the last six months, this one included —
     * from the same two payment tables, for the monthly chart.
     */
    private function loadAnalyticsMonthly(): void
    {
        $first = today()->startOfMonth()->subMonthsNoOverflow(5);

        $byMonth = [];
        foreach ([$this->analyticsFeeQuery(), $this->analyticsTransportQuery()] as $query) {
            $rows = $query->where('payment_date', '>=', $first)
                ->selectRaw('payment_date, SUM(amount) as day_amount, COUNT(*) as day_count')
                ->groupBy('payment_date')
                ->get();

            foreach ($rows as $row) {
                $key = Carbon::parse($row->payment_date)->format('Y-m');
                $byMonth[$key]['amount'] = ($byMonth[$key]['amount'] ?? 0) + (float) $row->day_amount;
                $byMonth[$key]['count']  = ($byMonth[$key]['count'] ?? 0) + (int) $row->day_count;
            }
        }

        $points = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = today()->startOfMonth()->subMonthsNoOverflow($i);
            $key   = $month->format('Y-m');
            $points[] = [
                'label'  => $month->format('M'),
                'title'  => $month->format('M Y'),
                'amount' => round((float) ($byMonth[$key]['amount'] ?? 0), 2),
                'count'  => (int) ($byMonth[$key]['count'] ?? 0),
            ];
        }

        $this->analyticsMonthly = [
            'points' => $points,
            'total'  => array_sum(array_column($points, 'amount')),
            'count'  => array_sum(array_column($points, 'count')),
        ];
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
