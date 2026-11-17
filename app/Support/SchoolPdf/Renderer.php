<?php

namespace App\Support\SchoolPdf;

use App\Support\Pdf\Devanagari;

/**
 * Draws a School Data document (Exporter::document) with TCPDF: plain on
 * purpose — black on white, thin grey rules, no colour or boxes; the school
 * on top, the figures in a line, then the blocks:
 *   heading — a group's name (a class, an exam) and its count
 *   table   — columns [label, 'w' => share, 'align' => 'right'], rows of cells;
 *             a table running on to the next page takes its header with it
 *   cards   — one card per person, label / value pairs four to a row (a third
 *             value in a pair spans that many pairs); never split across pages
 *   fee     — one student: name, Fee / Paid / Due, concessions, then every
 *             payment in a small table
 *
 * TCPDF, not dompdf: the Students PDF of a school of 1,150 is ~190 pages,
 * which dompdf took nearly four minutes and 1.2 GB to lay out — past both
 * the web request's and the queue worker's limits. In the bundled Poppins,
 * so ₹ and Hindi print, Hindi shaped as every other PDF has it (Devanagari).
 */
class Renderer
{
    private const M      = 10.0;   // side margins, mm
    private const TOP    = 10.0;
    private const FOOT   = 13.0;   // kept clear at the foot for the page line
    private const PT     = 0.3528; // mm in a point
    private const RATIO  = 1.22;   // line height ÷ font size

    private const INK   = [17, 24, 39];
    private const GREY  = [107, 114, 128];
    private const FAINT = [156, 163, 175];
    private const LINE  = [229, 231, 235];
    private const MID   = [190, 195, 203];

    private Pdf $pdf;
    private float $W;
    private float $bottom;
    private float $y = self::TOP;
    private string $font = 'dejavusans';
    private string $bold = 'dejavusans';
    private string $boldStyle = 'B';
    private bool $shape = false;

    public static function render(array $doc, array $school, string $generated): string
    {
        if (!defined('TCPDF_SILENCE_DEPRECATION')) {
            define('TCPDF_SILENCE_DEPRECATION', true);
        }

        $r = new self($doc, $school, $generated);

        return $r->draw($doc, $school, $generated);
    }

    private function __construct(array $doc, array $school, string $generated)
    {
        $landscape = ($doc['orientation'] ?? 'portrait') === 'landscape';
        $this->pdf = new Pdf($landscape ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
        $this->pdf->SetCreator('SuperLMS');
        $this->pdf->SetAuthor('SuperLMS');
        $this->pdf->SetTitle(($school['name'] ?? 'School') . ' · ' . $doc['title']);
        $this->pdf->setPrintHeader(false);
        $this->pdf->setPrintFooter(true);
        $this->pdf->SetMargins(self::M, self::TOP, self::M);
        $this->pdf->SetFooterMargin(9);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->setCellPaddings(0, 0, 0, 0);
        $this->pdf->setCellHeightRatio(self::RATIO);
        $this->pdf->setFontSubsetting(true);

        if ($fonts = Fonts::poppins()) {
            foreach ($fonts as [$name, $file]) {
                $this->pdf->AddFont($name, '', $file);
            }
            [$this->font, $this->bold] = [$fonts[0][0], $fonts[1][0]];
            $this->boldStyle = '';
            $this->shape = Devanagari::available();
        }

        $this->pdf->footFont = $this->font;
        $this->pdf->footText = $this->t(($school['name'] ?? 'School') . ' · ' . $doc['title'] . ' · Generated ' . $generated . ' by SuperLMS');

        $this->W      = $this->pdf->getPageWidth() - 2 * self::M;
        $this->bottom = $this->pdf->getPageHeight() - self::FOOT;
    }

    private function draw(array $doc, array $school, string $generated): string
    {
        $this->pdf->AddPage();
        $this->head($doc, $school, $generated);

        if (empty($doc['blocks'])) {
            $this->y += 20;
            $this->write(self::M, $this->y, $this->W, 'Nothing recorded here yet.', 8, self::FAINT, false, 'C');
        }

        foreach ($doc['blocks'] as $block) {
            match ($block['type']) {
                'heading' => $this->heading($block),
                'table'   => $this->table($block),
                'cards'   => $this->cards($block),
                'fee'     => $this->fee($block),
                default   => null,
            };
        }

        return $this->pdf->Output('school-data.pdf', 'S');
    }

    // ─── The page's top ───────────────────────────────────────────────────────

    private function head(array $doc, array $school, string $generated): void
    {
        $x = self::M;
        if (!empty($school['logo']) && str_contains($school['logo'], ',')) {
            try {
                $this->pdf->Image('@' . base64_decode(substr($school['logo'], strpos($school['logo'], ',') + 1)), self::M, $this->y, 0, 10, 'JPG');
                $x = $this->pdf->getImageRBX() + 2.5;
            } catch (\Throwable) {
                $x = self::M;
            }
        }

        $rightW = $this->W * 0.42;
        $leftW  = self::M + $this->W - $rightW - 4 - $x;

        $y = $this->y;
        $y += $this->write($x, $y, $leftW, $school['name'] ?? 'School', 11, self::INK, true);
        if (!empty($school['line'])) {
            $y += $this->write($x, $y + 0.4, $leftW, $school['line'], 5.8, self::GREY);
        }
        if (!empty($school['ids'])) {
            $y += $this->write($x, $y + 0.2, $leftW, $school['ids'], 5.8, self::GREY);
        }

        $ry = $this->y;
        $rx = self::M + $this->W - $rightW;
        $ry += $this->write($rx, $ry - 0.6, $rightW, $doc['title'], 12.5, self::INK, true, 'R');
        if (!empty($doc['subtitle'])) {
            $ry += $this->write($rx, $ry + 0.2, $rightW, $doc['subtitle'], 5.8, self::GREY, false, 'R');
        }
        $ry += $this->write($rx, $ry + 0.2, $rightW, 'Generated ' . $generated, 5.8, self::GREY, false, 'R');

        $this->y = max($y, $ry, $this->y + 10) + 2.2;
        $this->rule(self::INK, 0.3);
        $this->y += 3;

        if (!empty($doc['summary'])) {
            $x = self::M;
            $rowH = 0;
            foreach ($doc['summary'] as [$k, $v]) {
                $k = mb_strtoupper((string) $k);
                $this->font(4.9);
                $kw = $this->pdf->GetStringWidth($this->t($k));
                $this->font(8.6, true);
                $vw = $this->pdf->GetStringWidth($this->t((string) $v));
                $w  = max($kw, $vw) + 7;
                if ($x > self::M && $x + $w > self::M + $this->W) {
                    $x = self::M;
                    $this->y += $rowH + 1.5;
                }
                $this->write($x, $this->y, $w, $k, 4.9, self::GREY);
                $this->write($x, $this->y + 2.4, $w, (string) $v, 8.6, self::INK, true);
                $rowH = 6.4;
                $x += $w;
            }
            $this->y += $rowH + 1.5;
        }
    }

    // ─── Blocks ───────────────────────────────────────────────────────────────

    private function heading(array $b): void
    {
        if (!empty($b['break']) && $this->y > self::TOP + 30) {
            $this->newPage();
        }
        // A heading never ends a page on its own.
        $this->ensure(16);
        $this->y += 2.6;

        $text = (string) $b['text'];
        $this->font(7.6, true);
        $tw = min($this->W * 0.7, $this->pdf->GetStringWidth($this->t($text)) + 0.5);
        $h  = $this->write(self::M, $this->y, $tw, $text, 7.6, self::INK, true);
        if (($b['meta'] ?? '') !== '') {
            $this->write(self::M + $tw + 1.5, $this->y + 0.7, $this->W - $tw - 1.5, '·  ' . $b['meta'], 6, self::GREY);
        }
        $this->y += $h + 0.8;
        $this->rule(self::MID, 0.2);
        $this->y += 1.4;
    }

    /** A table; rows drawn in full, the header again on each page it runs on to. */
    private function table(array $b, ?float $size = null): void
    {
        $cols  = $b['columns'];
        $sum   = array_sum(array_map(fn ($c) => (float) ($c['w'] ?? 10), $cols)) ?: 1;
        $ws    = array_map(fn ($c) => (float) ($c['w'] ?? 10) / $sum * $this->W, $cols);
        $size  ??= !empty($b['small']) ? 5.6 : 6.2;
        $lh    = $size * self::PT * self::RATIO;
        $padX  = 1.6;
        $padY  = 0.75;
        $align = array_map(fn ($c) => ($c['align'] ?? '') === 'right' ? 'R' : 'L', $cols);

        $headSize = 4.8;
        $headLh   = $headSize * self::PT * self::RATIO;
        $labels   = array_map(fn ($c) => mb_strtoupper((string) $c[0]), $cols);
        $this->font($headSize);
        $headH = 0;
        foreach ($labels as $i => $l) {
            $headH = max($headH, $this->lines($l, $ws[$i] - 2 * $padX) * $headLh);
        }
        $headH += 2 * $padY + 0.4;

        $drawHead = function () use ($labels, $ws, $align, $headSize, $headH, $padX, $padY) {
            $x = self::M;
            foreach ($labels as $i => $l) {
                $this->write($x + $padX, $this->y + $padY + 0.2, $ws[$i] - 2 * $padX, $l, $headSize, self::GREY, false, $align[$i]);
                $x += $ws[$i];
            }
            $this->y += $headH;
            $this->rule(self::MID, 0.25);
        };

        $rows   = array_values($b['rows']);
        $height = function (array $row) use ($ws, $size, $lh, $padX, $padY) {
            $this->font($size);
            $n = 1;
            foreach (array_values($row) as $i => $v) {
                $n = max($n, $this->lines((string) $v, ($ws[$i] ?? 10) - 2 * $padX));
            }

            return $n * $lh + 2 * $padY;
        };

        $this->ensure($headH + ($rows ? $height($rows[0]) : 0) + 1);
        $drawHead();

        foreach ($rows as $row) {
            $h = $height($row);
            if ($this->y + $h > $this->bottom) {
                $this->newPage();
                $drawHead();
            }
            $x = self::M;
            foreach (array_values($row) as $i => $v) {
                $this->write($x + $padX, $this->y + $padY, ($ws[$i] ?? 10) - 2 * $padX, (string) $v, $size, self::INK, false, $align[$i] ?? 'L');
                $x += $ws[$i] ?? 10;
            }
            $this->y += $h;
            $this->rule(self::LINE, 0.12);
        }

        if (!empty($b['foot'])) {
            $h = $lh + 2 * $padY;
            $this->ensure($h);
            $this->rule(self::MID, 0.25);
            $x = self::M;
            foreach (array_values($b['foot']) as $i => $v) {
                $this->write($x + $padX, $this->y + $padY, ($ws[$i] ?? 10) - 2 * $padX, (string) $v, $size, self::INK, true, $align[$i] ?? 'L');
                $x += $ws[$i] ?? 10;
            }
            $this->y += $h;
        }

        if (!empty($b['note'])) {
            $this->y += 0.8;
            $this->ensure(4);
            $this->y += $this->write(self::M, $this->y, $this->W, $b['note'], 5.2, self::GREY);
        }
        $this->y += 1.2;
    }

    /** A card per person; a card is never split across two pages. */
    private function cards(array $b): void
    {
        $cols   = max(1, (int) ($b['cols'] ?? 4));
        $pairW  = $this->W / $cols;
        $labelW = $pairW * 0.34;
        $size   = 5.9;
        $lh     = $size * self::PT * self::RATIO;
        $pad    = 1.1;

        foreach ($b['items'] as $card) {
            // Pairs laid $cols to a row; a pair with a span takes that many.
            $lines = [[]];
            $used  = 0;
            foreach ($card['fields'] as $f) {
                $span = min($cols, max(1, (int) ($f[2] ?? 1)));
                if ($used + $span > $cols) {
                    $lines[] = [];
                    $used = 0;
                }
                $lines[array_key_last($lines)][] = [(string) $f[0], (string) $f[1], $span];
                $used += $span;
            }

            // A row as tall as its longest value — or label, when one wraps.
            $rowH = [];
            foreach ($lines as $l => $pairs) {
                $h = $lh;
                foreach ($pairs as [$k, $v, $span]) {
                    $this->font($size);
                    $h = max($h, $this->lines($v, $span * $pairW - $labelW - $pad) * $lh);
                    $this->font(4.6);
                    $h = max($h, $this->lines(mb_strtoupper($k), $labelW - $pad) * 4.6 * self::PT * self::RATIO + 0.35);
                }
                $rowH[$l] = $h + 0.7;
            }
            $headH = 5.0;
            $cardH = $headH + array_sum($rowH) + 1.4;

            $this->ensure($cardH + 2);
            $top = $this->y;

            // Name, then what identifies them, on one line.
            $this->font(7.2, true);
            $nw = min($this->W * 0.6, $this->pdf->GetStringWidth($this->t($card['title'])) + 0.5);
            $this->write(self::M + $pad, $top + 1.2, $nw, $card['title'], 7.2, self::INK, true);
            if (!empty($card['meta'])) {
                $this->write(self::M + $pad + $nw + 2, $top + 1.75, $this->W - $nw - 4 - 2 * $pad, $card['meta'], 5.6, self::GREY);
            }
            $this->pdf->SetDrawColor(...self::LINE);
            $this->pdf->SetLineWidth(0.12);
            $this->pdf->Line(self::M, $top + $headH, self::M + $this->W, $top + $headH);

            $y = $top + $headH + 0.8;
            foreach ($lines as $l => $pairs) {
                $x = self::M;
                foreach ($pairs as [$k, $v, $span]) {
                    $this->write($x + $pad, $y + 0.35, $labelW - $pad, mb_strtoupper($k), 4.6, self::GREY);
                    $this->write($x + $labelW, $y, $span * $pairW - $labelW - $pad, $v, $size, self::INK);
                    $x += $span * $pairW;
                }
                $y += $rowH[$l];
            }

            $this->pdf->SetDrawColor(...self::MID);
            $this->pdf->SetLineWidth(0.15);
            $this->pdf->Rect(self::M, $top, $this->W, $cardH);
            $this->y = $top + $cardH + 1.8;
        }
    }

    /** One student's fee: name and figures, concessions, then each payment. */
    private function fee(array $b): void
    {
        $size = 5.6;
        $rows = $b['rows'] ?? [];

        // Name and figures on a line; with a short list, the whole block on one page.
        $this->font(5.2);
        $noteH = !empty($b['note']) ? $this->lines($b['note'], $this->W) * 5.2 * self::PT * self::RATIO + 0.6 : 0;
        $rowH  = $size * self::PT * self::RATIO + 1.5;
        $blockH = 5 + $noteH + ($rows ? 4 + (count($rows) <= 14 ? count($rows) : 2) * $rowH : 4);
        $this->ensure($blockH + 1);

        $figures = $b['figures'] ?? [];
        $x = self::M + $this->W;
        foreach (array_reverse($figures) as $f) {
            $this->font(6.4, !empty($f[2]));
            $vw = $this->pdf->GetStringWidth($this->t((string) $f[1])) + 0.5;
            $this->font(5.4);
            $kw = $this->pdf->GetStringWidth($this->t((string) $f[0])) + 1.2;
            $x -= $vw;
            $this->write($x, $this->y + 0.7, $vw, (string) $f[1], 6.4, self::INK, !empty($f[2]), 'R');
            $x -= $kw;
            $this->write($x, $this->y + 1.05, $kw, (string) $f[0], 5.4, self::GREY);
            $x -= 4.5;
        }
        $figW = self::M + $this->W - $x;

        $this->font(7, true);
        $nw = min($this->W - $figW - 4, $this->pdf->GetStringWidth($this->t((string) $b['title'])) + 0.5);
        $this->write(self::M, $this->y + 0.4, $nw, (string) $b['title'], 7, self::INK, true);
        if (!empty($b['meta'])) {
            $this->write(self::M + $nw + 2, $this->y + 0.95, max(10, $this->W - $nw - $figW - 4), $b['meta'], 5.6, self::GREY);
        }
        $this->y += 4.4;

        if (!empty($b['note'])) {
            $this->y += $this->write(self::M, $this->y, $this->W, $b['note'], 5.2, self::GREY) + 0.6;
        }

        if ($rows) {
            $this->table(['columns' => $b['columns'], 'rows' => $rows], $size);
        } else {
            if (!empty($b['empty'])) {
                $this->y += $this->write(self::M, $this->y, $this->W, $b['empty'], 5.4, self::FAINT) + 0.5;
            }
            $this->rule(self::LINE, 0.12);
            $this->y += 1.4;
        }
        $this->y += 0.6;
    }

    // ─── Drawing ──────────────────────────────────────────────────────────────

    private function ensure(float $h): void
    {
        if ($this->y + $h > $this->bottom && $this->y > self::TOP + 1) {
            $this->newPage();
        }
    }

    private function newPage(): void
    {
        $this->pdf->AddPage();
        $this->y = self::TOP;
    }

    private function rule(array $color, float $width): void
    {
        $this->pdf->SetDrawColor(...$color);
        $this->pdf->SetLineWidth($width);
        $this->pdf->Line(self::M, $this->y, self::M + $this->W, $this->y);
    }

    private function font(float $size, bool $bold = false): void
    {
        $bold
            ? $this->pdf->SetFont($this->bold, $this->boldStyle, $size)
            : $this->pdf->SetFont($this->font, '', $size);
    }

    /** How many lines the text takes at width $w in the current font. */
    private function lines(string $text, float $w): int
    {
        $text = $this->t($text);
        if ($text === '' || (!str_contains($text, "\n") && $this->pdf->GetStringWidth($text) <= $w)) {
            return 1;
        }

        return max(1, $this->pdf->getNumLines($text, max(1, $w)));
    }

    /** Text at (x, y), wrapped to $w; returns the height it took. */
    private function write(float $x, float $y, float $w, string $text, float $size, array $color, bool $bold = false, string $align = 'L'): float
    {
        $this->font($size, $bold);
        $this->pdf->SetTextColor(...$color);
        $text = $this->t($text);
        $lh   = $size * self::PT * self::RATIO;
        $w    = max(1, $w);

        if (!str_contains($text, "\n") && $this->pdf->GetStringWidth($text) <= $w) {
            $this->pdf->SetXY($x, $y);
            $this->pdf->Cell($w, $lh, $text, 0, 0, $align, false, '', 0, false, 'T', 'T');

            return $lh;
        }

        $n = max(1, $this->pdf->getNumLines($text, $w));
        $this->pdf->MultiCell($w, $lh, $text, 0, $align, false, 0, $x, $y, true, 0, false, true, 0, 'T', false);

        return $n * $lh;
    }

    /** The text as it is printed: Hindi shaped into the font's own glyphs. */
    private function t(string $text): string
    {
        $text = str_replace("\r", '', $text);
        if (!$this->shape || !Devanagari::in($text)) {
            return $text;
        }

        static $memo = [];
        if (isset($memo[$text])) {
            return $memo[$text];
        }

        $html = Devanagari::html('<html><head></head><body>' . e($text) . '</body></html>');
        $body = preg_match('#<body>(.*)</body>#s', $html, $m) ? $m[1] : e($text);

        return $memo[$text] = html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
