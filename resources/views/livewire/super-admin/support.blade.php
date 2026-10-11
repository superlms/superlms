<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + filter bar)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Support Tickets</h1>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Total: <strong class="text-gray-800">{{ $totalQueries }}</strong></span>
                    <span class="px-4">Pending: <strong class="text-amber-500">{{ $pendingQueries }}</strong></span>
                    <span class="px-4">Replied: <strong class="text-emerald-600">{{ $repliedQueries }}</strong></span>
                    <span class="pl-4">This Month: <strong class="text-blue-600">{{ $thisMonthQueries }}</strong></span>
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $totalQueries }}</strong></span>
                <span>Pending: <strong class="text-amber-500">{{ $pendingQueries }}</strong></span>
                <span>Replied: <strong class="text-emerald-600">{{ $repliedQueries }}</strong></span>
                <span>This Month: <strong class="text-blue-600">{{ $thisMonthQueries }}</strong></span>
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

                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search topic, query, school..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 flex-[1_1_8rem] min-w-0 max-w-56
                           focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Last:</span>
                    <div class="flex gap-1">
                        @foreach ([7, 15, 30] as $days)
                            <button wire:click="$set('filterDays', '{{ $filterDays == $days ? '' : $days }}')"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $filterDays == $days
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $days }}d
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>

                <select wire:model.live="organizationFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="statusFilter"
                    class="text-xs min-w-0 max-w-[13rem] bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="replied">Replied</option>
                </select>

                @if ($search || $filterDays || $organizationFilter || $statusFilter)
                    <button wire:click="clearFilters"
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
         BODY — Ticket list
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        {{-- The list in the school panel's Enquiries style: one table — S.No, the
             ticket (its icon, the topic, under it the query on one line cut
             short with …, then, small, the school, who raised it, when,
             replied or not and an attachment) and the actions. The rest is on
             its View; a click on the row opens it. --}}
        @if ($supports->count())
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($supports as $index => $support)
                                @php $isReplied = (bool) $support->super_admin_reply; @endphp
                                <tr wire:key="support-{{ $support->id }}" wire:click="viewSupport({{ $support->id }})"
                                    class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $supports->firstItem() + $index }}</span>
                                    </td>

                                    {{-- Ticket --}}
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $support->topic ?: 'Untitled' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $support->admin_query ?: '—' }}</p>
                                                <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                    {{ $support->organization?->name ?? 'Unknown school' }}
                                                    @if ($support->user)
                                                        <span class="mx-1 text-gray-300">·</span>{{ $support->user->name }}
                                                        @if ($support->user->email)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $support->user->email }}
                                                        @endif
                                                    @endif
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    {{ $support->created_at->format('D, d M Y · g:i A') }}
                                                    <span class="mx-1 text-gray-300">·</span>
                                                    <span class="{{ $isReplied ? 'text-emerald-600' : 'text-amber-600' }}">{{ $isReplied ? 'Replied' : 'Pending' }}</span>
                                                    @if ($support->image)
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        Attachment
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3" onclick="event.stopPropagation()">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="viewSupport({{ $support->id }})" title="View"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </button>
                                            <button wire:click="openReplyModal({{ $support->id }})" title="{{ $isReplied ? 'Edit Reply' : 'Send Reply' }}"
                                                class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                </svg>
                                            </button>
                                            <button wire:click="confirmDelete({{ $support->id }})" title="Delete"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
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
                <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                    </svg>
                </div>
                <p class="text-base font-semibold text-gray-800">No support tickets found</p>
                <p class="text-sm text-gray-400 mt-1">Tickets raised by school admins will appear here.</p>
            </div>
        @endif

        @if ($supports->hasPages())
            <div class="mt-6">{{ $supports->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDetailModal && $selectedSupport)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Support Details</h2></div>
                    <button wire:click="closeDetailModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Topic</p>
                        <p class="text-base font-semibold text-gray-900">{{ $selectedSupport->topic ?? '—' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        @if ($selectedSupport->user)
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Submitted by</p>
                                <p class="text-sm text-gray-800">{{ $selectedSupport->user->name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Email</p>
                                <p class="text-sm text-gray-800 truncate">{{ $selectedSupport->user->email ?? '—' }}</p>
                            </div>
                        @endif
                        @if ($selectedSupport->organization)
                            <div class="col-span-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">School</p>
                                <p class="text-sm text-gray-800">{{ $selectedSupport->organization->name }}</p>
                            </div>
                        @endif
                    </div>

                    <div class="border-t border-gray-100 pt-6">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Query</p>
                        <p class="text-sm text-gray-700 whitespace-pre-line leading-relaxed">{{ $selectedSupport->admin_query ?? '—' }}</p>
                    </div>

                    @if ($selectedSupport->super_admin_reply && $selectedSupport->super_admin_text)
                        <div class="border-t border-gray-100 pt-6">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs text-gray-400 uppercase tracking-wider">Your Reply</p>
                                <span class="text-xs text-gray-400">{{ $selectedSupport->updated_at->format('d M Y · g:i A') }}</span>
                            </div>
                            <div class="bg-gray-50 border-l-2 border-emerald-500 rounded-r-md px-4 py-3">
                                <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">{{ $selectedSupport->super_admin_text }}</p>
                            </div>

                            @if ($selectedSupport->super_admin_attachment)
                                @php
                                    $replyUrl = $selectedSupport->super_admin_attachment;
                                    $replyExt = strtolower(pathinfo(parse_url($replyUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                @endphp
                                <div class="mt-3">
                                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1.5">Reply attachment</p>
                                    @if (in_array($replyExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                        <img src="{{ $replyUrl }}" class="w-full rounded-md border border-gray-200">
                                        <a href="{{ $replyUrl }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-1.5 inline-block">Open in new tab ↗</a>
                                    @elseif ($replyExt === 'pdf')
                                        <iframe src="{{ $replyUrl }}" class="w-full h-72 border border-gray-200 rounded-md"></iframe>
                                        <a href="{{ $replyUrl }}" target="_blank" class="text-xs text-blue-600 hover:underline mt-1.5 inline-block">Open / Download PDF ↗</a>
                                    @else
                                        <a href="{{ $replyUrl }}" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                            </svg>
                                            Download attachment (.{{ $replyExt }}) ↗
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex items-center gap-2.5 text-sm text-amber-700 border-t border-gray-100 pt-6">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            No reply sent yet
                        </div>
                    @endif

                    <div class="border-t border-gray-100 pt-4 text-xs text-gray-500">
                        Submitted on {{ $selectedSupport->created_at->format('d M Y · g:i A') }}
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button wire:click="openReplyModal({{ $selectedSupport->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $selectedSupport->super_admin_reply ? 'Edit Reply' : 'Send Reply' }}
                    </button>
                    <button wire:click="closeDetailModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         REPLY SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showReplyModal && $selectedSupport)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeReplyModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div><h2 class="text-lg font-semibold text-gray-900">Reply to Support</h2></div>
                    <button wire:click="closeReplyModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
                        <p class="text-xs text-blue-600 uppercase tracking-wider mb-1 font-semibold">Replying to</p>
                        <p class="text-sm font-medium text-gray-900">{{ $selectedSupport->topic }}</p>
                        <p class="text-xs text-gray-500 mt-1">{{ $selectedSupport->user->name ?? '' }} · {{ $selectedSupport->organization->name ?? '' }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Reply <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="superAdminReply" rows="8" placeholder="Type your reply here..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('superAdminReply')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- Reply attachment --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Attachment <span class="text-gray-400 text-xs font-normal">(optional)</span></label>

                        @if ($existingReplyAttachment && !$replyAttachment)
                            @php
                                $curExt = strtolower(pathinfo(parse_url($existingReplyAttachment, PHP_URL_PATH), PATHINFO_EXTENSION));
                            @endphp
                            <div class="flex items-center justify-between gap-3 border border-gray-200 rounded-md px-3 py-2 mb-2 bg-gray-50">
                                <a href="{{ $existingReplyAttachment }}" target="_blank" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline truncate">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                    <span class="truncate">Current attachment (.{{ $curExt }})</span>
                                </a>
                                <button type="button" wire:click="removeReplyAttachment" class="text-xs font-medium text-red-500 hover:text-red-700 flex-shrink-0">Remove</button>
                            </div>
                        @endif

                        <input type="file" wire:model="replyAttachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                            class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-blue-50 file:text-blue-700 file:text-xs file:font-medium hover:file:bg-blue-100" />
                        <div wire:loading wire:target="replyAttachment" class="text-xs text-gray-400 mt-1">Uploading…</div>
                        <p class="text-xs text-gray-400 mt-1">Images, PDF or Word — up to 5&nbsp;MB. The school admin will see this with your reply.</p>
                        @error('replyAttachment')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeReplyModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="sendReply" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="sendReply">{{ $selectedSupport->super_admin_reply ? 'Update Reply' : 'Send Reply' }}</span>
                        <span wire:loading wire:target="sendReply">Sending...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($confirmDeleteId)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete support ticket?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the ticket record. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="deleteSupport({{ $confirmDeleteId }})" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove>Delete</span>
                        <span wire:loading>Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
