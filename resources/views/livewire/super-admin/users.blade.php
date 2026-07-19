<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (sticky, analytics + add button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Users</h1>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $analytics['active'] }}</strong></span>
                        <span class="pl-4">Inactive: <strong class="text-rose-500">{{ $analytics['inactive'] }}</strong></span>
                    </div>
                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add User
                    </button>
                </div>
            </div>
            <div class="flex lg:hidden items-center gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $analytics['active'] }}</strong></span>
                <span>Inactive: <strong class="text-rose-500">{{ $analytics['inactive'] }}</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="search" wire:key="users-search" type="text" name="users_list_search"
                    autocomplete="off" placeholder="Search name, email, mobile..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                <select wire:model.live="filterOrg"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
                @if ($search || $filterStatus !== '' || $filterOrg !== '')
                    <button wire:click="clearFilters" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Clear</button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         LIST (table on desktop, cards on mobile — like Students)
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

        {{-- ══════════ DESKTOP TABLE ══════════ --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="overflow-x-auto rounded-xl">
                <table class="w-full min-w-[760px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mobile</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Functionalities</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $index => $u)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                {{-- S.No --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">{{ $users->firstItem() + $index }}</span>
                                </td>

                                {{-- User --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($u->image)
                                            <img src="{{ $u->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-purple-600">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $u->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Mobile --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-700">{{ $u->mobile_number ?: '—' }}{{ $u->alternative_mobile ? ' · ' . $u->alternative_mobile : '' }}</span>
                                </td>

                                {{-- Functionalities --}}
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-medium">
                                        {{ count((array) $u->permissions) }} functionalities
                                    </span>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    <button wire:click="toggleStatus({{ $u->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium
                                        {{ $u->is_active ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' : 'bg-rose-50 text-rose-600 hover:bg-rose-100' }} transition-colors">
                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="view({{ $u->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="edit({{ $u->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDeletePrompt({{ $u->id }})" title="Delete"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No sub super-admins yet. Click “Add User” to create one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">{{ $users->links() }}</div>
            @endif
        </div>

        {{-- ══════════ MOBILE CARDS ══════════ --}}
        <div class="md:hidden space-y-3">
            @forelse ($users as $u)
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
                    <div class="flex items-start gap-3">
                        @if ($u->image)
                            <img src="{{ $u->image }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-purple-600">{{ strtoupper(substr($u->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-bold text-gray-900 truncate">{{ $u->name }}</p>
                                <button wire:click="toggleStatus({{ $u->id }})"
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium flex-shrink-0
                                    {{ $u->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                                    {{ $u->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </div>
                            <p class="text-xs text-gray-400 truncate">{{ $u->email }}</p>
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1.5 text-xs text-gray-500">
                                <span>{{ $u->mobile_number ?: '—' }}</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-[11px] font-medium">{{ count((array) $u->permissions) }} functionalities</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100 mt-3 pt-1 -mb-1">
                        <button wire:click="view({{ $u->id }})" class="flex-1 py-2 text-xs font-medium text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">View</button>
                        <button wire:click="edit({{ $u->id }})" class="flex-1 py-2 text-xs font-medium text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">Edit</button>
                        <button wire:click="confirmDeletePrompt({{ $u->id }})" class="flex-1 py-2 text-xs font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors">Delete</button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-12 text-center text-gray-400">
                    No sub super-admins yet. Click “Add User” to create one.
                </div>
            @endforelse
            @if ($users->hasPages())
                <div class="mt-4">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — Create / Edit (2 steps)
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit User' : 'Add New User' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Create sub super-admins and grant scoped access</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 pt-6 pb-6 space-y-5">

                    {{-- Step indicator --}}
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 text-xs font-medium {{ $step === 1 ? 'text-purple-600' : 'text-gray-400' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] {{ $step === 1 ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-500' }}">1</span>
                            Personal Details
                        </span>
                        <span class="w-8 h-px bg-gray-200"></span>
                        <span class="flex items-center gap-1.5 text-xs font-medium {{ $step === 2 ? 'text-purple-600' : 'text-gray-400' }}">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[11px] {{ $step === 2 ? 'bg-purple-600 text-white' : 'bg-gray-200 text-gray-500' }}">2</span>
                            Access & Permissions
                        </span>
                    </div>

                    {{-- STEP 1 --}}
                    <div class="{{ $step === 1 ? '' : 'hidden' }} space-y-5">
                        {{-- Image --}}
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-full border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover" alt="">
                                @elseif ($imageUrl)
                                    <img src="{{ $imageUrl }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @endif
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Profile Image</label>
                                <input type="file" wire:model="image" accept="image/*" class="mt-1 block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-purple-50 file:text-purple-700 file:text-xs file:font-medium hover:file:bg-purple-100" />
                                <div wire:loading wire:target="image" class="text-xs text-gray-400 mt-1">Uploading…</div>
                                @error('image') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="fullName" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="e.g. Rahul Sharma">
                            @error('fullName') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-rose-500">*</span></label>
                            <input type="email" wire:model="email" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="user@example.com">
                            @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="mobile" maxlength="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="10-digit">
                                @error('mobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alternative Mobile</label>
                                <input type="text" wire:model="alternativeMobile" maxlength="10" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="optional">
                                @error('alternativeMobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                            <input type="text" wire:model="address" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="optional">
                            @error('address') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                                <input type="date" wire:model="dob" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @error('dob') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Joining <span class="text-gray-400 text-xs font-normal">(optional)</span></label>
                                <input type="date" wire:model="dateOfJoining" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @error('dateOfJoining') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gender <span class="text-rose-500">*</span></label>
                                <select wire:model="gender" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="">Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('gender') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account Status</label>
                                <select wire:model="isActive" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 2 --}}
                    <div class="{{ $step === 2 ? '' : 'hidden' }} space-y-4">
                        <div class="bg-purple-50 border border-purple-100 rounded-lg px-4 py-3 text-sm text-purple-700">
                            First choose the organization this user is limited to, then select the functionalities they can access. They will only see and use the screens you grant here.
                        </div>

                        {{-- Organization scope --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Access</label>
                            <select wire:model.live="allowedOrgId" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                <option value="">All Organizations (full access, like now)</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $allowedOrgId ? 'This user will use the granted functionalities for the selected school only.' : 'This user will use the granted functionalities for every school.' }}
                            </p>
                            @error('allowedOrgId') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">Functionalities</span>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400">{{ count($permissions) }} selected</span>
                                @if (count($permissions) < count($catalog))
                                    <button type="button" wire:click="selectAllPermissions"
                                        class="text-xs font-semibold text-purple-600 hover:text-purple-800">Select All</button>
                                @else
                                    <button type="button" wire:click="deselectAllPermissions"
                                        class="text-xs font-semibold text-purple-600 hover:text-purple-800">Unselect All</button>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($catalog as $routeName => $title)
                                <label class="flex items-center gap-2.5 border border-gray-200 rounded-lg px-3 py-2.5 cursor-pointer hover:border-purple-300 has-[:checked]:border-purple-500 has-[:checked]:bg-purple-50 transition-colors">
                                    <input type="checkbox" value="{{ $routeName }}" wire:model.live="permissions" class="rounded text-purple-600 focus:ring-purple-500 border-gray-300">
                                    <span class="text-sm text-gray-700">{{ $title }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-between gap-3">
                    @if ($step === 1)
                        <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                        <button wire:click="nextStep" type="button" class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium px-5 py-2 rounded-lg">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    @else
                        <button wire:click="backStep" type="button" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            Back
                        </button>
                        <button wire:click="save" type="button" wire:loading.attr="disabled" class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white text-sm font-medium px-5 py-2 rounded-lg">
                            <span wire:loading.remove wire:target="save">{{ $editId ? 'Update User' : 'Create User' }}</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — View
    ══════════════════════════════════════════════════ --}}
    @if ($showViewPanel)
        @teleport('body')
        <div class="fixed inset-0 z-[70] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeViewPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">User Details</h2></div>
                    <button wire:click="closeViewPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="flex flex-col items-center text-center mb-6">
                        @if (!empty($viewData['image']))
                            <img src="{{ $viewData['image'] }}" class="w-20 h-20 rounded-full object-cover border border-gray-200" alt="">
                        @else
                            <span class="w-20 h-20 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-2xl font-bold">{{ strtoupper(substr($viewData['name'] ?? '', 0, 1)) }}</span>
                        @endif
                        <h3 class="text-lg font-bold text-gray-900 mt-3">{{ $viewData['name'] ?? '' }}</h3>
                        <p class="text-sm text-gray-500">{{ $viewData['email'] ?? '' }}</p>
                        <span class="mt-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($viewData['is_active'] ?? false) ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-600' }}">
                            {{ ($viewData['is_active'] ?? false) ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Mobile</dt><dd class="text-gray-800 font-medium">{{ $viewData['mobile'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Alt. Mobile</dt><dd class="text-gray-800 font-medium">{{ $viewData['alternative_mobile'] ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500 flex-shrink-0">Address</dt><dd class="text-gray-800 font-medium text-right">{{ $viewData['address'] ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Date of Birth</dt><dd class="text-gray-800 font-medium">{{ $viewData['dob'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Date of Joining</dt><dd class="text-gray-800 font-medium">{{ $viewData['date_of_joining'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Gender</dt><dd class="text-gray-800 font-medium capitalize">{{ $viewData['gender'] ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-gray-500 flex-shrink-0">Organization Access</dt><dd class="text-gray-800 font-medium text-right">{{ $viewData['organization'] ?? 'All Organizations' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Last Login</dt><dd class="text-gray-800 font-medium">{{ $viewData['last_login_at'] ?? 'Never' }}</dd></div>
                    </dl>

                    <div class="mt-6">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">Granted Access</h4>
                        @if (!empty($viewData['permissions']))
                            <div class="flex flex-wrap gap-2">
                                @foreach ($viewData['permissions'] as $perm)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">{{ $perm }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">No functionalities granted.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        @teleport('body')
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Delete User?</h3>
                <p class="text-sm text-gray-500 mt-1">This will permanently remove the sub super-admin and revoke their access. This cannot be undone.</p>
                <div class="flex items-center justify-center gap-3 mt-6">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-200 rounded-lg">Cancel</button>
                    <button wire:click="executeDelete" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg">Delete</button>
                </div>
            </div>
        </div>
        @endteleport
    @endif
</div>
