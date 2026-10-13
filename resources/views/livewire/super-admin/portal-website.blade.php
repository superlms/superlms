<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER (figures + filter) — the Students page's look ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">School Websites</h1>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Schools: <strong class="text-gray-800">{{ number_format($stats['total']) }}</strong></span>
                    <span class="px-4">Published: <strong class="text-emerald-600">{{ number_format($stats['published']) }}</strong></span>
                    <span class="px-4">Draft: <strong class="text-amber-600">{{ number_format($stats['draft']) }}</strong></span>
                    <span class="pl-4">Not created: <strong class="text-gray-800">{{ number_format($stats['not_created']) }}</strong></span>
                </div>
            </div>

            {{-- Mobile / Tablet figures --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Schools: <strong class="text-gray-800">{{ number_format($stats['total']) }}</strong></span>
                <span>Published: <strong class="text-emerald-600">{{ number_format($stats['published']) }}</strong></span>
                <span>Draft: <strong class="text-amber-600">{{ number_format($stats['draft']) }}</strong></span>
                <span>Not created: <strong class="text-gray-800">{{ number_format($stats['not_created']) }}</strong></span>
            </div>
        </div>

        {{-- ── Filter bar ── --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative flex-[1_1_8rem] min-w-0 max-w-56">
                    <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or email…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-3 py-1.5 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="statusFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Status</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="none">Not created</option>
                </select>

                @if ($search !== '' || $statusFilter !== '')
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    @php
        // Status as a plain coloured word.
        $siteStatus = function ($site): array {
            if ($site && $site->status) return ['Published', 'text-emerald-600'];
            if ($site)                  return ['Draft', 'text-amber-600'];
            return ['Not created', 'text-gray-400'];
        };
    @endphp

    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5">

        {{-- ══════════ DESKTOP TABLE ══════════ --}}
        <div class="hidden md:block bg-white rounded-xl border border-gray-200 shadow-sm">
            <div class="overflow-x-auto rounded-xl">
                <table class="w-full min-w-[760px] table-fixed">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">School</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-[28%]">Domain</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-32">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($organizations as $index => $org)
                            @php
                                $site = $sites[$org->id] ?? null;
                                [$statusText, $statusTone] = $siteStatus($site);
                            @endphp
                            <tr class="hover:bg-gray-50/70 transition-colors" wire:key="site-{{ $org->id }}">
                                {{-- S.No --}}
                                <td class="px-4 py-3">
                                    <span class="text-sm text-gray-500 font-medium">{{ $organizations->firstItem() + $index }}</span>
                                </td>

                                {{-- School --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if ($org->logo)
                                            <img src="{{ $org->logo }}" alt="" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($org->name, 0, 1)) }}</span>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $org->name }}">{{ $org->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $org->email ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Domain --}}
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-700 truncate" title="{{ $site?->domain }}">{{ $site?->domain ?: '—' }}</p>
                                </td>

                                {{-- Status: plain text --}}
                                <td class="px-4 py-3 text-sm font-medium {{ $statusTone }}">{{ $statusText }}</td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        @if ($site && $site->status && $site->domain)
                                            <a href="https://{{ $site->domain }}" target="_blank" rel="noopener" title="View live"
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" /></svg>
                                            </a>
                                        @endif
                                        @if ($site)
                                            <a href="{{ route('super-admin.school-website.edit', $org->id) }}" title="Edit website"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </a>
                                        @else
                                            <a href="{{ route('super-admin.school-website.edit', $org->id) }}" title="Create website"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <p class="text-gray-500 text-sm">No schools found</p>
                                    @if ($search !== '' || $statusFilter !== '')
                                        <button wire:click="clearFilters" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Clear filters</button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination Desktop --}}
            @if ($organizations->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <strong class="text-gray-700">{{ $organizations->firstItem() }}</strong>
                        to <strong class="text-gray-700">{{ $organizations->lastItem() }}</strong>
                        of <strong class="text-gray-700">{{ $organizations->total() }}</strong> schools
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($organizations->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo; Prev</span>
                        @else
                            <button wire:click="previousPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">&laquo; Prev</button>
                        @endif

                        @foreach ($organizations->getUrlRange(max(1, $organizations->currentPage() - 2), min($organizations->lastPage(), $organizations->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors
                                    {{ $page == $organizations->currentPage() ? 'bg-blue-600 text-white border border-blue-600' : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                                {{ $page }}
                            </button>
                        @endforeach

                        @if ($organizations->hasMorePages())
                            <button wire:click="nextPage" class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">Next &raquo;</button>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- ══════════ MOBILE CARDS ══════════ --}}
        <div class="md:hidden space-y-3">
            @forelse ($organizations as $index => $org)
                @php
                    $site = $sites[$org->id] ?? null;
                    [$statusText, $statusTone] = $siteStatus($site);
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4" wire:key="site-card-{{ $org->id }}">
                    <div class="flex items-start gap-3">
                        @if ($org->logo)
                            <img src="{{ $org->logo }}" alt="" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">{{ strtoupper(substr($org->name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-gray-900 truncate">{{ $org->name }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $site?->domain ?: ($org->email ?: '—') }}</p>
                            <p class="text-xs font-medium mt-1 {{ $statusTone }}">{{ $statusText }}</p>
                        </div>
                    </div>
                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100 mt-3 pt-1 -mb-1">
                        @if ($site && $site->status && $site->domain)
                            <a href="https://{{ $site->domain }}" target="_blank" rel="noopener" class="flex-1 py-2 text-center text-xs font-medium text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">View Live</a>
                        @endif
                        <a href="{{ route('super-admin.school-website.edit', $org->id) }}" class="flex-1 py-2 text-center text-xs font-medium {{ $site ? 'text-amber-600 hover:bg-amber-50' : 'text-blue-600 hover:bg-blue-50' }} rounded-lg transition-colors">{{ $site ? 'Edit' : 'Create' }} Website</a>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-10 text-center text-sm text-gray-500">No schools found</div>
            @endforelse

            @if ($organizations->hasPages())
                <div class="flex items-center justify-between gap-2 pt-1">
                    <button wire:click="previousPage" @disabled($organizations->onFirstPage())
                        class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:text-gray-300 disabled:border-gray-200">&laquo; Prev</button>
                    <span class="text-xs text-gray-500">{{ $organizations->firstItem() }}–{{ $organizations->lastItem() }} of {{ $organizations->total() }}</span>
                    <button wire:click="nextPage" @disabled(!$organizations->hasMorePages())
                        class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:text-gray-300 disabled:border-gray-200">Next &raquo;</button>
                </div>
            @endif
        </div>
    </div>
</div>
