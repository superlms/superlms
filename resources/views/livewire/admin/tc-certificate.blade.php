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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Student / certificate no…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-52 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                <input wire:model.live="filterMonth" type="month" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700" />
                <select wire:model.live="filterClass" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Classes</option>
                    @foreach ($this->standards as $std)
                        <option value="{{ $std->id }}">{{ $std->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterSection" @disabled($this->filterSections->isEmpty())
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">All Sections</option>
                    @foreach ($this->filterSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                    @endforeach
                </select>
                <select wire:model.live="perPage" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="10">10 / page</option>
                    <option value="25">25 / page</option>
                    <option value="50">50 / page</option>
                </select>
                @if ($search || $filterMonth || $filterClass || $filterSection)
                    <button wire:click="$set('search','');$set('filterMonth','');$set('filterClass','');$set('filterSection','')"
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

        {{-- Achievement / Participation listing (student-style table) --}}
        @if (in_array($activeTab, ['achievement', 'participation']))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left w-10">#</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-left">Event / Activity</th>
                                <th class="px-4 py-3 text-left">Certificate No</th>
                                <th class="px-4 py-3 text-left">Issued By</th>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-center w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($certificates as $cert)
                                <tr wire:key="cert-{{ $cert->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $certificates->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center font-bold text-xs text-white {{ $cert->type === 'achievement' ? 'bg-amber-500' : 'bg-blue-500' }}">
                                                {{ strtoupper(substr($cert->student->full_name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate">{{ $cert->student->full_name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">Adm: {{ $cert->student->admission_no ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-semibold rounded-full {{ $cert->type === 'achievement' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">{{ ucfirst($cert->type) }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-800 max-w-[220px] truncate">{{ $cert->event_name }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $cert->certificate_no }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $cert->issued_by }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $cert->issued_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('admin.cert.download', ['organization' => auth()->user()->organization_id, 'id' => $cert->id]) }}" target="_blank"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-green-50 hover:text-green-600 hover:border-green-200" title="Download PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </a>
                                            <button wire:click="editCert({{ $cert->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="deleteCert({{ $cert->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
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

        {{-- TC listing (student-style table) --}}
        @if ($activeTab === 'tc')
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left w-10">#</th>
                                <th class="px-4 py-3 text-left">Student</th>
                                <th class="px-4 py-3 text-left">TC No</th>
                                <th class="px-4 py-3 text-left">Book No</th>
                                <th class="px-4 py-3 text-left">Last Class</th>
                                <th class="px-4 py-3 text-left">Conduct</th>
                                <th class="px-4 py-3 text-left">Issue Date</th>
                                <th class="px-4 py-3 text-center w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($tcList as $tc)
                                <tr wire:key="tc-{{ $tc->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-xs text-gray-400">{{ $tcList->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-9 h-9 rounded-full bg-rose-500 flex items-center justify-center text-white font-bold text-xs flex-shrink-0">
                                                {{ strtoupper(substr($tc->student->full_name ?? 'S', 0, 1)) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-gray-900 truncate">{{ $tc->student->full_name ?? '—' }}</p>
                                                <p class="text-xs text-gray-400 truncate">Adm: {{ $tc->student->admission_no ?? '—' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-rose-600 font-semibold whitespace-nowrap">{{ $tc->tc_no }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->book_no ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->last_class_studied ?: '—' }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $tc->general_conduct }}</td>
                                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">{{ $tc->issue_date->format('d M Y') }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="{{ route('admin.tc.download', ['organization' => auth()->user()->organization_id, 'id' => $tc->id]) }}" target="_blank"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-green-50 hover:text-green-600 hover:border-green-200" title="Download PDF">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                            </a>
                                            <button wire:click="editTc({{ $tc->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200" title="Edit">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>
                                            <button wire:click="deleteTc({{ $tc->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-12 text-center text-gray-400">
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

    {{-- ══════════════ ISSUE CERTIFICATE SLIDE-IN PANEL ══════════════ --}}
    @if ($certModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeCertModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">{{ $editCertId ? 'Edit Certificate' : 'Issue Certificate' }}</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Choose a student, then fill the certificate details</p>
                </div>
                <button wire:click="closeCertModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                {{-- Type --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Type <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition {{ $type === 'achievement' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="type" value="achievement" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-semibold text-gray-800">Achievement</span>
                        </label>
                        <label class="flex items-center gap-2 px-4 py-2.5 rounded-lg border cursor-pointer transition {{ $type === 'participation' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="type" value="participation" class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-semibold text-gray-800">Participation</span>
                        </label>
                    </div>
                </div>

                {{-- Student picker by class/section --}}
                @unless ($editCertId)
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <div class="px-4 py-2.5 bg-gray-50 border-b border-gray-100">
                        <p class="text-xs font-bold text-gray-700">Select Student <span class="text-red-500">*</span></p>
                    </div>
                    <div class="p-4 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <select wire:model.live="certClass" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Class</option>
                                @foreach ($this->standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="certSection" @disabled($this->certSections->isEmpty()) class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                                <option value="">All Sections</option>
                                @foreach ($this->certSections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($certClass)
                            <div class="relative">
                                <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <input wire:model.live.debounce.300ms="certStudentSearch" type="text" placeholder="Search student by name or admission no…" class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 border border-gray-100 rounded-lg">
                                @forelse ($this->certIssueStudents as $stu)
                                    <label class="flex items-center gap-3 px-3 py-2 cursor-pointer {{ $student_detail_id == $stu->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                        <input type="radio" wire:model.live="student_detail_id" value="{{ $stu->id }}" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-800">{{ $stu->full_name }}</span>
                                        <span class="text-xs text-gray-400 ml-auto">{{ $stu->admission_no }}</span>
                                    </label>
                                @empty
                                    <p class="px-3 py-4 text-center text-sm text-gray-400">No students found.</p>
                                @endforelse
                            </div>
                        @else
                            <p class="text-xs text-gray-400">Choose a class to load students.</p>
                        @endif
                        @error('student_detail_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
                @endunless

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Event / Activity Name <span class="text-red-500">*</span></label>
                    <input type="text" wire:model.defer="event_name" placeholder="{{ $type === 'achievement' ? 'e.g. Annual Science Olympiad 2025' : 'e.g. Annual Sports Day 2025' }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('event_name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Description</label>
                    <textarea wire:model.defer="description" rows="3" placeholder="{{ $type === 'achievement' ? 'For securing First Position in...' : 'For actively participating in...' }}" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 resize-none"></textarea>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Issued By <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="issued_by" placeholder="e.g. Rajesh Kumar" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('issued_by') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Designation</label>
                        <input type="text" wire:model.defer="issued_by_designation" placeholder="e.g. Principal" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Issued Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model.defer="issued_date" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @error('issued_date') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2 flex-shrink-0">
                <button wire:click="closeCertModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveCert" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-sm transition">{{ $editCertId ? 'Update' : 'Issue' }}</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ══════════════ ISSUE TC SLIDE-IN PANEL ══════════════ --}}
    @if ($tcModal)
    <div class="fixed inset-0 z-[9999] overflow-hidden">
        <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTcModal"></div>
        <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                <div>
                    <h3 class="text-base font-bold text-gray-800">{{ $editTcId ? 'Edit' : 'Issue' }} Transfer Certificate</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Choose a student, then fill all details as per records</p>
                </div>
                <button wire:click="closeTcModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
                {{-- Student picker --}}
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-1 border-b">Select Student</p>
                    @unless ($editTcId)
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <select wire:model.live="tcClass" class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Class</option>
                                @foreach ($this->standards as $std)
                                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                                @endforeach
                            </select>
                            <select wire:model.live="tcSection" @disabled($this->tcSections->isEmpty()) class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50">
                                <option value="">All Sections</option>
                                @foreach ($this->tcSections as $sec)
                                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if ($tcClass)
                            <div class="relative">
                                <svg class="absolute left-2.5 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                                <input wire:model.live.debounce.300ms="tcStudentSearch" type="text" placeholder="Search student by name or admission no…" class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                            <div class="max-h-48 overflow-y-auto divide-y divide-gray-100 border border-gray-100 rounded-lg">
                                @forelse ($this->tcIssueStudents as $stu)
                                    <label class="flex items-center gap-3 px-3 py-2 cursor-pointer {{ $tc_student_id == $stu->id ? 'bg-blue-50' : 'hover:bg-gray-50' }}">
                                        <input type="radio" wire:model.live="tc_student_id" value="{{ $stu->id }}" class="text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-800">{{ $stu->full_name }}</span>
                                        <span class="text-xs text-gray-400 ml-auto">{{ $stu->admission_no }}</span>
                                    </label>
                                @empty
                                    <p class="px-3 py-4 text-center text-sm text-gray-400">No students found.</p>
                                @endforelse
                            </div>
                        @else
                            <p class="text-xs text-gray-400">Choose a class to load students.</p>
                        @endif
                        @error('tc_student_id') <p class="text-xs text-red-500">{{ $message }}</p> @enderror
                    </div>
                    @else
                        <p class="text-sm text-gray-600">Editing TC for the selected student.</p>
                    @endunless
                </div>

                {{-- Other details --}}
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-1 border-b">Student & Academic</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nationality</label>
                            <input type="text" wire:model.defer="nationality" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Book No.</label>
                            <input type="text" wire:model.defer="book_no" placeholder="e.g. 096" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox" wire:model.defer="is_sc_st" id="tc_scst" class="h-4 w-4 text-blue-500 rounded border-gray-300">
                            <label for="tc_scst" class="text-sm text-gray-700">Belongs to Scheduled Caste / Scheduled Tribe</label>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Class Last Studied</label>
                            <input type="text" wire:model.defer="last_class_studied" placeholder="e.g. 12th" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Exam Last Taken with Result</label>
                            <input type="text" wire:model.defer="exam_last_taken" placeholder="e.g. 12th Passed" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Whether Failed</label>
                            <select wire:model.defer="whether_failed" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                                @foreach ($failedOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Qualified for Promotion</label>
                            <select wire:model.defer="qualified_for_promotion" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                                <option value="Yes">Yes</option><option value="No">No</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Subjects Studied</label>
                            <input type="text" wire:model.defer="subjects_studied" placeholder="e.g. Hindi, English, Mathematics, Science" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-1 border-b">Attendance & Fees</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Total Working Days</label>
                            <input type="number" wire:model.defer="total_working_days" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Days Present</label>
                            <input type="number" wire:model.defer="days_present" min="0" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fees Paid Upto</label>
                            <input type="text" wire:model.defer="fees_paid_upto" placeholder="e.g. March 2026" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Fee Concession (if any)</label>
                            <input type="text" wire:model.defer="fee_concession" placeholder="e.g. None" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-1 border-b">Activities & Conduct</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">NCC / Scout / Guide</label>
                            <select wire:model.defer="is_ncc_scout" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                                @foreach ($nccOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">General Conduct</label>
                            <select wire:model.defer="general_conduct" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                                @foreach ($conductOptions as $opt)<option value="{{ $opt }}">{{ $opt }}</option>@endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Games / Extra-Curricular Activities</label>
                            <input type="text" wire:model.defer="extra_activities" placeholder="e.g. Cricket, Debate" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3 pb-1 border-b">Issue Details</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Application <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="application_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                            @error('application_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Issue <span class="text-red-500">*</span></label>
                            <input type="date" wire:model.defer="tc_issue_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                            @error('tc_issue_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Leaving</label>
                            <input type="text" wire:model.defer="reason_for_leaving" placeholder="e.g. No Further Classes" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Any Other Remark</label>
                            <textarea wire:model.defer="tc_remarks" rows="2" placeholder="e.g. No" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 text-sm resize-none"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2 flex-shrink-0">
                <button wire:click="closeTcModal" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                <button wire:click="saveTc" class="px-5 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold shadow-sm transition">{{ $editTcId ? 'Update TC' : 'Issue TC' }}</button>
            </div>
        </div>
    </div>
    @endif

</div>
