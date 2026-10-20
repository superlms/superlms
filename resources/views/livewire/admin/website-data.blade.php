{{--
    More → Website Data. The site's menus as chips (School first, then the
    menus the site shows its pages under); a menu picked shows its pages as
    chips under them, and a page picked opens below on the blocks that page
    shows (App\Support\WebsitePages), saved with Save. Photos and files
    upload as soon as they are picked.
--}}
@php
    use App\Support\WebsitePages;
    use Illuminate\Support\Str;

    $leave    = "if (! changed || confirm('Leave this page without saving your changes?'))";
    $allRows  = collect($menus)->flatMap(fn ($rows) => $rows);
    $filled   = $allRows->where('status', 'filled')->count();
    $fillable = $allRows->where('status', '!=', 'form')->count();
@endphp
<div class="min-h-screen bg-gray-50"
     x-data="{ changed: @entangle('dirty') }"
     x-on:beforeunload.window="if (changed) { $event.preventDefault(); $event.returnValue = ''; }">
    <style>
        [x-cloak]{display:none !important;}
        .wd-chips { scrollbar-width: none; }
        .wd-chips::-webkit-scrollbar { display: none; }
        @media (min-width: 768px) { .wd-chips { flex-wrap: wrap; overflow: visible; } }
    </style>

    {{-- ══════════ THE MENUS, AND THE MENU'S PAGES — as chips ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-3 sm:px-6 pt-2.5 sm:pt-3 pb-2 flex flex-wrap items-center gap-x-3 gap-y-1">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900 mr-1">Website Data</h1>
            <span class="hidden sm:block h-5 w-px bg-gray-200"></span>
            <p class="hidden sm:block text-sm text-gray-500">Pick a menu, then a page — it is live on your website as soon as you save.</p>
            <span class="ml-auto text-sm text-gray-500">Filled: <strong class="text-gray-800">{{ $filled }}</strong> of {{ $fillable }}</span>
        </div>

        {{-- The menus: each with how many of its pages are filled in. --}}
        <div class="wd-chips px-3 sm:px-6 pb-2.5 flex gap-1.5 sm:gap-2 overflow-x-auto">
            @foreach ($menus as $name => $rows)
                @php
                    $rows = collect($rows);
                    $done = $rows->where('status', 'filled')->count();
                    $all  = $rows->where('status', '!=', 'form')->count();
                    $on   = $group === $name;
                @endphp
                <button type="button" wire:key="menu-{{ Str::slug($name) }}"
                    x-on:click="{{ $on ? '' : $leave }} $wire.openGroup(@js($name))"
                    class="shrink-0 inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full border text-sm font-medium transition
                           {{ $on ? 'bg-blue-600 border-blue-600 text-white shadow-sm' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300' }}">
                    {{ $name }}
                    @if ($all)
                        <span class="text-[11px] font-normal {{ $on ? 'text-blue-100' : ($done === $all ? 'text-emerald-600' : 'text-gray-400') }}">{{ $done }}/{{ $all }}</span>
                    @endif
                </button>
            @endforeach
        </div>

        {{-- The menu's pages: each with whether it is filled; what goes in it is the chip's tooltip. --}}
        @if ($group !== '' && isset($menus[$group]))
            <div class="wd-chips border-t border-gray-100 bg-gray-50/80 px-3 sm:px-6 py-2 flex gap-1.5 sm:gap-2 overflow-x-auto" wire:key="pages-{{ Str::slug($group) }}">
                @foreach ($menus[$group] as $slug => $row)
                    @php
                        [$icon, $color] = WebsitePages::tile($slug);
                        $on = $open === $slug;
                    @endphp
                    <button type="button" wire:key="pg-{{ $slug }}"
                        x-on:click="{{ $on ? '' : $leave }} $wire.openPage('{{ $slug }}')"
                        title="{{ $row['summary'] ?: 'A form — nothing to fill in' }}"
                        class="shrink-0 inline-flex items-center gap-1.5 pl-1 pr-3 py-1 rounded-full border text-[13px] font-medium transition
                               {{ $on ? 'bg-white border-blue-500 text-blue-700 ring-2 ring-blue-500/15' : 'bg-white border-gray-200 text-gray-700 hover:border-gray-300 hover:bg-gray-50' }}">
                        <span class="w-6 h-6 rounded-full bg-{{ $color }}-100 flex items-center justify-center shrink-0">
                            <x-icon name="{{ $icon }}" class="w-3.5 h-3.5 text-{{ $color }}-600" />
                        </span>
                        {{ $row['label'] }}
                        @if ($row['status'] === 'filled')
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500" title="Filled"></span><span class="sr-only">Filled</span>
                        @elseif ($row['status'] === 'empty')
                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300" title="Empty"></span><span class="sr-only">Empty</span>
                        @else
                            <span class="text-[10px] font-normal text-gray-400">Form</span>
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    @if (! $page)
        {{-- ══════════ NOTHING OPEN YET ══════════ --}}
        <div class="p-3 sm:p-6">
            <div class="max-w-3xl mx-auto bg-white border border-dashed border-gray-300 rounded-xl px-6 py-10 text-center">
                <span class="mx-auto mb-3 w-11 h-11 rounded-full bg-blue-50 flex items-center justify-center">
                    <x-icon name="globe-alt" class="w-5 h-5 text-blue-500" />
                </span>
                @if ($group === '')
                    <p class="text-sm font-semibold text-gray-800">Pick a menu above</p>
                    <p class="text-sm text-gray-500 mt-1">Your website's menus are above. Pick one to see its pages, then a page to fill in what it shows.</p>
                @else
                    <p class="text-sm font-semibold text-gray-800">Pick a page of {{ $group }}</p>
                    <p class="text-sm text-gray-500 mt-1">Its pages are above — a green dot is filled in, a grey one is still empty.</p>
                @endif
            </div>

            @if ($off)
                <p class="max-w-3xl mx-auto mt-3 px-1 text-xs text-gray-400">
                    {{ $off }} more {{ Str::plural('page', $off) }} switched off for your site — Super Admin can turn them on.
                </p>
            @endif
        </div>
    @else
        {{-- ══════════ ONE PAGE ══════════ --}}
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-4 sm:py-5 space-y-3" wire:key="page-{{ $open }}" x-on:input="changed = true">
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] text-gray-400 truncate leading-tight">Website Data{{ $page['group'] ? ' › ' . $page['group'] : '' }}</p>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900 truncate leading-tight">{{ $page['label'] }}</h2>
                </div>
                <span x-show="changed" x-cloak class="hidden sm:inline text-xs text-amber-600">Unsaved changes</span>
                @if ($pageUrl)
                    <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                        class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                        View page
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif
                @if (array_filter($page['blocks'], fn ($b) => WebsitePages::counts($b)))
                    @include('livewire.admin.website-data.save')
                @endif
            </div>

            @if ($page['intro'])
                <p class="px-1 text-sm text-gray-500">{{ $page['intro'] }}</p>
            @endif

            @php
                // Text and paragraph blocks in a row share one card; the rest have their own.
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
                    <div class="bg-white border border-gray-200 rounded-xl p-4 space-y-4" wire:key="run-{{ $run['blocks'][0]['id'] }}">
                        @foreach ($run['blocks'] as $b)
                            @include('livewire.admin.website-data.block', ['b' => $b, 'bare' => true])
                        @endforeach
                    </div>
                @else
                    @include('livewire.admin.website-data.block', ['b' => $run['blocks'][0], 'bare' => false])
                @endif
            @endforeach

            @if (array_filter($page['blocks'], fn ($b) => WebsitePages::counts($b)))
                <div class="flex items-center justify-end gap-3 pt-2">
                    <span x-show="changed" x-cloak class="text-xs text-amber-600">Unsaved changes</span>
                    @include('livewire.admin.website-data.save')
                </div>
            @endif
        </div>
    @endif
</div>
