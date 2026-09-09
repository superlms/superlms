@php
    $val = fn($v) => ($v === null || $v === '') ? '—' : $v;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! $fontCss !!}

        /* No `* { margin:0 }` — that wipes dompdf's @page margins and the
           content bleeds to the paper edge. Reset only what needs it. */
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; color: #1f2933; font-size: 8px; }
        .page { padding: 16px 18px; }

        /* Masthead */
        .masthead { text-align: center; border-bottom: 1.5px solid #4f46e5; padding-bottom: 7px; margin-bottom: 8px; }
        .masthead .logo { height: 34px; width: auto; margin: 0 auto 3px; display: block; }
        .school-name { font-family: 'PT Serif Bold', serif; font-size: 15px; color: #1e1b4b; }
        .report-title { font-family: 'Poppins SemiBold', sans-serif; font-size: 8.5px; color: #4338ca;
                        text-transform: uppercase; letter-spacing: 1.8px; margin-top: 2px; }
        .meta { font-size: 7.5px; color: #1f2933; margin-top: 2px; }

        .group { font-family: 'Poppins SemiBold', sans-serif; font-size: 9px; color: #ffffff;
                 background: #4f46e5; padding: 4px 8px; border-radius: 3px; margin: 9px 0 5px; }

        /* One card per record. `avoid` keeps a card off a page seam. */
        table.rec { width: 100%; border-collapse: collapse; table-layout: fixed;
                    border: 1px solid #d8dae8; margin-bottom: 6px; page-break-inside: avoid; }

        /* Card header strip */
        td.head { background: #eef2ff; border-bottom: 1px solid #d8dae8; padding: 4px 7px; }
        .h-no   { font-family: 'Poppins SemiBold', sans-serif; font-size: 8px; color: #3730a3; }
        .h-name { font-family: 'Poppins SemiBold', sans-serif; font-size: 10px; color: #1e1b4b; }
        .h-badge{ font-size: 7.5px; color: #1e1b4b; }

        /* Field cells: tiny label over the value */
        td.f { border: 1px solid #ececf4; padding: 3px 5px; vertical-align: top; word-wrap: break-word; }
        .k { font-size: 6px; color: #3730a3; text-transform: uppercase; letter-spacing: .3px; }
        .v { font-size: 7.6px; color: #111827; line-height: 1.25; }

        /* Month / summary strip along the bottom of a card */
        td.strip-label { background: #eef2ff; border: 1px solid #ececf4; padding: 3px 5px;
                         font-size: 6px; color: #3730a3; text-transform: uppercase; letter-spacing: .3px; }
        td.s { border: 1px solid #ececf4; padding: 3px 2px; text-align: center; background: #fafafe; }
        .sk { font-size: 6px; color: #3730a3; text-transform: uppercase; }
        .sv { font-size: 7.4px; color: #111827; }

        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #1f2933;
                  font-family: 'PT Serif', serif; font-style: italic; }
    </style>
</head>
<body>
<div class="page">

    <div class="masthead">
        @if (!empty($school['logo']))
            <img src="{{ $school['logo'] }}" class="logo" alt="">
        @endif
        <div class="school-name">{{ $school['name'] ?: 'School' }}</div>
        <div class="report-title">{{ $title }}</div>
        <div class="meta">Total: {{ $total }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y, g:i A') }}</div>
    </div>

    @foreach ($recordsByGroup as $groupLabel => $records)
        @if ($groupLabel !== '' && $groupLabel !== null)
            <div class="group">{{ $groupLabel }}
                <span style="font-family:'Poppins',sans-serif;">· {{ count($records) }}</span></div>
        @endif

        @foreach ($records as $rec)
            @php
                $fieldRows = array_chunk($rec['fields'], $perRow, true);
                $strip     = $rec['strip'] ?? null;
            @endphp
            <table class="rec">
                <tr>
                    <td class="head" colspan="{{ $perRow }}">
                        <span class="h-no">{{ $rec['no'] }}.</span>
                        <span class="h-name">{{ $val($rec['title']) }}</span>
                        @if (!empty($rec['badge']))
                            <span class="h-badge">· {{ $rec['badge'] }}</span>
                        @endif
                    </td>
                </tr>

                @foreach ($fieldRows as $row)
                    <tr>
                        @foreach ($row as $label => $value)
                            <td class="f">
                                <div class="k">{{ $label }}</div>
                                <div class="v">{{ $val($value) }}</div>
                            </td>
                        @endforeach
                        {{-- Pad a short last row so the card keeps its grid --}}
                        @for ($i = count($row); $i < $perRow; $i++)
                            <td class="f"></td>
                        @endfor
                    </tr>
                @endforeach

                @if ($strip)
                    <tr>
                        <td colspan="{{ $perRow }}" style="padding:0; border:0;">
                            <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                                <tr>
                                    <td class="strip-label" style="width:11%;">{{ $strip['label'] }}</td>
                                    @foreach ($strip['cells'] as $sLabel => $sValue)
                                        <td class="s">
                                            <div class="sk">{{ $sLabel }}</div>
                                            <div class="sv">{{ $val($sValue) }}</div>
                                        </td>
                                    @endforeach
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif
            </table>
        @endforeach
    @endforeach

    <div class="footer">Computer-generated {{ $title }} for {{ $school['name'] ?: 'the school' }}</div>
</div>
</body>
</html>
