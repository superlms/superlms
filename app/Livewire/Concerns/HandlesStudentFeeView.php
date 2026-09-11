<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Models\Student\StudentDetail;
use App\Models\User;
use App\Support\FeeCycleBreakdown;
use Illuminate\Support\Facades\DB;

/**
 * One student's fee ledger, in the one shape the View Fee screen renders —
 * shared by Admin\Fee's "View Fee" tab and Accounts\ViewFee so the two can't
 * drift. The markup that reads it is `livewire.partials.student-fee-view`.
 *
 * Fees are reported net of concession: each side (academic / transport) keeps
 * its gross, what the concession took off it, and what is actually payable.
 *
 * The two sides come from different places. Academic fee is the class's
 * fee_structures rows and its payments are fee_payments. Transport fee is the
 * student's ROUTE — its monthly fee times the months that student is billed
 * for — and its payments are transport_fee_payments. Reading transport off a
 * fee_structures row (there usually isn't one) is what used to leave students
 * who ride a bus showing nothing at all here.
 *
 * The host must provide `orgId(): int`.
 */
trait HandlesStudentFeeView
{
    /**
     * Everything the View Fee screen shows for one student: who they are, the
     * academic and transport structures with their concessions applied, the
     * running totals, and every payment with its receipt.
     */
    protected function buildStudentFeeView(int $studentId): array
    {
        $student = StudentDetail::with(['standard', 'section', 'user'])->find($studentId);
        if (!$student) {
            return [];
        }

        $orgId = $this->orgId();

        // A class's structure rows: the ones pinned to this section, plus the
        // ones that apply to every section of the class.
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $payments = FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        // Penalty waivers (is_penalty = true) are a different pool entirely —
        // FeeCycleBreakdown nets them against accrued penalty on its own, so
        // they must stay out of the base-fee discount math below.
        $concessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->where('is_penalty', false)
            ->orderByDesc('created_at')
            ->get();

        $academic  = $this->feeSideFor('academic', $structures, $payments, $concessions);
        $transport = $this->transportSideFor($student, $concessions);

        // A student rides the bus if they are actually on a route — the old
        // `transportation_required` flag is not kept in step with assignments.
        $hasTransport = $transport['route'] !== null;

        $gross      = $academic['gross'] + $transport['gross'];
        $concession = $academic['concession'] + $transport['concession'];
        $net        = $academic['net'] + $transport['net'];
        $paid       = $academic['paid'] + $transport['paid'];

        $submitters = User::whereIn('id', $payments->pluck('submitted_by')
                ->merge(collect($transport['payments'])->pluck('submitted_by'))
                ->filter()->unique())
            ->pluck('name', 'id')->toArray();

        return [
            'student' => [
                'id'            => $student->id,
                'name'          => $student->user->name ?? $student->full_name ?? '—',
                'father_name'   => $student->father_name ?: '—',
                'mother_name'   => $student->mother_name ?: '—',
                'admission_no'  => $student->admission_no ?: '—',
                'roll_no'       => $student->roll_no ?: '—',
                'class_section' => ($student->standard->name ?? '—')
                    . ($student->section ? ' · ' . $student->section->name : ''),
                'phone'         => $student->phone ?: '—',
                'initial'       => strtoupper(mb_substr($student->user->name ?? 'S', 0, 1)),
            ],
            'hasTransport' => $hasTransport,
            'academic'     => $academic,
            'transport'    => $transport,
            // How the school splits the year — the same rule the receipt prints,
            // with this student's payments allocated oldest installment first.
            'cycles'       => FeeCycleBreakdown::build(
                $orgId,
                $studentId,
                ['academic' => $academic['paid'], 'transport' => $transport['paid']],
                ['academic' => $academic['net'],  'transport' => $transport['net']],
            ),
            'concessions'  => $concessions->map(fn (FeeConcession $c) => [
                'reason' => $c->reason ?: 'Concession',
                'scope'  => $c->fee_type === 'all' ? 'All fees' : ucfirst($c->fee_type),
                'value'  => $c->concession_type === 'percent'
                    ? rtrim(rtrim(number_format((float) $c->value, 2), '0'), '.') . '%'
                    : '₹' . number_format((float) $c->value, 2),
                'year'   => $c->academic_year,
                'on'     => $c->created_at?->format('d M Y'),
            ])->all(),
            'totals' => [
                'gross'      => round($gross, 2),
                'concession' => round($concession, 2),
                'net'        => round($net, 2),
                'paid'       => round($paid, 2),
                'remaining'  => round(max(0, $net - $paid), 2),
                'pct'        => $net > 0 ? min(100, round($paid / $net * 100, 1)) : ($paid > 0 ? 100 : 0),
                // Penalties and waivers only exist on the academic side.
                'penalties'  => round((float) $payments->sum('penalty_amount'), 2),
                'waivers'    => round((float) $payments->sum('waiver_amount'), 2),
            ],
            'payments' => $this->mergedPayments($payments, $transport['payments'], $submitters),
        ];
    }

    /**
     * The transport side: the student's route, the months they are billed for,
     * and what has come in against it. No route means no transport fee at all.
     */
    private function transportSideFor(StudentDetail $student, $concessions): array
    {
        $orgId = $this->orgId();

        $route = Transportation::with('driver.user:id,name')
            ->where('organization_id', $orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $student->id))
            ->orderByDesc('is_active')
            ->first();

        if (!$route) {
            return $this->emptyFeeSide();
        }

        // Which months this student is billed for — the pivot row's own flags,
        // falling back to the house default of every month but June.
        $pivot = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $route->id)
            ->where('student_detail_id', $student->id)
            ->first();

        $months  = $this->billableMonthFlags($pivot->billable_months ?? null);
        $billed  = array_keys(array_filter($months));
        $monthly = (float) $route->monthly_fee;
        $gross   = round($monthly * count($billed), 2);

        $payments = TransportFeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->orderByDesc('payment_date')->orderByDesc('id')
            ->get();

        [$taken, $applied] = $this->concessionOn('transport', $gross, $concessions);

        $net  = round(max(0, $gross - $taken), 2);
        $paid = round((float) $payments->sum('amount'), 2);

        return [
            // One row per billed month, so it reads like the academic heads do.
            'rows' => array_map(fn ($key) => [
                'fee_name' => $this->monthLabels()[$key] . ' — ' . $route->route_name,
                'amount'   => $monthly,
            ], $billed),
            'gross'      => $gross,
            'concession' => $taken,
            'applied'    => $applied,
            'net'        => $net,
            'paid'       => $paid,
            'remaining'  => round(max(0, $net - $paid), 2),
            'pct'        => $net > 0 ? min(100, round($paid / $net * 100, 1)) : ($paid > 0 ? 100 : 0),
            'route'      => [
                'id'      => $route->id,
                'name'    => $route->route_name,
                'driver'  => $route->driver->user->name ?? '—',
                'monthly' => $monthly,
                'months'  => count($billed),
            ],
            'payments'   => $payments,
        ];
    }

    /** Academic fee months, April first — the order transport is billed in. */
    private function monthLabels(): array
    {
        return [
            'apr' => 'April', 'may' => 'May', 'jun' => 'June', 'jul' => 'July',
            'aug' => 'August', 'sep' => 'September', 'oct' => 'October',
            'nov' => 'November', 'dec' => 'December', 'jan' => 'January',
            'feb' => 'February', 'mar' => 'March',
        ];
    }

    /** Stored billable_months (null, JSON or array) as flags, June off by default. */
    private function billableMonthFlags($raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $raw = (array) $raw;

        $flags = [];
        foreach (array_keys($this->monthLabels()) as $key) {
            $flags[$key] = array_key_exists($key, $raw) ? (bool) $raw[$key] : ($key !== 'jun');
        }
        return $flags;
    }

    /** Academic and transport receipts in one list, newest first. */
    private function mergedPayments($academic, $transport, array $submitters): array
    {
        $rows = $academic->map(fn (FeePayment $p) => [
            'id'             => $p->id,
            'kind'           => 'academic',
            'receipt_number' => $p->receipt_number,
            'amount'         => (float) $p->amount,
            'penalty_amount' => (float) $p->penalty_amount,
            'waiver_amount'  => (float) $p->waiver_amount,
            'fee_type'       => $p->fee_type,
            'payment_mode'   => $p->payment_mode,
            'payment_date'   => optional($p->payment_date)->format('d M Y'),
            'sort'           => optional($p->payment_date)->timestamp ?? 0,
            'collected_by'   => $submitters[$p->submitted_by] ?? '—',
            'remark'         => $p->remark,
            'is_concession'  => $p->payment_mode === 'concession',
        ])->all();

        foreach ($transport ?: [] as $p) {
            $rows[] = [
                'id'             => $p->id,
                'kind'           => 'transport',
                'receipt_number' => $p->receipt_number,
                'amount'         => (float) $p->amount,
                'penalty_amount' => 0.0,
                'waiver_amount'  => 0.0,
                'fee_type'       => 'transport',
                'payment_mode'   => $p->payment_mode,
                'payment_date'   => optional($p->payment_date)->format('d M Y'),
                'sort'           => optional($p->payment_date)->timestamp ?? 0,
                'collected_by'   => $submitters[$p->submitted_by] ?? '—',
                'remark'         => $p->remark,
                'is_concession'  => $p->payment_mode === 'concession',
            ];
        }

        usort($rows, fn ($a, $b) => $b['sort'] <=> $a['sort']);

        return $rows;
    }

    /** What a set of concessions takes off one side's gross. */
    private function concessionOn(string $type, float $gross, $concessions): array
    {
        $taken   = 0.0;
        $applied = [];

        foreach ($concessions as $c) {
            if ($c->fee_type !== 'all' && $c->fee_type !== $type) {
                continue;
            }
            $off = $c->concession_type === 'percent'
                ? $gross * ((float) $c->value) / 100
                : (float) $c->value;
            $off = round(min($off, max(0, $gross - $taken)), 2);
            if ($off <= 0) {
                continue;
            }
            $taken += $off;
            $applied[] = [
                'reason' => $c->reason ?: 'Concession',
                'label'  => $c->concession_type === 'percent'
                    ? rtrim(rtrim(number_format((float) $c->value, 2), '0'), '.') . '% off'
                    : '₹' . number_format((float) $c->value, 2) . ' off',
                'amount' => $off,
            ];
        }

        return [round($taken, 2), $applied];
    }

    /** One side of the ledger — academic or transport — with its concession taken off. */
    private function feeSideFor(string $type, $structures, $payments, $concessions): array
    {
        $rows  = $structures->where('fee_type', $type)->values();
        $gross = (float) $rows->sum('amount');

        // 'all' concessions land on both sides; the rest only on their own.
        [$taken, $applied] = $this->concessionOn($type, $gross, $concessions);

        $net  = round(max(0, $gross - $taken), 2);
        $paid = (float) $payments->where('fee_type', $type)->sum('amount');

        return [
            'rows'       => $rows->map(fn ($r) => [
                'fee_name' => $r->fee_name,
                'amount'   => (float) $r->amount,
            ])->all(),
            'gross'      => round($gross, 2),
            'concession' => round($taken, 2),
            'applied'    => $applied,
            'net'        => $net,
            'paid'       => round($paid, 2),
            'remaining'  => round(max(0, $net - $paid), 2),
            'pct'        => $net > 0 ? min(100, round($paid / $net * 100, 1)) : ($paid > 0 ? 100 : 0),
            'route'      => null,
            'payments'   => [],
        ];
    }

    private function emptyFeeSide(): array
    {
        return [
            'rows' => [], 'gross' => 0.0, 'concession' => 0.0, 'applied' => [],
            'net' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'pct' => 0,
            'route' => null, 'payments' => [],
        ];
    }
}
