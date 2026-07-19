{{-- Custom Tailwind pagination — used app-wide via {{ $paginator->links() }} --}}
@php
    $btnBase = 'relative inline-flex items-center justify-center min-w-[2.25rem] h-9 px-3 text-sm font-medium rounded-lg border transition-colors select-none';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination Navigation"
            class="flex flex-col sm:flex-row items-center justify-between gap-3">

            {{-- Results summary --}}
            <p class="text-xs sm:text-sm text-gray-500 order-2 sm:order-1">
                @if ($paginator->firstItem())
                    Showing
                    <span class="font-semibold text-gray-700">{{ $paginator->firstItem() }}</span>
                    –
                    <span class="font-semibold text-gray-700">{{ $paginator->lastItem() }}</span>
                    of
                    <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
                @else
                    Showing <span class="font-semibold text-gray-700">{{ $paginator->count() }}</span> of
                    <span class="font-semibold text-gray-700">{{ $paginator->total() }}</span>
                @endif
                results
            </p>

            {{-- Page buttons --}}
            <div class="flex items-center gap-1 order-1 sm:order-2 flex-wrap justify-center">

                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="{{ $btnBase }} border-gray-200 text-gray-300 cursor-not-allowed" aria-disabled="true" aria-label="Previous">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </span>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev"
                        class="{{ $btnBase }} border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900" aria-label="Previous">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                    </button>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    {{-- "Three Dots" Separator --}}
                    @if (is_string($element))
                        <span class="{{ $btnBase }} border-transparent text-gray-400 cursor-default">{{ $element }}</span>
                    @endif

                    {{-- Array Of Links --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $btnBase }} border-indigo-600 bg-indigo-600 text-white shadow-sm">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                                    class="{{ $btnBase }} border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next"
                        class="{{ $btnBase }} border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900" aria-label="Next">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </button>
                @else
                    <span class="{{ $btnBase }} border-gray-200 text-gray-300 cursor-not-allowed" aria-disabled="true" aria-label="Next">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
