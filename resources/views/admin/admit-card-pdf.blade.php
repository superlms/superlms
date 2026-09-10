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

        @page { size: A4 portrait; margin: 15mm; }

        body {
            font-family: 'Poppins', 'Inter', 'DejaVu Sans', Arial, sans-serif;
            font-size: 9.5pt; color: #16181d; background: #fff; line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }
        /* dompdf collapses font-weight per family, so each weight is its own
           family name (see App\Support\PdfFonts). */
        .muted { color: #6b7280; }

        .rule { border-top: 0.6pt solid #16181d; height: 0; }

        /* ── Masthead: logo, name, one line of contacts ── */
        .masthead { text-align: center; padding-bottom: 5mm; }
        .masthead .logo { height: 15mm; width: 15mm; margin-bottom: 2.5mm; }
        .masthead .school {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 16pt; letter-spacing: -0.01em; line-height: 1.2;
        }
        .masthead .address { font-size: 8pt; color: #6b7280; margin-top: 1.5mm; }

        /* ── Title row: the tag on the left, the exam on the right ── */
        .titlebar { width: 100%; border-collapse: collapse; margin: 4mm 0 5mm; }
        .titlebar td { vertical-align: baseline; font-size: 9pt; }
        .titlebar .tag {
            font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600;
            font-size: 10.5pt; letter-spacing: 0.24em; text-transform: uppercase;
        }
        .titlebar .exam { text-align: right; color: #6b7280; font-size: 9pt; }

        /* ── Identity: facts on the left, photo on the right ── */
        .id-wrap { width: 100%; border-collapse: collapse; }
        .id-wrap > tbody > tr > td { vertical-align: top; }
        .id-photo { width: 30mm; padding-left: 8mm; }
        .passport { width: 25mm; height: 32mm; border: 0.6pt solid #d9dce1; }
        .passport-ph {
            width: 25mm; height: 32mm; border: 0.6pt dashed #d9dce1; color: #9aa0a6;
            font-size: 7pt; text-align: center; padding-top: 12mm; line-height: 1.5;
        }
        .photo-cap {
            width: 25mm; font-size: 6.5pt; color: #9aa0a6; margin-top: 1.5mm;
            text-align: center; letter-spacing: 0.08em; text-transform: uppercase;
        }

        .facts { width: 100%; border-collapse: collapse; }
        .facts td { padding: 1.6mm 0; font-size: 9.5pt; vertical-align: top; border-bottom: 0.6pt solid #f0f1f3; }
        .facts tr:last-child td { border-bottom: 0; }
        .facts td.k  { color: #6b7280; width: 24mm; }
        .facts td.k2 { color: #6b7280; width: 24mm; padding-left: 8mm; }
        .facts td.v  { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }

        /* ── Section label ── */
        .sec-label {
            font-size: 7.5pt; letter-spacing: 0.16em; text-transform: uppercase; color: #6b7280;
            margin-bottom: 2mm;
        }
        .block { margin-top: 6mm; }

        /* ── Paper schedule ── */
        .papers { width: 100%; border-collapse: collapse; }
        .papers th {
            font-size: 7pt; letter-spacing: 0.08em; text-transform: uppercase; color: #6b7280;
            font-weight: normal; text-align: left; padding: 0 2mm 1.6mm; border-bottom: 0.6pt solid #16181d;
        }
        .papers td { font-size: 9pt; padding: 2mm; border-bottom: 0.6pt solid #f0f1f3; white-space: nowrap; }
        .papers td:first-child { white-space: normal; }
        .papers tr:last-child td { border-bottom: 0; }
        .papers th:first-child, .papers td:first-child { padding-left: 0; }
        .papers th.c, .papers td.c { text-align: center; }
        .papers th:last-child, .papers td:last-child { padding-right: 0; }
        .papers .seat { font-family: 'Poppins SemiBold', 'Poppins', 'Inter', sans-serif; font-weight: 600; }
        .papers .off { color: #9aa0a6; }

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
            background: #fff; padding: 15mm;
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
            .page { padding: 10mm; }
        }
        @endunless
    </style>
</head>
<body>

{{-- Print / Back / Delete — only in the browser preview, never in the PDF. --}}
@unless($isPdf ?? false)
@php $isAccounts = request()->routeIs('accounts.*'); @endphp
<div class="toolbar no-print">
    <span class="who">{{ $admitCard->student_name }}</span>
    <a href="{{ route($isAccounts ? 'accounts.admit-card.download' : 'admin.admit-card.download', [$admitCard->organization_id, $admitCard->id]) }}">Download</a>
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

    @php
        // Resolve an image path for dompdf. Absolute URLs are used as-is
        // (remote images are enabled on the PDF), otherwise prefer the local
        // storage-symlink file and fall back to the disk's public URL (S3).
        $imgSrc = function ($path) {
            if (!$path) return null;
            if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) return $path;
            $local = public_path('storage/' . ltrim($path, '/'));
            if (is_file($local)) return $local;
            try { return \Illuminate\Support\Facades\Storage::url($path); } catch (\Throwable $e) { return $local; }
        };
        $logoSrc   = $imgSrc($organization->logo ?? null);
        $photoPath = $admitCard->student_photo ?: ($admitCard->studentDetail?->image ?? null);
        $photoSrc  = $imgSrc($photoPath);

        $papers = collect($admitCard->subjects ?? [])
            ->sortBy(fn ($p) => (($p['exam_date'] ?? '') ?: '9999-12-31') . ' ' . ($p['exam_time'] ?? ''))
            ->values();
        $sessions = $admitCard->seating_sessions ?? [];

        $contacts = collect([
            $organization->address ?? null,
            $organization->mobile_number ?? null,
            $organization->email ?? null,
        ])->filter()->implode('  ·  ');
    @endphp

    {{-- ── MASTHEAD ── --}}
    <div class="masthead">
        @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="">
        @endif
        <div class="school">{{ $organization->name }}</div>
        @if($contacts)
            <div class="address">{{ $contacts }}</div>
        @endif
    </div>
    <div class="rule"></div>

    {{-- ── TITLE ── --}}
    <table class="titlebar">
        <tr>
            <td class="tag">Admit Card</td>
            <td class="exam">{{ $admitCard->exam_name }}@if($admitCard->academic_year) · {{ $admitCard->academic_year }}@endif</td>
        </tr>
    </table>

    {{-- ── IDENTITY ── --}}
    <table class="id-wrap">
        <tr>
            <td>
                <table class="facts">
                    <tr>
                        <td class="k">Name</td>
                        <td class="v" colspan="3">{{ $admitCard->student_name }}</td>
                    </tr>
                    <tr>
                        <td class="k">Class</td>
                        <td class="v">
                            {{ $admitCard->studentDetail?->standard?->name ?? '—' }}@if($admitCard->studentDetail?->section?->name) · {{ $admitCard->studentDetail->section->name }}@endif
                        </td>
                        <td class="k2">Roll No.</td>
                        <td class="v">{{ $admitCard->roll_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Admission No.</td>
                        <td class="v">{{ $admitCard->studentDetail?->admission_no ?: '—' }}</td>
                        <td class="k2">{{ $admitCard->exam_roll_number ? 'Exam Roll' : 'Card No.' }}</td>
                        <td class="v">{{ $admitCard->exam_roll_number ?: $admitCard->admit_card_number }}</td>
                    </tr>
                    <tr>
                        <td class="k">Father</td>
                        <td class="v">{{ $admitCard->father_name ?: '—' }}</td>
                        <td class="k2">Mother</td>
                        <td class="v">{{ $admitCard->mother_name ?: '—' }}</td>
                    </tr>
                </table>
            </td>
            <td class="id-photo">
                @if($photoSrc)
                    <img src="{{ $photoSrc }}" class="passport" alt="Candidate photo">
                @else
                    <div class="passport-ph">Affix<br>passport<br>photo</div>
                @endif
                <div class="photo-cap">Candidate</div>
            </td>
        </tr>
    </table>

    {{-- ── PAPER SCHEDULE ── --}}
    <div class="block">
        <div class="sec-label">Examination Schedule</div>
        @if($papers->isNotEmpty())
            <table class="papers">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th style="width:26mm;">Date</th>
                        <th style="width:14mm;">Day</th>
                        <th style="width:38mm;">Time</th>
                        <th style="width:26mm;">Room</th>
                        <th style="width:14mm;" class="c">Seat</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($papers as $paper)
                        @php
                            $date = !empty($paper['exam_date']) ? \Carbon\Carbon::parse($paper['exam_date']) : null;
                            // Seat comes from the seating plan for this paper's own session,
                            // so a student can sit in a different room on a different date.
                            $seat = \App\Services\Seating\SeatLocator::seatFor(
                                $sessions, $paper['exam_date'] ?? null, $paper['shift'] ?? 1
                            );
                            $from = !empty($paper['exam_time']) ? \Carbon\Carbon::parse($paper['exam_time'])->format('g:i A') : '';
                            $to   = !empty($paper['exam_end_time']) ? \Carbon\Carbon::parse($paper['exam_end_time'])->format('g:i A') : '';
                        @endphp
                        <tr>
                            <td>{{ $paper['subject_name'] ?? '—' }}</td>
                            <td>{{ $date ? $date->format('d M Y') : '—' }}</td>
                            <td class="{{ $date ? '' : 'off' }}">{{ $date ? $date->format('D') : '—' }}</td>
                            <td class="{{ $from ? '' : 'off' }}">{{ $from ? ($to ? $from . ' – ' . $to : $from) : '—' }}</td>
                            <td class="{{ ($seat['room'] ?? null) ? '' : 'off' }}">{{ $seat['room'] ?? '—' }}</td>
                            <td class="c seat">{{ $seat['seat'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="muted" style="font-size:9pt;">The datesheet for this class has not been published yet.</p>
        @endif
    </div>

    {{-- ── INSTRUCTIONS ── --}}
    <div class="block">
        <div class="sec-label">Instructions</div>
        <ol class="notes">
            @if($admitCard->instructions)
                @foreach(array_filter(preg_split('/\r?\n|(?<=\.)(?=\s*\d+\.)/', $admitCard->instructions)) as $line)
                    @if(trim($line))
                        <li>{{ preg_replace('/^\d+\.\s*/', '', trim($line)) }}</li>
                    @endif
                @endforeach
            @else
                <li>Reach the examination hall 15 minutes before the scheduled time. Entry is not permitted 15 minutes after the paper begins.</li>
                <li>Carry this admit card and your school identity card to every paper.</li>
                <li>Read the instructions printed on the answer book and follow them strictly.</li>
                <li>Hand the answer script to the invigilator before leaving the hall; re-entry is not permitted.</li>
            @endif
        </ol>
    </div>

    {{-- ── FOOT ── --}}
    <table class="foot">
        <tr>
            <td>
                Card No. {{ $admitCard->admit_card_number }}<br>
                Issued {{ $admitCard->issue_date?->format('d M Y') ?? now()->format('d M Y') }}
            </td>
            <td class="sign">
                <div class="line"></div>
                Authorised Signatory
            </td>
        </tr>
    </table>

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
