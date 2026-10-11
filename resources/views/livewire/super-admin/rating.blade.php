<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">School Reviews & Ratings</h1>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $stats['total_reviews'] }}</strong></span>
                    <span class="px-4">Active: <strong class="text-emerald-600">{{ $stats['active_reviews'] }}</strong></span>
                    <span class="px-4">Pending: <strong class="text-amber-500">{{ $stats['pending_reviews'] }}</strong></span>
                    <span class="pl-4">Avg: <strong class="text-yellow-500">{{ number_format($stats['average_rating'], 1) }} ★</strong></span>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $stats['total_reviews'] }}</strong></span>
                <span>Active: <strong class="text-emerald-600">{{ $stats['active_reviews'] }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $stats['pending_reviews'] }}</strong></span>
                <span>Avg: <strong class="text-yellow-500">{{ number_format($stats['average_rating'], 1) }} ★</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search school name..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 flex-[1_1_8rem] min-w-0 max-w-56
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <select wire:model.live="statusFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="2">Pending</option>
                    <option value="3">Archived</option>
                </select>

                <select wire:model.live="ratingFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Ratings</option>
                    <option value="5">★★★★★ 5</option>
                    <option value="4">★★★★ 4</option>
                    <option value="3">★★★ 3</option>
                    <option value="2">★★ 2</option>
                    <option value="1">★ 1</option>
                </select>

                @if ($search || $statusFilter || $ratingFilter)
                    <button wire:click="resetFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY — Review list
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        {{-- The list in the school panel's Enquiries style: one table — S.No, the
             review (its icon, the school, under it the feedback on one line
             cut short with …, then, small, the stars, the school's email, when
             and the status) and the actions (View, and the status switch). A
             click on the row opens the review. --}}
        @if ($reviews->count())
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Review</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-44">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($reviews as $index => $review)
                                @php
                                    $statusTone = $review->status == 1 ? 'text-emerald-600' : ($review->status == 2 ? 'text-amber-600' : 'text-gray-500');
                                @endphp
                                <tr wire:key="review-{{ $review->id }}" wire:click="viewReview({{ $review->id }})"
                                    class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $reviews->firstItem() + $index }}</span>
                                    </td>

                                    {{-- Review --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $review->organization?->name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $review->feedback ?: 'No feedback written' }}</p>
                                                <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                    <span class="text-yellow-500">{{ str_repeat('★', (int) $review->rating) }}</span><span class="text-gray-300">{{ str_repeat('★', max(0, 5 - (int) $review->rating)) }}</span>
                                                    {{ $review->rating }}/5
                                                    @if ($review->organization?->email)
                                                        <span class="mx-1 text-gray-300">·</span>{{ $review->organization->email }}
                                                    @endif
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    {{ \Carbon\Carbon::parse($review->created_at)->timezone('Asia/Kolkata')->format('D, d M Y · g:i A') }}
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    <span class="{{ $statusTone }}">{{ $this->getStatusLabel($review->status) }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="viewReview({{ $review->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <select wire:change="updateStatus({{ $review->id }}, $event.target.value)" title="Status"
                                                class="text-xs border border-gray-200 rounded-md px-2 py-1 bg-white text-gray-700 focus:ring-1 focus:ring-blue-500">
                                                <option value="1" {{ $review->status == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="2" {{ $review->status == 2 ? 'selected' : '' }}>Pending</option>
                                                <option value="3" {{ $review->status == 3 ? 'selected' : '' }}>Archived</option>
                                            </select>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="text-center py-20 bg-white rounded-xl border border-gray-200">
                <div class="w-14 h-14 mx-auto mb-3 bg-yellow-50 rounded-full flex items-center justify-center">
                    <svg class="w-7 h-7 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-800">No reviews found</p>
                <p class="text-sm text-gray-400 mt-1">School ratings & feedback will appear here.</p>
            </div>
        @endif

        @if ($reviews->hasPages())
            <div class="mt-6">{{ $reviews->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($selectedReview)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeReview"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Review Details</h2></div>
                    <button wire:click="closeReview"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                    {{-- School Info --}}
                    <div class="flex items-center gap-4">
                        @if ($selectedReview->organization?->logo)
                            <img src="{{ $selectedReview->organization->logo }}"
                                class="w-14 h-14 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                        @else
                            <div class="w-14 h-14 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0 shadow">
                                <span class="text-xl font-bold text-indigo-600">
                                    {{ strtoupper(substr($selectedReview->organization?->name ?? 'S', 0, 1)) }}
                                </span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <p class="text-base font-bold text-gray-900 truncate">{{ $selectedReview->organization?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ $selectedReview->organization?->email ?? '' }}</p>
                            <p class="text-xs text-blue-500 mt-0.5 font-medium">{{ $selectedReview->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    {{-- Rating --}}
                    <div class="border-t border-gray-100 pt-6">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Rating</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <svg class="w-7 h-7 {{ $i <= $selectedReview->rating ? 'text-yellow-400' : 'text-gray-200' }}" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-3xl font-bold text-gray-800">
                                {{ $selectedReview->rating }}<span class="text-base font-normal text-gray-400">/5</span>
                            </span>
                        </div>
                    </div>

                    {{-- Feedback --}}
                    <div class="border-t border-gray-100 pt-6">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Feedback</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $selectedReview->feedback ?? '—' }}</p>
                    </div>

                    {{-- Status & date --}}
                    <div class="grid grid-cols-2 gap-6 border-t border-gray-100 pt-6">
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Status</p>
                            <span class="inline-flex items-center gap-2 text-sm font-semibold
                                {{ $selectedReview->status == 1 ? 'text-green-700' : '' }}
                                {{ $selectedReview->status == 2 ? 'text-amber-600' : '' }}
                                {{ $selectedReview->status == 3 ? 'text-gray-500' : '' }}">
                                <span class="w-2 h-2 rounded-full
                                    {{ $selectedReview->status == 1 ? 'bg-green-500' : '' }}
                                    {{ $selectedReview->status == 2 ? 'bg-amber-400' : '' }}
                                    {{ $selectedReview->status == 3 ? 'bg-gray-400' : '' }}"></span>
                                {{ $this->getStatusLabel($selectedReview->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Submitted on</p>
                            <p class="text-sm text-gray-800">
                                {{ \Carbon\Carbon::parse($selectedReview->created_at)->timezone('Asia/Kolkata')->format('d M Y · h:i A') }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end flex-shrink-0">
                    <button wire:click="closeReview" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

</div>
