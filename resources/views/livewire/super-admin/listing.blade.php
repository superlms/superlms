<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════ HEADER ══════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-12">
                <div class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('website-image/Group 11525.png') }}" alt="SUPERLMS"
                        class="w-11 h-11 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">School Listing</h1>
                    </div>
                </div>
                <div class="flex items-center gap-4 flex-shrink-0">
                    {{-- Analytics (enquiries-style) --}}
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ number_format($stats['total']) }}</strong></span>
                        <span class="px-4">Approved: <strong class="text-emerald-600">{{ number_format($stats['approved']) }}</strong></span>
                        <span class="px-4">Pending: <strong class="text-amber-500">{{ number_format($stats['pending']) }}</strong></span>
                        <span class="pl-4">Remarked: <strong class="text-rose-500">{{ number_format($stats['rejected']) }}</strong></span>
                    </div>
                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Add School
                    </button>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ number_format($stats['total']) }}</strong></span>
                <span>Approved: <strong class="text-emerald-600">{{ number_format($stats['approved']) }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ number_format($stats['pending']) }}</strong></span>
                <span>Remarked: <strong class="text-rose-500">{{ number_format($stats['rejected']) }}</strong></span>
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

                <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, email or mobile…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                {{-- Location filter --}}
                <select wire:model.live="locationFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}">{{ $loc }}</option>
                    @endforeach
                </select>

                {{-- Status filter --}}
                <select wire:model.live="statusFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
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
                                            @if ($school->email)
                                                <div class="text-xs text-gray-400 truncate max-w-[220px]">{{ $school->email }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                {{-- Location (plain text) --}}
                                <td class="px-4 py-3 text-xs text-gray-700">{{ $school->location ?: '—' }}</td>
                                {{-- Contact --}}
                                <td class="px-4 py-3">
                                    <div class="text-xs text-gray-700">{{ $school->mobile ?: '—' }}</div>
                                    @if ($school->address)
                                        <div class="text-xs text-gray-400 truncate max-w-[200px]">{{ $school->address }}</div>
                                    @endif
                                </td>
                                {{-- Classes --}}
                                <td class="px-4 py-3 text-xs text-gray-600">{{ $school->classes ?: '—' }}</td>
                                {{-- Students --}}
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $school->no_of_students !== null ? number_format($school->no_of_students) : '—' }}</td>
                                {{-- Avg fee --}}
                                <td class="px-4 py-3 text-right text-xs text-gray-700">{{ $school->avg_fee !== null ? '₹' . number_format((float) $school->avg_fee) : '—' }}</td>
                                {{-- Status — plain coloured text, no badges; an approved school's
                                     payment under it, a remarked one's remark itself. --}}
                                <td class="px-4 py-3 max-w-[240px]">
                                    @if ($school->status === 'approved')
                                        <div class="text-xs text-emerald-600 font-medium">Approved</div>
                                        @if ($school->payment_type)
                                            <div class="text-[11px] text-gray-500 mt-0.5">
                                                {{ $school->payment_type_label }}@if ($school->payment_amount !== null) · ₹{{ number_format((float) $school->payment_amount) }}@endif
                                            </div>
                                        @endif
                                    @elseif ($school->status === 'rejected')
                                        <div class="text-xs text-rose-600 font-medium">{{ $school->remark ?: 'Remarked' }}</div>
                                    @else
                                        <div class="text-xs text-amber-600 font-medium">Pending</div>
                                    @endif
                                </td>
                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button wire:click="openView({{ $school->id }})" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="openEdit({{ $school->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $school->id }})" title="Delete"
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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

    {{-- ══════════════════ VIEW + STATUS PANEL (slide-in) ══════════════════
         In the Website Enquiry view's look: the school in the header, then
         plain label / value rows; the status (same choices, fields and Save
         Status) under a line. --}}
    @if ($showViewPanel && $viewSchool)
        @php
            $lvStatus = in_array($viewSchool->status, ['approved', 'rejected'], true) ? $viewSchool->status : 'pending';
            $lvName   = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Remarked'][$lvStatus];
            $lvTone   = ['pending' => 'text-amber-600', 'approved' => 'text-emerald-600', 'rejected' => 'text-rose-600'][$lvStatus];
            $lvRows   = [
                'Location'        => $viewSchool->location ?: '—',
                'Email'           => $viewSchool->email ?: '—',
                'Mobile'          => $viewSchool->mobile ?: '—',
                'Address'         => $viewSchool->address ?: '—',
                'Classes'         => $viewSchool->classes ?: '—',
                'No. of Students' => $viewSchool->no_of_students !== null ? number_format($viewSchool->no_of_students) : '—',
                'Avg Fee'         => $viewSchool->avg_fee !== null ? '₹' . number_format((float) $viewSchool->avg_fee) : '—',
            ];
            $lvIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewSchool->name ?: 'School Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">School listing · {{ $lvName }}</p>
                    </div>
                    <button wire:click="closeViewPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Body — plain label / value rows --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @if ($viewSchool->logo)
                        <div class="grid grid-cols-3 gap-3 text-sm items-center">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Logo</span>
                            <span class="col-span-2"><img src="{{ $viewSchool->logo }}" alt="{{ $viewSchool->name }}" class="w-12 h-12 rounded-md object-cover border border-gray-200"></span>
                        </div>
                    @endif
                    @foreach ($lvRows as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium whitespace-pre-line break-words">{{ $value }}</span>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Status</span>
                        <span class="col-span-2 font-medium {{ $lvTone }}">{{ $lvName }}</span>
                    </div>
                    @if ($lvStatus === 'approved' && $viewSchool->payment_type)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Payment</span>
                            <span class="col-span-2 text-gray-800 font-medium">{{ $viewSchool->payment_type_label }}@if ($viewSchool->payment_amount !== null) · ₹{{ number_format((float) $viewSchool->payment_amount) }}@endif</span>
                        </div>
                    @endif
                    @if ($lvStatus === 'rejected' && $viewSchool->remark)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">Remark</span>
                            <span class="col-span-2 text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $viewSchool->remark }}</span>
                        </div>
                    @endif
                    <div class="grid grid-cols-3 gap-3 text-sm">
                        <span class="text-xs text-gray-400 uppercase tracking-wider">Added on</span>
                        <span class="col-span-2 text-gray-800 font-medium">{{ $viewSchool->created_at?->format('d M Y · g:i A') ?? '—' }}</span>
                    </div>

                    {{-- Status, under a line: Pending / Approved (payment type and amount) / Remark (the note) --}}
                    <div class="pt-4 mt-2 border-t border-gray-100 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Mark Status</label>
                            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                                @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Remark'] as $val => $label)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" name="listingStatus" wire:model.live="statusChoice" value="{{ $val }}"
                                            class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            @error('statusChoice')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if ($statusChoice === 'approved')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Type <span class="text-red-500">*</span></label>
                                    <select wire:model="paymentType" class="{{ $lvIn }}">
                                        <option value="">Select type…</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="one_time">One Time</option>
                                        <option value="student_based">Student Based</option>
                                    </select>
                                    @error('paymentType')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (₹) <span class="text-red-500">*</span></label>
                                    <input type="number" min="0" step="0.01" wire:model="paymentAmount" placeholder="e.g. 25000" class="{{ $lvIn }}">
                                    @error('paymentAmount')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            </div>
                        @endif

                        @if ($statusChoice === 'rejected')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">Remark <span class="text-red-500">*</span></label>
                                <textarea wire:model="remarkText" rows="4" placeholder="Reason / note about this school…" class="{{ $lvIn }} resize-y"></textarea>
                                <p class="mt-1 text-xs text-gray-400">This remark shows in the list's status column.</p>
                                @error('remarkText')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                    <button wire:click="openEdit({{ $viewSchool->id }})" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Edit details</button>
                    <div class="flex items-center gap-2">
                        <button wire:click="closeViewPanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
                        <button wire:click="saveStatus" type="button" wire:loading.attr="disabled" wire:target="saveStatus"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                            <span wire:loading.remove wire:target="saveStatus">Save Status</span>
                            <span wire:loading wire:target="saveStatus">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════ DELETE CONFIRM ══════════════════ --}}
    @if ($pendingDelete !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
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
