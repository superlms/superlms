<div>

    {{-- Header (title + Add button) and the search/status filter live in the
         parent Fee page's sticky header — controlled here via Livewire events. --}}

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
                            <th class="px-4 py-3 text-left">Email</th>
                            <th class="px-4 py-3 text-left">Mobile</th>
                            <th class="px-4 py-3 text-left">Designation</th>
                            <th class="px-4 py-3 text-center w-28">Status</th>
                            <th class="px-4 py-3 text-center w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $i => $user)
                            <tr wire:key="acc-user-{{ $user->id }}" class="hover:bg-gray-50/70">
                                <td class="px-4 py-3 text-gray-400">{{ $users->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($user->schoolUser?->image)
                                            <img src="{{ $user->schoolUser->image }}" alt="{{ $user->name }}"
                                                class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xs">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-800 truncate">{{ $user->name }}</p>
                                            @if ($user->schoolUser?->employee_id)
                                                <p class="text-xs text-gray-400 truncate">ID: {{ $user->schoolUser->employee_id }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->email }}</td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $user->mobile_number ?: '—' }}
                                    @if ($user->schoolUser?->alternate_mobile)
                                        <span class="text-gray-300"> · </span><span class="text-gray-400">{{ $user->schoolUser->alternate_mobile }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-600">{{ $user->schoolUser?->designation ?: '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="toggleActive({{ $user->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-600' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button wire:click="viewUser({{ $user->id }})" title="View"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                        <button wire:click="editUser({{ $user->id }})" title="Edit"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="toggleActive({{ $user->id }})" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50">
                                            @if ($user->is_active)
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @else
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            @endif
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                    No account users{{ ($search || $filterStatus !== '') ? ' for this filter' : ' yet' }}.
                                    @if (!$search && $filterStatus === '')
                                        <button wire:click="openAdd" class="block mx-auto mt-2 text-sm text-purple-600 hover:text-purple-800 font-medium">Add a user →</button>
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
         SLIDE-IN PANEL — Add / Edit
    ══════════════════════════════════════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $isEditing ? 'Edit Account User' : 'Add Account User' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Accounts panel login for your school</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Profile Image --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Image <span class="text-xs text-gray-400 font-normal">(max 2MB)</span></label>
                        <div class="flex items-center gap-4">
                            <div class="relative flex-shrink-0">
                                @if ($userImage)
                                    <img src="{{ $userImage->temporaryUrl() }}" alt="Preview" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-200">
                                @elseif ($existingImage)
                                    <img src="{{ $existingImage }}" alt="Current" class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-200">
                                @else
                                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center ring-2 ring-gray-200">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                @endif
                                @if ($userImage || $existingImage)
                                    <button type="button" wire:click="removeImage"
                                        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow transition-colors" title="Remove image">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" wire:model="userImage" accept="image/*"
                                    class="block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-purple-50 file:text-purple-700 file:text-xs file:font-medium hover:file:bg-purple-100" />
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WEBP</p>
                                <div wire:loading wire:target="userImage" class="text-xs text-purple-500 mt-1">Uploading…</div>
                            </div>
                        </div>
                        @error('userImage') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Basic Info</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="Enter full name"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" wire:model="email" placeholder="Enter email address"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password
                            @if ($isEditing)<span class="text-gray-400 font-normal">(leave blank to keep current)</span>@else<span class="text-rose-500">*</span>@endif
                        </label>
                        <input type="password" wire:model="password"
                            placeholder="{{ $isEditing ? 'Leave blank to keep current' : 'Enter password (8-16 chars)' }}"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        @error('password') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                            <input type="text" wire:model="mobile_number" placeholder="Primary mobile"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            @error('mobile_number') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alternate Mobile</label>
                            <input type="text" wire:model="alternate_mobile" placeholder="Alternate mobile"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                            @error('alternate_mobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Work Info</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                            <input type="text" wire:model="designation" placeholder="e.g. Accountant"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" wire:model="department" placeholder="e.g. Accounts"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                        <input type="text" wire:model="employee_id" placeholder="Enter employee ID"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea wire:model="address" rows="3" placeholder="Enter full address"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none"></textarea>
                        @error('address') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="saveUser" type="button" wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 bg-purple-600 hover:bg-purple-700 disabled:opacity-60 text-white text-sm font-medium px-5 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="saveUser">{{ $isEditing ? 'Update User' : 'Create User' }}</span>
                        <span wire:loading wire:target="saveUser">Saving…</span>
                    </button>
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
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Account User Details</h2></div>
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
                        <div class="flex justify-between"><dt class="text-gray-500">Mobile</dt><dd class="text-gray-800 font-medium">{{ $viewData['mobile_number'] ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Alt. Mobile</dt><dd class="text-gray-800 font-medium">{{ $viewData['alternate_mobile'] ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Designation</dt><dd class="text-gray-800 font-medium">{{ $viewData['designation'] ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Department</dt><dd class="text-gray-800 font-medium capitalize">{{ $viewData['department'] ?: '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Employee ID</dt><dd class="text-gray-800 font-medium">{{ $viewData['employee_id'] ?: '—' }}</dd></div>
                        <div class="flex justify-between gap-6"><dt class="text-gray-500 flex-shrink-0">Address</dt><dd class="text-gray-800 font-medium text-right">{{ $viewData['address'] ?: '—' }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    @endif
</div>
