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
                <div class="flex items-center gap-3 flex-shrink-0">
                    {{-- Compact analytics (kept small so the header stays tidy) --}}
                    <div class="hidden md:flex items-center gap-3 mr-1">
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Total <b class="text-gray-900">{{ number_format($stats['total']) }}</b></span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Approved <b class="text-gray-900">{{ number_format($stats['approved']) }}</b></span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending <b class="text-gray-900">{{ number_format($stats['pending']) }}</b></span>
                        <span class="inline-flex items-center gap-1.5 text-xs text-gray-600"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Remarked <b class="text-gray-900">{{ number_format($stats['rejected']) }}</b></span>
                    </div>
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
                                <td class="px-4 py-3 max-w-[240px]">
                                    @if ($school->status === 'approved')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-green-100 text-green-700">Approved</span>
                                        @if ($school->payment_type)
                                            <div class="text-[11px] text-gray-500 mt-1">
                                                {{ $school->payment_type_label }}@if ($school->payment_amount !== null) · ₹{{ number_format((float) $school->payment_amount) }}@endif
                                            </div>
                                        @endif
                                    @elseif ($school->status === 'rejected')
                                        {{-- Remarked: show the remark text itself, not a badge --}}
                                        <div class="text-xs text-rose-600 font-medium">{{ $school->remark ?: 'Remarked' }}</div>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-700">Pending</span>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button wire:click="openView({{ $school->id }})"
                                            class="px-2 py-1 text-[11px] font-medium text-violet-600 border border-violet-200 rounded-md hover:bg-violet-50 transition-colors">View</button>
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

    {{-- ══════════════════ VIEW + STATUS PANEL (slide-in) ══════════════════ --}}
    @if ($showViewPanel && $viewSchool)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeViewPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-11 h-11 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center flex-shrink-0">
                            @if ($viewSchool->logo)
                                <img src="{{ $viewSchool->logo }}" class="w-full h-full object-cover" alt="">
                            @else
                                <span class="text-lg">🏫</span>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewSchool->name }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $viewSchool->location }}</p>
                        </div>
                    </div>
                    <button wire:click="closeViewPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                    {{-- Details --}}
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">School Details</div>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                            <div class="col-span-2">
                                <dt class="text-xs text-gray-400">Address</dt>
                                <dd class="text-gray-800">{{ $viewSchool->address ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Email</dt>
                                <dd class="text-gray-800 break-words">{{ $viewSchool->email ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Mobile</dt>
                                <dd class="text-gray-800">{{ $viewSchool->mobile ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Classes</dt>
                                <dd class="text-gray-800">{{ $viewSchool->classes ?: '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">No. of Students</dt>
                                <dd class="text-gray-800">{{ $viewSchool->no_of_students !== null ? number_format($viewSchool->no_of_students) : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-gray-400">Avg Fee</dt>
                                <dd class="text-gray-800">{{ $viewSchool->avg_fee !== null ? '₹' . number_format((float) $viewSchool->avg_fee) : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    {{-- Status marking --}}
                    <div class="border-t border-gray-100 pt-5">
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-3">Mark Status</div>

                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="statusChoice" value="pending" class="peer sr-only">
                                <div class="text-center text-xs font-semibold py-2 rounded-lg border border-gray-200 text-gray-600 peer-checked:bg-amber-50 peer-checked:border-amber-300 peer-checked:text-amber-700 transition-colors">Pending</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="statusChoice" value="approved" class="peer sr-only">
                                <div class="text-center text-xs font-semibold py-2 rounded-lg border border-gray-200 text-gray-600 peer-checked:bg-green-50 peer-checked:border-green-300 peer-checked:text-green-700 transition-colors">Approved</div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="statusChoice" value="rejected" class="peer sr-only">
                                <div class="text-center text-xs font-semibold py-2 rounded-lg border border-gray-200 text-gray-600 peer-checked:bg-rose-50 peer-checked:border-rose-300 peer-checked:text-rose-700 transition-colors">Remark</div>
                            </label>
                        </div>
                        @error('statusChoice') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror

                        {{-- Approved → payment type + amount --}}
                        @if ($statusChoice === 'approved')
                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 rounded-xl bg-green-50/60 border border-green-100 p-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Type <span class="text-rose-500">*</span></label>
                                    <select wire:model="paymentType"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-green-400 focus:border-green-400">
                                        <option value="">Select type…</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="one_time">One Time</option>
                                        <option value="student_based">Student Based</option>
                                    </select>
                                    @error('paymentType') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount (₹) <span class="text-rose-500">*</span></label>
                                    <input type="number" min="0" step="0.01" wire:model="paymentAmount" placeholder="e.g. 25000"
                                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-green-400">
                                    @error('paymentAmount') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>
                        @endif

                        {{-- Remark → note --}}
                        @if ($statusChoice === 'rejected')
                            <div class="mt-4 rounded-xl bg-rose-50/60 border border-rose-100 p-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Remark <span class="text-rose-500">*</span></label>
                                <textarea wire:model="remarkText" rows="3" placeholder="Reason / note about this school…"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-rose-400 focus:border-rose-400 resize-y"></textarea>
                                <p class="text-xs text-gray-400 mt-1">This remark will show in the status column instead of an Approved badge.</p>
                                @error('remarkText') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-100 flex items-center justify-end gap-3 flex-shrink-0">
                    <button wire:click="openEdit({{ $viewSchool->id }})" type="button" class="px-4 py-2 text-sm font-medium text-indigo-600 hover:text-indigo-800">Edit details</button>
                    <button wire:click="closeViewPanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">Close</button>
                    <button wire:click="saveStatus" type="button" wire:loading.attr="disabled" wire:target="saveStatus"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 disabled:opacity-60 text-white text-sm font-semibold px-5 py-2 rounded-lg">
                        <span wire:loading.remove wire:target="saveStatus">Save Status</span>
                        <span wire:loading wire:target="saveStatus">Saving…</span>
                    </button>
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
