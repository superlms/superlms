<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Timetable — {{ $teacher->user?->name ?? 'Teacher' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 12mm 12mm 14mm 12mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #1f2937;
            font-size: 10pt;
        }

        .head {
            border-bottom: 2px solid #0d9488;
            padding-bottom: 4mm;
            margin-bottom: 5mm;
        }
        .head-table { width: 100%; }
        .head-table td { vertical-align: middle; }
        .logo { max-height: 16mm; max-width: 30mm; }
        .org-name { font-size: 17pt; font-weight: 700; color: #111827; letter-spacing: 0.3px; }
        .org-contact { font-size: 8pt; color: #6b7280; margin-top: 1mm; }
        .org-contact span + span::before { content: "  •  "; color: #d1d5db; }
        .doc-badge {
            display: inline-block;
            background: #f0fdfa;
            color: #0f766e;
            font-size: 8.5pt;
            font-weight: 700;
            padding: 2mm 4mm;
            border-radius: 3mm;
        }
        .doc-badge .sub { color: #14b8a6; font-weight: 400; }

        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.grid thead th {
            background: #0d9488;
            color: #ffffff;
            font-weight: 700;
            font-size: 9.5pt;
            text-align: center;
            padding: 7px 5px;
            border: 1px solid #0d9488;
        }
        table.grid thead th.time-col { width: 15%; background: #0f766e; border-color: #0f766e; }

        table.grid tbody td {
            border: 1px solid #e5e7eb;
            padding: 6px 6px;
            font-size: 9pt;
            text-align: center;
            vertical-align: middle;
            height: 44px;
        }
        table.grid tbody tr:nth-child(even) td { background: #f9fafb; }

        td.time {
            background: #f0fdfa !important;
            font-weight: 700;
            color: #0f766e;
            font-size: 8.5pt;
            white-space: nowrap;
        }
        td.time .to { color: #14b8a6; font-weight: 400; font-size: 7.5pt; display: block; margin-top: 1px; }

        .subject { font-weight: 700; color: #111827; display: block; }
        .teacher { color: #6b7280; font-size: 8pt; display: block; margin-top: 1px; }
        .lunch { color: #b45309; font-style: italic; font-weight: 600; font-size: 8.5pt; }
        td.is-lunch { background: #fffbeb !important; }

        .empty {
            text-align: center;
            padding: 22mm 6mm;
            color: #9ca3af;
            font-style: italic;
            border: 1px dashed #d1d5db;
            border-radius: 3mm;
        }

        .foot {
            position: fixed;
            bottom: 6mm; left: 12mm; right: 12mm;
            border-top: 1px solid #e5e7eb;
            padding-top: 1.5mm;
            font-size: 7.5pt;
            color: #9ca3af;
            text-align: center;
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
        $teacherName  = $teacher->user?->name ?? 'Teacher';
    @endphp

    <div class="head">
        <table class="head-table">
            <tr>
                @if ($logoSrc)
                    <td style="width: 34mm;"><img class="logo" src="{{ $logoSrc }}" alt="Logo"></td>
                @endif
                <td>
                    <div class="org-name">{{ $organization?->name ?? 'School' }}</div>
                    @if ($schoolEmail || $schoolMobile)
                        <div class="org-contact">
                            @if ($schoolEmail)<span>{{ $schoolEmail }}</span>@endif
                            @if ($schoolMobile)<span>{{ $schoolMobile }}</span>@endif
                        </div>
                    @endif
                </td>
                <td style="text-align: right;">
                    <span class="doc-badge">{{ $teacherName }} <span class="sub">— Weekly Timetable</span></span>
                </td>
            </tr>
        </table>
    </div>

    @if (empty($slots))
        <div class="empty">No classes scheduled for this teacher.</div>
    @else
        <table class="grid">
            <thead>
                <tr>
                    <th class="time-col">Time</th>
                    @foreach ($days as $dayNum => $dayName)
                        <th>{{ $dayName }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($slots as $slot)
                    @php $key = $slot['start_time'] . '|' . $slot['end_time']; @endphp
                    <tr>
                        <td class="time">
                            {{ \Carbon\Carbon::parse($slot['start_time'])->format('h:i A') }}
                            <span class="to">to {{ \Carbon\Carbon::parse($slot['end_time'])->format('h:i A') }}</span>
                        </td>
                        @foreach ($days as $dayNum => $dayName)
                            @php $cell = $grid[$key][$dayNum] ?? null; @endphp
                            <td class="{{ $cell ? '' : 'is-lunch' }}">
                                @if ($cell)
                                    <span class="subject">{{ $cell['subject'] }}</span>
                                    <span class="teacher">{{ $cell['teacher'] }}</span>
                                @else
                                    <span class="lunch">Free</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="foot">
        {{ $organization?->name ?? 'School' }} &nbsp;•&nbsp; {{ $teacherName }} &nbsp;•&nbsp; Generated {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}
    </div>

</body>
</html>
