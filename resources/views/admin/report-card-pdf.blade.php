<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->full_name ?? 'Student' }}</title>
    <style>
        {!! \App\Support\PdfFonts::faceCss() !!}

        /* ─── A4 portrait report card, matching the school's template ────────
           Poppins throughout (bundled as base64 @font-face — dompdf has no web
           fonts), a 3px navy frame around the sheet and black hairline table
           borders. No `* { margin:0 }`: that wipes dompdf's @page margins and
           the frame bleeds off the paper. ────────────────────────────────── */
        @page { size: A4 portrait; margin: 8mm; }

        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .page {
            border: 3px solid #1a3d8f;
            padding: 6mm 7mm 5mm;
        }

        /* ─── Top corners: affiliation number left, website right ─── */
        table.topbar { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
        table.topbar td { font-size: 10px; color: #000; padding: 0; }
        table.topbar td.right { text-align: right; }

        /* ─── Header ─── */
        .header { text-align: center; margin-bottom: 8px; }
        .header img.logo { width: 78px; height: 78px; }
        .school-name {
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
            color: #000; font-size: 24px; letter-spacing: 0.6px;
        }
        .school-address { font-size: 10.5px; margin-top: 3px; color: #000; }
        .doc-title {
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
            font-size: 13px; margin-top: 9px; color: #000;
        }
        .doc-session {
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
            font-size: 13px; margin-bottom: 9px; color: #000;
        }

        /* ─── Student info ─── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
        table.info td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5px; }
        table.info td.label {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            width: 16%; color: #000;
        }
        table.info td.value { width: 34%; }

        /* ─── Marks ─── */
        table.marks { width: 100%; border-collapse: collapse; margin-bottom: 9px; table-layout: fixed; }
        table.marks th, table.marks td {
            border: 1px solid #000; text-align: center; padding: 3px 2px;
            font-size: 10px; word-wrap: break-word;
        }
        table.marks thead th {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }
        table.marks td.subj, table.marks th.subj-head { text-align: left; padding-left: 7px; }
        table.marks th.subj-head span { font-family: 'Poppins', sans-serif; }
        table.marks tr.totrow td, table.marks tr.pctrow td {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }

        /* ─── Co-scholastic: two tables side by side (no flex in dompdf) ─── */
        table.co-wrap { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 9px; }
        table.co-wrap > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        table.co-wrap > tbody > tr > td.left  { padding-right: 5px; }
        table.co-wrap > tbody > tr > td.right { padding-left: 5px; }

        table.co { width: 100%; border-collapse: collapse; }
        table.co th, table.co td { border: 1px solid #000; padding: 4px 6px; font-size: 11px; }
        table.co th {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }
        table.co td.grade, table.co th.grade-head { text-align: center; width: 20%; }

        /* ─── Attendance / remark ─── */
        table.bottom-info { width: 100%; border-collapse: collapse; margin-bottom: 9px; }
        table.bottom-info td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5px; }
        table.bottom-info td.label {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }

        /* ─── Issue date / result ─── */
        table.issue-row { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.issue-row td { font-size: 11.5px; padding: 0; color: #000; }
        table.issue-row td.result {
            text-align: right;
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
        }

        /* ─── Signatures ─── */
        table.sign-row { width: 100%; border-collapse: collapse; margin-top: 22mm; }
        table.sign-row td { width: 50%; padding: 0; }
        table.sign-row span {
            border-top: 1px solid #000; padding-top: 6px;
            display: inline-block; width: 150px; text-align: center;
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 11.5px; color: #000;
        }
        table.sign-row td.right { text-align: right; }

        .footer-note {
            text-align: center; font-size: 9px; color: #222;
            margin-top: 8px; letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    @include('admin._report-card-body')
</body>
</html>
