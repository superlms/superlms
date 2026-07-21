<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (sticky, analytics + add button)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <x-admin.back-to-more />
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Users</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Create sub-admins and grant scoped access</p>
                    </div>
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
                <input wire:model.live.debounce.300ms="search" type="text" name="user-search" autocomplete="off"
                    data-lpignore="true" data-1p-ignore data-form-type="other" placeholder="Search name, email, mobile..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />
                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                @if ($search || $filterStatus !== '')
                    <button wire:click="clearFilters" class="text-xs text-purple-600 hover:text-purple-800 font-medium">Clear</button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         LIST
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[720px]">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left w-12">#</th>
                            <th class="px-4 py-3 text-left">User</th>
                            <th class="px-4 py-3 text-left">Contact</th>
                            <th class="px-4 py-3 text-center w-32">Access</th>
                            <th class="px-4 py-3 text-center w-28">Status</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $i => $u)
                            <tr wire:key="user-{{ $u->id }}" class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-400">{{ $users->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($u->image)
                                            <img src="{{ $u->image }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xs">{{ strtoupper(substr($u->name, 0, 1)) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 truncate">{{ $u->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $u->mobile_number ?: '—' }}
                                    @if ($u->alternative_mobile)<span class="text-gray-300"> · </span><span class="text-gray-400">{{ $u->alternative_mobile }}</span>@endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-600 text-xs font-medium">
                                        {{ count((array) $u->permissions) }} functionalities
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleStatus({{ $u->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $u->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600' }}">
                                        {{ $u->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button wire:click="view({{ $u->id }})" title="View"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                        <button wire:click="edit({{ $u->id }})" title="Edit"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="confirmDeletePrompt({{ $u->id }})" title="Delete"
                                            class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                    No sub-admins{{ ($search || $filterStatus !== '') ? ' for this filter' : ' yet' }}.
                                    @if (!$search && $filterStatus === '')
                                        <button wire:click="openCreate" class="block mx-auto mt-2 text-sm text-purple-600 hover:text-purple-800 font-medium">Add a user →</button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($users->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $users->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — Create / Edit (2 steps)
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit User' : 'Add New User' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Create sub-admins and grant scoped access</p>
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
                                <label class="text-sm font-medium text-gray-700">Profile Image <span class="text-gray-400 font-normal">(JPG/PNG/WEBP, max 1 MB)</span></label>
                                <input type="file" wire:model="image" accept=".jpg,.jpeg,.png,.webp" class="mt-1 block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-purple-50 file:text-purple-700 file:text-xs file:font-medium hover:file:bg-purple-100" />
                                <div wire:loading wire:target="image" class="text-xs text-gray-400 mt-1">Uploading…</div>
                                @error('image') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="fullName" autocomplete="off" maxlength="100" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="e.g. Rahul Sharma">
                            @error('fullName') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-rose-500">*</span></label>
                            <input type="email" wire:model="email" autocomplete="off" maxlength="191" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="user@example.com">
                            @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile <span class="text-rose-500">*</span></label>
                                <input type="text" wire:model="mobile" autocomplete="off" maxlength="10" inputmode="numeric" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="10-digit">
                                @error('mobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alternative Mobile</label>
                                <input type="text" wire:model="alternativeMobile" autocomplete="off" maxlength="10" inputmode="numeric" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="optional">
                                @error('alternativeMobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-gray-400 font-normal">(optional)</span></label>
                                <input type="date" wire:model="dob" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                                @error('dob') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date of Joining <span class="text-gray-400 font-normal">(optional)</span></label>
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
                            Select the functionalities this user can access. They will only see and use the screens you grant here.
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">
                                Functionalities
                                <span class="text-xs text-gray-400 font-normal">({{ count($permissions) }}/{{ count($catalog) }} selected)</span>
                            </span>
                            @if (count($catalog) > 0 && count($permissions) >= count($catalog))
                                <button type="button" wire:click="clearAllPermissions"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    Clear all
                                </button>
                            @else
                                <button type="button" wire:click="selectAllPermissions"
                                    class="inline-flex items-center gap-1 text-xs font-semibold text-purple-600 hover:text-purple-800">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    Select all
                                </button>
                            @endif
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
    @endif

    {{-- ══════════════════════════════════════════════════
         SLIDE-IN PANEL — View
    ══════════════════════════════════════════════════ --}}
    @if ($showViewPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewPanel"></div>
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
                        <div class="flex justify-between"><dt class="text-gray-500">Date of Birth</dt><dd class="text-gray-800 font-medium">{{ $viewData['dob'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Date of Joining</dt><dd class="text-gray-800 font-medium">{{ $viewData['date_of_joining'] ?? '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Gender</dt><dd class="text-gray-800 font-medium capitalize">{{ $viewData['gender'] ?: '—' }}</dd></div>
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
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-rose-100 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z" /></svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Delete User?</h3>
                <p class="text-sm text-gray-500 mt-1">This will permanently remove the sub-admin and revoke their access. This cannot be undone.</p>
                <div class="flex items-center justify-center gap-3 mt-6">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 border border-gray-200 rounded-lg">Cancel</button>
                    <button wire:click="executeDelete" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
