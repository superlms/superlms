<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admit Card – {{ $admitCard->student_name ?? 'Student' }}</title>
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
           dompdf's page box and silently wipes @page, bleeding the card to the
           paper edge. Reset only the elements this card actually uses. */
        * { box-sizing: border-box; }
        body, h1, p, ol, li, table, td, th, div { margin: 0; padding: 0; }

        /* Header sits high on the sheet — a small top margin, and the rest of
           the card follows it down the page. */
        @page { size: A4 portrait; margin: 10mm 15mm 15mm; }

        body {
            font-family: 'Poppins', 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5pt; color: #16181d; background: #fff; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        /* dompdf collapses font-weight per family, so each weight is its own
           family name (see App\Support\PdfFonts). */
        .muted { color: #6b7280; }

        /* ── Masthead: logo, name, one line of contacts ── */
        .masthead { text-align: center; padding-bottom: 4mm; border-bottom: 0.6pt solid #16181d; }
        .masthead .logo { height: 15mm; width: 15mm; margin-bottom: 2mm; }
        .masthead .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 16pt; letter-spacing: -0.01em; line-height: 1.2;
        }
        .masthead .address { font-size: 8pt; color: #6b7280; margin-top: 1.5mm; }

        /* ── Title row: the tag on the left, the exam on the right ── */
        .titlebar { width: 100%; border-collapse: collapse; margin: 4mm 0 4mm; }
        .titlebar td { vertical-align: baseline; font-size: 9pt; }
        .titlebar .tag {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 10.5pt; letter-spacing: 0.24em; text-transform: uppercase;
        }
        .titlebar .exam { text-align: right; color: #6b7280; font-size: 9pt; }

        /* ── Identity: a bordered grid, same shape as the report card ── */

        .info { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info td {
            border: 0.6pt solid #aaaaaa; padding: 1.8mm 2.5mm; font-size: 9pt;
            vertical-align: top; word-wrap: break-word;
        }
        .info td.label {
            color: #3f4451;
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
        }

        /* ── Section label ── */
        .sec-label {
            font-size: 7.5pt; letter-spacing: 0.16em; text-transform: uppercase; color: #6b7280;
            margin-bottom: 2mm;
        }
        .block { margin-top: 6mm; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .papers th {
            font-size: 7pt; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280;
            font-weight: normal; text-align: left; padding: 0 2mm 1.6mm; border-bottom: 0.6pt solid #16181d;
        }
        .papers td {
            font-size: 9pt; padding: 2mm; border-bottom: 0.6pt solid #f0f1f3;
            white-space: nowrap; overflow: hidden;
        }
        .papers td:first-child { white-space: normal; word-wrap: break-word; }
        .papers tr:last-child td { border-bottom: 0; }
        .papers th:first-child, .papers td:first-child { padding-left: 0; }
        .papers th.c, .papers td.c { text-align: center; }
        .papers th:last-child, .papers td:last-child { padding-right: 0; }
        .papers .seat { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }
        .papers .off { color: #9aa0a6; }
        .no-papers { font-size: 9pt; color: #9aa0a6; }

        /* ── Instructions ── */
        .notes { padding-left: 14px; }
        .notes li { font-size: 8pt; color: #6b7280; margin-bottom: 1.2mm; line-height: 1.5; padding-left: 1mm; }

        /* ── Foot ── */
        .foot { width: 100%; border-collapse: collapse; margin-top: 10mm; }
        .foot td { font-size: 8pt; color: #6b7280; vertical-align: bottom; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 0.6pt solid #16181d; width: 45mm; margin: 0 0 1.5mm auto; height: 0; }

        @unless($isPdf ?? false)
        /* ═══ Screen only — a plain sheet on a plain ground, nothing else.
               dompdf's default media type is "screen", so this block is kept
               out of the PDF by Blade rather than by a media query. ═══ */
        body { background: #f1f3f6; padding: 22px 16px 48px; }
        .page {
            width: 210mm; max-width: 100%; min-height: 297mm; margin: 0 auto;
            background: #fff; padding: 10mm 15mm 15mm;
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

        @media print {
            body { background: #fff; padding: 0; }
            .page { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .no-print { display: none !important; }
        }
        @media (max-width: 820px) {
            body { padding: 12px 10px 32px; }
            .page { padding: 8mm 10mm 10mm; }
        }
        @endunless
    </style>
</head>
<body>

{{-- Print / Back / Delete — only in the browser preview, never in the PDF.
     "Print this card" in the listing goes through the four-up sheet instead,
     so a single card printed from there takes only its own quarter. --}}
@unless($isPdf ?? false)
@php $isAccounts = request()->routeIs('accounts.*'); @endphp
<div class="toolbar no-print">
    <span class="who">{{ $admitCard->student_name }}</span>
    <a href="{{ route($isAccounts ? 'accounts.admit-card.download' : 'admin.admit-card.download', [$admitCard->organization_id, $admitCard->id]) }}">Download</a>
    <a href="{{ route($isAccounts ? 'accounts.admit-card.print-all' : 'admin.admit-card.print-all', [$admitCard->organization_id]) }}?ids={{ $admitCard->id }}">Print sheet</a>
    <button type="button" onclick="window.opener ? window.close() : history.back()">Back</button>
    @unless($isAccounts)
        <form method="POST" action="{{ route('admin.admit-card.destroy', [$admitCard->organization_id, $admitCard->id]) }}"
              onsubmit="return confirm('Delete this admit card? The student will move back to the not-issued list.');">
            @csrf
            <button type="submit" class="del">Delete</button>
        </form>
    @endunless
    <button type="button" class="go" onclick="window.print()">Print</button>
</div>
@endunless

<div class="page">
    @include('admin._admit-card-cell', ['admitCard' => $admitCard, 'organization' => $organization])
</div>

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
