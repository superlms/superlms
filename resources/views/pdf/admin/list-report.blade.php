<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { font-family: 'DejaVu Sans', sans-serif; box-sizing: border-box; }
    @page { margin: 22px 24px; }
    body { margin: 0; color: #111827; font-size: 11px; }

    .head { border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }
    .school { font-size: 16px; font-weight: bold; margin: 0; }
    .school-sub { font-size: 10px; color: #6b7280; margin: 2px 0 0; }
    .title { font-size: 13px; font-weight: bold; margin: 8px 0 0; color: #1d4ed8; }
    .scope { font-size: 10px; color: #4b5563; margin: 3px 0 0; }

    table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
    table.data th, table.data td { border: 1px solid #9ca3af; padding: 4px 6px; text-align: left; vertical-align: top; }
    table.data thead th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; letter-spacing: .3px; color: #374151; }
    table.data tbody tr:nth-child(even) td { background: #fafafa; }
    td.sn, th.sn { width: 26px; text-align: center; color: #6b7280; }
    th.blank, td.blank { background: #ffffff !important; min-width: 60px; }

    .empty { text-align: center; color: #9ca3af; padding: 24px 0; font-size: 12px; }
    .foot { margin-top: 10px; font-size: 9px; color: #9ca3af; text-align: right; }
</style>
</head>
<body>
    <div class="head">
        <p class="school">{{ $org->name ?? 'School' }}</p>
        @if($schoolInfo && ($schoolInfo->school_address || $schoolInfo->school_mobile))
            <p class="school-sub">
                {{ $schoolInfo->school_address }}@if($schoolInfo->school_address && $schoolInfo->school_mobile) · @endif{{ $schoolInfo->school_mobile }}
            </p>
        @endif
        <p class="title">{{ $title }}</p>
        <p class="scope">
            @if(!empty($scope)){{ implode('  |  ', $scope) }} · @endif
            Total: {{ $count }}
        </p>
    </div>

    @if(empty($columns) && $blanks === 0)
        <div class="empty">No columns selected.</div>
    @elseif($count === 0)
        <div class="empty">No records found for the chosen filters.</div>
    @else
        <table class="data">
            <thead>
                <tr>
                    <th class="sn">#</th>
                    @foreach($columns as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                    @for($b = 0; $b < $blanks; $b++)
                        <th class="blank">&nbsp;</th>
                    @endfor
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $i => $row)
                    <tr>
                        <td class="sn">{{ $i + 1 }}</td>
                        @foreach($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                        @for($b = 0; $b < $blanks; $b++)
                            <td class="blank">&nbsp;</td>
                        @endfor
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="foot">Generated {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
