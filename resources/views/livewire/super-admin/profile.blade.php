<div class="min-h-screen bg-gray-50">

    {{-- HEADER --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:min-h-15 flex items-center">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">My Profile</h1>
        </div>
        <div class="border-t border-gray-200 px-4 sm:px-6 overflow-x-auto">
            <div class="flex gap-1">
                <button wire:click="showTab('profile')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'profile' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Profile
                </button>
                <button wire:click="showTab('numbers')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'numbers' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    OTP Numbers
                </button>
                @if ($isMain)
                    <button wire:click="showTab('platform')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'platform' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        Platform
                    </button>
                @endif
                <button wire:click="showTab('password')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap {{ $activeTab === 'password' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Change Password
                </button>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 max-w-3xl mx-auto space-y-6">

        {{-- PROFILE TAB --}}
        @if ($activeTab === 'profile')
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-6">
                    <div class="flex flex-col items-center gap-3">
                        <div class="w-24 h-24 rounded-full border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                            @if ($tempPhotoUrl)
                                <img src="{{ $tempPhotoUrl }}" class="w-full h-full object-cover" alt="">
                            @elseif ($user->image)
                                <img src="{{ $user->image }}" class="w-full h-full object-cover" alt="">
                            @else
                                <span class="text-3xl font-bold text-purple-600">{{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}</span>
                            @endif
                        </div>
                        <label class="text-xs font-medium text-purple-600 cursor-pointer hover:text-purple-800">
                            Change Photo
                            <input type="file" wire:model="photo" accept="image/*" class="hidden">
                        </label>
                        <div wire:loading wire:target="photo" class="text-xs text-gray-400">Uploading…</div>
                        @error('photo') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                        @if ($photo)
                            <button wire:click="savePhoto" class="text-xs bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-md">Save Photo</button>
                        @endif
                    </div>

                    <div class="flex-1 space-y-1">
                        <h2 class="text-lg font-bold text-gray-900">{{ $user->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-medium capitalize">
                            {{ str_replace('-', ' ', $user->role) }}
                        </span>
                    </div>
                </div>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6 pt-6 border-t border-gray-100 text-sm">
                    <div>
                        <dt class="text-gray-500">Primary Mobile (OTP)</dt>
                        <dd class="text-gray-800 font-medium mt-0.5">
                            {{ $user->mobile_number ?: '—' }}
                            <button wire:click="showTab('numbers')" class="ml-2 text-xs font-medium text-purple-600 hover:text-purple-800">Manage</button>
                        </dd>
                    </div>
                    @if (!empty($user->dob))
                        <div><dt class="text-gray-500">Date of Birth</dt><dd class="text-gray-800 font-medium mt-0.5">{{ \Carbon\Carbon::parse($user->dob)->format('d M Y') }}</dd></div>
                    @endif
                    @if (!empty($user->date_of_joining))
                        <div><dt class="text-gray-500">Date of Joining</dt><dd class="text-gray-800 font-medium mt-0.5">{{ \Carbon\Carbon::parse($user->date_of_joining)->format('d M Y') }}</dd></div>
                    @endif
                    @if (!empty($user->gender))
                        <div><dt class="text-gray-500">Gender</dt><dd class="text-gray-800 font-medium mt-0.5 capitalize">{{ $user->gender }}</dd></div>
                    @endif
                    <div><dt class="text-gray-500">Last Login</dt><dd class="text-gray-800 font-medium mt-0.5">{{ $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never' }}</dd></div>
                </dl>

                @if (!empty($grantedAccess))
                    <div class="mt-6 pt-6 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Your Access</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($grantedAccess as $perm)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">{{ $perm }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif

        {{-- OTP NUMBERS TAB --}}
        @if ($activeTab === 'numbers')
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-base font-bold text-gray-900">OTP Numbers</h2>
                <p class="text-sm text-gray-500 mt-1">
                    The sign-in and forgot-password code goes to your email and, on WhatsApp, to the
                    <span class="font-semibold text-gray-700">primary</span> number only. Up to {{ $maxNumbers }} numbers.
                </p>

                <ul class="mt-5 divide-y divide-gray-100 border border-gray-100 rounded-lg">
                    @forelse ($numbers as $n)
                        @php $key = $n['primary'] ? 'p' : (string) $n['id']; @endphp
                        <li class="px-4 py-3" wire:key="otp-number-{{ $key }}">
                            @if ($editing === $key)
                                <div class="flex flex-col sm:flex-row sm:items-start gap-2">
                                    <div class="flex-1">
                                        <input type="tel" wire:model="editNumber" wire:keydown.enter="saveEdit" inputmode="tel" maxlength="16"
                                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 {{ $errors->has('editNumber') ? 'border-rose-400' : 'border-gray-200' }}">
                                        @error('editNumber') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <div class="flex gap-2">
                                        <button wire:click="saveEdit" wire:loading.attr="disabled" class="text-sm bg-purple-600 hover:bg-purple-700 text-white px-3 py-2 rounded-lg">Save</button>
                                        <button wire:click="cancelEdit" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Cancel</button>
                                    </div>
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                    <span class="text-sm font-medium text-gray-900 tabular-nums">{{ \App\Support\OtpNumbers::format($n['phone']) }}</span>
                                    @if ($n['primary'])
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-xs font-semibold">Primary · gets the OTP</span>
                                    @endif
                                    <div class="ml-auto flex items-center gap-3 text-sm">
                                        @unless ($n['primary'])
                                            <button wire:click="makePrimary({{ $n['id'] }})" class="font-medium text-purple-600 hover:text-purple-800">Make primary</button>
                                        @endunless
                                        <button wire:click="startEdit('{{ $key }}')" class="text-gray-500 hover:text-gray-800">Edit</button>
                                        <button wire:click="deleteNumber('{{ $key }}')"
                                            wire:confirm="Delete {{ \App\Support\OtpNumbers::format($n['phone']) }}?{{ $n['primary'] ? ' The next number becomes primary.' : '' }}"
                                            class="text-rose-500 hover:text-rose-700">Delete</button>
                                    </div>
                                </div>
                            @endif
                        </li>
                    @empty
                        <li class="px-4 py-6 text-sm text-gray-500 text-center">No number yet — the code goes by email only. Add one below.</li>
                    @endforelse
                </ul>

                @if (count($numbers) < $maxNumbers)
                    <div class="mt-5">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Add a number</label>
                        <div class="flex gap-2">
                            <input type="tel" wire:model="newNumber" wire:keydown.enter="addNumber" inputmode="tel" maxlength="16"
                                placeholder="10-digit mobile number"
                                class="flex-1 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 {{ $errors->has('newNumber') ? 'border-rose-400' : 'border-gray-200' }}">
                            <button wire:click="addNumber" wire:loading.attr="disabled"
                                class="text-sm bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white font-medium px-4 py-2 rounded-lg">Add</button>
                        </div>
                        @error('newNumber') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- PLATFORM TAB (main super admin) --}}
        @if ($activeTab === 'platform' && $isMain)
            {{-- Logo --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-base font-bold text-gray-900">Platform Logo</h2>
                <p class="text-sm text-gray-500 mt-1">Shown on the login pages, the sidebars and the website. PNG, JPG, WEBP or SVG, up to 2 MB.</p>

                <div class="mt-5 flex flex-col sm:flex-row sm:items-center gap-5">
                    <div class="flex items-center gap-4">
                        <div class="text-center">
                            <div class="w-24 h-24 rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center p-2">
                                <img src="{{ $logoUrl }}" alt="Current logo" class="max-w-full max-h-full object-contain">
                            </div>
                            <p class="text-xs text-gray-400 mt-1">{{ $customLogo ? 'Current' : 'Original' }}</p>
                        </div>
                        @if ($logoPreview)
                            <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5-5 5M6 12h12"/></svg>
                            <div class="text-center">
                                <div class="w-24 h-24 rounded-xl border-2 border-purple-300 bg-gray-50 flex items-center justify-center p-2">
                                    <img src="{{ $logoPreview }}" alt="New logo" class="max-w-full max-h-full object-contain">
                                </div>
                                <p class="text-xs text-purple-600 mt-1">New</p>
                            </div>
                        @endif
                    </div>

                    <div class="flex-1 space-y-2">
                        <label class="inline-flex items-center gap-2 text-sm font-medium text-purple-600 hover:text-purple-800 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M16 8l-4-4-4 4M12 4v12"/></svg>
                            Choose image or SVG
                            <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
                        </label>
                        <div wire:loading wire:target="logo" class="text-xs text-gray-400">Uploading…</div>
                        @error('logo') <p class="text-xs text-rose-500">{{ $message }}</p> @enderror
                        <div class="flex flex-wrap gap-2">
                            @if ($logo && !$errors->has('logo'))
                                <button wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo"
                                    class="text-sm bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white font-medium px-4 py-2 rounded-lg">
                                    <span wire:loading.remove wire:target="saveLogo">Save Logo</span>
                                    <span wire:loading wire:target="saveLogo">Saving…</span>
                                </button>
                            @endif
                            @if ($customLogo)
                                <button wire:click="resetLogo" wire:confirm="Go back to the original SuperLMS logo?"
                                    class="text-sm text-gray-500 hover:text-gray-800 px-3 py-2">Use original logo</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Platform details --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-base font-bold text-gray-900">Platform Details</h2>
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-4 mt-4 text-sm">
                    <div><dt class="text-gray-500">Platform</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['name'] }}</dd></div>
                    <div class="col-span-2 sm:col-span-2 min-w-0"><dt class="text-gray-500">Website</dt>
                        <dd class="mt-0.5 truncate"><a href="{{ $platform['website'] }}" target="_blank" rel="noopener" class="font-semibold text-purple-600 hover:text-purple-800">{{ $platform['website'] }}</a></dd></div>
                    <div><dt class="text-gray-500">Schools</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['schools'] }} <span class="text-xs font-normal text-gray-400">({{ $platform['active'] }} active)</span></dd></div>
                    <div><dt class="text-gray-500">Students</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['students'] }}</dd></div>
                    <div><dt class="text-gray-500">Teachers</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['teachers'] }}</dd></div>
                    <div><dt class="text-gray-500">School admins</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['admins'] }}</dd></div>
                    <div><dt class="text-gray-500">Super admin accounts</dt><dd class="text-gray-900 font-semibold mt-0.5">{{ $platform['super'] }}</dd></div>
                </dl>
            </div>

            {{-- Website data --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-base font-bold text-gray-900">Website Data</h2>
                <p class="text-sm text-gray-500 mt-1">What superlms.in shows and has collected.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">
                    @foreach ($websiteData as $row)
                        <div class="flex items-center justify-between gap-3 border border-gray-100 rounded-lg px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm text-gray-500">{{ $row['label'] }}</p>
                                <p class="text-lg font-bold text-gray-900 leading-tight">{{ $row['count'] }}</p>
                                <p class="text-xs text-gray-400">{{ $row['latest'] ? 'Latest ' . $row['latest'] : 'None yet' }}</p>
                            </div>
                            @if ($row['url'])
                                <a href="{{ $row['url'] }}" class="text-sm font-medium text-purple-600 hover:text-purple-800 whitespace-nowrap">View →</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- PASSWORD TAB --}}
        @if ($activeTab === 'password')
            <div class="bg-white rounded-xl border border-gray-200 p-6 max-w-md">
                <h2 class="text-base font-bold text-gray-900 mb-4">Change Password</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                        <div class="relative">
                            <input type="{{ $showCurrentPassword ? 'text' : 'password' }}" wire:model="currentPassword"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" wire:click="togglePasswordVisibility('current')" class="absolute right-3 top-2.5 text-gray-400 text-xs">{{ $showCurrentPassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        @error('currentPassword') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                        <div class="relative">
                            <input type="{{ $showNewPassword ? 'text' : 'password' }}" wire:model="newPassword"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" wire:click="togglePasswordVisibility('new')" class="absolute right-3 top-2.5 text-gray-400 text-xs">{{ $showNewPassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        @error('newPassword') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-400 mt-1">Min 8 chars, mixed case, number & symbol.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                        <div class="relative">
                            <input type="{{ $showConfirmPassword ? 'text' : 'password' }}" wire:model="confirmPassword"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm pr-10 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            <button type="button" wire:click="togglePasswordVisibility('confirm')" class="absolute right-3 top-2.5 text-gray-400 text-xs">{{ $showConfirmPassword ? 'Hide' : 'Show' }}</button>
                        </div>
                        @error('confirmPassword') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <button wire:click="updatePassword" wire:loading.attr="disabled"
                        class="w-full bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white text-sm font-medium px-4 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Updating…</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
