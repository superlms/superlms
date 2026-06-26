<div class="bg-gray-50">

    {{-- ─── Filter bar — Quick Links heading + Sort + static rows/cols info. ─── --}}
    <div class="bg-white border-b border-gray-200 px-3 sm:px-6 py-2.5 sm:py-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">

            {{-- Page heading (first item in the filter row). --}}
            <h1 class="text-base sm:text-lg font-semibold text-gray-900 mr-1">Quick Links</h1>

            {{-- Divider --}}
            <span class="hidden sm:block h-5 w-px bg-gray-200"></span>

            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span class="hidden sm:inline">Filter by:</span>
                <span class="sm:hidden">Filters</span>
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Sort</span>
                <select wire:model.live="sort"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700
                           focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="sidebar">Sidebar</option>
                    <option value="asc">A–Z</option>
                </select>
            </div>

            {{-- Read-only layout info (no sorting controls). --}}
            <div class="ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                        bg-gray-50 border border-gray-200 text-[11px] font-medium text-gray-600">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <span><strong class="text-gray-800">{{ $rows }}</strong> rows</span>
                <span class="text-gray-300">·</span>
                <span><strong class="text-gray-800">{{ $columns }}</strong> columns</span>
            </div>
        </div>
    </div>

    {{-- ─── Tile grid ───
         - On phones: 3 columns, comfy padding (mobile-app feel).
         - On sm+: respects user's columns choice via the inline custom property. --}}
    <div class="p-3 sm:p-4 md:p-5"
         style="--ql-cols: {{ $columns }}; --ql-rows: {{ $rows }};">
        <div class="ql-grid grid gap-2.5 sm:gap-3 md:gap-3.5">
            @foreach ($orderedLinks as $link)
                <a href="{{ route($link['route'], ['organization' => $organization]) }}"
                   wire:key="ql-{{ $link['route'] }}"
                   class="ql-tile group flex flex-col items-center justify-start
                          rounded-2xl sm:rounded-xl border border-gray-200 bg-white
                          hover:bg-gray-50 hover:border-gray-300
                          active:scale-[0.97] transition
                          p-2.5 sm:p-2 md:p-2.5 overflow-hidden min-w-0">
                    <div class="w-11 h-11 sm:w-9 sm:h-9 md:w-10 md:h-10 mb-1.5 sm:mb-1
                                bg-{{ $link['color'] }}-100 rounded-full
                                flex items-center justify-center shrink-0">
                        <x-icon name="{{ $link['icon'] }}"
                                class="w-5 h-5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-{{ $link['color'] }}-600" />
                    </div>
                    <span class="block w-full text-[11px] sm:text-[11px] md:text-xs leading-tight
                                 font-medium text-center text-gray-800 break-words"
                          style="overflow-wrap: anywhere; word-break: break-word;">
                        {{ $link['title'] }}
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    {{-- Adaptive grid: phones lock to 3 cols regardless of the desktop pick so
         tiles stay tappable. sm uses 4, md+ honors the user's --ql-cols. --}}
    <style>
        .ql-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        @media (min-width: 640px) {
            .ql-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        @media (min-width: 768px) {
            .ql-grid {
                grid-template-columns: repeat(var(--ql-cols, 6), minmax(0, 1fr));
            }
        }
    </style>
</div>
