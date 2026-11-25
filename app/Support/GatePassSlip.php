<?php

namespace App\Support;

use App\Models\Admin\SchoolInfo;
use App\Models\Admin\StudentGatePass;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * A student's gate pass as the slip the gate keeps: the school at the head,
 * the pass number and date, the student, why and when they went, who took
 * them, and the signatures. One B5 page in the Report Card's look (user's ask
 * of 2026-11-25), in the bundled Poppins and Merriweather faces.
 */
class GatePassSlip
{
    public const WITH = ['organization', 'studentDetail', 'standard:id,name', 'section:id,name', 'addedBy:id,name'];

    public static function pdf(StudentGatePass $pass): \Barryvdh\DomPDF\PDF
    {
        // A school under two names: the slip's class's name and logo (SchoolBrand).
        $org = SchoolBrand::org($pass->organization, $pass->standard_id ?: $pass->studentDetail?->standard_id);
        $website = trim((string) (SchoolInfo::where('organization_id', $pass->organization_id)->value('website_url') ?? ''));
        $site    = $website !== '' ? rtrim(preg_replace('#^https?://#i', '', $website), '/') : '';

        $fontDir = PdfFonts::cacheDir();
        // Without the embedded faces it still renders, in dompdf's own font.
        $fontCss = rescue(fn () => PdfFonts::faceCss(), '', false);

        return Pdf::loadView('pdf.gate-pass', [
            'pass'    => $pass,
            'org'     => $org,
            'logo'    => PdfLogo::src($org?->logo),
            // As the Report Card writes them: phone / email under the address,
            // the affiliation number and the website above the frame.
            'contact' => implode(' / ', array_filter([
                $org?->mobile_number ?: null,
                $org?->email ?: null,
            ])),
            'website'     => $site,
            'affiliation' => trim((string) ($org?->affiliation_no ?? '')),
            'fontCss' => $fontCss,
        ])
            ->setPaper('b5', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isFontSubsettingEnabled', true)
            ->setOption('fontDir', $fontDir)
            ->setOption('fontCache', $fontDir)
            ->setOption('defaultFont', 'DejaVu Sans');
    }
}
