{{--
    The look of the parts a school's website reads from its own records
    (App\Support\SchoolSiteData) — classes, fee structure, rules. Shared by
    every template; it takes the template's colours from its own variables.
--}}
@once
<style>
    .lms { --lms-line: var(--line, var(--border, #E4E5E8)); --lms-muted: var(--muted, var(--text3, #666)); --lms-text: var(--text, #222); color: var(--lms-text); font-size: 14px; }
    .lms-strong { font-weight: 600; color: var(--lms-text); }
    .lms-muted { color: var(--lms-muted); font-size: 12.5px; }
    .lms-chip { display: inline-block; padding: 2px 10px; margin: 2px 4px 2px 0; border-radius: 999px; font-size: 12.5px; line-height: 1.6; border: 1px solid var(--lms-line); background: #fff; white-space: nowrap; }
    .lms-chip.soft { background: var(--faint, var(--light, #F6F7F9)); border-color: transparent; }
    .lms-scroll { overflow-x: auto; }
    .lms-tbl { width: 100%; border-collapse: collapse; }
    .lms-tbl th { text-align: left; padding: 10px 12px; background: var(--primary); color: #fff; font-size: 12px; font-weight: 600; letter-spacing: .4px; text-transform: uppercase; }
    .lms-tbl td { padding: 10px 12px; border-bottom: 1px solid var(--lms-line); vertical-align: top; }
    .lms-tbl tr:last-child td { border-bottom: 0; }
    .lms-num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }

    .lms-fees { display: grid; gap: 10px; }
    .lms-fee { border: 1px solid var(--lms-line); border-radius: 10px; background: #fff; overflow: hidden; }
    .lms-fee summary { display: flex; align-items: center; gap: 10px; padding: 12px 14px; cursor: pointer; list-style: none; }
    .lms-fee summary::-webkit-details-marker { display: none; }
    .lms-fee summary::after { content: '▾'; color: var(--lms-muted); transition: transform .2s; }
    .lms-fee[open] summary::after { transform: rotate(180deg); }
    .lms-fee summary .lms-amt { margin-left: auto; font-weight: 700; color: var(--primary); font-variant-numeric: tabular-nums; }
    .lms-fee table { width: 100%; border-collapse: collapse; border-top: 1px solid var(--lms-line); }
    .lms-fee td { padding: 8px 14px; border-bottom: 1px solid var(--lms-line); }
    .lms-fee tr.total td { font-weight: 700; border-bottom: 0; background: var(--faint, var(--light, #F6F7F9)); }

    .lms-rules ol { margin: 0; padding-left: 22px; display: grid; gap: 10px; }
    .lms-rules li b { display: block; color: var(--lms-text); }
    .lms-rules .desc { color: var(--lms-muted); line-height: 1.7; margin-top: 2px; }
    .lms-info { display: grid; grid-template-columns: auto 1fr; gap: 6px 14px; margin-top: 16px; }
    .lms-info dt { color: var(--lms-muted); }
    .lms-files { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
    .lms-files a { display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px; border-radius: 999px; background: var(--primary); color: #fff; font-size: 12.5px; font-weight: 600; text-decoration: none; }

    @media (max-width: 640px) {
        .lms-stack thead { display: none; }
        .lms-stack tr { display: block; padding: 10px 0; border-bottom: 1px solid var(--lms-line); }
        .lms-stack td { display: block; padding: 3px 4px; border: 0; }
        .lms-stack td[data-label]::before { content: attr(data-label); display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: var(--lms-muted); }
    }
</style>
@endonce
