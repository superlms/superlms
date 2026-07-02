<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Students Report — {{ $organization?->name ?? 'School' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 12mm 10mm 14mm 10mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #1f2937;
            font-size: 8.5pt;
            line-height: 1.35;
        }

        /* ─── SCHOOL HEADER (report-card style) ─────────────── */
        .school {
            border: 1.5px solid #1e3a8a;
            border-radius: 6px;
            padding: 4mm 6mm;
            margin-bottom: 5mm;
            text-align: center;
        }
        .school .logo { max-height: 16mm; max-width: 32mm; margin-bottom: 1.5mm; }
        .school .name {
            font-size: 18pt; font-weight: 700; text-transform: uppercase;
            color: #111827; letter-spacing: 0.4px;
        }
        .school .meta { font-size: 8pt; color: #6b7280; margin-top: 1mm; }
        .school .meta span + span::before { content: "  ·  "; color: #cbd5e1; }
        .report-title {
            margin-top: 2.5mm; font-size: 12pt; font-weight: 700; color: #1e3a8a;
        }

        /* ─── CLASS SECTION ─────────────────────────────────── */
        .class-block { margin-bottom: 5mm; page-break-inside: auto; }
        .class-head {
            background: #1e3a8a; color: #fff; padding: 3px 9px;
            border-radius: 5px 5px 0 0; font-size: 10pt; font-weight: 700;
        }
        .class-head .count { float: right; font-size: 8pt; color: #c7d2fe; font-weight: 600; }

        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th {
            background: #f1f5f9; color: #334155; font-size: 7.5pt; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.2px;
            padding: 4px 5px; border: 0.5px solid #e2e8f0; text-align: left;
        }
        table.grid td {
            padding: 3.5px 5px; border: 0.5px solid #e8edf3; font-size: 8pt;
            vertical-align: top;
        }
        table.grid tr:nth-child(even) td { background: #fafbfc; }
        .status-a { color: #166534; font-weight: 700; }
        .status-i { color: #991b1b; font-weight: 700; }

        .footer {
            position: fixed; bottom: 5mm; left: 10mm; right: 10mm;
            border-top: 1px solid #e5e7eb; padding-top: 1.5mm;
            font-size: 7pt; color: #94a3b8; text-align: center;
        }
    </style>
</head>
<body>

    @php
        $logoSrc = null;
        if (!empty($organization?->logo)) {
            if (\Illuminate\Support\Str::startsWith($organization->logo, ['http://', 'https://'])) {
                $logoSrc = $organization->logo;
            } elseif (file_exists(public_path('storage/' . $organization->logo))) {
                $logoSrc = public_path('storage/' . $organization->logo);
            }
        }
        $schoolEmail  = $schoolInfo->school_email   ?? $organization?->email         ?? null;
        $schoolMobile = $schoolInfo->school_mobile  ?? $organization?->mobile_number ?? null;
        $schoolAddr   = $schoolInfo->school_address ?? $organization?->address        ?? null;
    @endphp

    {{-- ═══════════ SCHOOL HEADER ═══════════ --}}
    <div class="school">
        @if ($logoSrc)<img class="logo" src="{{ $logoSrc }}" alt="Logo">@endif
        <div class="name">{{ $organization?->name ?? 'School' }}</div>
        <div class="meta">
            @if ($schoolAddr)<span>{{ $schoolAddr }}</span>@endif
            @if ($schoolEmail)<span>{{ $schoolEmail }}</span>@endif
            @if ($schoolMobile)<span>{{ $schoolMobile }}</span>@endif
        </div>
        <div class="report-title">Students Report — {{ $total }} student(s)</div>
    </div>

    {{-- ═══════════ CLASS-BY-CLASS TABLES ═══════════ --}}
    @forelse ($rowsByClass as $classLabel => $classRows)
        <div class="class-block">
            <div class="class-head">
                {{ $classLabel !== '' ? $classLabel : 'Unassigned' }}
                <span class="count">{{ count($classRows) }} student(s)</span>
            </div>
            <table class="grid">
                <thead>
                    <tr>
                        <th style="width:5%;">Roll</th>
                        <th style="width:11%;">Admission No</th>
                        <th style="width:17%;">Name</th>
                        <th style="width:14%;">Father</th>
                        <th style="width:7%;">Gender</th>
                        <th style="width:9%;">Mobile</th>
                        <th style="width:9%;">Attendance</th>
                        <th style="width:11%;">Academic Fee</th>
                        <th style="width:11%;">Transport Fee</th>
                        <th style="width:6%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($classRows as $r)
                        <tr>
                            <td>{{ $r['Roll No'] }}</td>
                            <td>{{ $r['Admission No'] }}</td>
                            <td>{{ $r['Full Name'] }}</td>
                            <td>{{ $r['Father Name'] }}</td>
                            <td>{{ $r['Gender'] }}</td>
                            <td>{{ $r['Mobile'] }}</td>
                            <td>{{ $r['Attendance (P/Total)'] }}</td>
                            <td>{{ $r['Academic Fee (Paid/Total)'] }}</td>
                            <td>{{ $r['Transport Fee (Paid/Total)'] }}</td>
                            <td class="{{ $r['Status'] === 'Active' ? 'status-a' : 'status-i' }}">{{ $r['Status'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center;color:#94a3b8;padding:20mm 0;">No students to export.</p>
    @endforelse

    <div class="footer">
        {{ $organization?->name ?? 'School' }} · Students Report · Generated on {{ $generatedAt->format('d M Y, h:i A') }}
    </div>

</body>
</html>
