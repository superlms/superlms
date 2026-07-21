@php
    $val = fn($v) => ($v === null || $v === '') ? '—' : $v;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        {!! $fontCss !!}

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Poppins', 'DejaVu Sans', sans-serif; color: #1f2933; font-size: 9px; }
        .page { padding: 20px 24px; }

        /* Masthead */
        .masthead { text-align: center; border-bottom: 2px solid #4f46e5; padding-bottom: 10px; margin-bottom: 6px; }
        .masthead .logo { height: 46px; width: auto; margin: 0 auto 4px; display: block; }
        .school-name { font-family: 'PT Serif Bold', serif; font-size: 18px; color: #1e1b4b; }
        .report-title { font-family: 'Poppins SemiBold', sans-serif; font-size: 10px; color: #4338ca;
                        text-transform: uppercase; letter-spacing: 2px; margin-top: 3px; }
        .meta { font-size: 8.5px; color: #9ca3af; margin-top: 2px; }

        /* Group heading */
        .group { font-family: 'Poppins SemiBold', sans-serif; font-size: 10px; color: #ffffff;
                 background: #4f46e5; padding: 5px 10px; border-radius: 4px; margin: 12px 0 5px; }

        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th {
            background: #eef2ff; color: #3730a3; text-align: left;
            font-family: 'Poppins SemiBold', sans-serif; font-size: 8px;
            text-transform: uppercase; letter-spacing: .3px; padding: 5px 6px;
            border: 1px solid #dfe1f3;
        }
        table.grid td { padding: 4.5px 6px; border: 1px solid #ececef; font-size: 8.5px; vertical-align: top; }
        table.grid tr:nth-child(even) td { background: #fafafe; }

        .footer { margin-top: 14px; text-align: center; font-size: 7.5px; color: #b6b6bf;
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

    @foreach ($rowsByGroup as $groupLabel => $groupRows)
        @if ($groupLabel !== '' && $groupLabel !== null)
            <div class="group">{{ $groupLabel }} <span style="font-family:'Poppins',sans-serif;opacity:.85;">· {{ count($groupRows) }}</span></div>
        @endif
        <table class="grid">
            <thead>
                <tr>
                    @foreach ($columns as $col)
                        <th @if ($col === 'S.No') style="width:34px;" @endif>{{ $col }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    <tr>
                        @foreach ($columns as $col)
                            <td>{{ $val($row[$col] ?? null) }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="footer">Computer-generated {{ $title }} for {{ $school['name'] ?: 'the school' }}</div>
</div>
</body>
</html>
