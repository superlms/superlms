<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CertificatePdfController extends Controller
{
    /**
     * Download Achievement / Participation certificate as PDF
     * Route: GET /admin/certificates/{id}/download
     */
    public function downloadCert(int $id): Response
    {
        $cert = Certificate::with(['student', 'organization'])->findOrFail($id);

        abort_if($cert->organization_id !== Auth::user()?->organization_id, 403);

        $pdf = Pdf::loadView('pdf.admin.certificate', compact('cert'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'certificate_' . ($cert->certificate_no ?? $cert->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download Transfer Certificate as PDF
     * Route: GET /admin/tc/{id}/download
     */
    public function downloadTc(int $id): Response
    {
        $tc = TransferCertificate::with(['student', 'organization'])->findOrFail($id);

        abort_if($tc->organization_id !== Auth::user()?->organization_id, 403);

        $pdf = Pdf::loadView('pdf.admin.tc-certificate', compact('tc'))
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'TC_' . ($tc->tc_no ?? $tc->id) . '.pdf';

        return $pdf->download($filename);
    }
}