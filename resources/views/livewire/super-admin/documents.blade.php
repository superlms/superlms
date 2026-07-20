<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30 px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Documents</h1>
                <p class="text-xs text-gray-400 mt-0.5">Upload documents and send them to schools. Admins can view &amp; download them.</p>
            </div>
            <button wire:click="openAdd"
                class="inline-flex items-center gap-2 px-4 sm:px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add
            </button>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ══════════ FILTERS ══════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">School</label>
                    <select wire:model.live="filterOrg" @disabled($orgLocked)
                        class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50 disabled:text-gray-400">
                        <option value="">All schools</option>
                        @foreach ($organizations as $org)
                            <option value="{{ $org->id }}">{{ $org->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">From date</label>
                    <input type="date" wire:model.live="filterFrom"
                        class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-gray-500 mb-1">To date</label>
                    <input type="date" wire:model.live="filterTo"
                        class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex items-end">
                    <button wire:click="clearFilters"
                        class="w-full px-3 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        Clear filters
                    </button>
                </div>
            </div>
        </div>

        {{-- ══════════ LISTING ══════════ --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr class="text-left text-[11px] font-semibold uppercase tracking-wide text-gray-500">
                            <th class="px-4 py-3">Document</th>
                            <th class="px-4 py-3">Sent to</th>
                            <th class="px-4 py-3">Size</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse ($documents as $doc)
                            <tr class="hover:bg-gray-50/60" wire:key="doc-{{ $doc->id }}">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <span class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $doc->title }}</p>
                                            <p class="text-[11px] text-gray-400 truncate">{{ $doc->file_name }}</p>
                                            @if ($doc->description)
                                                <p class="text-[11px] text-gray-500 truncate max-w-xs">{{ $doc->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($doc->audience_scope === 'all')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-50 text-green-700 text-[11px] font-semibold">All schools</span>
                                    @else
                                        <span class="text-xs text-gray-600">{{ $doc->organizations->pluck('name')->take(2)->join(', ') }}@if ($doc->organizations->count() > 2) +{{ $doc->organizations->count() - 2 }}@endif</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $doc->readable_size }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $doc->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener"
                                            class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg" title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <button wire:click="downloadDocument({{ $doc->id }})"
                                            class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg" title="Download">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $doc->id }})"
                                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <svg class="w-10 h-10 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm text-gray-500">No documents yet. Click <strong>Add</strong> to upload one.</p>
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

    {{-- ══════════ ADD MODAL ══════════ --}}
    @if ($showAdd)
        @teleport('body')
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40" wire:click="closeAdd"></div>
            <div class="relative bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 sticky top-0 bg-white">
                    <h2 class="text-base font-bold text-gray-900">Add Document</h2>
                    <button wire:click="closeAdd" class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Title *</label>
                        <input type="text" wire:model="title" placeholder="e.g. Circular — Summer Vacation"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Description <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea wire:model="description" rows="2" placeholder="Short note about this document"
                            class="w-full text-sm bg-white border border-gray-200 rounded-lg px-3 py-2 text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"></textarea>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">File * <span class="font-normal text-gray-400">(any document or image, max 5 MB)</span></label>
                        <input type="file" wire:model="file"
                            class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                        <div wire:loading wire:target="file" class="text-[11px] text-gray-400 mt-1">Uploading…</div>
                        @error('file') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="border-t border-gray-100 pt-4">
                        <label class="block text-xs font-semibold text-gray-600 mb-2">Send to</label>

                        @if ($orgLocked)
                            <p class="text-xs text-gray-500">
                                Restricted to <strong class="text-gray-800">{{ $organizations->first()->name ?? 'your school' }}</strong>.
                            </p>
                        @else
                            <div class="flex flex-wrap gap-2 mb-3">
                                <button type="button" wire:click="$set('audienceScope', 'all')"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                           {{ $audienceScope === 'all' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                    All schools
                                </button>
                                <button type="button" wire:click="$set('audienceScope', 'selected')"
                                    class="px-3 py-1.5 text-xs font-semibold rounded-full border transition-colors
                                           {{ $audienceScope === 'selected' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                    Specific schools
                                </button>
                            </div>

                            @if ($audienceScope === 'selected')
                                <div class="max-h-48 overflow-y-auto border border-gray-100 rounded-lg divide-y divide-gray-50">
                                    @foreach ($organizations as $org)
                                        <label class="flex items-center gap-2 px-3 py-2 hover:bg-gray-50 cursor-pointer" wire:key="pick-org-{{ $org->id }}">
                                            <input type="checkbox" wire:click="toggleOrg({{ $org->id }})"
                                                @checked(in_array($org->id, $selectedOrgs))
                                                class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            <span class="text-sm text-gray-700">{{ $org->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('selectedOrgs') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            @endif
                        @endif
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-gray-100 sticky bottom-0 bg-white">
                    <button wire:click="closeAdd" class="px-4 py-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">Cancel</button>
                    <button wire:click="save" wire:loading.attr="disabled" wire:target="save,file"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white text-sm font-semibold rounded-lg shadow-sm disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Send Document</span>
                        <span wire:loading wire:target="save">Sending…</span>
                    </button>
                </div>
            </div>
        </div>
        @endteleport
    @endif

    {{-- ══════════ DELETE CONFIRM ══════════ --}}
    @if ($deleteId)
        @teleport('body')
        <div class="fixed inset-0 z-[70] flex items-center justify-center p-4">
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
        @endteleport
    @endif
</div>
