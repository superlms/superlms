{{-- ══════════════════════════════════════════════════════════════════
     FEE STRUCTURE — the "Add Fee Structure" button that belongs in the
     host's header row, next to the title. Only meaningful on the Academic
     tab; the Transport tab's routes are created in the Transport section.
══════════════════════════════════════════════════════════════════ --}}
@if ($structureTab === 'academic')
    <button wire:click="openStructureModal"
        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
        <span class="hidden sm:inline">Add Fee Structure</span>
        <span class="sm:hidden">Add</span>
    </button>
@endif
