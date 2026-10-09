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
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $analytics['total'] }}</strong></span>
                        <span class="px-4">Active: <strong class="text-emerald-600">{{ $analytics['active'] }}</strong></span>
                        <span class="pl-4">Inactive: <strong class="text-rose-500">{{ $analytics['inactive'] }}</strong></span>
                    </div>
                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Add User</span>
                        <span class="sm:hidden">Add</span>
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
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
                @if ($search || $filterStatus !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         LIST — in the Students list's look: S.No, photo + name over the email,
         mobile, access as plain text, and the actions as icons with the
         status dot in front (still a button: a click switches it).
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mobile</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Access</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $i => $u)
                            <tr wire:key="user-{{ $u->id }}" class="hover:bg-gray-50/70 transition-colors">
                                {{-- S.No --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">{{ $users->firstItem() + $i }}</span>
                                </td>

                                {{-- User (photo + name, email under it) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($u->image)
                                            <img src="{{ $u->image }}" alt="" class="w-9 h-9 rounded-full object-cover object-top border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($u->name ?? 'U', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $u->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $u->email }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Mobile (the alternative one small under it) --}}
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700">{{ $u->mobile_number ?: '—' }}</p>
                                    @if ($u->alternative_mobile)
                                        <p class="text-xs text-gray-400">{{ $u->alternative_mobile }}</p>
                                    @endif
                                </td>

                                {{-- Access, as plain text --}}
                                <td class="px-4 py-3">
                                    @php $permCount = count((array) $u->permissions); @endphp
                                    <span class="text-sm text-gray-700">{{ $permCount }} {{ $permCount === 1 ? 'functionality' : 'functionalities' }}</span>
                                </td>

                                {{-- Actions (status dot shown inline; a click switches it) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="toggleStatus({{ $u->id }})"
                                            title="{{ $u->is_active ? 'Active — click to make inactive' : 'Inactive — click to make active' }}"
                                            class="p-1 rounded-full hover:bg-gray-100 mr-0.5 flex-shrink-0">
                                            <span class="block w-2 h-2 rounded-full {{ $u->is_active ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        </button>
                                        <button wire:click="view({{ $u->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                        <button wire:click="edit({{ $u->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="confirmDeletePrompt({{ $u->id }})" title="Delete"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <p class="text-sm font-semibold text-gray-800">No users{{ ($search || $filterStatus !== '') ? ' for this filter' : ' yet' }}</p>
                                    @if (!$search && $filterStatus === '')
                                        <button wire:click="openCreate" class="mt-2 text-sm font-medium text-blue-600 hover:text-blue-800">Add a user →</button>
                                    @else
                                        <p class="text-xs text-gray-400 mt-1">Try another search or status.</p>
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
         SLIDE-IN PANEL — Create / Edit, in the Mark Attendance panel's look:
         a toolbar with the two steps, then flat label rows. Every field,
         rule and save is as before.
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit User' : 'Add New User' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $step === 1 ? 'Personal details' : 'The screens this user can open' }}</p>
                    </div>
                    <button wire:click="closePanel" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Toolbar: the two steps; on the second, the count and Select all / Clear all --}}
                <div class="px-6 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2 flex-shrink-0 text-sm">
                    <span class="{{ $step === 1 ? 'text-gray-900 font-medium' : 'text-gray-400' }}">1. Personal details</span>
                    <span class="text-gray-300">/</span>
                    <span class="{{ $step === 2 ? 'text-gray-900 font-medium' : 'text-gray-400' }}">2. Access</span>
                    @if ($step === 2)
                        <span class="ml-auto text-xs text-gray-400 tabular-nums">{{ count($permissions) }} of {{ count($catalog) }} selected</span>
                        <div class="inline-flex items-center rounded-md border border-gray-200 overflow-hidden text-xs">
                            <button type="button" wire:click="selectAllPermissions" class="px-2.5 py-1.5 text-gray-600 hover:bg-gray-50">Select all</button>
                            <button type="button" wire:click="clearAllPermissions" class="px-2.5 py-1.5 text-gray-500 hover:bg-gray-50 border-l border-gray-200">Clear all</button>
                        </div>
                    @endif
                </div>

                {{-- STEP 1 — one flat row a field --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 {{ $step === 1 ? '' : 'hidden' }}">
                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Photo</span>
                        <div class="flex-1 min-w-0 flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center flex-shrink-0">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="w-full h-full object-cover" alt="">
                                @elseif ($imageUrl)
                                    <img src="{{ $imageUrl }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <input type="file" wire:model="image" accept=".jpg,.jpeg,.png,.webp"
                                    class="block w-full text-xs text-gray-500 file:mr-3 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200" />
                                <p class="text-[11px] text-gray-400 mt-1">Optional · JPG, PNG or WebP · max 1 MB</p>
                                <div wire:loading wire:target="image" class="text-xs text-blue-600 mt-1">Uploading…</div>
                                @error('image') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Full name <span class="text-red-500">*</span></span>
                        <div class="flex-1 min-w-0">
                            <input type="text" wire:model="fullName" autocomplete="off" maxlength="100" placeholder="e.g. Rahul Sharma" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('fullName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Email <span class="text-red-500">*</span></span>
                        <div class="flex-1 min-w-0">
                            <input type="email" wire:model="email" autocomplete="off" maxlength="191" placeholder="user@example.com" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Mobile <span class="text-red-500">*</span></span>
                        <div class="flex-1 min-w-0">
                            <input type="text" wire:model="mobile" autocomplete="off" maxlength="10" inputmode="numeric" placeholder="10-digit" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('mobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Alternative mobile</span>
                        <div class="flex-1 min-w-0">
                            <input type="text" wire:model="alternativeMobile" autocomplete="off" maxlength="10" inputmode="numeric" placeholder="Optional" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('alternativeMobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Date of birth</span>
                        <div class="flex-1 min-w-0">
                            <input type="date" wire:model="dob" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('dob') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Date of joining</span>
                        <div class="flex-1 min-w-0">
                            <input type="date" wire:model="dateOfJoining" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400">
                            @error('dateOfJoining') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Gender <span class="text-red-500">*</span></span>
                        <div class="flex-1 min-w-0">
                            <select wire:model="gender" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="">Select</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            @error('gender') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="flex items-start gap-3 px-6 py-3">
                        <span class="w-32 pt-2 text-xs font-medium text-gray-500 flex-shrink-0">Account status</span>
                        <div class="flex-1 min-w-0">
                            <select wire:model="isActive" class="w-full text-sm border border-gray-200 rounded-md px-2.5 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 — one flat row a screen, ticked to grant it --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 {{ $step === 2 ? '' : 'hidden' }}">
                    <p class="px-6 py-2 text-xs text-gray-500">The user sees and uses only the screens ticked here.</p>
                    @foreach ($catalog as $routeName => $title)
                        <label wire:key="perm-{{ $routeName }}" class="flex items-center gap-3 px-6 py-2.5 cursor-pointer hover:bg-gray-50">
                            <span class="w-4 text-[11px] text-gray-300 tabular-nums flex-shrink-0">{{ $loop->iteration }}</span>
                            <input type="checkbox" value="{{ $routeName }}" wire:model.live="permissions" class="rounded border-gray-300 text-gray-900 focus:ring-gray-400">
                            <span class="text-sm text-gray-800">{{ $title }}</span>
                        </label>
                    @endforeach
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                    @if ($step === 1)
                        <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="nextStep" type="button"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5">
                            Next
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </button>
                    @else
                        <button wire:click="backStep" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                            Back
                        </button>
                        <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
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
