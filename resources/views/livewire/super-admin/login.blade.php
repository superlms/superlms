<div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6">
    <!-- Split Screen Container -->
    <div class="flex flex-col md:flex-row w-full max-w-5xl md:h-[600px] rounded-3xl overflow-hidden bg-white border border-slate-100 shadow-xl shadow-slate-200/60">

        <!-- Left Side: dynamic illustration that follows the active step -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-violet-50 to-fuchsia-50 flex-col items-center justify-center p-10">
            @include('partials.auth-illustration', ['variant' => $step === 'otp' ? 'otp' : 'login'])
        </div>

        <!-- Right Side (Form) -->
        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-8 md:p-12">

            <!-- Logo -->
            <div class="mb-6 w-16 h-16 sm:w-20 sm:h-20">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-full h-full object-contain">
            </div>

            @if (session('success'))
                <div class="w-full max-w-sm mb-5 flex items-start gap-2 rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700 ring-1 ring-green-100">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- ── STEP 1: Credentials ── --}}
            @if ($step === 'credentials')
                <div class="text-center mb-8">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Super Admin Login</h1>
                    <p class="text-gray-500 mt-2 text-sm">Login to continue to your dashboard</p>
                </div>

                <div class="w-full max-w-sm" x-data="{ email: @entangle('email'), password: @entangle('password'), showPass: false }">
                    <div class="mb-4">
                        <label class="block text-gray-600 text-sm font-medium mb-1.5">Email</label>
                        <input type="email" x-model="email" placeholder="Enter your email"
                            wire:keydown.enter="login"
                            class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition">
                        @error('email')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-600 text-sm font-medium mb-1.5">Password</label>
                        <div class="relative">
                            <input :type="showPass ? 'text' : 'password'" x-model="password"
                                placeholder="Enter your password"
                                wire:keydown.enter="login"
                                class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition pr-10">
                            <button type="button" @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                                <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end mb-5">
                        <a href="{{ route('super-admin.forgot-password') }}"
                            class="text-sm text-violet-600 hover:text-violet-800 hover:underline">Forgot password?</a>
                    </div>

                    <button wire:click="login" wire:loading.attr="disabled"
                        :disabled="!email.trim() || !password.trim()"
                        class="w-full py-3 bg-violet-600 text-white font-medium rounded-xl hover:bg-violet-700 transition duration-200 disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="login">Login</span>
                        <span wire:loading wire:target="login">Sending OTP…</span>
                    </button>
                </div>
            @endif

            {{-- ── STEP 2: OTP Verification ── --}}
            @if ($step === 'otp')
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">Verify OTP</h1>
                    <p class="text-gray-500 mt-1 text-sm">Enter the 6-digit code sent to your email</p>
                    <p class="text-violet-600 text-sm font-medium mt-1">{{ $otpSentTo }}</p>
                </div>

                <div class="w-full max-w-sm">
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
                        x-init="$watch('otp', () => { if (!allFilled()) submitting = false; })"
                        class="flex justify-center gap-2 sm:gap-3 mb-4">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" maxlength="1"
                                x-ref="otp{{ $i }}"
                                x-model="otp[{{ $i }}]"
                                x-on:input="focusNext({{ $i }})"
                                x-on:keydown="focusPrev({{ $i }}, $event)"
                                inputmode="numeric"
                                class="w-10 h-12 sm:w-11 sm:h-14 text-center text-lg sm:text-xl font-bold border-2 border-slate-200 rounded-xl
                                       focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-violet-500 transition-all"
                                @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    @error('otp')
                        <p class="text-red-500 text-xs text-center mb-3">{{ $message }}</p>
                    @enderror

                    <button wire:click="verifyOtp" wire:loading.attr="disabled"
                        class="w-full py-3 bg-violet-600 text-white font-medium rounded-xl hover:bg-violet-700 transition duration-200 disabled:opacity-60 mb-4">
                        <span wire:loading.remove wire:target="verifyOtp">Verify &amp; Login</span>
                        <span wire:loading wire:target="verifyOtp">Verifying…</span>
                    </button>

                    {{-- Countdown + Resend — restarts when canResend flips back to false --}}
                    <div class="text-center" x-data="{
                            countdown: @entangle('countdown'),
                            canResend: @entangle('canResend'),
                            timer: null,
                            startTimer() {
                                if (this.timer) clearInterval(this.timer);
                                this.timer = setInterval(() => {
                                    if (this.countdown > 0) {
                                        this.countdown--;
                                    } else {
                                        this.canResend = true;
                                        clearInterval(this.timer);
                                        this.timer = null;
                                        $wire.timerFinished();
                                    }
                                }, 1000);
                            }
                        }" x-init="
                            startTimer();
                            $watch('canResend', value => {
                                if (value === false && countdown > 0) startTimer();
                            });
                        ">
                        <template x-if="!canResend">
                            <p class="text-sm text-gray-500">
                                Resend OTP in
                                <span class="font-semibold text-violet-600"
                                    x-text="Math.floor(countdown / 60) + ':' + String(countdown % 60).padStart(2, '0')"></span>
                            </p>
                        </template>
                        <template x-if="canResend">
                            <button wire:click="resendOtp"
                                class="text-sm text-violet-600 hover:text-violet-800 font-medium hover:underline">
                                Resend OTP
                            </button>
                        </template>
                    </div>

                    <div class="text-center mt-3">
                        <button wire:click="backToLogin"
                            class="text-sm text-gray-400 hover:text-gray-600 hover:underline">
                            ← Back to login
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
