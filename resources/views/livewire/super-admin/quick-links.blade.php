{{-- Super Admin → Quick Links in the superlms.in look (2026-11-18): no top
     bar on a computer — its search, session, assistant, notifications,
     Profile and Logout sit on this page's own header row, as on the website's
     analytics screen — and every screen a white tile on a mint panel, as the
     website's "everything in one place" grid. The search filters the tiles
     as you type; Sort keeps Sidebar / A–Z. --}}
<div class="sa-skin sk-full flex flex-col" x-data="{ q: '' }">
    @include('livewire.super-admin.partials.home-skin')

    {{-- ─── Header row ─── --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Quick Links</h1>

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

        <label class="sk-search">
            <x-icon name="magnifying-glass" />
            <span class="sr-only">Search pages</span>
            <input type="text" x-model="q" placeholder="Search pages..." autocomplete="off"
                x-on:keydown.escape="q = ''"
                x-on:keydown.enter.prevent="const t = [...$root.querySelectorAll('.sk-tile')].find(el => el.style.display !== 'none'); if (t) t.click()">
            <button type="button" x-show="q" x-on:click="q = ''" style="display: none" aria-label="Clear"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
        </label>

        <div class="sk-right">
            {{-- Live day · date · time (client clock, ticks every second). --}}
            <span wire:ignore class="sk-pill live"
                x-data="{ t: '', tick() { const n = new Date(); this.t = n.toLocaleDateString('en-IN', { weekday: 'short', day: '2-digit', month: 'short' }) + ' · ' + n.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true }); } }"
                x-init="tick(); setInterval(() => tick(), 1000)"><span class="tabular-nums" x-text="t"></span></span>

            <select wire:model.live="sort" class="sk-select" aria-label="Sort">
                <option value="sidebar">Sidebar order</option>
                <option value="asc">A–Z</option>
            </select>

            {{-- What the top bar held (on a phone the bar is still there). --}}
            <div class="sk-actions">
                @if ($assistantEnabled)
                    <button type="button" class="sk-btn" x-on:click="$dispatch('gemini-toggle')" title="Super Assist" aria-label="Super Assist">
                        <img src="{{ \App\Support\Platform::logoUrl() }}" alt="">
                    </button>
                @endif
                <button type="button" class="sk-btn" x-on:click="Livewire.dispatch('open-notifications')" title="Notifications" aria-label="Notifications">
                    <x-icon name="bell-alert" />
                    @if ($unreadNotifications > 0)
                        <span class="sk-badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </button>
                <a href="{{ route('super-admin.profile') }}" class="sk-btn" title="Profile" aria-label="Profile"><x-icon name="user" /></a>
                <button type="button" class="sk-btn out" x-on:click="Livewire.dispatch('open-logout-confirm')" title="Logout" aria-label="Logout">
                    <x-icon name="arrow-right-on-rectangle" />
                </button>
            </div>
        </div>
    </div>

    {{-- ─── The tiles ─── --}}
    <div class="flex-1 min-h-0 p-3 sm:p-5 flex flex-col">
        <div class="sk-panel flex-1 min-h-0">
            <div class="sk-tiles" :class="q && 'is-filtering'">
                @foreach ($orderedLinks as $i => $link)
                    <a href="{{ route($link['route']) }}" wire:key="ql-{{ $link['route'] }}" class="sk-tile" style="--i: {{ $i }}"
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
