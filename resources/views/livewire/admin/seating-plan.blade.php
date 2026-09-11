<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (compact, fees/about-app style) + tabs
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900">Exam Seating Management</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1">
                        <span class="pr-4">Rooms: <strong class="text-gray-800">{{ $rooms->count() }}</strong></span>
                        <span class="px-4">Plans: <strong class="text-blue-600">{{ $planCount }}</strong></span>
                        <span class="pl-4">Datesheets: <strong class="text-emerald-600">{{ $datesheets->count() }}</strong></span>
                    </div>
                    @if ($activeTab === 'plans')
                        <button wire:click="openGeneratePanel"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Generate Plan
                        </button>
                    @elseif ($activeTab === 'rooms')
                        <button wire:click="openRoomPanel"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Add Room
                        </button>
                    @elseif ($activeTab === 'datesheet')
                        <button wire:click="openDatesheetCreate"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            Create Datesheet
                        </button>
                    @endif
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1 overflow-x-auto">
                @php
                    $tabs = [
                        'plans'     => 'Seating Plans',
                        'rooms'     => 'Rooms',
                        'datesheet' => 'Datesheet',
                    ];
                @endphp
                @foreach ($tabs as $key => $label)
                    <button wire:click="switchTab('{{ $key }}')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                               {{ $activeTab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- ══════════ FILTER BAND — stuck to the header, full width (attendance style)
             Every control carries a wire:key naming its tab and mode. Without one
             Livewire's DOM morph reuses whatever control sat in the same slot of
             the previous tab, and the select binds to the wrong property. ══════════ --}}
        @if ($activeTab === 'plans' || $activeTab === 'datesheet')
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>

                @if ($activeTab === 'plans')
                    <div class="inline-flex rounded-md border border-gray-200 bg-white p-0.5">
                        @foreach (['room' => 'By Room', 'class' => 'By Class'] as $k => $label)
                            <button wire:key="sf-mode-{{ $k }}" wire:click="setGraphMode('{{ $k }}')"
                                class="px-3 py-1 text-xs font-semibold rounded transition-colors {{ $graphMode === $k ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50' }}">{{ $label }}</button>
                        @endforeach
                    </div>

                    <select wire:key="sf-exam" wire:model.live="filterExamId"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <option value="">Select exam…</option>
                        @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                    </select>

                    @if ($graphMode === 'room')
                        <select wire:key="sf-room" wire:model.live="filterRoomId" @disabled(!$filterExamId)
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Select room…</option>
                            @foreach ($graphRoomOptions as $gr)
                                <option value="{{ $gr->id }}">{{ $gr->room_name }}{{ $gr->building ? ' · ' . $gr->building : '' }}</option>
                            @endforeach
                        </select>
                    @else
                        <select wire:key="sf-class" wire:model.live="filterStandardId" @disabled(!$filterExamId)
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Select class…</option>
                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                        </select>

                        <select wire:key="sf-section" wire:model.live="filterSectionId" @disabled(!$filterStandardId)
                            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                            <option value="">Select section…</option>
                            @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                    @endif

                    @if ($graphFiltersActive)
                        <button wire:key="sf-clear" wire:click="clearGraphFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif

                @elseif ($activeTab === 'datesheet')
                    <select wire:key="ds-exam" wire:model.live="dsFilterExamId"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate">
                        <option value="">Select exam…</option>
                        @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                    </select>

                    <select wire:key="ds-class" wire:model.live="dsFilterStandardId" @disabled(!$dsFilterExamId)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select class…</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>

                    <select wire:key="ds-section" wire:model.live="dsFilterSectionId" @disabled(!$dsFilterStandardId)
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">Select section…</option>
                        @foreach ($dsFilterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>

                    <select wire:key="ds-subject" wire:model.live="dsFilterSubjectId" @disabled($dsFilterSubjects->isEmpty())
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 max-w-full sm:max-w-[13rem] truncate disabled:opacity-50 disabled:cursor-not-allowed">
                        <option value="">All subjects</option>
                        @foreach ($dsFilterSubjects as $sub)<option value="{{ $sub['id'] }}">{{ $sub['name'] }}</option>@endforeach
                    </select>

                    @if ($dsFilterExamId || $dsFilterStandardId || $dsFilterSectionId || $dsFilterSubjectId)
                        <button wire:key="ds-clear" wire:click="clearDatesheetFilters"
                            class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear
                        </button>
                    @endif
                @endif
            </div>
        </div>
        @endif
    </div>

    <div class="p-4 sm:p-6">

        {{-- ══════════════════════════════════════════════
             TAB: SEATING PLANS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'plans')

            {{-- ══════════════════════════════════════════════
                 SEAT FINDER
                 The band under the tabs drives this: by room it lists the papers
                 written in one room, by class every paper the class sits and
                 where. A row draws the room, downloads the list or prints it.
            ══════════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl border border-gray-200 mb-5 overflow-hidden">
                {{-- The papers themselves --}}
                @php
                    $finderReady = $filterExamId && ($graphMode === 'room'
                        ? $filterRoomId
                        : $filterStandardId && $filterSectionId);
                @endphp

                @if (!$finderReady)
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-gray-700">
                            {{ $graphMode === 'room' ? 'Choose an exam and a room' : 'Choose an exam, a class and a section' }}
                        </p>
                        <p class="text-xs text-gray-400 mt-1">The papers appear here once the last one is picked.</p>
                    </div>
                @elseif ($sessionRows->isEmpty())
                    <div class="px-5 py-10 text-center">
                        <p class="text-sm font-semibold text-gray-700">Nothing seated yet</p>
                        <p class="text-xs text-gray-400 mt-1">No generated plan for this exam puts anyone {{ $graphMode === 'room' ? 'in that room' : 'on a seat' }}.</p>
                    </div>
                @else
                    @php
                        // A room can hold several classes and subjects at once, so
                        // its own row names the room instead of a paper that isn't
                        // the whole story — the roster underneath carries each
                        // candidate's own class, subject, date and time.
                        $selectedRoom = $graphMode === 'room'
                            ? $graphRoomOptions->firstWhere('id', (int) $filterRoomId)
                            : null;
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[780px]">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    @if ($graphMode === 'room')
                                        <th class="px-4 py-3 text-left">Room</th>
                                        <th class="px-4 py-3 text-left w-40">Date</th>
                                        <th class="px-4 py-3 text-left w-40">Time</th>
                                        <th class="px-4 py-3 text-left w-24">Shift</th>
                                    @else
                                        <th class="px-4 py-3 text-left">Subject</th>
                                        <th class="px-4 py-3 text-left w-44">Date</th>
                                        <th class="px-4 py-3 text-left w-28">Shift</th>
                                        <th class="px-4 py-3 text-left">Room(s)</th>
                                    @endif
                                    <th class="px-4 py-3 text-center w-28">Candidates</th>
                                    <th class="px-4 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($sessionRows as $i => $row)
                                    @php
                                        // Both the download and the print sheet take the same
                                        // narrowing the finder is showing.
                                        $listArgs = array_filter([
                                            'organization' => auth()->user()->organization_id,
                                            'id'           => $row['plan_id'],
                                            'room'         => $graphMode === 'room' ? (int) $filterRoomId : null,
                                            'standard'     => (int) $filterStandardId,
                                            'section'      => (int) $filterSectionId,
                                            'subject'      => $row['subject'],
                                        ]);
                                    @endphp
                                    <tr wire:key="sess-{{ $row['plan_id'] }}" class="hover:bg-gray-50/70">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                        @if ($graphMode === 'room')
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $selectedRoom->room_name ?? '—' }}{{ $selectedRoom?->building ? ' · ' . $selectedRoom->building : '' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['date']?->format('d M Y, D') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['time'] }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['session'] ?: 'Shift 1' }}</td>
                                        @else
                                            <td class="px-4 py-3 font-medium text-gray-800">{{ $row['subject'] }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['date']?->format('d M Y, D') ?? '—' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ $row['session'] ?: 'Shift 1' }}</td>
                                            <td class="px-4 py-3 text-gray-600">{{ implode(', ', $row['rooms']) }}</td>
                                        @endif
                                        <td class="px-4 py-3 text-center text-gray-600">{{ $row['students'] }}</td>
                                        <td class="px-4 py-3">
                                            {{-- View, download and print are one sheet: the same page,
                                                 opened, saved or sent straight to the printer. --}}
                                            <div class="flex items-center justify-center gap-1.5">
                                                <a href="{{ route('admin.seating-plan.list', $listArgs) }}" target="_blank"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600">View</a>
                                                <a href="{{ route('admin.seating-plan.list-pdf', $listArgs) }}" target="_blank"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-emerald-50 hover:text-emerald-600">Download</a>
                                                <a href="{{ route('admin.seating-plan.list', $listArgs + ['print' => 1]) }}" target="_blank"
                                                    class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">Print</a>
                                                @if ($row['status'] !== 'published')
                                                    <button wire:click="publishPlan({{ $row['plan_id'] }})"
                                                        class="text-xs font-medium px-3 py-1.5 rounded-md border border-emerald-200 text-emerald-600 hover:bg-emerald-50">Publish</button>
                                                @endif
                                                <button wire:click="confirmDeletePlan({{ $row['plan_id'] }})" title="Delete this session's plan"
                                                    class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        @endif

        {{-- ══════════════════════════════════════════════
             TAB: ROOMS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'rooms')
            <p class="text-sm text-gray-500 mb-4">{{ $rooms->count() }} room(s) configured</p>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
                @forelse ($rooms as $room)
                    <div class="bg-white rounded-lg border border-gray-200 p-3 hover:border-blue-200 hover:shadow-sm transition-all">
                        <div class="flex items-start justify-between gap-1.5">
                            <div class="min-w-0">
                                <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $room->room_name }}</h3>
                                @if ($room->building)
                                    <p class="text-[11px] text-gray-400 truncate">{{ $room->building }}</p>
                                @endif
                            </div>
                            <span class="w-2 h-2 rounded-full flex-shrink-0 mt-1 {{ $room->is_active ? 'bg-emerald-500' : 'bg-gray-300' }}" title="{{ $room->is_active ? 'Active' : 'Inactive' }}"></span>
                        </div>
                        @php $perSeat = max(1, (int) ($room->seat_capacity ?? 1)); @endphp
                        <div class="flex items-center gap-1.5 mt-2 text-xs text-gray-500">
                            <span>{{ $room->rows }}×{{ $room->columns }} desks</span>
                            <span class="text-gray-300">•</span>
                            <span>{{ $perSeat }}/desk</span>
                            <span class="text-gray-300">•</span>
                            <span class="font-semibold text-gray-700">{{ $room->capacity }} seats</span>
                        </div>
                        <div class="flex items-center gap-1 mt-2.5 pt-2 border-t border-gray-100">
                            <button wire:click="viewRoom({{ $room->id }})"
                                class="flex-1 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600">View</button>
                            <button wire:click="openRoomPanel({{ $room->id }})"
                                class="flex-1 text-[11px] font-medium px-2 py-1 rounded-md border border-gray-200 text-gray-600 hover:bg-amber-50 hover:text-amber-600">Edit</button>
                            <button wire:click="confirmDeleteRoom({{ $room->id }})"
                                class="px-1.5 py-1 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <p class="text-base font-semibold text-gray-800">No rooms yet</p>
                        <p class="text-sm text-gray-400 mt-1">Add a room with rows &amp; columns — seats are auto-generated.</p>
                    </div>
                @endforelse
            </div>
        @endif

        {{-- ══════════════════════════════════════════════
             TAB: DATESHEET
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'datesheet')
            @if (!$dsFilterExamId || !$dsFilterStandardId || !$dsFilterSectionId)
                <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
                    <p class="text-base font-semibold text-gray-800">Pick an exam, a class and a section</p>
                    <p class="text-sm text-gray-400 mt-1">The datesheet appears once a section is chosen.</p>
                    @if ($datesheets->isEmpty())
                        <button wire:click="openDatesheetCreate" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Create a datesheet →</button>
                    @endif
                </div>
            @elseif (!$filteredDatesheet)
                <div class="text-center py-16 bg-white rounded-xl border border-gray-200">
                    <p class="text-base font-semibold text-gray-800">No datesheet for this section yet</p>
                    <p class="text-sm text-gray-400 mt-1">Neither the section nor the class has one for this exam.</p>
                    <button wire:click="openDatesheetCreate" class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium">Create a datesheet →</button>
                </div>
            @else
                @php
                    $dsSectionLabel = $filteredDatesheet->section->name
                        ?? optional($dsFilterSections->firstWhere('id', (int) $dsFilterSectionId))->name;
                @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                    {{-- Header carries the actions for the whole sheet --}}
                    <div class="flex flex-wrap items-center gap-3 px-4 py-3 border-b border-gray-200">
                        <div class="min-w-0 mr-auto">
                            <h3 class="text-sm font-semibold text-gray-900 truncate">
                                {{ $filteredDatesheet->exam->exam_name ?? 'Exam' }} ·
                                {{ $filteredDatesheet->standard->name ?? '' }}{{ $dsSectionLabel ? ' — ' . $dsSectionLabel : '' }}
                            </h3>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ $filteredPapers->count() }} paper(s)
                                @if (!$filteredDatesheet->section) · class-wide sheet @endif
                                @if ($dsFilterSubjectId) · filtered to one subject @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('admin.datesheet.print', array_filter([
                                    'organization' => auth()->user()->organization_id,
                                    'id'           => $filteredDatesheet->id,
                                    'subject'      => $dsFilterSubjectId ?: null,
                                    'section'      => $dsFilterSectionId ?: null,
                                    'print'        => 1,
                               ])) }}" target="_blank"
                                class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">Print</a>
                            <button wire:click="viewDatesheet({{ $filteredDatesheet->id }})"
                                class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600">View</button>
                            <button wire:click="openDatesheetEdit({{ $filteredDatesheet->id }})"
                                class="text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-amber-50 hover:text-amber-600">Edit</button>
                            <button wire:click="confirmDeleteDatesheet({{ $filteredDatesheet->id }})"
                                class="text-xs font-medium px-3 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">Delete</button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm min-w-[720px]">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-4 py-3 text-left w-12">#</th>
                                    <th class="px-4 py-3 text-left">Subject</th>
                                    <th class="px-4 py-3 text-left w-36">Date</th>
                                    <th class="px-4 py-3 text-left w-28">Day</th>
                                    <th class="px-4 py-3 text-left w-44">Time</th>
                                    <th class="px-4 py-3 text-left w-24">Shift</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($filteredPapers as $i => $paper)
                                    @php
                                        $pDate = $paper->exam_date;
                                        $pFrom = $paper->start_time ? \Carbon\Carbon::parse($paper->start_time)->format('g:i A') : null;
                                        $pTo   = $paper->end_time ? \Carbon\Carbon::parse($paper->end_time)->format('g:i A') : null;
                                    @endphp
                                    <tr wire:key="dsp-row-{{ $paper->id }}" class="hover:bg-gray-50/70">
                                        <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                        <td class="px-4 py-3 font-medium text-gray-800">{{ $paper->subject->name ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $pDate?->format('d M Y') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $pDate?->format('D') ?? '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">{{ $pFrom ? ($pTo ? $pFrom . ' – ' . $pTo : $pFrom) : '—' }}</td>
                                        <td class="px-4 py-3 text-gray-600">Shift {{ $paper->shift ?: 1 }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">No paper for this subject on the sheet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         PLAN VIEWER (room-wise charts)
    ══════════════════════════════════════════════════ --}}
    @if ($viewingPlan)
        <div class="fixed inset-x-0 bottom-0 top-16 z-40 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePlanView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewingPlan->name }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewingPlan->exam->exam_name ?? '' }} · {{ $viewingPlan->exam_date?->format('d M Y') }}{{ $viewingPlan->session ? ' · ' . $viewingPlan->session : '' }}
                            @if ($viewingPlan->conflict_count > 0)
                                · <span class="text-red-600 font-medium">{{ $viewingPlan->conflict_count }} conflicts</span>
                            @else
                                · <span class="text-emerald-600 font-medium">No conflicts</span>
                            @endif
                        </p>
                        @if ($viewingPlan->notes)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $viewingPlan->notes }}</p>
                        @endif
                    </div>
                    <button wire:click="closePlanView" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto bg-gray-50 p-6 space-y-6">
                    @foreach ($planRooms as $room)
                        @php
                            // Seat order, occupied desks only — the same roster the
                            // PDF and print sheet read, so what you see here is what
                            // you get on paper.
                            $roomAssignments = $planAssignments->where('room_id', $room->id)
                                ->whereNotNull('student_id')
                                ->sortBy(fn ($a) => sprintf(
                                    '%04d|%04d|%03d',
                                    (int) ($a->seat?->row_no ?? 0),
                                    (int) ($a->seat?->col_no ?? 0),
                                    (int) ($a->seat_position ?? 1),
                                ))->values();
                            $filled = $roomAssignments->count();
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">{{ $room->room_name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $room->building }} · {{ $filled }}/{{ $room->capacity }} seats filled</p>
                                </div>
                                <a href="{{ route('admin.seating-plan.room-pdf', ['organization' => auth()->user()->organization_id, 'id' => $viewingPlan->id, 'roomId' => $room->id]) }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-gray-200 rounded-md text-gray-700 hover:bg-gray-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Room PDF
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                        <tr>
                                            <th class="px-4 py-2 text-left w-12">#</th>
                                            <th class="px-4 py-2 text-left">Seat</th>
                                            <th class="px-4 py-2 text-left">Roll No.</th>
                                            <th class="px-4 py-2 text-left">Class</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @forelse ($roomAssignments as $i => $a)
                                            <tr class="{{ $a->has_conflict ? 'bg-red-50' : '' }}">
                                                <td class="px-4 py-2 text-gray-400">{{ $i + 1 }}</td>
                                                <td class="px-4 py-2 font-semibold text-gray-800">{{ \App\Support\SeatLabel::full($room->room_name, $a->seat?->row_no, $a->seat?->col_no, $a->seat_position) }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ $planRollMap[(int) $a->student_id]['roll'] ?? '—' }}</td>
                                                <td class="px-4 py-2 text-gray-700 font-medium">{{ $a->class_label }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No candidates seated in this room.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between gap-2 flex-shrink-0">
                    <a href="{{ route('admin.seating-plan.print', ['organization' => auth()->user()->organization_id, 'id' => $viewingPlan->id]) }}" target="_blank"
                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Print / PDF</a>
                    <button wire:click="closePlanView" type="button"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ROOM SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showRoomPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRoomPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editRoomId ? 'Edit Room' : 'Add Room' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Seats are auto-generated from rows × columns.</p>
                    </div>
                    <button wire:click="closeRoomPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Room Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="roomForm.room_name" placeholder="e.g. Hall A"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('roomForm.room_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Building <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" wire:model="roomForm.building" placeholder="e.g. Main Block"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Rows <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="50" wire:model.live="roomForm.rows"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('roomForm.rows')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Columns <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="50" wire:model.live="roomForm.columns"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('roomForm.columns')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Seats per desk <span class="text-red-500">*</span></label>
                        <input type="number" min="1" max="10" wire:model.live="roomForm.seat_capacity"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-400">How many candidates sit at one desk — a two-seater bench is 2.</p>
                        @error('roomForm.seat_capacity')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    @php
                        $fRows    = (int) ($roomForm['rows'] ?? 0);
                        $fCols    = (int) ($roomForm['columns'] ?? 0);
                        $fPerSeat = max(1, (int) ($roomForm['seat_capacity'] ?? 1));
                    @endphp
                    <p class="text-xs text-gray-500">
                        Capacity:
                        <strong class="text-gray-700 font-medium">{{ $fRows * $fCols * $fPerSeat }} seats</strong>
                        <span class="text-gray-400">({{ $fRows }} × {{ $fCols }} desks × {{ $fPerSeat }} per desk)</span>
                    </p>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model="roomForm.is_active" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        Active (available for seating plans)
                    </label>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
                        <textarea wire:model="roomForm.notes" rows="2" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeRoomPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveRoom" type="button" wire:loading.attr="disabled" wire:target="saveRoom"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveRoom">{{ $editRoomId ? 'Update' : 'Add Room' }}</span>
                        <span wire:loading wire:target="saveRoom">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ROOM SEAT MAP — every desk drawn, one icon per seat at it
    ══════════════════════════════════════════════════ --}}
    @if ($viewingRoom)
        @php
            $vpRows    = (int) $viewingRoom->rows;
            $vpCols    = (int) $viewingRoom->columns;
            $vpPerSeat = max(1, (int) ($viewingRoom->seat_capacity ?? 1));
            $vpSeats   = $viewingRoom->seats->groupBy('row_no');
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeRoomView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewingRoom->room_name }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $viewingRoom->building ? $viewingRoom->building . ' · ' : '' }}{{ $vpRows }} × {{ $vpCols }} desks ·
                            {{ $vpPerSeat }} per desk ·
                            <span class="font-medium text-gray-700">{{ $viewingRoom->capacity }} seats</span>
                            · {{ $viewingRoom->is_active ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                    <button wire:click="closeRoomView" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="mb-4 text-center">
                        <span class="inline-block px-10 py-1.5 rounded-md bg-gray-900 text-white text-[11px] tracking-widest uppercase">Board / Front</span>
                    </div>

                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full space-y-2">
                            @for ($r = 1; $r <= $vpRows; $r++)
                                <div class="flex items-stretch gap-2">
                                    <div class="w-6 flex-shrink-0 flex items-center justify-center text-[10px] font-semibold text-gray-400">
                                        {{ chr(64 + $r) }}
                                    </div>
                                    <div class="flex-1 grid gap-2" style="grid-template-columns: repeat({{ $vpCols }}, minmax(0, 1fr));">
                                        @for ($c = 1; $c <= $vpCols; $c++)
                                            @php
                                                $seat = ($vpSeats[$r] ?? collect())->firstWhere('col_no', $c);
                                            @endphp
                                            <div class="rounded-lg border border-gray-200 bg-gray-50 px-1.5 py-2 text-center">
                                                <div class="flex items-center justify-center gap-0.5 flex-wrap">
                                                    @for ($k = 0; $k < $vpPerSeat; $k++)
                                                        {{-- one icon per candidate the desk seats --}}
                                                        <svg class="w-4 h-4 text-indigo-500" fill="currentColor" viewBox="0 0 24 24">
                                                            <path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v1a1 1 0 001 1h14a1 1 0 001-1v-1c0-2.76-3.58-5-8-5z" />
                                                        </svg>
                                                    @endfor
                                                </div>
                                                <div class="mt-1 text-[9px] font-medium text-gray-500">
                                                    {{ \App\Support\SeatLabel::seat($r, $c) }}
                                                </div>
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-4 text-[11px] text-gray-500">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a5 5 0 100-10 5 5 0 000 10zm0 2c-4.42 0-8 2.24-8 5v1a1 1 0 001 1h14a1 1 0 001-1v-1c0-2.76-3.58-5-8-5z" /></svg>
                            one candidate
                        </span>
                        <span>{{ $vpRows * $vpCols }} desks · {{ $viewingRoom->capacity }} seats in total</span>
                    </div>

                    @if ($viewingRoom->notes)
                        <p class="mt-4 text-xs text-gray-500">{{ $viewingRoom->notes }}</p>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="confirmDeleteRoom({{ $viewingRoom->id }})"
                        class="px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-md">Delete</button>
                    <button wire:click="openRoomPanel({{ $viewingRoom->id }})"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Edit room</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DATESHEET CREATE SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDatesheetPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDatesheetPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editDatesheetId ? 'Edit Datesheet' : 'Create Datesheet' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick an exam and class, then set the date, time &amp; shift per subject. One class cannot sit two papers at once.</p>
                    </div>
                    <button wire:click="closeDatesheetPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam <span class="text-red-500">*</span></label>
                            <select wire:model="dsExamId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select exam…</option>
                                @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                            </select>
                            @error('dsExamId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="dsStandardId" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select class…</option>
                                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                            </select>
                            @error('dsStandardId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select wire:model.live="dsSectionId" @disabled(!$dsStandardId) class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50">
                                <option value="">All sections</option>
                                @foreach ($dsSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    @if (!$dsStandardId)
                        <p class="py-10 text-center text-sm text-gray-400">Select a class to load its subjects.</p>
                    @elseif (empty($dsPapers))
                        <p class="py-10 text-center text-sm text-gray-400">No subjects mapped to this class/section.</p>
                    @else
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Subject</th>
                                        <th class="px-3 py-2 text-left w-36">Date</th>
                                        <th class="px-3 py-2 text-left w-28">Start</th>
                                        <th class="px-3 py-2 text-left w-28">End</th>
                                        <th class="px-3 py-2 text-left w-28">Shift</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($dsPapers as $subjectId => $p)
                                        <tr wire:key="dsp-{{ $subjectId }}">
                                            <td class="px-3 py-2 font-medium text-gray-800">{{ $p['name'] }}</td>
                                            <td class="px-3 py-2"><input type="date" wire:model="dsPapers.{{ $subjectId }}.exam_date" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></td>
                                            <td class="px-3 py-2"><input type="time" wire:model="dsPapers.{{ $subjectId }}.start_time" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></td>
                                            <td class="px-3 py-2"><input type="time" wire:model="dsPapers.{{ $subjectId }}.end_time" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-xs focus:ring-1 focus:ring-blue-500 focus:border-blue-500"></td>
                                            <td class="px-3 py-2">
                                                <select wire:model="dsPapers.{{ $subjectId }}.shift" class="w-full px-2.5 py-1.5 border border-gray-300 rounded-md text-xs bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                                    <option value="1">Shift 1</option>
                                                    <option value="2">Shift 2</option>
                                                </select>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="text-xs text-gray-400">Leave a subject's date blank to skip it.</p>
                        @error('dsPapers')
                            <p class="text-xs text-red-600 bg-red-50 border border-red-100 rounded-md px-3 py-2">{{ $message }}</p>
                        @enderror
                    @endif
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDatesheetPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDatesheet" type="button" wire:loading.attr="disabled" wire:target="saveDatesheet"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveDatesheet">{{ $editDatesheetId ? 'Update Datesheet' : 'Save Datesheet' }}</span>
                        <span wire:loading wire:target="saveDatesheet">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DATESHEET VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($viewingDatesheet)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDatesheetView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $viewingDatesheet->exam->exam_name ?? 'Datesheet' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $viewingDatesheet->standard->name ?? '' }}{{ $viewingDatesheet->section ? ' · ' . $viewingDatesheet->section->name : ' · All sections' }}</p>
                    </div>
                    <button wire:click="closeDatesheetView"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6">
                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Subject</th>
                                    <th class="px-3 py-2 text-left">Date</th>
                                    <th class="px-3 py-2 text-left">Time</th>
                                    <th class="px-3 py-2 text-center">Shift</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($viewingDatesheet->papers as $p)
                                    <tr>
                                        <td class="px-3 py-2 font-medium text-gray-800">{{ $p->subject->name ?? '—' }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ $p->exam_date?->format('d M Y') ?: '—' }}</td>
                                        <td class="px-3 py-2 text-gray-600">
                                            {{ $p->start_time ? \Carbon\Carbon::parse($p->start_time)->format('h:i A') : '—' }}
                                            @if ($p->end_time) – {{ \Carbon\Carbon::parse($p->end_time)->format('h:i A') }}@endif
                                        </td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="text-xs text-gray-600">Shift {{ $p->shift }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No papers.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDatesheetView" type="button"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         GENERATE PLAN SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showGeneratePanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeGeneratePanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Generate Seating Plan</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Reads the exam datesheet — one plan per exam date/shift is created automatically. Invigilators auto-assigned by date.</p>
                    </div>
                    <button wire:click="closeGeneratePanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Exam <span class="text-red-500">*</span></label>
                        <select wire:model.live="generateForm.exam_id" class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select exam…</option>
                            @foreach ($exams as $exam)
                                <option value="{{ $exam->id }}">{{ $exam->exam_name }} ({{ $exam->academic_year }})</option>
                            @endforeach
                        </select>
                        @error('generateForm.exam_id')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        @if ($generateForm['exam_id'])
                            <p class="text-xs mt-1.5 {{ count($datesheetStdIds) ? 'text-emerald-600' : 'text-amber-600' }}">
                                @if (count($datesheetStdIds))
                                    Datesheet found for {{ count($datesheetStdIds) }} class(es) — selected below by default.
                                @else
                                    No datesheet for this exam yet. Create one in the Datesheet tab first.
                                @endif
                            </p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Plan Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model="generateForm.name" placeholder="e.g. Final Exam 2026"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">The exam date is appended per plan, e.g. “{{ $generateForm['name'] ?: 'Final Exam' }} — 01 Jun 2026”.</p>
                        @error('generateForm.name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Classes <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" wire:click="selectAllClasses" class="text-blue-600 hover:text-blue-800 font-medium">Datesheet classes</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" wire:click="clearAllClasses" class="text-gray-500 hover:text-gray-700 font-medium">Clear</button>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-md p-3 max-h-40 overflow-y-auto space-y-1.5">
                            @forelse ($standards as $std)
                                @php $hasDs = in_array($std->id, $datesheetStdIds); @endphp
                                <label class="flex items-center justify-between gap-2 text-sm {{ $hasDs ? 'text-gray-700' : 'text-gray-400' }}">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" value="{{ $std->id }}" wire:model="generateForm.standard_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $std->name }}
                                    </span>
                                    @if ($hasDs)
                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full bg-emerald-50 text-emerald-600">datesheet</span>
                                    @endif
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">No classes found.</p>
                            @endforelse
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Only classes with a datesheet for this exam can be seated. Others are shown greyed for reference.</p>
                        @error('generateForm.standard_ids')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-700">Rooms <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2 text-xs">
                                <button type="button" wire:click="selectAllRooms" class="text-blue-600 hover:text-blue-800 font-medium">Select all</button>
                                <span class="text-gray-300">|</span>
                                <button type="button" wire:click="clearAllRooms" class="text-gray-500 hover:text-gray-700 font-medium">Clear</button>
                            </div>
                        </div>
                        <div class="border border-gray-200 rounded-md p-3 max-h-40 overflow-y-auto space-y-1.5">
                            @forelse ($rooms->where('is_active', true) as $room)
                                <label class="flex items-center justify-between gap-2 text-sm text-gray-700">
                                    <span class="flex items-center gap-2">
                                        <input type="checkbox" value="{{ $room->id }}" wire:model="generateForm.room_ids" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        {{ $room->room_name }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ $room->capacity }} seats</span>
                                </label>
                            @empty
                                <p class="text-xs text-gray-400">No active rooms. Add rooms first.</p>
                            @endforelse
                        </div>
                        <p class="text-xs text-gray-400 mt-1">If capacity is short on a date, an overflow “Exam Hall” is added automatically.</p>
                        @error('generateForm.room_ids')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeGeneratePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="generatePlan" type="button" wire:loading.attr="disabled" wire:target="generatePlan"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="generatePlan">Generate Plan</span>
                        <span wire:loading wire:target="generatePlan">Generating...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRMS
    ══════════════════════════════════════════════════ --}}
    @php
        $deletes = [
            ['flag' => $pendingDeleteRoomId,        'cancel' => 'cancelDeleteRoom',        'exec' => 'executeDeleteRoom',        'title' => 'Delete room?',        'body' => 'All its seats will be removed. Existing plans keep their snapshot.'],
            ['flag' => $pendingDeleteInvigilatorId, 'cancel' => 'cancelDeleteInvigilator', 'exec' => 'executeDeleteInvigilator', 'title' => 'Delete invigilator?', 'body' => 'They will be removed from future assignments.'],
            ['flag' => $pendingDeletePlanId,        'cancel' => 'cancelDeletePlan',        'exec' => 'executeDeletePlan',        'title' => 'Delete seating plan?', 'body' => 'All seat &amp; invigilator assignments for this plan will be permanently removed.'],
            ['flag' => $pendingDeleteDatesheetId,   'cancel' => 'cancelDeleteDatesheet',   'exec' => 'executeDeleteDatesheet',   'title' => 'Delete datesheet?',   'body' => 'Every paper on this sheet goes with it. Admit cards already issued keep their own copy.'],
        ];
    @endphp
    @foreach ($deletes as $d)
        @if ($d['flag'] !== null)
            <div class="fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="{{ $d['cancel'] }}"></div>
                <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-base font-semibold text-gray-900 mb-1">{{ $d['title'] }}</h3>
                            <p class="text-sm text-gray-500">{!! $d['body'] !!}</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 mt-5">
                        <button wire:click="{{ $d['cancel'] }}" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button wire:click="{{ $d['exec'] }}" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
