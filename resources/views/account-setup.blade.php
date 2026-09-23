<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f7f7f8">
    <title>Your SuperLMS account</title>
    <link rel="icon" href="/website-image/Logo.png">
    <style>
        :root {
            --bg: #f7f7f8; --card: #ffffff; --ink: #0f172a; --muted: #64748b; --faint: #94a3b8;
            --line: #eef0f3; --field: #f8fafc; --brand: #4f46e5; --brand-ink: #ffffff;
            --ok: #16a34a; --warn-bg: #fff7ed; --warn-ink: #9a3412;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b0d12; --card: #13161d; --ink: #e5e7eb; --muted: #9ca3af; --faint: #6b7280;
                --line: #1f2430; --field: #0f1218; --brand: #6366f1; --brand-ink: #ffffff;
                --ok: #22c55e; --warn-bg: #2a1a0e; --warn-ink: #fdba74;
            }
        }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
        html, body { margin: 0; background: var(--bg); color: var(--ink); }
        body { font: 15px/1.5 system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif; }
        .wrap { min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 16px 32px; }
        .card { width: 100%; max-width: 400px; background: var(--card); border: 1px solid var(--line); border-radius: 20px; padding: 28px 22px 24px; }

        .school { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 10px; }
        .school img { width: 56px; height: 56px; border-radius: 16px; object-fit: cover; background: var(--field); }
        .school .n { font-size: 13px; font-weight: 600; color: var(--muted); letter-spacing: .01em; }

        h1 { margin: 18px 0 2px; font-size: 22px; font-weight: 700; text-align: center; letter-spacing: -.01em; }
        .sub { margin: 0; text-align: center; color: var(--muted); font-size: 14px; }

        .fields { margin-top: 24px; display: grid; gap: 10px; }
        .field { display: flex; align-items: center; gap: 8px; background: transparent; border: 1px solid var(--line); border-radius: 14px; padding: 12px 8px 12px 16px; }
        .field .t { flex: 1; min-width: 0; }
        .field .k { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .06em; color: var(--faint); }
        .field .v { margin-top: 2px; font: 600 17px/1.3 ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; word-break: break-all; }
        .icon { flex-shrink: 0; width: 40px; height: 40px; display: grid; place-items: center; border: 0; border-radius: 10px; background: transparent; color: var(--muted); cursor: pointer; }
        .icon:hover, .icon:focus-visible { background: var(--line); color: var(--ink); outline: none; }
        .icon.done { color: var(--ok); }
        .icon svg { width: 20px; height: 20px; }

        .notice { margin-top: 10px; background: var(--warn-bg); color: var(--warn-ink); border-radius: 14px; padding: 12px 14px; font-size: 14px; }

        .btn { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 22px; padding: 15px; border-radius: 14px; background: var(--brand); color: var(--brand-ink); font-weight: 600; font-size: 16px; text-decoration: none; }
        .btn:active { transform: scale(.99); }
        .btn svg { width: 18px; height: 18px; }

        .steps { list-style: none; counter-reset: s; margin: 22px 0 0; padding: 0; display: grid; gap: 10px; }
        .steps li { counter-increment: s; display: flex; gap: 12px; align-items: flex-start; font-size: 14px; color: var(--muted); }
        .steps li::before { content: counter(s); flex-shrink: 0; width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; font-size: 12px; font-weight: 600; background: var(--field); border: 1px solid var(--line); color: var(--ink); }

        .note { margin: 20px 0 0; text-align: center; font-size: 12px; color: var(--faint); }
        .foot { margin-top: 20px; font-size: 12px; color: var(--faint); }
        .empty { text-align: center; padding: 8px 0; }
        .empty h1 { margin-top: 0; }
    </style>
</head>
<body>
<main class="wrap">
    <div class="card">
        @if ($state === 'invalid')
            <div class="empty">
                <h1>This link does not work</h1>
                <p class="sub">It may be incomplete, or it was never sent. Ask your school to send your login again.</p>
            </div>
        @else
            <div class="school">
                <img src="{{ $schoolLogo ?: '/website-image/Logo.png' }}" alt="">
                <div class="n">{{ $schoolName ?? 'SuperLMS' }}</div>
            </div>

            <h1>{{ $name }}</h1>
            <p class="sub">{{ $class ?: ($idLabel === 'Username' ? 'Teacher' : 'Student') }}</p>

            <div class="fields">
                <div class="field">
                    <div class="t">
                        <div class="k">{{ $idLabel }}</div>
                        <div class="v" id="idv">{{ $idValue }}</div>
                    </div>
                    <button type="button" class="icon" data-copy="idv" aria-label="Copy {{ strtolower($idLabel) }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                    </button>
                </div>

                @if ($state === 'ready')
                    <div class="field">
                        <div class="t">
                            <div class="k">Password</div>
                            <div class="v" id="pwv" data-secret="{{ $password }}">••••••••</div>
                        </div>
                        <button type="button" class="icon" id="eye" aria-label="Show password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                        <button type="button" class="icon" data-copy="pwv" aria-label="Copy password">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </button>
                    </div>
                @endif
            </div>

            @if ($state === 'expired')
                <div class="notice">This link has expired, so the password is no longer shown. In the app, tap <b>Forgot password</b> and enter your {{ strtolower($idLabel) }}.</div>
            @elseif ($state === 'changed')
                <div class="notice">The password has already been changed. Sign in with it, or tap <b>Forgot password</b> in the app.</div>
            @endif

            <a class="btn" href="{{ $appUrl }}" rel="noopener">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>
                Download Now
            </a>

            <ol class="steps">
                <li>Open the app after it installs.</li>
                <li>Enter your {{ strtolower($idLabel) }}{{ $state === 'ready' ? ' and password' : '' }}.</li>
                <li>Change your password after the first sign-in.</li>
            </ol>

            <p class="note">Keep these details private. The password stops showing here after {{ \App\Models\AccountSetupLink::DAYS }} days or once it is changed.</p>
        @endif
    </div>
    <div class="foot">SuperLMS</div>
</main>
<script>
    (function () {
        var pw = document.getElementById('pwv');
        var eye = document.getElementById('eye');
        if (pw && eye) {
            eye.addEventListener('click', function () {
                var shown = pw.dataset.shown === '1';
                pw.textContent = shown ? '••••••••' : pw.dataset.secret;
                pw.dataset.shown = shown ? '0' : '1';
                eye.setAttribute('aria-label', shown ? 'Show password' : 'Hide password');
            });
        }
        var tick = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';
        document.querySelectorAll('[data-copy]').forEach(function (b) {
            var icon = b.innerHTML;
            b.addEventListener('click', function () {
                var el = document.getElementById(b.dataset.copy);
                var text = (el.dataset.secret || el.textContent).trim();
                var done = function () {
                    b.innerHTML = tick; b.classList.add('done');
                    setTimeout(function () { b.innerHTML = icon; b.classList.remove('done'); }, 1400);
                };
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(text).then(done, function () {});
                } else {
                    var t = document.createElement('textarea');
                    t.value = text; t.setAttribute('readonly', ''); t.style.position = 'fixed'; t.style.opacity = '0';
                    document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done();
                }
            });
        });
    })();
</script>
</body>
</html>
