<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — Add Complaint opens the slide-in ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-3 sm:min-h-9">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Complaints</h1>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden sm:inline text-sm text-gray-500">Total: <strong class="text-gray-800">{{ $total }}</strong></span>
                <button wire:click="openAdd"
                    class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    <span class="hidden sm:inline">Add Complaint</span>
                    <span class="sm:hidden">Add</span>
                </button>
            </div>
        </div>

        {{-- Filter bar: class, section, then a word of the title or the student's name / admission no. --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
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
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search title, student, adm. no."
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-2.5 py-1.5 text-gray-700 w-56">
                </div>
                @if ($filterStandard !== '' || $filterSection !== '' || $search !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════ LIST — every complaint, newest first ══════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if ($complaints->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Complaint</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">File</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($complaints as $i => $c)
                                @php
                                    $sd    = $c->studentDetail;
                                    $sName = $sd?->full_name ?: 'Student removed';
                                    $sImg  = $sd?->user?->image ?: $sd?->image;
                                @endphp
                                <tr class="hover:bg-gray-50/70 transition-colors" wire:key="complaint-{{ $c->id }}">
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $complaints->firstItem() + $i }}</span></td>
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
                                                <p class="text-sm font-semibold text-gray-900 truncate max-w-[12rem]">{{ $sName }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $sd?->admission_no ?: '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3"><span class="text-sm text-gray-700 whitespace-nowrap">{{ $c->standard?->name ?? '—' }}{{ $c->section?->name ? ' - ' . $c->section->name : '' }}</span></td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm font-semibold text-gray-800 truncate max-w-[18rem]">{{ $c->title }}</p>
                                        <p class="text-xs text-gray-400 truncate max-w-[18rem]">{{ $c->description }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($c->file_path)
                                            <a href="{{ $c->fileUrl() }}" target="_blank" rel="noopener" data-no-download-loader title="{{ $c->file_name }}"
                                                class="inline-flex items-center gap-1 text-xs font-medium text-blue-600 hover:text-blue-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                                Open
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-300">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        {{ $c->created_at->format('d M Y') }}
                                        <p class="text-gray-400">{{ $c->created_at->format('h:i A') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="view({{ $c->id }})" title="View" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            </button>
                                            <button wire:click="openEdit({{ $c->id }})" title="Edit" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $c->id }})" title="Delete" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($complaints->hasPages())
                    <div class="px-5 py-3 border-t border-gray-100">{{ $complaints->links() }}</div>
                @endif
            @else
                <div class="p-12 text-center">
                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                        <x-icon name="exclamation-circle" class="w-7 h-7 text-gray-400" />
                    </div>
                    @if ($total === 0)
                        <p class="text-sm text-gray-600 font-medium">No complaints yet.</p>
                        <button wire:click="openAdd" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-800">Add a complaint →</button>
                    @else
                        <p class="text-sm text-gray-600 font-medium">No complaint matches these filters.</p>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN ══════════ --}}
    @if ($formOpen)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeForm"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Complaint' : 'Add Complaint' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick the student, then say what happened</p>
                    </div>
                    <button wire:click="closeForm" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @include('livewire.admin._partials.student-picker')

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="title" maxlength="150" placeholder="e.g. Misbehaved in class"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Description <span class="text-red-500">*</span></label>
                        <textarea wire:model="description" maxlength="5000" rows="5" placeholder="What happened, when, and who reported it"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Attach File <span class="font-normal text-gray-400">(optional — photo, PDF or Word, up to 10 MB)</span></label>
                        @if ($attachment)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                                <span class="text-sm text-gray-700 truncate">{{ $attachment->getClientOriginalName() }}</span>
                                <button type="button" wire:click="dropAttachment" class="text-xs font-medium text-red-600 hover:text-red-700 flex-shrink-0">Remove</button>
                            </div>
                        @elseif ($editing && $editing->file_path && !$removeFile)
                            <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 px-3 py-2">
                                <a href="{{ $editing->fileUrl() }}" target="_blank" rel="noopener" data-no-download-loader class="text-sm text-blue-600 hover:text-blue-800 truncate">{{ $editing->file_name ?: 'Attached file' }}</a>
                                <button type="button" wire:click="dropAttachment" class="text-xs font-medium text-red-600 hover:text-red-700 flex-shrink-0">Remove</button>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Pick another file below to replace it.</p>
                        @endif
                        @if (!$attachment)
                            <input type="file" wire:model="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx"
                                class="mt-1 block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @endif
                        <p wire:loading wire:target="attachment" class="text-xs text-gray-500 mt-1">Uploading…</p>
                        @error('attachment') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeForm" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save,attachment"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Complaint' : 'Save Complaint' }}</span>
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
                                {{ $viewing->standard?->name ?? '—' }}{{ $viewing->section?->name ? ' - ' . $viewing->section->name : '' }}
                                · Adm {{ $vs?->admission_no ?: '—' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Title</p>
                            <p class="mt-1 text-sm font-semibold text-gray-900 break-words">{{ $viewing->title }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Description</p>
                            <p class="mt-1 text-sm text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $viewing->description }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Added</p>
                            <p class="mt-1 text-sm text-gray-800">{{ $viewing->created_at->format('d M Y · h:i A') }}{{ $viewing->addedBy?->name ? ' · by ' . $viewing->addedBy->name : '' }}</p>
                        </div>
                        <div class="px-4 py-3">
                            <p class="text-[11px] text-gray-400 uppercase tracking-wider">Attached File</p>
                            @if ($viewing->file_path)
                                @if ($viewing->fileIsImage())
                                    <a href="{{ $viewing->fileUrl() }}" target="_blank" rel="noopener" data-no-download-loader>
                                        <img src="{{ $viewing->fileUrl() }}" alt="" class="mt-2 max-h-64 rounded-lg border border-gray-200">
                                    </a>
                                @endif
                                <a href="{{ $viewing->fileUrl() }}" target="_blank" rel="noopener" data-no-download-loader
                                    class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 break-all">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" /></svg>
                                    {{ $viewing->file_name ?: 'Open file' }}
                                </a>
                            @else
                                <p class="mt-1 text-sm text-gray-400">No file attached</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="confirmDelete({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                        <button type="button" wire:click="openEdit({{ $viewing->id }})" class="px-4 py-2 text-sm font-medium text-amber-600 hover:bg-amber-50 rounded-md">Edit</button>
                    </div>
                    <button type="button" wire:click="closeView" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
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
                    <h3 class="text-sm font-bold text-gray-900">Delete Complaint</h3>
                </div>
                <div class="p-5">
                    <p class="text-sm text-gray-600">This complaint and its attached file will be deleted. This cannot be undone.</p>
                </div>
                <div class="px-5 pb-5 flex items-center gap-2">
                    <button wire:click="deleteComplaint" wire:loading.attr="disabled" wire:target="deleteComplaint"
                        class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors disabled:opacity-60">Yes, Delete</button>
                    <button wire:click="cancelDelete" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">Cancel</button>
                </div>
            </div>
        </div>
    @endif

</div>
