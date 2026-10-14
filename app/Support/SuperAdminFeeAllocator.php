<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\Student\Standard;
use App\Models\Student\StudentDetail;
use App\Models\SuperAdmin\SuperAdminFeePayment;
use App\Models\SuperAdmin\SuperAdminFeeReceipt;
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

    /**
     * The school's students in the order money goes to them: class order,
     * then section, A–Z inside a section. [$students, $classNames by id].
     */
    private static function studentsInOrder(SuperAdminFeeStructure $structure): array
    {
        $orgId   = $structure->organization_id;
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

        return [$students, $classes];
    }

    /** A period's fee: its own amount when set, else the structure's (the Fees screen's loadInstallments rule). */
    private static function periodFee(SuperAdminFeeStructure $structure, ?string $key): float
    {
        $periodAmounts = (array) ($structure->period_amounts ?? []);

        return round($key !== null && array_key_exists($key, $periodAmounts)
            ? (float) $periodAmounts[$key]
            : (float) $structure->amount, 2);
    }

    /** The full fee one payment row is for (a student's, or its period's). */
    private static function rowFee(SuperAdminFeeStructure $structure, SuperAdminFeePayment $row): float
    {
        return $row->student_detail_id
            ? round((float) $structure->amount, 2)
            : self::periodFee($structure, $row->installment_period);
    }

    private static function studentDues(SuperAdminFeeStructure $structure): array
    {
        $fee = round((float) $structure->amount, 2);
        [$students, $classes] = self::studentsInOrder($structure);

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
        $payments = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)
            ->whereNotNull('installment_period')
            ->get(['id', 'installment_period', 'amount'])
            ->keyBy('installment_period');

        $dues = [];
        foreach ($structure->installmentPeriods() as $period) {
            // The Fees screen's own rule for a period's amount (loadInstallments).
            $fee       = self::periodFee($structure, $period['key']);
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
     * Write a plan onto the payment rows, quietly: each student / month's one
     * row gets its new total, the date, mode and (when given) remark; a new row
     * gets a receipt number as SuperAdminFeePayment's creating hook makes them
     * (the hook does not run while the rows are written quietly). Returns the
     * plan with each item's payment_id and receipt_number.
     */
    private static function writePlan(SuperAdminFeeStructure $structure, array $plan, string $mode, string $date, string $remark): array
    {
        $existing = SuperAdminFeePayment::whereIn('id', array_filter(array_column($plan, 'payment_id')))
            ->get()
            ->keyBy('id');
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
                $plan[$i]['payment_id']     = $row->id;
                $plan[$i]['receipt_number'] = $row->receipt_number;
            }
        });

        return $plan;
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
            $plan = self::writePlan($structure, self::plan(self::dues($structure), $amount), $mode, $date, $remark);

            // The whole sum is one receipt on the day it came (Reports reads these),
            // with where it went, so it can be edited later (editReceipt).
            if ($plan) {
                $numbers = array_values(array_filter(array_column($plan, 'receipt_number')));
                SuperAdminFeeReceipt::create([
                    'organization_id'              => $structure->organization_id,
                    'super_admin_fee_structure_id' => $structure->id,
                    'amount'                       => round(array_sum(array_column($plan, 'take')), 2),
                    'academic_year'                => $structure->academic_year,
                    'payment_mode'                 => $mode,
                    'payment_date'                 => $date,
                    'receipt_number'               => count($numbers) > 1 ? $numbers[0] . ' – ' . end($numbers) : ($numbers[0] ?? null),
                    'label'                        => SuperAdminFeeReceipt::describe(array_column($plan, 'student_id'), array_column($plan, 'key')),
                    'items'                        => count($plan),
                    'remark'                       => $remark !== '' ? $remark : null,
                ] + (SuperAdminFeeReceipt::tracksAllocation()
                    ? ['allocation' => array_map(fn ($p) => [$p['payment_id'], $p['take']], $plan)]
                    : []));
            }

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

    /**
     * Change one payment — a receipt in the school's Payments list on Fees —
     * from the Amount Received form filled in again: its amount, date, mode
     * and remark. More money goes on as Amount Received puts it, this
     * payment's own part paid students / months first; less comes off what
     * this payment paid, last first (a payment saved before 14 Oct 2026 does
     * not know where it went, so it gives back from the last students / months
     * paid in the fee's order). The rows this payment was the last money on
     * take its new date, mode and remark. Rows are written quietly, as apply()
     * writes them, and the receipt carries the change itself, so the receipts
     * still add up to the payment rows.
     *
     * Returns ['ok' => true, 'delta' => …] or ['ok' => false, 'error' => …].
     */
    public static function editReceipt(SuperAdminFeeReceipt $receipt, float $amount, string $mode, string $date, ?string $remark = null): array
    {
        $remark    = trim((string) $remark);
        $old       = round((float) $receipt->amount, 2);
        $amount    = round($amount, 2);
        $delta     = round($amount - $old, 2);
        $oldDate   = $receipt->payment_date?->toDateString();
        $oldMode   = (string) $receipt->payment_mode;
        $oldRemark = trim((string) $receipt->remark);
        $structure = $receipt->feeStructure;

        if ($old <= 0) {
            return ['ok' => false, 'error' => 'Only money received can be edited.'];
        }
        if (abs($delta) >= 0.005 && !$structure) {
            return ['ok' => false, 'error' => "This payment's fee is no longer set, so only its date, mode and remark can change."];
        }
        if ($delta >= 0.005) {
            $due = round(array_sum(array_column(self::dues($structure), 'due')), 2);
            if ($delta > $due + 0.005) {
                return ['ok' => false, 'error' => 'More than is due — at most ₹' . number_format($old + $due, 0)
                    . ($due > 0.005 ? ' (₹' . number_format($due, 0) . ' more).' : ', the whole fee is paid.')];
            }
        }
        if ($delta <= -0.005) {
            $collected = round((float) SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)->sum('amount'), 2);
            if (-$delta > $collected + 0.005) {
                return ['ok' => false, 'error' => 'Only ₹' . number_format($collected, 0) . ' has been collected on this fee.'];
            }
        }

        $logout = [];
        $alloc  = DB::transaction(function () use ($receipt, $structure, $delta, $amount, $mode, $date, $remark, $oldDate, $oldMode, $oldRemark, &$logout) {
            // Where this payment's money is: payment id => amount, in the order it went.
            $known = is_array($receipt->allocation);
            $alloc = [];
            foreach ((array) $receipt->allocation as $item) {
                [$pid, $take] = array_values((array) $item) + [null, 0];
                if ($pid) {
                    $alloc[(int) $pid] = round(($alloc[(int) $pid] ?? 0) + (float) $take, 2);
                }
            }

            if ($delta >= 0.005) {
                foreach (self::writePlan($structure, self::plan(self::duesForEdit($receipt, $structure), $delta), $mode, $date, $remark) as $p) {
                    $alloc[$p['payment_id']] = round(($alloc[$p['payment_id']] ?? 0) + $p['take'], 2);
                    if ($p['kind'] === 'student' && !$p['full']) {
                        $logout[] = $p['student_id'];
                    }
                }
            } elseif ($delta <= -0.005) {
                $back    = round(-$delta, 2);
                $rows    = SuperAdminFeePayment::where('super_admin_fee_structure_id', $structure->id)->get()->keyBy('id');
                $changed = [];

                // From what this payment paid, last first …
                foreach (array_reverse(array_keys($alloc)) as $pid) {
                    $row = $rows->get($pid);
                    if ($back < 0.005 || !$row) {
                        continue;
                    }
                    $take = round(min($back, $alloc[$pid], (float) $row->amount), 2);
                    if ($take < 0.005) {
                        continue;
                    }
                    $row->amount   = round((float) $row->amount - $take, 2);
                    $alloc[$pid]   = round($alloc[$pid] - $take, 2);
                    $back          = round($back - $take, 2);
                    $changed[$pid] = $row;
                }
                // … and what is left (a payment from before, or rows changed since)
                // from the last students / months paid in the fee's order.
                foreach (array_reverse(self::paidRowsInOrder($structure, $rows)) as $row) {
                    if ($back < 0.005) {
                        break;
                    }
                    $take = round(min($back, (float) $row->amount), 2);
                    if ($take < 0.005) {
                        continue;
                    }
                    $row->amount       = round((float) $row->amount - $take, 2);
                    $back              = round($back - $take, 2);
                    $changed[$row->id] = $row;
                }

                SuperAdminFeePayment::withoutEvents(function () use ($changed, $structure, &$logout) {
                    foreach ($changed as $row) {
                        $fee  = self::rowFee($structure, $row);
                        $full = $fee > 0 && (float) $row->amount + 0.01 >= $fee;
                        if ((float) $row->amount < 0.005) {
                            // Nothing left on it: pending again, as a cleared payment is.
                            $row->delete();
                        } else {
                            $row->is_paid = $full;
                            $row->save();
                        }
                        if ($row->student_detail_id && !$full) {
                            $logout[] = $row->student_detail_id;
                        }
                    }
                });

                $alloc = array_filter($alloc, fn ($take) => $take >= 0.005);
            }

            // The new date, mode and remark on the rows this payment was the last money on.
            if ($oldDate && ($date !== $oldDate || $mode !== $oldMode || $remark !== $oldRemark)) {
                $rows = SuperAdminFeePayment::where('super_admin_fee_structure_id', $receipt->super_admin_fee_structure_id)
                    ->whereDate('payment_date', $oldDate)
                    // A payment from before: only when no other payment of the fee came that day.
                    ->when($known, fn ($q) => $q->whereIn('id', array_keys($alloc) ?: [0]))
                    ->when(!$known && SuperAdminFeeReceipt::where('super_admin_fee_structure_id', $receipt->super_admin_fee_structure_id)
                        ->whereDate('payment_date', $oldDate)->where('amount', '>', 0)->whereKeyNot($receipt->id)->exists(),
                        fn ($q) => $q->whereRaw('1 = 0'))
                    ->get();

                SuperAdminFeePayment::withoutEvents(function () use ($rows, $date, $mode, $remark, $oldRemark) {
                    foreach ($rows as $row) {
                        $row->payment_date = $date;
                        $row->payment_mode = $mode;
                        if ($remark !== $oldRemark && trim((string) $row->remark) === $oldRemark) {
                            $row->remark = $remark !== '' ? $remark : null;
                        }
                        $row->save();
                    }
                });
            }

            $values = [
                'amount'       => $amount,
                'payment_mode' => $mode,
                'payment_date' => $date,
                'remark'       => $remark !== '' ? $remark : null,
            ];
            if ($known && SuperAdminFeeReceipt::tracksAllocation()) {
                $values['allocation'] = array_map(fn ($pid, $take) => [$pid, $take], array_keys($alloc), array_values($alloc));

                // What it covers now: "215 students", "Apr 2026 – Jun 2026", the receipt numbers.
                if (abs($delta) >= 0.005) {
                    $byId    = SuperAdminFeePayment::whereIn('id', array_keys($alloc) ?: [0])->get(['id', 'student_detail_id', 'installment_period', 'receipt_number'])->keyBy('id');
                    $covered = array_values(array_filter(array_map(fn ($pid) => $byId->get($pid), array_keys($alloc))));
                    if ($covered) {
                        $numbers                  = array_values(array_filter(array_map(fn ($r) => $r->receipt_number, $covered)));
                        $values['items']          = count($covered);
                        $values['label']          = SuperAdminFeeReceipt::describe(
                            array_map(fn ($r) => $r->student_detail_id, $covered),
                            array_map(fn ($r) => $r->installment_period, $covered)
                        );
                        $values['receipt_number'] = count($numbers) > 1 ? $numbers[0] . ' – ' . end($numbers) : ($numbers[0] ?? $receipt->receipt_number);
                    }
                }
            }
            $receipt->update($values);

            return $alloc;
        });

        // A per-student school's student left part paid (or unpaid) loses app access.
        foreach (array_unique(array_filter($logout)) as $studentId) {
            if ($student = StudentDetail::find($studentId)) {
                FeeAccess::logoutStudent($student);
            }
        }

        if (abs($delta) >= 0.005 || $date !== $oldDate) {
            try {
                $on = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('d M Y') : '—';
                ActivityNotifier::toSuperAdmins(
                    'School fee payment edited',
                    'A payment from ' . (Organization::find($receipt->organization_id)?->name ?? 'a school')
                        . ' of ₹' . number_format($old, 2) . ' on ' . $on($oldDate)
                        . ' is now ₹' . number_format($amount, 2) . ' on ' . $on($date) . '.',
                    ['type' => 'school_fee_payment'] + ($alloc ? ['id' => array_key_last($alloc)] : [])
                );
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return ['ok' => true, 'delta' => $delta];
    }

    /**
     * What is due, in the order more money on an edited payment goes: the
     * payment's own part paid students / months first, then the fee's order
     * (usort is stable). For editReceipt and the edit panel's preview.
     */
    public static function duesForEdit(SuperAdminFeeReceipt $receipt, SuperAdminFeeStructure $structure): array
    {
        $own = [];
        foreach ((array) $receipt->allocation as $item) {
            $pid = array_values((array) $item)[0] ?? null;
            if ($pid) {
                $own[(int) $pid] = true;
            }
        }

        $dues = self::dues($structure);
        usort($dues, fn ($a, $b) => isset($own[$b['payment_id'] ?? 0]) <=> isset($own[$a['payment_id'] ?? 0]));

        return $dues;
    }

    /**
     * A fee's payment rows with money on them, in the order money goes to
     * them (studentsInOrder, or the year's periods); rows of students or
     * periods no longer there come first, so they are given back last.
     */
    private static function paidRowsInOrder(SuperAdminFeeStructure $structure, $rows): array
    {
        $rows    = collect($rows)->filter(fn ($r) => (float) $r->amount >= 0.005);
        $ordered = [];

        if ($structure->fee_type === 'per_student') {
            [$students] = self::studentsInOrder($structure);
            $byStudent  = $rows->filter(fn ($r) => $r->student_detail_id)->keyBy('student_detail_id');
            foreach ($students as $s) {
                if ($row = $byStudent->get($s->id)) {
                    $ordered[$row->id] = $row;
                }
            }
        } else {
            $byPeriod = $rows->filter(fn ($r) => $r->installment_period !== null)->keyBy('installment_period');
            foreach ($structure->installmentPeriods() as $period) {
                if ($row = $byPeriod->get($period['key'])) {
                    $ordered[$row->id] = $row;
                }
            }
        }

        $rest = $rows->reject(fn ($r) => isset($ordered[$r->id]))->values()->all();

        return array_merge($rest, array_values($ordered));
    }
}
