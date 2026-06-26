<div class="p-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Account Users</h1>
            <p class="text-sm text-gray-500 mt-1">Manage accounts panel users for your school</p>
        </div>
        <button wire:click="openAddModal"
            class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
            <x-icon name="plus" class="w-4 h-4" />
            Add User
        </button>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name or email..."
            class="w-full max-w-sm px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mobile</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Designation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($users as $index => $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $users->firstItem() + $index }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if ($user->schoolUser?->image)
                                        <img src="{{ $user->schoolUser->image }}" alt="{{ $user->name }}"
                                            class="w-9 h-9 rounded-full object-cover ring-2 ring-purple-100">
                                    @else
                                        <div
                                            class="w-9 h-9 rounded-full bg-purple-100 flex items-center justify-center ring-2 ring-purple-50">
                                            <span class="text-sm font-semibold text-purple-600">
                                                {{ strtoupper(substr($user->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        @if ($user->schoolUser?->employee_id)
                                            <div class="text-xs text-gray-400">ID: {{ $user->schoolUser->employee_id }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <div>{{ $user->mobile_number ?? '-' }}</div>
                                @if ($user->schoolUser?->alternate_mobile)
                                    <div class="text-xs text-gray-400">Alt: {{ $user->schoolUser->alternate_mobile }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                {{ $user->schoolUser?->designation ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex items-center gap-2">
                                    <button wire:click="editUser({{ $user->id }})"
                                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <x-icon name="pencil" class="w-4 h-4" />
                                    </button>
                                    <button wire:click="toggleActive({{ $user->id }})"
                                        class="p-1.5 text-gray-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                        title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                        <x-icon name="{{ $user->is_active ? 'pause' : 'play' }}" class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <x-icon name="user-group" class="w-12 h-12 text-gray-300 mb-3" />
                                    <p class="text-sm text-gray-500">No account users found</p>
                                    <p class="text-xs text-gray-400 mt-1">Click "Add User" to create one</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Add/Edit Modal -->
    @if ($modalOpen)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $isEditing ? 'Edit Account User' : 'Add Account User' }}
                    </h3>
                    <button wire:click="$set('modalOpen', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <x-icon name="x-mark" class="w-5 h-5" />
                    </button>
                </div>

                <div class="space-y-4">

                    <!-- Profile Image Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Image <span
                                class="text-xs text-gray-400">(max 2MB)</span></label>
                        <div class="flex items-center gap-4">
                            <!-- Preview -->
                            <div class="relative flex-shrink-0">
                                @if ($userImage)
                                    <img src="{{ $userImage->temporaryUrl() }}" alt="Preview"
                                        class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-200">
                                @elseif ($existingImage)
                                    <img src="{{ $existingImage }}" alt="Current"
                                        class="w-16 h-16 rounded-full object-cover ring-2 ring-purple-200">
                                @else
                                    <div
                                        class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center ring-2 ring-gray-200">
                                        <x-icon name="user" class="w-7 h-7 text-gray-400" />
                                    </div>
                                @endif

                                @if ($userImage || $existingImage)
                                    <button type="button" wire:click="removeImage"
                                        class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center shadow transition-colors"
                                        title="Remove image">
                                        <x-icon name="x-mark" class="w-3 h-3" />
                                    </button>
                                @endif
                            </div>

                            <!-- Upload Button -->
                            <div class="flex-1">
                                <label for="userImageInput"
                                    class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-600 hover:bg-gray-50 cursor-pointer transition-colors">
                                    <x-icon name="arrow-up-tray" class="w-4 h-4" />
                                    {{ $userImage || $existingImage ? 'Change Image' : 'Upload Image' }}
                                </label>
                                <input id="userImageInput" type="file" wire:model="userImage" accept="image/*"
                                    class="hidden">
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF, WEBP</p>
                            </div>
                        </div>

                        <div wire:loading wire:target="userImage" class="mt-2">
                            <p class="text-xs text-purple-500 flex items-center gap-1">
                                <svg class="animate-spin w-3 h-3" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                        stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                                </svg>
                                Uploading...
                            </p>
                        </div>

                        @error('userImage')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Basic Info</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span
                                class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="Enter full name"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                        @error('name')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email <span
                                class="text-red-500">*</span></label>
                        <input type="email" wire:model="email" placeholder="Enter email address"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                        @error('email')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Password {{ $isEditing ? '(leave blank to keep current)' : '' }}
                            @if (!$isEditing)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="password" wire:model="password"
                            placeholder="{{ $isEditing ? 'Leave blank to keep current' : 'Enter password (8-16 chars)' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                        @error('password')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Mobile Numbers -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                            <input type="text" wire:model="mobile_number" placeholder="Primary mobile"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                            @error('mobile_number')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alternate Mobile</label>
                            <input type="text" wire:model="alternate_mobile" placeholder="Alternate mobile"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                            @error('alternate_mobile')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Work Info</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Designation</label>
                            <input type="text" wire:model="designation" placeholder="e.g. Accountant"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" wire:model="department" placeholder="e.g. Accounts"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                        <input type="text" wire:model="employee_id" placeholder="Enter employee ID"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm">
                    </div>

                    <!-- Address -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea wire:model="address" placeholder="Enter full address" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 text-sm resize-none"></textarea>
                        @error('address')
                            <span class="text-red-500 text-xs">{{ $message }}</span>
                        @enderror
                    </div>

                </div>

                <div class="flex items-center gap-3 mt-6">
                    <button wire:click="saveUser" wire:loading.attr="disabled"
                        class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50">
                        <span wire:loading.remove
                            wire:target="saveUser">{{ $isEditing ? 'Update User' : 'Create User' }}</span>
                        <span wire:loading wire:target="saveUser">Saving...</span>
                    </button>
                    <button wire:click="$set('modalOpen', false)"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
