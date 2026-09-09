<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->full_name ?? 'Student' }}</title>
    <style>
        {!! \App\Support\PdfFonts::faceCss() !!}

        /* ─── A copy of the school's own report card, measured off their PDF.
               Same sheet as report-card-print.blade.php; only the positioning
               of the frame, the topbar and the signatures differs, because
               dompdf has no flexbox and ignores min-height, so those three are
               pinned with position:fixed instead.

               No `* { margin:0 }` — that wipes dompdf's @page margins. ─── */
        @page { size: A4 portrait; margin: 0; }

        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #000;
        }

        /* Frame inset 5.9mm at the sides, 9.8mm at the top (the topbar lives
           in that gap) and 6.4mm at the foot. */
        .topbar     { position: fixed; top: 3.1mm; left: 5.9mm; right: 5.9mm; }
        .frame      { position: fixed; top: 9.8mm; left: 5.9mm; right: 5.9mm; bottom: 6.4mm; }
        .page       { padding: 15.5mm 14.6mm 26mm; }
        .page-foot  { position: fixed; left: 12.5mm; right: 13.6mm; bottom: 14mm; }

        .school-name { font-family: 'Merriweather', 'PT Serif', serif; }
        .doc-title, .doc-session,
        table.info td.label, table.info td.label2,
        table.marks th, table.marks td,
        table.co th, table.co td.grade,
        table.bottom-info td.label,
        table.marks td.foot-label,
        table.issue-row td.result span,
        table.sign-row td {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
        }
        table.marks td.subj, table.marks th.tiny,
        table.info td.value, table.info td.value2,
        table.co td, table.bottom-info td {
            font-family: 'Poppins', sans-serif;
        }

/* ─── Sheet ──────────────────────────────────────────────────────────── */
/* Spans the frame exactly (210 - 5.9 - 5.9), so the affiliation number sits
   on its left corner and the website on its right. A plain width:100% here
   would be 210mm starting at 5.9mm and push the website off the sheet. */
.topbar { width: 198.2mm; border-collapse: collapse; }
.topbar td { font-size: 11.8px; color: #000; padding: 0; vertical-align: top; }
.topbar td.right { text-align: right; }

.frame { border: 2px solid #428eb8; }

/* ─── Masthead ───────────────────────────────────────────────────────── */
.header { text-align: center; }
.header img.logo { width: 145px; height: auto; display: block; margin: 0 auto; }
/* Sits right under the logo, the way the school's card does. */
.school-name { font-size: 23px; color: #000; letter-spacing: 0.2px; line-height: 1.1; margin-top: -7px; }
.school-address { font-size: 11.8px; color: #000; line-height: 1.35; }
.doc-title { font-size: 14.1px; color: #000; margin-top: 7px; }
.doc-session { font-size: 14.1px; color: #000; margin-bottom: 8px; }

/* ─── Student info ───────────────────────────────────────────────────── */
table.info { width: 100%; border-collapse: collapse; margin-bottom: 9px; table-layout: fixed; }
table.info td { border: 1px solid #aaaaaa; padding: 6px 9px; font-size: 12.2px; color: #000; }
table.info td.label  { width: 25.1%; }
table.info td.value  { width: 33.2%; }
table.info td.label2 { width: 18.4%; }
table.info td.value2 { width: 23.3%; }   /* 25.1+33.2+18.4+23.3 = 100, so these rows end where the tables below do */

/* ─── Marks ──────────────────────────────────────────────────────────── */
table.marks { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
table.marks th, table.marks td {
    border: 1px solid #aaaaaa; text-align: center; color: #000;
    padding: 5px 1px; font-size: 10.9px; word-wrap: break-word;
}
table.marks th { font-size: 12.1px; padding: 6px 2px; }
table.marks th.tiny { font-size: 10.9px; padding: 4px 1px; }
table.marks th.mid  { font-size: 10.9px; padding: 4px 1px; }
table.marks th.big  { font-size: 12.1px; }
table.marks .subj { width: 22.1%; text-align: left; padding-left: 9px; }
table.marks td.subj { font-size: 11.8px; }
table.marks td.foot-label { text-align: right; padding-right: 12px; }

/* ─── Co-scholastic ──────────────────────────────────────────────────── */
table.co-wrap { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
table.co-wrap > tbody > tr > td { padding: 0; vertical-align: top; }
table.co-wrap > tbody > tr > td.left,
table.co-wrap > tbody > tr > td.right { width: 49%; }
table.co-wrap > tbody > tr > td.gap { width: 2%; }

table.co { width: 100%; border-collapse: collapse; table-layout: fixed; }
table.co th, table.co td { border: 1px solid #aaaaaa; padding: 6px 9px; font-size: 11.7px; color: #000; }
table.co th.grade-head, table.co td.grade { width: 18.1%; text-align: center; }

/* ─── Attendance / remark ────────────────────────────────────────────── */
table.bottom-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
table.bottom-info td { border: 1px solid #aaaaaa; padding: 6px 9px; font-size: 11.7px; color: #000; }
table.bottom-info td.label { width: 14.3%; }
table.bottom-info td.c2 { width: 24.1%; }
table.bottom-info td.c3 { width: 24.7%; }
table.bottom-info td.c4 { width: 36.9%; }

/* ─── Issue date / result — no borders ───────────────────────────────── */
table.issue-row { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.issue-row td { font-size: 11.5px; padding: 0; color: #000; border: 0; }
table.issue-row td.result { text-align: right; }

/* ─── Signatures — bold, no rule above ───────────────────────────────── */
table.sign-row { width: 100%; border-collapse: collapse; }
table.sign-row td { width: 50%; padding: 0; font-size: 14.3px; color: #000; border: 0; }
table.sign-row td.right { text-align: right; }

    </style>
</head>
<body>
    @include('admin._report-card-body')
</body>
</html>
