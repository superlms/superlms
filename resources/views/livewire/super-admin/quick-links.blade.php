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
     pastel under the pointer. --}}
<div class="sa-skin sk-full sk-ql flex flex-col" x-data="{ q: '' }">
    @include('livewire.super-admin.partials.home-skin')

    {{-- ─── Header row ─── --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Quick Links</h1>
            <span class="sk-bar-div" aria-hidden="true"></span>

            {{-- The current academic session — pick another (Session then asks
                 for its two codes) or open Session. --}}
            @if ($sessionLink && $sessionList->count() > 1)
                <div class="relative" x-data="{ open: false }" x-on:click.away="open = false" x-on:keydown.escape.window="open = false">
                    <button type="button" class="sk-pill" x-on:click="open = !open" :aria-expanded="open" title="Current session — switch it here">
                        <x-icon name="calendar-days" class="w-3.5 h-3.5" /> {{ $sessionLabel }}
                        <x-icon name="chevron-down" class="w-3 h-3" />
                    </button>
                    <div class="sk-menu" x-show="open" x-transition style="display: none" role="listbox">
                        <p class="sk-menu-h">Current session</p>
                        @foreach ($sessionList as $s)
                            @if ($s->is_current)
                                <div class="cur" role="option" aria-selected="true"><span class="flex-1">{{ $s->name }}<small>{{ $s->durationLabel() }}</small></span><x-icon name="check" class="w-4 h-4" /></div>
                            @else
                                <a role="option" href="{{ route('super-admin.sessions', ['switch' => $s->id]) }}"
                                    title="Make {{ $s->name }} current — needs a code from the Super Admin's email and one from WhatsApp">
                                    <span class="flex-1">{{ $s->name }}<small>{{ $s->durationLabel() }}</small></span><x-icon name="lock-closed" class="w-3.5 h-3.5 text-gray-300" />
                                </a>
                            @endif
                        @endforeach
                        <a class="foot" href="{{ route('super-admin.sessions') }}">Manage sessions <x-icon name="chevron-right" class="w-3.5 h-3.5" /></a>
                    </div>
                </div>
            @elseif ($sessionLink)
                <a href="{{ route('super-admin.sessions') }}" class="sk-pill" title="Current session — change it on Session"><x-icon name="calendar-days" class="w-3.5 h-3.5" /> {{ $sessionLabel }}</a>
            @else
                <span class="sk-pill" title="Current session"><x-icon name="calendar-days" class="w-3.5 h-3.5" /> {{ $sessionLabel }}</span>
            @endif
        </div>

        <div class="sk-bar-filter">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            <span>Filter by:</span>
            <select wire:model.live="sort" class="sk-select" aria-label="Sort">
                <option value="sidebar">Sidebar order</option>
                <option value="asc">A–Z</option>
            </select>
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

            {{-- Live day · date · time, as on the school admin's Quick Links (client clock, ticks every second). --}}
            <span wire:ignore class="sk-clock"
                x-data="{ day: '', date: '', time: '', tick() { const n = new Date(); this.day = n.toLocaleDateString('en-IN', { weekday: 'long' }); this.date = n.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }); this.time = n.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); } }"
                x-init="tick(); setInterval(() => tick(), 1000)">
                <b class="day" x-text="day"></b><i>·</i><span x-text="date"></span><i>·</i><b class="tabular-nums" x-text="time"></b>
            </span>

            <div class="sk-actions">
                <button type="button" class="sk-btn" x-on:click="Livewire.dispatch('open-notifications')" title="Notifications" aria-label="Notifications">
                    <x-icon name="bell-alert" />
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
