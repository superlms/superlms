<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\EmployeeIdCard;
use App\Models\Admin\StudentIdCard;
use App\Models\Admin\TeacherIdCard;
use App\Services\IdCardService;
use Illuminate\Support\Facades\Auth;

class IdCardPrintController extends Controller
{
    /**
     * Printable ID card (front + back) — browser print / save as PDF.
     * GET /{organization}/id-card/{type}/{id}/print
     */
    public function print($organization, $type, $id, IdCardService $service)
    {
        $orgId = Auth::user()->organization_id;

        if (!in_array($type, IdCardService::TYPES, true)) {
            abort(404);
        }

        if ($type === 'student') {
            $card = StudentIdCard::with(['studentDetail.user', 'studentDetail.standard', 'studentDetail.section', 'organization.schoolInfo'])
                ->where('organization_id', $orgId)->findOrFail($id);
            $person = $card->studentDetail;
        } elseif ($type === 'teacher') {
            $card = TeacherIdCard::with(['teacherDetail.user', 'organization.schoolInfo'])
                ->where('organization_id', $orgId)->findOrFail($id);
            $person = $card->teacherDetail;
        } else {
            $card = EmployeeIdCard::with(['adminEmployee.teacherDetail.user', 'organization.schoolInfo'])
                ->where('organization_id', $orgId)->findOrFail($id);
            $person = $card->adminEmployee;
        }

        if (!$card->qr_code && $person) {
            $qr = $service->generateQrCode($card, $person, $card->organization, $type);
            if ($qr) {
                $card->update(['qr_code' => $qr]);
            }
        }

        return view('admin.id-cards.print', [
            'c'    => $service->cardViewData($card, $type),
            'type' => $type,
        ]);
    }
}
