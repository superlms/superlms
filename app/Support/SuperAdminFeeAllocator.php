<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeStructure;
use App\Services\ActivityNotifier;
use Illuminate\Support\Facades\DB;

/**
 * One amount received from a school, put against what it still owes on its
 * fee structure, in order:
 *   - Per Student: the first class's students (section by section, A–Z), then
 *     the next class's, and so on;
 *   - Monthly: April first, then May, … (a month with no fee is skipped);
 *   - One Time: the year's single fee.
 * Each student / month keeps the one payment row the Fees screen already
 * reads (amount = everything collected on it so far), so Record / Edit / Mark
 * Paid on that row keep working as before.
 */
class SuperAdminFeeAllocator
{
    /**
     * What is still due, in the order money goes against it. One item per
     * student or period with something left to pay:
     * kind, student_id, section_id, key, label, group, fee, collected, due, payment_id.
     */
    public static function dues(SuperAdminFeeStructure $structure): array
    {
        return $structure->fee_type === 'per_student'
            ? self::studentDues($structure)
            : self::periodDues($structure);
    }

    private static function studentDues(SuperAdminFeeStructure $structure): array
    {
        $orgId   = $structure->organization_id;
        $fee     = round((float) $structure->amount, 2);
        $classes = Standard::where('organization_id', $orgId)->inClassOrder()->pluck('name', 'id');
        $rank    = array_flip(array_keys($classes->all()));

        $students = StudentDetail::with('section:id,name')
            ->where('organization_id', $orgId)
            ->tap(fn ($q) => NameOrder::students($q))
            ->get(['id', 'standard_id', 'section_id', 'full_name'])
            ->all();

        // Class order, then section; A–Z inside a section stays (the sort is stable).
        usort($students, fn ($a, $b) => [$rank[$a->standard_id] ?? PHP_INT_MAX, mb_strtolower((string) $a->section?->name)]
            <=> [$rank[$b->standard_id] ?? PHP_INT_MAX, mb_strtolower((string) $b->section?->name)]);

        $payments = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->whereNotNull('student_detail_id')
            ->get(['id', 'student_detail_id', 'amount'])
            ->keyBy('student_detail_id');

        $dues = [];
        foreach ($students as $s) {
            $payment   = $payments->get($s->id);
            $collected = round((float) ($payment?->amount ?? 0), 2);
            $due       = round(max(0, $fee - $collected), 2);
            if ($due <= 0.005) {
                continue;
            }

            $dues[] = [
                'kind'       => 'student',
                'student_id' => $s->id,
                'section_id' => $s->section_id,
                'key'        => null,
                'label'      => $s->full_name ?: '—',
                'group'      => $classes[$s->standard_id] ?? 'No class',
                'fee'        => $fee,
                'collected'  => $collected,
                'due'        => $due,
                'payment_id' => $payment?->id,
            ];
        }

        return $dues;
    }

    private static function periodDues(SuperAdminFeeStructure $structure): array
    {
        $payments      = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->whereNotNull('installment_period')
            ->get(['id', 'installment_period', 'amount'])
            ->keyBy('installment_period');
        $periodAmounts = (array) ($structure->period_amounts ?? []);

        $dues = [];
        foreach ($structure->installmentPeriods() as $period) {
            // The Fees screen's own rule for a period's amount (loadInstallments).
            $fee = round(array_key_exists($period['key'], $periodAmounts)
                ? (float) $periodAmounts[$period['key']]
                : (float) $structure->amount, 2);
            $payment   = $payments->get($period['key']);
            $collected = round((float) ($payment?->amount ?? 0), 2);
            $due       = round(max(0, $fee - $collected), 2);
            if ($due <= 0.005) {
                continue;
            }

            $dues[] = [
                'kind'       => 'period',
                'student_id' => null,
                'section_id' => null,
                'key'        => $period['key'],
                'label'      => $period['label'],
                'group'      => $period['label'],
                'fee'        => $fee,
                'collected'  => $collected,
                'due'        => $due,
                'payment_id' => $payment?->id,
            ];
        }

        return $dues;
    }

    /**
     * Spread $amount over $dues in order: each item takes what it still owes
     * until the money runs out; the last one may be left part paid. Every item
     * gains take (put on it now), after (collected once saved) and full.
     */
    public static function plan(array $dues, float $amount): array
    {
        $left = round($amount, 2);
        $plan = [];

        foreach ($dues as $due) {
            if ($left <= 0.005) {
                break;
            }
            $take  = round(min($left, $due['due']), 2);
            $left  = round($left - $take, 2);
            $after = round($due['collected'] + $take, 2);

            $plan[] = $due + [
                'take'  => $take,
                'after' => $after,
                'full'  => $due['fee'] > 0 && $after + 0.01 >= $due['fee'],
            ];
        }

        return $plan;
    }

    /**
     * The plan in a few lines for the screen — per class for students
     * ("Class 1 · 30 students paid in full"), per month for periods.
     */
    public static function lines(array $plan): array
    {
        $inr   = fn ($v) => '₹' . number_format((float) $v, 0);
        $lines = [];

        foreach ($plan as $p) {
            if ($p['kind'] === 'period') {
                $lines[] = [
                    'label' => $p['label'],
                    'text'  => $p['full']
                        ? $inr($p['take']) . ' · paid in full'
                        : $inr($p['take']) . ' · ' . $inr($p['after']) . ' of ' . $inr($p['fee']) . ' paid',
                    'full'  => $p['full'],
                ];
                continue;
            }

            $last = array_key_last($lines);
            if ($last === null || $lines[$last]['label'] !== $p['group']) {
                $lines[] = ['label' => $p['group'], 'paid' => 0, 'part' => null, 'full' => true];
                $last    = array_key_last($lines);
            }
            if ($p['full']) {
                $lines[$last]['paid']++;
            } else {
                $lines[$last]['part'] = $p['label'] . ' ' . $inr($p['after']) . ' of ' . $inr($p['fee']);
                $lines[$last]['full'] = false;
            }
        }

        // Student lines read as one sentence each.
        return array_map(function ($line) {
            if (array_key_exists('paid', $line)) {
                $parts = [];
                if ($line['paid'] > 0) {
                    $parts[] = $line['paid'] . ' ' . ($line['paid'] === 1 ? 'student' : 'students') . ' paid in full';
                }
                if ($line['part']) {
                    $parts[] = 'part paid: ' . $line['part'];
                }
                $line['text'] = implode(' · ', $parts);
                unset($line['paid'], $line['part']);
            }

            return $line;
        }, $lines);
    }

    /**
     * Record $amount against the structure as planned. Payment rows are written
     * quietly (one row per student / month would otherwise send a notification
     * each) and a single "fee received" notification goes out instead. A
     * per-student school's student left part paid loses app access, as Record
     * Payment does for a part payment.
     */
    public static function apply(SuperAdminFeeStructure $structure, float $amount, string $mode, string $date, ?string $remark = null): array
    {
        $remark = trim((string) $remark);

        $plan = DB::transaction(function () use ($structure, $amount, $mode, $date, $remark) {
            $plan     = self::plan(self::dues($structure), $amount);
            $existing = SuperAdminFeePayment::whereIn('id', array_filter(array_column($plan, 'payment_id')))
                ->get()
                ->keyBy('id');
            // Receipt numbers as SuperAdminFeePayment's creating hook makes them
            // (the hook does not run while the rows are written quietly).
            $receipts = SuperAdminFeePayment::whereYear('created_at', now()->year)->count();

            SuperAdminFeePayment::withoutEvents(function () use (&$plan, $existing, &$receipts, $structure, $mode, $date, $remark) {
                foreach ($plan as $i => $p) {
                    $values = [
                        'organization_id' => $structure->organization_id,
                        'amount'          => $p['after'],
                        'academic_year'   => $structure->academic_year,
                        'payment_mode'    => $mode,
                        'payment_date'    => $date,
                        'is_paid'         => $p['full'],
                    ];
                    if ($remark !== '') {
                        $values['remark'] = $remark;
                    }

                    $row = $p['payment_id'] ? $existing->get($p['payment_id']) : null;
                    if ($row) {
                        if ($p['kind'] === 'student') {
                            $values['section_id'] = $p['section_id'];
                        }
                        $row->fill($values)->save();
                    } else {
                        $row = new SuperAdminFeePayment($values + [
                            'super_admin_fee_structure_id' => $structure->id,
                            'student_detail_id'            => $p['student_id'],
                            'installment_period'           => $p['key'],
                            'standard_id'                  => $structure->standard_id,
                            'section_id'                   => $p['section_id'],
                            'receipt_number'               => 'SARC-' . $structure->organization_id
                                . '-' . now()->year
                                . '-' . str_pad(++$receipts, 5, '0', STR_PAD_LEFT),
                        ]);
                        $row->save();
                    }
                    $plan[$i]['payment_id'] = $row->id;
                }
            });

            return $plan;
        });

        $full    = count(array_filter($plan, fn ($p) => $p['full']));
        $part    = count($plan) - $full;
        $applied = round(array_sum(array_column($plan, 'take')), 2);
        $kind    = $plan[0]['kind'] ?? 'period';

        if ($structure->fee_type === 'per_student') {
            foreach ($plan as $p) {
                if (!$p['full'] && $p['student_id'] && ($student = StudentDetail::find($p['student_id']))) {
                    FeeAccess::logoutStudent($student);
                }
            }
        }

        if ($plan) {
            $what = $kind === 'student'
                ? $full . ' ' . ($full === 1 ? 'student' : 'students') . ' paid in full'
                : $full . ' ' . ($full === 1 ? 'period' : 'periods') . ' paid in full';
            if ($part > 0) {
                $what .= ', ' . $part . ' part paid';
            }

            try {
                ActivityNotifier::toSuperAdmins(
                    'School fee payment recorded',
                    'Fee of ₹' . number_format($applied, 2) . ' received from '
                        . (Organization::find($structure->organization_id)?->name ?? 'a school')
                        . ' — ' . $what
                        . ($structure->academic_year ? " ({$structure->academic_year})" : '') . '.',
                    ['type' => 'school_fee_payment', 'id' => end($plan)['payment_id']]
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'applied' => $applied,
            'full'    => $full,
            'part'    => $part,
            'kind'    => $kind,
            'rows'    => count($plan),
        ];
    }
}
