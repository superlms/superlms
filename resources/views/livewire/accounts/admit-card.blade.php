<div class="min-h-screen bg-gray-50">

    {{-- Flash message --}}
    @if(session('success'))
    <div x-data="{show:true}" x-show="show" x-init="setTimeout(() => show = false, 4000)"
        class="fixed top-4 right-4 z-[10001] flex items-center gap-2 bg-emerald-600 text-white text-sm font-medium px-4 py-2.5 rounded-lg shadow-lg">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- ══════════════════════════ BULK GENERATE FULL SCREEN ══════════════════════════ --}}
    @if($showBulkScreen)
    <div class="fixed inset-0 z-[9998] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeBulkScreen"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Bulk Generate Admit Cards</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Auto-generate by attendance, fee, or for all students</p>
                </div>
                <button wire:click="closeBulkScreen"
                    class="w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-200 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                {{-- Step 1 --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 1 — Select Exam &amp; Class</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model.live="bulkExam" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>
                            @endforeach
                        </select>
                        @error('bulkExam') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="bulkStandard" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            @foreach($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('bulkStandard') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Section <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model.live="bulkSection" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            {{ $bulkSections->isEmpty() ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @foreach($bulkSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Step 2 --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 2 — Eligibility Criteria</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $bulkGenerateType === 'attendance' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="bulkGenerateType" value="attendance" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div>
                            <span class="text-sm font-semibold text-gray-800">By Attendance</span>
                            <p class="text-xs text-gray-400">Attendance % meets threshold</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $bulkGenerateType === 'fee' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="bulkGenerateType" value="fee" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div>
                            <span class="text-sm font-semibold text-gray-800">By Fee</span>
                            <p class="text-xs text-gray-400">Paid fee % meets threshold</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer rounded-lg border p-3 transition {{ $bulkGenerateType === 'none' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                        <input type="radio" wire:model.live="bulkGenerateType" value="none" class="mt-0.5 text-blue-600 focus:ring-blue-500 border-gray-300">
                        <div>
                            <span class="text-sm font-semibold text-gray-800">None</span>
                            <p class="text-xs text-gray-400">All students, no criteria</p>
                        </div>
                    </label>
                </div>
                @if($bulkGenerateType !== 'none')
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-56">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                            {{ $bulkGenerateType === 'attendance' ? 'Minimum Attendance %' : 'Minimum Fee Paid %' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" wire:model="bulkPercentage" min="1" max="100" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 pr-8">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                        </div>
                        @error('bulkPercentage') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="inline-flex items-center gap-2 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-3 py-2 rounded-lg">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Students meeting the {{ $bulkPercentage }}% threshold get admit cards automatically
                    </div>
                </div>
                @else
                <div class="inline-flex items-center gap-2 bg-blue-50 border border-blue-200 text-blue-700 text-xs px-3 py-2 rounded-lg">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    Admit cards will be generated for all students in the selected class/section.
                </div>
                @endif
            </div>

            {{-- Step 3 --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 3 — Exam Schedule</h3>
                    <button type="button" wire:click="addBulkSubject" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg> Add Subject
                    </button>
                </div>
                <div class="space-y-3">
                    @foreach($bulkSubjects as $i => $subj)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold text-gray-500">Subject {{ $i + 1 }}</span>
                            @if(count($bulkSubjects) > 1)
                            <button type="button" wire:click="removeBulkSubject({{ $i }})" class="text-red-400 hover:text-red-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Subject *</label>
                                <select wire:model="bulkSubjects.{{ $i }}.subject_id" wire:change="syncBulkSubjectName({{ $i }})" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">Choose Subject</option>
                                    @foreach($this->allSubjects as $sub)
                                        <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->code ? ' ('.$sub->code.')' : '' }}</option>
                                    @endforeach
                                </select>
                                @error("bulkSubjects.{$i}.subject_id") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Date *</label>
                                <input type="date" wire:model="bulkSubjects.{{ $i }}.exam_date" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Time *</label>
                                <input type="time" wire:model="bulkSubjects.{{ $i }}.exam_time" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Duration *</label>
                                <input type="text" wire:model="bulkSubjects.{{ $i }}.exam_duration" placeholder="3 Hrs" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @error('bulkSubjects') <p class="text-sm text-red-500">{{ $message }}</p> @enderror
            </div>

            {{-- Step 4 --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Step 4 — Additional Info</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reporting Time</label>
                        <input type="time" wire:model="bulkReportingTime" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea wire:model="bulkInstructions" rows="3" placeholder="Enter exam instructions…" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                </div>
            </div>

            </div>

            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button wire:click="closeBulkScreen" class="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="bulkGenerateAdmitCards" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-lg font-semibold shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                    Generate Admit Cards
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════ HEADER (exam-style) ══════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Admit Card</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Issue and manage examination admit cards</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                        <span class="px-4">Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                        <span class="pl-4">Remaining: <strong class="text-amber-500">{{ $this->analytics['remaining'] }}</strong></span>
                    </div>
                    <a href="{{ $this->getPrintAllUrl() }}" target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                        <span class="hidden sm:inline">Print All</span>
                    </a>
                    <button wire:click="openBulkScreen" class="inline-flex items-center gap-1.5 px-3 py-2 border border-blue-200 bg-blue-50 text-blue-700 text-sm font-semibold rounded-lg hover:bg-blue-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                        Bulk Generate
                    </button>
                    <button wire:click="openIssueModal" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Issue Admit Card</span>
                        <span class="sm:hidden">Issue</span>
                    </button>
                </div>
            </div>

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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Name or card no…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-52 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <select wire:model.live="examFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Exams</option>
                    @foreach($exams as $exam)
                        <option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="standardFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach($standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="sectionFilter" @disabled($filterSections->isEmpty())
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach($filterSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="statusFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="used">Used</option>
                </select>
                <select wire:model.live="perPage" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="15">15 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                </select>
                @if($search || $examFilter || $standardFilter || $sectionFilter || $statusFilter)
                    <button wire:click="resetFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg> Clear
                    </button>
                @endif
                <span class="ml-auto text-xs text-gray-500">Total: <strong class="text-gray-700">{{ $admitCards->total() }}</strong> card(s)</span>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════ BODY: TABLE ══════════════════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class / Section</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Exam</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Card No. / Roll No.</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($admitCards as $i => $card)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ $admitCards->firstItem() + $i }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if($card->studentDetail?->image)
                                        <img src="{{ Storage::url($card->studentDetail->image) }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-blue-700 font-bold text-sm">{{ substr($card->student_name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-sm text-gray-900">{{ $card->student_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $card->studentDetail?->admission_no ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                {{ $card->studentDetail?->standard?->name ?? '—' }}@if($card->studentDetail?->section?->name) / {{ $card->studentDetail->section->name }} @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-700 font-medium">{{ $card->exam_name }}</p>
                                <p class="text-xs text-gray-400">{{ $card->academic_year }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-mono text-xs text-gray-800 font-medium">{{ $card->admit_card_number }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Roll: {{ $card->roll_number }}</p>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($card->status === 'active')
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-emerald-100 text-emerald-700">Active</span>
                                @elseif($card->status === 'used')
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-blue-100 text-blue-700">Used</span>
                                @else
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide bg-gray-100 text-gray-600">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('accounts.admit-card.view', [$org->serial_number ?? $org->id, $card->id]) }}" target="_blank"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                    </a>
                                    <a href="{{ route('accounts.admit-card.download', [$org->serial_number ?? $org->id, $card->id]) }}"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200" title="Download PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    </a>
                                    <button wire:click="openEditModal({{ $card->id }})"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button wire:click="deleteCard({{ $card->id }})"
                                        class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-16 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" /></svg>
                                </div>
                                <p class="text-sm font-semibold text-gray-800">No admit cards found</p>
                                @if($search || $examFilter || $standardFilter || $sectionFilter || $statusFilter)
                                    <button wire:click="resetFilters" class="mt-2 text-xs text-blue-600 hover:underline">Clear filters</button>
                                @else
                                    <button wire:click="openIssueModal" class="mt-2 text-xs text-blue-600 hover:underline">Issue first admit card</button>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($admitCards->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $admitCards->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════ ISSUE ADMIT CARD MODAL ══════════════════════════ --}}
    @if($showIssueModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeIssueModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">Issue Admit Cards</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Select exam &amp; class, set the schedule, then pick students</p>
                </div>
                <button wire:click="closeIssueModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model.live="issueExam" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Exam</option>
                            @foreach($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>
                            @endforeach
                        </select>
                        @error('issueExam') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.live="issueStandard" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Select Class</option>
                            @foreach($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                        @error('issueStandard') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Section <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model.live="issueSection" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"
                            {{ $issueSections->isEmpty() ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @foreach($issueSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($issueStandard)
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-blue-50 border-b border-blue-100">
                        <p class="text-xs font-bold text-blue-800">Exam Schedule (all subjects)</p>
                        <button type="button" wire:click="addIssueSubject" class="flex items-center gap-1 text-xs text-blue-600 hover:underline font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg> Add Row
                        </button>
                    </div>
                    <div class="divide-y divide-gray-100 max-h-64 overflow-y-auto">
                        @foreach($issueSubjects as $i => $subj)
                        <div class="px-4 py-3 bg-white">
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 items-end">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Subject *</label>
                                    <select wire:model="issueSubjects.{{ $i }}.subject_id" wire:change="syncIssueSubjectName({{ $i }})"
                                        class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Choose</option>
                                        @foreach($this->allSubjects as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->code ? ' ('.$sub->code.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error("issueSubjects.{$i}.subject_id") <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Date *</label>
                                    <input type="date" wire:model="issueSubjects.{{ $i }}.exam_date" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Time *</label>
                                    <input type="time" wire:model="issueSubjects.{{ $i }}.exam_time" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Duration *</label>
                                    <input type="text" wire:model="issueSubjects.{{ $i }}.exam_duration" placeholder="3 Hrs" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="flex items-end justify-end">
                                    @if(count($issueSubjects) > 1)
                                    <button type="button" wire:click="removeIssueSubject({{ $i }})" class="p-1.5 text-red-400 hover:text-red-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @error('issueSubjects') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reporting Time</label>
                        <input type="time" wire:model="issueReportingTime" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Instructions <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea wire:model="issueInstructions" rows="3" placeholder="Enter exam instructions…" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 resize-none"></textarea>
                </div>

                {{-- Student List (pick students to generate) --}}
                @if($issueExam && $issueStandard)
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-700">Select Students ({{ $this->issueAvailableStudents->count() }} available)</p>
                        <div class="flex gap-3">
                            <button type="button" wire:click="selectAllIssueStudents" class="text-xs text-blue-600 hover:underline font-semibold">Select All</button>
                            <button type="button" wire:click="deselectAllIssueStudents" class="text-xs text-gray-400 hover:underline">Deselect All</button>
                        </div>
                    </div>
                    @if($this->issueAvailableStudents->isEmpty())
                        <div class="px-4 py-6 text-center text-sm text-gray-400">All students already have admit cards for this exam.</div>
                    @else
                        <div class="max-h-56 overflow-y-auto divide-y divide-gray-100">
                            @foreach($this->issueAvailableStudents as $student)
                            <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/40 cursor-pointer">
                                <input type="checkbox" wire:click="toggleIssueStudent({{ $student->id }})" @checked(in_array($student->id, $issueStudents))
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <div class="flex items-center gap-2 flex-1">
                                    @if($student->image)
                                        <img src="{{ Storage::url($student->image) }}" class="w-7 h-7 rounded-full object-cover border">
                                    @else
                                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs">{{ substr($student->full_name, 0, 1) }}</div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">{{ $student->full_name }}</p>
                                        <p class="text-xs text-gray-400">{{ $student->admission_no }} · {{ $student->standard?->name }}@if($student->section) / {{ $student->section->name }} @endif</p>
                                    </div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>
                @error('issueStudents') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                @endif
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex justify-between items-center bg-gray-50 flex-shrink-0">
                <span class="text-xs text-gray-500">{{ count($issueStudents) }} student(s) selected</span>
                <div class="flex gap-2">
                    <button type="button" wire:click="closeIssueModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="button" wire:click="issueAdmitCards" wire:loading.attr="disabled" class="inline-flex items-center gap-2 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        Issue Cards
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════ EDIT MODAL ══════════════════════════ --}}
    @if($showEditModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeEditModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <h3 class="text-base font-bold text-gray-800">Edit Admit Card</h3>
                <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Card Number *</label>
                        <input type="text" wire:model="editAdmitCardNumber" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                        @error('editAdmitCardNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Roll Number *</label>
                        <input type="text" wire:model="editRollNumber" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('editRollNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam Roll No.</label>
                        <input type="text" wire:model="editExamRollNumber" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Reporting Time</label>
                        <input type="time" wire:model="editReportingTime" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Room No. (R)</label>
                        <input type="text" wire:model="editRoomNumber" placeholder="e.g. 23" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Seat No. (S)</label>
                        <input type="text" wire:model="editSeatNumber" placeholder="e.g. 18" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam Center</label>
                        <input type="text" wire:model="editExamCenter" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Status *</label>
                        <select wire:model="editStatus" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="used">Used</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Exam Center Address</label>
                        <input type="text" wire:model="editExamCenterAddress" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-2.5 bg-blue-50 border-b border-blue-100">
                        <p class="text-xs font-bold text-blue-800">Exam Schedule</p>
                        <button type="button" wire:click="addEditSubject" class="flex items-center gap-1 text-xs text-blue-700 hover:underline font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg> Add Row
                        </button>
                    </div>
                    <div class="divide-y divide-gray-100 max-h-52 overflow-y-auto">
                        @foreach($editSubjects as $i => $subj)
                        <div class="px-4 py-3 bg-white">
                            <div class="grid grid-cols-2 md:grid-cols-6 gap-2 items-end">
                                <div class="col-span-2">
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Subject *</label>
                                    <select wire:model="editSubjects.{{ $i }}.subject_id" wire:change="syncEditSubjectName({{ $i }})"
                                        class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Choose</option>
                                        @foreach($this->allSubjects as $sub)
                                            <option value="{{ $sub->id }}">{{ $sub->name }}{{ $sub->code ? ' ('.$sub->code.')' : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Date *</label>
                                    <input type="date" wire:model="editSubjects.{{ $i }}.exam_date" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Time *</label>
                                    <input type="time" wire:model="editSubjects.{{ $i }}.exam_time" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 mb-1">Duration *</label>
                                    <input type="text" wire:model="editSubjects.{{ $i }}.exam_duration" placeholder="3 Hrs" class="w-full rounded-lg border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                </div>
                                <div class="flex items-end gap-1 justify-end">
                                    <select wire:model="editSubjects.{{ $i }}.status" class="rounded border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                                        <option value="eligible">Eligible</option>
                                        <option value="not_eligible">Not Eligible</option>
                                    </select>
                                    @if(count($editSubjects) > 1)
                                    <button type="button" wire:click="removeEditSubject({{ $i }})" class="p-1 text-red-400 hover:text-red-600 rounded">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7H5m14 0l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7" /></svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Instructions</label>
                    <textarea wire:model="editInstructions" rows="3" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 resize-none"></textarea>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-2 bg-gray-50 flex-shrink-0">
                <button wire:click="closeEditModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveEditCard" class="px-4 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-sm transition">Save Changes</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════════════════ DELETE CONFIRM ══════════════════════════ --}}
    @if($pendingDeleteId)
    <div class="fixed inset-0 z-[10000] flex items-center justify-center px-4" style="background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" wire:click.stop>
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
            </div>
            <h3 class="text-base font-bold text-gray-800 mb-1">Delete Admit Card?</h3>
            <p class="text-sm text-gray-500 mb-5">This action cannot be undone.</p>
            <div class="flex justify-center gap-3">
                <button wire:click="cancelDelete" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="doDelete" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold shadow transition">Delete</button>
            </div>
        </div>
    </div>
    @endif

</div>
