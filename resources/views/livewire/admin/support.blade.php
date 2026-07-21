<div class="min-h-screen bg-gray-50">

    {{-- ══════════ HEADER ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3 sm:py-4 sticky top-0 z-50">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Support</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage queries from students & teachers</p>
            </div>
            <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalQueries }}</strong></span>
                <span class="px-4">Pending: <strong class="text-amber-500">{{ $pendingQueries }}</strong></span>
                <span class="px-4">Replied: <strong class="text-emerald-600">{{ $repliedQueries }}</strong></span>
                <span class="px-4">Students: <strong class="text-indigo-600">{{ $totalStudentQueries }}</strong></span>
                <span class="pl-4">Teachers: <strong class="text-purple-600">{{ $totalTeacherQueries }}</strong></span>
            </div>
        </div>

        {{-- Mobile Stats --}}
        <div class="flex lg:hidden flex-wrap gap-3 text-xs text-gray-500 mt-3">
            <span>Total: <strong class="text-gray-800">{{ $totalQueries }}</strong></span>
            <span>Pending: <strong class="text-amber-500">{{ $pendingQueries }}</strong></span>
            <span>Replied: <strong class="text-emerald-600">{{ $repliedQueries }}</strong></span>
            <span>Students: <strong class="text-indigo-600">{{ $totalStudentQueries }}</strong></span>
            <span>Teachers: <strong class="text-purple-600">{{ $totalTeacherQueries }}</strong></span>
        </div>

        {{-- ── FILTERS ── --}}
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">

                {{-- Search --}}
                <div class="sm:col-span-2 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="Search topic, query, name..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" />
                </div>

                {{-- Type Filter --}}
                <select wire:model.live="typeFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Types</option>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>

                {{-- Status --}}
                <select wire:model.live="statusFilter"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg
                           focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="replied">Replied</option>
                </select>

                {{-- Date + Clear --}}
                <div class="flex gap-2">
                    <select wire:model.live="filterDays"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">All Time</option>
                        <option value="7">Last 7 days</option>
                        <option value="15">Last 15 days</option>
                        <option value="30">Last 30 days</option>
                    </select>
                    @if ($search || $typeFilter || $statusFilter || $filterDays)
                        <button wire:click="clearFilters" title="Clear filters"
                            class="px-3 py-2 text-sm text-red-600 border border-red-200 bg-red-50
                                   hover:bg-red-100 rounded-lg transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4">

        {{-- ══════════ TABLE ══════════ --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Topic</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Query</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($supports as $i => $support)
                            <tr class="hover:bg-gray-50/70 transition-colors">

                                {{-- # --}}
                                <td class="px-4 py-3 text-xs text-gray-400">
                                    {{ $supports->firstItem() + $i }}
                                </td>

                                {{-- Type --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($support->_type === 'student')
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                            Student
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                            </svg>
                                            Teacher
                                        </span>
                                    @endif
                                </td>

                                {{-- User --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full {{ $support->_type === 'student' ? 'bg-indigo-100' : 'bg-purple-100' }} flex items-center justify-center flex-shrink-0">
                                            <span class="text-xs font-bold {{ $support->_type === 'student' ? 'text-indigo-600' : 'text-purple-600' }}">
                                                {{ strtoupper(substr($support->user?->name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate max-w-[130px]">
                                                {{ $support->user?->name ?? '—' }}
                                            </p>
                                            <p class="text-xs text-gray-400 truncate max-w-[130px]">
                                                {{ $support->user?->email ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                {{-- Topic --}}
                                <td class="px-4 py-3">
                                    <p class="text-sm font-medium text-gray-800 truncate max-w-[140px]">
                                        {{ $support->topic }}
                                    </p>
                                    @if ($support->image)
                                        <span class="inline-flex items-center gap-1 text-xs text-purple-600 mt-0.5">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Attachment
                                        </span>
                                    @endif
                                </td>

                                {{-- Query --}}
                                <td class="px-4 py-3">
                                    <p class="text-sm text-gray-600 truncate max-w-[200px]" title="{{ $support->_query }}">
                                        {{ $support->_query }}
                                    </p>
                                </td>

                                {{-- Status --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @if ($support->admin_reply)
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">
                                            <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                                            Replied
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                            <span class="w-1.5 h-1.5 bg-amber-400 rounded-full"></span>
                                            Pending
                                        </span>
                                    @endif
                                </td>

                                {{-- Date --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <p class="text-xs font-medium text-gray-700">
                                        {{ $support->created_at->format('d M Y') }}
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $support->created_at->diffForHumans() }}</p>
                                </td>

                                {{-- Actions --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="viewSupport({{ $support->id }}, '{{ $support->_type }}')"
                                            class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        <button wire:click="openReplyModal({{ $support->id }}, '{{ $support->_type }}')"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Reply">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                            </svg>
                                        </button>
                                        <button wire:click="deleteSupport({{ $support->id }}, '{{ $support->_type }}')"
                                            wire:confirm="Are you sure you want to delete this support ticket?"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-14 text-center">
                                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 text-sm">No support queries found</p>
                                    @if ($search || $typeFilter || $statusFilter || $filterDays)
                                        <button wire:click="clearFilters"
                                            class="mt-3 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            Clear filters
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if ($supports->hasPages())
                <div class="px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        Showing <strong class="text-gray-700">{{ $supports->firstItem() }}</strong>
                        to <strong class="text-gray-700">{{ $supports->lastItem() }}</strong>
                        of <strong class="text-gray-700">{{ $supports->total() }}</strong> queries
                    </p>
                    <div class="flex items-center gap-1">
                        @if ($supports->onFirstPage())
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">&laquo; Prev</span>
                        @else
                            <button wire:click="previousPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                &laquo; Prev
                            </button>
                        @endif
                        @foreach ($supports->getUrlRange(max(1, $supports->currentPage() - 2), min($supports->lastPage(), $supports->currentPage() + 2)) as $page => $url)
                            <button wire:click="gotoPage({{ $page }})"
                                class="px-3 py-1.5 text-sm rounded-lg transition-colors
                                    {{ $page == $supports->currentPage()
                                        ? 'bg-indigo-600 text-white border border-indigo-600'
                                        : 'text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                                {{ $page }}
                            </button>
                        @endforeach
                        @if ($supports->hasMorePages())
                            <button wire:click="nextPage"
                                class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Next &raquo;
                            </button>
                        @else
                            <span class="px-3 py-1.5 text-sm text-gray-300 border border-gray-200 rounded-lg cursor-not-allowed">Next &raquo;</span>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════ VIEW DETAIL MODAL ══════════ --}}
    @if ($showDetailModal && $selectedSupport)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg flex flex-col" style="max-height: 85vh;">

                {{-- Fixed Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-t-2xl flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-full {{ $selectedType === 'student' ? 'bg-indigo-100' : 'bg-purple-100' }} flex items-center justify-center">
                            <span class="text-sm font-bold {{ $selectedType === 'student' ? 'text-indigo-600' : 'text-purple-600' }}">
                                {{ strtoupper(substr($selectedSupport->user?->name ?? 'U', 0, 1)) }}
                            </span>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">{{ $selectedSupport->user?->name ?? '—' }}</h3>
                            <p class="text-xs text-gray-400">{{ ucfirst($selectedType) }} Query</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($selectedType === 'student')
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">Student</span>
                        @else
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-purple-50 text-purple-700 border border-purple-100">Teacher</span>
                        @endif
                        @if ($selectedSupport->admin_reply)
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-green-50 text-green-700 border border-green-100">Replied</span>
                        @else
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium bg-amber-50 text-amber-700 border border-amber-100">Pending</span>
                        @endif
                        <button wire:click="closeDetailModal"
                            class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Scrollable Body --}}
                <div class="p-5 space-y-4 overflow-y-auto flex-1">

                    {{-- Meta --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Submitted By</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $selectedSupport->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400">{{ $selectedSupport->user?->email ?? '' }}</p>
                        </div>
                        <div class="bg-gray-50 rounded-xl p-3">
                            <p class="text-xs text-gray-400 mb-0.5">Date</p>
                            <p class="text-sm font-semibold text-gray-800">
                                {{ $selectedSupport->created_at->format('d M Y') }}
                            </p>
                            <p class="text-xs text-gray-400">{{ $selectedSupport->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    {{-- Topic --}}
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Topic</p>
                        <p class="text-sm font-semibold text-gray-800">{{ $selectedSupport->topic }}</p>
                    </div>

                    {{-- Query --}}
                    <div class="bg-gray-50 rounded-xl p-3">
                        <p class="text-xs text-gray-400 mb-1">Message</p>
                        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">
                            {{ $selectedType === 'student' ? $selectedSupport->student_query : $selectedSupport->teacher_query }}
                        </p>
                    </div>

                    {{-- ── Attachment ── --}}
                    @if ($selectedSupport->image)
                        @php
                            $attUrl = $selectedSupport->image;
                            $attExt = strtolower(pathinfo(parse_url($attUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                        @endphp
                        <div class="bg-purple-50 rounded-xl border border-purple-100 p-3">
                            <p class="text-xs font-semibold text-purple-600 mb-2 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Attachment
                            </p>
                            @if (in_array($attExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <img src="{{ $attUrl }}"
                                    class="max-w-full rounded-lg shadow-sm border border-purple-100 mb-2"
                                    alt="Attachment">
                                <a href="{{ $attUrl }}" target="_blank"
                                    class="text-xs text-indigo-600 hover:underline">Open in new tab</a>
                            @elseif ($attExt === 'pdf')
                                <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-purple-100">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">PDF Document</p>
                                        <a href="{{ $attUrl }}" target="_blank"
                                            class="text-xs text-indigo-600 hover:underline">Open / Download PDF</a>
                                    </div>
                                </div>
                            @else
                                <a href="{{ $attUrl }}" target="_blank"
                                    class="text-sm text-indigo-600 hover:underline">View Attachment</a>
                            @endif
                        </div>
                    @endif

                    {{-- ── Reply Section ── --}}
                    @if ($selectedSupport->admin_reply)
                        <div class="bg-indigo-50 rounded-xl border border-indigo-100 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm font-semibold text-indigo-700 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                    </svg>
                                    Admin Reply
                                </p>
                                <span class="text-xs text-indigo-400">
                                    {{ $selectedSupport->updated_at->format('d M Y') }}
                                </span>
                            </div>
                            <div class="bg-white rounded-lg border border-indigo-100 p-3">
                                <p class="text-sm text-indigo-800 whitespace-pre-line leading-relaxed">
                                    {{ $selectedSupport->admin_text }}
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="bg-amber-50 rounded-xl border border-amber-100 p-3 flex items-center gap-3">
                            <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="text-sm font-medium text-amber-700">Awaiting reply from Admin</span>
                        </div>
                    @endif
                </div>

                {{-- Fixed Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 flex items-center gap-2 flex-shrink-0 rounded-b-2xl bg-white">
                    <button wire:click="openReplyModal({{ $selectedSupport->id }}, '{{ $selectedType }}'); closeDetailModal();"
                        class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                        {{ $selectedSupport->admin_reply ? 'Update Reply' : 'Reply' }}
                    </button>
                    <button wire:click="closeDetailModal"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg transition-colors">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ REPLY MODAL ══════════ --}}
    @if ($showReplyModal && $selectedSupport)
        <div class="fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg flex flex-col" style="max-height: 85vh;">

                {{-- Fixed Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-t-2xl flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-emerald-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900">
                                {{ $selectedSupport->admin_reply ? 'Update Reply' : 'Send Reply' }}
                            </h3>
                            <p class="text-xs text-gray-400">{{ ucfirst($selectedType) }} - {{ $selectedSupport->user?->name ?? '—' }}</p>
                        </div>
                    </div>
                    <button wire:click="closeReplyModal"
                        class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-white rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable Body --}}
                <div class="p-5 space-y-4 overflow-y-auto flex-1">

                    {{-- Original Query Summary --}}
                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-200">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Original Query</p>
                        <p class="text-xs font-semibold text-gray-700">{{ $selectedSupport->topic }}</p>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-3 leading-relaxed">
                            {{ $selectedType === 'student' ? $selectedSupport->student_query : $selectedSupport->teacher_query }}
                        </p>
                        @if ($selectedSupport->image)
                            <a href="{{ $selectedSupport->image }}" target="_blank"
                                class="inline-flex items-center gap-1 text-xs text-purple-600 hover:underline mt-1.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                View Attachment
                            </a>
                        @endif
                    </div>

                    {{-- Reply Textarea --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">
                            Your Reply <span class="text-red-500">*</span>
                        </label>
                        <textarea wire:model.defer="adminReply" rows="6" placeholder="Type your reply here..."
                            class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400
                                   resize-none leading-relaxed">
                        </textarea>
                        @error('adminReply')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Fixed Footer --}}
                <div class="px-5 py-4 border-t border-gray-100 flex items-center gap-2 flex-shrink-0 rounded-b-2xl bg-white">
                    <button wire:click="sendReply"
                        class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm
                               font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                        {{ $selectedSupport->admin_reply ? 'Update Reply' : 'Send Reply' }}
                    </button>
                    <button wire:click="closeReplyModal"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                               font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
