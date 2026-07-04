<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Transfer Certificate - {{ $tc->student->full_name ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { size: A4 portrait; margin: 8mm; }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9pt;
            color: #111;
            background: #fff;
        }

        .sheet { border: 1.5px solid #2b2b2b; }

        /* ── Header ── */
        .hdr { width: 100%; border-collapse: collapse; }
        .hdr td { vertical-align: middle; padding: 4mm 5mm 3mm; }
        .logo-cell { width: 26mm; text-align: center; }
        .logo-cell img { max-height: 19mm; max-width: 24mm; }
        .info-cell { text-align: center; padding-right: 26mm; }

        .school-name {
            font-family: "DejaVu Serif", Georgia, serif;
            font-size: 18pt; font-weight: bold;
            letter-spacing: 1px; text-transform: uppercase; color: #111;
        }
        .affil { font-size: 9.5pt; letter-spacing: 0.5px; text-transform: uppercase; margin-top: 1.5mm; color: #222; }
        .addr  { font-size: 9pt; text-transform: uppercase; margin-top: 0.8mm; color: #333; }

        /* ── School code / affiliation band ── */
        .codes { width: 100%; border-collapse: collapse; border-top: 1.2px solid #2b2b2b; }
        .codes td {
            padding: 2mm 5mm; font-size: 12pt; text-transform: uppercase;
            font-family: "DejaVu Serif", Georgia, serif; color: #111;
        }
        .codes td.right { text-align: right; }

        /* ── Banner ── */
        .banner {
            background: #3f3f46; color: #fff; text-align: center;
            font-family: "DejaVu Serif", Georgia, serif;
            font-weight: bold; font-size: 16pt; letter-spacing: 4px;
            text-transform: uppercase; padding: 3mm 0;
        }

        /* ── Book No / Admission No ── */
        .badm { width: 100%; border-collapse: collapse; border-bottom: 1.2px solid #2b2b2b; }
        .badm td { padding: 2.5mm 5mm; font-size: 11pt; }
        .badm td.right { text-align: right; }

        /* ── Data list ── */
        .data-wrap { padding: 3.5mm 6mm 3mm; }
        .data { width: 100%; border-collapse: collapse; }
        .data td { vertical-align: top; padding: 1.4mm 0; font-size: 9pt; line-height: 1.35; color: #111; }
        .data td.num { width: 7mm; }
        .data strong { font-weight: bold; color: #000; }

        /* ── Signatures ── */
        .sig { width: 100%; border-collapse: collapse; margin-top: 6mm; }
        .sig td { text-align: center; width: 33.33%; font-size: 10pt; font-weight: bold; padding: 0 5mm 7mm; color: #111; }
    </style>
</head>
<body>
@php
    $org = $tc->organization;

    // Robust logo: use a full URL directly (S3), else a local public file if present.
    $logoSrc = null;
    if (!empty($org?->logo)) {
        if (\Illuminate\Support\Str::startsWith($org->logo, ['http://', 'https://'])) {
            $logoSrc = $org->logo;
        } elseif (file_exists(public_path('storage/' . $org->logo))) {
            $logoSrc = public_path('storage/' . $org->logo);
        }
    }

    $ones = ['','ONE','TWO','THREE','FOUR','FIVE','SIX','SEVEN','EIGHT','NINE',
             'TEN','ELEVEN','TWELVE','THIRTEEN','FOURTEEN','FIFTEEN','SIXTEEN',
             'SEVENTEEN','EIGHTEEN','NINETEEN','TWENTY','TWENTY ONE','TWENTY TWO',
             'TWENTY THREE','TWENTY FOUR','TWENTY FIVE','TWENTY SIX','TWENTY SEVEN',
             'TWENTY EIGHT','TWENTY NINE','THIRTY','THIRTY ONE'];
    $months = ['','JANUARY','FEBRUARY','MARCH','APRIL','MAY','JUNE',
               'JULY','AUGUST','SEPTEMBER','OCTOBER','NOVEMBER','DECEMBER'];

    // Date of birth in words
    $dobWords = '—';
    if ($tc->student?->dob) {
        $d = $tc->student->dob;
        $year = (int) $d->year;
        $thousands = intdiv($year, 1000);
        $hundreds  = intdiv($year % 1000, 100);
        $tens      = $year % 100;
        $yr = ($thousands > 0 ? $ones[$thousands] . ' THOUSAND ' : '')
            . ($hundreds > 0 ? $ones[$hundreds] . ' HUNDRED ' : '')
            . ($tens > 0 && $tens <= 31 ? $ones[$tens] : '');
        $dobWords = trim($ones[(int) $d->format('j')] . ' ' . $months[(int) $d->format('n')] . ' ' . trim($yr));
    }

    // Last class in words (if numeric)
    $lastClassWords = '';
    $lcDigits = preg_replace('/[^0-9]/', '', (string) ($tc->last_class_studied ?? ''));
    if ($lcDigits !== '' && (int) $lcDigits >= 1 && (int) $lcDigits <= 31) {
        $lastClassWords = $ones[(int) $lcDigits];
    }
@endphp

<div class="sheet">

    {{-- ── SCHOOL HEADER ── --}}
    <table class="hdr">
        <tr>
            <td class="logo-cell">
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo">
                @endif
            </td>
            <td class="info-cell">
                <div class="school-name">{{ strtoupper($org->name ?? 'School Name') }}</div>
                <div class="affil">Affiliated to {{ $org->education_board ?: 'CBSE, New Delhi' }}</div>
                @if ($org->address ?? false)
                    <div class="addr">{{ $org->address }}</div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── SCHOOL CODE / AFFILIATION NO ── --}}
    <table class="codes">
        <tr>
            <td>School Code:{{ $org->school_code ?: '—' }}</td>
            <td class="right">Affiliation No:{{ $org->affiliation_no ?: '—' }}</td>
        </tr>
    </table>

    {{-- ── BANNER ── --}}
    <div class="banner">Transfer Certificate</div>

    {{-- ── BOOK NO / ADMISSION NO ── --}}
    <table class="badm">
        <tr>
            <td>Book No: <strong>{{ $tc->book_no ?: '—' }}</strong></td>
            <td class="right">Admission No: <strong>{{ $tc->student->admission_no ?? '—' }}</strong></td>
        </tr>
    </table>

    {{-- ── DATA LIST ── --}}
    <div class="data-wrap">
        <table class="data">
            <tr>
                <td class="num">1.</td>
                <td>Name of pupil:- <strong>{{ $tc->student->full_name ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">2.</td>
                <td>Mother's Name:- <strong>{{ $tc->student->mother_name ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">3.</td>
                <td>Father's / Guardian Name:- <strong>{{ $tc->student->father_name ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">4.</td>
                <td>Nationality:- <strong>{{ $tc->nationality }}</strong></td>
            </tr>
            <tr>
                <td class="num">5.</td>
                <td>Whether the Candidate belongs to Schedule Caste or Schedule Tribe:- <strong>{{ $tc->is_sc_st ? 'Yes' : 'No' }}</strong></td>
            </tr>
            <tr>
                <td class="num">6.</td>
                <td>
                    Date of first admission in the school with Class:-
                    <strong>{{ $tc->student->date_of_admission?->format('d/m/Y') ?? '—' }}</strong>
                    @if ($tc->student->standard?->name ?? false)
                        &nbsp;&nbsp; Class:- <strong>{{ $tc->student->standard->name }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="num">7.</td>
                <td>
                    Date of birth according to Admission register (in figure):-
                    <strong>{{ $tc->student->dob?->format('d/m/Y') ?? '—' }}</strong>
                    &nbsp; (in words):- <strong>{{ $dobWords }}</strong>
                </td>
            </tr>
            <tr>
                <td class="num">8.</td>
                <td>
                    Class in which the pupil last studied (in figures):-
                    <strong>{{ $tc->last_class_studied ?: '—' }}</strong>
                    @if ($lastClassWords)
                        &nbsp; (in words):- <strong>{{ $lastClassWords }}</strong>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="num">9.</td>
                <td>School/Board Annual examination last taken with results:- <strong>{{ $tc->exam_last_taken ?: '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">10.</td>
                <td>Whether failed, if so once/twice in the same class:- <strong>{{ $tc->whether_failed }}</strong></td>
            </tr>
            <tr>
                <td class="num">11.</td>
                <td>Subjects studied:- <strong>{{ $tc->subjects_studied ?: '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">12.</td>
                <td>Whether qualified for promotion to the higher class:- <strong>{{ $tc->qualified_for_promotion }}</strong></td>
            </tr>
            <tr>
                <td class="num">13.</td>
                <td>Month upto which the (pupil has paid) school dues paid:- <strong>{{ $tc->fees_paid_upto ?: '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">14.</td>
                <td>Any fee concession availed of: if so, the nature of such concession:- <strong>{{ $tc->fee_concession ?: 'None' }}</strong></td>
            </tr>
            <tr>
                <td class="num">15.</td>
                <td>Total No. of working days:- <strong>{{ $tc->total_working_days }}</strong></td>
            </tr>
            <tr>
                <td class="num">16.</td>
                <td>Total No. of working days present:- <strong>{{ $tc->days_present }}</strong></td>
            </tr>
            <tr>
                <td class="num">17.</td>
                <td>Whether NCC Cadet/Boy Scout/Girl Guide:- <strong>{{ $tc->is_ncc_scout }}</strong></td>
            </tr>
            <tr>
                <td class="num">18.</td>
                <td>Game played or extra curricular activities in which pupil usually took part (mention):- <strong>{{ $tc->extra_activities ?: 'None' }}</strong></td>
            </tr>
            <tr>
                <td class="num">19.</td>
                <td>General conduct:- <strong>{{ $tc->general_conduct }}</strong></td>
            </tr>
            <tr>
                <td class="num">20.</td>
                <td>Date of application for certificate:- <strong>{{ $tc->application_date?->format('d/m/Y') ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">21.</td>
                <td>Date of issue of certificate:- <strong>{{ $tc->issue_date?->format('d/m/Y') ?? '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">22.</td>
                <td>Reason for leaving the school:- <strong>{{ $tc->reason_for_leaving ?: '—' }}</strong></td>
            </tr>
            <tr>
                <td class="num">23.</td>
                <td>Any other Remark:- <strong>{{ $tc->remarks ?: 'No' }}</strong></td>
            </tr>
        </table>
    </div>

    {{-- ── SIGNATURES ── --}}
    <table class="sig">
        <tr>
            <td>Class Teacher</td>
            <td>Issuer</td>
            <td>Principle</td>
        </tr>
    </table>

</div>
</body>
</html>
