{{-- A Super Admin list row's last cell (Students, Teachers, Schools, Fees): the
     status as a dot — green active, red inactive — right beside the ⋮, whose
     menu holds the row's actions: each with its icon on a small tile, Delete
     set apart under a line. The menu pops in from the ⋮ (a quick fade and
     grow) and is placed on the window (fixed), so the table's edge never cuts
     it off; near the foot of the window it opens upwards. A scroll, a resize
     or Esc closes it.

     $active (bool); $actions = [[label, call, icon, js?], …] — icon one of
     view | edit | delete | login | fee | link | send | check | cancel | reopen;
     the call is a wire:click, or with js true an Alpine expression (Login as
     school). $dot (optional, true): false leaves the status dot out (a list
     that shows its status in a column of its own, as Calendar → Queries). --}}
@php
    $icons = [
        'view'   => 'M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z',
        'edit'   => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'delete' => 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
        'login'  => 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
        'fee'    => 'M9 8h6m-5 0a3 3 0 110 6H9l3 3m-3-6h6m6 1a9 9 0 11-18 0 9 9 0 0118 0z',
        'link'   => 'M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1',
        'send'   => 'M12 19l9 2-9-18-9 18 9-2zm0 0v-8',
        'check'  => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'cancel' => 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636',
        'reopen' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    ];
    $statusWord = $active ? 'Active' : 'Inactive';
@endphp
<div class="flex items-center justify-end gap-1"
    x-data="{ open: false, up: false, y: 0, right: 0 }"
    x-init="$el.closest('#main-scroll')?.addEventListener('scroll', () => open = false, { passive: true })"
    x-on:keydown.escape.window="open = false" x-on:resize.window="open = false">
    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $active ? 'bg-emerald-500' : 'bg-rose-500' }} {{ ($dot ?? true) ? '' : 'hidden' }}"
        title="{{ $statusWord }}" role="img" aria-label="{{ $statusWord }}" data-status="{{ strtolower($statusWord) }}"></span>
    <button type="button" x-ref="dots" title="More" aria-haspopup="menu" :aria-expanded="open"
        x-on:click="const r = $refs.dots.getBoundingClientRect(); up = r.bottom + {{ count($actions) * 44 + 24 }} > window.innerHeight; y = up ? window.innerHeight - r.top + 4 : r.bottom + 4; right = window.innerWidth - r.right; open = !open"
        :class="open ? 'bg-gray-100 text-gray-900' : 'text-gray-500'"
        class="inline-flex items-center justify-center w-7 h-7 rounded-md hover:text-gray-900 hover:bg-gray-100 transition-colors">
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="10" cy="4" r="1.6"/><circle cx="10" cy="10" r="1.6"/><circle cx="10" cy="16" r="1.6"/></svg>
    </button>
    <div x-show="open" x-cloak x-on:click.outside="open = false" role="menu"
        x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
        :style="`position: fixed; right: ${right}px; ${up ? 'bottom' : 'top'}: ${y}px;`"
        :class="up ? 'origin-bottom-right' : 'origin-top-right'"
        class="z-[60] w-48 rounded-xl border border-gray-100 bg-white p-1.5 text-left shadow-xl shadow-gray-900/10 ring-1 ring-black/5">
        @foreach ($actions as $i => $action)
            @php
                [$label, $call, $icon] = $action;
                $js     = $action[3] ?? false;
                $danger = $icon === 'delete';
                $item   = 'group w-full flex items-center gap-2.5 rounded-lg px-2 py-1.5 text-[13px] font-medium transition-colors '
                        . ($danger ? 'text-rose-600 hover:bg-rose-50' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900');
                $tile   = 'w-7 h-7 rounded-md flex items-center justify-center flex-shrink-0 transition-colors '
                        . ($danger ? 'bg-rose-50 text-rose-500 group-hover:bg-white' : 'bg-gray-50 text-gray-500 group-hover:bg-white group-hover:text-gray-700');
            @endphp
            @if ($danger && $i > 0)
                <div class="my-1 h-px bg-gray-100" role="separator"></div>
            @endif
            @if ($js)
                <button type="button" role="menuitem" x-on:click="open = false; {{ $call }}" class="{{ $item }}">
            @else
                <button type="button" role="menuitem" wire:click="{{ $call }}" x-on:click="open = false" class="{{ $item }}">
            @endif
                <span class="{{ $tile }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$icon] }}" /></svg>
                </span>
                {{ $label }}
            </button>
        @endforeach
    </div>
</div>
