{{-- The profile pages' own bits on Home's skin (Super Admin → My Profile, and
 since 2026-11-25 the school admin's Profile): the fields carry their name on
 the border, in the login's look; chips are one family; figures split by
 hairlines. --}}
@once
<style>
    /* In Tailwind's components layer, so utilities (pl-10, pr-11 …) still win. */
    @layer components {
    .pf-field { position: relative; display: block; }
    .pf-input {
        width: 100%; height: 2.875rem; border: 1.5px solid #d6dedb; border-radius: 9999px; background: #fff;
        padding: 0 1.125rem; font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; font-size: .9375rem; color: #111827;
        transition: border-color .15s, color .15s;
    }
    textarea.pf-input { height: auto; min-height: 5.75rem; border-radius: 1.125rem; padding: .85rem 1.125rem .7rem; resize: vertical; line-height: 1.45; }
    .pf-input::placeholder { color: #9ca3af; }
    .pf-input:focus { outline: none; box-shadow: none; border-color: #0d9488; }
    .pf-input.sa-filled { border-color: #0d9488; color: #0d9488; }
    .pf-label {
        position: absolute; left: 1rem; top: 0; transform: translateY(-50%);
        max-width: calc(100% - 2rem); padding: 0 .375rem; background: #fff;
        font-size: .75rem; font-weight: 600; line-height: 1rem; color: #6b7280;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none; transition: color .15s;
    }
    .pf-field:focus-within .pf-label, .pf-field:has(.sa-filled) .pf-label { color: #0f766e; }
    .pf-field.has-error .pf-input { border-color: #dc2626; color: #dc2626; }
    .pf-field.has-error .pf-label { color: #dc2626; }
    .pf-err { margin: .35rem 0 0 1.125rem; font-size: .75rem; line-height: 1rem; color: #dc2626; }

    .pf-chip {
        display: inline-flex; align-items: center; gap: .35rem; height: 1.5rem; padding: 0 .6rem;
        border-radius: 9999px; border: 1px solid transparent; white-space: nowrap;
        font-size: .6875rem; font-weight: 600; line-height: 1; letter-spacing: .01em;
    }
    .pf-chip > svg { width: .8rem; height: .8rem; flex-shrink: 0; }
    .pf-chip.pf-dot::before { content: ''; width: .375rem; height: .375rem; border-radius: 9999px; background: currentColor; }
    .pf-chip.pf-teal    { background: #effaf7; color: #0f766e; border-color: #d8f3ec; }
    .pf-chip.pf-sky     { background: #f0f9ff; color: #0369a1; border-color: #dff1fd; }
    .pf-chip.pf-emerald { background: #ecfdf5; color: #047857; border-color: #d3f5e5; }
    .pf-chip.pf-amber   { background: #fffbeb; color: #b45309; border-color: #fdefc8; }
    .pf-chip.pf-rose    { background: #fff1f2; color: #be123c; border-color: #ffe1e5; }
    .pf-chip.pf-gray    { background: #f6f8f7; color: #4b5b56; border-color: #e8edeb; }

    .pf-icon { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #effaf7; color: #0d9488; }
    .pf-icon svg { width: 1rem; height: 1rem; }
    /* A light pill button (Choose image, Cancel …). */
    .pf-ghost { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 9999px; border: 1px solid #e3e8e6; background: #fff; font-size: 13.5px; font-weight: 500; color: #44544f; white-space: nowrap; cursor: pointer; transition: border-color .2s, color .2s, background .2s; }
    .pf-ghost:hover { border-color: #b1e6db; color: #0f766e; background: #effaf7; }
    .pf-round { width: 32px; height: 32px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; color: #8b9893; transition: background .2s, color .2s; flex-shrink: 0; }
    .pf-round:hover { background: #effaf7; color: #0f766e; }
    .pf-round.danger:hover { background: #fff1f2; color: #e11d48; }
    .pf-round svg { width: 16px; height: 16px; }
    /* Figures in one card, split by hairlines (no small cards). */
    .pf-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid #eef1f0; border-radius: 16px; overflow: hidden; }
    .pf-figs > * { padding: 12px 14px; min-width: 0; border-left: 1px solid #eef1f0; }
    .pf-figs > :first-child { border-left: 0; }
    .pf-figs .l { font-size: 11.5px; color: #6b7c76; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .pf-figs .v { font-size: 17px; font-weight: 600; color: #0c2620; margin-top: 2px; font-variant-numeric: tabular-nums; }
    .pf-figs .v small { font-size: 11.5px; font-weight: 400; color: #95a39e; }
    .pf-glance { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
    .pf-glance > * { padding: 16px 18px; border-top: 1px solid #f0f3f2; border-left: 1px solid #f0f3f2; min-width: 0; display: block; transition: background .2s; }
    .pf-glance > :nth-child(-n+3) { border-top: 0; }
    .pf-glance > :nth-child(3n+1) { border-left: 0; }
    .pf-glance > a:hover { background: #f8fbfa; }
    @media (max-width: 639px) {
        .pf-glance { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pf-glance > * { border-left: 1px solid #f0f3f2 !important; border-top: 1px solid #f0f3f2 !important; }
        .pf-glance > :nth-child(2n+1) { border-left: 0 !important; }
        .pf-glance > :nth-child(-n+2) { border-top: 0 !important; }
    }
    .pf-two { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 1024px) { .pf-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
    .pf-main { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); }
    @media (min-width: 1280px) { .pf-main { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); } }
    @media (min-width: 1280px) { .pf-main.wide-first { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
    }
</style>
@endonce
