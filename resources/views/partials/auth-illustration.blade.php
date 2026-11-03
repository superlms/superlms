{{--
    Dynamic auth illustration for the login / OTP / reset-password screens.
    Pass a $variant: 'login' | 'otp' | 'reset' | 'newpass'. Each renders a clean,
    minimal inline-SVG scene tied to that step (no static image asset).

    Pass an optional $scheme ('violet' | 'emerald') to recolour the art. Admin
    uses the default violet/pink brand; the accounts panel passes 'emerald' so
    the illustration matches its green theme while keeping the same design.

    Pass $animated = true (the Super Admin login, forgot password and app
    screens) and each scene acts out its own step, on a loop:
      login   — the email is typed, the password dots appear, the button is
                pressed and the check pops in;
      otp     — the envelope floats in, its flap draws, the code's digits fill
                in one by one and the lock badge pulses;
      reset   — the padlock wiggles and its shackle lifts open, the refresh
                badge turns;
      newpass — the shackle snaps shut, the check pops in and a strength meter
                fills up to strong.
    Changing step brings the new scene in fresh. Nothing moves for people who
    ask their system for reduced motion.
--}}
@php($variant = $variant ?? 'login')
@php($scheme = $scheme ?? 'violet')
@php($animated = (bool) ($animated ?? false))
@php($schemes = [
    'violet' => [
        'bg1' => '#F5F3FF', 'bg2' => '#EDE9FE', 'stroke' => '#E9D5FF',
        'primary' => '#7C3AED', 'primaryLight' => '#C4B5FD', 'primaryDot' => '#C4B5FD',
        'accent' => '#EC4899', 'accentDot' => '#F9A8D4', 'line' => '#F1F5F9',
    ],
    'emerald' => [
        'bg1' => '#ECFDF5', 'bg2' => '#D1FAE5', 'stroke' => '#A7F3D0',
        'primary' => '#059669', 'primaryLight' => '#6EE7B7', 'primaryDot' => '#6EE7B7',
        'accent' => '#0D9488', 'accentDot' => '#5EEAD4', 'line' => '#F1F5F9',
    ],
])
@php($c = $schemes[$scheme] ?? $schemes['violet'])
@php($caption = [
    'login'   => ['Welcome back', 'Sign in to manage your school'],
    'otp'     => ['Check your inbox', 'We sent you a one-time code'],
    'reset'   => ['Forgot password?', "No worries — let's reset it"],
    'newpass' => ['Almost done', 'Choose a strong new password'],
][$variant])
{{-- Optional per-panel subtitle override (e.g. super-admin uses "Admin panel"). --}}
@php($subtitle = $subtitle ?? $caption[1])
{{-- A class only while animated, so the still art stays exactly as it was. --}}
@php($a = fn (string $classes) => $animated ? $classes : '')

<div wire:key="auth-art-{{ $variant }}" class="relative z-10 flex flex-col items-center text-center w-4/5 max-w-xs {{ $a('ai-on') }}">
    @if ($animated)
        <style>
            .ai-on { animation: ai-enter .55s cubic-bezier(.2,.8,.2,1) both; }
            .ai-on > h2 { animation: ai-rise .5s .12s cubic-bezier(.2,.8,.2,1) both; }
            .ai-on > p  { animation: ai-rise .5s .2s cubic-bezier(.2,.8,.2,1) both; }

            /* Scale / turn about the shape's own centre (or left / bottom edge). */
            .ai-on .ai-c { transform-box: fill-box; transform-origin: 50% 50%; }
            .ai-on .ai-l { transform-box: fill-box; transform-origin: 0% 50%; }
            .ai-on .ai-b { transform-box: fill-box; transform-origin: 50% 100%; }

            /* Shared */
            .ai-on .ai-halo    { animation: ai-halo 5s ease-in-out infinite; }
            .ai-on .ai-float   { animation: ai-float 6s ease-in-out infinite; }
            .ai-on .ai-drift-a { animation: ai-drift 5s ease-in-out infinite; }
            .ai-on .ai-drift-b { animation: ai-drift 6s ease-in-out -2.5s infinite reverse; }
            .ai-on .ai-pulse   { animation: ai-pulse 2.2s ease-in-out infinite; }
            .ai-on .ai-ring    { animation: ai-ring 2.2s ease-out infinite; }

            /* Login — a 5 s sign-in: type, dots, press, check */
            .ai-on .ai-l-type  { animation: ai-l-type 5s ease-out infinite; }
            @for ($i = 0; $i < 5; $i++)
                .ai-on .ai-l-pw{{ $i }} { animation: ai-l-pw{{ $i }} 5s ease-out infinite; }
            @endfor
            .ai-on .ai-l-btn   { animation: ai-l-btn 5s ease-in-out infinite; }
            .ai-on .ai-l-check { animation: ai-l-check 5s ease-out infinite; }
            .ai-on .ai-l-ring  { animation: ai-l-ring 5s ease-out infinite; }

            /* OTP — the flap draws once, then the digits fill in on a loop */
            .ai-on .ai-o-flap  { stroke-dasharray: 1; animation: ai-draw 1.1s ease-out .25s both; }
            @for ($i = 0; $i < 4; $i++)
                .ai-on .ai-o-d{{ $i }} { animation: ai-o-d{{ $i }} 4.5s ease-out infinite; }
            @endfor
            .ai-on .ai-o-bar   { animation: ai-o-bar 4.5s ease-in-out infinite; }

            /* Reset — wiggle, shackle up, turning refresh arrow */
            .ai-on .ai-r-lock    { animation: ai-r-wiggle 3.6s ease-in-out infinite; }
            .ai-on .ai-r-shackle { animation: ai-r-shackle 3.6s ease-in-out infinite; }
            .ai-on .ai-r-spin    { transform-box: view-box; transform-origin: 210px 96px; animation: ai-spin 2.4s linear infinite; }

            /* New password — snap shut, check, strength meter */
            .ai-on .ai-n-shackle { animation: ai-n-shackle 4s ease-in-out infinite; }
            .ai-on .ai-n-body    { animation: ai-n-body 4s ease-out infinite; }
            .ai-on .ai-n-check   { animation: ai-n-check 4s ease-out infinite; }
            .ai-on .ai-n-tick    { stroke-dasharray: 1; animation: ai-n-tick 4s ease-out infinite; }
            .ai-on .ai-n-ring    { animation: ai-n-ring 4s ease-out infinite; }
            @for ($i = 0; $i < 3; $i++)
                .ai-on .ai-n-bar{{ $i }} { animation: ai-n-bar{{ $i }} 4s ease-out infinite; }
            @endfor

            @keyframes ai-enter { from { opacity: 0; transform: translateY(10px) scale(.97); } to { opacity: 1; transform: none; } }
            @keyframes ai-rise  { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
            @keyframes ai-halo  { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.045); } }
            @keyframes ai-float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-6px); } }
            @keyframes ai-drift { 0%, 100% { transform: translate(0, 0); } 50% { transform: translate(5px, -9px); } }
            @keyframes ai-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }
            @keyframes ai-ring  { 0% { opacity: .55; transform: scale(1); } 100% { opacity: 0; transform: scale(1.8); } }
            @keyframes ai-spin  { to { transform: rotate(360deg); } }
            @keyframes ai-draw  { from { stroke-dashoffset: 1; } to { stroke-dashoffset: 0; } }

            @keyframes ai-l-type { 0%, 6% { opacity: 1; transform: scaleX(0); } 22%, 90% { opacity: 1; transform: scaleX(1); } 97%, 100% { opacity: 0; transform: scaleX(1); } }
            @for ($i = 0; $i < 5; $i++)
                @keyframes ai-l-pw{{ $i }} { 0%, {{ 26 + 4 * $i }}% { opacity: 0; transform: scale(.2); } {{ 29 + 4 * $i }}%, 90% { opacity: 1; transform: scale(1); } 97%, 100% { opacity: 0; transform: scale(1); } }
            @endfor
            @keyframes ai-l-btn   { 0%, 50% { transform: scale(1); } 54% { transform: scale(.92); } 59%, 100% { transform: scale(1); } }
            @keyframes ai-l-check { 0%, 56% { opacity: 0; transform: scale(0); } 63% { opacity: 1; transform: scale(1.18); } 68%, 90% { opacity: 1; transform: scale(1); } 97%, 100% { opacity: 0; transform: scale(.6); } }
            @keyframes ai-l-ring  { 0%, 60% { opacity: 0; transform: scale(1); } 62% { opacity: .55; transform: scale(1); } 80%, 100% { opacity: 0; transform: scale(1.8); } }

            @for ($i = 0; $i < 4; $i++)
                @keyframes ai-o-d{{ $i }} { 0%, {{ 14 + 10 * $i }}% { opacity: 0; transform: scale(.3); } {{ 18 + 10 * $i }}%, 86% { opacity: 1; transform: scale(1); } 94%, 100% { opacity: 0; transform: scale(1); } }
            @endfor
            @keyframes ai-o-bar { 0%, 50% { transform: scale(1); } 55% { transform: scale(1.05); } 61%, 100% { transform: scale(1); } }

            @keyframes ai-r-wiggle  { 0%, 12% { transform: rotate(0); } 16% { transform: rotate(-5deg); } 20% { transform: rotate(5deg); } 24% { transform: rotate(-3deg); } 28%, 100% { transform: rotate(0); } }
            @keyframes ai-r-shackle { 0%, 30% { transform: translateY(0); } 42%, 72% { transform: translateY(-10px); } 86%, 100% { transform: translateY(0); } }

            @keyframes ai-n-shackle { 0%, 14% { transform: translateY(-10px); } 24% { transform: translateY(1px); } 28%, 88% { transform: translateY(0); } 100% { transform: translateY(-10px); } }
            @keyframes ai-n-body    { 0%, 23% { transform: translateY(0); } 27% { transform: translateY(2.5px); } 33%, 100% { transform: translateY(0); } }
            @keyframes ai-n-check   { 0%, 30% { opacity: 0; transform: scale(0); } 37% { opacity: 1; transform: scale(1.18); } 42%, 88% { opacity: 1; transform: scale(1); } 95%, 100% { opacity: 0; transform: scale(.6); } }
            @keyframes ai-n-tick    { 0%, 36% { stroke-dashoffset: 1; } 46%, 100% { stroke-dashoffset: 0; } }
            @keyframes ai-n-ring    { 0%, 36% { opacity: 0; transform: scale(1); } 38% { opacity: .55; transform: scale(1); } 56%, 100% { opacity: 0; transform: scale(1.8); } }
            @for ($i = 0; $i < 3; $i++)
                @keyframes ai-n-bar{{ $i }} { 0%, {{ 44 + 9 * $i }}% { opacity: 1; transform: scaleX(0); } {{ 51 + 9 * $i }}%, 88% { opacity: 1; transform: scaleX(1); } 95%, 100% { opacity: 0; transform: scaleX(1); } }
            @endfor

            @media (prefers-reduced-motion: reduce) {
                .ai-on, .ai-on * { animation: none !important; }
            }
        </style>
    @endif

    @switch($variant)
        @case('otp')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle class="{{ $a('ai-c ai-halo') }}" cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <g class="{{ $a('ai-float') }}">
                    <rect x="96" y="96" width="128" height="90" rx="16" fill="#FFFFFF" stroke="{{ $c['stroke'] }}" stroke-width="2"/>
                    <path class="{{ $a('ai-o-flap') }}" pathLength="1" d="M104 106 L160 144 L216 106" fill="none" stroke="{{ $c['primaryLight'] }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    <g class="{{ $a('ai-c ai-o-bar') }}">
                        <rect x="112" y="150" width="96" height="36" rx="11" fill="{{ $c['primary'] }}"/>
                        <rect x="126" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                        <rect x="148" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                        <rect x="170" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                        <rect x="192" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                        @if ($animated)
                            {{-- The code's digits, entered one by one --}}
                            @foreach ([132, 154, 176, 198] as $i => $x)
                                <circle class="ai-c ai-o-d{{ $i }}" cx="{{ $x }}" cy="168" r="3" fill="{{ $c['primary'] }}"/>
                            @endforeach
                        @endif
                    </g>
                    @if ($animated)
                        <circle class="ai-c ai-ring" cx="214" cy="104" r="18" fill="none" stroke="{{ $c['accent'] }}" stroke-width="2" opacity="0"/>
                    @endif
                    <g class="{{ $a('ai-c ai-pulse') }}">
                        <circle cx="214" cy="104" r="18" fill="{{ $c['accent'] }}"/>
                        <rect x="208" y="105" width="12" height="9" rx="2" fill="#FFFFFF"/>
                        <path d="M210 105 v-3 a4 4 0 0 1 8 0 v3" fill="none" stroke="#FFFFFF" stroke-width="2"/>
                    </g>
                </g>
                <circle class="{{ $a('ai-drift-a') }}" cx="84" cy="178" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle class="{{ $a('ai-drift-b') }}" cx="238" cy="156" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
            @break

        @case('reset')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle class="{{ $a('ai-c ai-halo') }}" cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <g class="{{ $a('ai-b ai-r-lock') }}">
                    <path class="{{ $a('ai-r-shackle') }}" d="M138 132 v-14 a22 22 0 0 1 44 0 v14" fill="none" stroke="{{ $c['primary'] }}" stroke-width="11" stroke-linecap="round"/>
                    <rect x="122" y="130" width="76" height="66" rx="16" fill="{{ $c['primary'] }}"/>
                    <circle cx="160" cy="156" r="8" fill="#FFFFFF"/>
                    <rect x="156" y="160" width="8" height="16" rx="4" fill="#FFFFFF"/>
                </g>
                <g class="{{ $a('ai-float') }}">
                    <circle cx="210" cy="96" r="18" fill="{{ $c['accent'] }}"/>
                    <g class="{{ $a('ai-r-spin') }}">
                        <path d="M204 92 a8 8 0 1 1 -1.5 9" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round"/>
                        <path d="M204 92 l-1.5 -5 5 1 z" fill="#FFFFFF"/>
                    </g>
                </g>
                <circle class="{{ $a('ai-drift-a') }}" cx="86" cy="180" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle class="{{ $a('ai-drift-b') }}" cx="236" cy="166" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
            @break

        @case('newpass')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle class="{{ $a('ai-c ai-halo') }}" cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <path class="{{ $a('ai-n-shackle') }}" d="M138 132 v-14 a22 22 0 0 1 44 0 v14" fill="none" stroke="{{ $c['primary'] }}" stroke-width="11" stroke-linecap="round"/>
                <g class="{{ $a('ai-n-body') }}">
                    <rect x="122" y="130" width="76" height="66" rx="16" fill="{{ $c['primary'] }}"/>
                    <circle cx="160" cy="156" r="8" fill="#FFFFFF"/>
                    <rect x="156" y="160" width="8" height="16" rx="4" fill="#FFFFFF"/>
                </g>
                @if ($animated)
                    {{-- Strength meter: weak, good, strong --}}
                    @foreach ([[126, '#FBBF24'], [149, '#A3E635'], [172, '#22C55E']] as $i => [$x, $fill])
                        <rect x="{{ $x }}" y="212" width="22" height="6" rx="3" fill="#FFFFFF"/>
                        <rect class="ai-l ai-n-bar{{ $i }}" x="{{ $x }}" y="212" width="22" height="6" rx="3" fill="{{ $fill }}"/>
                    @endforeach
                    <circle class="ai-c ai-n-ring" cx="210" cy="96" r="18" fill="none" stroke="#22C55E" stroke-width="2" opacity="0"/>
                @endif
                <g class="{{ $a('ai-c ai-n-check') }}">
                    <circle cx="210" cy="96" r="18" fill="#22C55E"/>
                    <path class="{{ $a('ai-n-tick') }}" pathLength="1" d="M203 96 l4.5 4.5 L218 89" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </g>
                <circle class="{{ $a('ai-drift-a') }}" cx="86" cy="180" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle class="{{ $a('ai-drift-b') }}" cx="236" cy="166" r="4" fill="#86EFAC"/>
            </svg>
            @break

        @default
            {{-- login --}}
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle class="{{ $a('ai-c ai-halo') }}" cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <g class="{{ $a('ai-float') }}">
                    <rect x="96" y="68" width="128" height="152" rx="20" fill="#FFFFFF" stroke="{{ $c['stroke'] }}" stroke-width="2"/>
                    <circle cx="160" cy="104" r="22" fill="{{ $c['bg1'] }}"/>
                    <circle cx="160" cy="99" r="7.5" fill="{{ $c['primary'] }}"/>
                    <path d="M147 117 a13 13 0 0 1 26 0 Z" fill="{{ $c['primary'] }}"/>
                    <rect x="116" y="142" width="88" height="13" rx="6.5" fill="{{ $c['line'] }}"/>
                    <rect x="116" y="165" width="88" height="13" rx="6.5" fill="{{ $c['line'] }}"/>
                    @if ($animated)
                        {{-- The email being typed, then the password's dots --}}
                        <rect class="ai-l ai-l-type" x="123" y="146.5" width="48" height="4" rx="2" fill="{{ $c['primaryLight'] }}"/>
                        @for ($i = 0; $i < 5; $i++)
                            <circle class="ai-c ai-l-pw{{ $i }}" cx="{{ 125 + 8 * $i }}" cy="171.5" r="2.2" fill="{{ $c['primary'] }}"/>
                        @endfor
                    @endif
                    <rect class="{{ $a('ai-c ai-l-btn') }}" x="116" y="192" width="88" height="17" rx="8.5" fill="{{ $c['primary'] }}"/>
                    @if ($animated)
                        <circle class="ai-c ai-l-ring" cx="214" cy="82" r="18" fill="none" stroke="{{ $c['accent'] }}" stroke-width="2" opacity="0"/>
                    @endif
                    <g class="{{ $a('ai-c ai-l-check') }}">
                        <circle cx="214" cy="82" r="18" fill="{{ $c['accent'] }}"/>
                        <path d="M207 82 l4.5 4.5 L222 75" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </g>
                </g>
                <circle class="{{ $a('ai-drift-a') }}" cx="84" cy="176" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle class="{{ $a('ai-drift-b') }}" cx="236" cy="166" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
    @endswitch

    <h2 class="mt-6 text-lg font-semibold text-gray-800">{{ $caption[0] }}</h2>
    <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
</div>
