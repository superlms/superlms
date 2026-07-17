<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">School Listing</h1>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">Add prospective schools, track them by location and approve or remark each one.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add School
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Filter bar ── --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email or mobile…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-64 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Location filter --}}
                <select wire:model.live="locationFilter"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
                    @endforeach
                </select>

                {{-- Status filter --}}
                <select wire:model.live="statusFilter"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Remarked</option>
                </select>

                @if ($search || $locationFilter || $statusFilter)
                    <button wire:click="clearFilters" class="text-xs font-medium text-indigo-600 hover:text-indigo-800">Clear</button>
                @endif
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-6 space-y-5">

        {{-- ── Stats ── --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Total</div>
                <div class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total']) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Approved</div>
                <div class="text-2xl font-bold text-green-600 mt-1">{{ number_format($stats['approved']) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Pending</div>
                <div class="text-2xl font-bold text-amber-500 mt-1">{{ number_format($stats['pending']) }}</div>
            </div>
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Remarked</div>
                <div class="text-2xl font-bold text-rose-500 mt-1">{{ number_format($stats['rejected']) }}</div>
            </div>
        </div>

        {{-- ── Table ── --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">School</th>
                            <th class="px-4 py-3">Location</th>
                            <th class="px-4 py-3">Contact</th>
                            <th class="px-4 py-3">Classes</th>
                            <th class="px-4 py-3 text-right">Students</th>
                            <th class="px-4 py-3 text-right">Avg Fee</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($schools as $school)
                            <tr class="hover:bg-gray-50/70" wire:key="school-{{ $school->id }}">
                                {{-- School (logo + name + address) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center flex-shrink-0">
                                            @if ($school->logo)
                                                <img src="{{ $school->logo }}" alt="{{ $school->name }}" class="w-full h-full object-cover">
                                            @else
                                                <span class="text-base">🏫</span>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-gray-900 truncate max-w-[220px]">{{ $school->name }}</div>
                                            @if ($school->address)
                                                <div class="text-xs text-gray-400 truncate max-w-[220px]">{{ $school->address }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                {{-- Location --}}
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-700">
                                        <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        {{ $school->location }}
                                    </span>
                                </td>
                                {{-- Contact --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs text-gray-700">{{ $school->email ?: '—' }}</div>
                                    <div class="text-xs text-gray-400">{{ $school->mobile ?: '—' }}</div>
                                </td>
                                {{-- Classes --}}
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $school->classes ?: '—' }}</td>
                                {{-- Students --}}
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $school->no_of_students !== null ? number_format($school->no_of_students) : '—' }}</td>
                                {{-- Avg fee --}}
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $school->avg_fee !== null ? '₹' . number_format((float) $school->avg_fee) : '—' }}</td>
                                {{-- Status --}}
                                <td class="px-4 py-3">
                                    @php
                                        $badge = [
                                            'approved' => ['Approved', 'bg-green-100 text-green-700'],
                                            'rejected' => ['Remarked', 'bg-rose-100 text-rose-700'],
                                            'pending'  => ['Pending', 'bg-amber-100 text-amber-700'],
                                        ][$school->status] ?? ['Pending', 'bg-amber-100 text-amber-700'];
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $badge[1] }}">{{ $badge[0] }}</span>
                                    @if ($school->status === 'rejected' && $school->remark)
                                        <div class="text-[11px] text-gray-400 mt-1 max-w-[180px] truncate" title="{{ $school->remark }}">“{{ $school->remark }}”</div>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($school->status !== 'approved')
                                            <button wire:click="approve({{ $school->id }})"
                                                class="px-2 py-1 text-[11px] font-medium text-green-700 border border-green-200 rounded-md hover:bg-green-50 transition-colors">Approve</button>
                                        @endif
                                        <button wire:click="openRemark({{ $school->id }})"
                                            class="px-2 py-1 text-[11px] font-medium text-amber-700 border border-amber-200 rounded-md hover:bg-amber-50 transition-colors">Remark</button>
                                        @if ($school->status !== 'pending')
                                            <button wire:click="markPending({{ $school->id }})"
                                                class="px-2 py-1 text-[11px] font-medium text-gray-600 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors">Reset</button>
                                        @endif
                                        <button wire:click="openEdit({{ $school->id }})"
                                            class="px-2 py-1 text-[11px] font-medium text-indigo-600 border border-indigo-200 rounded-md hover:bg-indigo-50 transition-colors">Edit</button>
                                        <button wire:click="confirmDelete({{ $school->id }})"
                                            class="px-2 py-1 text-[11px] font-medium text-red-600 border border-red-200 rounded-md hover:bg-red-50 transition-colors">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-16 text-center">
                                    <div class="text-4xl mb-3">🏫</div>
                                    <h3 class="text-base font-semibold text-gray-800">No schools listed yet</h3>
                                    <p class="text-sm text-gray-500 mt-1">Click <span class="font-semibold text-indigo-600">Add School</span> to list your first prospective school.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div>{{ $schools->links() }}</div>
    </div>

    {{-- ══════════════════ SLIDE-IN PANEL (Add / Edit) ══════════════════ --}}
    @if ($showPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit School' : 'Add New School' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Start with the location, then fill in the school details.</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                    {{-- Location (entered first) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="location" placeholder="e.g. Jaipur, Rajasthan"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        <p class="text-xs text-gray-400 mt-1">Used to group and filter schools in the listing.</p>
                        @error('location') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Logo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">School Logo</label>
                        <div class="flex items-center gap-4">
                            <div class="w-20 h-20 rounded-lg border border-gray-200 overflow-hidden bg-gray-50 flex items-center justify-center flex-shrink-0">
                                @if ($logo)
                                    <img src="{{ $logo->temporaryUrl() }}" class="w-full h-full object-cover" alt="">
                                @elseif ($logoUrl)
                                    <img src="{{ $logoUrl }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <span class="text-2xl">🏫</span>
                                @endif
                            </div>
                            <div>
                                <input type="file" wire:model="logo" accept="image/*"
                                    class="block text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:text-xs file:font-medium hover:file:bg-indigo-100" />
                                <div wire:loading wire:target="logo" class="text-xs text-gray-400 mt-1">Uploading…</div>
                                <p class="text-xs text-gray-400 mt-1">JPG / PNG, up to 2 MB.</p>
                                @error('logo') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">School Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="e.g. Green Valley Public School"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('name') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Email --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model="email" placeholder="school@example.com"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('email') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        {{-- Mobile --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mobile</label>
                            <input type="text" wire:model="mobile" maxlength="10" inputmode="numeric"
                                x-on:input="$event.target.value = $event.target.value.replace(/[^0-9]/g,'')"
                                placeholder="10-digit number"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('mobile') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea wire:model="address" rows="2" placeholder="Full address"
                            class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 resize-y"></textarea>
                        @error('address') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        {{-- Classes --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Classes</label>
                            <input type="text" wire:model="classes" placeholder="e.g. Nursery – 12th"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('classes') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        {{-- No of students --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">No. of Students</label>
                            <input type="number" min="0" wire:model="noOfStudents" placeholder="e.g. 850"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('noOfStudents') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        {{-- Avg fee --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Avg Fee (₹)</label>
                            <input type="number" min="0" step="0.01" wire:model="avgFee" placeholder="e.g. 25000"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                            @error('avgFee') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-3 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save,logo"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update School' : 'Add School' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ REMARK MODAL ══════════════════ --}}
    @if ($remarkTargetId !== null)
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-amber-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-amber-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Add a Remark</h3>
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-2">Adding a remark marks this school as <span class="font-semibold text-rose-600">Remarked</span> (not approved).</p>
                    <textarea wire:model="remarkText" rows="4" placeholder="Reason / note about this school…"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 resize-y"></textarea>
                    @error('remarkText') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="saveRemark"
                        class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition-colors">Save Remark</button>
                    <button wire:click="cancelRemark"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE CONFIRM ══════════════════ --}}
    @if ($pendingDelete !== null)
        <div class="fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete School</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">Are you sure you want to remove this school from the listing? This also removes its logo and cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteSchool"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">Yes, Delete</button>
                    <button wire:click="cancelDelete"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
