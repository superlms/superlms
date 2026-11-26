{{-- School Admin → Quick Links in the Super Admin's exact style (user's ask of
     2026-11-19, livewire.super-admin.partials.home-skin, sk-ql): no top bar on
     a computer — the page's own header is one 60px row with a line under it,
     level with the sidebar's divider: the title, the session, Filter by /
     Sort, the search (filters the tiles as you type; Enter opens the first),
     Ask AI (the website's ask pill — Enter opens the assistant as the
     website's popup), the day · date · time, the bell and Messages. Profile
     and Logout are on the sidebar's user card. Every screen a white tile on a
     white page that fills with its own pastel under the pointer.
     2026-11-19 (user's ask): the title is "Welcome! School Admin"; no Filter
     by / Sort (tiles keep the sidebar's order); the time with the date under
     it, then the session, then Messages and Notifications in soft circles —
     as the Super Admin's. --}}
<div class="sa-skin sk-full sk-ql flex flex-col" data-no-empty-art x-data="{ q: '' }">
    @include('livewire.super-admin.partials.home-skin')

    {{-- ─── Header row ─── --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1><span class="sk-hi">Welcome!</span> {{ $roleLabel }}</h1>
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

            {{-- The time with the date under it, as the Super Admin's. --}}
            @include('livewire.super-admin.partials.ql-clock')

            {{-- The current session, just before the icons. --}}
            <span class="sk-pill sk-session" title="Current session"><x-icon name="calendar-days" class="w-3.5 h-3.5" /> {{ $sessionLabel }}</span>

            {{-- In the corner: Messages, then Notifications — each in a thin circle, no colour behind it. --}}
            <div class="sk-actions">
                @if (\Illuminate\Support\Facades\Route::has('admin.messages'))
                    <a href="{{ route('admin.messages', ['organization' => $organization]) }}" class="sk-btn" title="Messages" aria-label="Messages"
                        x-data="{ count: {{ (int) $unreadMessages }} }" x-on:chat-sync.window="count = $event.detail.unread">
                        <x-icon name="chat-bubble-oval-left-ellipsis" />
                        <span class="sk-badge" x-show="count > 0" x-cloak x-text="count > 99 ? '99+' : count" @if ($unreadMessages <= 0) style="display: none" @endif>{{ $unreadMessages > 99 ? '99+' : $unreadMessages }}</span>
                    </a>
                @endif
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
                    <a href="{{ route($link['route'], ['organization' => $organization]) }}" wire:key="ql-{{ $link['route'] }}" class="sk-tile" style="--i: {{ $i }}; --tint: {{ $tint }}; --ink: {{ $ink }};"
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
