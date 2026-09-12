{{--
    The receipt sheet's look, in one file.

    Both receipts — academic (admin/fee-receipt) and transport
    (admin/transport-receipt) — pull this in, because they ARE the same sheet.
    They were copies once, and the copy rotted: the academic one was redesigned
    into this A5 minimalist form and the transport one kept the old navy
    gradient for months. One stylesheet, one look, no drift.
--}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* A5 portrait, the size a receipt actually is. Minimal by design:
           thin rules, no fills, no colour beyond the ink. */
        @page { size: A5 portrait; margin: 9mm; }

        html { background: #e9ecf1; }
        body { margin: 0; font-family: 'Inter', Arial, Helvetica, sans-serif; color: #111827; font-size: 9.5px; line-height: 1.45; }
        * { box-sizing: border-box; }

        .sheet { width: 130mm; min-height: 192mm; margin: 12px auto; padding: 9mm; background: #fff; display: flex; flex-direction: column; }

        h1, h2, h3, p, table { margin: 0; }
        .num { text-align: right; }
        table { width: 100%; border-collapse: collapse; }

        /* Masthead — logo first, then name, then address, then contacts, all centred */
        .masthead { text-align: center; border-bottom: 1px solid #111827; padding-bottom: 7px; }
        .masthead .logo { height: 34px; width: auto; margin-bottom: 4px; }
        .masthead .school { font-family: 'Inter', Arial, sans-serif; font-weight: 700; font-size: 14px; letter-spacing: .3px; text-transform: uppercase; }
        .masthead .addr { font-size: 7.5px; color: #6b7280; margin-top: 2px; }
        .masthead .contact { font-size: 7.5px; color: #6b7280; margin-top: 1px; }

        /* Section heads */
        .sec { font-size: 7.5px; letter-spacing: 1.2px; text-transform: uppercase; color: #9ca3af;
               margin: 10px 0 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }

        /* Key/value pairs, two to a row — quiet uppercase labels, regular-weight data */
        table.kv td { padding: 2.4px 0; vertical-align: top; }
        table.kv td.k { font-size: 6.5px; letter-spacing: .7px; text-transform: uppercase; color: #9ca3af; width: 22mm; }
        table.kv td.v { font-size: 9px; font-weight: 500; color: #111827; padding-right: 6mm; }

        /* This Payment — a slim meta strip: date, time, status, mode, collected by */
        .payinfo { display: flex; border: 1px solid #e5e7eb; border-radius: 3px; margin-top: 3px; overflow: hidden; }
        .payinfo .cell { flex: 1 1 0; min-width: 0; padding: 5px 6px; border-right: 1px solid #e5e7eb; }
        .payinfo .cell:last-child { border-right: 0; }
        .payinfo .lbl { font-size: 6.3px; letter-spacing: .8px; text-transform: uppercase; color: #9ca3af; white-space: nowrap; }
        .payinfo .val { font-size: 9px; font-weight: 600; margin-top: 1.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .payinfo .val.status-late { color: #b45309; }
        .payinfo .val.status-ontime { color: #0a8a4a; }
        .remark-line { font-size: 8px; color: #6b7280; margin-top: 5px; }

        /* Amount for this payment — one minimalistic row, no table */
        .payamt { border: 1px solid #111827; border-radius: 4px; margin-top: 5px; overflow: hidden; }
        .payamt-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: 7px; padding: 7px 9px; }
        .payamt .chip { font-size: 8px; color: #6b7280; white-space: nowrap; }
        .payamt .chip b { color: #111827; font-weight: 600; }
        .payamt .chip.plus b, .payamt .chip.plus { color: #b45309; }
        .payamt .eq { color: #9ca3af; font-size: 11px; }
        .payamt .grand { margin-left: auto; font-size: 17px; font-weight: bold; white-space: nowrap; }
        .words { font-size: 8px; color: #4b5563; padding: 6px 9px; border-top: 1px solid #e5e7eb; background: #f9fafb; }
        .words .words-lbl { display: block; font-size: 6.3px; letter-spacing: .8px; text-transform: uppercase; color: #9ca3af; margin-bottom: 1.5px; }

        /* Data tables — fixed layout so every row's columns line up exactly */
        table.dt { table-layout: fixed; }
        table.dt col.c-sl { width: 6%; }
        table.dt col.c-inst { width: 22%; }
        table.dt col.c-due { width: 14%; }
        table.dt col.c-num { width: 14.5%; }
        table.dt th { font-size: 7px; letter-spacing: .4px; text-transform: uppercase; color: #9ca3af;
                      font-weight: normal; text-align: left; padding: 3px 0; border-bottom: 1px solid #e5e7eb; }
        table.dt th.num { text-align: right; }
        table.dt td { padding: 3px 0; font-size: 8.5px; border-bottom: 1px solid #f3f4f6; vertical-align: top;
                      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        table.dt td.sl { color: #cbd0d8; font-size: 8px; }
        table.dt tr.sum td { border-top: 1px solid #111827; border-bottom: 0; font-weight: bold; padding-top: 4px; }
        .muted { color: #9ca3af; }
        .penalty-flag { color: #b45309; }

        /* Overall standing */
        table.tot td { padding: 2.6px 0; font-size: 9px; }
        table.tot tr.grand td { border-top: 1px solid #111827; font-weight: bold; padding-top: 4px; font-size: 10px; }

        .foot { margin-top: auto; padding-top: 6px; border-top: 1px solid #e5e7eb;
                display: flex; align-items: flex-end; justify-content: space-between; }
        .foot .note { font-size: 7px; color: #9ca3af; max-width: 62mm; line-height: 1.5; }
        .foot .sign { text-align: center; }
        .foot .sign .line { width: 34mm; border-top: 1px solid #111827; margin-bottom: 3px; }
        .foot .sign .role { font-size: 8px; }
        .foot-num { text-align: center; font-size: 7px; letter-spacing: .4px; color: #9ca3af; margin-top: 10px; }

        .toolbar { text-align: center; margin: 14px 0 24px; }
        .toolbar button { padding: 7px 18px; background: #111827; color: #fff; border: 0; border-radius: 5px;
                          cursor: pointer; font-size: 11px; letter-spacing: .3px; }

        @media print {
            html { background: #fff; }
            .toolbar { display: none; }
            .sheet { width: auto; min-height: 0; margin: 0; padding: 0; }
        }
    </style>
