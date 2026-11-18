@php
    $authUser  = auth()->user();
    $allItems  = config('menu.' . App\Helpers\Constants::ROLEVALUE[$authUser->role]);

    // A screen marked 'sidebar' => false (Credit) is left off the menu.
    $allItems  = array_values(array_filter($allItems, fn($i) => ($i['sidebar'] ?? true) !== false));

    // Sub-super-admins see only the functionalities granted to them, and never
    // the Users management screen (reserved for the main super-admin).
    if ($authUser->role === 'sub-super-admin') {
        $granted  = (array) $authUser->permissions;
        $navItems = collect($allItems)
            ->reject(fn($i) => ($i['link'] ?? '') === 'super-admin.users')
            ->filter(fn($i) => in_array($i['link'] ?? '', $granted, true))
            ->values()
            ->all();
    } else {
        $navItems = $allItems;
    }

    // The card at the foot: who is signed in, and the current session.
    $saName    = trim((string) ($authUser->name ?? '')) ?: 'Super Admin';
    $saInitial = mb_strtoupper(mb_substr($saName, 0, 1));
    $saRole    = $authUser->role === 'sub-super-admin' ? 'Sub Super Admin' : 'Super Admin';
    $saSession = \App\Models\AcademicSession::currentLabel();
    // Under the name: the role and the session ("Super Admin" once only).
    $saSub     = strcasecmp($saName, $saRole) === 0 ? $saSession : $saRole . ' · ' . $saSession;
@endphp

{{-- The Super Admin's sidebar in the look of the website's product screen
     (superlms.in → Features, 2026-11-18): a light panel with a hairline edge,
     the logo and SUPERLMS on one line with a full-width divider under it,
     one plain list of rows (no group labels), each icon in a soft rounded
     tile — teal for the open page — and who is signed in at the foot.
     It is always open on md+ (no menu button, no icon rail): the layout
     sets `sidebar-expanded` for the super admin before the first paint.
     Scrollbars in the school panel's style (this partial is only included for
     the super admin and sub-super-admins, so the other panels keep theirs):
     - the menu's scrollbar is a hairline, as the school sidebar's;
     - every box that scrolls inside a page (slide-in panel bodies, lists,
       wide tables) gets the page's own slim 8px bar, darker on hover,
       instead of the browser's wide one.
     The menu is a little narrower than the layout's 253px (226px), giving
     the page more width. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    @media (min-width: 768px) {
        html.sidebar-expanded { --lms-sb: 226px; }
    }
    /* No shadow beside the menu: its hairline edge is enough, as on the website. */
    .lms-aside { box-shadow: none !important; }

    .sa-side {
        --sa-ink: #0c2620; --sa-text: #44544f; --sa-icon: #5f716b; --sa-line: #eef1f0; --sa-divider: #e3e9e7;
        --sa-teal: #0d9488; --sa-teal-50: #e4f6f2; --sa-on: #edf1f0; --sa-hover: #f3f6f5;
        font-family: 'Geist', 'Instrument Sans', system-ui, -apple-system, 'Segoe UI', sans-serif;
        background: #fbfcfc; border-right: 1px solid var(--sa-line); color: var(--sa-text);
    }

    /* Logo · SUPERLMS, and a divider the full width of the sidebar under it */
    .sa-brand { display: flex; align-items: center; gap: 8px; padding: 18px 18px 16px; flex-shrink: 0; border-bottom: 1px solid var(--sa-divider); }
    .sa-brand-link { display: flex; align-items: center; gap: 10px; min-width: 0; }
    .sa-brand-link img { width: 28px; height: 28px; object-fit: contain; flex-shrink: 0; }
    .sa-brand-link span { font-size: 16px; font-weight: 700; letter-spacing: .01em; color: var(--sa-ink); white-space: nowrap; }

    /* The menu: each icon in a small rounded tile, as on the Quick Links tiles */
    .sa-nav { display: flex; flex-direction: column; gap: 2px; padding: 12px 12px 14px; }
    .sa-link { display: flex; align-items: center; gap: 11px; padding: 5px 8px; border-radius: 11px; font-size: 13.5px; font-weight: 500; color: var(--sa-text); line-height: 1.25; transition: background .2s, color .2s; }
    .sa-ic { width: 30px; height: 30px; border-radius: 9px; display: grid; place-items: center; flex-shrink: 0; background: #f0f4f3; color: var(--sa-icon); transition: background .2s, color .2s, box-shadow .2s, transform .2s; }
    .sa-ic svg { width: 16px; height: 16px; }
    .sa-ic svg * { stroke-width: 1.8px; }
    .sa-link:hover { background: var(--sa-hover); color: var(--sa-ink); }
    .sa-link:hover .sa-ic { background: var(--sa-teal-50); color: var(--sa-teal); transform: scale(1.04); }
    .sa-link.on { background: var(--sa-on); color: var(--sa-ink); font-weight: 600; }
    .sa-link.on .sa-ic { background: linear-gradient(135deg, #14b8a6, #0d9488); color: #fff; box-shadow: 0 6px 14px -6px rgba(13,148,136,.65); transform: none; }

    /* Who is signed in */
    .sa-user { display: flex; align-items: center; gap: 10px; margin: 0 12px; padding: 12px 6px 14px; border-top: 1px solid var(--sa-line); flex-shrink: 0; border-radius: 0; transition: color .2s; }
    .sa-user:hover .sa-user-name { color: var(--sa-teal); }
    .sa-av { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: #f59e0b; color: #fff; font-size: 13px; font-weight: 700; flex-shrink: 0; }
    .sa-user-text { min-width: 0; line-height: 1.25; }
    .sa-user-name { display: block; font-size: 13px; font-weight: 600; color: var(--sa-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .2s; }
    .sa-user-text small { display: block; font-size: 11.5px; color: #8b9893; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    #sidebar-nav::-webkit-scrollbar { width: 3px; }
    #sidebar-nav::-webkit-scrollbar-track { background: transparent; }
    #sidebar-nav::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    #sidebar-nav::-webkit-scrollbar-button { display: none; }

    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar { width: 8px; height: 8px; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-track { background: transparent; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 4px; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-thumb:hover { background: #9ca3af; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-button { display: none; }
    #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto)::-webkit-scrollbar-corner { background: transparent; }

    @supports not selector(::-webkit-scrollbar) {
        #sidebar-nav,
        #main-scroll :is(.overflow-y-auto, .overflow-x-auto, .overflow-auto) { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
    }
</style>

<!-- Off-canvas menu for mobile, show/hide based on off-canvas menu state. -->
<div x-show="offcanvas" x-cloak class="fixed inset-0 flex z-[60] md:hidden"
     role="dialog" aria-modal="true"
     x-on:click="if ($event.target.closest('a')) offcanvas = false">
    <div x-show="offcanvas" x-on:click="offcanvas = false" class="fixed inset-0 bg-black bg-opacity-50"
        x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100" aria-hidden="true"></div>

    <div x-show="offcanvas" x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full">

        <div x-show="offcanvas" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="absolute top-0 right-0 -mr-12 pt-2">
            <button x-on:click="offcanvas = false" type="button"
                class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
                <span class="sr-only">Close sidebar</span>
                <svg class="h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="sa-side flex-1 h-0 flex flex-col">
            <div class="sa-brand">
                <a href="{{ route('super-admin.quick-links') }}" class="sa-brand-link">
                    <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
                    <span>SUPERLMS</span>
                </a>
            </div>

            <div class="flex-1 min-h-0 overflow-y-auto">
                <nav class="sa-nav">
                    @foreach ($navItems as $menu_item)
                        @php $is_active = Route::is($menu_item['prefix']); @endphp
                        <a href="{{ route($menu_item['link']) }}" class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                            <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                            <span>{{ $menu_item['title'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <a href="{{ route('super-admin.profile') }}" class="sa-user" title="Profile">
                <span class="sa-av">{{ $saInitial }}</span>
                <span class="sa-user-text"><span class="sa-user-name">{{ $saName }}</span><small>{{ $saSub }}</small></span>
            </a>
        </div>
    </div>

    <div class="flex-shrink-0 w-14">
        <!-- Force sidebar to shrink to fit close icon -->
    </div>
</div>

<!-- Static sidebar for desktop (collapsible icon rail ⇆ full) -->
<div class="lms-rail lms-sa-rail sa-rail hidden md:flex md:flex-col md:fixed md:inset-y-0 md:left-0">
    <div class="sa-side flex-1 flex flex-col min-h-0">
        <!-- Logo · SUPERLMS, the divider under it -->
        <div class="sa-brand">
            <a href="{{ route('super-admin.quick-links') }}" class="sa-brand-link" title="Quick Links">
                <img src="{{ \App\Support\Platform::logoUrl() }}" alt="Logo">
                <span>SUPERLMS</span>
            </a>
        </div>

        <!-- Scrollable navigation area -->
        <div id="sidebar-nav" class="relative flex-1 min-h-0 overflow-y-auto">
            <nav class="sa-nav">
                @foreach ($navItems as $menu_item)
                    @php $is_active = Route::is($menu_item['prefix']); @endphp
                    <a href="{{ route($menu_item['link']) }}" title="{{ $menu_item['title'] }}"
                        class="sa-link {{ $is_active ? 'on' : '' }}" @if ($is_active) aria-current="page" @endif>
                        <span class="sa-ic"><x-icon name="{{ $menu_item['icon'] }}" /></span>
                        <span class="lms-label">{{ $menu_item['title'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Who is signed in -->
        <a href="{{ route('super-admin.profile') }}" class="sa-user" title="Profile — {{ $saName }}">
            <span class="sa-av">{{ $saInitial }}</span>
            <span class="sa-user-text"><span class="sa-user-name">{{ $saName }}</span><small>{{ $saSub }}</small></span>
        </a>
    </div>
</div>

<!-- JavaScript to scroll active tab into view -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('sidebar-nav');
        if (!container) return;

        const activeTab = container.querySelector('.sa-link.on');
        if (!activeTab) return;

        // Center the active item within the sidebar scroll container
        const containerH = container.clientHeight;
        const itemTop    = activeTab.offsetTop;
        const itemH      = activeTab.clientHeight;

        container.scrollTop = itemTop - (containerH / 2) + (itemH / 2);
    });
</script>
