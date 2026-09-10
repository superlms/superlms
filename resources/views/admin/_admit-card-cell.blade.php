{{-- The admit card itself — one markup, two stylesheets.
     `admin.admit-card-page`  renders it on a full A4 page (view + download).
     `admin.admit-card-sheet` renders it in a quadrant, four to an A4 (print).
     Both keep the same sections: masthead, identity table, paper schedule,
     instructions and the signature foot. Table-based (no flex/grid) because
     dompdf renders this same markup for the PDF. --}}
@php
    // Resolve an image path for dompdf. Absolute URLs are used as-is (remote
    // images are enabled on the PDF), otherwise prefer the local
    // storage-symlink file and fall back to the disk's public URL (S3).
    $imgSrc = function ($path) {
        if (!$path) return null;
        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) return $path;
        $local = public_path('storage/' . ltrim($path, '/'));
        if (is_file($local)) return $local;
        try { return \Illuminate\Support\Facades\Storage::url($path); } catch (\Throwable $e) { return $local; }
    };

    $org       = $admitCard->organization ?: $organization;
    $logoSrc   = $imgSrc($org->logo ?? null);
    $student   = $admitCard->studentDetail;

    // Papers in the order the student sits them.
    $papers = collect($admitCard->subjects ?? [])
        ->sortBy(fn ($p) => (($p['exam_date'] ?? '') ?: '9999-12-31') . ' ' . ($p['exam_time'] ?? ''))
        ->values();
    $sessions = $admitCard->seating_sessions ?? [];

    $contacts = collect([
        $org->address ?? null,
        $org->mobile_number ?? null,
        $org->email ?? null,
    ])->filter()->implode('  ·  ');

    // The instruction list, already split into lines. Trimmed to four so a
    // wordy school note can never push the foot off a quadrant.
    $notes = $admitCard->instructions
        ? collect(preg_split('/\r?\n|(?<=\.)(?=\s*\d+\.)/', $admitCard->instructions))
            ->map(fn ($l) => preg_replace('/^\d+\.\s*/', '', trim($l)))
            ->filter()
            ->values()
        : collect([
            'Reach the examination hall 15 minutes before the scheduled time. Entry is not permitted 15 minutes after the paper begins.',
            'Carry this admit card and your school identity card to every paper.',
            'Read the instructions printed on the answer book and follow them strictly.',
            'Hand the answer script to the invigilator before leaving the hall.',
        ]);
    $notes = $notes->take(4);
@endphp

@php
    // Density tier for the 4-up sheet, from the number of papers. The full-page
    // stylesheet ignores these — it has room for any count at one size.
    $tier = match (true) {
        $papers->count() > 13 => ' dense tight micro',
        $papers->count() > 9  => ' dense tight',
        $papers->count() > 6  => ' dense',
        default               => '',
    };
@endphp

<div class="card{{ $tier }}">

    {{-- ── MASTHEAD ── --}}
    <div class="masthead">
        @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="">
        @endif
        <div class="school">{{ $org->name }}</div>
        @if($contacts)
            <div class="address">{{ $contacts }}</div>
        @endif
    </div>

    {{-- ── TITLE ── --}}
    <table class="titlebar">
        <tr>
            <td class="tag">Admit Card</td>
            <td class="exam">{{ $admitCard->exam_name }}@if($admitCard->academic_year) · {{ $admitCard->academic_year }}@endif</td>
        </tr>
    </table>

    {{-- ── IDENTITY — a bordered grid, same shape as the report card.
           No candidate photo: the card carries the student's name, class, roll
           and card number, which is what an invigilator checks against. --}}
    <table class="info">
        {{-- Explicit columns: the first row carries a colspan, and a
             fixed-layout table without a colgroup would take its widths
             from that row and hand column 1 half the table. --}}
        <colgroup>
            <col style="width:16%"><col style="width:34%">
            <col style="width:16%"><col style="width:34%">
        </colgroup>
        <tr>
            <td class="label">Name</td>
            <td class="value" colspan="3">{{ $admitCard->student_name }}</td>
        </tr>
        <tr>
            <td class="label">Class</td>
            <td class="value">{{ $student?->standard?->name ?? '—' }}@if($student?->section?->name) · {{ $student->section->name }}@endif</td>
            <td class="label">Roll No.</td>
            <td class="value">{{ $admitCard->roll_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Adm. No.</td>
            <td class="value">{{ $student?->admission_no ?: '—' }}</td>
            <td class="label">{{ $admitCard->exam_roll_number ? 'Exam Roll' : 'Card No.' }}</td>
            <td class="value">{{ $admitCard->exam_roll_number ?: $admitCard->admit_card_number }}</td>
        </tr>
        <tr>
            <td class="label">Father</td>
            <td class="value">{{ $admitCard->father_name ?: '—' }}</td>
            <td class="label">Mother</td>
            <td class="value">{{ $admitCard->mother_name ?: '—' }}</td>
        </tr>
    </table>

    {{-- ── PAPER SCHEDULE ── --}}
    <div class="block">
        <div class="sec-label">Examination Schedule</div>
        @if($papers->isNotEmpty())
            <table class="papers">
                <thead>
                    <tr>
                        <th style="width:34%;">Subject</th>
                        <th style="width:24%;">Date</th>
                        <th style="width:26%;">Time</th>
                        <th style="width:16%;" class="c">Seat (Room)</th>
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

                            // Seat and room read as one thing — "12 (A1)".
                            $seatNo = $seat['seat'] ?? null;
                            $room   = $seat['room'] ?? null;
                            $where  = $seatNo && $room ? $seatNo . ' (' . $room . ')' : ($seatNo ?: ($room ?: '—'));
                        @endphp
                        <tr>
                            <td>{{ $paper['subject_name'] ?? '—' }}</td>
                            <td class="{{ $date ? '' : 'off' }}">{{ $date ? $date->format('d M Y, D') : '—' }}</td>
                            <td class="{{ $from ? '' : 'off' }}">{{ $from ? ($to ? $from . ' – ' . $to : $from) : '—' }}</td>
                            <td class="c seat {{ $where === '—' ? 'off' : '' }}">{{ $where }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-papers">The datesheet for this class has not been published yet.</div>
        @endif
    </div>

    {{-- ── INSTRUCTIONS ── --}}
    <div class="block">
        <div class="sec-label">Instructions</div>
        <ol class="notes">
            @foreach($notes as $note)
                <li>{{ $note }}</li>
            @endforeach
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
