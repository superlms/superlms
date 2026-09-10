{{-- One admit card, sized for a quarter of an A4 portrait sheet (2×2 grid).
     Same sections as the full-page card — masthead, identity + photo, paper
     schedule, instructions, foot — just scaled to fit the quadrant. Shared by
     the browser view, the downloaded PDF and the print sheet, so all three
     look identical. Table-based (no flex/grid) because dompdf renders this. --}}
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
    $photoPath = $admitCard->student_photo ?: ($admitCard->studentDetail?->image ?? null);
    $photoSrc  = $imgSrc($photoPath);
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
    // wordy school note can never push the foot off the quadrant.
    $notes = $admitCard->instructions
        ? collect(preg_split('/\r?\n|(?<=\.)(?=\s*\d+\.)/', $admitCard->instructions))
            ->map(fn ($l) => preg_replace('/^\d+\.\s*/', '', trim($l)))
            ->filter()
            ->values()
        : collect([
            'Reach the examination hall 15 minutes before the scheduled time.',
            'Carry this admit card and your school identity card to every paper.',
            'Follow the instructions printed on the answer book strictly.',
            'Hand the answer script to the invigilator before leaving the hall.',
        ]);
    $notes = $notes->take(4);
@endphp

<div class="card{{ $papers->count() > 7 ? ' dense' : '' }}{{ $papers->count() > 11 ? ' tight' : '' }}">

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
            <td class="id-facts">
                <table class="facts">
                    <tr>
                        <td class="k">Name</td>
                        <td class="v" colspan="3">{{ $admitCard->student_name }}</td>
                    </tr>
                    <tr>
                        <td class="k">Class</td>
                        <td class="v">{{ $student?->standard?->name ?? '—' }}@if($student?->section?->name) · {{ $student->section->name }}@endif</td>
                        <td class="k2">Roll No.</td>
                        <td class="v">{{ $admitCard->roll_number ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Adm. No.</td>
                        <td class="v">{{ $student?->admission_no ?: '—' }}</td>
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
                    <div class="passport-ph">Affix<br>photo</div>
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
                        <th style="width:30%;">Subject</th>
                        <th style="width:19%;">Date</th>
                        <th style="width:25%;">Time</th>
                        <th style="width:15%;">Room</th>
                        <th style="width:11%;" class="c">Seat</th>
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
                            <td class="{{ $date ? '' : 'off' }}">{{ $date ? $date->format('d M, D') : '—' }}</td>
                            <td class="{{ $from ? '' : 'off' }}">{{ $from ? ($to ? $from . '–' . $to : $from) : '—' }}</td>
                            <td class="{{ ($seat['room'] ?? null) ? '' : 'off' }}">{{ $seat['room'] ?? '—' }}</td>
                            <td class="c seat">{{ $seat['seat'] ?? '—' }}</td>
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
