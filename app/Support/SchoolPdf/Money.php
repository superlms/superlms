<?php

namespace App\Support\SchoolPdf;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeeStructure;
use App\Support\CustomFee;
use App\Support\TransportBilling;
use Illuminate\Support\Facades\DB;

/**
 * Fees (every payment of every student: how much, when, how and who took
 * it), Ledger, Payroll and Transport.
 */
class Money
{
    /**
     * Each student's whole fee, what they have paid and what is left — the
     * fee receipt's "Overall" (App\Support\StudentFeeTotals), for many
     * students at once; a Custom Fee school's own fee instead.
     *
     * @return array<int, array{total: float, paid: float, due: float, custom: bool}>
     */
    public static function positions(Ctx $c, array $ids): array
    {
        if (!$ids) {
            return [];
        }

        if (CustomFee::enabled($c->org)) {
            return array_map(fn ($t) => ['total' => $t['total'], 'paid' => $t['paid'], 'due' => $t['balance'], 'custom' => true],
                CustomFee::studentTotals($c->org, $ids));
        }

        $structures  = FeeStructure::where('organization_id', $c->org)->where('is_active', true)->get();
        $own         = FeeStructure::ownRows($c->org, $ids, null)->groupBy('student_detail_id');
        $concessions = FeeConcession::where('organization_id', $c->org)->whereIn('student_detail_id', $ids)
            ->where('is_penalty', false)->get()->groupBy('student_detail_id');
        $busGross    = TransportBilling::yearTotals($c->org, $ids);
        $busPaid     = TransportBilling::paidByStudent($c->org, $ids);
        $paid        = DB::table('fee_payments')->where('organization_id', $c->org)->whereIn('student_detail_id', $ids)
            ->where('fee_type', 'academic')->groupBy('student_detail_id')->selectRaw('student_detail_id, SUM(amount) as s')
            ->pluck('s', 'student_detail_id');

        $out = [];
        foreach ($ids as $id) {
            $s    = $c->student($id);
            $rows = $structures->filter(fn ($st) => (int) $st->standard_id === (int) $s['standard_id']
                && (is_null($st->section_id) || (int) $st->section_id === (int) $s['section_id']))
                ->concat($own[$id] ?? collect());
            $academic  = $rows->where('fee_type', 'academic');
            $gross     = (float) $academic->sum('amount');
            $duesGross = (float) $academic->filter(fn ($r) => !empty($r->student_detail_id))->sum('amount');
            $conc      = $concessions[$id] ?? collect();
            $bus       = (float) ($busGross[$id] ?? 0);

            $total = max(0, $gross - FeeConcession::takeOff($conc, 'academic', $gross, $duesGross)['taken'])
                + max(0, $bus - FeeConcession::takeOff($conc, 'transport', $bus)['taken']);
            $got = (float) ($paid[$id] ?? 0) + (float) ($busPaid[$id] ?? 0);

            $out[$id] = ['total' => round($total, 2), 'paid' => round($got, 2), 'due' => round(max(0, $total - $got), 2), 'custom' => false];
        }

        return $out;
    }

    /** Fees: every student class by class, their fee, and each payment with all its detail. */
    public static function fees(Ctx $c): array
    {
        $custom   = CustomFee::enabled($c->org);
        $students = $c->byClass(array_keys($c->students()), fn ($id) => $c->student($id)['standard_id'],
            fn ($id) => $c->student($id)['section_id'], fn ($id) => $c->student($id)['name']);
        $pos = self::positions($c, $students);

        // Every payment, one list per student: academic and bus fee (or the
        // Custom Fee), each with who took it and how.
        $payments = [];
        $add = function ($sid, array $row) use (&$payments) {
            $payments[(int) $sid][] = $row;
        };
        $collector = function ($by, $mode, $sid) use ($c) {
            if ($by === null || $by === '') {
                $student = $c->student($sid);
                $name = $student['user_id'] ? ($c->users()[$student['user_id']] ?? $student['name']) : $student['name'];

                return [strtolower((string) $mode) === 'online' ? 'Online (app)' : 'Counter', $name];
            }

            return ['Counter', $c->user($by)];
        };

        foreach (DB::table('fee_payments')->where('organization_id', $c->org)->orderBy('payment_date')->orderBy('id')->get() as $p) {
            [$via, $by] = $collector($p->submitted_by, $p->payment_mode, $p->student_detail_id);
            $add($p->student_detail_id, [
                'date' => $p->payment_date, 'id' => $p->id, 'receipt' => $p->receipt_number,
                'fee' => ($p->applies_to ?? null) === 'dues' ? 'Last Year Dues' : Ctx::headline($p->fee_type),
                'amount' => (float) $p->amount, 'discount' => (float) ($p->waiver_amount ?? 0), 'fine' => (float) ($p->penalty_amount ?? 0),
                'mode' => $p->payment_mode, 'via' => $via, 'by' => $by,
                'remark' => trim(implode(' · ', array_filter([$p->remark, $p->waiver_reason ?? null]))),
            ]);
        }
        foreach (DB::table('transport_fee_payments')->where('organization_id', $c->org)->orderBy('payment_date')->orderBy('id')->get() as $p) {
            [$via, $by] = $collector($p->submitted_by, $p->payment_mode, $p->student_detail_id);
            $add($p->student_detail_id, [
                'date' => $p->payment_date, 'id' => $p->id, 'receipt' => $p->receipt_number, 'fee' => 'Transport',
                'amount' => (float) $p->amount, 'discount' => 0, 'fine' => 0, 'mode' => $p->payment_mode, 'via' => $via, 'by' => $by,
                'remark' => (string) $p->remark,
            ]);
        }
        if (Ctx::has('custom_fee_payments')) {
            foreach (DB::table('custom_fee_payments')->where('organization_id', $c->org)->orderBy('payment_date')->orderBy('id')->get() as $p) {
                [$via, $by] = $collector($p->submitted_by ?: $p->created_by, $p->payment_mode, $p->student_detail_id);
                $add($p->student_detail_id, [
                    'date' => $p->payment_date, 'id' => $p->id, 'receipt' => $p->receipt_number, 'fee' => 'Custom Fee',
                    'amount' => (float) $p->amount, 'discount' => 0, 'fine' => 0, 'mode' => $p->payment_mode, 'via' => $via, 'by' => $by,
                    'remark' => (string) $p->remark,
                ]);
            }
        }

        $concessions = [];
        foreach (DB::table('fee_concessions')->where('organization_id', $c->org)->get() as $r) {
            $what = $r->concession_type === 'percent' ? rtrim(rtrim(number_format((float) $r->value, 2), '0'), '.') . '%' : Ctx::money($r->value);
            $on   = FeeConcession::APPLIES_TO[$r->fee_type] ?? Ctx::headline($r->fee_type);
            $concessions[(int) $r->student_detail_id][] = ($r->is_penalty ? 'Fine ' : 'Concession ') . $what . ' on ' . $on
                . ($r->reason ? ' (' . $r->reason . ')' : '');
        }

        $columns = [['Date', 'w' => 9], ['Receipt', 'w' => 10], ['Fee', 'w' => 10], ['Amount', 'w' => 9, 'align' => 'right'],
            ['Discount', 'w' => 8, 'align' => 'right'], ['Fine', 'w' => 7, 'align' => 'right'], ['Mode', 'w' => 7],
            ['Paid via', 'w' => 9], ['Received by', 'w' => 13], ['Remark', 'w' => 18]];
        $line = fn (array $p) => [
            Ctx::date($p['date']), Ctx::dash($p['receipt']), $p['fee'], Ctx::money($p['amount']),
            $p['discount'] > 0 ? Ctx::money($p['discount']) : '—', $p['fine'] > 0 ? Ctx::money($p['fine']) : '—',
            Ctx::mode($p['mode']), $p['via'], Ctx::dash($p['by']), Ctx::dash($p['remark']),
        ];

        $blocks = [];
        $group  = null;
        $byMode = [];
        $collected = $count = 0;
        foreach ($students as $id) {
            $s = $c->student($id);
            if ($s['class'] !== $group) {
                $group    = $s['class'];
                $inGroup  = count(array_filter($students, fn ($x) => $c->student($x)['class'] === $group));
                $blocks[] = ['type' => 'heading', 'text' => $group, 'meta' => $inGroup . ' ' . ($inGroup === 1 ? 'student' : 'students')];
            }
            $list = $payments[$id] ?? [];
            usort($list, fn ($a, $b) => [(string) $a['date'], $a['id']] <=> [(string) $b['date'], $b['id']]);
            foreach ($list as $p) {
                $collected += $p['amount'];
                $count++;
                $byMode[Ctx::mode($p['mode'])] = ($byMode[Ctx::mode($p['mode'])] ?? 0) + $p['amount'];
            }
            $f = $pos[$id] ?? ['total' => 0, 'paid' => 0, 'due' => 0];
            $blocks[] = [
                'type'    => 'fee',
                'title'   => $s['name'] ?: '—',
                'meta'    => implode(' · ', array_filter([
                    $s['adm'] ? 'Adm ' . $s['adm'] : null, $s['roll'] ? 'Roll ' . $s['roll'] : null,
                    $s['father'] ? 'Father ' . $s['father'] : null, $s['phone'] ?: null,
                ])),
                'figures' => [['Fee', Ctx::money($f['total'])], ['Paid', Ctx::money($f['paid'])], ['Due', Ctx::money($f['due']), $f['due'] > 0]],
                'note'    => implode(' · ', $concessions[$id] ?? []),
                'columns' => $columns,
                'rows'    => array_map($line, $list),
                'empty'   => 'No payment yet',
            ];
        }

        // Money from students no longer in the school.
        $gone = array_diff_key($payments, array_flip($students));
        if ($gone) {
            $blocks[] = ['type' => 'heading', 'text' => 'Students no longer in the school', 'meta' => count($gone) . ' ' . (count($gone) === 1 ? 'student' : 'students')];
            foreach ($gone as $id => $list) {
                foreach ($list as $p) {
                    $collected += $p['amount'];
                    $count++;
                    $byMode[Ctx::mode($p['mode'])] = ($byMode[Ctx::mode($p['mode'])] ?? 0) + $p['amount'];
                }
                $blocks[] = ['type' => 'fee', 'title' => 'Removed student #' . $id, 'meta' => '', 'figures' => [],
                    'note' => '', 'columns' => $columns, 'rows' => array_map($line, $list), 'empty' => ''];
            }
        }

        $total = array_sum(array_column($pos, 'total'));
        $due   = array_sum(array_column($pos, 'due'));
        arsort($byMode);

        return [
            'title'       => 'Fees',
            'subtitle'    => ($custom ? 'Custom Fee · ' : '') . 'Every student\'s fee and each payment — when, how much, how and who received it',
            'orientation' => 'landscape',
            'count'       => $count,
            'summary'     => array_merge([
                ['Total fee', Ctx::money($total)],
                ['Collected', Ctx::money($collected)],
                ['Due', Ctx::money($due)],
                ['Payments', number_format($count)],
            ], array_map(fn ($m, $v) => [$m, Ctx::money($v)], array_keys($byMode), array_values($byMode))),
            'blocks'      => $blocks,
        ];
    }

    /** Ledger: every credit and debit with its party and reason. */
    public static function ledger(Ctx $c): array
    {
        $rows = DB::table('ledger_transactions')->where('organization_id', $c->org)->orderBy('txn_date')->orderBy('id')->get();
        $in = $outSum = 0;
        $out = [];
        foreach ($rows as $r) {
            $credit = $r->type === 'credit';
            $credit ? $in += (float) $r->amount : $outSum += (float) $r->amount;
            $out[] = [
                Ctx::date($r->txn_date), $credit ? 'Credit' : 'Debit', Ctx::dash($r->party), Ctx::dash($r->party_to), Ctx::mode($r->mode),
                Ctx::dash($r->reason), $credit ? Ctx::money($r->amount) : '', $credit ? '' : Ctx::money($r->amount), $c->user($r->created_by),
            ];
        }

        return [
            'title'       => 'Ledger',
            'subtitle'    => 'Every credit and debit the school recorded',
            'orientation' => 'landscape',
            'count'       => count($out),
            'summary'     => [['Entries', number_format(count($out))], ['Credits', Ctx::money($in)], ['Debits', Ctx::money($outSum)], ['Balance', Ctx::money($in - $outSum)]],
            'blocks'      => [[
                'type'    => 'table',
                'columns' => [['Date', 'w' => 9], ['Type', 'w' => 6], ['Party', 'w' => 14], ['To', 'w' => 12], ['Mode', 'w' => 7],
                    ['Reason', 'w' => 24], ['Credit', 'w' => 9, 'align' => 'right'], ['Debit', 'w' => 9, 'align' => 'right'], ['Added by', 'w' => 10]],
                'rows'    => $out,
                'foot'    => ['', '', '', '', '', 'Total', Ctx::money($in), Ctx::money($outSum), ''],
            ]],
        ];
    }

    /** Payroll: every salary payment, month by month. */
    public static function payroll(Ctx $c): array
    {
        $staff = DB::table('admin_employees')->where('organization_id', $c->org)->get()->keyBy('id');
        $rows  = DB::table('admin_salary_payments')->where('organization_id', $c->org)
            ->orderByDesc('month')->orderBy('admin_employee_id')->get();

        $out = [];
        $paid = 0;
        foreach ($rows as $r) {
            $e = $staff[$r->admin_employee_id] ?? null;
            $paid += $r->status === 'paid' ? (float) $r->amount : 0;
            $month = preg_match('/^\d{4}-\d{2}$/', (string) $r->month) ? Ctx::date($r->month . '-01', 'M Y') : Ctx::dash($r->month);
            $out[] = [
                $month, Ctx::dash($e->name ?? null), Ctx::dash($e->designation ?? Ctx::headline($e->type ?? null)), Ctx::money($r->amount),
                Ctx::mode($r->payment_mode), Ctx::dash($r->paid_by), Ctx::headline($r->status), Ctx::date($r->payment_date),
                Ctx::dash($r->transaction_id), Ctx::dash($r->receipt_number), Ctx::dash($r->remark),
            ];
        }

        return [
            'title'       => 'Payroll',
            'subtitle'    => 'Every salary payment to teachers and staff',
            'orientation' => 'landscape',
            'count'       => count($out),
            'summary'     => [['Payments', number_format(count($out))], ['Paid', Ctx::money($paid)], ['People', number_format($rows->pluck('admin_employee_id')->unique()->count())]],
            'blocks'      => [[
                'type'    => 'table',
                'columns' => [['Month', 'w' => 7], ['Employee', 'w' => 13], ['Designation', 'w' => 10], ['Amount', 'w' => 8, 'align' => 'right'],
                    ['Mode', 'w' => 7], ['Paid by', 'w' => 10], ['Status', 'w' => 6], ['Paid on', 'w' => 8], ['Txn ID', 'w' => 10],
                    ['Receipt', 'w' => 9], ['Remark', 'w' => 12]],
                'rows'    => $out,
            ]],
        ];
    }

    /** Transport: the routes with driver and timing, then who rides each. */
    public static function transport(Ctx $c): array
    {
        $routes  = DB::table('transportations')->where('organization_id', $c->org)->orderBy('route_name')->get();
        $drivers = DB::table('driver_details')->where('organization_id', $c->org)->get()->keyBy('id');
        $riders  = DB::table('transportation_students')->whereIn('transportation_id', $routes->pluck('id'))->get()->groupBy('transportation_id');

        $rows   = [];
        $blocks = [];
        foreach ($routes as $r) {
            $d     = $drivers[$r->driver_detail_id] ?? null;
            $stops = json_decode((string) $r->stops, true);
            $stops = is_array($stops) ? implode(', ', array_map(fn ($s) => is_array($s) ? ($s['name'] ?? $s['stop'] ?? implode(' ', $s)) : $s, $stops)) : '';
            $rows[] = [
                Ctx::dash($r->route_name), Ctx::dash($r->vehicle_type ?: ($d->vehicle_type ?? null)),
                $d ? trim(implode("\n", array_filter([$c->user($d->user_id), $d->phone, $d->vehicle_no ? 'Vehicle ' . $d->vehicle_no : null, $d->license_no ? 'Licence ' . $d->license_no : null]))) : '—',
                Ctx::dash($r->pickup_time) . ' / ' . Ctx::dash($r->drop_time),
                Ctx::dash($r->pickup_location) . ' → ' . Ctx::dash($r->drop_location),
                $stops ?: '—', (float) $r->monthly_fee > 0 ? Ctx::money($r->monthly_fee) : '—', Ctx::dash($r->capacity),
                count($riders[$r->id] ?? []), $r->is_active ? 'Active' : 'Off',
            ];

            $list = [];
            foreach ($riders[$r->id] ?? [] as $t) {
                $s = $c->student($t->student_detail_id);
                $months = json_decode((string) $t->billable_months, true);
                $list[] = [$s['name'], $s['class'], Ctx::dash($s['adm']), Ctx::dash($s['phone']),
                    is_array($months) && $months ? implode(', ', array_map(fn ($m) => preg_match('/^\d{4}-\d{2}$/', (string) $m) ? Ctx::date($m . '-01', 'M') : $m, $months)) : 'All months'];
            }
            $list = $c->byClass($list, fn ($x) => null, fn ($x) => null, fn ($x) => $x[1] . ' ' . $x[0]);
            if ($list) {
                $blocks[] = ['type' => 'heading', 'text' => $r->route_name ?: 'Route', 'meta' => count($list) . ' ' . (count($list) === 1 ? 'student' : 'students')];
                $blocks[] = ['type' => 'table', 'columns' => [['Student', 'w' => 26], ['Class', 'w' => 14], ['Adm No', 'w' => 12], ['Phone', 'w' => 14], ['Billed months', 'w' => 34]], 'rows' => $list];
            }
        }

        array_unshift($blocks, ['type' => 'table',
            'columns' => [['Route', 'w' => 11], ['Vehicle', 'w' => 7], ['Driver', 'w' => 15], ['Pickup / Drop', 'w' => 10], ['From → To', 'w' => 14],
                ['Stops', 'w' => 18], ['Monthly fee', 'w' => 7, 'align' => 'right'], ['Seats', 'w' => 5, 'align' => 'right'],
                ['Students', 'w' => 6, 'align' => 'right'], ['Status', 'w' => 6]],
            'rows' => $rows]);

        return [
            'title'       => 'Transport',
            'subtitle'    => 'Bus routes with driver, timing and fee, and the students on each',
            'orientation' => 'landscape',
            'count'       => count($rows),
            'summary'     => [['Routes', number_format(count($rows))], ['Students', number_format($riders->flatten()->count())], ['Drivers', number_format($drivers->count())]],
            'blocks'      => $blocks,
        ];
    }
}
