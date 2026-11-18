{{-- The Super Admin's sign-in screens (Login with its Verify OTP step, and
     Forgot Password with its OTP and new-password steps) in the superlms.in
     website's colours and layout (user's ask of 2026-11-18): a white page
     with the website's soft teal and olive glows and one large rounded card
     (nothing over or under it) — on the left a mint panel (the website's
     call-to-action band) with the step acted out in a small app window
     (partials.super-admin-auth-motion), on the right the form with a teal
     eyebrow, a display heading with a gradient word, and the website's pill
     button. A field's typed text takes its border's colour (teal once
     filled, red while wrong).

     Everything is scoped to .sa-auth, so the shared field styles
     (partials.super-admin-auth-fields) keep their colours on the school and
     accounts panels' screens; here the fields, code boxes and links turn teal.

     Classes: .sa-auth (page), .sa-auth-card, .sa-auth-art, .sa-auth-form,
     .sa-auth-eyebrow, .grad, .sa-btn (main button), .sa-accent (teal link),
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

    /* The card */
    .sa-auth-main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 28px 16px; }
    .sa-auth-card { width: 100%; max-width: 1040px; display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); min-height: 600px; background: #fff; border: 1px solid var(--line); border-radius: 32px; overflow: hidden;
        box-shadow: 0 44px 90px -30px rgba(12,38,32,.32), 0 14px 28px -14px rgba(12,38,32,.14); animation: sa-auth-rise .7s var(--ease) both; }
    @keyframes sa-auth-rise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }

    /* Left: the website's mint band with the picture */
    .sa-auth-art { position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 40px;
        background: radial-gradient(120% 140% at 0% 0%, #d3f0e8 0%, rgba(211,240,232,0) 55%), radial-gradient(90% 120% at 100% 100%, #f4f3d6 0%, rgba(244,243,214,0) 55%), #ecf7f3; }
    .sa-auth-art::after { content: ''; position: absolute; width: 380px; height: 380px; border-radius: 50%; right: -120px; top: -160px; background: radial-gradient(closest-side, rgba(255,255,255,.7), transparent); pointer-events: none; }
    .sa-auth-art .sa-title { font-weight: 800; letter-spacing: -.035em; color: var(--ink); }
    .sa-auth-art .sa-sub { color: var(--muted); }

    /* Right: the form */
    .sa-auth-form { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px; }
    .sa-auth-logo { width: 60px; height: 60px; margin-bottom: 18px; border-radius: 18px; display: grid; place-items: center; background: #fff; border: 1px solid var(--line); box-shadow: 0 12px 30px -16px rgba(12,38,32,.35); }
    .sa-auth-logo img { width: 38px; height: 38px; object-fit: contain; }
    .sa-auth-eyebrow { display: inline-block; font-size: 14.5px; font-weight: 600; color: var(--teal); margin-bottom: 8px; }
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

    @media (max-width: 767px) {
        .sa-auth-main { align-items: flex-start; padding: 8px 12px 20px; }
        .sa-auth-card { grid-template-columns: minmax(0, 1fr); min-height: 0; border-radius: 26px; }
        .sa-auth-art { display: none; }
        .sa-auth-form { padding: 32px 20px 30px; }
    }
</style>
