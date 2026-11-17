<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — Add Visitor opens the slide-in ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Reception</h1>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-4 text-sm text-gray-500 mr-2 divide-x divide-gray-200">
                    <button wire:click="showToday" class="pr-4 hover:text-gray-800">Today: <strong class="text-gray-800">{{ $today }}</strong></button>
                    <button wire:click="showInside" class="px-4 hover:text-gray-800">Inside now: <strong class="text-emerald-600">{{ $inside }}</strong></button>
                    <span class="pl-4 hidden sm:inline">Total: <strong class="text-gray-800">{{ $total }}</strong></span>
                </div>
                <button wire:click="openAdd"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span class="hidden sm:inline">Add Visitor</span>
                    <span class="sm:hidden">Add</span>
                </button>
            </div>
        </div>

        {{-- Filter bar: a day, inside / gone, then a name, mobile or whom they met. --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <input type="date" wire:model.live="filterDate"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <select wire:model.live="filterStatus"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                    <option value="">All Visitors</option>
                    <option value="inside">Inside now</option>
                    <option value="left">Left</option>
                </select>
                <div class="relative">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, mobile, person met"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-2.5 py-1.5 text-gray-700 w-56">
                </div>
                @if ($filterDate !== '' || $filterStatus !== '' || $search !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ LIST — the visitor book, newest first ══════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if ($visitors->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Visitor</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">To Meet</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Purpose</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">In</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Out</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($visitors as $i => $v)
                                <tr class="hover:bg-gray-50/70 transition-colors" wire:key="visitor-{{ $v->id }}">
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $visitors->firstItem() + $i }}</span></td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-900 truncate max-w-[13rem]">{{ $v->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $v->mobile }}</p>
                                        @if ($v->email)
                                            <p class="text-xs text-gray-400 truncate max-w-[13rem]">{{ $v->email }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3"><span class="text-sm text-gray-700">{{ $v->whom_to_meet ?: '—' }}</span></td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-800">{{ $v->purpose }}</p>
                                        @if ($v->details)
                                            <p class="text-xs text-gray-400 truncate max-w-[14rem]">{{ $v->details }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <p class="text-sm text-gray-800 tabular-nums">{{ $v->in_at->format('h:i A') }}</p>
                                        <p class="text-xs text-gray-400">{{ $v->in_at->format('d M Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($v->out_at)
                                            <p class="text-sm text-gray-800 tabular-nums">{{ $v->out_at->format('h:i A') }}</p>
                                            <p class="text-xs text-gray-400">{{ $v->stayed() }}</p>
                                        @else
                                            <button wire:click="markOut({{ $v->id }})" title="They are leaving now"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md hover:bg-emerald-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Inside · Mark Out
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="view({{ $v->id }})" title="View" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <button wire:click="openEdit({{ $v->id }})" title="Edit" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $v->id }})" title="Delete" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($visitors->hasPages())
                    <div class="px-5 py-3 border-t border-gray-100">{{ $visitors->links() }}</div>
                @endif
            @else
                <div class="p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-icon name="building-office" class="w-7 h-7 text-gray-400" />
                    </div>
                    @if ($total === 0)
                        <p class="text-sm text-gray-600 font-medium">No visitors yet.</p>
                        <button wire:click="openAdd" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add a visitor →</button>
                    @else
                        <p class="text-sm text-gray-600 font-medium">No visitor matches these filters.</p>
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
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Visitor' : 'Add Visitor' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Who came, to meet whom, and why</p>
                    </div>
                    <button wire:click="closeForm" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Visitor Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="name" maxlength="120" placeholder="Full name" class="{{ $field }}">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Mobile <span class="text-red-500">*</span></label>
                            <input type="tel" wire:model="mobile" maxlength="20" placeholder="10-digit mobile" class="{{ $field }}">
                            @error('mobile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Email</label>
                            <input type="email" wire:model="email" maxlength="150" placeholder="Optional" class="{{ $field }}">
                            @error('email') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Whom to Meet</label>
                            <input type="text" wire:model="whomToMeet" maxlength="120" placeholder="e.g. Principal, Class teacher of 5-A" class="{{ $field }}">
                            @error('whomToMeet') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Purpose <span class="text-red-500">*</span></label>
                            <select wire:model.live="purpose" class="{{ $field }}">
                                <option value="">Select purpose</option>
                                @foreach (\App\Models\Admin\ReceptionVisitor::PURPOSES as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                            @error('purpose') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">What was the work? @if ($purpose === 'Other')<span class="text-red-500">*</span>@endif</label>
                        <textarea wire:model="details" maxlength="1000" rows="3" placeholder="e.g. Came to ask about admission in Class 6" class="{{ $field }}"></textarea>
                        @error('details') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="inDate" class="{{ $field }}">
                            @error('inDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Time In <span class="text-red-500">*</span></label>
                            <input type="time" wire:model="inTime" class="{{ $field }}">
                            @error('inTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Time Out</label>
                            <input type="time" wire:model="outTime" class="{{ $field }}">
                            @error('outTime') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400 -mt-2">Leave Time Out empty while they are inside; Mark Out on the list records it when they go.</p>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeForm" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Visitor' : 'Save Visitor' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ VIEW SLIDE-IN ══════════ --}}
    @if ($viewing)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewing->name }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $viewing->in_at->format('d M Y') }}</p>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        @foreach ([
                            'Mobile'       => $viewing->mobile,
                            'Email'        => $viewing->email ?: '—',
                            'Whom to Meet' => $viewing->whom_to_meet ?: '—',
                            'Purpose'      => $viewing->purpose,
                            'Work'         => $viewing->details ?: '—',
                            'Came In'      => $viewing->in_at->format('d M Y · h:i A'),
                            'Left'         => $viewing->out_at ? $viewing->out_at->format('h:i A') . ' · stayed ' . $viewing->stayed() : 'Still inside',
                            'Entered By'   => $viewing->addedBy?->name ?: '—',
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
                        @if (!$viewing->out_at)
                            <button type="button" wire:click="markOut({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-50 rounded-md">Mark Out</button>
                        @endif
                        <button type="button" wire:click="closeView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
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
                    <h3 class="text-sm font-bold text-gray-900">Delete Visitor</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">This visit will be taken out of the visitor book. This cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteVisitor" wire:loading.attr="disabled" wire:target="deleteVisitor"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60">Yes, Delete</button>
                    <button wire:click="cancelDelete" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

</div>
