{{-- The school admin's sign-in in the Super Admin's look (user's ask of
     2026-11-19): the superlms.in website's colours and layout
     (partials.super-admin-auth-site) — Login, then its Verify OTP step, with
     the step acted out in a small app window beside the form. --}}
<div class="sa-auth">
    @include('partials.super-admin-auth-fields')
    @include('partials.super-admin-auth-site')

    {{-- Inside the installed app: over to its code screen --}}
    @include('partials.panel-app-login', ['panel' => 'admin'])

    <main class="sa-auth-main">
    <div class="sa-auth-card">

        <!-- Left: the website's mint band with the step acted out in a small app window -->
        <div class="sa-auth-art">
            @include('partials.super-admin-auth-motion', [
                'variant'  => $step === 'otp' ? 'otp' : 'login',
                'subtitle' => $step === 'otp' ? null : 'Sign in to manage your school — students, teachers, fees and more',
                'amTitle'  => $step === 'otp' ? null : 'School Admin Login',
                'amPath'   => 'login',
                'amEmail'  => 'admin@school.in',
            ])
        </div>

        <!-- Right: the form -->
        <div class="sa-auth-form">

            <div class="sa-auth-logo">
                <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
            </div>

            @if (session('success'))
                <div class="sa-auth-note">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            {{-- ── STEP 1: Credentials ── --}}
            @if ($step === 'credentials')
                <div class="text-center mb-8">
                    <h1 class="sa-title text-3xl md:text-4xl">School Admin <span class="grad">Login</span></h1>
                    <p class="sa-sub mt-3 text-sm">Enter your registered email and password to access your school's admin panel</p>
                </div>

                {{-- A school already open on this browser: carry on there, or sign in to another below. --}}
                @if ($signedIn)
                    <div class="sa-auth-note">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>
                            Signed in to <strong>{{ $signedIn['name'] }}</strong>.
                            <a href="{{ $signedIn['url'] }}" class="sa-accent">Continue →</a>
                            <span class="block text-xs opacity-75 mt-0.5">Or sign in to another school below.</span>
                        </span>
                    </div>
                @endif

                <div class="w-full max-w-sm" x-data="{ email: @entangle('email'), password: @entangle('password'), showPass: false }">
                    <div class="mb-5">
                        <div class="sa-field" :class="{ 'is-filled': String(email ?? '').trim() !== '' }"
                            @error('email') data-invalid @enderror
                            x-on:input="$el.removeAttribute('data-invalid')">
                            <input id="admin-login-email" type="email" x-model="email" placeholder="Enter your email"
                                autocomplete="username" wire:keydown.enter="login">
                            <label for="admin-login-email">Email</label>
                        </div>
                        @error('email')
                            <p class="sa-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <div class="sa-field has-eye" :class="{ 'is-filled': String(password ?? '') !== '' }"
                            @error('password') data-invalid @enderror
                            x-on:input="$el.removeAttribute('data-invalid')">
                            <input id="admin-login-password" :type="showPass ? 'text' : 'password'" x-model="password"
                                placeholder="Enter your password"
                                autocomplete="current-password" wire:keydown.enter="login">
                            <label for="admin-login-password">Password</label>
                            <button type="button" @click="showPass = !showPass" class="sa-eye focus:outline-none"
                                :title="showPass ? 'Hide password' : 'Show password'">
                                <svg x-cloak x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                            <p class="sa-field-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end mb-5">
                        <a href="{{ route('reset.password') }}"
                            class="sa-accent text-sm">Forgot password?</a>
                    </div>

                    <button wire:click="login" wire:loading.attr="disabled"
                        :disabled="!String(email ?? '').trim() || !String(password ?? '').trim()"
                        class="sa-btn">
                        <span wire:loading.remove wire:target="login">Login</span>
                        <span wire:loading wire:target="login">Sending OTP…</span>
                    </button>

                    <p class="sa-terms mt-4 text-xs text-center leading-relaxed sa-quiet">
                        By signing in you agree to our
                        <a href="{{ route('website.terms-conditions') }}" target="_blank" rel="noopener noreferrer" class="sa-accent">Terms &amp; Conditions</a>,
                        <a href="{{ route('website.privacy') }}" target="_blank" rel="noopener noreferrer" class="sa-accent">Privacy Policy</a>
                        and
                        <a href="{{ route('website.terms-of-use') }}" target="_blank" rel="noopener noreferrer" class="sa-accent">Terms of Use</a>.
                    </p>
                </div>
            @endif

            {{-- ── STEP 2: OTP Verification ── --}}
            @if ($step === 'otp')
                <div class="text-center mb-6">
                    <h1 class="sa-title text-3xl md:text-4xl">Verify <span class="grad">OTP</span></h1>
                    <p class="sa-sub mt-3 text-sm">
                        {{ $hasMobile
                            ? 'We have sent an OTP to your email address and registered mobile number'
                            : 'We have sent an OTP to your registered email address' }}
                    </p>
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
                        @error('otp') data-invalid @enderror
                        x-on:input="$el.removeAttribute('data-invalid')"
                        class="sa-otp flex justify-center gap-2 sm:gap-3 mb-4">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" maxlength="1"
                                x-ref="otp{{ $i }}"
                                x-model="otp[{{ $i }}]"
                                x-on:input="focusNext({{ $i }})"
                                x-on:keydown="focusPrev({{ $i }}, $event)"
                                inputmode="numeric" autocomplete="one-time-code" aria-label="Digit {{ $i + 1 }}"
                                :class="{ 'is-filled': String(otp[{{ $i }}] ?? '') !== '' }"
                                class="sa-otp-box w-10 h-12 sm:w-11 sm:h-14 text-center text-lg sm:text-xl font-bold rounded-xl"
                                @if ($i === 0) autofocus @endif>
                        @endfor
                    </div>

                    @error('otp')
                        <p class="text-red-500 text-xs text-center mb-3">{{ $message }}</p>
                    @enderror

                    <x-otp-lockout :until="$otpLockedUntil" />

                    <button wire:click="verifyOtp" wire:loading.attr="disabled"
                        class="sa-btn mb-4">
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
                            <p class="text-sm sa-quiet">
                                Resend OTP in
                                <span class="font-semibold sa-accent"
                                    x-text="Math.floor(countdown / 60) + ':' + String(countdown % 60).padStart(2, '0')"></span>
                            </p>
                        </template>
                        <template x-if="canResend">
                            <button wire:click="resendOtp"
                                class="text-sm sa-accent">
                                Resend OTP
                            </button>
                        </template>
                    </div>

                    <div class="text-center mt-3">
                        <button wire:click="backToLogin"
                            class="text-sm sa-quiet">
                            Back to login
                        </button>
                    </div>
                </div>
            @endif

        </div>
    </div>
    </main>

</div>
