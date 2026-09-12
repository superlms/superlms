<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin\Certificate;
use App\Models\Admin\TransferCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CertificatePdfController extends Controller
{
    /**
     * Readable "why" page instead of Laravel's opaque default 404 (whose template
     * hardcodes "NOT FOUND" and never prints the abort message). Also logs the
     * closest existing ids so we can tell a genuinely missing row apart from an
     * org mismatch / stale listing.
     */
    private function notFound(string $kind, int $id, string $model, string $table): Response
    {
        $orgId  = Auth::user()?->organization_id;
        $exists = DB::table($table)->where('id', $id)->exists();
        $latest = $model::query()->where('organization_id', $orgId)->orderByDesc('id')->limit(5)->pluck('id')->all();

        Log::warning("{$kind}.download not found", [
            'id' => $id, 'user' => Auth::id(), 'org' => $orgId,
            'row_exists_any_org' => $exists, 'recent_ids_this_org' => $latest,
        ]);

        $reason = $exists
            ? "This {$kind} exists but belongs to a different school, so it can't be opened from here."
            : "This {$kind} (#{$id}) no longer exists — it may have been deleted, or the list you opened it from is out of date.";

        return response(
            '<!doctype html><meta charset="utf-8"><title>Not available</title>'
            . '<div style="font-family:system-ui,Segoe UI,Arial;max-width:520px;margin:12vh auto;text-align:center;color:#374151">'
            . '<div style="font-size:44px;font-weight:800;color:#111827">Not available</div>'
            . '<p style="margin-top:10px;font-size:15px;line-height:1.5">' . e($reason) . '</p>'
            . '<p style="margin-top:6px;font-size:13px;color:#6b7280">Go back to the certificates list and refresh it, then try again.</p>'
            . '</div>',
            404
        );
    }

    /**
     * Shared paper setup, so the on-screen preview and the downloaded file are
     * rendered from the very same template and options — what you view is what
     * you print.
     */
    private function render(string $view, string $key, $model): \Barryvdh\DomPDF\PDF
    {
        return Pdf::loadView($view, [$key => $model])
            ->setPaper('a4', 'portrait')
            ->setOption('dpi', 150)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('defaultFont', 'DejaVu Sans');
    }

    /**
     * NOTE: these routes are /{organization}/certificates/{id}/... — TWO params.
     * Scalar controller args are filled positionally, so $organization MUST be
     * declared first or $id would receive the organization value (the #4-vs-#1 bug).
     */
    private function findCert(int $id): Certificate|Response
    {
        $cert = Certificate::with(['student', 'organization'])->find($id);

        return (! $cert || $cert->organization_id !== Auth::user()?->organization_id)
            ? $this->notFound('certificate', $id, Certificate::class, 'certificates')
            : $cert;
    }

    private function findTc(int $id): TransferCertificate|Response
    {
        $tc = TransferCertificate::with(['student', 'organization'])->find($id);

        return (! $tc || $tc->organization_id !== Auth::user()?->organization_id)
            ? $this->notFound('transfer certificate', $id, TransferCertificate::class, 'transfer_certificates')
            : $tc;
    }

    /**
     * Download Achievement / Participation certificate as PDF
     * Route: GET /admin/certificates/{id}/download
     */
    public function downloadCert($organization, int $id): Response
    {
        Log::info('cert.download hit', ['id' => $id, 'user' => Auth::id(), 'org' => Auth::user()?->organization_id]);

        $cert = $this->findCert($id);
        if ($cert instanceof Response) {
            return $cert;
        }

        return $this->render('pdf.admin.certificate', 'cert', $cert)
            ->download('certificate_' . ($cert->certificate_no ?? $cert->id) . '.pdf');
    }

    /**
     * Same certificate, streamed inline so the View panel can show the printed
     * page itself instead of a list of the fields behind it.
     * Route: GET /admin/certificates/{id}/view
     */
    public function viewCert($organization, int $id): Response
    {
        $cert = $this->findCert($id);
        if ($cert instanceof Response) {
            return $cert;
        }

        return $this->render('pdf.admin.certificate', 'cert', $cert)
            ->stream('certificate_' . ($cert->certificate_no ?? $cert->id) . '.pdf');
    }

    /**
     * Download Transfer Certificate as PDF
     * Route: GET /admin/tc/{id}/download
     */
    public function downloadTc($organization, int $id): Response
    {
        Log::info('tc.download hit', ['id' => $id, 'user' => Auth::id(), 'org' => Auth::user()?->organization_id]);

        $tc = $this->findTc($id);
        if ($tc instanceof Response) {
            return $tc;
        }

        return $this->render('pdf.admin.tc-certificate', 'tc', $tc)
            ->download('TC_' . ($tc->tc_no ?? $tc->id) . '.pdf');
    }

    /**
     * Route: GET /admin/tc/{id}/view
     */
    public function viewTc($organization, int $id): Response
    {
        $tc = $this->findTc($id);
        if ($tc instanceof Response) {
            return $tc;
        }

        return $this->render('pdf.admin.tc-certificate', 'tc', $tc)
            ->stream('TC_' . ($tc->tc_no ?? $tc->id) . '.pdf');
    }
}