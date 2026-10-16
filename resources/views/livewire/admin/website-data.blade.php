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
        {{-- ══════════ THE PAGES ══════════ --}}
        <div class="max-w-3xl mx-auto px-4 sm:px-6 py-6 space-y-6">
            <div class="px-1">
                <h1 class="text-lg font-semibold text-gray-900">Website Data</h1>
                <p class="text-sm text-gray-500 mt-0.5">Open a page to fill in what it shows. It is live on your website as soon as you save.</p>
            </div>

            <button type="button" wire:click="openPage('details')"
                class="w-full flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3.5 text-left hover:bg-gray-50 transition-colors">
                <span class="w-9 h-9 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-5h6v5M9 11h.01M15 11h.01"/></svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium text-gray-900">School Details</span>
                    <span class="block text-xs text-gray-500 truncate">Name, logo, phone, address and social links — on every page</span>
                </span>
                @include('livewire.admin.website-data.status', ['status' => $listing['details']['status']])
                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>

            @foreach ($listing['groups'] as $group => $rows)
                <section wire:key="grp-{{ \Illuminate\Support\Str::slug($group) }}">
                    <h2 class="px-1 mb-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">{{ $group }}</h2>
                    <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100 overflow-hidden">
                        @foreach ($rows as $slug => $row)
                            <button type="button" wire:click="openPage('{{ $slug }}')" wire:key="pg-{{ $slug }}"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-gray-50 transition-colors">
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm text-gray-800">{{ $row['label'] }}</span>
                                    <span class="block text-xs text-gray-400 truncate">{{ $row['summary'] ?: 'A form — nothing to fill in' }}</span>
                                </span>
                                @include('livewire.admin.website-data.status', ['status' => $row['status']])
                                <svg class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
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
