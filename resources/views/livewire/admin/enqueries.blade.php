<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (full-width, sticky, analytics + tabs)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Enquiries</h1>
                </div>
                <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                    <span class="pr-4">Students: <strong class="text-gray-800">{{ $totalStudent }}</strong></span>
                    <span class="px-4">Teachers: <strong class="text-gray-800">{{ $totalTeacher }}</strong></span>
                    <span class="px-4">Website: <strong class="text-gray-800">{{ $totalWebsite }}</strong></span>
                    @if ($activeTab !== 'website')
                        <span class="px-4">Pending: <strong class="text-amber-500">{{ $pendingCount }}</strong></span>
                        <span class="pl-4">Replied: <strong class="text-emerald-600">{{ $repliedCount }}</strong></span>
                    @endif
                </div>
            </div>

            {{-- Mobile/Tablet stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Students: <strong class="text-gray-800">{{ $totalStudent }}</strong></span>
                <span>Teachers: <strong class="text-gray-800">{{ $totalTeacher }}</strong></span>
                <span>Website: <strong class="text-gray-800">{{ $totalWebsite }}</strong></span>
                @if ($activeTab !== 'website')
                    <span>Pending: <strong class="text-amber-500">{{ $pendingCount }}</strong></span>
                    <span>Replied: <strong class="text-emerald-600">{{ $repliedCount }}</strong></span>
                @endif
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                <button wire:click="showTab('student')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'student' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                        Student Enquiries
                    </span>
                </button>
                <button wire:click="showTab('teacher')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'teacher' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />
                        </svg>
                        Teacher Enquiries
                    </span>
                </button>
                <button wire:click="showTab('website')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                           {{ $activeTab === 'website' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3M3.6 9h16.8M3.6 15h16.8" />
                        </svg>
                        Website Enquiries
                    </span>
                </button>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search topic, query, name, email..."
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-500 hidden sm:inline">Last:</span>
                    <div class="flex gap-1">
                        @foreach ([7, 15, 30] as $days)
                            <button wire:click="applyFilterDays({{ $days }})"
                                class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                       {{ $filterDays == $days
                                           ? 'bg-blue-600 text-white'
                                           : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                {{ $days }}d
                            </button>
                        @endforeach
                    </div>
                </div>

                @if ($activeTab !== 'website')
                    <div class="h-5 w-px bg-gray-300 hidden sm:block"></div>

                    {{-- Status (All / Pending / Replied), as the Announcements' Audience --}}
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 hidden sm:inline">Status:</span>
                        <div class="flex gap-1">
                            @foreach ([['', 'All'], ['pending', 'Pending'], ['replied', 'Replied']] as $opt)
                                <button wire:click="$set('statusFilter', '{{ $opt[0] }}')"
                                    class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors
                                           {{ $statusFilter === $opt[0]
                                               ? 'bg-blue-600 text-white'
                                               : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50' }}">
                                    {{ $opt[1] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($search || $filterDays || $statusFilter)
                    <button wire:click="clearFilters"
                        class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         BODY — Enquiry list
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">
        {{-- The list, in the Announcements list's style: one table — S.No, the
             enquiry (its icon, the topic, and under it the message on one line,
             cut short with … when it is long; then, small, who sent it, when,
             and — for a student's or teacher's — whether it is replied and has
             an attachment) and the actions. The rest is on its View. --}}
        @if ($enquiries->count())
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full table-fixed">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-16">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Enquiry</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($enquiries as $index => $enquiry)
                                <tr wire:click="viewEnquiry({{ $enquiry->id }})"
                                    class="hover:bg-gray-50/70 transition-colors cursor-pointer">

                                    {{-- S.No --}}
                                    <td class="px-4 py-3">
                                        <span class="text-sm text-gray-500 font-medium">{{ $enquiries->firstItem() + $index }}</span>
                                    </td>

                                    @if ($activeTab === 'website')
                                        {{-- Website enquiry (public school-site submission: name/email/phone/subject/message) --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9 9 0 100-18 9 9 0 000 18zm0 0c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3M3.6 9h16.8M3.6 15h16.8" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $enquiry->subject ?: 'No subject' }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $enquiry->message ?: '—' }}</p>
                                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                        {{ $enquiry->name ?? 'Unknown' }}
                                                        @if ($enquiry->email)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $enquiry->email }}
                                                        @endif
                                                        @if ($enquiry->phone)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $enquiry->phone }}
                                                        @endif
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        {{ $enquiry->created_at->format('D, d M Y · g:i A') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>

                                        {{-- Actions --}}
                                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                                            <div class="flex items-center justify-center gap-1">
                                                <button wire:click="viewEnquiry({{ $enquiry->id }})" title="View"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="deleteEnquiry({{ $enquiry->id }})" title="Delete"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    @else
                                        @php
                                            $isReplied = !empty($enquiry->admin_reply);
                                            $queryText = $enquiry->teacher_query ?? $enquiry->student_query ?? '';
                                        @endphp
                                        {{-- Icon, topic, the message on one line, and under it — small
                                             and plain — who sent it, when, replied or not, and an attachment. --}}
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div class="w-9 h-9 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $enquiry->topic ?? 'No topic' }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $queryText }}</p>
                                                    <p class="text-[11px] text-gray-400 truncate mt-0.5">
                                                        {{ $enquiry->user?->name ?? 'Unknown' }}
                                                        @if ($enquiry->user?->email)
                                                            <span class="mx-1 text-gray-300">·</span>{{ $enquiry->user->email }}
                                                        @endif
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        {{ $enquiry->created_at->format('D, d M Y · g:i A') }}
                                                        <span class="mx-1 text-gray-300">·</span>
                                                        <span class="{{ $isReplied ? 'text-emerald-600' : 'text-amber-600' }}">{{ $isReplied ? 'Replied' : 'Pending' }}</span>
                                                        @if ($enquiry->image)
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
                                                <button wire:click="viewEnquiry({{ $enquiry->id }})" title="View"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <button wire:click="openReplyModal({{ $enquiry->id }})" title="{{ $isReplied ? 'Edit Reply' : 'Reply' }}"
                                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                                    </svg>
                                                </button>
                                                <button wire:click="deleteEnquiry({{ $enquiry->id }})" title="Delete"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    @endif
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
                <p class="text-base font-semibold text-gray-800">No enquiries found</p>
                <p class="text-sm text-gray-400 mt-1">{{ ['teacher' => 'Teacher', 'website' => 'Website'][$activeTab] ?? 'Student' }} enquiries will appear here.</p>
            </div>
        @endif

        @if ($enquiries->hasPages())
            <div class="mt-6">{{ $enquiries->links() }}</div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL — as the Announcement's: one plain card of
         label / value rows, and under it the attachment and the reply.
    ══════════════════════════════════════════════════ --}}
    @if ($showDetailModal && $selectedEnquiry && $activeTab === 'website')
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">Website Enquiry</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            From public website · {{ $selectedEnquiry->created_at->format('d M Y, g:i A') }}
                        </p>
                    </div>
                    <button wire:click="closeDetailModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @php
                        $rows = [
                            'Subject'  => $selectedEnquiry->subject ?: '—',
                            'Message'  => $selectedEnquiry->message ?: '—',
                            'Name'     => $selectedEnquiry->name ?: '—',
                            'Phone'    => $selectedEnquiry->phone ?: '—',
                            'Email'    => $selectedEnquiry->email ?: '—',
                            'Received' => $selectedEnquiry->created_at->format('d M Y · g:i A'),
                        ];
                    @endphp
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        @foreach ($rows as $label => $value)
                            <div class="px-4 py-3">
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">{{ $label }}</p>
                                <p class="mt-1 text-sm break-words {{ $label === 'Subject' ? 'font-semibold text-gray-900' : 'text-gray-800' }} {{ $label === 'Message' ? 'whitespace-pre-line leading-relaxed' : '' }}">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end flex-shrink-0">
                    <button wire:click="closeDetailModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @elseif ($showDetailModal && $selectedEnquiry)
        {{-- Student / teacher enquiry detail — the same card, plus the reply. --}}
        @php
            $detailQueryText = $selectedEnquiry->teacher_query ?? $selectedEnquiry->student_query ?? '';
            $rows = [
                'Topic'        => $selectedEnquiry->topic ?: '—',
                'Message'      => $detailQueryText ?: '—',
                'From'         => $selectedEnquiry->user?->name ?: '—',
                'Email'        => $selectedEnquiry->user?->email ?: '—',
                'Organization' => $selectedEnquiry->organization?->name ?: '—',
                'Status'       => $selectedEnquiry->admin_reply ? 'Replied' : 'Pending',
                'Received'     => $selectedEnquiry->created_at->format('d M Y · g:i A'),
            ];
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDetailModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="block w-2 h-2 rounded-full flex-shrink-0 {{ $selectedEnquiry->admin_reply ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">Enquiry Details</h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $selectedEnquiry->admin_reply ? 'Replied' : 'Pending' }} · {{ $selectedEnquiry->created_at->format('d M Y') }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeDetailModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                        @foreach ($rows as $label => $value)
                            <div class="px-4 py-3">
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">{{ $label }}</p>
                                <p class="mt-1 text-sm break-words
                                    {{ $label === 'Topic' ? 'font-semibold text-gray-900' : '' }}
                                    {{ $label === 'Message' ? 'text-gray-800 whitespace-pre-line leading-relaxed' : '' }}
                                    {{ $label === 'Status' ? ($selectedEnquiry->admin_reply ? 'text-emerald-600' : 'text-amber-600') : '' }}
                                    {{ !in_array($label, ['Topic', 'Message', 'Status']) ? 'text-gray-800' : '' }}">{{ $value }}</p>
                            </div>
                        @endforeach
                    </div>

                    @if ($selectedEnquiry->image)
                        @php $ext = strtolower(pathinfo(parse_url($selectedEnquiry->image, PHP_URL_PATH), PATHINFO_EXTENSION)); @endphp
                        <div class="rounded-xl border border-gray-200 overflow-hidden">
                            @if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <a href="{{ $selectedEnquiry->image }}" target="_blank" rel="noopener" class="block px-4 pt-3">
                                    <img src="{{ $selectedEnquiry->image }}" alt="Attachment"
                                        class="max-h-40 rounded-md border border-gray-200">
                                </a>
                            @endif
                            <a href="{{ $selectedEnquiry->image }}" target="_blank" rel="noopener" title="Open attachment"
                                class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 transition-colors">
                                <svg class="w-5 h-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] text-gray-400 uppercase tracking-wider">Attachment</p>
                                    <p class="mt-0.5 text-sm font-medium text-gray-800">Open attachment</p>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    @if ($selectedEnquiry->admin_reply)
                        <div class="rounded-xl border border-gray-200">
                            <div class="px-4 py-3">
                                <p class="text-[11px] text-gray-400 uppercase tracking-wider">
                                    Reply
                                    <span class="normal-case tracking-normal text-gray-300 ml-1">{{ $selectedEnquiry->updated_at->format('d M Y, g:i A') }}</span>
                                </p>
                                <p class="mt-1 text-sm text-gray-800 whitespace-pre-line leading-relaxed break-words">{{ $selectedEnquiry->admin_text }}</p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <button wire:click="openReplyModal({{ $selectedEnquiry->id }})"
                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                        </svg>
                        {{ $selectedEnquiry->admin_reply ? 'Edit Reply' : 'Reply' }}
                    </button>
                    <button wire:click="closeDetailModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         REPLY SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showReplyModal && $selectedEnquiry)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeReplyModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $selectedEnquiry->admin_reply ? 'Edit Reply' : 'Send Reply' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Replying to {{ $selectedEnquiry->user?->name ?? '—' }}</p>
                    </div>
                    <button wire:click="closeReplyModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    @php $replyQueryText = $selectedEnquiry->teacher_query ?? $selectedEnquiry->student_query ?? ''; @endphp
                    <div class="bg-gray-50 rounded-md p-3 border border-gray-200">
                        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1.5">{{ $selectedEnquiry->topic ?? 'Enquiry' }}</p>
                        <p class="text-sm text-gray-700 leading-relaxed line-clamp-4">{{ $replyQueryText }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Your Reply <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="adminReply" rows="8" placeholder="Type your reply here..."
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm text-gray-800 resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                        @error('adminReply')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeReplyModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="sendReply" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="sendReply">Send Reply</span>
                        <span wire:loading wire:target="sendReply">Sending...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
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
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete enquiry?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the enquiry and any attachment.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="confirmDelete">Delete</span>
                        <span wire:loading wire:target="confirmDelete">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
