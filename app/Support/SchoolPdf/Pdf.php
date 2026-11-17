<?php

namespace App\Support\SchoolPdf;

if (!defined('TCPDF_SILENCE_DEPRECATION')) {
    define('TCPDF_SILENCE_DEPRECATION', true);
}

/** TCPDF with the School Data foot on every page: what it is, and "page / pages". */
class Pdf extends \TCPDF
{
    public string $footText = '';

    public string $footFont = 'dejavusans';

    public function Footer(): void
    {
        $w = $this->getPageWidth() - 20;
        $this->SetY(-9);
        $this->SetFont($this->footFont, '', 5.4);
        $this->SetTextColor(156, 163, 175);
        $this->SetX(10);
        $this->Cell($w * 0.85, 4, $this->footText, 0, 0, 'L');
        $this->SetX(10);
        $this->Cell($w, 4, $this->getAliasRightShift() . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
    }
}
