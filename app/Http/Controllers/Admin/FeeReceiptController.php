<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Fee\FeePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeeReceiptController extends Controller
{
    public function show(Request $request, $organization, $id)
    {
        $orgId   = Auth::user()->organization_id;
        $payment = FeePayment::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'standard', 'section', 'organization'])
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        $org = $payment->organization;

        return view('admin.fee-receipt', compact('payment', 'org'));
    }
}
