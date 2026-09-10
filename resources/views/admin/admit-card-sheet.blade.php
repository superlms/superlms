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

        @page { size: A4 portrait; margin: 8mm; }

        body {
            font-family: 'Poppins', 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 6.6pt; color: #16181d; background: #fff; line-height: 1.4;
            -webkit-font-smoothing: antialiased;
        }

        /* ═══ The sheet: four quadrants of an A4 portrait page ═══
           A table, not grid/flex, because dompdf renders this same markup for
           the download. Every card is one 140mm-tall cell, so a run of one card
           occupies exactly one quadrant instead of stretching over the page. */
        .sheet { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .sheet .cell { width: 50%; height: 140mm; vertical-align: top; padding: 0; }
        /* Cut lines only where a card actually has a neighbour, so a sheet of
           one or three cards prints clean. */
        .sheet .cell.br { border-right: 0.5pt dotted #b0b5bb; }
        .sheet .cell.bb { border-bottom: 0.5pt dotted #b0b5bb; }

        .sheet-wrap { page-break-after: always; }
        .sheet-wrap.last { page-break-after: auto; }

        .card { padding: 5mm; height: 140mm; }

        .muted { color: #6b7280; }
        .rule { border-top: 0.5pt solid #16181d; height: 0; }

        /* ── Masthead: logo, name, one line of contacts ── */
        .masthead { text-align: center; padding-bottom: 2mm; }
        .masthead .logo { height: 9mm; width: 9mm; margin-bottom: 1mm; }
        .masthead .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 10.2pt; letter-spacing: -0.01em; line-height: 1.2;
        }
        .masthead .address { font-size: 5.1pt; color: #6b7280; margin-top: 0.8mm; line-height: 1.35; }

        /* ── Title row: the tag on the left, the exam on the right ── */
        .titlebar { width: 100%; border-collapse: collapse; margin: 2mm 0 2.5mm; }
        .titlebar td { vertical-align: baseline; }
        .titlebar .tag {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 7.6pt; letter-spacing: 0.2em; text-transform: uppercase;
        }
        .titlebar .exam { text-align: right; color: #6b7280; font-size: 6pt; }

        /* ── Identity: facts on the left, photo on the right ── */
        .id-wrap { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .id-wrap > tbody > tr > td { vertical-align: top; }
        .id-wrap .id-photo { width: 19mm; padding-left: 3mm; }
        .passport { width: 16mm; height: 20mm; border: 0.5pt solid #d9dce1; }
        .passport-ph {
            width: 16mm; height: 20mm; border: 0.5pt dashed #d9dce1; color: #9aa0a6;
            font-size: 4.6pt; text-align: center; padding-top: 7mm; line-height: 1.4;
        }
        .photo-cap {
            width: 16mm; font-size: 4.4pt; color: #9aa0a6; margin-top: 0.8mm;
            text-align: center; letter-spacing: 0.08em; text-transform: uppercase;
        }

        .facts { width: 100%; border-collapse: collapse; table-layout: fixed; }
        /* Values wrap rather than clip — a long parent name or admission
           number must stay readable, and the card has the vertical room. */
        .facts td {
            padding: 1mm 0; font-size: 6.6pt; vertical-align: top;
            border-bottom: 0.4pt solid #f0f1f3; line-height: 1.25;
            word-wrap: break-word;
        }
        .facts tr:last-child td { border-bottom: 0; }
        .facts td.k  { color: #6b7280; width: 23%; }
        .facts td.k2 { color: #6b7280; width: 20%; padding-left: 2mm; }
        .facts td.v  { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }

        /* ── Section label ── */
        .sec-label {
            font-size: 5.3pt; letter-spacing: 0.14em; text-transform: uppercase; color: #6b7280;
            margin-bottom: 1mm;
        }
        .block { margin-top: 3mm; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .papers th {
            font-size: 5.1pt; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280;
            font-weight: normal; text-align: left; padding: 0 1mm 0.9mm; border-bottom: 0.5pt solid #16181d;
        }
        .papers td {
            font-size: 6.3pt; padding: 1mm; border-bottom: 0.4pt solid #f0f1f3; line-height: 1.2;
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
            font-size: 5.8pt; color: #9aa0a6; border: 0.5pt dashed #d9dce1;
            padding: 3mm; text-align: center;
        }

        /* A long subject list tightens rather than running off the quadrant:
           `dense` from 8 papers, `tight` from 12. */
        .card.dense .papers th { font-size: 4.6pt; padding-bottom: 0.6mm; }
        .card.dense .papers td { font-size: 5.4pt; padding: 0.6mm 1mm; }
        .card.dense .notes li  { font-size: 4.6pt; margin-bottom: 0.4mm; }
        .card.dense .block     { margin-top: 2mm; }

        .card.tight .papers td { font-size: 4.9pt; padding: 0.4mm 1mm; }
        .card.tight .facts td  { font-size: 6pt; padding: 0.7mm 0; }
        .card.tight .notes li  { font-size: 4.2pt; }
        .card.tight .masthead  { padding-bottom: 1mm; }

        /* ── Instructions ── */
        .notes { padding-left: 9px; }
        .notes li { font-size: 5.2pt; color: #6b7280; margin-bottom: 0.6mm; line-height: 1.35; }

        /* ── Foot ── */
        .foot { width: 100%; border-collapse: collapse; margin-top: 3mm; }
        .foot td { font-size: 5.3pt; color: #6b7280; vertical-align: bottom; line-height: 1.4; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 0.5pt solid #16181d; width: 26mm; margin: 0 0 0.8mm auto; height: 0; }

        @unless($isPdf ?? false)
        /* ═══ Screen only — the same sheet on a plain ground, nothing else.
               dompdf's default media type is "screen", so this block is kept
               out of the PDF by Blade rather than by a media query. ═══ */
        body { background: #f1f3f6; padding: 22px 16px 48px; }
        .sheet-wrap {
            width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto 8mm;
            background: #fff; padding: 8mm;
            box-shadow: 0 1px 3px rgba(16,24,40,.08), 0 8px 28px rgba(16,24,40,.10);
        }
        .toolbar {
            position: sticky; top: 0; z-index: 10; width: 210mm; max-width: 100%;
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
        .toolbar .del { color: #c0392b; }
        .toolbar .del:hover { background: #fdf1f0; }
        .toolbar form { margin: 0; display: inline-block; }

        .empty {
            width: 210mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 48px;
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
            <a href="{{ route($isAccounts ? 'accounts.admit-card.download' : 'admin.admit-card.download', [$single->organization_id, $single->id]) }}">Download</a>
            <button type="button" onclick="window.opener ? window.close() : history.back()">Back</button>
            @unless($isAccounts)
                <form method="POST" action="{{ route('admin.admit-card.destroy', [$single->organization_id, $single->id]) }}"
                      onsubmit="return confirm('Delete this admit card? The student will move back to the not-issued list.');">
                    @csrf
                    <button type="submit" class="del">Delete</button>
                </form>
            @endunless
        @else
            <span class="count">{{ $cards->count() }} card(s) · 4 to an A4 portrait sheet · cut along the dotted lines</span>
            <button type="button" onclick="window.opener ? window.close() : history.back()">Close</button>
        @endif
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
