{{-- The admit card itself — one markup, two stylesheets.
     `admin.admit-card-page`  renders it on a full A4 page (view + download).
     `admin.admit-card-sheet` renders it in a quadrant, four to an A4 (print).
     Both keep the same sections: masthead, identity table, paper schedule,
     instructions and the signature foot. Table-based (no flex/grid) because
     dompdf renders this same markup for the PDF. --}}
@php
    // No logo on the card: the masthead is the school's name over its contact
    // line, both set flush left, and nothing else.
    $org     = $admitCard->organization ?: $organization;
    $student = $admitCard->studentDetail;

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

    // One line of instructions, not a numbered list. A school note written as
    // several lines is rolled into that same single sentence, and the whole
    // thing is capped so a wordy note can never push the foot off a quadrant.
    $note = collect(preg_split('/\r?\n|(?<=\.)(?=\s*\d+\.)/', (string) $admitCard->instructions))
        ->map(fn ($l) => trim(preg_replace('/^\d+[.)]\s*/', '', trim($l)), " \t.;,"))
        ->filter()
        ->implode('; ');

    $note = $note
        ? $note . '.'
        : 'Reach the examination hall 15 minutes before the paper begins, carry this admit card with your school identity card to every paper, follow the instructions printed on the answer book, and hand the answer script to the invigilator before leaving.';

    $note = \Illuminate\Support\Str::limit($note, 320);
@endphp

@php
    // The 4-up sheet prints every card at one size: the masthead, the identity
    // grid and the foot never change scale. Only a schedule longer than the
    // window holds tightens its own rows, so a long datesheet still fits the
    // quadrant without making the card around it look like a different card.
    // The full-page stylesheet ignores these — it has room for any count.
    $tier = match (true) {
        $papers->count() > 15 => ' many many-x many-xx',
        $papers->count() > 12 => ' many many-x',
        $papers->count() > 10 => ' many',
        default               => '',
    };
@endphp

<div class="card{{ $tier }}">

  {{-- Everything that flows from the top of the card. On the 4-up sheet this
       box carries the card's padding, because the card itself cannot (see the
       .pad note in admit-card-sheet). --}}
  <div class="pad">

    {{-- ── MASTHEAD — name over contacts, flush left, no logo ── --}}
    <div class="masthead">
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
        {{-- On the 4-up sheet this window has a fixed height, which is what
             keeps every card on the page the same shape. On the full page it
             is an ordinary div. --}}
        <div class="sched">
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
                            <td>{{ $date ? $date->format('d M Y, D') : '—' }}</td>
                            <td>{{ $from ? ($to ? $from . ' – ' . $to : $from) : '—' }}</td>
                            <td class="c seat">{{ $where }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="no-papers">The datesheet for this class has not been published yet.</div>
        @endif
        </div>
    </div>

  </div>

    {{-- ── INSTRUCTIONS — the whole note in one line, pinned to the bottom of
           the quadrant on the sheet ── --}}
    <div class="block instructions">
        <div class="sec-label">Instructions</div>
        <div class="note">{{ $note }}</div>
    </div>

    {{-- ── FOOT — the wrapper is what carries the pin on the 4-up sheet:
           dompdf resolves `bottom` on a div but not on a table, and a pinned
           table ends up hanging off the bottom of the card. ── --}}
    <div class="foot-wrap">
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

</div>
