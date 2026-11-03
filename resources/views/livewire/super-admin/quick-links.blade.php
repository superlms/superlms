<div class="bg-gray-50 md:h-[calc(100vh-4rem)] md:overflow-hidden flex flex-col">

    {{-- ─── Filter bar — heading + Sort (Sidebar / A–Z); compact ─── --}}
    <div class="shrink-0 bg-white border-b border-gray-200 px-3 sm:px-6 py-1.5 sm:py-2">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 sm:min-h-8">

            <h1 class="text-base font-semibold text-gray-900 mr-1">Quick Links</h1>

            <span class="hidden sm:block h-4 w-px bg-gray-200"></span>

            <div class="flex items-center gap-1.5 text-xs font-semibold text-gray-600">
                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                <span class="hidden sm:inline">Filter by:</span>
                <span class="sm:hidden">Filters</span>
            </div>

            <div class="flex items-center gap-1.5">
                <span class="text-xs text-gray-500">Sort</span>
                <select wire:model.live="sort"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2 py-1 text-gray-700
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
                 class="ml-auto inline-flex items-center gap-1.5 text-xs font-medium text-gray-600">
                <span class="text-gray-800 font-semibold" x-text="day"></span>
                <span class="text-gray-300">·</span>
                <span x-text="date"></span>
                <span class="text-gray-300">·</span>
                <span class="tabular-nums text-gray-800 font-semibold" x-text="time"></span>
            </div>
        </div>
    </div>

    {{-- ─── 6 × 5 grid (up to 30 screens) — rows stretch to fill the area so it never scrolls.
         Between loud and plain: five soft tints (violet, sky, emerald, amber,
         rose) for the icon chips, each tile keeping its tint in either sort;
         the tile lifts and takes its tint on the border under the pointer.
         The classes are written out whole so Tailwind builds them. ─── --}}
    @php
        $tints = [
            ['chip' => 'bg-violet-50 ring-violet-100 text-violet-500 group-hover:bg-violet-100',     'tile' => 'hover:border-violet-200'],
            ['chip' => 'bg-sky-50 ring-sky-100 text-sky-500 group-hover:bg-sky-100',                 'tile' => 'hover:border-sky-200'],
            ['chip' => 'bg-emerald-50 ring-emerald-100 text-emerald-500 group-hover:bg-emerald-100', 'tile' => 'hover:border-emerald-200'],
            ['chip' => 'bg-amber-50 ring-amber-100 text-amber-500 group-hover:bg-amber-100',         'tile' => 'hover:border-amber-200'],
            ['chip' => 'bg-rose-50 ring-rose-100 text-rose-500 group-hover:bg-rose-100',             'tile' => 'hover:border-rose-200'],
        ];
    @endphp
    <div class="flex-1 min-h-0 p-3 sm:p-4 flex flex-col">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 md:grid-rows-5 md:auto-rows-fr gap-2.5 sm:gap-3 flex-1 min-h-0">
            @foreach ($orderedLinks as $link)
                @php $tint = $tints[$link['order'] % count($tints)]; @endphp
                <a href="{{ route($link['route']) }}" wire:key="ql-{{ $link['route'] }}"
                   class="group flex flex-col items-center justify-center text-center gap-2 p-2 rounded-xl bg-white border border-gray-200 shadow-sm {{ $tint['tile'] }} hover:shadow-md hover:-translate-y-0.5 transition-all duration-200">
                    <div class="w-10 h-10 rounded-xl ring-1 {{ $tint['chip'] }} flex items-center justify-center group-hover:scale-105 transition-all duration-200">
                        <x-icon name="{{ $link['icon'] }}" class="w-5 h-5" />
                    </div>
                    <span class="text-xs font-medium text-gray-700 group-hover:text-gray-900 leading-tight transition-colors">{{ $link['title'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
