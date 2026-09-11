<?php

namespace App\Livewire\Concerns;

use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Student\StudentDetail;
use App\Models\User;

/**
 * One student's fee ledger, in the one shape the View Fee screen renders —
 * shared by Admin\Fee's "View Fee" tab and Accounts\ViewFee so the two can't
 * drift. The markup that reads it is `livewire.partials.student-fee-view`.
 *
 * Fees are reported net of concession: each side (academic / transport) keeps
 * its gross, what the concession took off it, and what is actually payable.
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

        $concessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $studentId)
            ->orderByDesc('created_at')
            ->get();

        $hasTransport = (bool) $student->transportation_required;

        $academic  = $this->feeSideFor('academic', $structures, $payments, $concessions);
        $transport = $hasTransport
            ? $this->feeSideFor('transport', $structures, $payments, $concessions)
            : $this->emptyFeeSide();

        $gross      = $academic['gross'] + $transport['gross'];
        $concession = $academic['concession'] + $transport['concession'];
        $net        = $academic['net'] + $transport['net'];
        $paid       = $academic['paid'] + $transport['paid'];

        $submitters = User::whereIn('id', $payments->pluck('submitted_by')->filter()->unique())
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
                'penalties'  => round((float) $payments->sum('penalty_amount'), 2),
                'waivers'    => round((float) $payments->sum('waiver_amount'), 2),
            ],
            'payments' => $payments->map(fn (FeePayment $p) => [
                'id'             => $p->id,
                'receipt_number' => $p->receipt_number,
                'amount'         => (float) $p->amount,
                'penalty_amount' => (float) $p->penalty_amount,
                'waiver_amount'  => (float) $p->waiver_amount,
                'fee_type'       => $p->fee_type,
                'payment_mode'   => $p->payment_mode,
                'payment_date'   => optional($p->payment_date)->format('d M Y'),
                'collected_by'   => $submitters[$p->submitted_by] ?? '—',
                'remark'         => $p->remark,
                'is_concession'  => $p->payment_mode === 'concession',
            ])->all(),
        ];
    }

    /** One side of the ledger — academic or transport — with its concession taken off. */
    private function feeSideFor(string $type, $structures, $payments, $concessions): array
    {
        $rows  = $structures->where('fee_type', $type)->values();
        $gross = (float) $rows->sum('amount');

        // 'all' concessions land on both sides; the rest only on their own.
        $taken = 0.0;
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
        ];
    }

    private function emptyFeeSide(): array
    {
        return [
            'rows' => [], 'gross' => 0.0, 'concession' => 0.0, 'applied' => [],
            'net' => 0.0, 'paid' => 0.0, 'remaining' => 0.0, 'pct' => 0,
        ];
    }
}
