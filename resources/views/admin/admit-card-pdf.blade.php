<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admit Card – {{ $admitCard->student_name ?? 'Student' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        /* Force A4 portrait everywhere (browser print + dompdf). */
        @page { size: A4 portrait; margin: 16px 20px; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #fff; }
        .page { padding: 0; max-width: 780px; margin: 0 auto; }

        /* Header */
        .header { text-align: center; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 2px solid #4f46e5; }
        .header img.logo { height: 76px; width: 76px; object-fit: contain; margin-bottom: 4px; }
        .header .school-name { font-size: 22px; font-weight: 900; color: #111; text-transform: uppercase; letter-spacing: 0.02em; }
        .header .address { font-size: 10px; color: #444; margin-top: 3px; line-height: 1.5; }

        /* Admit Card title band */
        .title-band { background: #4f46e5; color: #fff; text-align: center; padding: 6px; font-size: 14px; font-weight: 800; letter-spacing: 0.1em; text-transform: uppercase; }
        .title-sub { text-align: center; font-size: 11px; font-weight: 700; margin: 8px 0 10px; color: #333; }

        /* Outer border box */
        .card-box { border: 1.5px solid #333; }

        /* Student info + passport photo layout */
        .id-wrap { width: 100%; border-collapse: collapse; }
        .id-info { vertical-align: top; }
        .id-photo { width: 124px; vertical-align: top; }
        .photo-box { border-left: 1px solid #555; border-bottom: 1px solid #555; padding: 8px 6px; text-align: center; }
        .passport { width: 104px; height: 128px; object-fit: cover; border: 1px solid #333; }
        .passport-ph { width: 104px; height: 128px; border: 1px dashed #999; color: #999; font-size: 10px; text-align: center; padding-top: 50px; line-height: 1.4; margin: 0 auto; }
        .photo-cap { font-size: 8px; color: #777; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.04em; }

        /* Student info table */
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { border: 1px solid #555; padding: 5px 8px; font-size: 11px; vertical-align: middle; }
        .info-table td:first-child { border-left: 0; }
        .info-table .label { font-weight: 700; background: #eef2ff; white-space: nowrap; width: 120px; }
        .info-table .value { color: #111; }

        /* Subject table */
        .subject-table { width: 100%; border-collapse: collapse; }
        .subject-table th { border: 1px solid #555; padding: 5px 6px; font-size: 10px; font-weight: 700; background: #4f46e5; color: #fff; text-align: center; }
        .subject-table td { border: 1px solid #555; padding: 5px 6px; font-size: 11px; text-align: center; }
        .subject-table td:nth-child(2) { text-align: left; }
        .subject-table tbody tr:nth-child(even) td { background: #f6f7ff; }

        /* Status badges */
        .eligible     { color: #166534; font-weight: 700; }
        .not-eligible { color: #991b1b; font-weight: 700; }

        /* Footer (issue date + signatory) */
        .foot { width: 100%; border-collapse: collapse; border-top: 1px solid #555; }
        .foot td { padding: 10px 8px 6px; font-size: 11px; vertical-align: bottom; }
        .foot .issue { text-align: left; }
        .foot .sign { text-align: right; font-weight: 700; }

        /* Instructions */
        .instructions-section { margin-top: 14px; }
        .instructions-section h4 { font-size: 12px; font-weight: 800; text-align: center; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em; color: #4f46e5; }
        .instructions-section ol { padding-left: 18px; }
        .instructions-section ol li { font-size: 10.5px; margin-bottom: 4px; line-height: 1.5; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

{{-- Print / Close controls — only in the browser preview, never in the PDF. --}}
@unless($isPdf ?? false)
<div class="no-print" style="position:fixed;top:0;left:0;right:0;z-index:100;background:#1e293b;padding:8px 16px;display:flex;align-items:center;gap:10px;">
    <button onclick="window.print()" style="background:#4f46e5;color:#fff;border:none;padding:6px 18px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Print</button>
    <button onclick="window.close()" style="background:#64748b;color:#fff;border:none;padding:6px 18px;border-radius:6px;cursor:pointer;font-size:13px;">Close</button>
    <span style="color:#94a3b8;font-size:12px;margin-left:8px;">Admit Card – {{ $admitCard->student_name }}</span>
    <form method="POST" action="{{ route('admin.admit-card.destroy', [$organization->serial_number ?? $organization->id, $admitCard->id]) }}"
          style="margin-left:auto;" onsubmit="return confirm('Delete this admit card? The student will move back to the not-issued list.');">
        @csrf
        <button type="submit" style="background:#dc2626;color:#fff;border:none;padding:6px 18px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600;">Delete Admit Card</button>
    </form>
</div>
<div style="height:44px;" class="no-print"></div>
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
    @endphp

    {{-- ── HEADER ── --}}
    <div class="header">
        @if($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
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

    {{-- ── ADMIT CARD TITLE ── --}}
    <div class="title-band">Admit Card</div>
    <div class="title-sub">{{ $admitCard->academic_year }}/ Exam: {{ $admitCard->exam_name }}</div>

    {{-- ── STUDENT INFO + PHOTO + SUBJECTS BOX ── --}}
    <div class="card-box">

        {{-- Student info (left) + passport photo (right) --}}
        <table class="id-wrap">
            <tr>
                <td class="id-info">
                    <table class="info-table">
            <tr>
                <td class="label">Student Name:</td>
                <td class="value" colspan="3">{{ $admitCard->student_name }}</td>
            </tr>
            <tr>
                <td class="label">Mother's Name:</td>
                <td class="value">{{ $admitCard->mother_name ?: '—' }}</td>
                <td class="label">Father's Name:</td>
                <td class="value">{{ $admitCard->father_name ?: '—' }}</td>
            </tr>
            <tr>
                <td class="label">Admission No:</td>
                <td class="value">{{ $admitCard->studentDetail?->admission_no ?? '—' }}</td>
                <td class="label">Roll No:</td>
                <td class="value">{{ $admitCard->roll_number }}</td>
            </tr>
            <tr>
                <td class="label">School Name:</td>
                <td class="value" colspan="3">{{ $organization->name }}</td>
            </tr>
            <tr>
                <td class="label">Program Name:</td>
                <td class="value">{{ $admitCard->exam_name }}</td>
                <td class="label">Class/Section</td>
                <td class="value">
                    {{ $admitCard->studentDetail?->standard?->name ?? '—' }}
                    @if($admitCard->studentDetail?->section?->name)
                        / {{ $admitCard->studentDetail->section->name }}
                    @endif
                </td>
            </tr>
                    </table>
                </td>
                <td class="id-photo">
                    <div class="photo-box">
                        @if($photoSrc)
                            <img src="{{ $photoSrc }}" class="passport" alt="Candidate photo">
                        @else
                            <div class="passport-ph">Affix<br>Passport<br>Size<br>Photo</div>
                        @endif
                        <div class="photo-cap">Candidate</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Subject Schedule --}}
        @if(!empty($admitCard->subjects))
        <table class="subject-table">
            <thead>
                <tr>
                    <th style="width:70px;">Subject Code</th>
                    <th>Course Name</th>
                    <th style="width:90px;">Date</th>
                    <th style="width:80px;">Day</th>
                    <th style="width:100px;">Seating Plan</th>
                    <th style="width:80px;">Status</th>
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

        {{-- Issue date + authorized signatory --}}
        <table class="foot">
            <tr>
                <td class="issue">Issue Date: {{ $admitCard->issue_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</td>
                <td class="sign">Authorized Signatory</td>
            </tr>
        </table>

    </div>{{-- end card-box --}}

    {{-- ── INSTRUCTIONS ── --}}
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
                <li>Enter the examination hall 15 minutes before the scheduled time. Students coming 15 minutes after the commencement of the examination will not be permitted to enter the examination hall or to write the exam.</li>
                <li>Without identity card and Hall Ticket, no student will be permitted to enter the Exam Hall.</li>
                <li>Read all instructions printed on the answer book and follow them strictly.</li>
                <li>Candidates should handover the answer script to the invigilator before leaving the exam Hall and shall not be permitted to re-enter.</li>
            </ol>
        @endif
    </div>

</div>

<script>
    // Auto-trigger print if opened with ?print=1
    if (new URLSearchParams(window.location.search).get('print') === '1') {
        window.addEventListener('load', function() { window.print(); });
    }
</script>
</body>
</html>
