<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - {{ $student->full_name ?? 'Student' }}</title>
    <style>
        /* ─── Screen + print share the same dimensions ─────────────────────
           A4 portrait at 96dpi ≈ 794px wide. We constrain .sheet to 800px and
           reuse identical typography to mirror the downloaded PDF. The look
           is modelled after the Shreeji Public School template.
        ─────────────────────────────────────────────────────────────── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: A4 portrait; margin: 14px; }

        body {
            font-family: Arial, "DejaVu Sans", sans-serif;
            font-size: 10px;
            color: #1f2937;
            background: #f3f4f6;
        }

        .sheet {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            border: 1px solid #93c5fd;
            padding: 14px 18px;
        }

        .topbar { width: 100%; font-size: 9px; color: #374151; margin-bottom: 8px; }
        .topbar td { vertical-align: top; }
        .topbar .right { text-align: right; }

        .brand { text-align: center; margin-bottom: 6px; }
        .brand img { height: 75px; width: 75px; object-fit: contain; }
        .brand .school-name {
            font-family: "Times New Roman", Times, serif;
            font-size: 26px;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.3px;
            margin-top: 2px;
            border-bottom: 1px solid #1f2937;
            padding-bottom: 2px;
            display: inline-block;
        }
        .brand .address { font-size: 9px; color: #4b5563; margin-top: 4px; }
        .brand .contact { font-size: 9px; color: #4b5563; }
        .brand .doc-title { font-size: 13px; font-weight: bold; color: #111827; margin-top: 10px; }
        .brand .session { font-size: 11px; font-weight: bold; color: #111827; margin-top: 2px; }

        table.info { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.info td { border: 1px solid #6b7280; padding: 6px 10px; font-size: 10px; }
        table.info .label { font-weight: bold; width: 18%; background: #fff; }
        table.info .value { width: 32%; }

        table.marks { width: 100%; border-collapse: collapse; margin-top: 12px; table-layout: fixed; }
        table.marks th, table.marks td {
            border: 1px solid #6b7280;
            text-align: center;
            padding: 4px 2px;
            font-size: 9px;
            word-wrap: break-word;
        }
        table.marks th { font-weight: bold; }
        table.marks td.subj, table.marks th.subj { text-align: left; padding-left: 6px; }
        table.marks .grp { background: #ffffff; font-weight: bold; }
        table.marks .bigtotal { font-size: 14px; font-weight: bold; vertical-align: middle; }
        table.marks .totrow td { font-weight: bold; background: #f9fafb; }
        table.marks .pctrow td { font-weight: bold; background: #f9fafb; }

        table.cosch { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin-top: 12px; }
        table.cosch > tbody > tr > td { padding: 0; vertical-align: top; width: 50%; }
        table.cosch > tbody > tr > td.cell-left  { padding-right: 4px; }
        table.cosch > tbody > tr > td.cell-right { padding-left: 4px; }

        table.cosch-inner { width: 100%; border-collapse: collapse; }
        table.cosch-inner th, table.cosch-inner td { border: 1px solid #6b7280; padding: 6px 10px; font-size: 9px; }
        table.cosch-inner th { background: #fff; font-weight: bold; text-align: left; }
        table.cosch-inner .gd { text-align: center; width: 60px; font-weight: bold; }

        table.foot { width: 100%; border-collapse: collapse; margin-top: 12px; }
        table.foot td { border: 1px solid #6b7280; padding: 6px 10px; font-size: 9px; }
        table.foot .label { font-weight: bold; width: 16%; background: #fff; }

        .result { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .result td { font-size: 10px; padding: 4px 0; }
        .result .r { text-align: right; }
        .result strong { font-weight: bold; }

        .sign { margin-top: 50px; width: 100%; }
        .sign td { font-size: 11px; font-weight: bold; color: #111827; }
        .sign .r { text-align: right; }

        /* Print floating action */
        .print-btn {
            display: block; max-width: 220px; margin: 20px auto;
            padding: 10px 24px; background: #2563eb; color: #fff;
            border: none; border-radius: 6px; font-size: 14px;
            cursor: pointer; text-align: center;
        }
        .print-btn:hover { background: #1d4ed8; }

        @media print {
            body { background: #fff; }
            .sheet { margin: 0; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<button class="print-btn no-print" onclick="window.print()">Print Report Card</button>

@include('admin._report-card-body')

</body>
</html>
