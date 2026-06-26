@props(['schoolId', 'viewEvent', 'editEvent', 'deleteConfirmEvent', 'deleteEvent'])

<div class="relative ml-2" x-data="{ open: false }" x-cloak>
    <!-- Dropdown trigger button -->
    <button @click.stop="open = !open" class="p-1 focus:outline-none" :class="{ 'z-[10000]': open }">
        <!-- Higher z-index when open -->
        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
        </svg>
    </button>

    <!-- Dropdown menu -->
    <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 bottom-0 mb-2 w-36 bg-white rounded-lg shadow-xl border border-gray-200 py-1 z-[10000]"
        style="backdrop-filter: none;"> <!-- Explicitly disable backdrop filter -->

        <!-- Buttons - added stop modifier to prevent modal from closing dropdown -->
        <x-mini-button wire:click.stop="$dispatch('{{ $viewEvent }}', {id: {{ $schoolId }}})" rounded flat
            icon="eye" />
        <x-mini-button wire:click.stop="$dispatch('{{ $editEvent }}', {id: {{ $schoolId }}})" rounded flat
            icon="pencil" />
        <x-mini-button wire:click.stop="$dispatch('{{ $deleteEvent }}', {id: {{ $schoolId }}})" rounded
            icon="trash" flat gray interaction="negative" />
    </div>
</div>
