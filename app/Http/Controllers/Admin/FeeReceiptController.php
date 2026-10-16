<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\TransportFeePayment;
use App\Models\User;
use App\Support\FeeCycleBreakdown;
use App\Support\FeeDues;
use App\Support\TransportBilling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeReceiptController extends Controller
{
    /**
     * The printable fee receipt: who paid, what this payment was, how the
     * year's fee is split by the school's own fee cycle, and where the student
     * stands overall. A5, one sheet.
     */
    public function show(Request $request, $organization, $id)
    {
        $orgId = Auth::user()->organization_id;

        $payment = FeePayment::with([
            'studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization',
        ])->where('organization_id', $orgId)->findOrFail($id);

        $org     = $payment->organization;
        $student = $payment->studentDetail;

        // ── What the student owes, net of concession ─────────────────────────
        $structures = FeeStructure::where('organization_id', $orgId)
            ->where('standard_id', $student->standard_id)
            ->where(function ($q) use ($student) {
                $q->where('section_id', $student->section_id)->orWhereNull('section_id');
            })
            ->where('is_active', true)
            ->get()
            // …and the student's own — Last Year Dues.
            ->concat(FeeStructure::ownRows($orgId, [$student->id], null));

        // Penalty waivers (is_penalty = true) are netted against accrued
        // penalty by FeeCycleBreakdown on its own — kept out of the base-fee
        // discount below so a penalty waiver never shrinks the fee itself.
        $concessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('is_penalty', false)
            ->get();

        $academicGross = (float) $structures->where('fee_type', 'academic')->sum('amount');
        [$route, $transportGross] = TransportBilling::forStudent($orgId, $student->id);
        // The student's own rows — Last Year Dues, which a 'dues' concession is on.
        $duesGross = (float) $structures->where('fee_type', 'academic')
            ->filter(fn ($r) => !empty($r->student_detail_id))->sum('amount');

        $academicOff  = FeeConcession::takeOff($concessions, 'academic', $academicGross, $duesGross);
        $academicNet  = round(max(0, $academicGross - $academicOff['taken']), 2);
        $transportNet = round(max(0, $transportGross - FeeConcession::takeOff($concessions, 'transport', (float) $transportGross)['taken']), 2);

        // ── What has actually come in ────────────────────────────────────────
        $academicPaid = (float) FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('fee_type', 'academic')->sum('amount');

        $transportPaid = (float) TransportFeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)->sum('amount');

        // The academic fee as this year's (the installments) and the Last Year
        // Dues — a payment taken as Dues comes off the dues alone (FeeDues), and
        // so does a concession on them.
        $dues = FeeDues::split($academicNet, max(0, $duesGross - $academicOff['dues']), $academicPaid, FeeDues::paidAsDues($orgId, $student->id))
            + ['has' => $duesGross > 0];

        // No dues: this year's fee is the whole academic fee, as it always was.
        $totals = ['academic' => $dues['has'] ? $dues['cycle_total'] : $academicNet, 'transport' => $transportNet];
        $paid   = ['academic' => $dues['has'] ? $dues['cycle_paid'] : $academicPaid, 'transport' => $transportPaid];

        $overall = [
            'total'     => round($academicNet + $transportNet, 2),
            'paid'      => round($academicPaid + $transportPaid, 2),
            'balance'   => round(max(0, ($academicNet + $transportNet) - ($academicPaid + $transportPaid)), 2),
            'academic'  => ['total' => $academicNet, 'paid' => $academicPaid],
            'transport' => ['total' => $transportNet, 'paid' => $transportPaid],
            'concession' => round(($academicGross - $academicNet) + ($transportGross - $transportNet), 2),
        ];

        return view('admin.fee-receipt', [
            'payment'     => $payment,
            'org'         => $org,
            'student'     => $student,
            'route'       => $route,
            'cycles'      => FeeCycleBreakdown::build($orgId, $student->id, $paid, $totals),
            'dues'        => $dues,
            'overall'     => $overall,
            'concessions' => $concessions,
            // fee_payments.submitted_by holds the collector's NAME today (see
            // Admin\Fee and Accounts\FeeSubmission). Rows written by older
            // builds hold a user id instead, so resolve one when that is what
            // is stored rather than printing a bare number.
            'collectedBy' => $this->collectorName($payment->submitted_by),
        ]);
    }

    /** The collector's name, whether the column holds a name or an old user id. */
    private function collectorName(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (ctype_digit((string) $value)) {
            return User::find((int) $value)?->name ?: (string) $value;
        }

        return (string) $value;
    }

}
