{{--
    Dynamic auth illustration for the login / OTP / reset-password screens.
    Pass a $variant: 'login' | 'otp' | 'reset' | 'newpass'. Each renders a clean,
    minimal inline-SVG scene tied to that step (no static image asset).

    Pass an optional $scheme ('violet' | 'emerald') to recolour the art. Admin
    uses the default violet/pink brand; the accounts panel passes 'emerald' so
    the illustration matches its green theme while keeping the same design.
--}}
@php($variant = $variant ?? 'login')
@php($scheme = $scheme ?? 'violet')
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

<div class="relative z-10 flex flex-col items-center text-center w-4/5 max-w-xs">
    @switch($variant)
        @case('otp')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <rect x="96" y="96" width="128" height="90" rx="16" fill="#FFFFFF" stroke="{{ $c['stroke'] }}" stroke-width="2"/>
                <path d="M104 106 L160 144 L216 106" fill="none" stroke="{{ $c['primaryLight'] }}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <rect x="112" y="150" width="96" height="36" rx="11" fill="{{ $c['primary'] }}"/>
                <rect x="126" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                <rect x="148" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                <rect x="170" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                <rect x="192" y="161" width="12" height="14" rx="3" fill="#FFFFFF"/>
                <circle cx="214" cy="104" r="18" fill="{{ $c['accent'] }}"/>
                <rect x="208" y="105" width="12" height="9" rx="2" fill="#FFFFFF"/>
                <path d="M210 105 v-3 a4 4 0 0 1 8 0 v3" fill="none" stroke="#FFFFFF" stroke-width="2"/>
                <circle cx="84" cy="178" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle cx="238" cy="156" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
            @break

        @case('reset')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <path d="M138 132 v-14 a22 22 0 0 1 44 0 v14" fill="none" stroke="{{ $c['primary'] }}" stroke-width="11" stroke-linecap="round"/>
                <rect x="122" y="130" width="76" height="66" rx="16" fill="{{ $c['primary'] }}"/>
                <circle cx="160" cy="156" r="8" fill="#FFFFFF"/>
                <rect x="156" y="160" width="8" height="16" rx="4" fill="#FFFFFF"/>
                <circle cx="210" cy="96" r="18" fill="{{ $c['accent'] }}"/>
                <path d="M204 92 a8 8 0 1 1 -1.5 9" fill="none" stroke="#FFFFFF" stroke-width="2.6" stroke-linecap="round"/>
                <path d="M204 92 l-1.5 -5 5 1 z" fill="#FFFFFF"/>
                <circle cx="86" cy="180" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle cx="236" cy="166" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
            @break

        @case('newpass')
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <path d="M138 132 v-14 a22 22 0 0 1 44 0 v14" fill="none" stroke="{{ $c['primary'] }}" stroke-width="11" stroke-linecap="round"/>
                <rect x="122" y="130" width="76" height="66" rx="16" fill="{{ $c['primary'] }}"/>
                <circle cx="160" cy="156" r="8" fill="#FFFFFF"/>
                <rect x="156" y="160" width="8" height="16" rx="4" fill="#FFFFFF"/>
                <circle cx="210" cy="96" r="18" fill="#22C55E"/>
                <path d="M203 96 l4.5 4.5 L218 89" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="86" cy="180" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle cx="236" cy="166" r="4" fill="#86EFAC"/>
            </svg>
            @break

        @default
            {{-- login --}}
            <svg viewBox="0 0 320 280" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-auto">
                <circle cx="160" cy="142" r="112" fill="{{ $c['bg1'] }}"/>
                <circle cx="160" cy="150" r="84" fill="{{ $c['bg2'] }}"/>
                <rect x="96" y="68" width="128" height="152" rx="20" fill="#FFFFFF" stroke="{{ $c['stroke'] }}" stroke-width="2"/>
                <circle cx="160" cy="104" r="22" fill="{{ $c['bg1'] }}"/>
                <circle cx="160" cy="99" r="7.5" fill="{{ $c['primary'] }}"/>
                <path d="M147 117 a13 13 0 0 1 26 0 Z" fill="{{ $c['primary'] }}"/>
                <rect x="116" y="142" width="88" height="13" rx="6.5" fill="{{ $c['line'] }}"/>
                <rect x="116" y="165" width="88" height="13" rx="6.5" fill="{{ $c['line'] }}"/>
                <rect x="116" y="192" width="88" height="17" rx="8.5" fill="{{ $c['primary'] }}"/>
                <circle cx="214" cy="82" r="18" fill="{{ $c['accent'] }}"/>
                <path d="M207 82 l4.5 4.5 L222 75" fill="none" stroke="#FFFFFF" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="84" cy="176" r="5" fill="{{ $c['primaryDot'] }}"/>
                <circle cx="236" cy="166" r="4" fill="{{ $c['accentDot'] }}"/>
            </svg>
    @endswitch

    <h2 class="mt-6 text-lg font-semibold text-gray-800">{{ $caption[0] }}</h2>
    <p class="mt-1 text-sm text-gray-500">{{ $caption[1] }}</p>
</div>
