<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════
         Not sticky: this page has no filter bar to pin, and the shared
         header-collapse script would empty a pinned bar into a blank strip. --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 py-3 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Profile</h1>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                {{-- Last login --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-gray-50 border border-gray-200 text-gray-600">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Last login: <strong class="text-gray-800">{{ $user['last_login_at'] ?? '—' }}</strong>
                </span>
                {{-- Live IST clock --}}
                <span x-data="{ t: '' }" x-init="
                        const f = () => new Date().toLocaleString('en-IN', { timeZone: 'Asia/Kolkata', weekday:'short', day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit', second:'2-digit', hour12:true });
                        t = f(); setInterval(() => t = f(), 1000);
                    "
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 border border-blue-100 text-blue-700">
                    <svg class="w-4 h-4 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span x-text="t"></span> <span class="font-semibold">IST</span>
                </span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-6">

    {{-- Combined User + Staff Card --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

        {{-- Top: Avatar + Name + Status --}}
        <div class="px-6 py-5 flex items-center gap-4 border-b border-gray-50">
            <div class="relative w-16 h-16 flex-shrink-0">
                @if (!empty($user['image']))
                    <img src="{{ $user['image'] }}" alt="{{ $user['name'] }}"
                        class="w-16 h-16 rounded-full object-cover ring-2 ring-gray-100"
                        onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="w-16 h-16 rounded-full bg-emerald-50 items-center justify-center ring-2 ring-emerald-100" style="display:none;">
                        <span class="text-xl font-semibold text-emerald-600">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                    </div>
                @else
                    <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center ring-2 ring-emerald-100">
                        <span class="text-xl font-semibold text-emerald-600">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-gray-900 truncate">{{ $user['name'] ?? '-' }}</h2>
                <p class="text-sm text-gray-400 truncate">{{ $user['email'] ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">
                    {{ ucfirst($user['role'] ?? '-') }}
                </span>
                @if (!empty($schoolUser))
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $schoolUser['is_active'] ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                        {{ $schoolUser['is_active'] ? 'Active' : 'Inactive' }}
                    </span>
                @endif
                <button wire:click="openPasswordPanel"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                    <x-icon name="lock-closed" class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">Change Password</span>
                </button>
            </div>
        </div>

        {{-- Details Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-50">

            {{-- Left: Account Info --}}
            <div class="px-6 py-5 space-y-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Account</p>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Email</span>
                        <span class="text-sm text-gray-700 truncate ml-4">{{ $user['email'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Role</span>
                        <span class="text-sm text-gray-700 capitalize">{{ $user['role'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Mobile</span>
                        <span class="text-sm text-gray-700">{{ $user['phone'] ?? '-' }}</span>
                    </div>
                    @if (!empty($schoolUser['alternate_mobile']) && $schoolUser['alternate_mobile'] !== '-')
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400">Alternate Mobile</span>
                            <span class="text-sm text-gray-700">{{ $schoolUser['alternate_mobile'] }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Member Since</span>
                        <span class="text-sm text-gray-700">{{ $user['created_at'] ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Last Login</span>
                        <span class="text-sm text-gray-700">{{ $user['last_login_at'] ?? '—' }}</span>
                    </div>
                </div>
            </div>

            {{-- Right: Staff Info --}}
            <div class="px-6 py-5 space-y-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Staff</p>
                @if (!empty($schoolUser))
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400">Employee ID</span>
                            <span class="text-sm text-gray-700">{{ $schoolUser['employee_id'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400">Designation</span>
                            <span class="text-sm text-gray-700">{{ $schoolUser['designation'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-400">Department</span>
                            <span class="text-sm text-gray-700">{{ $schoolUser['department'] }}</span>
                        </div>
                        @if (!empty($schoolUser['address']) && $schoolUser['address'] !== '-')
                            <div class="flex justify-between items-start gap-4">
                                <span class="text-sm text-gray-400 flex-shrink-0">Address</span>
                                <span class="text-sm text-gray-700 text-right">{{ $schoolUser['address'] }}</span>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-gray-400">No staff profile found.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Organization Card --}}
    @if (!empty($organization))
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- Org Header --}}
            <div class="px-6 py-5 flex items-center gap-4 border-b border-gray-50">
                <div class="relative w-12 h-12 flex-shrink-0">
                    @if (!empty($organization['logo']))
                        <img src="{{ $organization['logo'] }}" alt="{{ $organization['name'] }}"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-100 bg-white"
                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 items-center justify-center border border-emerald-100" style="display:none;">
                            <x-icon name="building-office-2" class="w-6 h-6 text-emerald-500" />
                        </div>
                    @else
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center border border-emerald-100">
                            <x-icon name="building-office-2" class="w-6 h-6 text-emerald-500" />
                        </div>
                    @endif
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">{{ $organization['name'] }}</h2>
                    <p class="text-sm text-gray-400">Organization details</p>
                </div>
            </div>

            {{-- Org Details Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-0 divide-y sm:divide-y-0 sm:divide-x divide-gray-50">

                {{-- Left column --}}
                <div class="px-6 py-5 space-y-3">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-4">Contact</p>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Email</span>
                        <span class="text-sm text-gray-700">{{ $organization['email'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Phone</span>
                        <span class="text-sm text-gray-700">{{ $organization['phone'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">State</span>
                        <span class="text-sm text-gray-700">{{ $organization['state'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Board</span>
                        <span class="text-sm text-gray-700">{{ $organization['board'] }}</span>
                    </div>
                    <div class="flex justify-between items-start gap-4">
                        <span class="text-sm text-gray-400 flex-shrink-0">Address</span>
                        <span class="text-sm text-gray-700 text-right">{{ $organization['address'] }}</span>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="px-6 py-5 space-y-3">
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-4">Identifiers</p>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">School Code</span>
                        <span class="text-sm text-gray-700 font-mono">{{ $organization['school_code'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Affiliation No.</span>
                        <span class="text-sm text-gray-700 font-mono">{{ $organization['affiliation_number'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Serial No.</span>
                        <span class="text-sm text-gray-700 font-mono">{{ $organization['serial_number'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">UDISE No.</span>
                        <span class="text-sm text-gray-700 font-mono">{{ $organization['udise_number'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-400">Registered</span>
                        <span class="text-sm text-gray-700">{{ $organization['created_at'] ?? '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    </div>

    {{-- ══════════ CHANGE PASSWORD ══════════
         The admin profile's flow, teleported to <body> so the panel paints over
         the navbar and sidebar rather than under them. --}}
    @if ($showPasswordPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePasswordPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Set a new strong password for the accounts login</p>
                    </div>
                    <button wire:click="closePasswordPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4"
                     x-data="{ pwd: @entangle('newPassword').live }">
                    @foreach ([
                        ['label' => 'Current Password', 'model' => 'currentPassword', 'show' => $showCurrentPassword, 'toggle' => 'current', 'error' => 'currentPassword'],
                        ['label' => 'New Password',     'model' => 'newPassword',     'show' => $showNewPassword,     'toggle' => 'new',     'error' => 'newPassword'],
                        ['label' => 'Confirm Password', 'model' => 'confirmPassword', 'show' => $showConfirmPassword, 'toggle' => 'confirm', 'error' => null],
                    ] as $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                {{ $field['label'] }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input wire:model.live.debounce.150ms="{{ $field['model'] }}"
                                       type="{{ $field['show'] ? 'text' : 'password' }}"
                                       placeholder="{{ $field['label'] }}"
                                       autocomplete="new-password"
                                       class="w-full px-3.5 py-2.5 pr-10 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" wire:click="togglePasswordVisibility('{{ $field['toggle'] }}')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($field['show'])
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
                            @if ($field['error'])
                                @error($field['error'])<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endforeach

                    {{-- Strong-password live checklist --}}
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Password must include</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ([
                                ['label' => 'At least 8 characters', 'test' => 'pwd && pwd.length >= 8'],
                                ['label' => 'An uppercase letter',   'test' => '/[A-Z]/.test(pwd || "")'],
                                ['label' => 'A lowercase letter',    'test' => '/[a-z]/.test(pwd || "")'],
                                ['label' => 'A number',              'test' => '/[0-9]/.test(pwd || "")'],
                                ['label' => 'A symbol',              'test' => '/[^A-Za-z0-9]/.test(pwd || "")'],
                            ] as $rule)
                                <div class="flex items-center gap-1.5 text-xs"
                                     :class="{{ $rule['test'] }} ? 'text-emerald-600' : 'text-gray-400'">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="leading-tight">{{ $rule['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePasswordPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="updatePassword" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
