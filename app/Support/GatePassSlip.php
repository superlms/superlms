<?php

namespace App\Support;

use App\Models\Admin\SchoolInfo;
use App\Models\Admin\StudentGatePass;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * A student's gate pass as the slip the gate keeps: the school at the head,
 * the pass number and date, the student, why and when they went, who took
 * them, and the signatures. One A5 page, in the bundled Poppins faces.
 */
class GatePassSlip
{
    public const WITH = ['organization', 'studentDetail', 'standard:id,name', 'section:id,name', 'addedBy:id,name'];

    public static function pdf(StudentGatePass $pass): \Barryvdh\DomPDF\PDF
    {
        $org = $pass->organization;
        $website = trim((string) (SchoolInfo::where('organization_id', $pass->organization_id)->value('website_url') ?? ''));

        $fontDir = PdfFonts::cacheDir();
        // Without the embedded faces it still renders, in dompdf's own font.
        $fontCss = rescue(fn () => PdfFonts::faceCss(), '', false);

        return Pdf::loadView('pdf.gate-pass', [
            'pass'    => $pass,
            'org'     => $org,
            'logo'    => PdfLogo::src($org?->logo),
            'contact' => implode('  ·  ', array_filter([
                $org?->mobile_number ? 'Ph: ' . $org->mobile_number : null,
                $org?->email ?: null,
                $website !== '' ? rtrim(preg_replace('#^https?://#i', '', $website), '/') : null,
            ])),
            'fontCss' => $fontCss,
        ])
            ->setPaper('a5', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontDir)
            ->setOption('fontCache', $fontDir)
            ->setOption('defaultFont', 'DejaVu Sans');
    }
}
