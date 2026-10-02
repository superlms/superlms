<div class="min-h-screen bg-gray-50">
    {{-- Notification component --}}
    <x-notifications />
    <x-dialog />

    @if ($viewMode === 'list')
        {{-- ══════════════════════════════════════════════════
             HEADER (full-width, sticky, analytics + Issue button)
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Report Card</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                            <span class="pr-4">Total: <strong class="text-gray-800">{{ $this->analytics['total_students'] }}</strong></span>
                            <span class="px-4">Active: <strong class="text-blue-600">{{ $this->analytics['active_students'] }}</strong></span>
                            <span class="px-4">Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                            <span class="pl-4">Pending: <strong class="text-amber-500">{{ $this->analytics['pending'] }}</strong></span>
                        </div>

                        <button wire:click="openIssueScreen"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span class="hidden sm:inline">Issue Report Card</span>
                            <span class="sm:hidden">Issue</span>
                        </button>
                    </div>
                </div>

                {{-- Mobile/Tablet stats --}}
                <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                    <span>Total: <strong class="text-gray-800">{{ $this->analytics['total_students'] }}</strong></span>
                    <span>Active: <strong class="text-blue-600">{{ $this->analytics['active_students'] }}</strong></span>
                    <span>Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                    <span>Pending: <strong class="text-amber-500">{{ $this->analytics['pending'] }}</strong></span>
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

                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name or admission no..."
                        class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-52 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />

                    <select wire:model.live="filterStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($this->standards as $standard)
                            <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterSection" @disabled(!$filterStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($this->filterSections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>

                    <select wire:model.live="filterStatus" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Status</option>
                        <option value="issued">Active</option>
                        <option value="revoked">Inactive</option>
                    </select>

                    @if ($search || $filterStandard || $filterSection || $filterStatus)
                        <button wire:click="resetFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif

                    @if ($hasFilters)
                        <span class="ml-auto text-xs text-gray-500">Total: <strong class="text-gray-700">{{ $reportCards->total() }}</strong> report cards</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             BODY
        ══════════════════════════════════════════════════ --}}
        <div class="p-4 sm:p-6">
            @if (!$hasFilters)
                {{-- Home screen: nothing is listed until a filter narrows it down. --}}
                <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Choose a filter to see report cards</p>
                    <p class="text-xs text-gray-400 mt-1">Search a name or admission number, or pick a class, section or status above.</p>
                </div>
            @else
            {{-- The listing, in the Students list's style: the photo opens large on a
                 click, the admission number sits small under the name and the
                 section small under the class, the status is plain text, and the
                 actions are the Students list's plain buttons — View (the card in a
                 slide-in, as a transfer certificate is viewed) and Download (the
                 file, straight away) — then one icon for active / inactive, which a
                 click switches. An inactive card can only be made active again. --}}
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Academic Year</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issued On</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider w-36">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($reportCards as $index => $card)
                                @php $active = $card->status === 'issued'; @endphp
                                <tr wire:key="rc-{{ $card->id }}" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $reportCards->firstItem() + $index }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($card->studentDetail?->user?->image)
                                                <img src="{{ $card->studentDetail->user->image }}" wire:click="showStudentPhoto({{ $card->studentDetail->id }})" title="View photo"
                                                    class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-zoom-in hover:opacity-90">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($card->studentDetail->full_name ?? 'N', 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $card->studentDetail->full_name ?? 'N/A' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $card->studentDetail->admission_no ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-sm text-gray-700">{{ $card->studentDetail?->standard?->name ?? '—' }}</p>
                                        <p class="text-xs text-gray-400">{{ $card->studentDetail?->section?->name ?? '' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $card->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm {{ $active ? 'text-gray-700' : 'text-red-600 font-medium' }}">{{ $active ? 'Active' : 'Inactive' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $card->issued_at ? $card->issued_at->format('d M Y') : 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($active)
                                                <button wire:click="openCardView({{ $card->id }})" title="View"
                                                    class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                {{-- `download`, and no new tab: the file is saved and nothing opens. --}}
                                                <a href="{{ route($downloadRoute, ['organization' => auth()->user()->organization_id, 'id' => $card->id]) }}" download title="Download"
                                                    class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </a>
                                            @endif
                                            {{-- One icon for active / inactive: a green tick while the card
                                                 is active, a red cross while it is not; a click switches it. --}}
                                            <button type="button" wire:click="setCardStatus({{ $card->id }}, '{{ $active ? 'revoked' : 'issued' }}')"
                                                title="{{ $active ? 'Active — click to make inactive' : 'Inactive — click to make active' }}"
                                                class="p-1.5 rounded-lg transition-colors {{ $active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-red-600 hover:bg-red-50' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    @if ($active)
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @else
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    @endif
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-16 text-center">
                                        <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800">No report cards found</p>
                                        <p class="text-xs text-gray-400 mt-1">Issue report cards using the button above.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($reportCards->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        {{ $reportCards->links() }}
                    </div>
                @endif
            </div>
            @endif
        </div>

    @endif

{{-- ══════════════════════════════════════════════════
     ISSUE REPORT CARDS — a slide-in over the list, in two steps.
     1. Pick the class and its section; the students come up, and the ones to
        issue for are ticked. Continue.
     2. A row for each: number, name over admission number, the remark to
        enter (the registration number beside it) and the co-scholastic
        grades to choose. Submit issues them. The result is no longer asked:
        the card works it out from the marks.
══════════════════════════════════════════════════ --}}
@if ($showIssuePanel)
    <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.08] backdrop-blur-[1.5px]" wire:click="backToList"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-5xl bg-white shadow-2xl flex flex-col">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Issue Report Cards</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if ($showIssueForm)
                            {{ count($issueRows) }} student(s) — enter the remark and choose the co-scholastic grades for each
                        @else
                            Pick the class and section, tick the students, then Continue
                        @endif
                    </p>
                </div>
                <button wire:click="backToList" type="button"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            @if (! $showIssueForm)
                {{-- ─────────── Step 1: class, section, students ─────────── --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5" wire:key="issue-step-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="issueStandard"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Class</option>
                                @foreach ($this->standards as $standard)
                                    <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section <span class="text-red-500">*</span></label>
                            <select wire:model.live="issueSection" wire:key="issue-section-{{ $issueStandard ?: 'none' }}" @disabled(!$issueStandard)
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:bg-gray-50 disabled:opacity-60">
                                <option value="">{{ $issueStandard ? 'Select Section' : 'Select a class first' }}</option>
                                @foreach ($this->issueSections as $section)
                                    <option value="{{ $section->id }}">{{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($issueStudentsLoaded)
                        @php
                            $eligibleCount = $this->issueStudents->filter(fn($s) => $s['marks_complete'] && !$s['already_issued'])->count();
                        @endphp
                        <div class="border border-gray-200 rounded-xl overflow-hidden">
                            <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                                <h3 class="text-sm font-semibold text-gray-900">Select Students</h3>
                                <div class="flex flex-wrap items-center gap-4">
                                    <div class="flex flex-wrap items-center gap-3 text-[11px]">
                                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span><span class="text-gray-500">Eligible</span></span>
                                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-gray-300"></span><span class="text-gray-500">Incomplete</span></span>
                                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-400"></span><span class="text-gray-500">Issued</span></span>
                                    </div>
                                    @if ($eligibleCount > 0)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" wire:click="toggleAllEligible($event.target.checked)"
                                                {{ count($selectedStudents) === $eligibleCount && $eligibleCount > 0 ? 'checked' : '' }}
                                                class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="text-xs font-medium text-gray-700">Select all eligible ({{ count($selectedStudents) }}/{{ $eligibleCount }})</span>
                                        </label>
                                    @endif
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 border-b border-gray-200">
                                        <tr>
                                            <th class="px-4 py-3 w-10"></th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roll No.</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Marks Status</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Report Card</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($this->issueStudents as $index => $student)
                                            @php
                                                $isEligible = $student['marks_complete'] && !$student['already_issued'];
                                                $rowClass = !$student['marks_complete'] ? 'opacity-50 bg-gray-50/60' : ($student['already_issued'] ? 'bg-blue-50/40' : '');
                                            @endphp
                                            <tr wire:key="issue-pick-{{ $student['id'] }}" class="{{ $rowClass }} hover:bg-gray-50 transition-colors">
                                                <td class="px-4 py-3">
                                                    @if ($isEligible)
                                                        <input type="checkbox" wire:model.live="selectedStudents" value="{{ $student['id'] }}"
                                                            class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    @else
                                                        <input type="checkbox" disabled class="w-4 h-4 rounded border-gray-200 text-gray-300 cursor-not-allowed">
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $index + 1 }}</span></td>
                                                <td class="px-4 py-3">
                                                    <p class="text-sm font-semibold text-gray-900">{{ $student['full_name'] }}</p>
                                                    <p class="text-xs text-gray-400">{{ $student['admission_no'] ?? '' }}</p>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-gray-600">{{ $student['roll_no'] }}</td>
                                                <td class="px-4 py-3">
                                                    @if ($student['marks_complete'])
                                                        <span class="text-sm text-gray-700">Complete</span>
                                                    @else
                                                        <span class="text-sm text-amber-700">Incomplete</span>
                                                        @if ($student['missing_info'])
                                                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $student['missing_info'] }}</p>
                                                        @endif
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if ($student['already_issued'])
                                                        <span class="text-sm text-blue-700">Already issued</span>
                                                    @elseif ($student['marks_complete'])
                                                        <span class="text-sm text-gray-500">Ready to issue</span>
                                                    @else
                                                        <span class="text-sm text-gray-400">—</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="px-4 py-12 text-center">
                                                    <p class="text-sm font-semibold text-gray-800">No students found</p>
                                                    <p class="text-xs text-gray-400 mt-1">No students are enrolled in this class and section.</p>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="border border-gray-200 rounded-xl px-6 py-14 text-center">
                            <p class="text-sm font-semibold text-gray-800">Select a class and section</p>
                            <p class="text-xs text-gray-400 mt-1">The students of the section come up here to pick from.</p>
                        </div>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-2 px-6 py-3.5 border-t border-gray-200 flex-shrink-0">
                    <div class="text-sm text-gray-600"><strong class="text-gray-900">{{ count($selectedStudents) }}</strong> student(s) selected</div>
                    <div class="flex items-center gap-2">
                        <button wire:click="backToList" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="openIssueForm" type="button" @disabled(empty($selectedStudents)) wire:loading.attr="disabled" wire:target="openIssueForm"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-medium rounded-md">Continue</button>
                    </div>
                </div>
            @else
                {{-- ─────────── Step 2: each student's details ─────────── --}}
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5" wire:key="issue-step-2">

                    {{-- The issue date, and beside it in the same row the grades that can
                         be set for every student at once (each can still be changed below). --}}
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-x-4 gap-y-3 items-start">
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Issue Date <span class="text-red-500">*</span></label>
                            <input wire:model.defer="issueDate" type="date"
                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-sm @error('issueDate') border-red-400 @enderror">
                            @error('issueDate')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        @if (count($issueRows) > 1)
                            <div class="lg:col-span-9" x-data>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Set a grade for all students</label>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    @foreach ($coAreas as $ai => $area)
                                        <div wire:key="co-all-{{ $ai }}">
                                            <div class="grid grid-cols-2 gap-2">
                                                @foreach (['term1' => 'Term 1', 'term2' => 'Term 2'] as $termKey => $termLabel)
                                                    <select x-on:change="$wire.setAllCoGrade('{{ $termKey }}', {{ $ai }}, $event.target.value); $event.target.value = ''" title="{{ $area }} — {{ $termLabel }}, for all students"
                                                        class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-sm text-gray-600">
                                                        <option value="">{{ $termLabel }}</option>
                                                        @foreach ($coGrades as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                                                    </select>
                                                @endforeach
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1 truncate" title="{{ $area }}">{{ $area }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="border border-gray-200 rounded-xl divide-y divide-gray-100">
                        @foreach ($issueRows as $studentId => $row)
                            <div class="px-4 py-4 grid grid-cols-12 gap-x-4 gap-y-3" wire:key="issue-row-{{ $studentId }}">
                                <div class="col-span-12 md:col-span-3 flex gap-3">
                                    <span class="text-sm text-gray-500 font-medium w-6 flex-shrink-0">{{ $loop->iteration }}</span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate">{{ $row['name'] ?: '—' }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $row['admission_no'] ?: '' }}</p>
                                    </div>
                                </div>

                                <div class="col-span-12 md:col-span-9 space-y-3">
                                    {{-- The remark, with the registration number beside it --}}
                                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                        <div class="sm:col-span-3">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Remark <span class="text-red-500">*</span></label>
                                            <input wire:model.defer="issueRows.{{ $studentId }}.remark" type="text" maxlength="500" placeholder="e.g. Very good progress. Keep it up."
                                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-sm @error('issueRows.' . $studentId . '.remark') border-red-400 @enderror">
                                            @error('issueRows.' . $studentId . '.remark')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">Regd. No</label>
                                            <input wire:model.defer="issueRows.{{ $studentId }}.regd_no" type="text" maxlength="50" placeholder="—"
                                                class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-sm">
                                            @error('issueRows.' . $studentId . '.regd_no')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-gray-700 mb-1">Co-Scholastic Grades <span class="text-red-500">*</span></label>
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            @foreach ($coAreas as $ai => $area)
                                                <div>
                                                    <p class="text-xs text-gray-500 mb-1 truncate" title="{{ $area }}">{{ $area }}</p>
                                                    <div class="grid grid-cols-2 gap-2">
                                                        @foreach (['term1' => 'Term 1', 'term2' => 'Term 2'] as $termKey => $termLabel)
                                                            <select wire:model.defer="issueRows.{{ $studentId }}.co.{{ $termKey }}.{{ $ai }}" title="{{ $area }} — {{ $termLabel }}"
                                                                class="w-full px-2 py-1.5 border rounded-md text-sm @error('issueRows.' . $studentId . '.co.' . $termKey . '.' . $ai) border-red-400 @else border-gray-300 @enderror">
                                                                <option value="">{{ $termLabel }}</option>
                                                                @foreach ($coGrades as $g)<option value="{{ $g }}">{{ $g }}</option>@endforeach
                                                            </select>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if ($errors->has('issueRows.' . $studentId . '.co.*'))
                                            <p class="mt-1 text-xs text-red-500">Choose every co-scholastic grade.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <p class="text-xs text-gray-500">
                        The issue date prints as "Issue Date" on every card in this batch. Regd. No is prefilled from the
                        student's registration number.
                    </p>
                </div>

                <div class="flex items-center justify-between gap-2 px-6 py-3.5 border-t border-gray-200 flex-shrink-0">
                    <button wire:click="closeIssueForm" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Back</button>
                    <button wire:click="issueReportCards" type="button" wire:loading.attr="disabled" wire:target="issueReportCards"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 disabled:opacity-60 text-white text-sm font-medium rounded-md">
                        <span wire:loading.remove wire:target="issueReportCards">Submit</span>
                        <span wire:loading wire:target="issueReportCards">Issuing…</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
@endif

{{-- ══════════════════════════════════════════════════
     VIEW — the card in a slide-in panel, as a transfer certificate is viewed:
     the printed page in a frame, Download PDF and Close at the foot.
══════════════════════════════════════════════════ --}}
@if ($viewCard)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCardView"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewCard->studentDetail->full_name ?? 'Report Card' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5 truncate">Report Card{{ $viewCard->academic_year ? ' · ' . $viewCard->academic_year : '' }}</p>
                </div>
                <button wire:click="closeCardView" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-hidden bg-gray-100">
                <iframe src="{{ route($viewRoute, ['organization' => auth()->user()->organization_id, 'id' => $viewCard->id]) }}#toolbar=0&amp;navpanes=0&amp;view=FitH"
                    class="w-full h-full border-0" title="Report Card"></iframe>
            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                <a href="{{ route($downloadRoute, ['organization' => auth()->user()->organization_id, 'id' => $viewCard->id]) }}" download
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md inline-flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    Download PDF
                </a>
                <button type="button" wire:click="closeCardView"
                    class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
            </div>
        </div>
    </div>
@endif

{{-- A student's photo in the list, clicked: shown large in the middle of the
     window (lms-cover), its cross in its own corner — as the Students list. --}}
@if ($studentPhoto)
    <div class="lms-cover fixed inset-0 z-[9999] flex overflow-y-auto p-4 bg-black/80"
        wire:click.self="closeStudentPhoto" x-on:keydown.escape.window="$wire.closeStudentPhoto()">
        <div class="relative m-auto max-w-full">
            <img src="{{ $studentPhoto }}" alt=""
                style="--photo: min(28rem, 90vw, calc(100vh - 8rem)); width: var(--photo); height: var(--photo)"
                class="rounded-lg object-cover shadow-2xl bg-white">
            <button type="button" wire:click="closeStudentPhoto" title="Close"
                class="absolute top-2 right-2 w-9 h-9 flex items-center justify-center rounded-full bg-black/50 hover:bg-black/70 text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
@endif
</div>
