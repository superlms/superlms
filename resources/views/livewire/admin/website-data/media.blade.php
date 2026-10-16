{{--
    One photo or file in Website Data: what is there now, Upload / Change,
    Open and Remove; a file can be a pasted link instead (a YouTube video).
    Params: $kind (image | file), $path (under data: "logo", "documents.2.file"),
    $value, $compact (smaller, inside a list row).
--}}
@php
    $url   = $value !== '' ? \App\Models\SchoolWebsite::media($value) : null;
    $isImg = $value !== '' && ($kind === 'image' || preg_match('/\.(jpe?g|png|webp|gif)($|\?)/i', $value));
    $isPdf = $value !== '' && preg_match('/\.pdf($|\?)/i', $value);
    $box   = !empty($compact) ? 'w-12 h-12' : 'w-20 h-20';
@endphp
<div class="flex items-center gap-3">
    <div class="{{ $box }} rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center flex-shrink-0">
        @if ($isImg)
            <img src="{{ $url }}" alt="" class="w-full h-full object-cover">
        @elseif ($value !== '')
            <span class="text-[10px] font-semibold {{ $isPdf ? 'text-red-600' : 'text-gray-500' }}">{{ $isPdf ? 'PDF' : 'LINK' }}</span>
        @elseif ($kind === 'image')
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M4 16l4.6-4.6a2 2 0 012.8 0L16 16m-2-2l1.6-1.6a2 2 0 012.8 0L20 14M14 8h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        @else
            <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.6a1 1 0 01.7.3l5.4 5.4a1 1 0 01.3.7V19a2 2 0 01-2 2z"/></svg>
        @endif
    </div>
    <div class="min-w-0 flex-1 space-y-1.5">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <label class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 cursor-pointer">
                <span wire:loading.remove wire:target="uploads.{{ $path }}">{{ $value !== '' ? 'Change' : ($kind === 'file' ? 'Upload PDF / photo' : 'Upload photo') }}</span>
                <span wire:loading wire:target="uploads.{{ $path }}">Uploading…</span>
                <input type="file" class="sr-only" accept="{{ $kind === 'file' ? '.pdf,image/*' : 'image/*' }}" wire:model="uploads.{{ $path }}">
            </label>
            @if ($value !== '')
                <a href="{{ $url }}" target="_blank" rel="noopener" class="text-xs text-blue-600 hover:underline">Open</a>
                <button type="button" wire:click="clearValue('{{ $path }}')" class="text-xs text-gray-400 hover:text-red-600">Remove</button>
            @endif
        </div>
        @if ($kind === 'file')
            <input type="text" wire:model.blur="data.{{ $path }}" placeholder="or paste a link (e.g. a YouTube video)"
                class="w-full border border-gray-200 rounded-md px-2.5 py-1.5 text-xs text-gray-600 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
        @endif
        @error("uploads.{$path}")<p class="text-xs text-red-500">{{ $message }}</p>@enderror
    </div>
</div>
