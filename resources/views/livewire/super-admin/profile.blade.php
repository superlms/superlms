<div class="min-h-screen bg-gray-50">

    {{-- HEADER --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">My Profile</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage your account details and password</p>
        </div>
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="showTab('profile')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'profile' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    Profile
                </button>
                <button wire:click="showTab('password')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'password' ? 'border-purple-600 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
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
                    <div><dt class="text-gray-500">Mobile</dt><dd class="text-gray-800 font-medium mt-0.5">{{ $user->mobile_number ?: '—' }}</dd></div>
                    @if (!empty($user->alternative_mobile))
                        <div><dt class="text-gray-500">Alternative Mobile</dt><dd class="text-gray-800 font-medium mt-0.5">{{ $user->alternative_mobile }}</dd></div>
                    @endif
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
