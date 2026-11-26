{{-- One Super Admin document and every file in it (2026-11-26), a slide-in
     like the others: the title, the note, who it went to (Super Admin only),
     then each file with its own name — open it or download it.

     Takes: $vDoc (SuperAdminDocument with files), $docType (closure: file
     name → [label, tile classes]), $vClose / $vDownload (Livewire methods),
     $vEdit (method, or null), $vAudience (bool: show who it went to). --}}
@php
    $vFiles = $vDoc->fileList();
    $vEdit  = $vEdit ?? null;
@endphp
<div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" x-data @keydown.escape.window="$wire.{{ $vClose }}()">
    <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="{{ $vClose }}"></div>
    <div class="absolute top-0 right-0 bottom-0 w-full max-w-lg bg-white shadow-2xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold text-gray-900 truncate" title="{{ $vDoc->title }}">{{ $vDoc->title }}</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    {{ $vFiles->count() }} {{ $vFiles->count() === 1 ? 'file' : 'files' }}{{ $vDoc->totalSize() !== '—' ? ' · ' . $vDoc->totalSize() : '' }} · {{ $vDoc->created_at?->format('d M Y') }}
                </p>
            </div>
            <button wire:click="{{ $vClose }}" type="button"
                class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
            @if ($vDoc->description)
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Description</p>
                    <p class="text-sm text-gray-700 whitespace-pre-line">{{ $vDoc->description }}</p>
                </div>
            @endif

            @if ($vAudience ?? false)
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">Sent to</p>
                    <p class="text-sm text-gray-700">
                        @if ($vDoc->audience_scope === 'private')
                            Kept private
                        @elseif ($vDoc->audience_scope === 'all')
                            All schools
                        @else
                            {{ $vDoc->organizations->pluck('name')->join(', ') ?: '—' }}
                        @endif
                    </p>
                </div>
            @endif

            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2">Files</p>
                <div class="border border-gray-200 rounded-lg divide-y divide-gray-100 overflow-hidden">
                    @foreach ($vFiles as $vf)
                        @php [$vLabel, $vTile] = $docType($vf->file_name); @endphp
                        <div class="flex items-center gap-3 px-3 py-2.5" wire:key="view-file-{{ $vDoc->id }}-{{ $vf->id ?? $loop->index }}">
                            <span class="w-9 h-9 rounded-lg {{ $vTile }} flex items-center justify-center flex-shrink-0 text-[9px] font-bold">{{ $vLabel }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $vf->name }}">{{ $vf->name }}</p>
                                <p class="text-[11px] text-gray-400 truncate" title="{{ $vf->file_name }}">{{ $vf->file_name }}{{ $vf->readable_size !== '—' ? ' · ' . $vf->readable_size : '' }}</p>
                            </div>
                            <a href="{{ $vf->url }}" target="_blank" rel="noopener" title="Open {{ $vf->name }}"
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors flex-shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
                            @if ($vf->id)
                                <button type="button" wire:click="{{ $vDownload }}({{ $vf->id }})" title="Download {{ $vf->name }}"
                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors flex-shrink-0"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
            @if ($vEdit)
                <button wire:click="{{ $vEdit }}({{ $vDoc->id }})" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Edit</button>
            @endif
            <button wire:click="{{ $vClose }}" type="button" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
        </div>
    </div>
</div>
