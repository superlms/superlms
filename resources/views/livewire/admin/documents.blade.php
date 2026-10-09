<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER — as Exams and the other pages: title, counts and
         Add Doc (school tab) on one row, the two tabs under a line ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Documents</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">School: <strong class="text-gray-800">{{ $documents->total() }}</strong></span>
                        <span class="pl-4">Admin: <strong class="text-blue-600">{{ $sharedDocuments->total() }}</strong></span>
                    </div>

                    @if ($tab === 'school')
                        <button wire:click="openCreate"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Add Doc</span>
                            <span class="sm:hidden">New</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet counts --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>School: <strong class="text-gray-800">{{ $documents->total() }}</strong></span>
                <span>Admin: <strong class="text-blue-600">{{ $sharedDocuments->total() }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="setTab('school')" type="button"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $tab === 'school' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        School Documents
                    </span>
                </button>
                <button wire:click="setTab('admin')" type="button"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $tab === 'admin' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                        Admin Documents
                    </span>
                </button>
            </div>
        </div>
    </div>

    @php
        // The file's kind from its name: a coloured tile with its label, as a file manager shows it.
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
        @if ($tab === 'admin')
            {{-- ══════════ ADMIN DOCUMENTS (sent by the Super Admin — view & download only) ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Shared On</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-40">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($sharedDocuments as $i => $doc)
                            @php [$typeLabel, $typeClass] = $docType($doc->file_name); @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors" wire:key="shared-{{ $doc->id }}">
                                <td class="px-4 py-3 text-sm text-gray-500 font-medium">{{ $sharedDocuments->firstItem() + $i }}</td>
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
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ $doc->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener" title="View"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                                        <button wire:click="downloadShared({{ $doc->id }})" title="Download"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center text-gray-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                    </div>
                                    <p class="text-sm text-gray-500">No documents from SuperLMS yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($sharedDocuments->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $sharedDocuments->links() }}
                </div>
            @endif
        </div>
        @else
            {{-- ══════════ SCHOOL DOCUMENTS (the school's own — add, view, edit, download, delete) ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px]">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Document</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Description</th>
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
                                <td colspan="5" class="px-6 py-16 text-center">
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
        @endif
    </div>

    {{-- ══════════ ADD / EDIT SLIDE-IN PANEL ══════════ --}}
    @if ($showPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Document' : 'Add Document' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Add a title, description and attach a file</p>
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
                            <input type="text" wire:model="title" placeholder="e.g. Fee Structure 2026-27"
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
                                <span class="text-gray-400 font-normal">(PDF, image or any file, max 5 MB)</span>
                            </label>
                            @if ($editId && $existingFileName)
                                <p class="text-xs text-gray-500 mb-1.5">Current: <strong class="text-gray-700 font-medium">{{ $existingFileName }}</strong> — upload a new file to replace it.</p>
                            @endif
                            <input type="file" wire:model="file"
                                class="w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <div wire:loading wire:target="file" class="text-xs text-blue-600 mt-1">Uploading...</div>
                            @error('file')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePanel" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="save" type="button" wire:loading.attr="disabled" wire:target="save,file"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">{{ $editId ? 'Update Document' : 'Save Document' }}</span>
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
                <p class="text-sm text-gray-500 mt-1">This permanently removes the document. This can't be undone.</p>
                <div class="flex items-center justify-center gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="delete" class="px-4 py-2 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
