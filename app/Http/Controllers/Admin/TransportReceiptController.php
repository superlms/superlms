<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\TransportFeePayment;
use App\Support\TransportReceipt;
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

        $payment = TransportFeePayment::with(TransportReceipt::WITH)
            ->where('organization_id', $orgId)
            ->findOrFail($id);

        return view('admin.transport-receipt', TransportReceipt::data($payment));
    }
}
