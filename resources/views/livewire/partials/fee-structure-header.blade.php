{{-- ══════════════════════════════════════════════════════════════════
     FEE STRUCTURE — the two tabs and the grey "Filter by:" band, meant to
     be @included inside the host's own sticky header (Admin\FeeStructure or
     Accounts\FeeStructure). The "Add Fee Structure" button sits in the
     host's header row next to the title.

     Both hosts feed this via App\Livewire\Concerns\HandlesFeeStructures, so
     editing this one file keeps the two pages in sync.
══════════════════════════════════════════════════════════════════ --}}

{{-- Tabs: Academic | Transport --}}
<div class="border-t border-gray-200 px-4 sm:px-6">
    <div class="flex gap-1">
        @foreach (['academic' => 'Academic', 'transport' => 'Transport'] as $tab => $label)
            <button wire:click="setStructureTab('{{ $tab }}')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $structureTab === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
        @endforeach
    </div>
</div>

{{-- Grey filter band — student-page style, attached under the tabs --}}
<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>

        @if ($structureTab === 'academic')
            <select wire:model.live="filterStructureStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">Select Class</option>
                @foreach ($standards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="filterStructureSection" @disabled(!$filterStructureStandard) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
                <option value="">All Sections</option>
                @foreach ($filterSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>

            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search fee name…"
                class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

            @if ($filterStructureStandard || $filterStructureSection || $search)
                <button wire:click="clearStructureFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    Clear
                </button>
            @endif
        @else
            <select wire:model.live="filterStructureRoute" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[200px]">
                <option value="">Select Route</option>
                @foreach ($routes as $r)
                    <option value="{{ $r->id }}">{{ $r->route_name }}</option>
                @endforeach
            </select>

            @if ($filterStructureRoute)
                <button wire:click="clearRouteFilter"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    Clear
                </button>
            @endif
        @endif
    </div>
</div>
