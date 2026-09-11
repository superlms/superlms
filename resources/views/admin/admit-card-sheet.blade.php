<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $single ?? null ? 'Admit Card – ' . ($single->student_name ?? 'Student') : 'Admit Cards' }}</title>
    @unless($isPdf ?? false)
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @endunless
    <style>
        {{-- Embedded Poppins faces for dompdf; empty in the browser, where the
             Google Fonts link above does the job. --}}
        {!! $fontCss ?? '' !!}

        /* Never zero margins with `*` — the universal selector also matches
           dompdf's page box and silently wipes @page, bleeding the sheet to the
           paper edge. Reset only the elements these cards actually use. */
        * { box-sizing: border-box; }
        body, h1, p, ol, li, table, td, th, div { margin: 0; padding: 0; }

        /* ═══ The page, cut in four ═══
           A4 landscape is 297 × 210mm and the sheet takes no page margin, so a
           quadrant is a true half each way — 148.5mm wide, 105mm tall — and the
           cut lines run down and across the middle of the paper.

           The rows ask for 104.7mm rather than a flat 105: collapsed cut-line
           borders add their own fraction of a millimetre, and a height in mm
           rounds up to a whole pixel at 96dpi, so a table asking for exactly
           210mm measures over 210mm and drops the bottom row onto a second
           sheet. 0.3mm of slack per row buys immunity and cannot be seen. */
        @page { size: A4 landscape; margin: 0; }

        body {
            font-family: 'Poppins', 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 6.6pt; color: #000; background: #fff; line-height: 1.35;
            -webkit-font-smoothing: antialiased;
        }

        /* A table, not grid/flex, because dompdf renders this same markup for
           the download. Two rows of two, always — both columns exactly 50% of
           the width and both rows exactly the same height, so the cut runs down
           the middle and across the middle whether the sheet holds one card or
           four, and an empty quadrant is simply an empty quadrant. */
        .sheet { width: 100%; border-collapse: collapse; table-layout: fixed; page-break-inside: avoid; }
        .sheet .cell { width: 50%; height: 104.7mm; vertical-align: top; padding: 0; }
        .sheet tr { page-break-inside: avoid; }
        /* The cross: the right edge of column one, the bottom edge of row one.
           Both lines run the full length of the page. */
        .sheet .cell.br { border-right: 0.4pt dotted #000; }
        .sheet .cell.bb { border-bottom: 0.4pt dotted #000; }

        .sheet-wrap { page-break-after: always; }
        .sheet-wrap.last { page-break-after: auto; }

        /* ═══ One frame, whatever the schedule holds ═══
           The card fills its quadrant, and the parts that decide how it reads
           are locked to the box rather than to the content: masthead and
           identity run from the top, the instructions and the signature foot
           are pinned to the bottom, and the schedule lives in a fixed window
           between them. A two-subject card and a ten-subject card are then the
           same card at the same size — only the number of rows differs. */
        .card { position: relative; height: 104.7mm; overflow: hidden; page-break-inside: avoid; }
        /* The padding lives on an inner box, never on the card itself: dompdf
           ignores box-sizing, so padding on a height-fixed element is added to
           that height — 104.7mm of card plus 13mm of padding is 117.7mm of row,
           which is exactly what used to push the bottom pair onto a second
           sheet. The card keeps its height; this box keeps the margins. */
        .pad { padding: 3.6mm 4.2mm 21mm; }

        /* ── Masthead: the school's name over one line of contacts, flush left
              against the same edge the card's content starts from ── */
        .masthead { text-align: left; padding-bottom: 1mm; border-bottom: 0.5pt solid #000; }
        .masthead .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 8.8pt; letter-spacing: -0.01em; line-height: 1.15;
        }
        .masthead .address { font-size: 4.6pt; margin-top: 0.4mm; line-height: 1.25; }

        /* ── Title row: the tag on the left, the exam on the right ── */
        .titlebar { width: 100%; border-collapse: collapse; margin: 1.1mm 0; }
        .titlebar td { vertical-align: baseline; }
        .titlebar .tag {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 6.8pt; letter-spacing: 0.18em; text-transform: uppercase;
        }
        .titlebar .exam { text-align: right; font-size: 5.4pt; }

        /* ── Identity: a bordered grid, same shape as the report card ── */
        .info { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info td {
            border: 0.5pt solid #000; padding: 0.4mm 1mm; font-size: 5.9pt;
            vertical-align: top; line-height: 1.2; word-wrap: break-word;
        }
        .info td.label {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
        }

        /* ── Section label ── */
        .sec-label {
            font-size: 4.9pt; letter-spacing: 0.12em; text-transform: uppercase;
            margin-bottom: 0.6mm;
        }
        .block { margin-top: 1.2mm; }

        /* ── Paper schedule ──
           The window takes the room the logo and the four-line instruction
           list used to hold: it fits a dozen papers at this scale, and past
           that the rows tighten, only the rows (see .card.many below). */
        .sched { height: 48mm; overflow: hidden; }
        .papers { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .papers th {
            font-size: 4.8pt; letter-spacing: 0.08em; text-transform: uppercase;
            font-weight: normal; text-align: left; padding: 0 1mm 0.6mm; border-bottom: 0.5pt solid #000;
        }
        .papers td {
            font-size: 5.9pt; padding: 0.4mm 1mm; border-bottom: 0.3pt solid #000; line-height: 1.15;
            overflow: hidden; white-space: nowrap;
        }
        /* A long subject name wraps instead of being cut in half. */
        .papers td:first-child { white-space: normal; word-wrap: break-word; }
        .papers tr:last-child td { border-bottom: 0; }
        .papers th:first-child, .papers td:first-child { padding-left: 0; }
        .papers th:last-child, .papers td:last-child { padding-right: 0; }
        .papers th.c, .papers td.c { text-align: center; }
        .papers .seat { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }
        .no-papers {
            font-size: 5.6pt; border: 0.5pt dashed #000;
            padding: 3mm; text-align: center;
        }

        /* Past ten papers the schedule alone tightens, so the card keeps its
           masthead, its identity grid and its foot at exactly the size every
           other card on the sheet prints them at. */
        .card.many .papers td    { font-size: 5.4pt; padding: 0.3mm 1mm; }
        .card.many-x .papers th  { font-size: 4.4pt; padding-bottom: 0.4mm; }
        .card.many-x .papers td  { font-size: 4.5pt; padding: 0.15mm 0.8mm; }
        .card.many-xx .papers td { font-size: 4pt; padding: 0.05mm 0.7mm; }

        /* ── Instructions: pinned above the foot, so they end on the same line
              in every card and the schedule window above never moves ── */
        .instructions { position: absolute; left: 4.2mm; right: 4.2mm; bottom: 9.4mm; }
        .note { font-size: 4.7pt; line-height: 1.3; text-align: justify; }

        /* ── Foot: pinned into the card's bottom padding ── */
        .foot-wrap { position: absolute; left: 4.2mm; right: 4.2mm; bottom: 3.2mm; }
        .foot { width: 100%; border-collapse: collapse; }
        .foot td { font-size: 5.1pt; vertical-align: bottom; line-height: 1.3; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 0.5pt solid #000; width: 26mm; margin: 0 0 0.8mm auto; height: 0; }

        @if($isPdf ?? false)
        /* ═══ dompdf only — leading ═══
           dompdf builds every line box out of the font's own metrics, and
           Poppins declares an em box of about 1.7 — so a line-height that
           reads normally in a browser prints half again as tall here, which
           is what used to spill the fourth card onto a second sheet. Dividing
           the browser's leading by that 1.7 makes the PDF measure the same as
           the print preview, line for line. */
        body, .masthead .school, .masthead .address, .titlebar td, .info td,
        .papers th, .papers td, .note, .foot td { line-height: 0.72; }
        @endif

        @unless($isPdf ?? false)
        /* ═══ Screen only — the same sheet on a plain ground, nothing else.
               dompdf's default media type is "screen", so this block is kept
               out of the PDF by Blade rather than by a media query. ═══ */
        body { background: #f1f3f6; padding: 22px 16px 48px; }
        .sheet-wrap {
            width: 297mm; max-width: 100%; height: 210mm; margin: 0 auto 8mm;
            background: #fff; padding: 0; overflow: hidden;
            box-shadow: 0 1px 3px rgba(16,24,40,.08), 0 8px 28px rgba(16,24,40,.10);
        }
        .toolbar {
            position: sticky; top: 0; z-index: 10; width: 297mm; max-width: 100%;
            margin: 0 auto 14px; background: #fff; border: 1px solid #e4e6ea; border-radius: 10px;
            padding: 8px 10px 8px 14px; display: flex; align-items: center; gap: 8px;
        }
        .toolbar .who {
            font-size: 13px; font-weight: 600; color: #16181d; margin-right: auto;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        }
        .toolbar .count { font-size: 12.5px; color: #6b7280; margin-right: auto; }
        .toolbar button, .toolbar a {
            font-family: inherit; font-size: 12.5px; font-weight: 500; line-height: 1;
            padding: 8px 14px; border-radius: 7px; cursor: pointer; text-decoration: none;
            border: 1px solid #e4e6ea; background: #fff; color: #3f4451;
        }
        .toolbar button:hover, .toolbar a:hover { background: #f7f8fa; }
        .toolbar .go { background: #16181d; border-color: #16181d; color: #fff; }
        .toolbar .go:hover { background: #2b2f38; }

        .empty {
            width: 297mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 48px;
            text-align: center; color: #6b7280; font-size: 14px;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .sheet-wrap { width: auto; height: auto; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
            .card { break-inside: avoid; }
        }
        @media (max-width: 820px) {
            body { padding: 12px 10px 32px; }
        }
        @endunless
    </style>
</head>
<body>

@php
    $cards  = collect($admitCards ?? [])->values();
    $sheets = $cards->chunk(4)->values();
@endphp

{{-- Toolbar — only in the browser preview, never in the PDF. --}}
@unless($isPdf ?? false)
    @php $isAccounts = request()->routeIs('accounts.*'); @endphp
    <div class="toolbar no-print">
        @if($single ?? null)
            <span class="who">{{ $single->student_name }}</span>
            <a href="{{ route($isAccounts ? 'accounts.admit-card.view' : 'admin.admit-card.view', [$single->organization_id, $single->id]) }}">Full page</a>
            <a href="{{ route($isAccounts ? 'accounts.admit-card.download' : 'admin.admit-card.download', [$single->organization_id, $single->id]) }}">Download</a>
        @else
            <span class="count">{{ $cards->count() }} card(s) · 4 to an A4 landscape sheet · cut along the dotted lines</span>
        @endif
        <button type="button" onclick="window.opener ? window.close() : history.back()">Close</button>
        <button type="button" class="go" onclick="window.print()">Print</button>
    </div>
@endunless

@forelse($sheets as $s => $sheet)
    @php $items = $sheet->values(); @endphp
    {{-- Always two rows of two, however many cards the sheet holds: the page is
         cut in exact halves both ways, so a sheet of one card is cut the same
         as a sheet of four and the guillotine setting never changes. --}}
    <div class="sheet-wrap{{ $s === $sheets->count() - 1 ? ' last' : '' }}">
        <table class="sheet">
            @for($r = 0; $r < 2; $r++)
                <tr>
                    @for($c = 0; $c < 2; $c++)
                        @php $card = $items[$r * 2 + $c] ?? null; @endphp
                        <td class="cell{{ $c === 0 ? ' br' : '' }}{{ $r === 0 ? ' bb' : '' }}">
                            @if($card)
                                @include('admin._admit-card-cell', ['admitCard' => $card, 'organization' => $organization])
                            @else
                                {{-- Empty quadrant: dompdf drops a cell with nothing in it. --}}
                                &nbsp;
                            @endif
                        </td>
                    @endfor
                </tr>
            @endfor
        </table>
    </div>
@empty
    @unless($isPdf ?? false)
        <div class="empty">No admit cards to print.</div>
    @endunless
@endforelse

@unless($isPdf ?? false)
<script>
    // Auto-trigger print if opened with ?print=1
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.addEventListener('load', function () { window.print(); });
    }
</script>
@endunless
</body>
</html>
