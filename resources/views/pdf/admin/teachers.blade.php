<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Teachers Report — {{ $organization?->name ?? 'School' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 16mm 14mm 16mm 14mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #1f2937;
            font-size: 10pt;
            line-height: 1.4;
        }

        /* ─── SCHOOL HEADER (report-card style) ─────────────── */
        .school {
            border: 1.5px solid #1e3a8a;
            border-radius: 6px;
            padding: 5mm 6mm;
            margin-bottom: 6mm;
            text-align: center;
        }
        .school .logo { max-height: 18mm; max-width: 34mm; margin-bottom: 2mm; }
        .school .name {
            font-size: 19pt; font-weight: 700; text-transform: uppercase;
            color: #111827; letter-spacing: 0.4px;
        }
        .school .meta { font-size: 8.5pt; color: #6b7280; margin-top: 1.5mm; }
        .school .meta span + span::before { content: "  ·  "; color: #cbd5e1; }
        .report-title {
            margin-top: 3mm; font-size: 12pt; font-weight: 700; color: #1e3a8a;
        }

        /* ─── TEACHER CARD ──────────────────────────────────── */
        .card {
            border: 1px solid #d5dbe6;
            border-radius: 6px;
            margin-bottom: 5mm;
            page-break-inside: avoid;
        }
        .card-head {
            background: #1e3a8a;
            color: #fff;
            padding: 5px 9px;
            border-radius: 6px 6px 0 0;
        }
        .card-head .cname { font-size: 11pt; font-weight: 700; }
        .card-head .csub  { font-size: 8pt; color: #c7d2fe; }
        .badge {
            float: right; font-size: 7.5pt; font-weight: 700; padding: 1px 7px;
            border-radius: 8px; background: #dcfce7; color: #166534;
        }
        .badge.inactive { background: #fee2e2; color: #991b1b; }

        table.fields { width: 100%; border-collapse: collapse; }
        table.fields td { padding: 4px 9px; vertical-align: top; font-size: 9pt; border-top: 1px solid #eef2f7; }
        td.label { width: 24%; color: #6b7280; font-weight: 600; }
        td.value { width: 26%; color: #111827; }
        .section-row td {
            background: #f8fafc; font-weight: 700; color: #334155; font-size: 8pt;
            text-transform: uppercase; letter-spacing: 0.3px;
        }

        .footer {
            position: fixed; bottom: 6mm; left: 14mm; right: 14mm;
            border-top: 1px solid #e5e7eb; padding-top: 1.5mm;
            font-size: 7.5pt; color: #94a3b8; text-align: center;
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
        $schoolEmail  = $schoolInfo->school_email  ?? $organization?->email         ?? null;
        $schoolMobile = $schoolInfo->school_mobile ?? $organization?->mobile_number ?? null;
        $schoolAddr   = $schoolInfo->school_address ?? $organization?->address ?? null;
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
        <div class="report-title">Teachers Report</div>
    </div>

    {{-- ═══════════ TEACHER CARDS ═══════════ --}}
    @forelse ($rows as $r)
        @php $active = ($r['Status'] ?? '') === 'Active'; @endphp
        <div class="card">
            <div class="card-head">
                <span class="badge {{ $active ? '' : 'inactive' }}">{{ $r['Status'] ?? '-' }}</span>
                <div class="cname">{{ $r['Full Name'] ?? '-' }}</div>
                <div class="csub">Employee ID: {{ $r['Employee ID'] ?? '-' }}</div>
            </div>
            <table class="fields">
                <tr>
                    <td class="label">Email</td><td class="value">{{ $r['Email'] ?? '-' }}</td>
                    <td class="label">Mobile</td><td class="value">{{ $r['Mobile'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Gender</td><td class="value">{{ $r['Gender'] ?? '-' }}</td>
                    <td class="label">Date of Birth</td><td class="value">{{ $r['Date of Birth'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Date of Joining</td><td class="value">{{ $r['Date of Joining'] ?? '-' }}</td>
                    <td class="label">Qualification</td><td class="value">{{ $r['Qualification'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Emergency Contact</td><td class="value">{{ $r['Emergency Contact'] ?? '-' }}</td>
                    <td class="label">Pincode</td><td class="value">{{ $r['Pincode'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">City / State</td>
                    <td class="value">{{ trim(($r['City'] ?? '-') . ' / ' . ($r['State'] ?? '-'), ' /') ?: '-' }}</td>
                    <td class="label">Address</td><td class="value">{{ $r['Address'] ?? '-' }}</td>
                </tr>

                <tr class="section-row"><td colspan="4">Attendance &amp; Teaching</td></tr>
                <tr>
                    <td class="label">Overall Attendance</td><td class="value">{{ $r['Attendance'] ?? '-' }}</td>
                    <td class="label">Attendance %</td><td class="value">{{ $r['Attendance %'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Subjects (with Class)</td>
                    <td class="value" colspan="3">{{ $r['Subjects (with Class)'] ?? '-' }}</td>
                </tr>

                <tr class="section-row"><td colspan="4">Bank Details</td></tr>
                <tr>
                    <td class="label">Bank Name</td><td class="value">{{ $r['Bank Name'] ?? '-' }}</td>
                    <td class="label">Account No</td><td class="value">{{ $r['Bank Account No'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">IFSC</td><td class="value">{{ $r['Bank IFSC'] ?? '-' }}</td>
                    <td class="label">Branch</td><td class="value">{{ $r['Bank Branch'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Account Holder</td>
                    <td class="value" colspan="3">{{ $r['Account Holder'] ?? '-' }}</td>
                </tr>
            </table>
        </div>
    @empty
        <p style="text-align:center;color:#94a3b8;padding:20mm 0;">No teachers to export.</p>
    @endforelse

    <div class="footer">
        {{ $organization?->name ?? 'School' }} · Teachers Report · Generated on {{ $generatedAt->format('d M Y, h:i A') }}
    </div>

</body>
</html>
