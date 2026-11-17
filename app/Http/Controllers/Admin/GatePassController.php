<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\StudentGatePass;
use App\Support\GatePassSlip;
use Illuminate\Support\Facades\Auth;

/** More → Gatepass's slip: one of the school's passes as an A5 PDF, opened to print. */
class GatePassController extends Controller
{
    public function pdf($organization, $id)
    {
        $pass = StudentGatePass::with(GatePassSlip::WITH)
            ->where('organization_id', Auth::user()->organization_id)
            ->findOrFail((int) $id);

        return GatePassSlip::pdf($pass)->stream('gate_pass_' . $pass->number() . '.pdf');
    }
}
