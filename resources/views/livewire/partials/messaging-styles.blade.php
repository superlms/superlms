{{-- Messaging in the Home's analytics look (2026-11-21), shared by the Super
     Admin's Messaging and the school admin's More → Messaging: a channel's
     tile, the option cards and fields of the slide-ins, the pack-size chips,
     a meter of what is used. On top of home-skin (sa-skin sa-home). --}}
@once
<style>
    [x-cloak] { display: none !important; }
    .mg-ch-ic { width: 38px; height: 38px; border-radius: 12px; flex: none; display: grid; place-items: center; }
    .mg-ch-ic svg { width: 18px; height: 18px; }
    .mg-ch-ic.whatsapp { background: #effaf7; color: #0d9488; }
    .mg-ch-ic.sms { background: #f0f9ff; color: #0284c7; }
    .mg-ch-ic.email { background: #fffbeb; color: #d97706; }
    .mg-ch-ic.sm { width: 30px; height: 30px; border-radius: 10px; }
    .mg-ch-ic.sm svg { width: 15px; height: 15px; }
    .an-tag.whatsapp { background: #effaf7; color: #0f766e; }
    .an-tag.sms { background: #f0f9ff; color: #0369a1; }
    .an-tag.email { background: #fffbeb; color: #b45309; }

    /* Channels side by side: a tile each with its figures. */
    .mg-chs { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; }
    @media (min-width: 900px) { .mg-chs { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    .mg-chc { border: 1px solid #eef1f0; border-radius: 18px; padding: 16px; background: #fff; min-width: 0; }
    .mg-chc .big { font-family: var(--sk-display); font-size: 28px; font-weight: 800; letter-spacing: -.045em; color: var(--sk-ink); line-height: 1; font-variant-numeric: tabular-nums; }
    .mg-chc .big.neg { color: #be123c; }
    .mg-meter { height: 8px; border-radius: 999px; background: #eef2f1; overflow: hidden; }
    .mg-meter > i { display: block; height: 100%; border-radius: 999px; }
    .mg-meter.whatsapp > i { background: #0d9488; }
    .mg-meter.sms > i { background: #0ea5e9; }
    .mg-meter.email > i { background: #f59e0b; }
    .mg-meter > i.over { background: #e11d48; }
    .mg-dot { width: 9px; height: 9px; border-radius: 3px; display: inline-block; flex: none; }

    /* Slide-in sheet (Give Messages, Record Payment, a school's detail, Buy Messages). */
    .mg-sheet-wrap { position: fixed; inset: 0; z-index: 60; }
    .mg-sheet-back { position: absolute; inset: 0; background: rgba(12, 38, 32, .14); backdrop-filter: blur(2px); }
    .mg-sheet { position: absolute; top: 0; right: 0; bottom: 0; width: 100%; max-width: 620px; background: #f6f8f7; display: flex; flex-direction: column; box-shadow: -30px 0 60px -30px rgba(12,38,32,.45); animation: mgin .28s cubic-bezier(.2,.7,.2,1); }
    .mg-sheet.wide { max-width: 860px; }
    @keyframes mgin { from { transform: translateX(28px); } to { transform: none; } }
    .mg-sheet-h { display: flex; align-items: center; gap: 12px; height: 60px; padding: 0 20px; background: #fff; border-bottom: 1px solid #eef1f0; flex: none; }
    .mg-sheet-h h2 { font-family: var(--sk-display); font-size: 18px; font-weight: 800; letter-spacing: -.03em; color: var(--sk-ink); }
    .mg-sheet-h p { font-size: 12px; color: var(--sk-muted); }
    .mg-sheet-b { flex: 1; overflow-y: auto; padding: 18px 20px 24px; }
    .mg-sheet-f { display: flex; align-items: center; justify-content: flex-end; gap: 8px; padding: 12px 20px; background: #fff; border-top: 1px solid #eef1f0; flex: none; }
    .mg-step { font-size: 11px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; color: #95a39e; margin: 0 0 8px; display: flex; align-items: center; gap: 8px; }
    .mg-step b { width: 20px; height: 20px; border-radius: 50%; display: inline-grid; place-items: center; background: var(--sk-teal); color: #fff; font-size: 11px; letter-spacing: 0; }
    .mg-box { background: #fff; border: 1px solid #eef1f0; border-radius: 20px; padding: 16px; }
    .mg-box + .mg-box { margin-top: 12px; }

    /* Option cards (channel, payment, give / take). */
    .mg-opts { display: grid; gap: 8px; grid-template-columns: repeat(var(--c, 3), minmax(0, 1fr)); }
    @media (max-width: 479px) { .mg-opts { grid-template-columns: minmax(0, 1fr); } }
    .mg-opt { position: relative; display: flex; flex-direction: column; align-items: flex-start; gap: 6px; padding: 12px; border-radius: 16px; border: 1.5px solid #e3e8e6; background: #fff; text-align: left; min-width: 0; transition: border-color .2s, background .2s, box-shadow .25s; }
    .mg-opt:hover:not(:disabled) { border-color: #b1e6db; }
    .mg-opt.on { border-color: var(--sk-teal); background: var(--sk-teal-50); box-shadow: 0 10px 22px -16px rgba(13,148,136,.7); }
    .mg-opt:disabled { opacity: .5; cursor: not-allowed; }
    .mg-opt b { font-size: 13.5px; font-weight: 600; color: var(--sk-ink); }
    .mg-opt small { font-size: 11.5px; color: var(--sk-muted); line-height: 1.35; }
    .mg-opt .tick { position: absolute; top: 10px; right: 10px; width: 18px; height: 18px; border-radius: 50%; border: 1.5px solid #d6dedb; background: #fff; }
    .mg-opt.on .tick { border-color: var(--sk-teal); background: var(--sk-teal) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='white' stroke-width='3.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M5 12.5l4.5 4.5L19 7.5'/%3E%3C/svg%3E") center / 12px no-repeat; }
    .mg-seg { display: inline-flex; padding: 4px; border-radius: 999px; background: #eef2f1; gap: 2px; }
    .mg-seg button { height: 32px; padding: 0 16px; border-radius: 999px; font-size: 13px; font-weight: 600; color: #44544f; transition: background .2s, color .2s, box-shadow .2s; }
    .mg-seg button.on { background: #fff; color: var(--sk-ink); box-shadow: 0 2px 8px -3px rgba(12,38,32,.3); }

    /* Pack sizes. */
    .mg-sizes { display: flex; flex-wrap: wrap; gap: 6px; }
    .mg-size { height: 34px; padding: 0 14px; border-radius: 999px; border: 1px solid #e3e8e6; background: #fff; font-size: 13px; font-weight: 600; color: #44544f; font-variant-numeric: tabular-nums; transition: background .2s, border-color .2s, color .2s; }
    .mg-size:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); }
    .mg-size.on { background: var(--sk-teal); border-color: var(--sk-teal); color: #fff; }

    /* Fields: the login's pill. */
    .mg-label { display: block; font-size: 12.5px; font-weight: 600; color: #44544f; margin-bottom: 6px; }
    .mg-in { width: 100%; height: 42px; padding: 0 16px; border-radius: 999px; border: 1px solid #d6dedb; background: #fff; font-size: 14px; color: var(--sk-ink); outline: none; transition: border-color .2s, box-shadow .2s; }
    select.mg-in { padding-right: 34px; appearance: none; -webkit-appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236b7c76' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 14px center; }
    textarea.mg-in { height: auto; min-height: 84px; padding: 12px 16px; border-radius: 18px; resize: vertical; line-height: 1.5; }
    .mg-in:focus { border-color: var(--sk-teal); box-shadow: 0 0 0 4px rgba(13,148,136,.12); }
    .mg-in.err { border-color: #f43f5e; }
    .mg-err { margin-top: 6px; font-size: 12px; color: #e11d48; }
    .mg-hint { margin-top: 6px; font-size: 12px; color: var(--sk-muted); line-height: 1.45; }

    /* The total of a pack, and the line that says what will happen. */
    .mg-total { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; padding: 14px 16px; border-radius: 18px; background: var(--sk-teal-50); border: 1px solid var(--sk-teal-100); }
    .mg-total .sd-big { color: var(--sk-teal-700); }
    .mg-say { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 16px; background: #fff; border: 1px dashed #b1e6db; font-size: 13px; color: #2b433b; line-height: 1.5; }
    .mg-say svg { width: 18px; height: 18px; color: var(--sk-teal); flex: none; margin-top: 1px; }
    .mg-warn { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 16px; background: #fffbeb; border: 1px solid #fde68a; font-size: 13px; color: #92400e; line-height: 1.5; }
    .mg-warn svg { width: 18px; height: 18px; flex: none; margin-top: 1px; }

    /* Buttons. */
    .mg-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; height: 40px; padding: 0 18px; border-radius: 999px; font-size: 13.5px; font-weight: 600; white-space: nowrap; transition: background .2s, border-color .2s, color .2s, box-shadow .25s; }
    .mg-btn svg { width: 16px; height: 16px; }
    .mg-btn.main { background: var(--sk-teal); color: #fff; }
    .mg-btn.main:hover { background: var(--sk-teal-700); box-shadow: 0 10px 22px -10px rgba(13,148,136,.7); }
    .mg-btn.line { border: 1px solid #e3e8e6; background: #fff; color: #44544f; }
    .mg-btn.line:hover { border-color: var(--sk-teal); color: var(--sk-teal-700); background: var(--sk-teal-50); }
    .mg-btn.ghost { color: #44544f; }
    .mg-btn.ghost:hover { background: #eef2f1; }
    .mg-btn.rose { border: 1px solid #fecdd3; background: #fff; color: #e11d48; }
    .mg-btn.rose:hover { background: #fff1f2; }
    .mg-btn.sm { height: 32px; padding: 0 12px; font-size: 12.5px; }
    .mg-btn:disabled { opacity: .55; cursor: not-allowed; }

    /* A switch. */
    .mg-sw { position: relative; width: 38px; height: 22px; border-radius: 999px; background: #d6dedb; flex: none; transition: background .2s; }
    .mg-sw::after { content: ''; position: absolute; top: 3px; left: 3px; width: 16px; height: 16px; border-radius: 50%; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.2); transition: transform .2s; }
    .mg-sw.on { background: var(--sk-teal); }
    .mg-sw.on::after { transform: translateX(16px); }
    .mg-sw:disabled { opacity: .6; }
    .mg-switch { display: flex; align-items: flex-start; gap: 12px; padding: 14px 0; border-top: 1px solid #f0f3f2; }
    .mg-switch:first-child { border-top: 0; padding-top: 0; }

    .mg-qr { display: grid; place-items: center; padding: 10px; border-radius: 18px; background: #fff; border: 1px solid #eef1f0; }
    .mg-qr svg { display: block; width: 180px; height: 180px; }
    .mg-pages nav { padding: 12px 20px; border-top: 1px solid #eef1f0; }
</style>
@endonce
