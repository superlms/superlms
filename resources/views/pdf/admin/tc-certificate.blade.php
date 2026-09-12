<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Transfer Certificate - {{ $tc->student->full_name ?? '' }}</title>
    <style>
        {{-- No margin in the `*` reset: it wipes @page margins and the sheet bleeds to
             the paper edge. Page padding does the insetting instead. --}}
        * { padding: 0; }
        @page { size: A4 portrait; margin: 0; }

        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 9pt; color: #1f2937; background: #fff; }
        .page { width: 210mm; padding: 10mm 15mm; }
        .sheet { border: 0.8px solid #e5e7eb; padding: 8mm 9mm 6mm; }

        /* ── Masthead ── */
        .head { text-align: center; }
        .logo { height: 16mm; margin: 0 0 3mm; }
        .school-name { font-family: "DejaVu Serif", Georgia, serif; font-size: 16pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #111827; }
        .affil { font-size: 8pt; color: #6b7280; margin-top: 1mm; }
        .addr { font-size: 8pt; color: #6b7280; margin-top: 1mm; }
        {{-- Same masthead contact line as the achievement certificate. --}}
        .contact { font-size: 8pt; color: #9ca3af; margin-top: 1mm; }

        .rule { border-top: 0.8px solid #e5e7eb; margin: 4mm 0 0; }

        /* ── Title ── */
        .title { text-align: center; font-family: "DejaVu Serif", Georgia, serif; font-size: 12pt; font-weight: bold;
                 text-transform: uppercase; letter-spacing: 5px; color: #111827; margin: 4mm 0 0; }

        /* ── Identifier strip: label above value, no boxes ── */
        .meta { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4mm; }
        .meta td { width: 25%; vertical-align: top; }
        .meta-label { font-size: 6.5pt; text-transform: uppercase; letter-spacing: 1px; color: #9ca3af; }
        .meta-value { font-size: 9pt; font-weight: bold; color: #111827; margin-top: 0.8mm; }

        /* ── The statutory questions ── */
        {{-- `table-layout: fixed` or dompdf hands the long labels two thirds of the row
             and squeezes the answers; percentages (not mm) are what survive it. --}}
        .data { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 4mm; }
        .data td { vertical-align: top; padding: 0.7mm 0; border-bottom: 0.5px solid #f3f4f6; line-height: 1.25; }
        .data td.num { width: 6%; font-size: 8pt; color: #9ca3af; }
        .data td.label { width: 54%; font-size: 8.5pt; color: #6b7280; padding-right: 4mm; }
        .data td.value { width: 40%; font-size: 9pt; font-weight: bold; color: #111827; }
        .data tr.last td { border-bottom: 0; }

        /* ── Signatures ── */
        .sig { width: 100%; border-collapse: collapse; table-layout: fixed; margin-top: 7mm; }
        .sig td { width: 33.33%; text-align: center; padding: 0 6mm; }
        .sig-line { border-top: 0.8px solid #d1d5db; padding-top: 2mm; font-size: 8pt; text-transform: uppercase;
                    letter-spacing: 1px; color: #6b7280; }
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

    // The controller supplies the masthead; fall back to the school record alone so
    // the template still renders when handed nothing but a TC.
    $contact = $contact ?? [];
    $address = $contact['address'] ?? ($org->address ?? null);
    $bits    = array_filter([
        $contact['mobile']  ?? ($org->mobile_number ?? null),
        $contact['email']   ?? ($org->email ?? null),
        $contact['website'] ?? null,
    ]);

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

    $admissionWith = ($tc->student->date_of_admission?->format('d/m/Y') ?? '—')
        . (($tc->student->standard?->name ?? false) ? '  ·  Class ' . $tc->student->standard->name : '');

    // One list, one shape: every question is a label and its answer.
    $rows = [
        'Name of pupil'                                          => $tc->student->full_name ?? '—',
        "Mother's name"                                          => $tc->student->mother_name ?? '—',
        "Father's / guardian name"                               => $tc->student->father_name ?? '—',
        'Nationality'                                            => $tc->nationality,
        'Belongs to Schedule Caste / Schedule Tribe'             => $tc->is_sc_st ? 'Yes' : 'No',
        'Date of first admission in the school with class'       => $admissionWith,
        'Date of birth as per admission register (in figures)'   => $tc->student->dob?->format('d/m/Y') ?? '—',
        'Date of birth (in words)'                               => $dobWords,
        'Class in which the pupil last studied'                  => trim(($tc->last_class_studied ?: '—') . ($lastClassWords ? '  ·  ' . $lastClassWords : '')),
        'Annual examination last taken, with result'             => $tc->exam_last_taken ?: '—',
        'Whether failed, if so once / twice in the same class'    => $tc->whether_failed,
        'Subjects studied'                                       => $tc->subjects_studied ?: '—',
        'Whether qualified for promotion to the higher class'    => $tc->qualified_for_promotion,
        'Month upto which school dues are paid'                  => $tc->fees_paid_upto ?: '—',
        'Any fee concession availed, and its nature'             => $tc->fee_concession ?: 'None',
        'Total number of working days'                           => $tc->total_working_days,
        'Total number of working days present'                   => $tc->days_present,
        'Whether NCC cadet / boy scout / girl guide'             => $tc->is_ncc_scout,
        'Games / extra-curricular activities taken part in'      => $tc->extra_activities ?: 'None',
        'General conduct'                                        => $tc->general_conduct,
        'Date of application for certificate'                    => $tc->application_date?->format('d/m/Y') ?? '—',
        'Date of issue of certificate'                           => $tc->issue_date?->format('d/m/Y') ?? '—',
        'Reason for leaving the school'                          => $tc->reason_for_leaving ?: '—',
        'Any other remark'                                       => $tc->remarks ?: 'No',
    ];
@endphp

<div class="page">
    <div class="sheet">

        {{-- ── Masthead ── --}}
        <div class="head">
            @if ($logoSrc)
                <img class="logo" src="{{ $logoSrc }}" alt="Logo">
            @endif
            <div class="school-name">{{ strtoupper($org->name ?? 'School Name') }}</div>
            <div class="affil">
                Affiliated to {{ $org->education_board ?: 'CBSE, New Delhi' }}@if ($org->affiliation_no) · Affiliation No. {{ $org->affiliation_no }}@endif
            </div>
            @if ($address)
                <div class="addr">{{ $address }}</div>
            @endif
            @if (count($bits))
                <div class="contact">{{ implode(' · ', $bits) }}</div>
            @endif
        </div>

        <div class="rule"></div>
        <div class="title">Transfer Certificate</div>

        {{-- ── Identifiers ── --}}
        <table class="meta">
            <tr>
                <td>
                    <div class="meta-label">TC No</div>
                    <div class="meta-value">{{ $tc->tc_no ?: '—' }}</div>
                </td>
                <td>
                    <div class="meta-label">Book No</div>
                    <div class="meta-value">{{ $tc->book_no ?: '—' }}</div>
                </td>
                <td>
                    <div class="meta-label">Admission No</div>
                    <div class="meta-value">{{ $tc->student->admission_no ?? '—' }}</div>
                </td>
                <td>
                    <div class="meta-label">School Code</div>
                    <div class="meta-value">{{ $org->school_code ?: '—' }}</div>
                </td>
            </tr>
        </table>

        {{-- ── The record ── --}}
        <table class="data">
            @foreach ($rows as $label => $value)
                <tr @if ($loop->last) class="last" @endif>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td class="label">{{ $label }}</td>
                    <td class="value">{{ $value }}</td>
                </tr>
            @endforeach
        </table>

        {{-- ── Signatures ── --}}
        <table class="sig">
            <tr>
                <td><div class="sig-line">Class Teacher</div></td>
                <td><div class="sig-line">Issued By</div></td>
                <td><div class="sig-line">Principal</div></td>
            </tr>
        </table>

    </div>
</div>
</body>
</html>
