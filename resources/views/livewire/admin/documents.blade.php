<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sticky top-0 z-30">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.more', ['organization' => $organization]) }}" class="p-2 -ml-2 text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">Documents</h1>
                <p class="text-xs text-gray-500 mt-0.5">Documents shared with your school by SUPERLMS.</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($documents as $doc)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 flex flex-col" wire:key="doc-{{ $doc->id }}">
                    <div class="flex items-start gap-3">
                        <span class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-gray-900 break-words">{{ $doc->title }}</p>
                            <p class="text-[11px] text-gray-400 truncate">{{ $doc->file_name }}</p>
                        </div>
                    </div>

                    @if ($doc->description)
                        <p class="text-xs text-gray-500 mt-3 line-clamp-3">{{ $doc->description }}</p>
                    @endif

                    <div class="flex items-center gap-2 mt-3 text-[11px] text-gray-400">
                        <span>{{ $doc->readable_size }}</span>
                        <span>•</span>
                        <span>{{ $doc->created_at?->format('d M Y') }}</span>
                    </div>

                    <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100">
                        <a href="{{ $doc->url }}" target="_blank" rel="noopener"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View
                        </a>
                        <button wire:click="downloadDocument({{ $doc->id }})"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs font-semibold text-green-700 bg-green-50 hover:bg-green-100 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-gray-200 shadow-sm py-16 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="text-sm text-gray-500">No documents shared with your school yet.</p>
                </div>
            @endforelse
        </div>

        @if ($documents->hasPages())
            <div class="mt-6">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
