{{-- Super Admin → Quick Links in the superlms.in look (2026-11-18): no top
     bar on a computer — its search, session, assistant, notifications,
     Profile and Logout sit on this page's own header row, as on the website's
     analytics screen — and every screen a white tile on a mint panel, as the
     website's "everything in one place" grid. The search filters the tiles
     as you type; Sort keeps Sidebar / A–Z.
     Later the same day: the mint is the whole page, edge to edge, with the
     tiles straight on it, and the header row has no lines (sk-ql); then the
     page's colour became a light cream (the tiles stay white).
     Later still (user's ask): a white page and the school admin's header — one
     60px row with a line under it, level with the sidebar's divider — with
     Filter by / Sort, the search, Ask AI (the website's ask pill; Enter opens
     the assistant as the website's popup), the clock and the bell; Profile
     and Logout left for the sidebar's user card. Each tile fills with its own
     pastel under the pointer.
     2026-11-19 (user's ask): the title is "Welcome! Super Admin"; no session
     pill and no Filter by / Sort (tiles keep the sidebar's order); the time
     with the date under it in small type; Messages then Notifications in the
     corner, each in a soft circle. Later that day: "Welcome! <their name>"
     for the super admin and every sub super admin. --}}
<div class="sa-skin sk-full sk-ql flex flex-col" data-no-empty-art x-data="{ q: '' }">
    @include('livewire.super-admin.partials.home-skin')

    {{-- ─── Header row ─── --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1 title="{{ $roleLabel }}"><span class="sk-hi">Welcome!</span> {{ $welcomeName }}</h1>
        </div>

        <label class="sk-search">
            <x-icon name="magnifying-glass" />
            <span class="sr-only">Search pages</span>
            <input type="text" x-model="q" placeholder="Search pages..." autocomplete="off"
                x-on:keydown.escape="q = ''"
                x-on:keydown.enter.prevent="const t = [...$root.querySelectorAll('.sk-tile')].find(el => el.style.display !== 'none'); if (t) t.click()">
            <button type="button" x-show="q" x-on:click="q = ''" style="display: none" aria-label="Clear"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
        </label>

        <div class="sk-right">
            @if ($assistantEnabled)
                {{-- Ask AI — type and press Enter: the assistant opens as the
                     website's popup with the question asked; empty, it just opens. --}}
                <form class="sk-ask" x-data="{ ask: '' }" :class="ask.trim() && 'has-text'"
                    x-on:submit.prevent="if (ask.trim()) { $dispatch('gemini-ask', { q: ask.trim() }); ask = ''; } else { $dispatch('gemini-toggle'); }">
                    <img src="{{ \App\Support\Platform::logoUrl() }}" alt="">
                    <input type="text" x-model="ask" placeholder="Ask AI…" aria-label="Ask AI" autocomplete="off">
                    <button type="submit" title="Ask AI" aria-label="Ask AI">
                        <svg fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                    </button>
                </form>
            @endif

            @include('livewire.super-admin.partials.ql-clock')

            {{-- In the corner: Messages — the live chat with the schools' admins
                 and the sub super admins (2026-11-26), the badge counting unread
                 messages as the chat notifier reports them — then Notifications.
                 Each in a thin circle with no colour behind it, as the school
                 admin's (user's ask, 2026-11-26). --}}
            <div class="sk-actions">
                <a href="{{ route('super-admin.messages') }}" class="sk-btn" title="Messages" aria-label="Messages"
                    x-data="{ count: {{ (int) $unreadMessages }} }" x-on:chat-sync.window="count = $event.detail.unread">
                    <x-icon name="chat-bubble-oval-left-ellipsis" />
                    <span class="sk-badge" x-show="count > 0" x-cloak x-text="count > 99 ? '99+' : count" @if ($unreadMessages <= 0) style="display: none" @endif>{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>
                </a>
                <button type="button" class="sk-btn sk-bell" x-on:click="Livewire.dispatch('open-notifications')" title="Notifications" aria-label="Notifications">
                    <x-icon name="bell" />
                    @if ($unreadNotifications > 0)
                        <span class="sk-badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- ─── The tiles ─── --}}
    <div class="sk-body flex-1 min-h-0 flex flex-col">
        <div class="sk-panel flex-1 min-h-0">
            <div class="sk-tiles" :class="q && 'is-filtering'">
                @php $tints = [['#dff3ee', '#0f766e'], ['#dde9fb', '#2563eb'], ['#e9e2fb', '#7c3aed'], ['#f8edd0', '#b45309'], ['#f6dcd8', '#c2410c'], ['#e3efdc', '#4d7c0f']]; @endphp
                @foreach ($orderedLinks as $i => $link)
                    @php [$tint, $ink] = $tints[$i % count($tints)]; @endphp
                    <a href="{{ route($link['route']) }}" wire:key="ql-{{ $link['route'] }}" class="sk-tile" style="--i: {{ $i }}; --tint: {{ $tint }}; --ink: {{ $ink }};"
                        x-show="!q || {{ \Illuminate\Support\Js::from(mb_strtolower($link['title'])) }}.includes(q.trim().toLowerCase())">
                        <span class="sk-tile-ic"><x-icon name="{{ $link['icon'] }}" /></span>
                        <span>{{ $link['title'] }}</span>
                    </a>
                @endforeach
            </div>
            <p class="sk-empty" x-show="q && ![...$root.querySelectorAll('.sk-tile')].some(el => el.textContent.toLowerCase().includes(q.trim().toLowerCase()))" style="display: none">
                No pages found for “<span x-text="q"></span>”
            </p>
        </div>
    </div>
</div>
