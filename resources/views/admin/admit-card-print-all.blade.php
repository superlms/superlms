<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print All Admit Cards</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #fff; }

        .page { padding: 20px 24px; max-width: 780px; margin: 0 auto; page-break-after: always; }
        .page:last-child { page-break-after: avoid; }

        .header { text-align: center; margin-bottom: 12px; }
        .header img.logo { height: 80px; width: 80px; object-fit: contain; margin-bottom: 4px; }
        .header .school-name { font-size: 20px; font-weight: 900; color: #111; text-transform: uppercase; }
        .header .address { font-size: 10px; color: #444; margin-top: 3px; line-height: 1.5; }

        .ac-title { text-align: center; margin: 12px 0 8px; }
        .ac-title p { font-size: 12px; font-weight: 700; }

        .card-box { border: 1.5px solid #333; }

        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { border: 1px solid #555; padding: 4px 7px; font-size: 10.5px; vertical-align: middle; }
        .info-table .label { font-weight: 700; background: #f5f5f5; white-space: nowrap; width: 120px; }

        .subject-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
        .subject-table th { border: 1px solid #555; padding: 4px 5px; font-size: 10px; font-weight: 700; background: #f0f0f0; text-align: center; }
        .subject-table td { border: 1px solid #555; padding: 4px 5px; font-size: 10px; text-align: center; }
        .subject-table td:nth-child(2) { text-align: left; }

        .eligible     { color: #166534; font-weight: 600; }
        .not-eligible { color: #991b1b; font-weight: 600; }
        .issue-date { padding: 5px 7px; font-size: 10.5px; border-top: 1px solid #555; }

        .instructions-section { margin-top: 14px; }
        .instructions-section h4 { font-size: 11px; font-weight: 700; text-align: center; margin-bottom: 6px; text-decoration: underline; }
        .instructions-section ol { padding-left: 16px; }
        .instructions-section ol li { font-size: 10px; margin-bottom: 3px; line-height: 1.5; }

        .no-print { display: block; }

        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print" style="position:fixed;top:0;left:0;right:0;z-index:100;background:#1e293b;padding:8px 16px;display:flex;align-items:center;gap:10px;">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff;border:none;padding:6px 18px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Print All ({{ count($admitCards) }} Cards)</button>
    <button onclick="window.close()" style="background:#64748b;color:#fff;border:none;padding:6px 18px;border-radius:6px;cursor:pointer;font-size:13px;">Close</button>
</div>
<div style="height:44px;" class="no-print"></div>

@forelse($admitCards as $admitCard)
<div class="page">

    <div class="header">
        @if($organization->logo)
            <img class="logo" src="{{ public_path('storage/' . $organization->logo) }}" alt="Logo">
        @endif
        <div class="school-name">{{ $organization->name }}</div>
        <div class="address">
            {{ $organization->address }}
            @if($organization->mobile_number)
                <br>{{ $organization->mobile_number }}
                @if($organization->email) / {{ $organization->email }} @endif
            @endif
        </div>
    </div>

    <div class="ac-title">
        <p>Admit Card</p>
        <p>{{ $admitCard->academic_year }}/ Exam: {{ $admitCard->exam_name }}</p>
    </div>

    <div class="card-box">
        <table class="info-table">
            <tr>
                <td class="label">Student Name:</td>
                <td colspan="3">{{ $admitCard->student_name }}</td>
            </tr>
            <tr>
                <td class="label">Mother's Name:</td>
                <td>{{ $admitCard->mother_name ?: '—' }}</td>
                <td class="label">Father's Name:</td>
                <td>{{ $admitCard->father_name ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Admission No:</td>
                <td>{{ $admitCard->studentDetail?->admission_no ?? '—' }}</td>
                <td class="label">Roll No:</td>
                <td>{{ $admitCard->roll_number }}</td>
            </tr>
            <tr>
                <td class="label">School Name:</td>
                <td colspan="3">{{ $organization->name }}</td>
            </tr>
            <tr>
                <td class="label">Program Name:</td>
                <td>{{ $admitCard->exam_name }}</td>
                <td class="label">Class/Section</td>
                <td>
                    {{ $admitCard->studentDetail?->standard?->name ?? '—' }}
                    @if($admitCard->studentDetail?->section?->name)
                        / {{ $admitCard->studentDetail->section->name }}
                    @endif
                </td>
            </tr>
        </table>

        <table style="width:100%;border-collapse:collapse;">
            <tr><td style="height:7px;border-left:1px solid #555;border-right:1px solid #555;"></td></tr>
        </table>

        @if(!empty($admitCard->subjects))
        <table class="subject-table">
            <thead>
                <tr>
                    <th style="width:65px;">Subject Code</th>
                    <th>Course Name</th>
                    <th style="width:80px;">Date</th>
                    <th style="width:75px;">Day</th>
                    <th style="width:95px;">Seating Plan</th>
                    <th style="width:75px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admitCard->subjects as $i => $subject)
                @php
                    $subjectModel = \App\Models\Student\Subject::find($subject['subject_id'] ?? null);
                    $code = $subjectModel?->code ?? str_pad($i + 1, 3, '0', STR_PAD_LEFT);
                    $examDate = isset($subject['exam_date']) ? \Carbon\Carbon::parse($subject['exam_date']) : null;
                    $seatingPlan = $admitCard->seating_label ?? '';
                    if (!$seatingPlan) {
                        if ($admitCard->room_number && $admitCard->seat_number) {
                            $seatingPlan = 'R(' . $admitCard->room_number . ')/ S(' . $admitCard->seat_number . ')';
                        } elseif ($admitCard->seat_number) {
                            $seatingPlan = 'S(' . $admitCard->seat_number . ')';
                        } elseif ($admitCard->room_number) {
                            $seatingPlan = 'R(' . $admitCard->room_number . ')';
                        }
                    }
                    $subjectStatus = $subject['status'] ?? 'eligible';
                @endphp
                <tr>
                    <td>{{ $code }}</td>
                    <td>{{ $subject['subject_name'] ?? '—' }}</td>
                    <td>{{ $examDate ? $examDate->format('d/m/Y') : '—' }}</td>
                    <td>{{ $examDate ? $examDate->format('l') : '—' }}</td>
                    <td>{{ $seatingPlan ?: '—' }}</td>
                    <td>
                        @if($subjectStatus === 'not_eligible')
                            <span class="not-eligible">Not Eligible</span>
                        @else
                            <span class="eligible">Eligible</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="issue-date">Issue Date: {{ $admitCard->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
    </div>

    <div class="instructions-section">
        <h4>Instructions to Candidates</h4>
        @if($admitCard->instructions)
            <ol>
                @foreach(array_filter(preg_split('/\r?\n|(?<=\.)(?=\s*\d+\.)/', $admitCard->instructions)) as $line)
                    @if(trim($line))
                        <li>{{ preg_replace('/^\d+\.\s*/', '', trim($line)) }}</li>
                    @endif
                @endforeach
            </ol>
        @else
            <ol>
                <li>Enter the examination hall 15 minutes before the scheduled time. Students coming 15 minutes after commencement of the examination will not be permitted to enter or write the exam.</li>
                <li>Without identity card and Hall Ticket, no student will be permitted to enter the Exam Hall.</li>
                <li>Read all instructions printed on the answer book and follow them strictly.</li>
                <li>Candidates should handover the answer script to the invigilator before leaving the exam Hall.</li>
            </ol>
        @endif
    </div>

</div>
@empty
<div style="text-align:center;padding:40px;color:#666;">No admit cards found.</div>
@endforelse

</body>
</html>
