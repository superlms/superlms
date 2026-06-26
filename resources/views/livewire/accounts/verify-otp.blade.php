<div class="min-h-screen flex items-center justify-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-8">
        <!-- Logo -->
        <div class="flex justify-center mb-6">
            <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo" class="w-16 h-16 object-contain">
        </div>

        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-800">Verify OTP</h1>
            <p class="text-gray-500 mt-2 text-sm">Enter the 6-digit code sent to your email</p>
            <p class="text-emerald-600 text-sm font-medium mt-1">{{ auth()->user()->email ?? '' }}</p>
        </div>

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

        @if (session('success'))
            <p class="text-emerald-600 text-xs text-center mb-4">{{ session('success') }}</p>
        @endif

        <!-- Verify Button -->
        <button wire:click="verifyOtp" wire:loading.attr="disabled"
            class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-300 shadow-md disabled:opacity-50 mb-4">
            <span wire:loading.remove wire:target="verifyOtp">Verify & Continue</span>
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
    </div>
</div>
