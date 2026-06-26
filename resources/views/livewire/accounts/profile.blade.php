<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (admin theme — sticky white) ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Profile</h1>
                <p class="text-sm text-gray-500 mt-0.5">Your account and organization details</p>
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
                    <div class="w-16 h-16 rounded-full bg-purple-50 items-center justify-center ring-2 ring-purple-100" style="display:none;">
                        <span class="text-xl font-semibold text-purple-600">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                    </div>
                @else
                    <div class="w-16 h-16 rounded-full bg-purple-50 flex items-center justify-center ring-2 ring-purple-100">
                        <span class="text-xl font-semibold text-purple-600">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                    </div>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <h2 class="text-base font-semibold text-gray-900 truncate">{{ $user['name'] ?? '-' }}</h2>
                <p class="text-sm text-gray-400 truncate">{{ $user['email'] ?? '-' }}</p>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-600">
                    {{ ucfirst($user['role'] ?? '-') }}
                </span>
                @if (!empty($schoolUser))
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-medium
                        {{ $schoolUser['is_active'] ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-500' }}">
                        {{ $schoolUser['is_active'] ? 'Active' : 'Inactive' }}
                    </span>
                @endif
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

            {{-- Analytics --}}
            <div class="grid grid-cols-3 divide-x divide-gray-50 border-b border-gray-50">
                <div class="px-6 py-4 text-center">
                    <p class="text-2xl font-bold text-blue-600">{{ $analytics['students'] ?? 0 }}</p>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Students</p>
                </div>
                <div class="px-6 py-4 text-center">
                    <p class="text-2xl font-bold text-purple-600">{{ $analytics['teachers'] ?? 0 }}</p>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Teachers</p>
                </div>
                <div class="px-6 py-4 text-center">
                    <p class="text-2xl font-bold text-emerald-600">{{ $analytics['staff'] ?? 0 }}</p>
                    <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Active Staff</p>
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
</div>
