<div class="min-h-screen bg-gray-50">
    <x-notifications />
    <x-dialog />

    {{-- ══════════════ HEADER (white, sticky) ══════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Certificates &amp; TC</h1>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total Issued: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                        <span class="px-4">This Month: <strong class="text-emerald-600">{{ $this->analytics['this_month'] }}</strong></span>
                        <span class="px-4">Last Month: <strong class="text-blue-600">{{ $this->analytics['last_month'] }}</strong></span>
                        <span class="pl-4">This Week: <strong class="text-amber-500">{{ $this->analytics['this_week'] }}</strong></span>
                    </div>
                    @if ($activeTab === 'tc')
                        <button wire:click="createTc" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Issue TC
                        </button>
                    @else
                        <button wire:click="createCert" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span class="hidden sm:inline">Issue Certificate</span><span class="sm:hidden">Issue</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- mobile analytics --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                <span>This Month: <strong class="text-emerald-600">{{ $this->analytics['this_month'] }}</strong></span>
                <span>Last Month: <strong class="text-blue-600">{{ $this->analytics['last_month'] }}</strong></span>
                <span>This Week: <strong class="text-amber-500">{{ $this->analytics['this_week'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs (rules-regulation style, no numbering) --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1 overflow-x-auto">
                <button wire:click="$set('activeTab','achievement')"
                    class="py-3.5 px-5 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'achievement' ? 'border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Achievement
                </button>
                <button wire:click="$set('activeTab','participation')"
                    class="py-3.5 px-5 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'participation' ? 'border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Participation
                </button>
                <button wire:click="$set('activeTab','tc')"
                    class="py-3.5 px-5 text-sm font-semibold border-b-2 whitespace-nowrap transition-colors {{ $activeTab === 'tc' ? 'border-blue-500 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Transfer Certificate
                </button>
            </nav>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <input wire:model.live.debounce.300ms="search" type="text"
                    placeholder="{{ $activeTab === 'tc' ? 'Name / admission no / TC no…' : 'Name / admission no / certificate no…' }}"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                {{-- The month box reads "Select month" until a month is picked: an
                     empty month box only shows a row of dashes, so those are hidden
                     and the words laid over it. --}}
                <div class="relative">
                    <input wire:model.live="filterMonth" type="month" title="Month"
                        class="peer text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 {{ $filterMonth ? 'text-gray-700' : 'text-transparent focus:text-gray-700' }}" />
                    @unless ($filterMonth)
                        <span class="pointer-events-none absolute inset-y-0 left-2.5 flex items-center text-xs text-gray-500 peer-focus:hidden">Select month</span>
                    @endunless
                </div>
                <select wire:model.live="filterClass" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach ($this->standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                <select data-sole-section wire:model.live="filterSection" @disabled($this->filterSections->isEmpty())
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach ($this->filterSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
                @if ($search || $filterMonth || $filterClass || $filterSection)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════ BODY ══════════════ --}}
    <div class="p-4 sm:p-6">

        {{-- Achievement / Participation listing, in the Students list's style: a
             number, the student's photo (a click shows it large) with the name
             over the admission number, the event with its type small under it,
             who issued it with the date small under that, and the Students
             list's plain action buttons. --}}
        @if (in_array($activeTab, ['achievement', 'participation']))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">S.No</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">Event / Activity</th>
                                <th class="px-4 py-3 text-left">Issued By</th>
                                <th class="px-4 py-3 text-center w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($certificates as $cert)
                                <tr wire:key="cert-{{ $cert->id }}" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $certificates->firstItem() + $loop->index }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($cert->student?->user?->image)
                                                <img src="{{ $cert->student->user->image }}" wire:click="showStudentPhoto({{ $cert->student->id }})" title="View photo"
                                                    class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-zoom-in hover:opacity-90">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($cert->student->full_name ?? 'S', 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $cert->student->full_name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $cert->student->admission_no ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-800 max-w-[360px] truncate" title="{{ $cert->event_name }}">{{ $cert->event_name }}</p>
                                        <p class="text-xs text-gray-400">{{ ucfirst($cert->type) }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="text-gray-700">{{ $cert->issued_by }}</p>
                                        <p class="text-xs text-gray-400 whitespace-nowrap">{{ $cert->issued_date->format('d M Y') }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="previewCert({{ $cert->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <a href="{{ route('admin.cert.download', ['organization' => auth()->user()->organization_id, 'id' => $cert->id]) }}" download
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Download PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </a>
                                            <button wire:click="editCert({{ $cert->id }})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="deleteCert({{ $cert->id }})" class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                        No {{ $activeTab }} certificates yet.
                                        <button wire:click="createCert" class="text-blue-600 hover:underline ml-1">Issue your first →</button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($certificates->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $certificates->links() }}</div>
                @endif
            </div>
        @endif

        {{-- TC listing: the student and the action buttons as the certificates' above --}}
        @if ($activeTab === 'tc')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">S.No</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">TC No</th>
                                <th class="px-4 py-3 text-left">Class</th>
                                <th class="px-4 py-3 text-left">Book No</th>
                                <th class="px-4 py-3 text-left">Last Class</th>
                                <th class="px-4 py-3 text-left">Conduct</th>
                                <th class="px-4 py-3 text-left">Issue Date</th>
                                <th class="px-4 py-3 text-center w-40">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($tcList as $tc)
                                <tr wire:key="tc-{{ $tc->id }}" class="hover:bg-gray-50/70 transition-colors">
                                    <td class="px-4 py-3"><span class="text-sm text-gray-500 font-medium">{{ $tcList->firstItem() + $loop->index }}</span></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            @if ($tc->student?->user?->image)
                                                <img src="{{ $tc->student->user->image }}" wire:click="showStudentPhoto({{ $tc->student->id }})" title="View photo"
                                                    class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0 cursor-zoom-in hover:opacity-90">
                                            @else
                                                <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-semibold text-indigo-600">{{ strtoupper(substr($tc->student->full_name ?? 'S', 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate">{{ $tc->student->full_name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">{{ $tc->student->admission_no ?? '' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-rose-600 font-semibold whitespace-nowrap">{{ $tc->tc_no }}</td>
                                    {{-- The class and section the student was last in here --}}
                                    <td class="px-4 py-3">
                                        <p class="text-gray-700">{{ $tc->student?->standard?->name ?? '—' }}</p>
                                        @if ($tc->student?->section?->name)
                                            <p class="text-xs text-gray-400">{{ $tc->student->section->name }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->book_no ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->last_class_studied ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->general_conduct }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $tc->issue_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button wire:click="previewTc({{ $tc->id }})" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <a href="{{ route('admin.tc.download', ['organization' => auth()->user()->organization_id, 'id' => $tc->id]) }}" download
                                                class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Download PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </a>
                                            <button wire:click="editTc({{ $tc->id }})" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            {{-- No Delete: an issued transfer certificate stays on record. --}}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-12 text-center text-gray-400">
                                        No Transfer Certificates issued yet.
                                        <button wire:click="createTc" class="text-blue-600 hover:underline ml-1">Issue your first TC →</button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($tcList->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $tcList->links() }}</div>
                @endif
            </div>
        @endif
    </div>

    {{-- ══════════════ ISSUE CERTIFICATE SLIDE-IN PANEL (student-style) ══════════════ --}}
    @if ($certModal)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCertModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>

            {{-- Fixed header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editCertId ? 'Edit Certificate' : 'Issue Certificate' }}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pick the class, then the student, then fill the certificate details — every field is needed</p>
                </div>
                <button wire:click="closeCertModal" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                {{-- Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-md border cursor-pointer {{ $type === 'achievement' ? 'border-blue-500 bg-blue-50/60' : 'border-gray-300 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="type" value="achievement" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800">Achievement</span>
                        </label>
                        <label class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-md border cursor-pointer {{ $type === 'participation' ? 'border-blue-500 bg-blue-50/60' : 'border-gray-300 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="type" value="participation" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-800">Participation</span>
                        </label>
                    </div>
                </div>

                {{-- Student picker --}}
                @include('livewire.partials.certificate-student-picker', [
                    'classProp'    => 'certClass',
                    'studentProp'  => 'student_detail_id',
                    'sectionProp'  => 'certSection',
                    'searchProp'   => 'certStudentSearch',
                    'classValue'   => $certClass,
                    'sectionsList' => $this->certSections,
                    'students'     => $this->certIssueStudents,
                    'standards'    => $this->standards,
                    'selected'     => $this->selectedCertStudent,
                    'selectMethod' => 'selectCertStudent',
                    'clearMethod'  => 'clearCertStudent',
                    'errorKey'     => 'student_detail_id',
                    'locked'       => (bool) $editCertId,
                ])

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Event / Activity Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="event_name" maxlength="255"
                            placeholder="{{ $type === 'achievement' ? 'e.g. Annual Science Olympiad 2025' : 'e.g. Annual Sports Day 2025' }}"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('event_name') border-red-400 @enderror">
                        @error('event_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-red-500">*</span></label>
                        <textarea wire:model.defer="description" rows="3" maxlength="1000"
                            placeholder="{{ $type === 'achievement' ? 'For securing First Position in…' : 'For actively participating in…' }}"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-400 @enderror"></textarea>
                        @error('description')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Issued By <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="issued_by" maxlength="255" placeholder="e.g. Rajesh Kumar"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('issued_by') border-red-400 @enderror">
                        @error('issued_by')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="issued_by_designation" maxlength="100" placeholder="e.g. Principal"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('issued_by_designation') border-red-400 @enderror">
                        @error('issued_by_designation')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Issued Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.defer="issued_date"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('issued_date') border-red-400 @enderror">
                        @error('issued_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" wire:click="closeCertModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="button" wire:click="saveCert" wire:loading.attr="disabled" wire:target="saveCert"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveCert">{{ $editCertId ? 'Update Certificate' : 'Issue Certificate' }}</span>
                    <span wire:loading wire:target="saveCert">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════ ISSUE TC SLIDE-IN PANEL (student-style) ══════════════ --}}
    @if ($tcModal)
    <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTcModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>

            {{-- Fixed header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">{{ $editTcId ? 'Edit' : 'Issue' }} Transfer Certificate</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Pick the class, then the student, then fill all details as per records — every field is needed</p>
                </div>
                <button wire:click="closeTcModal" type="button"
                    class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            {{-- Scrollable body --}}
            <div class="flex-1 overflow-y-auto px-6 py-6 space-y-6">

                {{-- Student picker --}}
                @include('livewire.partials.certificate-student-picker', [
                    'classProp'    => 'tcClass',
                    'studentProp'  => 'tc_student_id',
                    'sectionProp'  => 'tcSection',
                    'searchProp'   => 'tcStudentSearch',
                    'classValue'   => $tcClass,
                    'sectionsList' => $this->tcSections,
                    'students'     => $this->tcIssueStudents,
                    'standards'    => $this->standards,
                    'selected'     => $this->selectedTcStudent,
                    'selectMethod' => 'selectTcStudent',
                    'clearMethod'  => 'clearTcStudent',
                    'errorKey'     => 'tc_student_id',
                    'locked'       => (bool) $editTcId,
                ])

                {{-- What the certificate prints from the student's own record, where
                     the record has it blank: filled here, it is saved to the record. --}}
                @if (count($this->tcMissingFields))
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Missing in the Student's Record</p>
                        <p class="text-xs text-gray-400 mb-3">The certificate prints these from the student's record, where they are blank. Fill them here — they are needed, and are saved to the record too.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($this->tcMissingFields as $fillKey => $fillLabel)
                                <div wire:key="tc-fill-{{ $fillKey }}">
                                    <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $fillLabel }} <span class="text-red-500">*</span></label>
                                    <input type="{{ in_array($fillKey, ['dob', 'date_of_admission'], true) ? 'date' : 'text' }}" wire:model.defer="tcStudentFill.{{ $fillKey }}"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('tcStudentFill.' . $fillKey) border-red-400 @enderror">
                                    @error('tcStudentFill.' . $fillKey)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Student & Academic --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Student &amp; Academic</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nationality <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="nationality"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('nationality')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Book No. <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="book_no" placeholder="e.g. 096"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('book_no')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="flex items-center gap-2.5 px-3.5 py-2.5 border border-gray-300 rounded-md cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" wire:model.defer="is_sc_st" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="text-sm text-gray-700">Belongs to Scheduled Caste / Scheduled Tribe</span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Last School Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="previous_school_name" maxlength="255" placeholder="e.g. ABC Public School, Aligarh"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('previous_school_name') border-red-400 @enderror">
                            @error('previous_school_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class in Last School <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="previous_school_class" maxlength="50" placeholder="e.g. 8th"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('previous_school_class') border-red-400 @enderror">
                            @error('previous_school_class')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class Last Studied <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="last_class_studied" placeholder="e.g. 12th"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('last_class_studied')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam Last Taken with Result <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="exam_last_taken" placeholder="e.g. 12th Passed"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('exam_last_taken')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Whether Failed <span class="text-red-500">*</span></label>
                            <select wire:model.defer="whether_failed"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($failedOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                            @error('whether_failed')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Qualified for Promotion <span class="text-red-500">*</span></label>
                            <select wire:model.defer="qualified_for_promotion"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="Yes">Yes</option><option value="No">No</option>
                            </select>
                            @error('qualified_for_promotion')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subjects Studied <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="subjects_studied" placeholder="e.g. Hindi, English, Mathematics, Science"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('subjects_studied')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Attendance & Fees --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Attendance &amp; Fees</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Total Working Days <span class="text-red-500">*</span></label>
                            <input type="number" wire:model.defer="total_working_days" min="0"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('total_working_days')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Days Present <span class="text-red-500">*</span></label>
                            <input type="number" wire:model.defer="days_present" min="0"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('days_present')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fees Paid Upto <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="fees_paid_upto" placeholder="e.g. March 2026"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fees_paid_upto')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Fee Concession (if any) <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="fee_concession" placeholder="e.g. None"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('fee_concession')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Activities & Conduct --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Activities &amp; Conduct</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">NCC / Scout / Guide <span class="text-red-500">*</span></label>
                            <select wire:model.defer="is_ncc_scout"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                @foreach ($nccOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                            @error('is_ncc_scout')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">General Conduct <span class="text-red-500">*</span></label>
                            <select wire:model.defer="general_conduct"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('general_conduct') border-red-400 @enderror">
                                @foreach ($conductOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                            @error('general_conduct')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Games / Extra-Curricular Activities <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="extra_activities" placeholder="e.g. Cricket, Debate"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('extra_activities')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                {{-- Issue Details --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Issue Details</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Application <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="application_date"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('application_date') border-red-400 @enderror">
                            @error('application_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Date of Issue <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="tc_issue_date"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('tc_issue_date') border-red-400 @enderror">
                            @error('tc_issue_date')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Reason for Leaving <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="reason_for_leaving" placeholder="e.g. No Further Classes"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('reason_for_leaving')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Any Other Remark <span class="text-red-500">*</span></label>
                            <textarea wire:model.defer="tc_remarks" rows="2" placeholder="e.g. No"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                            @error('tc_remarks')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                <button type="button" wire:click="closeTcModal"
                    class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                <button type="button" wire:click="saveTc" wire:loading.attr="disabled" wire:target="saveTc"
                    class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60">
                    <span wire:loading.remove wire:target="saveTc">{{ $editTcId ? 'Update TC' : 'Issue TC' }}</span>
                    <span wire:loading wire:target="saveTc">Saving...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════ VIEW SLIDE-IN PANEL (student view style) ══════════════ --}}
    @if ($previewModal && ($previewCert || $previewTc))
        @php
            $pv     = $previewCert ?: $previewTc;
            $isTc   = (bool) $previewTc;
            $pvStu  = $pv->student;
            $pvPdf = $isTc
                ? route('admin.tc.download', ['organization' => auth()->user()->organization_id, 'id' => $pv->id])
                : route('admin.cert.download', ['organization' => auth()->user()->organization_id, 'id' => $pv->id]);
            // Streamed inline: the panel shows the printed certificate itself, not the fields behind it.
            $pvView = $isTc
                ? route('admin.tc.view', ['organization' => auth()->user()->organization_id, 'id' => $pv->id])
                : route('admin.cert.view', ['organization' => auth()->user()->organization_id, 'id' => $pv->id]);
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePreview"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col" wire:click.stop>

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $pvStu->full_name ?? 'Certificate' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ $isTc ? 'Transfer Certificate · ' . $pv->tc_no : ucfirst($pv->type) . ' Certificate · ' . $pv->certificate_no }}
                        </p>
                    </div>
                    <button wire:click="closePreview" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-hidden bg-gray-100">
                    <iframe src="{{ $pvView }}#toolbar=0&amp;navpanes=0&amp;view=FitH"
                        class="w-full h-full border-0" title="{{ $isTc ? 'Transfer Certificate' : 'Certificate' }}"></iframe>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    <a href="{{ $pvPdf }}" download
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        Download PDF
                    </a>
                    <button type="button" wire:click="closePreview"
                        class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete, asked the way Logout asks: a small card in the middle of the
         window over the dimmed page (lms-cover). --}}
    @if ($pendingDeleteCertId || $pendingDeleteTcId)
        @php $delTc = (bool) $pendingDeleteTcId; @endphp
        <div class="lms-cover fixed inset-0 flex items-center justify-center bg-black/30 backdrop-blur-sm z-[9999] px-4">
            <div class="bg-white rounded-2xl shadow-xl p-6 max-w-sm w-full">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ $delTc ? 'Delete Transfer Certificate' : 'Delete Certificate' }}</h3>
                        <p class="text-xs text-gray-400">Certificates &amp; TC</p>
                    </div>
                </div>
                <p class="text-sm text-gray-500 mb-5">Are you sure you want to delete this {{ $delTc ? 'transfer certificate' : 'certificate' }}? It cannot be brought back.</p>
                <div class="flex items-center gap-2">
                    <button wire:click="{{ $delTc ? 'confirmDeleteTc(' . (int) $pendingDeleteTcId . ')' : 'confirmDeleteCert(' . (int) $pendingDeleteCertId . ')' }}"
                        class="flex-1 py-2 bg-red-500 hover:bg-red-600 text-white text-sm
                               font-medium rounded-lg transition-colors">
                        Yes, Delete
                    </button>
                    <button wire:click="cancelDelete"
                        class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm
                               font-medium rounded-lg transition-colors">
                        Cancel
                    </button>
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
