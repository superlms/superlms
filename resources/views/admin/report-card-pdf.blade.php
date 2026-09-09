<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report Card - {{ $student->full_name ?? 'Student' }}</title>
    <style>
        {!! \App\Support\PdfFonts::faceCss() !!}

        /* ─── The downloaded PDF and the browser print view are the same
               sheet. Every size, weight and colour below is the twin of
               report-card-print.blade.php; only the two things dompdf cannot
               do are handled differently:

                 · the navy frame is a position:fixed box covering the page,
                   because dompdf ignores min-height, so a short report would
                   otherwise get a border that hugs the content;
                 · the signature block is pinned with position:fixed, because
                   there is no flexbox to push it down with.

               No `* { margin:0 }` — that wipes dompdf's @page margins and the
               frame bleeds off the paper. ─────────────────────────────── */
        @page { size: A4 portrait; margin: 5mm; }

        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Poppins', 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        /* Navy frame around the whole sheet, not just the content. */
        .frame {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            border: 3px solid #1a3d8f;
        }

        /* Tight top and sides so the masthead sits close to the frame.
           Bottom clears the pinned .page-foot, which is out of flow. */
        .page { padding: 4mm 7mm 26mm; }

        /* ─── Top corners: affiliation number left, website right ─── */
        table.topbar { width: 100%; border-collapse: collapse; margin-bottom: 1mm; }
        table.topbar td { font-size: 10px; color: #000; padding: 0; }
        table.topbar td.right { text-align: right; }

        /* ─── Header ─── */
        .header { text-align: center; margin-bottom: 8px; }
        /* width only, height auto: dompdf cannot clip an image to a circle, so
           both mediums size the logo the same way and keep its aspect. */
        .header img.logo { width: 120px; height: auto; }
        /* Merriweather at regular weight — a school name reads better set in a
           serif than shouted in a heavy sans. */
        .school-name {
            font-family: 'Merriweather', 'PT Serif', serif;
            color: #000; font-size: 27px; letter-spacing: 0.2px;
        }
        .school-address { font-size: 11px; margin-top: 3px; color: #000; }
        .rule { border-bottom: 1px solid #1a3d8f; margin: 7px auto 0; width: 100%; }
        .doc-title {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 13px; margin-top: 8px; color: #000; letter-spacing: 0.3px;
        }
        .doc-session {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 13px; margin-bottom: 9px; color: #000;
        }

        /* ─── Student info ─── */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.info td { border: 1px solid #000; padding: 4px 6px; font-size: 12px; }
        table.info td.label {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            width: 16%; color: #000;
        }
        table.info td.value { width: 34%; }

        /* ─── Marks ─── */
        table.marks { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.marks th, table.marks td {
            border: 1px solid #000; text-align: center; padding: 3px 2px;
            font-size: 11px; word-wrap: break-word;
        }
        table.marks thead th {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }
        table.marks td.subj, table.marks th.subj-head {
            text-align: left; padding-left: 8px; width: 18%;
        }
        table.marks th.subj-head span { font-family: 'Poppins', sans-serif; }
        table.marks tr.totrow td, table.marks tr.pctrow td {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }

        /* ─── Co-scholastic: two tables side by side (no flex in dompdf) ─── */
        table.co-wrap { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 10px; }
        table.co-wrap > tbody > tr > td { width: 50%; vertical-align: top; padding: 0; }
        table.co-wrap > tbody > tr > td.left  { padding-right: 5px; }
        table.co-wrap > tbody > tr > td.right { padding-left: 5px; }

        table.co { width: 100%; border-collapse: collapse; }
        table.co th, table.co td { border: 1px solid #000; padding: 4px 6px; font-size: 11.5px; }
        table.co th {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }
        table.co td.grade, table.co th.grade-head { text-align: center; width: 20%; }

        /* ─── Attendance / remark ─── */
        table.bottom-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.bottom-info td { border: 1px solid #000; padding: 4px 6px; font-size: 12px; }
        table.bottom-info td.label {
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif; color: #000;
        }

        /* ─── Issue date / result ─── */
        table.issue-row { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.issue-row td { font-size: 12px; padding: 0; color: #000; }
        table.issue-row td.result {
            text-align: right;
            font-family: 'Poppins Bold', 'Poppins', sans-serif;
        }

        /* ─── Signatures + footnote, at the foot of the sheet ─── */
        .page-foot { position: fixed; left: 7mm; right: 7mm; bottom: 6mm; }

        table.sign-row { width: 100%; border-collapse: collapse; }
        table.sign-row td { width: 50%; padding: 0; }
        table.sign-row td.right { text-align: right; }
        table.sign-row span {
            border-top: 1px solid #000; padding-top: 6px;
            display: inline-block; width: 160px; text-align: center;
            font-family: 'Poppins SemiBold', 'Poppins', sans-serif;
            font-size: 12px; color: #000;
        }

        .footer-note {
            text-align: center; font-size: 9.5px; color: #222;
            margin-top: 10px; letter-spacing: 0.3px;
        }
    </style>
</head>
<body>
    <div class="frame"></div>
    @include('admin._report-card-body')
</body>
</html>
