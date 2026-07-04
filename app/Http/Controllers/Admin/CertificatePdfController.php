<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CertificatePdfController extends Controller
{
    /**
     * Download Achievement / Participation certificate as PDF
     * Route: GET /admin/certificates/{id}/download
     */
    public function downloadCert(int $id): Response
    {
        // Diagnostic breadcrumb: confirms the route matched and reached the
        // controller (vs. a routing / infra 404 that never gets here).
        Log::info('cert.download hit', ['id' => $id, 'user' => Auth::id(), 'org' => Auth::user()?->organization_id]);

        $cert = Certificate::with(['student', 'organization'])->find($id);
        abort_if(! $cert, 404, "Certificate #{$id} not found.");

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
        Log::info('tc.download hit', ['id' => $id, 'user' => Auth::id(), 'org' => Auth::user()?->organization_id]);

        $tc = TransferCertificate::with(['student', 'organization'])->find($id);
        abort_if(! $tc, 404, "Transfer certificate #{$id} not found.");

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