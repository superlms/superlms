<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — as the school panel's Documents: title, the count
         and Add Doc on one row; the filters under a line ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Documents</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3">
                        <span>Total: <strong class="text-gray-800">{{ $documents->total() }}</strong></span>
                    </div>

                    <button wire:click="openCreate"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Add Doc</span>
                        <span class="sm:hidden">New</span>
                    </button>
                </div>
            </div>

            {{-- Mobile/Tablet count --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $documents->total() }}</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" name="documents_search"
                    autocomplete="off" placeholder="Search title, file, description..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 flex-[1_1_8rem] min-w-0 max-w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="filterOrg" @disabled($orgLocked)
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] text-gray-500">From</label>
                    <input type="date" wire:model.live="filterFrom"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                <div class="flex items-center gap-1.5">
                    <label class="text-[11px] text-gray-500">To</label>
                    <input type="date" wire:model.live="filterTo"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                </div>
                @if ($search || (!$orgLocked && $filterOrg !== '') || $filterFrom || $filterTo)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    @php
        // The file's kind from its name: a coloured tile with its label, as the school panel's Documents shows it.
        $docType = function (?string $name): array {
            $ext = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));
            return match (true) {
                $ext === 'pdf'                                                   => ['PDF', 'bg-red-50 text-red-600'],
                in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true)              => ['DOC', 'bg-blue-50 text-blue-600'],
                in_array($ext, ['xls', 'xlsx', 'csv', 'ods'], true)              => ['XLS', 'bg-emerald-50 text-emerald-600'],
                in_array($ext, ['ppt', 'pptx', 'odp'], true)                     => ['PPT', 'bg-orange-50 text-orange-600'],
                in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'svg', 'bmp'], true) => ['IMG', 'bg-purple-50 text-purple-600'],
                in_array($ext, ['zip', 'rar', '7z'], true)                       => ['ZIP', 'bg-amber-50 text-amber-600'],
                default => [$ext !== '' ? strtoupper(substr($ext, 0, 4)) : 'FILE', 'bg-gray-100 text-gray-600'],
            };
        };
    @endphp

    <div class="p-4 sm:p-6">

        {{-- ══════════ LISTING — the school panel's Documents table: S.No, the
             document (its kind, the title, the size under it — not the file's
             name), description, who it went to, the date and the actions ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Sent To</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Added On</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($documents as $i => $doc)
                            @php [$typeLabel, $typeClass] = $docType($doc->file_name); @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors" wire:key="doc-{{ $doc->id }}">
                                <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $documents->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-10 h-10 rounded-lg {{ $typeClass }} flex flex-col items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-[9px] font-bold leading-none mt-0.5">{{ $typeLabel }}</span>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate max-w-[300px]" title="{{ $doc->title }}">{{ $doc->title }}</p>
                                            @if ($doc->readable_size !== '—')
                                                <p class="text-xs text-gray-400">{{ $doc->readable_size }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <span class="line-clamp-2">{{ $doc->description ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if ($doc->audience_scope === 'private')
                                        <span class="text-gray-500">Kept private</span>
                                    @elseif ($doc->audience_scope === 'all')
                                        <span class="text-emerald-600">All schools</span>
                                    @else
                                        <span class="text-gray-600 line-clamp-2">{{ $doc->organizations->pluck('name')->take(2)->join(', ') }}@if ($doc->organizations->count() > 2) +{{ $doc->organizations->count() - 2 }}@endif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $doc->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                        <button wire:click="downloadDocument({{ $doc->id }})" title="Download"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></button>
                                        <button wire:click="edit({{ $doc->id }})" title="Edit"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                                        <button wire:click="confirmDelete({{ $doc->id }})" title="Delete"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm text-gray-500">No documents yet. Click <strong>Add Doc</strong> to upload one.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($documents->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN PANEL — the school panel's form, with
         who gets it as plain radio buttons ══════════ --}}
    @if ($showPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Document' : 'Add Document' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload a file and choose which schools receive it</p>
                    </div>
                    <button wire:click="closePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Body --}}
                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="title" placeholder="e.g. Circular — Summer Vacation"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('title')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea wire:model="description" rows="3" placeholder="Short note about this document"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 resize-y"></textarea>
                            @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                File @if (!$editId)<span class="text-red-500">*</span>@endif
                                <span class="text-gray-400 font-normal">(any document or image, max 5 MB)</span>
                            </label>
                            @if ($editId && $existingFileName)
                                <p class="text-xs text-gray-500 mb-1.5">Current: <strong class="text-gray-700 font-medium">{{ $existingFileName }}</strong> — upload a new file to replace it.</p>
                            @endif
                            <input type="file" wire:model="file"
                                class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <div wire:loading wire:target="file" class="text-xs text-blue-600 mt-1">Uploading...</div>
                            @error('file')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div class="border-t border-gray-100 pt-5">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Who gets this document?</label>

                            {{-- Keep it here, or push it down to schools — plain radio buttons. --}}
                            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mb-3">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="radio" name="documentAudience" wire:click="$set('audienceScope', 'private')" @checked($audienceScope === 'private')
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    Keep with me
                                </label>
                                @unless ($orgLocked)
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="radio" name="documentAudience" wire:click="$set('audienceScope', 'all')" @checked($audienceScope === 'all')
                                            class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                        All schools
                                    </label>
                                @endunless
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                    <input type="radio" name="documentAudience" wire:click="$set('audienceScope', 'selected')" @checked($audienceScope === 'selected')
                                        class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                    {{ $orgLocked ? 'Send to my school' : 'Specific schools' }}
                                </label>
                            </div>

                            @if ($audienceScope === 'private')
                                <p class="text-xs text-gray-500">
                                    Stored here only — no school sees it and no notification is sent.
                                    You can send it later by editing this document.
                                </p>
                            @elseif ($orgLocked)
                                <p class="text-xs text-gray-500">
                                    Goes to <strong class="text-gray-800">{{ $organizations->first()->name ?? 'your school' }}</strong>.
                                </p>
                            @else

                                @if ($audienceScope === 'selected')
                                    <div class="max-h-56 overflow-y-auto border border-gray-300 rounded-md divide-y divide-gray-100">
                                        @foreach ($organizations as $org)
                                            <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer" wire:key="pick-org-{{ $org->id }}">
                                                <input type="checkbox" wire:click="toggleOrg({{ $org->id }})"
                                                    @checked(in_array($org->id, $selectedOrgs))
                                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="text-sm text-gray-700">{{ $org->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('selectedOrgs')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save,file"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">
                            {{ $editId ? 'Update Document' : ($audienceScope === 'private' ? 'Save Document' : 'Send Document') }}
                        </span>
                        <span wire:loading wire:target="save">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deleteId)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[60] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                <div class="mx-auto w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mb-3">
                    <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14A2 2 0 0021 17.05L13.86 4.9a2 2 0 00-3.46 0L3.07 17.05A2 2 0 004.93 19z"/></svg>
                </div>
                <h3 class="text-base font-bold text-gray-900">Delete this document?</h3>
                <p class="text-sm text-gray-500 mt-1">This removes it from every school it was sent to. This can't be undone.</p>
                <div class="flex items-center justify-center gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="delete" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
