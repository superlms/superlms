<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card - {{ $student->full_name ?? 'Student' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ─── The twin of report-card-pdf.blade.php. Every size, weight and
               colour is shared, and the sheet is the same box: the PDF prints
               A4 with a 5mm page margin, so the content area is 200 × 287mm —
               which is what .page is here. Change one, change the other. ── */
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', Arial, Helvetica, sans-serif;
            background: #e9e9e9;
            padding: 20px;
            font-size: 12px;
            color: #222;
        }

        /* 5mm of paper outside the frame, matching the PDF's @page margin.
           The margin lives here rather than on @page so the browser prints no
           header or footer of its own — see the @page rule below. */
        .page {
            width: 200mm;
            min-height: 287mm;
            margin: 5mm auto;
            background: #fff;
            border: 3px solid #1a3d8f;
            padding: 4mm 7mm 6mm;
            font-size: 12px;
            color: #222;
            display: flex;
            flex-direction: column;
        }

        /* ─── Top corners ─── */
        table.topbar { width: 100%; border-collapse: collapse; margin-bottom: 1mm; }
        table.topbar td { font-size: 10px; font-weight: 500; color: #000; }
        table.topbar td.right { text-align: right; }

        /* ─── Header ─── */
        .header { text-align: center; margin-bottom: 8px; }
        /* width only, height auto — matches what dompdf can render, so the
           downloaded logo is the same shape and size as this one. */
        .header img.logo {
            width: 120px; height: auto; display: block; margin: 0 auto 8px;
        }
        /* Merriweather at regular weight — a school name reads better set in a
           serif than shouted in a heavy sans. */
        .school-name {
            font-family: 'Merriweather', 'PT Serif', Georgia, serif;
            font-size: 27px; font-weight: 400; letter-spacing: 0.2px; color: #000;
        }
        .school-address { font-size: 11px; margin-top: 3px; font-weight: 400; color: #000; }
        .rule { border-bottom: 1px solid #1a3d8f; margin: 7px auto 0; width: 100%; }
        .doc-title { font-weight: 600; font-size: 13px; margin-top: 8px; color: #000; letter-spacing: 0.3px; }
        .doc-session { font-weight: 600; font-size: 13px; margin-bottom: 9px; color: #000; }

        /* ─── Student info ─── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info td { border: 1px solid #000; padding: 4px 6px; font-size: 12px; }
        table.info td.label { font-weight: bold; width: 16%; background: #fff; color: #000; }
        table.info td.value { width: 34%; }

        /* ─── Marks ─── */
        table.marks { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.marks th, table.marks td {
            border: 1px solid #000; text-align: center; padding: 3px 2px;
            font-size: 11px; word-wrap: break-word;
        }
        table.marks thead th { background: #fff; color: #000; font-weight: bold; }
        table.marks td.subj, table.marks th.subj-head {
            text-align: left; padding-left: 8px; width: 18%; font-weight: 500;
        }
        table.marks th.subj-head span { font-weight: normal; }
        table.marks tr.totrow td, table.marks tr.pctrow td { font-weight: bold; background: #fff; color: #000; }

        /* ─── Co-scholastic ─── */
        table.co-wrap { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 10px; }
        table.co-wrap > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        table.co-wrap > tbody > tr > td.left { padding-right: 5px; }
        table.co-wrap > tbody > tr > td.right { padding-left: 5px; }

        table.co { width: 100%; border-collapse: collapse; }
        table.co th, table.co td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5px; }
        table.co th { background: #fff; color: #000; font-weight: bold; }
        table.co td.grade, table.co th.grade-head { text-align: center; width: 20%; }

        /* ─── Attendance / remark ─── */
        table.bottom-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.bottom-info td { border: 1px solid #000; padding: 4px 6px; font-size: 12px; }
        table.bottom-info td.label { font-weight: bold; background: #fff; color: #000; }

        /* ─── Issue / result ─── */
        table.issue-row { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.issue-row td { font-size: 12px; color: #000; }
        table.issue-row td.result { text-align: right; font-weight: bold; }

        /* ─── Signatures + footnote, at the foot of the sheet ─── */
        .page-foot { margin-top: auto; padding-top: 40px; }

        table.sign-row { width: 100%; border-collapse: collapse; }
        table.sign-row td { width: 50%; }
        table.sign-row td.right { text-align: right; }
        table.sign-row span {
            border-top: 1px solid #000; padding-top: 6px; display: inline-block;
            width: 160px; text-align: center; font-weight: bold; font-size: 12px; color: #000;
        }

        .footer-note {
            text-align: center; font-size: 9.5px; color: #222;
            margin-top: 10px; letter-spacing: 0.3px;
        }

        /* Zero page margin is what stops the browser printing its own header
           and footer — the date at the top, the URL and page number at the
           bottom. The 5mm of paper around the frame comes from .page's margin
           instead. */
        @page { size: A4 portrait; margin: 0; }

        @media print {
            body { background: #fff; padding: 0; }
            .page { border: 3px solid #1a3d8f; margin: 5mm auto; }
            .no-print { display: none; }
        }

        .no-print { max-width: 200mm; margin: 0 auto 12px; text-align: center; }
        .no-print button {
            padding: 8px 18px; font-size: 14px; background: #333; color: #fff;
            border: none; border-radius: 4px; cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">🖨️ Print / Save as PDF</button>
    </div>

    @include('admin._report-card-body')
</body>
</html>
