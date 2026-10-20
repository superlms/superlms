{{--
    The look of the Online Admission pages (the admission form, classes, fee
    structure, rules, documents) — the admin's Mark Attendance list: a white
    card with a title, a light header row, a serial number, plain text, a
    column only as wide as what is in it. Shared by every template; the
    accent is the template's own colour.
--}}
@once
<style>
    .lms-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 14px; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); overflow: hidden; margin: 0 0 22px; color: #374151; font-size: 14px; line-height: 1.55; }
    .lms-card:last-child { margin-bottom: 0; }
    .lms-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 4px 12px; padding: 16px 20px; border-bottom: 1px solid #F1F2F4; }
    .lms-head h2 { margin: 0; font-size: 16px; font-weight: 600; color: #111827; display: flex; align-items: center; gap: 9px; letter-spacing: 0; text-transform: none; }
    .lms-head h2::before { content: ''; width: 4px; height: 16px; border-radius: 4px; background: var(--primary); }
    .lms-head .meta { margin-left: auto; font-size: 12.5px; color: #6B7280; }
    .lms-head .note { flex-basis: 100%; margin: 2px 0 0 13px; font-size: 13px; color: #6B7280; }
    .lms-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    .lms-tbl { width: 100%; border-collapse: collapse; }
    .lms-tbl th { padding: 10px 16px; background: #F9FAFB; border-bottom: 1px solid #EEF0F3; text-align: left; font-size: 11.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6B7280; white-space: nowrap; }
    .lms-tbl td { padding: 12px 16px; border-top: 1px solid #F3F4F6; vertical-align: top; color: #374151; }
    .lms-tbl tbody tr:first-child td { border-top: 0; }
    .lms-tbl tbody tr:hover td { background: #FAFBFC; }
    .lms-tbl .fit { width: 1%; white-space: nowrap; }
    .lms-tbl .sno { width: 1%; white-space: nowrap; color: #9CA3AF; font-weight: 500; }
    .lms-tbl .strong { color: #111827; font-weight: 600; }
    .lms-tbl .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .lms-tbl .total { color: var(--primary); font-weight: 700; }
    .lms-tbl .muted { color: #9CA3AF; }
    .lms-tbl .desc { color: #4B5563; }
    .lms-btn { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border: 1px solid #E5E7EB; border-radius: 8px; color: #374151; font-size: 12.5px; font-weight: 500; text-decoration: none; white-space: nowrap; background: #fff; transition: border-color .2s, color .2s; }
    .lms-btn:hover { border-color: var(--primary); color: var(--primary); }
    .lms-empty { padding: 34px 20px; text-align: center; color: #9CA3AF; font-size: 14px; }
    .lms-text { padding: 18px 20px; color: #4B5563; line-height: 1.75; }
    .lms-text p + p { margin-top: 12px; }
    .lms-tbl .nw { white-space: nowrap; }

    /* The admission form in the same card: the template's own form (it still
       posts as before) with the panel's plain inputs. */
    .lms-card .lms-form { padding: 20px; }
    .lms-card .lms-form .form-card { background: none; border: 0; border-radius: 0; padding: 0; box-shadow: none; }
    .lms-card .lms-form .sform-grid, .lms-card .lms-form .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px 18px; }
    .lms-card .lms-form .sform label, .lms-card .lms-form .form-field { display: flex; flex-direction: column; gap: 6px; }
    .lms-card .lms-form .sform label > span, .lms-card .lms-form .form-field > label { margin: 0; font-size: 12.5px; font-weight: 500; color: #374151; }
    .lms-card .lms-form .sform label b { color: #EF4444; font-weight: 500; }
    .lms-card .lms-form input:not([name=company]), .lms-card .lms-form select, .lms-card .lms-form textarea {
        width: 100%; padding: 9px 12px; border: 1px solid #D1D5DB; border-radius: 8px; background: #fff;
        font-family: inherit; font-size: 14px; line-height: 1.4; color: #111827; box-shadow: none; outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .lms-card .lms-form textarea { min-height: 96px; resize: vertical; }
    .lms-card .lms-form input::placeholder, .lms-card .lms-form textarea::placeholder { color: #9CA3AF; }
    .lms-card .lms-form input:focus, .lms-card .lms-form select:focus, .lms-card .lms-form textarea:focus {
        border-color: var(--primary); background: #fff; outline: none;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary) 16%, transparent);
    }
    .lms-card .lms-form .sform-foot, .lms-card .lms-form .form-field.full:last-child { margin-top: 4px; padding-top: 16px; border-top: 1px solid #F1F2F4; flex-direction: row; align-items: center; gap: 14px; }
    .lms-card .lms-form .sform-foot { margin-top: 20px; }
    .lms-card .lms-form button[type=submit] {
        display: inline-flex; align-items: center; justify-content: center; padding: 10px 24px; border: 0; border-radius: 8px;
        background: var(--primary); color: #fff; font-family: inherit; font-size: 14px; font-weight: 600; letter-spacing: 0;
        box-shadow: none; transform: none; cursor: pointer; transition: filter .15s;
    }
    .lms-card .lms-form button[type=submit]:hover { filter: brightness(.93); transform: none; box-shadow: none; }
    .lms-card .lms-form button[type=submit]:disabled { opacity: .7; cursor: default; }

    @media (max-width: 640px) {
        .lms-card .lms-form { padding: 14px; }
        .lms-card .lms-form .sform-grid, .lms-card .lms-form .form-grid { grid-template-columns: 1fr; gap: 14px; }
        .lms-card { font-size: 13.5px; border-radius: 12px; }
        .lms-head { padding: 14px; }
        .lms-tbl th, .lms-tbl td { padding: 10px; }
        .lms-tbl th:first-child, .lms-tbl td:first-child { padding-left: 14px; }
        .lms-tbl th:last-child, .lms-tbl td:last-child { padding-right: 14px; }
        .lms-text { padding: 14px; }
    }
</style>
@endonce
