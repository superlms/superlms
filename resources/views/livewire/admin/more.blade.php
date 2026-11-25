{{-- School Admin → More in the Quick Links look (user's ask of 2026-11-25,
     livewire.super-admin.partials.home-skin, sk-ql): one 60px header row —
     the title, the search (filters the tiles as you type; Enter opens the
     first) and the time with the date under it — and every screen a white
     tile on a white page that fills with its own pastel under the pointer. --}}
<div class="sa-skin sk-full sk-ql flex flex-col" x-data="{ q: '' }">
    @include('livewire.super-admin.partials.home-skin')
    <style>
        /* Three rows of six in the user's order (2026-11-25): on a wide screen
           each tile stays on its own row even when a school lacks a module.
           A search, or a narrow screen, packs them in that order instead. */
        @media (min-width: 768px) { .more-tiles:not(.is-filtering) > .sk-tile { grid-row: var(--row); } }
    </style>

    {{-- ─── Header row ─── --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>More</h1>
        </div>

        <label class="sk-search">
            <x-icon name="magnifying-glass" />
            <span class="sr-only">Search</span>
            <input type="text" x-model="q" placeholder="Search..." autocomplete="off"
                x-on:keydown.escape="q = ''"
                x-on:keydown.enter.prevent="const t = [...$root.querySelectorAll('.sk-tile')].find(el => el.style.display !== 'none'); if (t) t.click()">
            <button type="button" x-show="q" x-on:click="q = ''" style="display: none" aria-label="Clear"><x-icon name="x-mark" class="w-3.5 h-3.5" /></button>
        </label>

        <div class="sk-right">
            @include('livewire.super-admin.partials.ql-clock')
        </div>
    </div>

    {{-- ─── The tiles ─── --}}
    <div class="sk-body flex-1 min-h-0 flex flex-col">
        <div class="sk-panel flex-1 min-h-0">
            <div class="sk-tiles more-tiles" :class="q && 'is-filtering'">
                @php $tints = [['#dff3ee', '#0f766e'], ['#dde9fb', '#2563eb'], ['#e9e2fb', '#7c3aed'], ['#f8edd0', '#b45309'], ['#f6dcd8', '#c2410c'], ['#e3efdc', '#4d7c0f']]; @endphp
                @foreach ($items as $i => $item)
                    @php [$tint, $ink] = $tints[$i % count($tints)]; @endphp
                    <a href="{{ route($item['route'], ['organization' => $organization]) }}" wire:key="more-{{ $item['route'] }}" class="sk-tile" style="--i: {{ $i }}; --row: {{ $item['row'] ?? 'auto' }}; --tint: {{ $tint }}; --ink: {{ $ink }};"
                        x-show="!q || {{ \Illuminate\Support\Js::from(mb_strtolower($item['title'])) }}.includes(q.trim().toLowerCase())">
                        <span class="sk-tile-ic"><x-icon name="{{ $item['icon'] }}" /></span>
                        <span>{{ $item['title'] }}</span>
                    </a>
                @endforeach
            </div>
            <p class="sk-empty" x-show="q && ![...$root.querySelectorAll('.sk-tile')].some(el => el.textContent.toLowerCase().includes(q.trim().toLowerCase()))" style="display: none">
                Nothing found for “<span x-text="q"></span>”
            </p>
        </div>
    </div>
</div>
