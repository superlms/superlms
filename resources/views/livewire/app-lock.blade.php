@php
    // The accounts desk keeps its green; the other panels the brand violet.
    $green = $panel === 'accounts';
    $c = $green
        ? [
            'side'   => 'from-emerald-50 to-teal-50',
            'text'   => 'text-emerald-600',
            'link'   => 'text-emerald-600 hover:text-emerald-800',
            'button' => 'bg-emerald-600 hover:bg-emerald-700',
            'focus'  => 'focus:ring-emerald-500 focus:border-emerald-500',
            'spin'   => 'border-emerald-200 border-t-emerald-600',
        ]
        : [
            'side'   => 'from-violet-50 to-fuchsia-50',
            'text'   => 'text-violet-600',
            'link'   => 'text-violet-600 hover:text-violet-800',
            'button' => 'bg-violet-600 hover:bg-violet-700',
            'focus'  => 'focus:ring-violet-500 focus:border-violet-500',
            'spin'   => 'border-violet-200 border-t-violet-600',
        ];
@endphp

<div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6">
    {{-- This page waits for a code; the layout's background refresh (a silent
         GET of the page every few seconds) has nothing to show here, and a
         request of its in flight while the code is checked could write the
         session back as it was before the sign-in. Claiming its flag before it
         starts keeps it off. --}}
    <script>
        window.__superlmsAutoRefresh = window.__superlmsAutoRefresh || true;

        // Is the panel open in this browser — in another window or tab, or in
        // this very window a moment ago? Every open page of a panel holds a
        // lock named after it (partials/panel-app-open); the browser lets go of
        // it the instant the page is closed, so no page holding it means the
        // app was closed.
        window.lmsPanelOpen = window.lmsPanelOpen || function (name, done) {
            var answered = false;
            function answer(running) {
                if (answered) return;
                answered = true;
                done(!!running);
            }

            try {
                if (window.sessionStorage.getItem(name) === '1') return answer(true);
            } catch (e) {}

            if (!navigator.locks || !navigator.locks.query) return answer(false);

            navigator.locks.query().then(function (state) {
                answer((state.held || []).some(function (lock) { return lock.name === name; }));
            }, function () { answer(false); });

            setTimeout(function () { answer(false); }, 3000);
        };
    </script>

    <div class="flex flex-col md:flex-row w-full max-w-5xl md:h-[600px] rounded-3xl overflow-hidden bg-white border border-slate-100 shadow-xl shadow-slate-200/60">

        <!-- Left Side: illustration -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br {{ $c['side'] }} flex-col items-center justify-center p-10">
            @include('partials.auth-illustration', ['variant' => 'otp', 'scheme' => $green ? 'emerald' : 'violet'])
        </div>

        <!-- Right Side -->
        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-8 md:p-12">

            <!-- Logo -->
            <div class="mb-6 w-16 h-16 sm:w-20 sm:h-20">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-full h-full object-contain">
            </div>

            {{-- ── Asking the browser whether the panel is still open ── --}}
            @if ($step === 'checking')
                <div class="text-center" wire:key="app-lock-checking"
                    x-data x-init="window.lmsPanelOpen(@js($openName), running => $wire.opened(running))">
                    <div class="mx-auto mb-5 h-9 w-9 rounded-full border-4 {{ $c['spin'] }} animate-spin"></div>
                    <h1 class="text-xl font-bold text-gray-800">Opening SuperLMS…</h1>
                    <p class="text-gray-500 mt-1 text-sm">Just a moment</p>
                </div>
            @endif

            {{-- ── The code ── --}}
            @if ($step === 'otp')
                <div class="text-center mb-6" wire:key="app-lock-otp-title"
                    x-data
                    x-on:focus.window="$wire.recheck()"
                    x-on:visibilitychange.document="document.hidden || $wire.recheck()">
                    <h1 class="text-2xl font-bold text-gray-800">Verify OTP</h1>
                    <p class="text-gray-500 mt-1 text-sm">Enter the 6-digit code sent to your email</p>
                    <p class="{{ $c['text'] }} text-sm font-medium mt-1">{{ $otpSentTo }}</p>
                </div>

                <div class="w-full max-w-sm" wire:key="app-lock-otp">
                    {{-- 6-box OTP input --}}
                    <div x-data="{
                            otp: @entangle('otp'),
                            submitting: false,
                            allFilled() {
                                return this.otp.every(d => String(d ?? '').trim().length === 1);
                            },
                            focusNext(index) {
                                if (this.otp[index] && index < 5) {
                                    this.$refs['otp' + (index + 1)].focus();
                                }
                                // Auto-verify once every box is filled (single fire).
                                if (this.allFilled() && !this.submitting) {
                                    this.submitting = true;
                                    this.$wire.verifyOtp();
                                }
                            },
                            focusPrev(index, event) {
                                if (event.key === 'Backspace' && !this.otp[index] && index > 0) {
                                    this.$refs['otp' + (index - 1)].focus();
                                }
                            }
                        }"
                        x-init="$watch('otp', () => { if (!allFilled()) submitting = false; }); $nextTick(() => $refs.otp0.focus())"
                        class="flex justify-center gap-2 sm:gap-3 mb-4">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" maxlength="1"
                                x-ref="otp{{ $i }}"
                                x-model="otp[{{ $i }}]"
                                x-on:input="focusNext({{ $i }})"
                                x-on:keydown="focusPrev({{ $i }}, $event)"
                                inputmode="numeric"
                                class="w-10 h-12 sm:w-11 sm:h-14 text-center text-lg sm:text-xl font-bold border-2 border-slate-200 rounded-xl
                                       focus:outline-none focus:ring-2 {{ $c['focus'] }} transition-all"
                                @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    @error('otp')
                        <p class="text-red-500 text-xs text-center mb-3">{{ $message }}</p>
                    @enderror

                    <x-otp-lockout :until="$otpLockedUntil" />

                    <button wire:click="verifyOtp" wire:loading.attr="disabled"
                        class="w-full py-3 {{ $c['button'] }} text-white font-medium rounded-xl transition duration-200 disabled:opacity-60 mb-4">
                        <span wire:loading.remove wire:target="verifyOtp">Verify &amp; Open</span>
                        <span wire:loading wire:target="verifyOtp">Verifying…</span>
                    </button>

                    {{-- Resend, once the server's cooldown is over --}}
                    <div class="text-center" x-data="{
                            until: @entangle('resendAvailableAt'),
                            now: Math.floor(Date.now() / 1000),
                            sync() { this.now = Math.floor(Date.now() / 1000); },
                            get remaining() { return Math.max(0, (this.until || 0) - this.now); },
                            get canResend() { return this.remaining <= 0; },
                            get label() {
                                const m = Math.floor(this.remaining / 60);
                                const s = this.remaining % 60;
                                return m + ':' + String(s).padStart(2, '0');
                            }
                        }" x-init="
                            sync();
                            setInterval(() => sync(), 500);
                            $watch('until', () => sync());
                        ">
                        <template x-if="!canResend">
                            <p class="text-sm text-gray-500">
                                Resend OTP in
                                <span class="font-semibold {{ $c['text'] }}" x-text="label"></span>
                            </p>
                        </template>
                        <template x-if="canResend">
                            <button wire:click="resendOtp" wire:loading.attr="disabled" wire:target="resendOtp"
                                class="text-sm {{ $c['link'] }} font-medium hover:underline">
                                Resend OTP
                            </button>
                        </template>
                    </div>

                    <div class="text-center mt-3">
                        <button wire:click="usePassword"
                            class="text-sm text-gray-400 hover:text-gray-600 hover:underline">
                            Sign in with email and password instead
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
