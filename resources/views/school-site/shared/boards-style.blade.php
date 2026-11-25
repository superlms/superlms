{{--
    The look of the Boards page (shared.boards, shared.boards-result) on
    every template: the Online Admission pages' white cards (lms-style), the
    template's own colour as the accent.
--}}
@once
<style>
    .bd { max-width: 980px; margin: 0 auto; }
    .bd [hidden] { display: none !important; }
    .bd-login { max-width: 760px; margin-left: auto; margin-right: auto; }
    .bd-login .sform-grid.bd-grid3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .bd-login .bd-hint { margin: 12px 0 0; font-size: 12.5px; color: #6B7280; }

    .bd-who-in { display: flex; align-items: center; gap: 14px; padding: 18px 20px; }
    .bd-ph { width: 60px; height: 60px; flex: none; border-radius: 50%; object-fit: cover; border: 1px solid #E5E7EB; background: #F3F4F6; }
    .bd-ini { display: inline-flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: var(--primary);
        background: color-mix(in srgb, var(--primary) 12%, #fff); border-color: transparent; }
    .bd-who-t { flex: 1; min-width: 0; }
    .bd-who-t h2 { margin: 0; font-size: 18px; font-weight: 700; color: #111827; line-height: 1.3; overflow-wrap: anywhere; letter-spacing: 0; text-transform: none; }
    .bd-who-t p { margin: 3px 0 0; font-size: 13px; color: #6B7280; }
    .bd-who-act { display: flex; gap: 8px; flex-wrap: wrap; }
    .bd-who-act button { cursor: pointer; font-family: inherit; }

    .bd-figs { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); border-top: 1px solid #F1F2F4; }
    .bd-figs > div { padding: 14px 20px; }
    .bd-figs > div + div { border-left: 1px solid #F1F2F4; }
    .bd-figs span { display: block; font-size: 11.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6B7280; }
    .bd-figs b { display: block; margin-top: 4px; font-size: 18px; font-weight: 700; color: #111827; font-variant-numeric: tabular-nums; }
    .bd-figs .due b { color: #DC2626; }
    .bd-figs .ok b { color: #059669; }

    .bd-cols { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr); gap: 22px; align-items: start; margin-bottom: 22px; }
    .bd-cols .lms-card { margin: 0; }
    .bd-dl { margin: 0; }
    .bd-dl > div { display: grid; grid-template-columns: minmax(0, 2fr) minmax(0, 3fr); gap: 12px; padding: 10px 20px; border-top: 1px solid #F3F4F6; }
    .bd-dl > div:first-child { border-top: 0; }
    .bd-dl dt { font-size: 12.5px; color: #6B7280; }
    .bd-dl dd { margin: 0; font-size: 14px; font-weight: 500; color: #111827; overflow-wrap: anywhere; }

    .bd-sub { margin: 0; padding: 14px 20px 8px; font-size: 13px; font-weight: 600; color: #111827; border-top: 1px solid #F1F2F4; }
    .bd-st { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 11.5px; font-weight: 600; white-space: nowrap; }
    .bd-st.paid { background: #ECFDF5; color: #047857; }
    .bd-st.partial { background: #FFFBEB; color: #B45309; }
    .bd-st.pending { background: #FEF2F2; color: #B91C1C; }
    .bd-st.na { background: #F3F4F6; color: #6B7280; }
    .lms-tbl tfoot td { padding: 10px 16px; border-top: 1px solid #EEF0F3; background: #F9FAFB; font-weight: 600; color: #111827; }

    .bd-pay-in { display: flex; gap: 24px; align-items: center; padding: 20px; flex-wrap: wrap; }
    .bd-qr { flex: none; width: 220px; padding: 10px; border: 1px solid #E5E7EB; border-radius: 12px; background: #fff; }
    .bd-qr img, .bd-qr svg { display: block; width: 100%; height: auto; }
    .bd-pay-t { flex: 1; min-width: 220px; font-size: 14px; color: #374151; }
    .bd-pay-t h3 { margin: 0 0 6px; font-size: 22px; font-weight: 700; color: #111827; letter-spacing: 0; text-transform: none; }
    .bd-pay-t p { margin: 6px 0 0; }
    .bd-pay-t .upi { font-weight: 600; color: #111827; overflow-wrap: anywhere; }
    .bd-pay-t .btn, .bd-pay-t .bd-upi { display: inline-flex; margin-top: 14px; padding: 10px 20px; border-radius: 8px; background: var(--primary); color: #fff; font-weight: 600; text-decoration: none; }
    .bd-none { padding: 18px 20px; color: #6B7280; }

    @media (max-width: 760px) {
        .bd-login .sform-grid.bd-grid3 { grid-template-columns: minmax(0, 1fr); }
        .bd-cols { grid-template-columns: minmax(0, 1fr); }
        .bd-figs { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .bd-figs > div:nth-child(3) { border-left: 0; }
        .bd-figs > div:nth-child(n+3) { border-top: 1px solid #F1F2F4; }
        .bd-who-in { flex-wrap: wrap; padding: 14px; }
        .bd-who-act { width: 100%; }
        .bd-figs > div { padding: 12px 14px; }
        .bd-dl > div { padding: 9px 14px; }
        .bd-pay-in { padding: 14px; }
        .bd-qr { width: 200px; margin: 0 auto; }
    }
    @media print {
        .bd-who-act, .bd-login { display: none !important; }
    }
</style>
@endonce
