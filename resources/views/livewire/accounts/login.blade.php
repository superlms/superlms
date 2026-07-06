<div class="min-h-screen flex items-center justify-center bg-slate-50 p-4 sm:p-6">
    <!-- Split Screen Container -->
    <div class="flex flex-col md:flex-row w-full max-w-5xl md:h-[600px] rounded-3xl overflow-hidden bg-white border border-slate-100 shadow-xl shadow-slate-200/60">

        <!-- Left Side: illustration (emerald scheme to match the accounts theme) -->
        <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-emerald-50 to-teal-50 flex-col items-center justify-center p-10">
            @include('partials.auth-illustration', ['variant' => 'login', 'scheme' => 'emerald'])
        </div>

        <!-- Right Side (Form) -->
        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-6 sm:p-8 md:p-12">

            <!-- Logo -->
            <div class="mb-6 w-16 h-16 sm:w-20 sm:h-20">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-full h-full object-contain">
            </div>

            <div class="text-center mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Welcome Back</h1>
                <p class="text-gray-500 mt-2 text-sm">Login to Accounts Panel</p>
            </div>

            <div class="w-full max-w-sm">
                <!-- Email Field -->
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Email</label>
                    <input type="email" wire:model="email" placeholder="Enter your email"
                        wire:keydown.enter="login"
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition">
                    @error('email')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label class="block text-gray-600 text-sm font-medium mb-1.5">Password</label>
                    <div class="relative">
                        <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="password"
                            placeholder="Enter your password"
                            wire:keydown.enter="login"
                            class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition pr-10">
                        <button type="button" wire:click="toggleShowPassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            @if ($showPassword)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Forgot Password -->
                <div class="flex items-center justify-end mb-5">
                    <a href="{{ route('accounts.reset-password') }}"
                        class="text-sm text-emerald-600 hover:text-emerald-800 hover:underline">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button wire:click="login" wire:loading.attr="disabled"
                    class="w-full py-3 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 transition duration-200 disabled:opacity-60">
                    <span wire:loading.remove wire:target="login">Login</span>
                    <span wire:loading wire:target="login">Sending OTP…</span>
                </button>
            </div>
        </div>
    </div>
</div>
