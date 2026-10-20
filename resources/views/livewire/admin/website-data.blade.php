{{--
    More → Website Data. Opens on the site's pages under the menus the site
    shows them in, School Details above them; a page opens on the blocks that
    page shows (App\Support\WebsitePages), saved with Save. Photos and files
    upload as soon as they are picked.
--}}
@php
    $leave = "if (! changed || confirm('Leave this page without saving your changes?'))";
@endphp
<div class="min-h-screen bg-gray-50"
     x-data="{ changed: @entangle('dirty') }"
     x-on:beforeunload.window="if (changed) { $event.preventDefault(); $event.returnValue = ''; }">
    <style>[x-cloak]{display:none !important;}</style>

    @if (! $page)
        {{-- ══════════ THE PAGES — Quick Links' tiles: a round coloured icon,
             the page's name and whether it is filled in, a grid under each of
             the site's menus (App\Support\WebsitePages::tile). ══════════ --}}
        @php
            $allRows = collect($listing['groups'])->flatMap(fn ($rows) => $rows);
            $filled  = $allRows->where('status', 'filled')->count() + ($listing['details']['status'] === 'filled' ? 1 : 0);
            $fillable = $allRows->where('status', '!=', 'form')->count() + 1;
        @endphp
        <div class="bg-white border-b border-gray-200 px-3 sm:px-6 py-2.5 sm:py-3 sticky top-0 z-30">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900 mr-1">Website Data</h1>
                <span class="hidden sm:block h-5 w-px bg-gray-200"></span>
                <p class="text-xs sm:text-sm text-gray-500">Open a page to fill in what it shows — it is live on your website as soon as you save.</p>
                <span class="ml-auto text-sm text-gray-500">Filled: <strong class="text-gray-800">{{ $filled }}</strong> of {{ $fillable }}</span>
            </div>
        </div>

        <div class="p-3 sm:p-4 md:p-5 space-y-5">
            @php
                $tiles = ['School' => [\App\Support\WebsitePages::DETAILS => [
                    'label'   => 'School Details',
                    'summary' => 'Name, logo, phone, address and social links — on every page',
                    'status'  => $listing['details']['status'],
                ]]] + $listing['groups'];
            @endphp
            @foreach ($tiles as $group => $rows)
                <section wire:key="grp-{{ \Illuminate\Support\Str::slug($group) }}">
                    <h2 class="px-1 mb-2 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $group }}</h2>
                    <div class="wd-grid grid gap-2.5 sm:gap-3 md:gap-3.5">
                        @foreach ($rows as $slug => $row)
                            @php [$icon, $color] = \App\Support\WebsitePages::tile($slug); @endphp
                            <button type="button" wire:click="openPage('{{ $slug }}')" wire:key="pg-{{ $slug }}"
                                title="{{ $row['summary'] ?: 'A form — nothing to fill in' }}"
                                class="group flex flex-col items-center justify-start rounded-2xl sm:rounded-xl border border-gray-200 bg-white
                                       hover:bg-gray-50 hover:border-gray-300 active:scale-[0.97] transition p-2.5 sm:p-2 md:p-2.5 overflow-hidden min-w-0">
                                <span class="w-11 h-11 sm:w-9 sm:h-9 md:w-10 md:h-10 mb-1.5 sm:mb-1 bg-{{ $color }}-100 rounded-full flex items-center justify-center shrink-0">
                                    <x-icon name="{{ $icon }}" class="w-5 h-5 sm:w-4 sm:h-4 md:w-5 md:h-5 text-{{ $color }}-600" />
                                </span>
                                <span class="block w-full text-[11px] md:text-xs leading-tight font-medium text-center text-gray-800 break-words"
                                      style="overflow-wrap: anywhere; word-break: break-word;">{{ $row['label'] }}</span>
                                <span class="mt-1 inline-flex items-center gap-1 text-[10px] leading-none
                                             {{ $row['status'] === 'filled' ? 'text-emerald-600' : 'text-gray-400' }}">
                                    @if ($row['status'] === 'filled')
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Filled
                                    @elseif ($row['status'] === 'empty')
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>Empty
                                    @else
                                        Form
                                    @endif
                                </span>
                            </button>
                        @endforeach
                    </div>
                    @if ($group === 'Mandatory Disclosure' && $site->templateKey() === 'classic')
                        <p class="px-1 mt-1.5 text-xs text-gray-400">The Documents menu fills itself — every disclosure you upload gets a page of its own.</p>
                    @endif
                </section>
            @endforeach

            @if ($listing['off'])
                <p class="px-1 text-xs text-gray-400">
                    {{ $listing['off'] }} more {{ \Illuminate\Support\Str::plural('page', $listing['off']) }} switched off for your site — Super Admin can turn them on.
                </p>
            @endif
        </div>

        {{-- Quick Links' grid: 3 across on a phone, 4 on a tablet, 6 on a desktop. --}}
        <style>
            .wd-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            @media (min-width: 640px) { .wd-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
            @media (min-width: 768px) { .wd-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }
        </style>
    @else
        {{-- ══════════ ONE PAGE ══════════ --}}
        <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="max-w-3xl mx-auto px-4 sm:px-6 py-2.5 flex items-center gap-3">
                <button type="button" x-on:click="{{ $leave }} $wire.closePage()" title="All pages"
                    class="p-1.5 -ml-1.5 rounded-lg text-gray-500 hover:text-gray-800 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] text-gray-400 truncate leading-tight">Website Data{{ $page['group'] ? ' › ' . $page['group'] : '' }}</p>
                    <h1 class="text-base font-semibold text-gray-900 truncate leading-tight">{{ $page['label'] }}</h1>
                </div>
                <span x-show="changed" x-cloak class="hidden sm:inline text-xs text-amber-600">Unsaved changes</span>
                @if ($pageUrl)
                    <a href="{{ $pageUrl }}" target="_blank" rel="noopener"
                        class="hidden sm:inline-flex items-center gap-1 px-3 py-1.5 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50">
                        View page
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif
                @include('livewire.admin.website-data.save')
            </div>
        </div>

        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-5 space-y-3" wire:key="page-{{ $open }}" x-on:input="changed = true">
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

            @if (array_filter($page['blocks'], fn ($b) => \App\Support\WebsitePages::counts($b)))
                <div class="flex items-center justify-end gap-3 pt-2">
                    <span x-show="changed" x-cloak class="text-xs text-amber-600">Unsaved changes</span>
                    @include('livewire.admin.website-data.save')
                </div>
            @endif
        </div>
    @endif
</div>
