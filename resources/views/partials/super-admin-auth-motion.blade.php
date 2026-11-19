{{--
    The picture beside the Super Admin's sign-in forms (user's ask of
    2026-11-18): a small web-app window in the superlms.in look, acting out
    the step on a 7 s loop, with a mouse pointer —
      login   — the pointer clicks the email field and the address is typed,
                clicks the password field and the dots fill in, presses Login
                and an "OTP sent" note pops up;
      otp     — the pointer clicks the first box, the 6 digits are typed in one
                by one, it presses Verify & Login and "Signed in" pops up;
      reset   — the email is typed, Send OTP is pressed, "OTP sent" pops up;
      newpass — the new password is typed (the strength meter fills), then
                the confirmation, Reset Password is pressed, "Password updated".
    The typed text is teal, like the field's border once it has a value.

    One timeline per step ($spec below): the pointer's stops, its clicks, each
    field's focus and typing, the button press and the note — the keyframes
    are written from it. Nothing moves for people who ask for reduced motion
    (the finished step is shown instead).

    Params: $variant ('login' | 'otp' | 'reset' | 'newpass'), optional $subtitle;
    optional $amTitle (the window's heading), $amPath (its address after the
    host), $amHeadline (the caption's heading) and $amEmail (the address typed).
--}}
@php
    $variant = in_array($variant ?? 'login', ['login', 'otp', 'reset', 'newpass'], true) ? ($variant ?? 'login') : 'login';
    $v = 'am-' . $variant;

    // Each step's timeline, in % of the loop. Field/box y positions follow the
    // window's fixed layout (bar 32px, body padding 18px 20px 20px, logo 34 + 8,
    // heading 18 + 14 or + 4 with a sub line of 14 + 14, fields 38 + 10, button 36).
    $spec = [
        'login' => [
            'title' => 'Super Admin Login', 'sub' => null, 'path' => 'admin',
            'fields' => [
                ['Email', 'Enter your email', 'admin@superlms.in', 13, 47, 15, 38],
                ['Password', 'Enter your password', '••••••••', 48, 72, 50, 62],
            ],
            'button' => ['Login', 73], 'toast' => ['OTP sent', 'To your email & mobile', 77, 92],
            'cursor' => [[0, 262, 300, 0], [4, 262, 300, 1], [12, 212, 145, 1], [40, 212, 145, 1], [47, 212, 193, 1], [64, 212, 193, 1],
                         [71, 162, 244, 1], [78, 162, 244, 1], [88, 250, 300, 1], [93, 250, 300, 0], [100, 250, 300, 0]],
            'clicks' => [13, 48, 73],
        ],
        'otp' => [
            'title' => 'Verify OTP', 'sub' => 'Code sent to your email & mobile', 'path' => 'admin',
            'digits' => ['4', '8', '2', '9', '1', '6'], 'digitAt' => [18, 24, 30, 36, 42, 48],
            'button' => ['Verify & Login', 57], 'toast' => ['Signed in', 'Welcome to your panel', 61, 92],
            'cursor' => [[0, 262, 290, 0], [4, 262, 290, 1], [12, 54, 163, 1], [14.5, 54, 163, 1], [18, 80, 188, 1], [49, 80, 188, 1], [56, 162, 216, 1], [62, 162, 216, 1],
                         [80, 250, 290, 1], [86, 250, 290, 0], [100, 250, 290, 0]],
            'clicks' => [13, 57],
        ],
        'reset' => [
            'title' => 'Forgot Password?', 'sub' => 'We’ll send a code to reset it', 'path' => 'forgot-password',
            'fields' => [
                ['Email Address', 'Enter your email address', 'admin@superlms.in', 13, 52, 15, 40],
            ],
            'button' => ['Send OTP', 53], 'toast' => ['OTP sent', 'Check your email & mobile', 57, 90],
            'cursor' => [[0, 262, 290, 0], [4, 262, 290, 1], [12, 212, 163, 1], [44, 212, 163, 1], [51, 162, 214, 1], [58, 162, 214, 1],
                         [78, 250, 290, 1], [84, 250, 290, 0], [100, 250, 290, 0]],
            'clicks' => [13, 53],
        ],
        'newpass' => [
            'title' => 'Set New Password', 'sub' => null, 'path' => 'forgot-password',
            'fields' => [
                ['New Password', 'Enter new password', '••••••••••', 12, 45, 14, 32],
                ['Confirm Password', 'Confirm new password', '••••••••••', 46, 73, 48, 62],
            ],
            'meter' => [20, 26, 32],
            'button' => ['Reset Password', 74], 'toast' => ['Password updated', 'Sign in with the new one', 78, 93],
            'cursor' => [[0, 262, 310, 0], [4, 262, 310, 1], [11, 212, 145, 1], [38, 212, 145, 1], [45, 212, 193, 1], [64, 212, 193, 1],
                         [72, 162, 258, 1], [79, 162, 258, 1], [89, 250, 310, 1], [94, 250, 310, 0], [100, 250, 310, 0]],
            'clicks' => [12, 46, 74],
        ],
    ][$variant];

    $caption = [
        'login'   => ['Welcome back', 'Sign in to manage your school'],
        'otp'     => ['Check your inbox', 'We sent you a one-time code'],
        'reset'   => ['Forgot password?', "No worries — let's reset it"],
        'newpass' => ['Almost done', 'Choose a strong new password'],
    ][$variant];
    $subtitle = $subtitle ?? $caption[1];

    // Another panel's screens (the school admin's login and reset, 2026-11-19)
    // name their own window: its heading, the address after the host, the
    // email typed in, and the caption under the picture.
    if (!empty($amTitle))    { $spec['title'] = $amTitle; }
    if (!empty($amPath))     { $spec['path'] = $amPath; }
    if (!empty($amHeadline)) { $caption[0] = $amHeadline; }
    if (!empty($amEmail) && !empty($spec['fields'][0]) && str_contains($spec['fields'][0][2], '@')) {
        // Same length as the address it replaces would keep the timeline's pace;
        // a different length only types a little faster or slower.
        $spec['fields'][0][2] = $amEmail;
    }

    // Keyframe writers.
    $grey = '#d6dedb'; $teal = '#0d9488';
    $kf = [];
    $kf[] = "@keyframes {$v}-cur { " . implode(' ', array_map(fn ($p) => "{$p[0]}% { transform: translate({$p[1]}px, {$p[2]}px); opacity: {$p[3]}; }", $spec['cursor'])) . ' }';
    $rip = ['0% { opacity: 0; transform: scale(.4); }'];
    foreach ($spec['clicks'] as $c) {
        $rip[] = ($c - .5) . "% { opacity: 0; transform: scale(.4); } " . ($c + .5) . "% { opacity: .6; transform: scale(.5); } " . ($c + 5) . "% { opacity: 0; transform: scale(1.8); }";
    }
    $rip[] = '100% { opacity: 0; transform: scale(1.8); }';
    $kf[] = "@keyframes {$v}-rip { " . implode(' ', $rip) . ' }';
    foreach ($spec['fields'] ?? [] as $i => [$label, $ph, $text, $click, $leave, $from, $to]) {
        $n = mb_strlen($text);
        $kf[] = "@keyframes {$v}-f{$i} { 0%, " . ($click - .5) . "% { border-color: {$grey}; box-shadow: none; } {$click}%, {$leave}% { border-color: {$teal}; box-shadow: 0 0 0 3px rgba(13,148,136,.14); } "
            . ($leave + .5) . "%, 95% { border-color: {$teal}; box-shadow: none; } 96%, 100% { border-color: {$grey}; box-shadow: none; } }";
        // Typed a character at a time, in the form's own face (Geist): each
        // character opens at its moment and they all close together for the loop.
        for ($j = 0; $j < $n; $j++) {
            $at = round($from + ($to - $from) * ($j + 1) / $n, 2);
            $kf[] = "@keyframes {$v}-t{$i}-{$j} { 0%, " . ($at - .01) . "% { max-width: 0; } {$at}%, 95% { max-width: 1em; } 95.01%, 100% { max-width: 0; } }";
        }
        $kf[] = "@keyframes {$v}-p{$i} { 0%, " . ($from - .5) . "% { opacity: 1; } {$from}%, 95% { opacity: 0; } 96%, 100% { opacity: 1; } }";
        $kf[] = "@keyframes {$v}-c{$i} { 0%, " . ($click - .5) . "% { opacity: 0; } {$click}%, {$leave}% { opacity: 1; } " . ($leave + .5) . "%, 100% { opacity: 0; } }";
    }
    foreach ($spec['digitAt'] ?? [] as $i => $at) {
        $cur = $i === 0 ? $spec['clicks'][0] : $spec['digitAt'][$i - 1];
        $kf[] = "@keyframes {$v}-b{$i} { 0%, " . ($cur - .5) . "% { border-color: {$grey}; box-shadow: none; background: #fff; } {$cur}%, " . ($at - .5) . "% { border-color: {$teal}; box-shadow: 0 0 0 3px rgba(13,148,136,.14); background: #fff; } "
            . "{$at}%, 95% { border-color: {$teal}; box-shadow: none; background: #effaf7; } 96%, 100% { border-color: {$grey}; box-shadow: none; background: #fff; } }";
        $kf[] = "@keyframes {$v}-d{$i} { 0%, " . ($at - .5) . "% { opacity: 0; transform: scale(.4); } " . ($at + 1.5) . "% { opacity: 1; transform: scale(1.15); } " . ($at + 3) . "%, 95% { opacity: 1; transform: none; } 96%, 100% { opacity: 0; transform: scale(.4); } }";
    }
    foreach ($spec['meter'] ?? [] as $i => $at) {
        $kf[] = "@keyframes {$v}-m{$i} { 0%, " . ($at - .5) . "% { background: #eceff0; } {$at}%, 95% { background: {$teal}; } 96%, 100% { background: #eceff0; } }";
    }
    [$btnLabel, $press] = $spec['button'];
    $kf[] = "@keyframes {$v}-btn { 0%, " . ($press - .5) . "% { transform: none; background: {$teal}; } {$press}% { transform: scale(.95); background: #0f766e; } " . ($press + 3) . "%, 100% { transform: none; background: {$teal}; } }";
    [$toastTitle, $toastSub, $tFrom, $tTo] = $spec['toast'];
    $kf[] = "@keyframes {$v}-toast { 0%, {$tFrom}% { opacity: 0; transform: translateY(8px) scale(.95); } " . ($tFrom + 3) . "%, {$tTo}% { opacity: 1; transform: none; } " . ($tTo + 3) . "%, 100% { opacity: 0; transform: translateY(-4px) scale(.98); } }";

    $host = request()->getHost();
@endphp

<div wire:key="auth-motion-{{ $variant }}" class="relative z-10 flex flex-col items-center text-center">
    <style>
        /* Laid out at 300px (the pointer's stops are in that frame) and zoomed to fit the panel. */
        .am { position: relative; width: 300px; zoom: 1.12; margin: 0 auto; text-align: left; font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; animation: am-in .6s cubic-bezier(.2,.7,.2,1) both; }
        @keyframes am-in { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: none; } }
        /* A soft, feathered edge (no hard line): the window sits in the panel rather than on it. */
        .am-win { position: relative; border-radius: 18px; background: #fff; overflow: hidden; box-shadow: 0 0 0 1px rgba(12,38,32,.035), 0 30px 70px -32px rgba(12,38,32,.32), 0 0 40px -12px rgba(255,255,255,.9); }
        .am-bar { height: 32px; display: flex; align-items: center; gap: 8px; padding: 0 12px; border-bottom: 1px solid #eef1f0; background: #fbfcfc; }
        .am-dots { display: flex; gap: 5px; }
        .am-dots i { width: 8px; height: 8px; border-radius: 50%; background: #e3e8e6; }
        .am-dots i:nth-child(1) { background: #f0506e; } .am-dots i:nth-child(2) { background: #f5a524; } .am-dots i:nth-child(3) { background: #22a75a; }
        .am-url { flex: 1; min-width: 0; height: 20px; display: flex; align-items: center; gap: 5px; padding: 0 9px; border-radius: 999px; background: #f4f6f5; font-size: 10px; color: #6b7c76; white-space: nowrap; overflow: hidden; }
        .am-url svg { width: 10px; height: 10px; flex: none; color: #0d9488; }
        .am-body { padding: 18px 20px 20px; }
        .am-logo { width: 34px; height: 34px; margin: 0 auto 8px; border-radius: 10px; display: grid; place-items: center; background: #fff; border: 1px solid #e5eae8; box-shadow: 0 6px 14px -8px rgba(12,38,32,.3); }
        .am-logo img { width: 22px; height: 22px; object-fit: contain; }
        .am-h { height: 18px; line-height: 18px; margin-bottom: 14px; text-align: center; font-family: 'Plus Jakarta Sans', 'Geist', system-ui, sans-serif; font-size: 13.5px; font-weight: 800; letter-spacing: -.03em; color: #0c2620; white-space: nowrap; }
        .am-h.has-sub { margin-bottom: 4px; }
        .am-sub { height: 14px; line-height: 14px; margin-bottom: 14px; text-align: center; font-size: 10.5px; color: #6b7c76; white-space: nowrap; }
        .am-f { position: relative; height: 38px; display: flex; align-items: center; padding: 0 14px; border: 1.5px solid #d6dedb; border-radius: 999px; background: #fff; }
        .am-f + .am-f { margin-top: 10px; }
        .am-f label { position: absolute; top: 0; left: 12px; transform: translateY(-50%); padding: 0 4px; background: #fff; font-size: 9px; font-weight: 600; line-height: 1; color: #6b7c76; }
        .am-ph { position: absolute; left: 14px; font-size: 11.5px; color: #a3b0ab; white-space: nowrap; }
        .am-typed { display: inline-flex; align-items: center; white-space: pre; vertical-align: middle; font-size: 11.5px; line-height: 14px; font-weight: 500; color: #0d9488; }
        .am-typed i { display: inline-block; max-width: 0; overflow: hidden; font-style: normal; }
        .am-caret { display: inline-block; width: 1.5px; height: 14px; margin-left: 1px; vertical-align: middle; background: #0d9488; opacity: 0; }
        .am-caret::after { content: ''; display: block; width: 100%; height: 100%; background: #fff; animation: am-blink 1s steps(2, start) infinite; }
        @keyframes am-blink { to { visibility: hidden; } }
        .am-boxes { display: flex; justify-content: center; gap: 7px; }
        .am-box { width: 32px; height: 38px; border-radius: 10px; border: 1.5px solid #d6dedb; background: #fff; display: grid; place-items: center; font-size: 15px; font-weight: 600; color: #0d9488; }
        .am-box b { opacity: 0; }
        .am-meter { display: flex; gap: 4px; height: 4px; margin-top: 10px; }
        .am-meter i { flex: 1; border-radius: 99px; background: #eceff0; }
        .am-btn { height: 36px; margin-top: 14px; border-radius: 999px; display: grid; place-items: center; background: #0d9488; color: #fff; font-size: 12.5px; font-weight: 500; }
        .am-otp .am-btn { margin-top: 16px; }
        .am-toast { position: absolute; z-index: 2; top: 40px; right: -30px; display: flex; align-items: center; gap: 8px; padding: 8px 14px 8px 8px; border-radius: 14px; background: #fff; box-shadow: 0 14px 30px -14px rgba(12,38,32,.38), 0 1px 3px rgba(12,38,32,.06); opacity: 0; }
        .am-tick { width: 28px; height: 28px; flex: none; border-radius: 9px; display: grid; place-items: center; background: #effaf7; color: #0d9488; }
        .am-tick svg { width: 15px; height: 15px; }
        .am-toast b { display: block; font-size: 11.5px; line-height: 1.25; font-weight: 600; color: #0c2620; white-space: nowrap; }
        .am-toast small { display: block; font-size: 10px; line-height: 1.25; color: #6b7c76; white-space: nowrap; }
        .am-cursor { position: absolute; left: 0; top: 0; z-index: 3; width: 18px; height: 20px; pointer-events: none; opacity: 0; }
        .am-cursor svg { position: relative; z-index: 1; width: 18px; height: 20px; filter: drop-shadow(0 2px 3px rgba(12,38,32,.3)); }
        .am-ripple { position: absolute; left: -9px; top: -9px; width: 20px; height: 20px; border-radius: 50%; background: rgba(13,148,136,.5); opacity: 0; }

        {!! implode("\n        ", $kf) !!}
        .{{ $v }} .am-cursor { animation: {{ $v }}-cur 7s ease-in-out infinite; }
        .{{ $v }} .am-ripple { animation: {{ $v }}-rip 7s linear infinite; }
        @foreach ($spec['fields'] ?? [] as $i => $f)
        .{{ $v }} .am-f{{ $i }} { animation: {{ $v }}-f{{ $i }} 7s linear infinite; }
        @for ($j = 0; $j < mb_strlen($f[2]); $j++)
        .{{ $v }} .am-f{{ $i }} .am-typed i:nth-child({{ $j + 1 }}) { animation: {{ $v }}-t{{ $i }}-{{ $j }} 7s linear infinite; }
        @endfor
        .{{ $v }} .am-f{{ $i }} .am-ph { animation: {{ $v }}-p{{ $i }} 7s linear infinite; }
        .{{ $v }} .am-f{{ $i }} .am-caret { animation: {{ $v }}-c{{ $i }} 7s linear infinite; }
        @endforeach
        @foreach ($spec['digitAt'] ?? [] as $i => $at)
        .{{ $v }} .am-box{{ $i }} { animation: {{ $v }}-b{{ $i }} 7s linear infinite; }
        .{{ $v }} .am-box{{ $i }} b { animation: {{ $v }}-d{{ $i }} 7s ease-out infinite; }
        @endforeach
        @foreach ($spec['meter'] ?? [] as $i => $at)
        .{{ $v }} .am-m{{ $i }} { animation: {{ $v }}-m{{ $i }} 7s linear infinite; }
        @endforeach
        .{{ $v }} .am-btn { animation: {{ $v }}-btn 7s ease-out infinite; }
        .{{ $v }} .am-toast { animation: {{ $v }}-toast 7s cubic-bezier(.2,.7,.2,1) infinite; }

        @media (max-width: 1080px) { .am { zoom: 1; } }
        @media (max-width: 920px) { .am { zoom: .9; } .am-toast { right: -14px; } }
        @media (prefers-reduced-motion: reduce) {
            .am, .am * { animation: none !important; }
            .am-cursor, .am-ph, .am-caret { display: none; }
            .am-typed i { max-width: none; }
            .am-box b, .am-toast { opacity: 1; }
            .am-f, .am-box { border-color: #0d9488; }
        }
    </style>

    <div class="am {{ $v }}" aria-hidden="true">
        <div class="am-win">
            <div class="am-bar">
                <span class="am-dots"><i></i><i></i><i></i></span>
                <span class="am-url"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>{{ $host }}/{{ $spec['path'] }}</span>
            </div>
            <div class="am-body">
                <div class="am-logo"><img src="{{ \App\Support\Platform::logoUrl() }}" alt=""></div>
                <div class="am-h {{ $spec['sub'] ? 'has-sub' : '' }}">{{ $spec['title'] }}</div>
                @if ($spec['sub'])<div class="am-sub">{{ $spec['sub'] }}</div>@endif

                @if ($variant === 'otp')
                    <div class="am-boxes">
                        @foreach ($spec['digits'] as $i => $digit)
                            <span class="am-box am-box{{ $i }}"><b>{{ $digit }}</b></span>
                        @endforeach
                    </div>
                @else
                    @foreach ($spec['fields'] as $i => [$label, $ph, $text])
                        <div class="am-f am-f{{ $i }}">
                            <label>{{ $label }}</label>
                            <span class="am-ph">{{ $ph }}</span>
                            <span class="am-typed">@foreach (mb_str_split($text) as $ch)<i>{{ $ch }}</i>@endforeach</span><span class="am-caret"></span>
                        </div>
                    @endforeach
                    @if (!empty($spec['meter']))
                        <div class="am-meter">@foreach ($spec['meter'] as $i => $at)<i class="am-m{{ $i }}"></i>@endforeach</div>
                    @endif
                @endif

                <div class="am-btn">{{ $btnLabel }}</div>
            </div>
        </div>

        <div class="am-toast">
            <span class="am-tick"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span>
            <span><b>{{ $toastTitle }}</b><small>{{ $toastSub }}</small></span>
        </div>

        <div class="am-cursor">
            <span class="am-ripple"></span>
            <svg viewBox="0 0 18 20"><path d="M1.5 1.5v14.2l3.9-3.7 2.6 6 2.6-1.1-2.6-5.9h5.4z" fill="#0c2620" stroke="#fff" stroke-width="1.2" stroke-linejoin="round"/></svg>
        </div>
    </div>

    <h2 class="mt-8 text-lg font-semibold text-gray-800 sa-title">{{ $caption[0] }}</h2>
    <p class="mt-1 text-sm text-gray-500 sa-sub">{{ $subtitle }}</p>
</div>
