<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4 sticky top-0 z-30">
        <div class="flex items-center gap-2.5">
            <x-admin.back-to-more />
            <div>
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Documents</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Documents shared with your school by SUPERLMS.</p>
            </div>
        </div>
    </div>

    {{-- ══════════ LISTING ══════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[760px]">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left w-12">#</th>
                            <th class="px-4 py-3 text-left">Document</th>
                            <th class="px-4 py-3 text-left">Description</th>
                            <th class="px-4 py-3 text-left w-24">Size</th>
                            <th class="px-4 py-3 text-left w-32">Shared On</th>
                            <th class="px-4 py-3 text-center w-28">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($documents as $i => $doc)
                            <tr class="hover:bg-gray-50/70 transition-colors" wire:key="doc-{{ $doc->id }}">
                                <td class="px-4 py-3 text-gray-400">{{ $documents->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-gray-900 truncate">{{ $doc->title }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $doc->file_name }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    <span class="line-clamp-2">{{ $doc->description ?: '—' }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $doc->readable_size }}</td>
                                <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $doc->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ $doc->url }}" target="_blank" rel="noopener" title="View"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                        <button wire:click="downloadDocument({{ $doc->id }})" title="Download"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-green-50 hover:text-green-600 hover:border-green-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    <p class="text-sm text-gray-500">No documents shared with your school yet.</p>
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
</div>
