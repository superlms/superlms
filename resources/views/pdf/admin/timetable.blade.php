<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Timetable — {{ $standard->name }} — {{ $section->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 22mm 16mm 18mm 16mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #1f2937;
            font-size: 11pt;
            line-height: 1.45;
        }

        /* ─── HEADER ─────────────────────────────────────────── */
        .header { text-align: center; }
        .logo {
            display: block;
            margin: 0 auto 6mm auto;
            max-height: 24mm;
            max-width: 42mm;
        }
        .org-name {
            font-size: 22pt;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            color: #111827;
        }
        .org-contact {
            font-size: 9.5pt;
            color: #6b7280;
            margin-top: 2mm;
        }
        .org-contact span + span { margin-left: 6mm; }

        .doc-title {
            font-size: 15pt;
            font-weight: 700;
            color: #111827;
            margin: 10mm 0 5mm 0;
        }

        /* ─── TABLE (screenshot style) ───────────────────────── */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            overflow: hidden;
        }
        thead th {
            background: #1e3a8a;
            color: #ffffff;
            font-weight: 600;
            font-size: 10pt;
            letter-spacing: 0.2px;
            text-align: left;
            padding: 8px 10px;
            border-right: 1px solid #2d4ea1;
        }
        thead th:last-child { border-right: 0; }

        tbody td {
            padding: 7px 10px;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            font-size: 10.5pt;
            color: #1f2937;
            vertical-align: middle;
        }
        tbody td:last-child { border-right: 0; }
        tbody tr:first-child td { border-top: 0; }
        tbody tr:nth-child(even) td { background: #f8fafc; }

        td.period {
            width: 11%;
            text-align: center;
            color: #475569;
            font-weight: 600;
        }
        td.subject { font-weight: 600; }
        td.time   { width: 22%; white-space: nowrap; color: #374151; }
        td.days   { width: 16%; white-space: nowrap; color: #4338ca; font-weight: 600; }

        .empty {
            text-align: center;
            padding: 20mm 6mm;
            color: #94a3b8;
            font-style: italic;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
        }

        .footer {
            position: fixed;
            bottom: 6mm; left: 16mm; right: 16mm;
            border-top: 1px solid #e5e7eb;
            padding-top: 2mm;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
        }
    </style>
</head>
<body>

    {{-- ═══════════ HEADER ═══════════ --}}
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
    @endphp

    <div class="header">
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
        @endif

        <div class="org-name">{{ $organization?->name ?? 'School' }}</div>

        @if ($schoolEmail || $schoolMobile)
            <div class="org-contact">
                @if ($schoolEmail)<span>Email: {{ $schoolEmail }}</span>@endif
                @if ($schoolMobile)<span>Phone: {{ $schoolMobile }}</span>@endif
            </div>
        @endif

        <div class="doc-title">
            {{ $standard->name }} &mdash; {{ $section->name }} Timetable
        </div>
    </div>

    {{-- ═══════════ TABLE ═══════════ --}}
    @if (empty($rows))
        <div class="empty">No timetable entries scheduled for this section.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th class="period">Period</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th class="time">Time</th>
                    <th class="days">Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $r)
                    <tr>
                        <td class="period">{{ $i + 1 }}</td>
                        <td class="subject">{{ $r['subject'] }}</td>
                        <td>{{ $r['teacher'] }}</td>
                        <td class="time">
                            {{ \Carbon\Carbon::parse($r['start_time'])->format('h:i A') }}
                            &ndash; {{ \Carbon\Carbon::parse($r['end_time'])->format('h:i A') }}
                        </td>
                        <td class="days">{{ $r['days_range'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ $organization?->name ?? 'School' }} &nbsp;·&nbsp; Generated on {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}
    </div>

</body>
</html>
