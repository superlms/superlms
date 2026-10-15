<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Fee\CustomFeeItem;
use App\Models\Admin\Fee\CustomFeePayment;
use App\Support\CustomFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomFeeReceiptController extends Controller
{
    /**
     * The printable receipt of a Custom Fee payment (App\Support\CustomFee):
     * who paid, this payment, the student's own fee structure in the fee
     * cycle's place, and where the fee stands overall. A5, one sheet — the
     * ordinary receipt's look.
     */
    public function show(Request $request, $organization, $id)
    {
        $orgId = (int) Auth::user()->organization_id;

        $payment = CustomFeePayment::with([
            'studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization',
        ])->where('organization_id', $orgId)->findOrFail($id);

        $student = $payment->studentDetail;
        abort_unless($student, 404);

        $items = CustomFeeItem::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)->orderBy('id')->get();

        // Paid up to and with this receipt, and everything paid till now.
        $paidThrough = (float) CustomFeePayment::where('organization_id', $orgId)
            ->where('student_detail_id', $student->id)
            ->where(fn ($q) => $q->where('payment_date', '<', $payment->payment_date)
                ->orWhere(fn ($s) => $s->where('payment_date', $payment->payment_date)->where('id', '<=', $payment->id)))
            ->sum('amount');

        return view('admin.custom-fee-receipt', [
            'payment'     => $payment,
            'org'         => $payment->organization,
            'student'     => $student,
            'items'       => $items,
            'overall'     => CustomFee::forStudent($orgId, $student->id),
            'paidThrough' => round($paidThrough, 2),
        ]);
    }
}
