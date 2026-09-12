<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $title }}</title>
    <style>
        {{-- No `* { margin: 0 }`: it wipes the @page margins and the sheet
             bleeds to the paper edge. --}}
        @page { size: A4; margin: 14mm 12mm; }
        body { font-family: "DejaVu Sans", Arial, sans-serif; font-size: 9pt; color: #1f2937; }

        .head { border-bottom: 0.8px solid #e5e7eb; padding-bottom: 3mm; margin-bottom: 5mm; }
        .school { font-size: 12pt; font-weight: bold; color: #111827; }
        .sub { font-size: 8pt; color: #6b7280; margin-top: 1mm; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 6mm; }
        th, td { border: 0.5px solid #e5e7eb; padding: 1.6mm 2mm; text-align: left; vertical-align: top;
                 word-wrap: break-word; line-height: 1.25; }
        th { background: #f9fafb; font-weight: bold; font-size: 8.5pt; color: #111827; }
        td { font-size: 8.5pt; }
        {{-- Zebra striping survives dompdf as a background on the row. --}}
        tr.alt td { background: #fcfcfd; }
    </style>
</head>
<body>

<div class="head">
    <div class="school">{{ $school }}</div>
    <div class="sub">{{ $title }} · printed {{ $printed->format('d M Y, g:i A') }}</div>
</div>

@foreach ($tables as $table)
    @php
        $headers = array_values(array_filter($table['headers'] ?? [], fn ($h) => $h !== null));
        $rows    = $table['rows'] ?? [];
    @endphp
    <table>
        @if ($headers)
            <thead>
                <tr>
                    @foreach ($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif
        <tbody>
            @foreach ($rows as $i => $row)
                <tr @class(['alt' => $i % 2 === 1])>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

</body>
</html>
