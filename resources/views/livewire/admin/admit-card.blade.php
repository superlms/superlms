<div class="min-h-screen bg-gray-50"
    x-data
    x-on:open-in-new-tab.window="window.open($event.detail.url, '_blank')">
    <x-notifications />
    <x-dialog />

    {{-- ══════════════════════════ HEADER ══════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">Admit Card</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Issue admit cards by class — schedule &amp; seating come from the seating plan</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                        <span class="px-4">Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                        <span class="pl-4">Remaining: <strong class="text-amber-500">{{ $this->analytics['remaining'] }}</strong></span>
                    </div>
                    <button wire:click="openGenerateModal"
                        class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Generate Admit Card</span>
                        <span class="sm:hidden">Generate</span>
                    </button>
                </div>
            </div>

            {{-- Mobile stats --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                <span>Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                <span>Remaining: <strong class="text-amber-500">{{ $this->analytics['remaining'] }}</strong></span>
            </div>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <select wire:model.live="examFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Exam</option>
                    @foreach($this->exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="standardFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach($this->standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="sectionFilter" @disabled($this->filterSections->isEmpty())
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach($this->filterSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Students</option>
                    <option value="issued">Issued</option>
                    <option value="not_issued">Not Issued</option>
                </select>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Name, roll, admission…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="perPage" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="15">15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>

                <button wire:click="openPrintModal"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-gray-300 bg-white text-gray-700 text-xs font-semibold rounded-md hover:bg-gray-50 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                    Print All
                </button>

                @if($examFilter || $standardFilter || $sectionFilter || $statusFilter || $search)
                    <button wire:click="resetFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════ BODY ══════════════════════════ --}}
    <div class="p-4 sm:p-6">
        @if(!$ready)
            {{-- Prompt to choose exam + class --}}
            <div class="bg-white rounded-xl border border-gray-200 p-12 text-center">
                <div class="w-14 h-14 mx-auto mb-4 bg-blue-50 rounded-full flex items-center justify-center">
                    <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>
                </div>
                <p class="text-base font-semibold text-gray-800">Select an exam and a class</p>
                <p class="text-sm text-gray-400 mt-1">Pick an <strong>Exam</strong> and a <strong>Class</strong> in the filter above to list students and their admit-card status.</p>
            </div>
        @else
            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-4 mb-3 text-xs text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-400"></span> Issued</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-300"></span> Not issued</span>
                <span class="ml-auto">Showing <strong class="text-gray-700">{{ $students->total() }}</strong> student(s) on this page-set</span>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Roll</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Card No.</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($students as $student)
                                @php $card = $issued[$student->id] ?? null; @endphp
                                <tr wire:key="stu-{{ $student->id }}"
                                    class="transition-colors {{ $card ? 'bg-emerald-50/40 hover:bg-emerald-50' : 'bg-amber-50/40 hover:bg-amber-50' }}">
                                    <td class="px-4 py-3 text-sm text-gray-600 tabular-nums">{{ $student->roll_no ?: '—' }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if($student->image)
                                                <img src="{{ Storage::url($student->image) }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-blue-700 font-bold text-sm">{{ substr($student->full_name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <div>
                                                <p class="font-semibold text-sm text-gray-900">{{ $student->full_name }}</p>
                                                <p class="text-xs text-gray-400">{{ $student->admission_no ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        {{ $student->standard?->name ?? '—' }}@if($student->section?->name) / {{ $student->section->name }} @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($card)
                                            <p class="font-mono text-xs text-gray-800 font-medium">{{ $card->admit_card_number }}</p>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        @if($card)
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Issued</span>
                                        @else
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-amber-100 text-amber-700">Not Issued</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            @if($card)
                                                <a href="{{ route('admin.admit-card.view', ['organization' => auth()->user()->organization_id, 'id' => $card->id]) }}"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200" title="View / Print">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                </a>
                                                <a href="{{ route('admin.admit-card.download', ['organization' => auth()->user()->organization_id, 'id' => $card->id]) }}"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200" title="Download PDF">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                                </a>
                                                <button wire:click="confirmDelete({{ $card->id }})"
                                                    class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200" title="Delete">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            @else
                                                <button wire:click="issueOne({{ $student->id }})" wire:loading.attr="disabled" wire:target="issueOne({{ $student->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-md shadow-sm transition disabled:opacity-60">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                                    Issue
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-16 text-center">
                                        <p class="text-sm font-semibold text-gray-800">No students found</p>
                                        <p class="text-xs text-gray-400 mt-1">Try adjusting the filters.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($students->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $students->links() }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- ══════════════════════════ GENERATE MODAL ══════════════════════════ --}}
    @if($showGenerateModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeGenerateModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Generate Admit Cards</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Select exam &amp; class, choose the eligibility criteria</p>
                </div>
                <button wire:click="closeGenerateModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">
                {{-- Exam / class / section --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model.live="genExam" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Exam</option>
                            @foreach($this->exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>
                            @endforeach
                        </select>
                        @error('genExam') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="genStandard" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            @foreach($this->standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('genStandard') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Section <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model.live="genSection" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            {{ $this->genSections->isEmpty() ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @foreach($this->genSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Criteria --}}
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Eligibility Criteria</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $genCriteria === 'none' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="genCriteria" value="none" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <div><span class="text-sm font-semibold text-gray-800">All Students</span><p class="text-xs text-gray-400">No criteria</p></div>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $genCriteria === 'attendance' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="genCriteria" value="attendance" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <div><span class="text-sm font-semibold text-gray-800">By Attendance</span><p class="text-xs text-gray-400">Attendance % ≥ threshold</p></div>
                        </label>
                        <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $genCriteria === 'fee' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="genCriteria" value="fee" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                            <div><span class="text-sm font-semibold text-gray-800">By Fee</span><p class="text-xs text-gray-400">Paid fee % ≥ threshold</p></div>
                        </label>
                    </div>

                    @if($genCriteria !== 'none')
                    <div class="mt-3 w-56">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ $genCriteria === 'attendance' ? 'Minimum Attendance %' : 'Minimum Fee Paid %' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" wire:model="genPercentage" min="1" max="100"
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 pr-8">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                        </div>
                        @error('genPercentage') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif
                </div>

                <div class="flex items-start gap-2 bg-blue-50 border border-blue-200 text-blue-700 text-xs px-3 py-2.5 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span>Subjects, dates &amp; times are pulled from the exam <strong>datesheet</strong>, and seat/room from the <strong>seating plan</strong>. Students who already have a card for this exam are skipped.</span>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 flex-shrink-0">
                <button wire:click="closeGenerateModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="generateAdmitCards" wire:loading.attr="disabled" wire:target="generateAdmitCards"
                    class="inline-flex items-center gap-2 px-5 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-sm transition disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    <span wire:loading.remove wire:target="generateAdmitCards">Generate</span>
                    <span wire:loading wire:target="generateAdmitCards">Generating…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════ PRINT-SELECTION MODAL ══════════════════════════ --}}
    @if($showPrintModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePrintModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Print Admit Cards</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Choose issued students to print together</p>
                </div>
                <button wire:click="closePrintModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-5">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-bold text-gray-600">{{ $this->printableCards->count() }} issued card(s)</p>
                    <div class="flex gap-3">
                        <button type="button" wire:click="selectAllPrint" class="text-xs text-blue-600 hover:underline font-semibold">Select All</button>
                        <button type="button" wire:click="deselectAllPrint" class="text-xs text-gray-400 hover:underline">Deselect All</button>
                    </div>
                </div>

                @if($this->printableCards->isEmpty())
                    <div class="px-4 py-10 text-center text-sm text-gray-400 border border-dashed border-gray-200 rounded-lg">
                        No admit cards issued yet for this exam &amp; class.
                    </div>
                @else
                    <div class="border border-gray-200 rounded-xl divide-y divide-gray-100 max-h-[60vh] overflow-y-auto">
                        @foreach($this->printableCards as $card)
                        <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/40 cursor-pointer">
                            <input type="checkbox" value="{{ $card->id }}" wire:model="printSelected" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $card->student_name }}</p>
                                <p class="text-xs text-gray-400">Roll: {{ $card->roll_number }} · {{ $card->admit_card_number }}</p>
                            </div>
                        </label>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
                <span class="text-xs text-gray-500">{{ count(array_filter($printSelected)) }} selected</span>
                <div class="flex gap-2">
                    <button wire:click="closePrintModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="printSelectedCards"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-gray-900 text-white rounded-lg hover:bg-gray-800 font-semibold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        Print Selected
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════ DELETE CONFIRM ══════════════════════════ --}}
    @if($showDeleteModal)
    <div class="fixed inset-0 z-[10000] flex items-center justify-center px-4" style="background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" wire:click.stop>
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Delete Admit Card?</h3>
            <p class="text-sm text-gray-500 mb-5">The student will move back to the not-issued list.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="deleteAdmitCard" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold shadow transition">Delete</button>
            </div>
        </div>
    </div>
    @endif

</div>
