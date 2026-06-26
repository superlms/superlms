<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\TransportFeePayment;
use Illuminate\Support\Facades\Auth;

class TransportReceiptController extends Controller
{
    /**
     * Printable transport fee receipt.
     * GET /{organization}/transport/receipt/{id}
     */
    public function show($organization, $id)
    {
        $orgId = Auth::user()->organization_id;

        $payment = TransportFeePayment::with([
            'organization',
            'transportation:id,route_name,pickup_time',
            'studentDetail.standard:id,name',
            'studentDetail.section:id,name',
            'submittedBy:id,name',
        ])->where('organization_id', $orgId)->findOrFail($id);

        return view('admin.transport-receipt', compact('payment'));
    }
}
