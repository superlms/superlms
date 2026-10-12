<div class="bg-gray-50 md:h-[calc(100vh-4rem)] md:overflow-hidden flex flex-col">

    {{-- ─── Filter bar — heading + Sort (Sidebar / A–Z) ─── --}}
    <div class="shrink-0 bg-white border-b border-gray-200 px-3 sm:px-6 py-2.5 sm:py-3">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">

            <h1 class="text-lg sm:text-xl font-bold text-gray-900 mr-1">Quick Links</h1>

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

            {{-- Live day · date · time (client clock, ticks every second), as on the
                 school panel's Quick Links. The "N links" count is off the bar. --}}
            <div wire:ignore
                 x-data="{
                    day: '', date: '', time: '',
                    tick() {
                        const now = new Date();
                        this.day  = now.toLocaleDateString('en-IN', { weekday: 'long' });
                        this.date = now.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
                        this.time = now.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
                    }
                 }"
                 x-init="tick(); setInterval(() => tick(), 1000)"
                 class="ml-auto inline-flex items-center gap-1.5 text-sm font-medium text-gray-600">
                <span class="text-gray-800 font-semibold" x-text="day"></span>
                <span class="text-gray-300">·</span>
                <span x-text="date"></span>
                <span class="text-gray-300">·</span>
                <span class="tabular-nums text-gray-800 font-semibold" x-text="time"></span>
            </div>
        </div>
    </div>

    {{-- ─── 6 × 4 grid (the 24 screens) — rows stretch to fill the area so it never scrolls ─── --}}
    <div class="flex-1 min-h-0 p-3 sm:p-4 flex flex-col">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 md:grid-rows-4 md:auto-rows-fr gap-2.5 sm:gap-3 flex-1 min-h-0">
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
