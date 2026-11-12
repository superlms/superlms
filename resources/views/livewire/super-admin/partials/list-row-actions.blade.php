{{-- A Super Admin list row's last cell (Students, Teachers): the status as a
     dot — green active, red inactive — right beside the ⋮, whose menu holds
     the row's actions. The menu is placed on the window (fixed), so the
     table's edge never cuts it off; a scroll, a resize or Esc closes it.

     $active (bool); $actions = [[label, wire:click call, 'view'|'edit'|'delete'], …] --}}
@php
    $icons = [
        'view'   => 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'edit'   => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'delete' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    ];
    $statusWord = $active ? 'Active' : 'Inactive';
@endphp
<div class="flex items-center justify-end gap-1"
    x-data="{ open: false, top: 0, right: 0 }"
    x-init="$el.closest('#main-scroll')?.addEventListener('scroll', () => open = false, { passive: true })"
    x-on:keydown.escape.window="open = false" x-on:resize.window="open = false">
    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }}"
        title="{{ $statusWord }}" role="img" aria-label="{{ $statusWord }}" data-status="{{ strtolower($statusWord) }}"></span>
    <button type="button" x-ref="dots" title="More"
        x-on:click="const r = $refs.dots.getBoundingClientRect(); top = r.bottom + 4; right = window.innerWidth - r.right; open = !open"
        class="inline-flex items-center justify-center w-7 h-7 rounded-md text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
    </button>
    <div x-show="open" x-cloak x-on:click.outside="open = false" x-transition.opacity.duration.100ms
        :style="`position: fixed; top: ${top}px; right: ${right}px;`"
        class="z-[60] w-36 rounded-lg border border-gray-200 bg-white py-1 text-left shadow-lg">
        @foreach ($actions as [$label, $call, $icon])
            @php $danger = $icon === 'delete'; @endphp
            <button type="button" wire:click="{{ $call }}" x-on:click="open = false"
                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs {{ $danger ? 'text-rose-600 hover:bg-rose-50' : 'text-gray-700 hover:bg-gray-50' }}">
                <svg class="w-3.5 h-3.5 {{ $danger ? '' : 'text-gray-400' }}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}" /></svg>
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
