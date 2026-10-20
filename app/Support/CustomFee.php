<?php

namespace App\Support;

use App\Models\Admin\Fee\CustomFeeItem;
use App\Models\Admin\Fee\CustomFeePayment;
use App\Models\Admin\Fee\CustomFeeSetting;
use App\Models\Admin\Fee\FeeCycle;
use App\Models\Organization;
use App\Models\Student\StudentDetail;
use Illuminate\Support\Facades\Schema;

/**
 * Custom Fee — a school the super admin turns it on for (Super Admin →
 * Schools → the school → Custom Fee) sets each student's fee by hand:
 * particulars and amounts on that student alone, so a parent's children can
 * all be put on one of them. The school's Fee page then has Add Fee, Submit
 * Fee and View Fee in place of its nine sections, the accounts desk a Fees
 * page with the same three, and Home and Analytics count the fee from it.
 *
 * Off for every school until turned on. Its rows live in custom_fee_items and
 * custom_fee_payments, apart from the ordinary fee tables, so a school without
 * it reads exactly what it read before — and a school that turns it off again
 * gets its ordinary fee screens back as they were.
 */
class CustomFee
{
    /** The ordinary fee screens a school on Custom Fee does not get. */
    public const STANDARD_LINKS = [
        'admin.fee-structure',
        'admin.fee-structure.pdf',
        'admin.fee-structure.transport-pdf',
        'accounts.fee-submission',
        'accounts.view-fee',
        'accounts.fee-structure',
        'accounts.fee-structure.pdf',
        'accounts.fee-structure.transport-pdf',
        'accounts.payments',
        'accounts.penalties',
        'accounts.fee-cycles',
        'accounts.fee-concessions',
    ];

    /** The screens only a school on Custom Fee gets. */
    public const CUSTOM_LINKS = [
        'accounts.custom-fee',
    ];

    /** Whether the tables are there yet — asked once a request. */
    public static function tracked(): bool
    {
        $key = 'custom_fee.tracked';
        if (!app()->bound($key)) {
            try {
                $has = Schema::hasTable('custom_fee_settings');
            } catch (\Throwable $e) {
                $has = false;
            }
            app()->instance($key, $has);
        }

        return (bool) app($key);
    }

    /** Whether a school is on Custom Fee — read once a request per school. */
    public static function enabled(?int $orgId): bool
    {
        if (!$orgId || !self::tracked()) {
            return false;
        }

        $key = 'custom_fee.enabled.' . $orgId;
        if (!app()->bound($key)) {
            app()->instance($key, (bool) CustomFeeSetting::where('organization_id', $orgId)->value('enabled'));
        }

        return (bool) app($key);
    }

    public static function setEnabled(int $orgId, bool $on, ?int $userId = null): void
    {
        if (!self::tracked()) {
            return;
        }

        CustomFeeSetting::updateOrCreate(
            ['organization_id' => $orgId],
            ['enabled' => $on, 'updated_by' => $userId]
        );
        app()->instance('custom_fee.enabled.' . $orgId, $on);
    }

    /**
     * Custom Fee's say on a menu link or route: false when the school may not
     * open it (an ordinary fee screen while it is on, a custom one while it is
     * off), null when Custom Fee has nothing to do with it.
     */
    public static function linkAllowed(?Organization $org, ?string $link): ?bool
    {
        if (!$org || !$link) {
            return null;
        }
        if (in_array($link, self::CUSTOM_LINKS, true)) {
            return self::enabled($org->id) ? null : false;
        }
        if (in_array($link, self::STANDARD_LINKS, true)) {
            return self::enabled($org->id) ? false : null;
        }

        return null;
    }

    // ── Figures ──────────────────────────────────────────────────────────────

    /** The school's students as they are now — a removed student's fee goes with them. */
    private static function currentStudents(int $orgId)
    {
        return StudentDetail::where('organization_id', $orgId)->select('id');
    }

    /** [student_detail_id => their fee, what they have paid and what is left]. */
    public static function studentTotals(int $orgId, array $studentIds): array
    {
        if (!$studentIds || !self::tracked()) {
            return [];
        }

        $fee = CustomFeeItem::where('organization_id', $orgId)->whereIn('student_detail_id', $studentIds)
            ->selectRaw('student_detail_id, SUM(amount) as total')->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');
        $paid = CustomFeePayment::where('organization_id', $orgId)->whereIn('student_detail_id', $studentIds)
            ->selectRaw('student_detail_id, SUM(amount) as total')->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');

        $out = [];
        foreach ($studentIds as $id) {
            $total = round((float) ($fee[$id] ?? 0), 2);
            $got   = round((float) ($paid[$id] ?? 0), 2);
            $out[$id] = ['total' => $total, 'paid' => $got, 'balance' => round(max(0, $total - $got), 2)];
        }

        return $out;
    }

    /** One student's fee, paid and left. */
    public static function forStudent(int $orgId, int $studentId): array
    {
        return self::studentTotals($orgId, [$studentId])[$studentId];
    }

    /** Every student's fee in the school, added up. */
    public static function schoolTotal(int $orgId): float
    {
        if (!self::tracked()) {
            return 0.0;
        }

        return round((float) CustomFeeItem::where('organization_id', $orgId)
            ->whereIn('student_detail_id', self::currentStudents($orgId))
            ->sum('amount'), 2);
    }

    /** What has been collected between two dates (both included; either may be open). */
    public static function collected(int $orgId, ?string $from = null, ?string $to = null): float
    {
        if (!self::tracked()) {
            return 0.0;
        }

        return round((float) CustomFeePayment::where('organization_id', $orgId)
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->sum('amount'), 2);
    }

    /** [Y-m-d => amount collected that day], from $from on. */
    public static function collectedByDay(int $orgId, string $from, ?string $to = null): array
    {
        if (!self::tracked()) {
            return [];
        }

        return CustomFeePayment::where('organization_id', $orgId)
            ->whereDate('payment_date', '>=', $from)
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->selectRaw('DATE(payment_date) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd')
            ->mapWithKeys(fn ($total, $d) => [(string) $d => (float) $total])
            ->all();
    }

    /**
     * Each class's fee and what has come in on it, by the class the student is
     * in now: [standard_id => ['fee' => …, 'paid' => …]].
     */
    public static function byClass(int $orgId): array
    {
        if (!self::tracked()) {
            return [];
        }

        $classOf = StudentDetail::where('organization_id', $orgId)->pluck('standard_id', 'id');
        $out     = [];

        $fee = CustomFeeItem::where('organization_id', $orgId)
            ->selectRaw('student_detail_id, SUM(amount) as total')->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');
        foreach ($fee as $studentId => $total) {
            if (($class = $classOf[$studentId] ?? null) !== null) {
                $out[$class]['fee'] = ($out[$class]['fee'] ?? 0) + (float) $total;
            }
        }

        $paid = CustomFeePayment::where('organization_id', $orgId)
            ->selectRaw('student_detail_id, SUM(amount) as total')->groupBy('student_detail_id')
            ->pluck('total', 'student_detail_id');
        foreach ($paid as $studentId => $total) {
            if (($class = $classOf[$studentId] ?? null) !== null) {
                $out[$class]['paid'] = ($out[$class]['paid'] ?? 0) + (float) $total;
            }
        }

        return $out;
    }

    /**
     * A student's fee on the school's Fee Cycle — the same split the ordinary
     * fee makes (Fee → Fee Cycle → Calculator): the Registration Fee off the
     * top, then each installment its % of what is left, the year with
     * installments latest; what they have paid goes against the earliest
     * first. Empty when the school has no academic cycle.
     *
     * @return array<int, array{label: string, span: ?string, percent: ?float, due: ?\Carbon\Carbon,
     *               amount: float, paid: float, left: float, status: string}>
     */
    public static function installments(int $orgId, float $total, float $paid): array
    {
        $cycles = FeeCycle::forOrg($orgId)->active()->where('fee_type', 'academic')->get();
        $cycles = $cycles->where('academic_year', $cycles->max('academic_year'))
            ->sortBy(fn ($c) => ($c->is_token ? '0' : '1') . str_pad((string) $c->payment_serial, 3, '0', STR_PAD_LEFT))
            ->values();
        if ($cycles->isEmpty()) {
            return [];
        }

        $registration = min($total, (float) optional($cycles->firstWhere('is_token', true))->amount);
        $base         = max(0, $total - $registration);
        $left         = $paid;
        $today        = today();
        $out          = [];

        foreach ($cycles as $c) {
            $amount  = round($c->is_token ? $registration : $base * (float) $c->fee_percent / 100, 2);
            $covered = round(min($left, $amount), 2);
            $left    = max(0, round($left - $covered, 2));
            $due     = $c->due_date ? \Carbon\Carbon::parse($c->due_date) : null;

            $status = match (true) {
                $amount <= 0                         => 'Nil',
                $covered >= $amount - 0.01           => 'Paid',
                $covered > 0                         => 'Partial',
                $due !== null && $due->lt($today)    => 'Overdue',
                default                              => 'Due',
            };

            $span = null;
            if (!$c->is_token && $c->start_date && $c->end_date) {
                $start = \Carbon\Carbon::parse($c->start_date);
                $end   = \Carbon\Carbon::parse($c->end_date);
                $span  = $start->isSameMonth($end) ? $start->format('M Y') : $start->format('M') . '–' . $end->format('M Y');
            }

            $out[] = [
                'label'   => $c->is_token ? 'Registration Fee' : 'Installment ' . $c->payment_serial,
                'span'    => $span,
                'percent' => $c->is_token ? null : (float) $c->fee_percent,
                'due'     => $due,
                'amount'  => $amount,
                'paid'    => $covered,
                'left'    => round(max(0, $amount - $covered), 2),
                'status'  => $status,
            ];
        }

        return $out;
    }

    /** The students who have a fee set and have paid nothing on it yet. */
    public static function unpaidCount(int $orgId): int
    {
        if (!self::tracked()) {
            return 0;
        }

        return StudentDetail::where('organization_id', $orgId)
            ->whereIn('id', CustomFeeItem::where('organization_id', $orgId)->where('amount', '>', 0)->select('student_detail_id'))
            ->whereNotIn('id', CustomFeePayment::where('organization_id', $orgId)->select('student_detail_id'))
            ->count();
    }
}
