<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Fee\FeeConcession;
use App\Models\Admin\Fee\FeePayment;
use App\Models\Admin\Fee\FeeStructure;
use App\Models\Admin\Transportation;
use App\Models\Admin\TransportFeePayment;
use App\Support\FeeCycleBreakdown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeeReceiptController extends Controller
{
    /** Academic year, April first. June is the free month on transport routes. */
    private const MONTHS = ['apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec', 'jan', 'feb', 'mar'];

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
            ->get();

        // Penalty waivers (is_penalty = true) are netted against accrued
        // penalty by FeeCycleBreakdown on its own — kept out of the base-fee
        // discount below so a penalty waiver never shrinks the fee itself.
        $concessions = FeeConcession::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('is_penalty', false)
            ->get();

        $academicGross = (float) $structures->where('fee_type', 'academic')->sum('amount');
        [$route, $transportGross] = $this->transportFor($orgId, $student->id);

        $academicNet  = $this->netOf($academicGross, 'academic', $concessions);
        $transportNet = $this->netOf($transportGross, 'transport', $concessions);

        // ── What has actually come in ────────────────────────────────────────
        $academicPaid = (float) FeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where('fee_type', 'academic')->sum('amount');

        $transportPaid = (float) TransportFeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)->sum('amount');

        $totals = ['academic' => $academicNet, 'transport' => $transportNet];
        $paid   = ['academic' => $academicPaid, 'transport' => $transportPaid];

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
            'overall'     => $overall,
            'concessions' => $concessions,
            // fee_payments.submitted_by is the collector's name itself (see
            // Admin\Fee and Accounts\FeeSubmission), not a user id — it was
            // never a valid User::find() lookup, which is why this always
            // rendered as a dash.
            'collectedBy' => $payment->submitted_by ?: '—',
        ]);
    }

    /** A concession-adjusted total for one side of the ledger. */
    private function netOf(float $gross, string $type, $concessions): float
    {
        $taken = 0.0;
        foreach ($concessions as $c) {
            if ($c->fee_type !== 'all' && $c->fee_type !== $type) {
                continue;
            }
            $off = $c->concession_type === 'percent'
                ? $gross * ((float) $c->value) / 100
                : (float) $c->value;
            $taken += min($off, max(0, $gross - $taken));
        }

        return round(max(0, $gross - $taken), 2);
    }

    /**
     * The student's route and the year's transport fee — monthly fee times the
     * months they are billed for. Transport is never a fee_structures row.
     *
     * @return array{0: ?Transportation, 1: float}
     */
    private function transportFor(int $orgId, int $studentId): array
    {
        $route = Transportation::where('organization_id', $orgId)
            ->whereHas('students', fn ($q) => $q->where('student_details.id', $studentId))
            ->orderByDesc('is_active')
            ->first();

        if (!$route) {
            return [null, 0.0];
        }

        $pivot = DB::table('transportation_students')
            ->where('organization_id', $orgId)
            ->where('transportation_id', $route->id)
            ->where('student_detail_id', $studentId)
            ->first();

        $raw = $pivot->billable_months ?? null;
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        $raw = (array) $raw;

        $billed = 0;
        foreach (self::MONTHS as $key) {
            $on = array_key_exists($key, $raw) ? (bool) $raw[$key] : ($key !== 'jun');
            if ($on) {
                $billed++;
            }
        }

        return [$route, round((float) $route->monthly_fee * $billed, 2)];
    }
}
