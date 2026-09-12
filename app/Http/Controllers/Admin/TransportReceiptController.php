<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\TransportFeePayment;
use App\Support\TransportBilling;
use Illuminate\Support\Facades\Auth;

class TransportReceiptController extends Controller
{
    /**
     * Printable transport fee receipt — the same A5 sheet as the academic one,
     * plus where the student stands on the bus for the year.
     * GET /{organization}/transport/receipt/{id}
     */
    public function show($organization, $id)
    {
        $orgId = Auth::user()->organization_id;

        $payment = TransportFeePayment::with([
            'organization',
            'transportation:id,route_name,pickup_time,monthly_fee',
            'studentDetail.user:id,name',
            'studentDetail.standard:id,name',
            'studentDetail.section:id,name',
            'submittedBy:id,name',
        ])->where('organization_id', $orgId)->findOrFail($id);

        $student = $payment->studentDetail;

        // The route on the payment is what was actually paid for; the student's
        // current route is what the year is billed on. They are the same route
        // in every ordinary case, and the payment's wins when they are not.
        [$currentRoute, $yearTotal, $billedMonths] = $student
            ? TransportBilling::forStudent($orgId, $student->id)
            : [null, 0.0, 0];

        $route = $payment->transportation ?: $currentRoute;

        if ($route && $currentRoute && $route->id !== $currentRoute->id) {
            $billedMonths = TransportBilling::billedMonths($orgId, $route->id, $student->id);
            $yearTotal    = round((float) $route->monthly_fee * $billedMonths, 2);
        }

        $paidSoFar = $student
            ? (float) TransportFeePayment::where('organization_id', $orgId)
                ->where('student_detail_id', $student->id)
                ->when($payment->academic_year, fn ($q) => $q->where('academic_year', $payment->academic_year))
                ->sum('amount')
            : (float) $payment->amount;

        return view('admin.transport-receipt', [
            'payment'      => $payment,
            'org'          => $payment->organization,
            'student'      => $student,
            'route'        => $route,
            'yearTotal'    => $yearTotal,
            'billedMonths' => $billedMonths,
            'paidSoFar'    => $paidSoFar,
            // transport_fee_payments.submitted_by IS a user id here (unlike the
            // academic receipt, where the column holds the collector's name).
            'collectedBy'  => $payment->submittedBy->name ?? ($payment->submitted_by ?: '—'),
        ]);
    }
}
