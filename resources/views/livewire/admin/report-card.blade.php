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
                        <option value="issued">Issued</option>
                        <option value="revoked">Revoked</option>
                    </select>

                    <select wire:model.live="perPage" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="10">10 / page</option>
                        <option value="25">25 / page</option>
                        <option value="50">50 / page</option>
                    </select>

                    @if ($search || $filterStandard || $filterSection || $filterStatus)
                        <button wire:click="resetFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif

                    <span class="ml-auto text-xs text-gray-500">Total: <strong class="text-gray-700">{{ $reportCards->total() }}</strong> report cards</span>
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             BODY
        ══════════════════════════════════════════════════ --}}
        <div class="p-4 sm:p-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">S.No</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class &amp; Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No.</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Academic Year</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Issued On</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($reportCards as $index => $card)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ $reportCards->firstItem() + $index }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                                <span class="text-blue-700 font-semibold text-xs">{{ strtoupper(substr($card->studentDetail->full_name ?? 'N', 0, 1)) }}</span>
                                            </div>
                                            <span class="text-sm font-semibold text-gray-900">{{ $card->studentDetail->full_name ?? 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $card->studentDetail->standard->name ?? '' }} - {{ $card->studentDetail->section->name ?? '' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $card->studentDetail->admission_no ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $card->academic_year ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        @if ($card->status === 'issued')
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Issued</span>
                                        @else
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-red-100 text-red-700">Revoked</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $card->issued_at ? $card->issued_at->format('d M Y') : 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if ($card->status === 'issued')
                                                <a href="{{ route('admin.report-card.download', ['organization' => auth()->user()->organization_id, 'id' => $card->id]) }}"
                                                    target="_blank" title="Download PDF"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                    </svg>
                                                </a>
                                                <a href="{{ route('admin.report-card.print', ['organization' => auth()->user()->organization_id, 'id' => $card->id]) }}"
                                                    target="_blank" title="Print"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                    </svg>
                                                </a>
                                                <button wire:click="revokeReportCard({{ $card->id }})"
                                                    wire:confirm="Are you sure you want to revoke this report card?" title="Revoke"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                    </svg>
                                                </button>
                                            @else
                                                <span class="text-xs text-gray-400 italic">Revoked</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-16 text-center">
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
        </div>

    @else
        {{-- ══════════════════════════════════════════════════
             ISSUE REPORT CARD SCREEN
        ══════════════════════════════════════════════════ --}}
        <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
            <div class="px-4 sm:px-6 py-3">
                <div class="flex items-center gap-3">
                    <button wire:click="backToList"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                    </button>
                    <div>
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900">Issue Report Cards</h1>
                    </div>
                </div>
            </div>

            {{-- Class / Section selector bar --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                        <span class="w-5 h-5 bg-blue-600 text-white rounded-full flex items-center justify-center text-[11px] font-bold">1</span>
                        Select Class &amp; Section:
                    </div>

                    <select wire:model.live="issueStandard" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">-- Select Class --</option>
                        @foreach ($this->standards as $standard)
                            <option value="{{ $standard->id }}">{{ $standard->name }}</option>
                        @endforeach
                    </select>

                    <span class="text-gray-300">→</span>

                    <select wire:model.live="issueSection" @disabled(!$issueStandard)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">-- Select Section --</option>
                        @foreach ($this->issueSections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>

                    <button wire:click="loadStudents" @disabled(!$issueStandard || !$issueSection)
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold rounded-md shadow-sm transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Load Students
                    </button>
                </div>
            </div>
        </div>

        {{-- BODY: student list --}}
        <div class="p-4 sm:p-6">
            @if ($issueStudentsLoaded)
                @php
                    $eligibleCount = $this->issueStudents->filter(fn($s) => $s['marks_complete'] && !$s['already_issued'])->count();
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    {{-- Sub-header --}}
                    <div class="px-5 py-4 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                            <h3 class="text-base font-semibold text-gray-900">Select Students</h3>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            {{-- Legend --}}
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
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">S.No</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Admission No.</th>
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
                                    <tr class="{{ $rowClass }} hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">
                                            @if ($isEligible)
                                                <input type="checkbox" wire:model.live="selectedStudents" value="{{ $student['id'] }}"
                                                    class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            @else
                                                <input type="checkbox" disabled class="w-4 h-4 rounded border-gray-200 text-gray-300 cursor-not-allowed">
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0 {{ $student['marks_complete'] ? 'bg-blue-100' : 'bg-gray-200' }}">
                                                    <span class="{{ $student['marks_complete'] ? 'text-blue-700' : 'text-gray-500' }} font-semibold text-xs">{{ strtoupper(substr($student['full_name'], 0, 1)) }}</span>
                                                </div>
                                                <span class="text-sm font-semibold text-gray-900">{{ $student['full_name'] }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $student['admission_no'] ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $student['roll_no'] }}</td>
                                        <td class="px-4 py-3">
                                            @if ($student['marks_complete'])
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Complete</span>
                                            @else
                                                <div>
                                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-amber-100 text-amber-700">Incomplete</span>
                                                    @if ($student['missing_info'])
                                                        <p class="text-[11px] text-gray-400 mt-1">{{ $student['missing_info'] }}</p>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($student['already_issued'])
                                                <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-blue-100 text-blue-700">Already Issued</span>
                                            @elseif ($student['marks_complete'])
                                                <span class="text-xs text-gray-500">Ready to issue</span>
                                            @else
                                                <span class="text-xs text-gray-400">--</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-16 text-center">
                                            <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4z" />
                                                </svg>
                                            </div>
                                            <p class="text-sm font-semibold text-gray-800">No students found</p>
                                            <p class="text-xs text-gray-400 mt-1">No students are enrolled in this class and section.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Issue button footer --}}
                    @if ($this->issueStudents->isNotEmpty())
                        <div class="px-5 py-4 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                            <div class="text-sm text-gray-600"><strong class="text-gray-900">{{ count($selectedStudents) }}</strong> student(s) selected</div>
                            <button wire:click="issueReportCards" @disabled(empty($selectedStudents)) wire:loading.attr="disabled"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                                <svg wire:loading.remove wire:target="issueReportCards" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <svg wire:loading wire:target="issueReportCards" class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Issue Report Cards
                            </button>
                        </div>
                    @endif
                </div>
            @else
                {{-- Prompt before loading --}}
                <div class="bg-white rounded-xl border border-gray-200 px-6 py-16 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l7-3 7 3z" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-gray-800">Select a class and section</p>
                    <p class="text-xs text-gray-400 mt-1">Choose a class and section above, then click "Load Students".</p>
                </div>
            @endif
        </div>
    @endif
</div>
