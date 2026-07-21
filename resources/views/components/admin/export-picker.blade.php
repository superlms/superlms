@props([
    'excel' => 'exportExcel',
    'pdf'   => 'exportPdf',
    'close' => 'closeExportPicker',
])
{{-- Format chooser shown before an export. `excel`/`pdf`/`close` are the parent
     Livewire component's action names. The chosen action returns a file download
     AND flips the parent's showExportPicker flag off, so Livewire removes this
     overlay when the response comes back. --}}
<div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="{{ $close }}"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-lg font-bold text-gray-900">Export as…</h3>
            <button wire:click="{{ $close }}" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <p class="text-xs text-gray-500 mb-5">Choose a format to download.</p>

        <div class="grid grid-cols-2 gap-3">
            {{-- Excel --}}
            <button wire:click="{{ $excel }}" wire:loading.attr="disabled" wire:target="{{ $excel }}"
                class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-emerald-300 hover:bg-emerald-50 transition-colors disabled:opacity-60">
                <span class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                </span>
                <span class="text-sm font-semibold text-gray-900">Excel</span>
                <span class="text-[11px] text-gray-400" wire:loading.remove wire:target="{{ $excel }}">.xlsx spreadsheet</span>
                <span wire:loading wire:target="{{ $excel }}" class="text-[11px] text-emerald-600">Preparing…</span>
            </button>

            {{-- PDF --}}
            <button wire:click="{{ $pdf }}" wire:loading.attr="disabled" wire:target="{{ $pdf }}"
                class="flex flex-col items-center gap-2 p-4 rounded-xl border border-gray-200 hover:border-rose-300 hover:bg-rose-50 transition-colors disabled:opacity-60">
                <span class="w-12 h-12 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /><path stroke-linecap="round" stroke-linejoin="round" d="M13 3v6h6" /></svg>
                </span>
                <span class="text-sm font-semibold text-gray-900">PDF</span>
                <span class="text-[11px] text-gray-400" wire:loading.remove wire:target="{{ $pdf }}">printable document</span>
                <span wire:loading wire:target="{{ $pdf }}" class="text-[11px] text-rose-600">Preparing…</span>
            </button>
        </div>
    </div>
</div>
