{{-- The Super Admin's Forgot Password, in the superlms.in website's colours
     and layout (partials.super-admin-auth-site): email, Verify OTP, new password. --}}
<div class="sa-auth">
    @include('partials.super-admin-auth-fields')
    @include('partials.super-admin-auth-site')

    <main class="sa-auth-main">
    <div class="sa-auth-card">

        <!-- Left: the website's mint band with the step acted out in a small app window -->
        <div class="sa-auth-art">
            @include('partials.super-admin-auth-motion', [
                'variant' => $step === 'otp' ? 'otp' : ($step === 'password' ? 'newpass' : 'reset'),
            ])
        </div>

        <!-- Right: the form -->
        <div class="sa-auth-form">

            <div class="sa-auth-logo">
                <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
            </div>

            {{-- ========== STEP 1: EMAIL ========== --}}
            @if ($step === 'email')
                <div class="text-center mb-8">
                    <h1 class="sa-title text-3xl md:text-4xl">Forgot <span class="grad">Password?</span></h1>
                    <p class="sa-sub mt-3 text-sm">Enter your registered email address and we'll send you an OTP to reset your password</p>
                </div>

                <div class="w-full max-w-sm" x-data="{ email: @entangle('email') }">
                    <div class="mb-5">
                        <div class="sa-field" :class="{ 'is-filled': String(email ?? '').trim() !== '' }"
                            @error('email') data-invalid @enderror
                            x-on:input="$el.removeAttribute('data-invalid')">
                            <input id="sa-forgot-email" type="email" x-model="email"
                                placeholder="Enter your email address"
                                autocomplete="username" wire:keydown.enter="submitEmail">
                            <label for="sa-forgot-email">Email Address</label>
                        </div>
                        @error('email') <p class="sa-field-error">{{ $message }}</p> @enderror
                    </div>

                    <button wire:click="submitEmail" wire:loading.attr="disabled"
                        :disabled="!email.trim()"
                        class="sa-btn mb-4">
                        <span wire:loading.remove wire:target="submitEmail">Send OTP</span>
                        <span wire:loading wire:target="submitEmail">Sending…</span>
                    </button>

                    <div class="text-center">
                        <a href="{{ route('super-admin.login') }}" class="text-sm sa-quiet">
                            Back to login
                        </a>
                    </div>
                </div>
            @endif

            {{-- ========== STEP 2: OTP ========== --}}
            @if ($step === 'otp')
                <div class="text-center mb-6">
                    <h1 class="sa-title text-3xl md:text-4xl">Verify <span class="grad">OTP</span></h1>
                    <p class="sa-sub mt-3 text-sm">We have sent an OTP to your email address and primary registered mobile number</p>
                </div>

                <div class="w-full max-w-sm">
                    {{-- 6-box OTP input --}}
                    <div x-data="{
                            otp: @entangle('otp'),
                            focusNext(index) {
                                if (this.otp[index] && index < 5) {
                                    this.$refs['otp' + (index + 1)].focus();
                                }
                            },
                            focusPrev(index, event) {
                                if (event.key === 'Backspace' && !this.otp[index] && index > 0) {
                                    this.$refs['otp' + (index - 1)].focus();
                                }
                            }
                        }"
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
                        <span wire:loading.remove wire:target="verifyOtp">Verify OTP</span>
                        <span wire:loading wire:target="verifyOtp">Verifying…</span>
                    </button>

                    {{-- Countdown + Resend --}}
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
                        <button wire:click="changeEmail"
                            class="text-sm sa-quiet">
                            Change Email
                        </button>
                    </div>
                </div>
            @endif

            {{-- ========== STEP 3: NEW PASSWORD ========== --}}
            @if ($step === 'password')
                <div class="text-center mb-8">
                    <h1 class="sa-title text-3xl md:text-4xl">Set New <span class="grad">Password</span></h1>
                    <p class="sa-sub mt-3 text-sm">Use 8–16 characters with at least one number and one special character like @, # or $</p>
                </div>

                {{-- The rules show once typing starts, each turning green as it is
                     met. Reset Password with a rule unmet shows only the unmet
                     ones, in red, until the password is typed in again (the
                     server checks the same rules and names only the broken ones). --}}
                <div class="w-full max-w-sm" x-data="{
                        password: @entangle('password'),
                        confirmation: @entangle('password_confirmation'),
                        showPass: false,
                        showConfirm: false,
                        tried: false,
                        get rules() {
                            const p = String(this.password ?? '');
                            return [
                                { label: '8–16 characters', ok: p.length >= 8 && p.length <= 16 },
                                { label: 'At least 1 number (0–9)', ok: /[0-9]/.test(p) },
                                { label: 'At least 1 special character (! @ # $ % & *)', ok: /[!@#$%^&*(),.?\&quot;:{}|<>]/.test(p) },
                            ];
                        },
                        get unmet() { return this.rules.filter(r => !r.ok); },
                        get filled() { return String(this.password ?? '').trim().length > 0 && String(this.confirmation ?? '').trim().length > 0; },
                        submit() {
                            this.tried = true;
                            if (this.unmet.length === 0) this.$wire.resetPassword();
                        }
                    }">
                    {{-- New Password --}}
                    <div class="mb-5">
                        <div class="sa-field has-eye"
                            :class="{ 'is-filled': String(password ?? '') !== '', 'is-bad': tried && unmet.length > 0 }"
                            @error('password') data-invalid @enderror
                            x-on:input="$el.removeAttribute('data-invalid'); tried = false">
                            <input id="sa-new-password" :type="showPass ? 'text' : 'password'" x-model="password"
                                placeholder="Enter new password" autocomplete="new-password">
                            <label for="sa-new-password">New Password</label>
                            <button type="button" @click="showPass = !showPass" class="sa-eye focus:outline-none"
                                :title="showPass ? 'Hide password' : 'Show password'">
                                <svg x-cloak x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        {{-- Every rule the server found broken — only those. --}}
                        @foreach ($errors->get('password') as $message)
                            <p class="sa-field-error">{{ $message }}</p>
                        @endforeach
                    </div>

                    {{-- Confirm Password --}}
                    <div class="mb-6">
                        <div class="sa-field has-eye" :class="{ 'is-filled': String(confirmation ?? '') !== '' }"
                            @error('password_confirmation') data-invalid @enderror
                            x-on:input="$el.removeAttribute('data-invalid')">
                            <input id="sa-confirm-password" :type="showConfirm ? 'text' : 'password'" x-model="confirmation"
                                placeholder="Confirm new password" autocomplete="new-password">
                            <label for="sa-confirm-password">Confirm Password</label>
                            <button type="button" @click="showConfirm = !showConfirm" class="sa-eye focus:outline-none"
                                :title="showConfirm ? 'Hide password' : 'Show password'">
                                <svg x-cloak x-show="showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                                <svg x-show="!showConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        @error('password_confirmation') <p class="sa-field-error">{{ $message }}</p> @enderror

                        {{-- The new password's rules, under both fields — live: every
                             rule, green once met; after a try: only the unmet ones, red. --}}
                        <div class="sa-rules" x-show="String(password ?? '') !== '' && (!tried || unmet.length > 0)"
                            x-transition.opacity.duration.200ms style="display: none">
                            <template x-for="rule in (tried ? unmet : rules)" :key="rule.label">
                                <p class="sa-rule" :class="rule.ok ? 'is-ok' : (tried ? 'is-bad' : '')">
                                    <span class="sa-rule-ico">
                                        <svg x-show="rule.ok" class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <svg x-show="!rule.ok && tried" class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="3.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        <span x-show="!rule.ok && !tried" class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                    </span>
                                    <span x-text="rule.label"></span>
                                </p>
                            </template>
                        </div>
                    </div>

                    <button type="button" x-on:click="submit()" wire:loading.attr="disabled"
                        :disabled="!filled"
                        class="sa-btn">
                        <span wire:loading.remove wire:target="resetPassword">Reset Password</span>
                        <span wire:loading wire:target="resetPassword">Resetting…</span>
                    </button>

                    <div class="text-center mt-4">
                        <a href="{{ route('super-admin.login') }}" class="text-sm sa-quiet">
                            Back to login
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
    </main>

</div>
