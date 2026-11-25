{{-- Every page of both panels fits laptops, PCs and tablets (user's ask of
     2026-11-25: on a 14-inch laptop "some things get cut"). Measured on the
     real pages at 1024, 1280 and 1366 px: what overflowed was list tables a
     little wider than the screen, the chip rows and one table whose hidden
     label pushed its whole page sideways. CSS only, after the list theme;
     drop the include in layouts/app to undo it. Phones are left for later. --}}
<style>
    /* A table's scroll box holds its own absolutely placed bits: a screen
       reader's label in a cell escaped it and made Boards' whole page scroll. */
    body.lms-ui #main-scroll .overflow-x-auto:has(> table) { position: relative; }

    /* ─── Tables: less room between columns on a laptop … ─── */
    @media (max-width: 1439px) {
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):not(:first-child):not(:last-child) { padding-left: 10px; padding-right: 10px; }
        body.lms-ui .and-table :is(th, td) { padding-left: 11px; padding-right: 11px; }
    }
    /* … and below 1366 px (a 14-inch laptop is 1280 px) a long value wraps
       onto a second line instead of pushing the table past the screen … */
    @media (max-width: 1365px) {
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):not(:first-child):not(:last-child) { padding-left: 8px; padding-right: 8px; }
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):is(.whitespace-nowrap, .truncate),
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) td :is(.whitespace-nowrap, .truncate) { white-space: normal; text-overflow: clip; }
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) th { white-space: normal; }
        body.lms-ui .and-table :is(th, td) { padding-left: 9px; padding-right: 9px; }
    }
    /* … and on a tablet the columns sit closer still. */
    @media (max-width: 1279px) {
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):not(:first-child):not(:last-child) { padding-left: 6px; padding-right: 6px; }
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):first-child { padding-left: 12px; }
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) :is(th, td):last-child { padding-right: 12px; }
        body.lms-ui .and-table :is(th, td) { padding-left: 7px; padding-right: 7px; }
        /* Only here may a long word (an email) break inside itself. */
        body.lms-ui #main-scroll table:not(.sk-table, .an-cmp, .border-separate) td { overflow-wrap: anywhere; }
    }

    /* ─── Chip rows (Analytics, Fee, Messaging …): a little tighter on a laptop
           so they stay on one row; on a tablet they take a second row rather
           than run off the edge. ─── */
    @media (max-width: 1365px) {
        body.lms-ui .an-chips { gap: 6px; }
        body.lms-ui .an-chip { padding: 0 12px; gap: 6px; font-size: 13px; }
        body.lms-ui .fee-chips { gap: 3px; }
        body.lms-ui .fee-chips .an-chip { padding: 0 7px; gap: 4px; font-size: 12.5px; }
    }
    @media (max-width: 1199px) {
        body.lms-ui .an-chips { flex-wrap: wrap; overflow-x: visible; }
    }
</style>
