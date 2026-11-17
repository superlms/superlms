<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — New Gate Pass opens the slide-in ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Gatepass</h1>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-4 text-sm text-gray-500 mr-2 divide-x divide-gray-200">
                    <button wire:click="showToday" class="pr-4 hover:text-gray-800">Today: <strong class="text-gray-800">{{ $today }}</strong></button>
                    <span class="pl-4">Total: <strong class="text-gray-800">{{ $total }}</strong></span>
                </div>
                <button wire:click="openAdd"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span class="hidden sm:inline">New Gate Pass</span>
                    <span class="sm:hidden">New</span>
                </button>
            </div>
        </div>

        {{-- Filter bar: a day, class, section, then the student, the person who took them or the pass number. --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="date" wire:model.live="filterDate"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <select wire:model.live="filterStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Classes</option>
                    @foreach ($filterStandards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                </select>
                <select data-sole-section wire:model.live="filterSection" @disabled($filterStandard === '')
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                    <option value="">All Sections</option>
                    @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                </select>
                <div class="relative">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search student, adm. no., pass no."
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-2.5 py-1.5 text-gray-700 w-56">
                </div>
                @if ($filterDate !== '' || $filterStandard !== '' || $filterSection !== '' || $search !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ LIST — every pass, newest first ══════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if ($passes->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pass No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Out</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Taken By</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Back</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($passes as $p)
                                @php
                                    $sd    = $p->studentDetail;
                                    $sName = $sd?->full_name ?: 'Student removed';
                                    $sImg  = $sd?->user?->image ?: $sd?->image;
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors" wire:key="pass-{{ $p->id }}">
                                    <td class="px-4 py-3"><span class="text-sm font-semibold text-red-700 tabular-nums whitespace-nowrap">{{ $p->number() }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($sImg)
                                                <img src="{{ $sImg }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ mb_strtoupper(mb_substr($sName, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate max-w-[11rem]">{{ $sName }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $sd?->admission_no ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><span class="text-sm text-gray-700 whitespace-nowrap">{{ $p->standard?->name ?? '—' }}{{ $p->section?->name ? ' - ' . $p->section->name : '' }}</span></td>
                                    <td class="px-4 py-3"><p class="text-sm text-gray-800 truncate max-w-[13rem]">{{ $p->reason }}</p></td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm text-gray-800 tabular-nums">{{ $p->out_at->format('h:i A') }}</p>
                                        <p class="text-xs text-gray-400">{{ $p->out_at->format('d M Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-800 truncate max-w-[11rem]">{{ $p->picked_by_name }}</p>
                                        <p class="text-xs text-gray-400">{{ implode(' · ', array_filter([$p->picked_by_relation, $p->picked_by_mobile])) ?: '—' }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($p->returned_at)
                                            <span class="text-sm text-gray-800 tabular-nums">{{ $p->returned_at->format('h:i A') }}</span>
                                        @else
                                            <button wire:click="markReturned({{ $p->id }})" title="The student is back now"
                                                class="px-2.5 py-1 text-xs font-medium text-gray-600 bg-white border border-gray-200 rounded-md hover:bg-gray-50">Mark Returned</button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="view({{ $p->id }})" title="View" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <a href="{{ route('admin.gatepass.pdf', ['organization' => auth()->user()->organization_id, 'id' => $p->id]) }}" target="_blank" rel="noopener" data-no-download-loader title="Print"
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                                            </a>
                                            <button wire:click="openEdit({{ $p->id }})" title="Edit" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $p->id }})" title="Delete" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($passes->hasPages())
                    <div class="px-5 py-3 border-t border-gray-100">{{ $passes->links() }}</div>
                @endif
            @else
                <div class="p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-icon name="ticket" class="w-7 h-7 text-gray-400" />
                    </div>
                    @if ($total === 0)
                        <p class="text-sm text-gray-600 font-medium">No gate passes yet.</p>
                        <button wire:click="openAdd" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Make a gate pass →</button>
                    @else
                        <p class="text-sm text-gray-600 font-medium">No gate pass matches these filters.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN ══════════ --}}
    @if ($formOpen)
        @php
            $field = 'w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeForm"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Gate Pass' : 'New Gate Pass' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">The student, why they are going, and who is taking them</p>
                    </div>
                    <button wire:click="closeForm" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @include('livewire.admin._partials.student-picker')

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Reason <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="reason" maxlength="255" placeholder="e.g. Not feeling well, family function, doctor's appointment" class="{{ $field }}">
                        @error('reason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="outDate" class="{{ $field }}">
                            @error('outDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Time Out <span class="text-red-500">*</span></label>
                            <input type="time" wire:model="outTime" class="{{ $field }}">
                            @error('outTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Came Back</label>
                            <input type="time" wire:model="returnTime" class="{{ $field }}">
                            @error('returnTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <p class="text-xs font-semibold text-gray-600 mb-2">Taken By</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs text-gray-500 mb-1">Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="pickedByName" maxlength="120" placeholder="Who is taking the student" class="{{ $field }}">
                                @error('pickedByName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Relation</label>
                                <select wire:model="pickedByRelation" class="{{ $field }}">
                                    <option value="">Select relation</option>
                                    @foreach (\App\Models\Admin\StudentGatePass::RELATIONS as $r)
                                        <option value="{{ $r }}">{{ $r }}</option>
                                    @endforeach
                                </select>
                                @error('pickedByRelation') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Mobile</label>
                                <input type="tel" wire:model="pickedByMobile" maxlength="20" placeholder="10-digit mobile" class="{{ $field }}">
                                @error('pickedByMobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Remarks</label>
                        <textarea wire:model="remarks" maxlength="1000" rows="2" placeholder="Optional" class="{{ $field }}"></textarea>
                        @error('remarks') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeForm" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Gate Pass' : 'Create Gate Pass' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ VIEW SLIDE-IN ══════════ --}}
    @if ($viewing)
        @php
            $vs   = $viewing->studentDetail;
            $vImg = $vs?->user?->image ?: $vs?->image;
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        @if ($vImg)
                            <img src="{{ $vImg }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-indigo-600">{{ mb_strtoupper(mb_substr($vs?->full_name ?: '?', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $vs?->full_name ?: 'Student removed' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                <span class="font-semibold text-red-700">{{ $viewing->number() }}</span>
                                · {{ $viewing->standard?->name ?? '—' }}{{ $viewing->section?->name ? ' - ' . $viewing->section->name : '' }}
                                · Adm {{ $vs?->admission_no ?: '—' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        @foreach ([
                            'Reason'     => $viewing->reason,
                            'Time Out'   => $viewing->out_at->format('d M Y · h:i A'),
                            'Taken By'   => trim($viewing->picked_by_name . ($viewing->picked_by_relation ? ' (' . $viewing->picked_by_relation . ')' : '')),
                            'Mobile'     => $viewing->picked_by_mobile ?: '—',
                            'Came Back'  => $viewing->returned_at ? $viewing->returned_at->format('h:i A') : 'Not yet',
                            'Remarks'    => $viewing->remarks ?: '—',
                            'Issued By'  => $viewing->addedBy?->name ?: '—',
                        ] as $label => $value)
                            <div class="px-4 py-3 grid grid-cols-3 gap-3">
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider pt-0.5">{{ $label }}</p>
                                <p class="col-span-2 text-sm text-gray-800 whitespace-pre-line break-words">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="confirmDelete({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                        <button type="button" wire:click="openEdit({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-amber-600 hover:bg-amber-50 rounded-md">Edit</button>
                    </div>
                    <div class="flex items-center gap-2">
                        @if (!$viewing->returned_at)
                            <button type="button" wire:click="markReturned({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Mark Returned</button>
                        @endif
                        <a href="{{ route('admin.gatepass.pdf', ['organization' => auth()->user()->organization_id, 'id' => $viewing->id]) }}" target="_blank" rel="noopener" data-no-download-loader
                            class="inline-flex items-center gap-1.5 px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print Pass
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deletingId !== null)
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/40 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 bg-red-50 flex items-center gap-3">
                    <div class="w-9 h-9 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900">Delete Gate Pass</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">This gate pass will be deleted. This cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deletePass" wire:loading.attr="disabled" wire:target="deletePass"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60">Yes, Delete</button>
                    <button wire:click="cancelDelete" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

</div>
