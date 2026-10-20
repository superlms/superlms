{{--
    More → Website Data, three steps (user's ask of 2026-10-21):
      1. the site's menus as tiles in Quick Links' look (School first, then the
         menus the site shows its pages under), each with how many of its
         pages are filled in;
      2. a menu's pages as tiles, each Filled / Empty / Form;
      3. a page, full width in the About App editor's look: a header strip
         with Back, the page's name, View page and Save, then its blocks
         (App\Support\WebsitePages) as white cards on the grey page.
    Back steps out one level. Photos and files upload as soon as they are picked.
--}}
@php
    use App\Support\WebsitePages;
    use Illuminate\Support\Str;

    $leave    = "if (! changed || confirm('Leave this page without saving your changes?'))";
    $allRows  = collect($menus)->flatMap(fn ($rows) => $rows);
    $filled   = $allRows->where('status', 'filled')->count();
    $fillable = $allRows->where('status', '!=', 'form')->count();
    $strip    = 'bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sticky top-0 z-30';
    $backBtn  = 'inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-700 flex-shrink-0';
    $backIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>';
    $tileCls  = 'group flex flex-col items-center justify-start rounded-2xl sm:rounded-xl border border-gray-200 bg-white hover:bg-gray-50 hover:border-gray-300 active:scale-[0.97] transition p-2.5 sm:p-3 overflow-hidden min-w-0 text-center';
@endphp
<div class="min-h-screen bg-gray-50"
     x-data="{ changed: @entangle('dirty') }"
     x-on:beforeunload.window="if (changed) { $event.preventDefault(); $event.returnValue = ''; }">
    <style>
        [x-cloak]{display:none !important;}
        /* Quick Links' grid: 3 tiles a row on a phone, 4 on a tablet, more on a computer. */
        .wd-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        @media (min-width: 640px)  { .wd-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (min-width: 768px)  { .wd-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        @media (min-width: 1280px) { .wd-grid { grid-template-columns: repeat(8, minmax(0, 1fr)); } }
    </style>

    @if (! $page && ($group === '' || ! isset($menus[$group])))
        {{-- ══════════ 1. THE MENUS ══════════ --}}
        <div class="{{ $strip }}">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 sm:min-h-9">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 mr-1">Website Data</h1>
                <span class="hidden sm:block h-5 w-px bg-gray-200"></span>
                <p class="hidden sm:block text-sm text-gray-500">Pick a menu, then a page — it is live on your website as soon as you save.</p>
                <span class="ml-auto text-sm text-gray-500">Filled: <strong class="text-gray-800">{{ $filled }}</strong> of {{ $fillable }}</span>
            </div>
        </div>

        <div class="p-3 sm:p-4 md:p-5">
            <div class="wd-grid grid gap-2.5 sm:gap-3 md:gap-3.5">
                @foreach ($menus as $name => $rows)
                    @php
                        [$icon, $color] = WebsitePages::menuTile($name);
                        $rows = collect($rows);
                        $done = $rows->where('status', 'filled')->count();
                        $all  = $rows->where('status', '!=', 'form')->count();
                    @endphp
                    <button type="button" wire:key="menu-{{ Str::slug($name) }}" wire:click="openGroup(@js($name))" class="{{ $tileCls }}">
                        <span class="w-11 h-11 sm:w-10 sm:h-10 mb-1.5 bg-{{ $color }}-100 rounded-full flex items-center justify-center shrink-0">
                            <x-icon name="{{ $icon }}" class="w-5 h-5 text-{{ $color }}-600" />
                        </span>
                        <span class="block w-full text-[11px] md:text-xs leading-tight font-medium text-gray-800 break-words" style="overflow-wrap:anywhere;">{{ $name }}</span>
                        <span class="block mt-0.5 text-[10px] md:text-[11px] {{ $all && $done === $all ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $all ? "{$done}/{$all} filled" : 'Form' }}
                        </span>
                    </button>
                @endforeach
            </div>

            @if ($off)
                <p class="mt-4 px-1 text-xs text-gray-400">
                    {{ $off }} more {{ Str::plural('page', $off) }} switched off for your site — Super Admin can turn them on.
                </p>
            @endif
        </div>

    @elseif (! $page)
        {{-- ══════════ 2. A MENU'S PAGES ══════════ --}}
        @php
            $rows = collect($menus[$group]);
            $done = $rows->where('status', 'filled')->count();
            $all  = $rows->where('status', '!=', 'form')->count();
        @endphp
        <div class="{{ $strip }}">
            <div class="flex items-center gap-3 sm:min-h-9">
                <button type="button" wire:click="back" class="{{ $backBtn }}" title="Back to the menus">{!! $backIcon !!}</button>
                <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $group }}</h1>
                    <p class="text-xs text-gray-400 truncate">Website Data › {{ $group }} · pick a page to fill in what it shows</p>
                </div>
                @if ($all)
                    <span class="ml-auto text-sm text-gray-500 whitespace-nowrap">Filled: <strong class="text-gray-800">{{ $done }}</strong> of {{ $all }}</span>
                @endif
            </div>
        </div>

        <div class="p-3 sm:p-4 md:p-5" wire:key="pages-{{ Str::slug($group) }}">
            <div class="wd-grid grid gap-2.5 sm:gap-3 md:gap-3.5">
                @foreach ($menus[$group] as $slug => $row)
                    @php [$icon, $color] = WebsitePages::tile($slug); @endphp
                    <button type="button" wire:key="pg-{{ $slug }}" wire:click="openPage('{{ $slug }}')"
                        title="{{ $row['summary'] ?: 'A form — nothing to fill in' }}" class="{{ $tileCls }}">
                        <span class="w-11 h-11 sm:w-10 sm:h-10 mb-1.5 bg-{{ $color }}-100 rounded-full flex items-center justify-center shrink-0">
                            <x-icon name="{{ $icon }}" class="w-5 h-5 text-{{ $color }}-600" />
                        </span>
                        <span class="block w-full text-[11px] md:text-xs leading-tight font-medium text-gray-800 break-words" style="overflow-wrap:anywhere;">{{ $row['label'] }}</span>
                        @if ($row['status'] === 'filled')
                            <span class="inline-flex items-center gap-1 mt-0.5 text-[10px] md:text-[11px] text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Filled</span>
                        @elseif ($row['status'] === 'empty')
                            <span class="inline-flex items-center gap-1 mt-0.5 text-[10px] md:text-[11px] text-gray-400"><span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>Empty</span>
                        @else
                            <span class="mt-0.5 text-[10px] md:text-[11px] text-gray-400">Form</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

    @else
        {{-- ══════════ 3. ONE PAGE — the About App editor's look ══════════ --}}
        @php $savable = (bool) array_filter($page['blocks'], fn ($b) => WebsitePages::counts($b)); @endphp
        <div class="{{ $strip }}">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="flex items-center gap-3 min-w-0">
                    <button type="button" x-on:click="{{ $leave }} $wire.back()" class="{{ $backBtn }}" title="Back">{!! $backIcon !!}</button>
                    <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $page['label'] }}</h1>
                        <p class="text-xs text-gray-400 truncate">Website Data{{ $page['group'] ? ' › ' . $page['group'] : '' }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span x-show="changed" x-cloak class="hidden sm:inline text-xs text-amber-600 mr-1">Unsaved changes</span>
                    @if ($pageUrl)
                        <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 border border-gray-200 rounded-md hover:bg-gray-50">
                            View page
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    @endif
                    @if ($savable)
                        @include('livewire.admin.website-data.save')
                    @endif
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-6 space-y-5" wire:key="page-{{ $open }}" x-on:input="changed = true">
            @if ($page['intro'])
                <p class="text-sm text-gray-500">{{ $page['intro'] }}</p>
            @endif

            @php
                // Text and paragraph blocks in a row share one card, as About
                // App's Basic Information; the rest have a card each.
                $runs = [];
                foreach ($page['blocks'] as $b) {
                    $plain = in_array($b['type'], ['text', 'textarea'], true);
                    if ($plain && $runs && $runs[array_key_last($runs)]['plain']) {
                        $runs[array_key_last($runs)]['blocks'][] = $b;
                    } else {
                        $runs[] = ['plain' => $plain, 'blocks' => [$b]];
                    }
                }
            @endphp
            @foreach ($runs as $run)
                @if ($run['plain'])
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden" wire:key="run-{{ $run['blocks'][0]['id'] }}">
                        <div class="px-5 py-3.5 border-b border-gray-100">
                            <h2 class="text-sm font-semibold text-gray-800">{{ count($run['blocks']) === 1 ? $run['blocks'][0]['label'] : 'Details' }}</h2>
                        </div>
                        <div class="p-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                            @foreach ($run['blocks'] as $b)
                                @include('livewire.admin.website-data.block', ['b' => $b, 'bare' => true, 'alone' => count($run['blocks']) === 1])
                            @endforeach
                        </div>
                    </div>
                @else
                    @include('livewire.admin.website-data.block', ['b' => $run['blocks'][0], 'bare' => false, 'alone' => false])
                @endif
            @endforeach

            @if ($savable)
                <div class="flex items-center justify-end gap-3">
                    <span x-show="changed" x-cloak class="text-xs text-amber-600">Unsaved changes</span>
                    @include('livewire.admin.website-data.save')
                </div>
            @endif
        </div>
    @endif
</div>
