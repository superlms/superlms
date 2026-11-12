{{-- The top of a Super Admin list's header in the Fees look (Students,
     Teachers): the title on the left; on the right its figures in one line,
     split by faint rules, and the page's blue button. On a phone or tablet
     the figures move under the title.

     $title; $stats = [[label, figure, colour class, note|null], …];
     $addLabel and $addAction (the button's words and its wire:click). --}}
<div class="px-4 sm:px-6 py-3">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
        <div>
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ $title }}</h1>
        </div>
        <div class="flex items-center gap-4">
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200" data-list-stats>
                @foreach ($stats as [$label, $figure, $tone, $note])
                    <span class="{{ $loop->first ? 'pr-4' : ($loop->last ? 'pl-4' : 'px-4') }} whitespace-nowrap">{{ $label }}: <strong class="{{ $tone }}">{{ $figure }}</strong>@if ($note) <span class="text-xs text-gray-400">({{ $note }})</span>@endif</span>
                @endforeach
            </div>
            <button wire:click="{{ $addAction }}"
                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                <span>{{ $addLabel }}</span>
            </button>
        </div>
    </div>

    {{-- Mobile/Tablet stats --}}
    <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
        @foreach ($stats as [$label, $figure, $tone, $note])
            <span class="whitespace-nowrap">{{ $label }}: <strong class="{{ $tone }}">{{ $figure }}</strong>@if ($note) <span class="text-gray-400">({{ $note }})</span>@endif</span>
        @endforeach
    </div>
</div>
