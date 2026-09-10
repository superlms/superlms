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

        /* A4 landscape is 297 × 210mm; 6mm margins leave 285 × 198mm of paper
           for two 142.5mm columns and two 99mm rows, with enough slack that
           rounding can never push the fourth card onto a second sheet. */
        @page { size: A4 landscape; margin: 6mm; }

        body {
            font-family: 'Poppins', 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 6pt; color: #16181d; background: #fff; line-height: 1.35;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══ The sheet: four quadrants of an A4 landscape page ═══
           A table, not grid/flex, because dompdf renders this same markup for
           the download. Every card is one 99mm-tall cell, so a run of one card
           occupies exactly one quadrant instead of stretching over the page. */
        .sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet .cell { width: 50%; height: 99mm; vertical-align: top; padding: 0; }
        .sheet tr { page-break-inside: avoid; }
        /* Cut lines only where a card actually has a neighbour, so a sheet of
           one or three cards prints clean. */
        .sheet .cell.br { border-right: 0.5pt dotted #b0b5bb; }
        .sheet .cell.bb { border-bottom: 0.5pt dotted #b0b5bb; }

        .sheet-wrap { page-break-after: always; }
        .sheet-wrap.last { page-break-after: auto; }

        /* Fixed height and clipped: whatever a card holds, it occupies exactly
           its quadrant, so four always land on one sheet and a fifth can never
           be squeezed out onto the next. The type tiers below are sized so real
           subject counts fit well inside this without ever reaching the clip. */
        .card { padding: 3mm 3.5mm; height: 99mm; overflow: hidden; page-break-inside: avoid; }

        .muted { color: #6b7280; }

        /* ── Masthead: logo, name, one line of contacts ── */
        .masthead { text-align: center; padding-bottom: 1.2mm; border-bottom: 0.5pt solid #16181d; }
        .masthead .logo { height: 7mm; width: 7mm; margin-bottom: 0.6mm; }
        .masthead .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 9pt; letter-spacing: -0.01em; line-height: 1.2;
        }
        .masthead .address { font-size: 4.5pt; color: #6b7280; margin-top: 0.5mm; line-height: 1.3; }

        /* ── Title row: the tag on the left, the exam on the right ── */
        .titlebar { width: 100%; border-collapse: collapse; margin: 1.4mm 0; }
        .titlebar td { vertical-align: baseline; }
        .titlebar .tag {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 6.6pt; letter-spacing: 0.18em; text-transform: uppercase;
        }
        .titlebar .exam { text-align: right; color: #6b7280; font-size: 5.2pt; }

        /* ── Identity: a bordered grid, same shape as the report card ── */

        .info { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info td {
            border: 0.5pt solid #aaaaaa; padding: 0.5mm 0.9mm; font-size: 5.4pt;
            vertical-align: top; line-height: 1.2; word-wrap: break-word;
        }
        .info td.label {
            color: #3f4451;
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
        }

        /* ── Section label ── */
        .sec-label {
            font-size: 4.7pt; letter-spacing: 0.12em; text-transform: uppercase; color: #6b7280;
            margin-bottom: 0.7mm;
        }
        .block { margin-top: 1.5mm; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .papers th {
            font-size: 4.6pt; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280;
            font-weight: normal; text-align: left; padding: 0 0.9mm 0.7mm; border-bottom: 0.5pt solid #16181d;
        }
        .papers td {
            font-size: 5.6pt; padding: 0.7mm 0.9mm; border-bottom: 0.4pt solid #f0f1f3; line-height: 1.2;
            overflow: hidden; white-space: nowrap;
        }
        /* A long subject name wraps instead of being cut in half. */
        .papers td:first-child { white-space: normal; word-wrap: break-word; }
        .papers tr:last-child td { border-bottom: 0; }
        .papers th:first-child, .papers td:first-child { padding-left: 0; }
        .papers th:last-child, .papers td:last-child { padding-right: 0; }
        .papers th.c, .papers td.c { text-align: center; }
        .papers .seat { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }
        .papers .off { color: #9aa0a6; }
        .no-papers {
            font-size: 5pt; color: #9aa0a6; border: 0.5pt dashed #d9dce1;
            padding: 2.5mm; text-align: center;
        }

        /* ═══ Holding the quadrant at any subject count ═══
           A quadrant is 99mm; the fixed parts (masthead, identity, labels,
           instructions, foot) take roughly 55mm of it, leaving about 38mm for
           the schedule. Each tier shrinks the row until that many rows fit
           inside those 38mm with room to spare, so eight subjects sit exactly
           the way six do — the format does not change shape, only its scale.

             tier      papers   row      rows × height
             ──────    ──────   ─────    ─────────────
             (base)      ≤6     ~3.5mm    6 × 3.5 = 21mm
             dense      7–9     ~3.1mm    9 × 3.1 = 28mm
             tight     10–13    ~2.6mm   13 × 2.6 = 34mm
             micro       14+    ~2.2mm   16 × 2.2 = 35mm                     */
        .card.dense .papers th { font-size: 4.3pt; padding-bottom: 0.55mm; }
        .card.dense .papers td { font-size: 5.1pt; padding: 0.5mm 0.9mm; }
        .card.dense .notes li  { font-size: 4.2pt; margin-bottom: 0.3mm; }

        .card.tight .papers td { font-size: 4.7pt; padding: 0.35mm 0.8mm; }
        .card.tight .info td   { font-size: 5pt; padding: 0.4mm 0.8mm; }
        .card.tight .notes li  { font-size: 3.9pt; margin-bottom: 0.2mm; }
        .card.tight .masthead  { padding-bottom: 0.9mm; }
        .card.tight .block     { margin-top: 1.2mm; }

        .card.micro .papers th { font-size: 4pt; padding-bottom: 0.4mm; }
        .card.micro .papers td { font-size: 4.3pt; padding: 0.25mm 0.7mm; }
        .card.micro .masthead .logo { height: 5.5mm; width: 5.5mm; }
        .card.micro .masthead .school { font-size: 8pt; }
        .card.micro .titlebar  { margin: 1mm 0; }
        .card.micro .info td   { font-size: 4.8pt; padding: 0.3mm 0.7mm; }
        .card.micro .block     { margin-top: 0.9mm; }
        .card.micro .foot      { margin-top: 1mm; }
        .card.micro .notes li  { font-size: 3.5pt; margin-bottom: 0.15mm; line-height: 1.2; }

        /* ── Instructions ── */
        .notes { padding-left: 8px; }
        .notes li { font-size: 4.6pt; color: #6b7280; margin-bottom: 0.3mm; line-height: 1.3; }

        /* ── Foot ── */
        .foot { width: 100%; border-collapse: collapse; margin-top: 1.5mm; }
        .foot td { font-size: 4.8pt; color: #6b7280; vertical-align: bottom; line-height: 1.35; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 0.5pt solid #16181d; width: 22mm; margin: 0 0 0.6mm auto; height: 0; }

        @unless($isPdf ?? false)
        /* ═══ Screen only — the same sheet on a plain ground, nothing else.
               dompdf's default media type is "screen", so this block is kept
               out of the PDF by Blade rather than by a media query. ═══ */
        body { background: #f1f3f6; padding: 22px 16px 48px; }
        .sheet-wrap {
            width: 297mm; max-width: 100%; min-height: 210mm; margin: 0 auto 8mm;
            background: #fff; padding: 6mm;
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
            .sheet-wrap { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
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
    @php $items = $sheet->values(); $rows = (int) ceil($items->count() / 2); @endphp
    <div class="sheet-wrap{{ $s === $sheets->count() - 1 ? ' last' : '' }}">
        <table class="sheet">
            @for($r = 0; $r < $rows; $r++)
                <tr>
                    @for($c = 0; $c < 2; $c++)
                        @php
                            $i        = $r * 2 + $c;
                            $card     = $items[$i] ?? null;
                            $hasRight = $c === 0 && isset($items[$i + 1]);
                            $hasBelow = isset($items[$i + 2]);
                        @endphp
                        <td class="cell{{ $hasRight ? ' br' : '' }}{{ $hasBelow ? ' bb' : '' }}">
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
