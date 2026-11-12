{{-- A Super Admin list's foot (Students, Teachers): "Showing 1–100 of 2,185
     entries", then Previous · 1 2 3 … 22 · Next. $paginator. --}}
@php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $pages   = collect([1, $current - 1, $current, $current + 1, $last])
        ->filter(fn ($p) => $p >= 1 && $p <= $last)->unique()->sort()->values();
@endphp
<div class="px-4 py-3 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-2">
    <p class="text-xs text-gray-500">
        @if ($paginator->total())
            Showing <span class="font-medium text-gray-800">{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</span>
            of <span class="font-medium text-gray-800">{{ number_format($paginator->total()) }}</span> entries
        @else
            No entries
        @endif
    </p>
    @if ($paginator->hasPages())
        <div class="flex items-center gap-1.5">
            <button wire:click="previousPage" @disabled($paginator->onFirstPage())
                class="inline-flex items-center gap-1 h-8 px-3 text-xs font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:text-gray-300 disabled:hover:bg-white disabled:cursor-not-allowed">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                Previous
            </button>
            @foreach ($pages as $i => $page)
                @if ($i > 0 && $page - $pages[$i - 1] > 1)
                    <span class="inline-flex items-center justify-center w-8 h-8 text-xs rounded-lg border border-gray-200 text-gray-400">…</span>
                @endif
                <button wire:click="gotoPage({{ $page }})"
                    class="inline-flex items-center justify-center min-w-8 h-8 px-2 text-xs font-medium rounded-lg border tabular-nums transition-colors
                           {{ $page === $current ? 'bg-gray-900 border-gray-900 text-white' : 'border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                    {{ $page }}
                </button>
            @endforeach
            <button wire:click="nextPage" @disabled(!$paginator->hasMorePages())
                class="inline-flex items-center gap-1 h-8 px-3 text-xs font-medium rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50 disabled:text-gray-300 disabled:hover:bg-white disabled:cursor-not-allowed">
                Next
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
            </button>
        </div>
    @endif
</div>
