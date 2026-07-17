<div class="bg-gray-50 md:h-[calc(100vh-4rem)] md:overflow-hidden flex flex-col">

    {{-- ─── Filter bar — heading + Sort (Sidebar / A–Z) ─── --}}
    <div class="shrink-0 bg-white border-b border-gray-200 px-3 sm:px-6 py-2.5 sm:py-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">

            <h1 class="text-base sm:text-lg font-semibold text-gray-900 mr-1">Quick Links</h1>

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
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="sidebar">Sidebar</option>
                    <option value="asc">A–Z</option>
                </select>
            </div>

            <div class="ml-auto flex items-center gap-2">
                {{-- Live date · day · time (client clock, ticks every second) --}}
                <div x-data="{
                        now: new Date(),
                        get dateStr() {
                            return this.now.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                        },
                        get timeStr() {
                            return this.now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                        }
                    }"
                    x-init="setInterval(() => now = new Date(), 1000)"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-[11px] font-medium text-indigo-700">
                    <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span x-text="dateStr"></span>
                    <span class="text-indigo-300">•</span>
                    <span x-text="timeStr" class="tabular-nums"></span>
                </div>

                <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full
                            bg-gray-50 border border-gray-200 text-[11px] font-medium text-gray-600">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    <span><strong class="text-gray-800">{{ count($orderedLinks) }}</strong> links</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ─── 5 × 5 grid — rows stretch to fill the area so it never scrolls ─── --}}
    <div class="flex-1 min-h-0 p-3 sm:p-4 flex flex-col">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 md:grid-rows-5 md:auto-rows-fr gap-2.5 sm:gap-3 flex-1 min-h-0">
            @foreach ($orderedLinks as $link)
                <a href="{{ route($link['route']) }}" wire:key="ql-{{ $link['route'] }}"
                   class="group flex flex-col items-center justify-center text-center gap-2 p-2 rounded-xl bg-white border border-gray-200 shadow-sm hover:shadow-md hover:-translate-y-0.5 hover:border-indigo-200 transition-all duration-200">
                    <div class="w-10 h-10 rounded-lg bg-{{ $link['color'] }}-100 flex items-center justify-center group-hover:scale-110 transition-transform duration-200">
                        <x-icon name="{{ $link['icon'] }}" class="w-5 h-5 text-{{ $link['color'] }}-600" />
                    </div>
                    <span class="text-xs font-semibold text-gray-700 group-hover:text-gray-900 leading-tight transition-colors">{{ $link['title'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
