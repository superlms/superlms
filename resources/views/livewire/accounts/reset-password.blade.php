<div class="min-h-screen flex items-center justify-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo" class="w-16 h-16 object-contain">
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center justify-center gap-2 mb-8">
            @for ($i = 1; $i <= 3; $i++)
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                        {{ $step >= $i ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-500' }}">
                        {{ $i }}
                    </div>
                    @if ($i < 3)
                        <div class="w-12 h-0.5 {{ $step > $i ? 'bg-emerald-600' : 'bg-gray-200' }}"></div>
                    @endif
                </div>
            @endfor
        </div>

        <!-- Step 1: Enter Email -->
        @if ($step === 1)
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-gray-800">Forgot Password</h1>
                <p class="text-gray-500 mt-2 text-sm">Enter your registered email address</p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-1">Email Address</label>
                <input type="email" wire:model="email" placeholder="Enter registered email"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                @error('email')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <button wire:click="sendOtp" wire:loading.attr="disabled"
                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-300 shadow-md disabled:opacity-50 mb-4">
                <span wire:loading.remove wire:target="sendOtp">Send OTP</span>
                <span wire:loading wire:target="sendOtp">Sending...</span>
            </button>

            <div class="text-center">
                <a href="{{ route('accounts.login') }}" class="text-sm text-emerald-600 hover:text-emerald-800 hover:underline">
                    Back to Login
                </a>
            </div>
        @endif

        <!-- Step 2: Enter OTP -->
        @if ($step === 2)
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-gray-800">Enter OTP</h1>
                <p class="text-gray-500 mt-2 text-sm">Enter the 6-digit code sent to</p>
                <p class="text-emerald-600 text-sm font-medium">{{ $email }}</p>
            </div>

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
            }" class="flex justify-center gap-3 mb-6">
                @for ($i = 0; $i < 6; $i++)
                    <input type="text" maxlength="1" x-ref="otp{{ $i }}"
                        x-model="otp[{{ $i }}]"
                        x-on:input="focusNext({{ $i }})"
                        x-on:keydown="focusPrev({{ $i }}, $event)"
                        class="w-12 h-14 text-center text-xl font-bold border-2 border-gray-300 rounded-xl
                               focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                        @if ($i === 0) autofocus @endif>
                @endfor
            </div>

            @error('otp')
                <p class="text-red-500 text-xs text-center mb-4">{{ $message }}</p>
            @enderror

            <button wire:click="verifyOtp" wire:loading.attr="disabled"
                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-300 shadow-md disabled:opacity-50 mb-4">
                <span wire:loading.remove wire:target="verifyOtp">Verify OTP</span>
                <span wire:loading wire:target="verifyOtp">Verifying...</span>
            </button>

            <!-- Resend OTP -->
            <div class="text-center" x-data="{
                countdown: @entangle('countdown'),
                canResend: @entangle('canResend'),
                timer: null,
                startTimer() {
                    this.timer = setInterval(() => {
                        if (this.countdown > 0) {
                            this.countdown--;
                        } else {
                            this.canResend = true;
                            clearInterval(this.timer);
                            $wire.timerFinished();
                        }
                    }, 1000);
                }
            }" x-init="startTimer()">
                <template x-if="!canResend">
                    <p class="text-sm text-gray-500">
                        Resend OTP in
                        <span class="font-semibold text-emerald-600"
                            x-text="Math.floor(countdown / 60) + ':' + String(countdown % 60).padStart(2, '0')"></span>
                    </p>
                </template>
                <template x-if="canResend">
                    <button wire:click="resendOtp"
                        class="text-sm text-emerald-600 hover:text-emerald-800 font-medium hover:underline">
                        Resend OTP
                    </button>
                </template>
            </div>
        @endif

        <!-- Step 3: Set New Password -->
        @if ($step === 3)
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold text-gray-800">Set New Password</h1>
                <p class="text-gray-500 mt-2 text-sm">Create a new password for your account</p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-medium mb-1">New Password</label>
                <div class="relative">
                    <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="password"
                        placeholder="Enter new password"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10">
                    <button type="button" wire:click="$toggle('showPassword')"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if ($showPassword)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            @endif
                        </svg>
                    </button>
                </div>
                @error('password')
                    <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                @enderror
                <p class="text-xs text-gray-400 mt-1">8-16 characters, at least 1 number and 1 special character</p>
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-medium mb-1">Confirm Password</label>
                <div class="relative">
                    <input type="{{ $showConfirmPassword ? 'text' : 'password' }}" wire:model="password_confirmation"
                        placeholder="Confirm new password"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10">
                    <button type="button" wire:click="$toggle('showConfirmPassword')"
                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if ($showConfirmPassword)
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            @else
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            @endif
                        </svg>
                    </button>
                </div>
            </div>

            <button wire:click="resetPassword" wire:loading.attr="disabled"
                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-300 shadow-md disabled:opacity-50">
                <span wire:loading.remove wire:target="resetPassword">Set New Password</span>
                <span wire:loading wire:target="resetPassword">Saving...</span>
            </button>
        @endif
    </div>
</div>
