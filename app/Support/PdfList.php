<?php

namespace App\Support;

/**
 * The list PDF the Students and Teachers exports print: A4 landscape, ten
 * people a page, one row each — the serial number at the left, the photo in a
 * small circle (or the name's initial), then cells of four fields as plain
 * "Label  value" lines, labels in a column of their own. A value too long for
 * its cell carries on to the next line under itself, so nothing is cut; a row
 * grows to fit, and a page whose ten rows would then run past the foot is set
 * in smaller text rather than holding fewer. A thin divider keeps the rows
 * apart.
 *
 * Drawn straight with TCPDF: laying a whole school out from HTML with dompdf
 * ran past the gateway's minute (a 504).
 */
class PdfList
{
    private const LEFT   = 10.0;   // page margin, mm
    private const RIGHT  = 287.0;  // 297 − margin
    private const TOP    = 16.5;   // first row, under the header
    private const BOTTOM = 201.0;  // last row must end above this
    private const NO_W   = 7.0;    // serial number column
    private const PIC_W  = 12.5;   // photo column
    private const R      = 4.6;    // photo circle radius
    private const LINE   = 3.0;    // text line height
    private const PAD    = 1.6;    // above and below a row's text
    private const FONT   = 6.5;    // text size, pt
    private const GAP    = 2.5;    // between cells

    /** Text sizes a page may drop to (× FONT and LINE) so its ten rows fit. */
    private const SCALES = [1.0, 0.94, 0.88, 0.82, 0.76, 0.7];

    private const GREY = [107, 114, 128];
    private const INK  = [17, 24, 39];

    /**
     * @param  array{school:?string, logo:?string, title:string, scope:string, count:string}  $head  logo = JPEG bytes
     * @param  float[]  $widths  each field cell's width in mm (they share 287 − 10 − 7 − 12.5)
     * @param  array<int, array{photo:?string, initial:string, cells:array<int, array<int, array{0:string,1:mixed}>>}>  $rows
     */
    public static function render(array $head, array $widths, array $rows): string
    {
        if (!defined('TCPDF_SILENCE_DEPRECATION')) {
            // TCPDF 6.11 flags itself deprecated in favour of tc-lib-pdf once per
            // process; it still works, so keep that note out of the logs.
            define('TCPDF_SILENCE_DEPRECATION', true);
        }

        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator('SuperLMS');
        $pdf->SetTitle($head['title']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(self::LEFT, 9, 297 - self::RIGHT);
        $pdf->SetAutoPageBreak(false);
        $pdf->setCellPaddings(0, 0, 0, 0);
        // A wrapped value's lines sit LINE apart, as the fields do.
        $pdf->setCellHeightRatio(self::LINE / (self::FONT * 25.4 / 72));
        $pdf->setFontSubsetting(true);
        $pdf->SetFont('dejavusans', '', self::FONT);

        $value = fn ($v) => ($v === null || trim((string) $v) === '') ? '-' : trim((string) $v);
        $avail = self::BOTTOM - self::TOP;

        // Each cell's labels share one column, as wide as its widest label —
        // at the text size a page is drawn at.
        $labelCache = [];
        $labelW = function (float $s) use (&$labelCache, $pdf, $rows): array {
            $key = (string) $s;
            if (!isset($labelCache[$key])) {
                $pdf->SetFont('dejavusans', '', self::FONT * $s);
                $w = [];
                foreach ($rows as $row) {
                    foreach ($row['cells'] as $c => $fields) {
                        foreach ($fields as [$label]) {
                            $w[$c] = max($w[$c] ?? 0, $pdf->GetStringWidth($label . ':') + 1.2);
                        }
                    }
                }
                $labelCache[$key] = $w;
            }
            return $labelCache[$key];
        };

        // A row's height at text scale $s: every value in full, wrapped.
        $measure = function (array $row, float $s) use ($pdf, $widths, $value, $labelW): array {
            $lw = $labelW($s);
            $pdf->SetFont('dejavusans', '', self::FONT * $s);
            $lines = 4;
            $cellLines = [];
            foreach ($row['cells'] as $c => $fields) {
                $valueW = $widths[$c] - self::GAP - $lw[$c];
                $n = 0;
                foreach ($fields as $f => [, $v]) {
                    $cellLines[$c][$f] = max(1, $pdf->getNumLines($value($v), $valueW));
                    $n += $cellLines[$c][$f];
                }
                $lines = max($lines, $n);
            }

            return [
                'h'     => max($lines * self::LINE * $s + 2 * self::PAD * $s, 2 * self::R + 1.6),
                'lines' => $cellLines,
                's'     => $s,
            ];
        };

        // Ten to a page. A page whose ten rows would run past the foot is set
        // in smaller text until they fit, so nothing is cut and no page holds
        // fewer; only if even the smallest text cannot fit them are they split.
        $pages = [];
        foreach (array_chunk($rows, 10) as $chunk) {
            $placed = null;
            foreach (self::SCALES as $s) {
                $measured = array_map(fn ($r) => $r + $measure($r, $s), $chunk);
                if (array_sum(array_column($measured, 'h')) <= $avail) {
                    $placed = $measured;
                    break;
                }
            }
            if ($placed) {
                $pages[] = $placed;
                continue;
            }
            $page = [];
            $used = 0.0;
            foreach ($measured as $r) {
                if ($page && $used + $r['h'] > $avail) {
                    $pages[] = $page;
                    $page = [];
                    $used = 0.0;
                }
                $page[] = $r;
                $used += $r['h'];
            }
            if ($page) {
                $pages[] = $page;
            }
        }
        if (!$pages) {
            $pages[] = [];
        }

        $no = 0;
        foreach ($pages as $p => $pageRows) {
            $pdf->AddPage();
            self::header($pdf, $head, $p + 1, count($pages));

            if (!$pageRows) {
                $pdf->SetFont('dejavusans', '', 9);
                $pdf->SetTextColor(...self::GREY);
                $pdf->SetXY(self::LEFT, 30);
                $pdf->Cell(self::RIGHT - self::LEFT, 6, 'Nothing to export.', 0, 0, 'C');
            }

            $y = self::TOP;
            foreach ($pageRows as $row) {
                $no++;
                $h    = $row['h'];
                $s    = $row['s'];
                $line = self::LINE * $s;
                $pad  = self::PAD * $s;
                $lw   = $labelW($s);

                // Serial number, at the very left
                $pdf->SetFont('dejavusans', 'B', 7.5);
                $pdf->SetTextColor(...self::INK);
                $pdf->SetXY(self::LEFT, $y);
                $pdf->Cell(self::NO_W, $h, (string) $no, 0, 0, 'L', false, '', 0, false, 'T', 'M');

                // Photo in a circle, or the initial on a grey one
                $cx = self::LEFT + self::NO_W + self::PIC_W / 2 - 0.5;
                $cy = $y + max(2 * self::R + 1.6, min($h, 4 * $line + 2 * $pad)) / 2;
                if (!empty($row['photo'])) {
                    $pdf->StartTransform();
                    $pdf->Circle($cx, $cy, self::R, 0, 360, 'CNZ');
                    $pdf->Image('@' . $row['photo'], $cx - self::R, $cy - self::R, 2 * self::R, 2 * self::R, 'JPG');
                    $pdf->StopTransform();
                } else {
                    $pdf->Circle($cx, $cy, self::R, 0, 360, 'F', [], [229, 231, 235]);
                    $pdf->SetFont('dejavusans', '', 10);
                    $pdf->SetTextColor(...self::GREY);
                    $pdf->SetXY($cx - self::R, $cy - self::R);
                    $pdf->Cell(2 * self::R, 2 * self::R, $row['initial'], 0, 0, 'C', false, '', 0, false, 'T', 'M');
                }

                // The field cells: label column, then the value, wrapping under itself
                $pdf->SetFont('dejavusans', '', self::FONT * $s);
                $x = self::LEFT + self::NO_W + self::PIC_W;
                foreach ($row['cells'] as $c => $fields) {
                    $valueW = $widths[$c] - self::GAP - $lw[$c];
                    $ty = $y + $pad;
                    foreach ($fields as $f => [$label, $v]) {
                        $lines = $row['lines'][$c][$f];
                        $pdf->SetTextColor(...self::GREY);
                        $pdf->SetXY($x, $ty);
                        $pdf->Cell($lw[$c], $line, $label . ':', 0, 0, 'L');
                        $pdf->SetTextColor(...self::INK);
                        if ($lines === 1) {
                            $pdf->Cell($valueW, $line, $value($v), 0, 0, 'L');
                        } else {
                            $pdf->MultiCell($valueW, $line, $value($v), 0, 'L', false, 0, $x + $lw[$c], $ty, true, 0, false, true, 0, 'T', false);
                        }
                        $ty += $lines * $line;
                    }
                    $x += $widths[$c];
                }

                $y += $h;
                $pdf->SetDrawColor(209, 213, 219);
                $pdf->SetLineWidth(0.2);
                $pdf->Line(self::LEFT, $y, self::RIGHT, $y);
            }
        }

        return $pdf->Output('export.pdf', 'S');
    }

    /** School (with its logo on the first page), what this is, the date and the page. */
    private static function header(\TCPDF $pdf, array $head, int $page, int $pages): void
    {
        $x = self::LEFT;
        if ($page === 1 && !empty($head['logo'])) {
            $pdf->Image('@' . $head['logo'], self::LEFT, 8.5, 0, 6, 'JPG');
            $x = $pdf->getImageRBX() + 2;
        }

        $pdf->SetFont('dejavusans', 'B', 10);
        $pdf->SetTextColor(...self::INK);
        $name  = $head['school'] ?: 'School';
        $nameW = $pdf->GetStringWidth($name) + 1;
        $pdf->SetXY($x, 9);
        $pdf->Cell($nameW, 5, $name, 0, 0, 'L');

        $pdf->SetFont('dejavusans', '', 7.5);
        $pdf->SetTextColor(...self::GREY);
        $pdf->Cell(max(10, 187 - $x - $nameW), 5, '  ·  ' . $head['title'] . '  ·  ' . $head['scope'] . '  ·  ' . $head['count'], 0, 0, 'L');

        $pdf->SetXY(187, 9);
        $pdf->Cell(self::RIGHT - 187, 5, now()->format('d M Y, g:i A') . '  ·  Page ' . $page . ' of ' . $pages, 0, 0, 'R');

        $pdf->SetDrawColor(...self::INK);
        $pdf->SetLineWidth(0.3);
        $pdf->Line(self::LEFT, 15.5, self::RIGHT, 15.5);
    }

    /** A school's logo as JPEG bytes on white, 80px high (fetched once), or null. */
    public static function logo(?string $url): ?string
    {
        if (!$url || !\Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])) {
            return null;
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(5)->connectTimeout(3)->get($url);
            if (!$response->successful() || strlen($response->body()) > 5_000_000) {
                return null;
            }
            $src = @imagecreatefromstring($response->body());
            if (!$src) {
                return null;
            }
            $h   = 80;
            $w   = max(1, (int) round(imagesx($src) * $h / max(1, imagesy($src))));
            $out = imagecreatetruecolor($w, $h);
            imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
            imagecopyresampled($out, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
            ob_start();
            imagejpeg($out, null, 90);
            $jpeg = (string) ob_get_clean();
            imagedestroy($out);
            imagedestroy($src);

            return $jpeg !== '' ? $jpeg : null;
        } catch (\Throwable $e) {
            // No logo is better than no PDF.
            return null;
        }
    }
}
