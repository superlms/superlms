{{-- The Super Admin's sign-in screens (Login with its Verify OTP step, and
     Forgot Password with its OTP and new-password steps) in the superlms.in
     website's colours and layout (user's ask of 2026-11-18): a white page
     with the website's soft teal and olive glows and one large rounded card
     (nothing over or under it) — on the left a mint panel (the website's
     call-to-action band) with the step acted out in a small app window
     (partials.super-admin-auth-motion), on the right the form under the
     platform's logo (on its own, no box), a display heading with a gradient
     word (no eyebrow over it), and the website's pill button. A field's
     typed text takes its border's colour (teal once filled, red while wrong).

     Everything is scoped to .sa-auth, so the shared field styles
     (partials.super-admin-auth-fields) keep their colours on the school and
     accounts panels' screens; here the fields, code boxes and links turn teal.

     Classes: .sa-auth (page), .sa-auth-card, .sa-auth-art, .sa-auth-form,
     .grad, .sa-btn (main button), .sa-accent (teal link),
     .sa-quiet (grey link). --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap">
<style>
    .sa-auth {
        --ink: #0c2620; --text: #2b433b; --muted: #6b7c76; --faint: #c3ccc8; --line: #e5eae8; --line-2: #d6dedb;
        --teal: #0d9488; --teal-700: #0f766e; --teal-50: #effaf7; --teal-100: #d8f3ec; --teal-200: #b1e6db; --olive: #86a83a;
        --ease: cubic-bezier(.2,.7,.2,1);
        position: relative; isolation: isolate; overflow: hidden; min-height: 100vh; display: flex; flex-direction: column;
        background: #fff; color: var(--text); font-family: 'Geist', 'Segoe UI', system-ui, -apple-system, sans-serif;
        -webkit-font-smoothing: antialiased;
    }
    .sa-auth::before, .sa-auth::after { content: ''; position: absolute; z-index: -1; border-radius: 50%; filter: blur(70px); pointer-events: none; }
    .sa-auth::before { width: 620px; height: 520px; right: -120px; top: -140px; background: radial-gradient(circle, rgba(13,148,136,.16), transparent 70%); }
    .sa-auth::after { width: 520px; height: 440px; left: -140px; bottom: -120px; background: radial-gradient(circle, rgba(134,168,58,.14), transparent 70%); }

    /* The card. On a computer the whole screen fits the window, so the browser
       never shows a scrollbar (user's ask of 2026-11-19): the space round the
       card and the card's height follow the window's height — 600px tall where
       there is room, shorter on a laptop whose window is less than ~660px. */
    .sa-auth { --sa-pad: clamp(12px, 3.5vh, 28px); }
    .sa-auth-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--sa-pad) 16px; }
    /* No hard border: a feathered, wide soft shadow, so the card reads as part of the page. */
    .sa-auth-card { width: 100%; max-width: 1040px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); min-height: min(600px, calc(100vh - 2 * var(--sa-pad))); min-height: min(600px, calc(100dvh - 2 * var(--sa-pad))); background: #fff; border-radius: 32px; overflow: hidden;
        box-shadow: 0 0 0 1px rgba(12,38,32,.03), 0 40px 110px -40px rgba(12,38,32,.26), 0 0 70px -24px rgba(13,148,136,.16); animation: sa-auth-rise .7s var(--ease) both; }
    @keyframes sa-auth-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

    /* Left: the website's mint band with the picture */
    .sa-auth-art { position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px;
        background: radial-gradient(120% 140% at 0% 0%, #d3f0e8 0%, rgba(211,240,232,0) 55%), radial-gradient(90% 120% at 100% 100%, #f4f3d6 0%, rgba(244,243,214,0) 55%), #ecf7f3; }
    .sa-auth-art::after { content: ''; position: absolute; width: 380px; height: 380px; border-radius: 50%; right: -120px; top: -160px; background: radial-gradient(closest-side, rgba(255,255,255,.7), transparent); pointer-events: none; }
    .sa-auth-art .sa-title { font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
    .sa-auth-art .sa-sub { color: var(--muted); }

    /* Right: the form */
    .sa-auth-form { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px; }
    /* The logo on its own (no box round it), a little bigger. */
    .sa-auth-logo { margin-bottom: 18px; line-height: 0; }
    .sa-auth-logo img { width: 54px; height: 54px; object-fit: contain; }
    .sa-auth .sa-title { font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; }
    .sa-auth-form .sa-title { font-weight: 800; letter-spacing: -.04em; color: var(--ink); line-height: 1.1; }
    .sa-auth .grad { background: linear-gradient(92deg, var(--teal) 0%, #23a083 55%, var(--olive) 110%); -webkit-background-clip: text; background-clip: text; color: transparent; padding-bottom: .06em; }
    .sa-auth .sa-sub { color: var(--muted); }

    /* Fields, code boxes and links in teal (only on these screens) */
    .sa-auth .sa-field input { border-color: var(--line-2); font-family: inherit; }
    .sa-auth .sa-field input:focus { border-color: var(--teal); }
    /* Typed text in the border's colour: teal once filled, red while wrong. */
    .sa-auth .sa-field.is-filled input, .sa-auth .sa-field input:-webkit-autofill { border-color: var(--teal); color: var(--teal); -webkit-text-fill-color: var(--teal); }
    .sa-auth .sa-field.is-filled label { color: var(--teal-700); }
    .sa-auth .sa-field[data-invalid] input, .sa-auth .sa-field.is-bad input { border-color: #dc2626; color: #dc2626; -webkit-text-fill-color: #dc2626; }
    .sa-auth .sa-field[data-invalid] label, .sa-auth .sa-field.is-bad label { color: #dc2626; }
    .sa-auth .sa-eye:hover { color: var(--teal-700); }
    .sa-auth .sa-otp-box { border-color: var(--line-2); border-radius: 14px; font-family: inherit; }
    .sa-auth .sa-otp-box:focus, .sa-auth .sa-otp-box.is-filled { border-color: var(--teal); }
    .sa-auth .sa-otp-box.is-filled { background: var(--teal-50); color: var(--teal); }
    .sa-auth .sa-otp[data-invalid] .sa-otp-box { border-color: #dc2626; background: #fff; color: #dc2626; }

    .sa-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; height: 52px; padding: 0 28px; border: 1px solid transparent; border-radius: 999px; background: var(--teal); color: #fff; font-size: 16px; font-weight: 500; letter-spacing: -.01em;
        transition: background .2s, box-shadow .25s, transform .25s var(--ease); }
    .sa-btn:hover:not(:disabled) { background: var(--teal-700); box-shadow: 0 12px 26px -10px rgba(13,148,136,.7); transform: translateY(-1px); }
    .sa-btn:disabled { opacity: .6; cursor: not-allowed; }
    .sa-accent { color: var(--teal-700); font-weight: 500; }
    .sa-accent:hover { color: var(--ink); text-decoration: underline; text-underline-offset: 3px; }
    .sa-quiet { color: var(--muted); }
    .sa-quiet:hover { color: var(--ink); text-decoration: underline; text-underline-offset: 3px; }
    .sa-auth-note { width: 100%; max-width: 24rem; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 8px; padding: 12px 16px; border-radius: 16px; background: var(--teal-50); border: 1px solid var(--teal-100); color: var(--teal-700); font-size: 14px; }
    @media (prefers-reduced-motion: reduce) { .sa-auth-card { animation: none; } }

    /* A short window (a laptop with the bookmarks bar, a zoomed page): a little
       less air in the card, so the form and the picture still fit without a scroll. */
    @media (min-width: 768px) and (max-height: 720px) {
        .sa-auth-form { padding: 26px 44px; }
        .sa-auth-art { padding: 24px 32px; }
        .sa-auth-logo { margin-bottom: 12px; }
        .sa-auth-logo img { width: 46px; height: 46px; }
        .sa-auth-form > .text-center { margin-bottom: 22px; }
        .sa-auth-art .am { zoom: 1; }
        .sa-auth-art h2.sa-title { margin-top: 22px; }
    }
    @media (min-width: 768px) and (max-height: 600px) {
        .sa-auth-form { padding: 18px 40px; }
        .sa-auth-form > .text-center { margin-bottom: 14px; }
        .sa-auth-art .am { zoom: .86; }
        .sa-auth-art h2.sa-title { margin-top: 14px; }
        .sa-auth .sa-btn { height: 46px; }
    }

    @media (max-width: 767px) {
        .sa-auth-main { align-items: flex-start; padding: 8px 12px 20px; }
        .sa-auth-card { grid-template-columns: minmax(0, 1fr); min-height: 0; border-radius: 26px; }
        .sa-auth-art { display: none; }
        .sa-auth-form { padding: 32px 20px 30px; }
    }
</style>
