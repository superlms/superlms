<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Datesheet – {{ $datesheet->standard->name ?? '' }}{{ $sectionName ? ' ' . $sectionName : '' }}</title>
    <style>
        /* A plain landscape page: the school, the exam, the class, the papers.
           Nothing is coloured or boxed that a photocopier would not keep. */
        @page { size: A4 landscape; margin: 14mm 16mm; }

        * { box-sizing: border-box; }
        body, h1, p, table, td, th, div { margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
            color: #000; background: #fff; font-size: 11pt; line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        .sheet { width: 100%; }

        .head { text-align: center; border-bottom: 1px solid #000; padding-bottom: 8px; }
        .head .school { font-size: 17pt; font-weight: 600; letter-spacing: -0.01em; }
        .head .contacts { font-size: 9pt; margin-top: 3px; }
        .head .exam { font-size: 12pt; font-weight: 600; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.08em; }
        .head .who { font-size: 11pt; margin-top: 3px; }

        table.papers { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.papers th {
            font-size: 9.5pt; text-transform: uppercase; letter-spacing: 0.06em;
            text-align: left; font-weight: 600; padding: 6px 8px; border-bottom: 1px solid #000;
        }
        table.papers td { font-size: 11pt; padding: 7px 8px; border-bottom: 1px solid #c8c8c8; }
        table.papers tr:last-child td { border-bottom: 1px solid #000; }
        table.papers th:first-child, table.papers td:first-child { padding-left: 0; }
        table.papers th:last-child, table.papers td:last-child { padding-right: 0; text-align: center; }
        table.papers .no { width: 40px; }
        table.papers .subject { font-weight: 600; }

        .foot { margin-top: 26px; width: 100%; }
        .foot td { font-size: 10pt; vertical-align: bottom; }
        .foot .sign { text-align: right; }
        .foot .sign .line { border-top: 1px solid #000; width: 55mm; margin: 0 0 4px auto; height: 0; }

        .empty { margin-top: 30px; text-align: center; font-size: 11pt; }

        /* Screen only — a plain ground and the two buttons, never printed. */
        @media screen {
            body { background: #f1f3f6; padding: 20px 16px 44px; }
            .sheet {
                width: 297mm; max-width: 100%; min-height: 210mm; margin: 0 auto;
                background: #fff; padding: 14mm 16mm;
                box-shadow: 0 1px 3px rgba(16,24,40,.08), 0 8px 28px rgba(16,24,40,.10);
            }
            .toolbar {
                width: 297mm; max-width: 100%; margin: 0 auto 14px;
                display: flex; align-items: center; gap: 8px;
                background: #fff; border: 1px solid #e4e6ea; border-radius: 10px; padding: 8px 10px 8px 14px;
            }
            .toolbar .who-label { font-size: 13px; font-weight: 600; color: #16181d; margin-right: auto; }
            .toolbar button {
                font-family: inherit; font-size: 12.5px; font-weight: 500; line-height: 1;
                padding: 8px 14px; border-radius: 7px; cursor: pointer;
                border: 1px solid #e4e6ea; background: #fff; color: #3f4451;
            }
            .toolbar button:hover { background: #f7f8fa; }
            .toolbar .go { background: #16181d; border-color: #16181d; color: #fff; }
        }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>

<div class="toolbar no-print">
    <span class="who-label">
        {{ $datesheet->exam->exam_name ?? 'Exam' }} ·
        {{ $datesheet->standard->name ?? '' }}{{ $sectionName ? ' — ' . $sectionName : '' }}
    </span>
    <button type="button" onclick="window.opener ? window.close() : history.back()">Close</button>
    <button type="button" class="go" onclick="window.print()">Print</button>
</div>

<div class="sheet">
    <div class="head">
        <div class="school">{{ $organization->name ?? '' }}</div>
        @php
            $contacts = collect([
                $organization->address ?? null,
                $organization->mobile_number ?? null,
                $organization->email ?? null,
            ])->filter()->implode('  ·  ');
        @endphp
        @if ($contacts)
            <div class="contacts">{{ $contacts }}</div>
        @endif

        <div class="exam">
            {{ $datesheet->exam->exam_name ?? 'Examination' }} — Datesheet
            @if ($datesheet->exam?->academic_year) · {{ $datesheet->exam->academic_year }} @endif
        </div>
        <div class="who">
            Class {{ $datesheet->standard->name ?? '—' }}{{ $sectionName ? ' · Section ' . $sectionName : ' · All sections' }}
        </div>
    </div>

    @if ($papers->isEmpty())
        <p class="empty">No papers on this datesheet yet.</p>
    @else
        <table class="papers">
            <thead>
                <tr>
                    <th class="no">#</th>
                    <th>Subject</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Time</th>
                    <th>Shift</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($papers as $i => $paper)
                    @php
                        $date  = $paper->exam_date;
                        $from  = $paper->start_time ? \Carbon\Carbon::parse($paper->start_time)->format('g:i A') : null;
                        $to    = $paper->end_time ? \Carbon\Carbon::parse($paper->end_time)->format('g:i A') : null;
                    @endphp
                    <tr>
                        <td class="no">{{ $i + 1 }}</td>
                        <td class="subject">{{ $paper->subject->name ?? '—' }}</td>
                        <td>{{ $date?->format('d M Y') ?? '—' }}</td>
                        <td>{{ $date?->format('l') ?? '—' }}</td>
                        <td>{{ $from ? ($to ? $from . ' – ' . $to : $from) : '—' }}</td>
                        <td>{{ $paper->shift > 1 ? 'Shift ' . $paper->shift : 'Shift 1' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="foot">
        <tr>
            <td>Issued {{ now()->format('d M Y') }}</td>
            <td class="sign">
                <div class="line"></div>
                Principal / Examination In-charge
            </td>
        </tr>
    </table>
</div>

<script>
    // Opened with ?print=1 → straight to the print dialog.
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.addEventListener('load', function () { window.print(); });
    }
</script>
</body>
</html>
