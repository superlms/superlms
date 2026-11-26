{{--
    A list with nothing in it (user's ask, 2026-11-26: "null data screen ko
    shi se redesign kro"), in the panel's teal look: a small illustration,
    a title, one line under it and the next step as a pill button.

    <x-empty-state title="No users yet" text="…" action="Add User" click="openCreate" />
    <x-empty-state icon="search" title="No users match this filter" text="…" clear="clearFilters" />

    icon:   'users' (cards with a person and a plus) | 'search' (a card under a magnifier)
    action: the button's label, with click the Livewire method it calls
    clear:  the Livewire method that clears the filters (a light pill, "Clear filters")
--}}
@props([
    'title',
    'text'   => null,
    'icon'   => 'users',
    'action' => null,
    'click'  => null,
    'clear'  => null,
])

<div {{ $attributes->merge(['class' => 'es']) }}>
    <svg class="es-art" viewBox="0 0 168 120" fill="none" aria-hidden="true">
        <circle cx="84" cy="62" r="52" fill="#effaf7" />
        <circle cx="84" cy="62" r="52" stroke="#d8f3ec" stroke-dasharray="3 6" />
        <circle cx="28" cy="94" r="3" fill="#b1e6db" />
        <circle cx="146" cy="30" r="2.5" fill="#b1e6db" />
        <circle cx="140" cy="98" r="2" fill="#d8f3ec" />
        {{-- the card behind --}}
        <rect x="38" y="30" width="80" height="48" rx="11" fill="#fff" stroke="#d8f3ec" stroke-width="1.5" transform="rotate(-7 78 54)" />
        {{-- the card in front: a person and two lines --}}
        <rect x="50" y="44" width="84" height="50" rx="11" fill="#fff" stroke="#b1e6db" stroke-width="1.5" />
        <circle cx="70" cy="69" r="11" fill="#d8f3ec" />
        <circle cx="70" cy="66" r="4" fill="#0d9488" />
        <path d="M62.5 76.5c1.6-3.4 4.4-5 7.5-5s5.9 1.6 7.5 5" stroke="#0d9488" stroke-width="2" stroke-linecap="round" />
        <rect x="88" y="61" width="34" height="6" rx="3" fill="#b1e6db" />
        <rect x="88" y="72" width="22" height="5" rx="2.5" fill="#d8f3ec" />
        @if ($icon === 'search')
            <circle cx="126" cy="86" r="13" fill="#fff" stroke="#0d9488" stroke-width="3" />
            <path d="M135.5 95.5l8 8" stroke="#0d9488" stroke-width="4" stroke-linecap="round" />
            <path d="M121 86h10" stroke="#b1e6db" stroke-width="2.5" stroke-linecap="round" />
        @else
            <circle cx="132" cy="44" r="12" fill="#0d9488" />
            <path d="M132 38.5v11M126.5 44h11" stroke="#fff" stroke-width="2.2" stroke-linecap="round" />
        @endif
    </svg>

    <p class="es-title">{{ $title }}</p>
    @if ($text)
        <p class="es-text">{{ $text }}</p>
    @endif

    @if (($action && $click) || $clear)
        <div class="es-actions">
            @if ($action && $click)
                <button type="button" wire:click="{{ $click }}" class="es-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    {{ $action }}
                </button>
            @endif
            @if ($clear)
                <button type="button" wire:click="{{ $clear }}" class="es-btn es-btn-light">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18" /></svg>
                    Clear filters
                </button>
            @endif
        </div>
    @endif
</div>

@once
    <style>
        .es { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 56px 20px 60px; }
        .es-art { width: 168px; height: 120px; margin-bottom: 18px; }
        .es-title { margin: 0; font-family: 'Plus Jakarta Sans', 'Geist', ui-sans-serif, system-ui, sans-serif; font-size: 17px; font-weight: 700; color: #0c2620; letter-spacing: -.01em; }
        .es-text { margin: 6px 0 0; max-width: 360px; font-size: 13.5px; line-height: 1.55; color: #6b7280; }
        .es-actions { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-top: 20px; }
        .es-btn { display: inline-flex; align-items: center; gap: 6px; height: 40px; padding: 0 20px; border-radius: 9999px; border: 1px solid #0d9488;
                  background: #0d9488; color: #fff; font-size: 14px; font-weight: 600; line-height: 1; cursor: pointer; transition: background-color .15s, border-color .15s, color .15s; }
        .es-btn:hover { background: #0f766e; border-color: #0f766e; }
        .es-btn:focus-visible { outline: 2px solid #b1e6db; outline-offset: 2px; }
        .es-btn svg { width: 16px; height: 16px; flex-shrink: 0; }
        .es-btn-light { background: #fff; color: #0f766e; border-color: #d6dedb; }
        .es-btn-light:hover { background: #effaf7; border-color: #b1e6db; color: #0c2620; }
        tr.es-row, tr.es-row:hover { background: transparent !important; }
    </style>
@endonce
