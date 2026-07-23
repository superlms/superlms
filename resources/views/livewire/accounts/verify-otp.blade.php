<div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6">
    <div class="flex flex-col md:flex-row w-full max-w-5xl md:h-[600px] rounded-3xl overflow-hidden bg-white border border-slate-100 shadow-xl shadow-slate-200/60">

        <!-- Left Side: OTP illustration (emerald scheme) -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-emerald-50 to-teal-50 flex-col items-center justify-center p-10">
            @include('partials.auth-illustration', ['variant' => 'otp', 'scheme' => 'emerald'])
        </div>

        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-8 md:p-12">
            <div class="mb-6 w-16 h-16 sm:w-20 sm:h-20">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-full h-full object-contain">
            </div>

            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Verify OTP</h1>
                <p class="text-gray-500 mt-1 text-sm">Enter the 6-digit code sent to your email</p>
                <p class="text-emerald-600 text-sm font-medium mt-1">{{ auth()->user()->email ?? '' }}</p>
            </div>

            <div class="w-full max-w-sm">
                <!-- OTP Input Fields -->
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
                }" class="flex justify-center gap-2 sm:gap-3 mb-4">
                    @for ($i = 0; $i < 6; $i++)
                        <input type="text" maxlength="1" x-ref="otp{{ $i }}"
                            x-model="otp[{{ $i }}]"
                            x-on:input="focusNext({{ $i }})"
                            x-on:keydown="focusPrev({{ $i }}, $event)"
                            inputmode="numeric"
                            class="w-10 h-12 sm:w-11 sm:h-14 text-center text-lg sm:text-xl font-bold border-2 border-slate-200 rounded-xl
                                   focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all"
                            @if ($i === 0) autofocus @endif>
                    @endfor
                </div>

                @error('otp')
                    <p class="text-red-500 text-xs text-center mb-3">{{ $message }}</p>
                @enderror

                @if (session('success'))
                    <p class="text-emerald-600 text-xs text-center mb-3">{{ session('success') }}</p>
                @endif

                <!-- Verify Button -->
                <button wire:click="verifyOtp" wire:loading.attr="disabled"
                    class="w-full py-3 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition duration-200 disabled:opacity-50 mb-4">
                    <span wire:loading.remove wire:target="verifyOtp">Verify &amp; Continue</span>
                    <span wire:loading wire:target="verifyOtp">Verifying...</span>
                </button>

                <!-- Resend OTP -->
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
                    document.addEventListener('visibilitychange', () => sync());
                    window.addEventListener('focus', () => sync());
                    $watch('until', () => sync());
                ">
                    <template x-if="!canResend">
                        <p class="text-sm text-gray-500">
                            Resend OTP in
                            <span class="font-semibold text-emerald-600" x-text="label"></span>
                        </p>
                    </template>
                    <template x-if="canResend">
                        <button wire:click="resendOtp"
                            class="text-sm text-emerald-600 hover:text-emerald-800 font-medium hover:underline">
                            Resend OTP
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>
