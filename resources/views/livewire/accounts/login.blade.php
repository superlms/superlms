<div class="min-h-screen flex items-center justify-center bg-gradient-to-r from-emerald-50 to-teal-50">
    <!-- Split Screen Container -->
    <div class="flex flex-col md:flex-row w-full max-w-5xl h-[600px] rounded-2xl overflow-hidden shadow-lg bg-white">
        <!-- Left Side (Decorative) -->
        <div
            class="hidden md:flex md:w-1/2 bg-gradient-to-br from-emerald-100 to-teal-100 flex-col items-center justify-center p-8 relative">
            <div
                class="absolute inset-0 opacity-10 bg-[url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiPjxkZWZzPjxwYXR0ZXJuIGlkPSJwYXR0ZXJuIiB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHBhdHRlcm5Vbml0cz0idXNlclNwYWNlT25Vc2UiIHBhdHRlcm5UcmFuc2Zvcm09InJvdGF0ZSg0NSkiPjxyZWN0IHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgZmlsbD0icmdiYSgyMTYsIDE4MCwgMjU1LCAwLjEpIj48L3JlY3Q+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI3BhdHRlcm4pIj48L3JlY3Q+PC9zdmc+')]">
            </div>
            <div class="relative z-10 w-3/4 max-w-xs">
                <img src="{{ asset('admin-image/Frame 1171279095.png') }}" alt="Illustration"
                    class="w-full h-auto object-contain">
            </div>
        </div>

        <!-- Right Side (Login Form) -->
        <div class="w-full md:w-1/2 flex flex-col items-center justify-center p-8 md:p-12">
            <div class="mb-8 w-20 h-20">
                <img src="{{ asset('website-image/Group 11525.png') }}" alt="Logo"
                    class="w-full h-full object-contain">
            </div>

            <div class="text-center mb-8">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Welcome Back!</h1>
                <p class="text-gray-500 mt-2">Login to Accounts Panel</p>
            </div>

            <div class="w-full max-w-sm">
                <!-- Email Field -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Email</label>
                    <input type="email" wire:model="email" placeholder="Enter your Email"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    @error('email')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-medium mb-1">Password</label>
                    <div class="relative">
                        <input type="{{ $showPassword ? 'text' : 'password' }}" wire:model="password"
                            placeholder="Enter your password"
                            class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent pr-10">
                        <button type="button" wire:click="toggleShowPassword"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none">
                            @if ($showPassword)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21">
                                    </path>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            @endif
                        </button>
                    </div>
                    @error('password')
                        <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Forgot Password -->
                <div class="flex items-center justify-end mb-6">
                    <a href="{{ route('accounts.reset-password') }}"
                        class="text-sm text-emerald-600 hover:text-emerald-800 hover:underline">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button wire:click="login" wire:loading.attr="disabled"
                    class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg transition duration-300 shadow-md hover:shadow-lg disabled:opacity-50">
                    <span wire:loading.remove wire:target="login">Login</span>
                    <span wire:loading wire:target="login">Verifying...</span>
                </button>
            </div>
        </div>
    </div>
</div>
