<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Your SuperLMS account</title>
    <link rel="icon" href="/website-image/Logo.png">
    <style>
        :root { --ink:#111827; --muted:#6b7280; --line:#e5e7eb; --brand:#4f46e5; --bg:#f3f4f6; --ok:#16a34a; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background:var(--bg); color:var(--ink); }
        .wrap { max-width: 440px; margin: 0 auto; padding: 24px 16px 40px; }
        .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:22px 20px; }
        .school { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
        .school img { width:44px; height:44px; border-radius:10px; object-fit:cover; border:1px solid var(--line); }
        .school .n { font-weight:700; font-size:15px; }
        .school .s { font-size:12px; color:var(--muted); }
        h1 { font-size:20px; margin:0 0 4px; }
        .sub { color:var(--muted); font-size:14px; margin:0 0 18px; }
        .row { border:1px solid var(--line); border-radius:12px; padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:10px; }
        .row .k { font-size:12px; color:var(--muted); }
        .row .v { font-size:17px; font-weight:700; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; word-break:break-all; }
        .copy { border:1px solid var(--line); background:#fff; border-radius:8px; padding:7px 12px; font-size:13px; font-weight:600; color:var(--brand); cursor:pointer; flex-shrink:0; }
        .copy.done { color:var(--ok); border-color:var(--ok); }
        .btn { display:block; text-align:center; text-decoration:none; background:var(--brand); color:#fff; font-weight:700; font-size:16px; padding:14px; border-radius:12px; margin-top:18px; }
        .steps { font-size:14px; color:var(--ink); padding-left:18px; margin:16px 0 0; line-height:1.6; }
        .note { font-size:12px; color:var(--muted); margin-top:14px; line-height:1.5; }
        .warn { background:#fef3c7; color:#92400e; border-radius:12px; padding:12px 14px; font-size:14px; line-height:1.5; margin-bottom:10px; }
        .foot { text-align:center; font-size:12px; color:var(--muted); margin-top:18px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        @if ($state === 'invalid')
            <h1>This link does not work</h1>
            <p class="sub">It may have been typed wrongly, or it was never sent. Ask your school to send your login again.</p>
        @else
            <div class="school">
                <img src="{{ $schoolLogo ?: '/website-image/Logo.png' }}" alt="">
                <div>
                    <div class="n">{{ $schoolName ?? 'SuperLMS' }}</div>
                    <div class="s">SuperLMS</div>
                </div>
            </div>

            <h1>{{ $name }}</h1>
            <p class="sub">{{ $class ?: ($idLabel === 'Username' ? 'Teacher' : 'Student') }}</p>

            <div class="row">
                <div>
                    <div class="k">{{ $idLabel }}</div>
                    <div class="v" id="idv">{{ $idValue }}</div>
                </div>
                <button type="button" class="copy" data-copy="idv">Copy</button>
            </div>

            @if ($state === 'ready')
                <div class="row">
                    <div>
                        <div class="k">Password</div>
                        <div class="v" id="pwv">{{ $password }}</div>
                    </div>
                    <button type="button" class="copy" data-copy="pwv">Copy</button>
                </div>
            @elseif ($state === 'expired')
                <div class="warn">This link has expired, so the password is no longer shown here. Open the app and tap <b>Forgot password</b> with your {{ strtolower($idLabel) }} to set a new one.</div>
            @else
                <div class="warn">The password has already been changed. Open the app and sign in with it, or tap <b>Forgot password</b> with your {{ strtolower($idLabel) }}.</div>
            @endif

            <a class="btn" href="{{ $appUrl }}" rel="noopener">Download the SuperLMS app</a>

            <ol class="steps">
                <li>Download and open the SuperLMS app.</li>
                <li>Enter your {{ strtolower($idLabel) }}{{ $state === 'ready' ? ' and the password above' : '' }}.</li>
                <li>Change your password after you sign in the first time.</li>
            </ol>

            <p class="note">Keep these details to yourself. This page stops showing the password {{ \App\Models\AccountSetupLink::DAYS }} days after it was sent, or once the password is changed.</p>
        @endif
    </div>
    <div class="foot">SuperLMS</div>
</div>
<script>
    document.querySelectorAll('.copy').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = document.getElementById(b.dataset.copy).textContent.trim();
            var done = function () { b.textContent = 'Copied'; b.classList.add('done'); setTimeout(function () { b.textContent = 'Copy'; b.classList.remove('done'); }, 1500); };
            if (navigator.clipboard) { navigator.clipboard.writeText(t).then(done, function () {}); }
            else { var r = document.createRange(); r.selectNodeContents(document.getElementById(b.dataset.copy)); var s = getSelection(); s.removeAllRanges(); s.addRange(r); document.execCommand('copy'); s.removeAllRanges(); done(); }
        });
    });
</script>
</body>
</html>
