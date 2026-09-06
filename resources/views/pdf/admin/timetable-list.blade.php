<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Timetable — {{ $heading }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 landscape; margin: 12mm 14mm 16mm 14mm; }

        body {
            font-family: "DejaVu Sans", "Helvetica", Arial, sans-serif;
            color: #111111;
            font-size: 10pt;
        }

        /* ─── Centred masthead: logo, school name, contact, title ─── */
        .masthead { text-align: center; margin-bottom: 5mm; }
        .masthead .logo { max-height: 18mm; max-width: 40mm; margin-bottom: 2mm; }
        .masthead .org  { font-size: 15pt; font-weight: 700; letter-spacing: 0.2px; }
        .masthead .meta { font-size: 8pt; color: #555555; margin-top: 1mm; }
        .masthead .meta span + span::before { content: "   |   "; color: #bbbbbb; }

        .rule { border-bottom: 1px solid #111111; margin: 3mm 0 0 0; }

        .title-bar {
            margin: 3mm 0 4mm 0;
            font-size: 11pt;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .title-bar .sub {
            display: block;
            font-size: 8pt;
            font-weight: 400;
            text-transform: none;
            letter-spacing: 0;
            color: #555555;
            margin-top: 1mm;
        }

        /* ─── Table: plain rules only, like a Word / Excel sheet ─── */
        table.sheet {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.sheet th, table.sheet td {
            border: 0.5pt solid #9a9a9a;
            padding: 2.4mm 2.2mm;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.sheet thead th {
            background: #ececec;
            font-size: 8.5pt;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        table.sheet tbody td { font-size: 9.5pt; }
        table.sheet tbody tr:nth-child(even) td { background: #f7f7f7; }

        td.num  { text-align: center; color: #555555; }
        td.time { white-space: nowrap; }
        td.subject { font-weight: 700; }
        .muted { color: #777777; }

        .empty {
            margin-top: 6mm;
            text-align: center;
            padding: 20mm 6mm;
            color: #777777;
            font-style: italic;
            border: 0.5pt dashed #9a9a9a;
        }

        /* ─── Footer ─────────────────────────────────────────── */
        .foot {
            position: fixed;
            bottom: 6mm; left: 14mm; right: 14mm;
            border-top: 0.5pt solid #cccccc;
            padding-top: 1.5mm;
            font-size: 7.5pt;
            color: #777777;
        }
        .foot table { width: 100%; border-collapse: collapse; }
        .foot td { padding: 0; border: none; }
        .foot td.pg { text-align: right; }
        .foot td.pg:after { content: counter(page) " / " counter(pages); }
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
        $schoolEmail   = $schoolInfo->school_email   ?? $organization?->email          ?? null;
        $schoolMobile  = $schoolInfo->school_mobile  ?? $organization?->mobile_number  ?? null;
        $schoolAddress = $schoolInfo->school_address ?? null;
    @endphp

    <div class="masthead">
        @if ($logoSrc)
            <img class="logo" src="{{ $logoSrc }}" alt="Logo">
        @endif
        <div class="org">{{ $organization?->name ?? 'School' }}</div>
        @if ($schoolAddress)
            <div class="meta">{{ $schoolAddress }}</div>
        @endif
        @if ($schoolEmail || $schoolMobile)
            <div class="meta">
                @if ($schoolEmail)<span>{{ $schoolEmail }}</span>@endif
                @if ($schoolMobile)<span>{{ $schoolMobile }}</span>@endif
            </div>
        @endif
        <div class="rule"></div>
    </div>

    <div class="title-bar">
        {{ $mode === 'teacher' ? 'Teacher Timetable' : 'Class Timetable' }}
        <span class="sub">{{ $heading }}</span>
    </div>

    @if ($rows->isEmpty())
        <div class="empty">No timetable entries to print.</div>
    @else
        <table class="sheet">
            <thead>
                <tr>
                    <th style="width: 8mm;">#</th>
                    @if ($mode === 'teacher')
                        <th style="width: 16%;">Class &middot; Section</th>
                    @endif
                    <th style="width: {{ $mode === 'teacher' ? '18%' : '20%' }};">Time</th>
                    <th style="width: {{ $mode === 'teacher' ? '20%' : '24%' }};">Subject</th>
                    <th>Teacher(s)</th>
                    <th style="width: {{ $mode === 'teacher' ? '17%' : '20%' }};">Days</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $i => $r)
                    <tr>
                        <td class="num">{{ $i + 1 }}</td>
                        @if ($mode === 'teacher')
                            <td>{{ $r['class_section'] }}</td>
                        @endif
                        <td class="time">
                            {{ \Carbon\Carbon::parse($r['start_time'])->format('h:i A') }} &ndash;
                            {{ \Carbon\Carbon::parse($r['end_time'])->format('h:i A') }}
                        </td>
                        <td class="subject">{{ $r['subject'] }}</td>
                        <td>
                            @if (empty($r['teachers']))
                                <span class="muted">&mdash;</span>
                            @else
                                {{ implode(', ', $r['teachers']) }}
                            @endif
                        </td>
                        <td>
                            @if (empty($r['days']))
                                <span class="muted">&mdash;</span>
                            @else
                                {{ implode(', ', array_map(fn ($d) => $daysShort[$d] ?? $d, $r['days'])) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="foot">
        <table>
            <tr>
                <td>{{ $organization?->name ?? 'School' }} &nbsp;&middot;&nbsp; {{ $heading }} &nbsp;&middot;&nbsp; Generated {{ \Carbon\Carbon::now()->format('d M Y, h:i A') }}</td>
                <td class="pg">Page&nbsp;</td>
            </tr>
        </table>
    </div>

</body>
</html>
