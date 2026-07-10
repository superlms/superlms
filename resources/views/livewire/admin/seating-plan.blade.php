<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         HEADER (compact, fees/about-app style) + tabs
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Exam Seating Management</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Rooms, auto-generated seating plans &amp; datesheets</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200 mr-1">
                        <span class="pr-4">Rooms: <strong class="text-gray-800">{{ $rooms->count() }}</strong></span>
                        <span class="px-4">Plans: <strong class="text-blue-600">{{ $plans->total() }}</strong></span>
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
    </div>

    <div class="p-4 sm:p-6">

        {{-- ══════════════════════════════════════════════
             TAB: SEATING PLANS
        ══════════════════════════════════════════════ --}}
        @if ($activeTab === 'plans')

            {{-- ══════════════════════════════════════════════
                 GRAPHICAL SEAT FINDER  (exam → session → class → section → student → room)
            ══════════════════════════════════════════════ --}}
            <div class="bg-white rounded-xl border border-gray-200 mb-5 overflow-hidden">
                <div class="px-4 sm:px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50 flex flex-wrap items-center gap-x-2 gap-y-1">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    <h3 class="text-sm font-semibold text-gray-800">Graphical Seat Finder</h3>
                    <span class="text-xs text-gray-400">Filter by exam, class, section, student &amp; room to view the seating graphics</span>
                </div>
                <div class="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Exam</label>
                        <select wire:model.live="filterExamId" class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                            <option value="">Select exam</option>
                            @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Session</label>
                        <select wire:model.live="filterPlanId" @disabled(!$filterExamId) class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400 disabled:opacity-50">
                            <option value="">Select session</option>
                            @foreach ($filterPlans as $fp)
                                <option value="{{ $fp->id }}">{{ $fp->exam_date?->format('d M') }}{{ $fp->session ? ' · ' . $fp->session : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Class</label>
                        <select wire:model.live="filterStandardId" class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400">
                            <option value="">All classes</option>
                            @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Section</label>
                        <select wire:model.live="filterSectionId" @disabled(!$filterStandardId) class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400 disabled:opacity-50">
                            <option value="">All sections</option>
                            @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Student</label>
                        <select wire:model.live="filterStudentId" @disabled(!$filterStandardId) class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400 disabled:opacity-50">
                            <option value="">All students</option>
                            @foreach ($filterStudents as $stu)<option value="{{ $stu->user_id }}">{{ $stu->full_name }}{{ $stu->roll_no ? ' (Roll ' . $stu->roll_no . ')' : '' }}</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-gray-500 mb-1">Room</label>
                        <select wire:model.live="filterRoomId" @disabled(!$filterPlanId) class="w-full text-xs bg-white border border-gray-200 rounded-md px-2.5 py-2 text-gray-700 focus:ring-2 focus:ring-indigo-400 disabled:opacity-50">
                            <option value="">All rooms</option>
                            @foreach ($graphRoomOptions as $gr)<option value="{{ $gr->id }}">{{ $gr->room_name }}</option>@endforeach
                        </select>
                    </div>
                </div>
                @if ($filterExamId || $graphFiltersActive)
                    <div class="px-4 pb-3 -mt-1">
                        <button wire:click="clearGraphFilters" class="inline-flex items-center gap-1 text-xs font-medium text-red-600 hover:text-red-800">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                            Clear filters
                        </button>
                    </div>
                @endif
            </div>

            {{-- ── Graphical seating result ── --}}
            @if ($graphPlan)
                <div class="mb-5">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900">{{ $graphPlan->name }}</h3>
                            <p class="text-xs text-gray-500">
                                {{ $graphPlan->exam->exam_name ?? '' }} · {{ $graphPlan->exam_date?->format('d M Y') }}{{ $graphPlan->session ? ' · ' . $graphPlan->session : '' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3 text-[11px] text-gray-500">
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-emerald-100 border border-emerald-200 inline-block"></span> Seated</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-red-50 border border-red-200 inline-block"></span> Conflict</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm ring-2 ring-blue-500 inline-block"></span> Student</span>
                            <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-gray-50 border border-dashed border-gray-300 inline-block"></span> Empty</span>
                        </div>
                    </div>

                    @forelse ($graphRooms as $room)
                        @php
                            $roomAssignments = $graphAssignments->where('room_id', $room->id);
                            $cells = [];
                            foreach ($roomAssignments as $a) {
                                if ($a->seat) $cells[$a->seat->row_no][$a->seat->col_no] = $a;
                            }
                            $filled = $roomAssignments->whereNotNull('student_id')->count();
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-4">
                            <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 class="text-base font-semibold text-gray-900">{{ $room->room_name }}</h4>
                                    <p class="text-xs text-gray-500">{{ $room->building }} · {{ $filled }}/{{ $room->capacity }} seats filled</p>
                                </div>
                                <a href="{{ route('admin.seating-plan.room-pdf', ['organization' => auth()->user()->organization_id, 'id' => $graphPlan->id, 'roomId' => $room->id]) }}" target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white border border-gray-200 rounded-md text-gray-700 hover:bg-gray-50">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    Room PDF
                                </a>
                            </div>
                            <div class="p-4 overflow-x-auto">
                                <p class="text-[10px] uppercase tracking-wide text-gray-400 text-center mb-2">⬆ Front (Board)</p>
                                <div class="inline-block min-w-full">
                                    @for ($r = 1; $r <= $room->rows; $r++)
                                        <div class="flex gap-1.5 justify-center mb-1.5">
                                            @for ($c = 1; $c <= $room->columns; $c++)
                                                @php
                                                    $cell   = $cells[$r][$c] ?? null;
                                                    $hasStu = $cell && $cell->student_id;
                                                    $sid    = $hasStu ? (int) $cell->student_id : null;
                                                    $isFocus = $graphFocusId && $sid === $graphFocusId;
                                                    $dim = $hasStu && !empty($graphMatchIds) && !in_array($sid, $graphMatchIds);
                                                @endphp
                                                <div class="w-16 h-14 rounded-md border text-[10px] flex flex-col items-center justify-center text-center px-1 leading-tight transition-opacity
                                                    {{ !$hasStu ? 'bg-gray-50 border-dashed border-gray-200 text-gray-300'
                                                        : ($cell->has_conflict ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200') }}
                                                    {{ $dim ? 'opacity-25' : '' }}
                                                    {{ $isFocus ? 'ring-2 ring-blue-500 ring-offset-1 z-10' : '' }}">
                                                    @if ($hasStu)
                                                        <span class="font-bold text-gray-700">{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="truncate w-full text-gray-600">{{ \Illuminate\Support\Str::limit($cell->student->name ?? '', 8) }}</span>
                                                        <span class="text-[9px] text-blue-600 font-medium">{{ $cell->class_label }}</span>
                                                    @else
                                                        <span>{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="text-[9px]">empty</span>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12 bg-white rounded-xl border border-gray-200 text-sm text-gray-400">
                            No seats match the selected filters.
                        </div>
                    @endforelse
                </div>
            @elseif ($filterExamId)
                <div class="mb-5 text-center py-10 bg-white rounded-xl border border-gray-200">
                    <p class="text-sm font-semibold text-gray-700">No seating session found</p>
                    <p class="text-xs text-gray-400 mt-1">This exam has no generated seating plan yet. Generate one first.</p>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-3 mb-5">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">All plans</span>
                <input wire:model.live.debounce.300ms="planSearch" type="text" placeholder="Search plans…"
                    class="text-sm bg-white border border-gray-200 rounded-md px-3 py-2 text-gray-700 w-64 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($plans as $plan)
                    <div class="bg-white rounded-xl border border-gray-200 hover:border-blue-200 hover:shadow-md transition-all overflow-hidden">
                        <div class="px-5 py-4 border-b border-gray-100 flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <h3 class="text-base font-semibold text-gray-900 truncate">{{ $plan->name }}</h3>
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $plan->exam->exam_name ?? '—' }}</p>
                            </div>
                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide flex-shrink-0
                                {{ $plan->status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $plan->status }}
                            </span>
                        </div>
                        <div class="px-5 py-4 space-y-2">
                            <div class="flex items-center gap-2 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $plan->exam_date?->format('d M Y') }}{{ $plan->session ? ' · ' . ucfirst($plan->session) : '' }}
                            </div>
                            <div class="grid grid-cols-3 gap-2 text-center pt-1">
                                <div class="bg-blue-50 rounded-lg py-2">
                                    <p class="text-base font-bold text-blue-700">{{ $plan->total_students }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-blue-500">Students</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg py-2">
                                    <p class="text-base font-bold text-gray-700">{{ $plan->total_seats }}</p>
                                    <p class="text-[10px] uppercase tracking-wide text-gray-500">Seats</p>
                                </div>
                                <div class="rounded-lg py-2 {{ $plan->conflict_count > 0 ? 'bg-red-50' : 'bg-emerald-50' }}">
                                    <p class="text-base font-bold {{ $plan->conflict_count > 0 ? 'text-red-600' : 'text-emerald-600' }}">{{ $plan->conflict_count }}</p>
                                    <p class="text-[10px] uppercase tracking-wide {{ $plan->conflict_count > 0 ? 'text-red-500' : 'text-emerald-500' }}">Conflicts</p>
                                </div>
                            </div>
                        </div>
                        <div class="px-5 py-3 border-t border-gray-100 flex items-center gap-1.5">
                            <button wire:click="viewPlan({{ $plan->id }})"
                                class="flex-1 text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">View</button>
                            <a href="{{ route('admin.seating-plan.print', ['organization' => auth()->user()->organization_id, 'id' => $plan->id]) }}" target="_blank"
                                class="flex-1 text-center text-xs font-medium px-3 py-1.5 rounded-md border border-gray-200 text-gray-600 hover:bg-gray-50">Print</a>
                            @if ($plan->status !== 'published')
                                <button wire:click="publishPlan({{ $plan->id }})"
                                    class="text-xs font-medium px-3 py-1.5 rounded-md border border-emerald-200 text-emerald-600 hover:bg-emerald-50">Publish</button>
                            @endif
                            <button wire:click="confirmDeletePlan({{ $plan->id }})"
                                class="px-2 py-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-16 bg-white rounded-xl border border-gray-200">
                        <div class="w-14 h-14 mx-auto mb-3 bg-blue-50 rounded-full flex items-center justify-center">
                            <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                        </div>
                        <p class="text-base font-semibold text-gray-800">No seating plans yet</p>
                        <p class="text-sm text-gray-400 mt-1">Add rooms &amp; invigilators, then generate a plan.</p>
                    </div>
                @endforelse
            </div>

            @if ($plans->hasPages())
                <div class="mt-6">{{ $plans->links() }}</div>
            @endif
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
                        <div class="flex items-center gap-1.5 mt-2 text-xs text-gray-500">
                            <span>{{ $room->rows }}×{{ $room->columns }}</span>
                            <span class="text-gray-300">•</span>
                            <span class="font-semibold text-gray-700">{{ $room->capacity }} seats</span>
                        </div>
                        <div class="flex items-center gap-1 mt-2.5 pt-2 border-t border-gray-100">
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
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[640px]">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left w-12">#</th>
                                <th class="px-4 py-3 text-left">Exam</th>
                                <th class="px-4 py-3 text-left">Class &amp; Section</th>
                                <th class="px-4 py-3 text-center w-28">Papers</th>
                                <th class="px-4 py-3 text-center w-32">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($datesheets as $i => $ds)
                                <tr wire:key="ds-{{ $ds->id }}" class="hover:bg-gray-50/70">
                                    <td class="px-4 py-3 text-gray-400">{{ $i + 1 }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-800">{{ $ds->exam->exam_name ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-100">
                                            {{ $ds->standard->name ?? '—' }}@if ($ds->section)<span class="text-blue-400"> · </span>{{ $ds->section->name }}@else <span class="text-blue-400">· All sections</span>@endif
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-600">{{ $ds->papers_count }}</td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <button wire:click="viewDatesheet({{ $ds->id }})" title="View"
                                                class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>
                                            <button wire:click="confirmDeleteDatesheet({{ $ds->id }})" title="Delete"
                                                class="p-1.5 rounded-md border border-red-200 text-red-500 hover:bg-red-50">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-12 text-center text-gray-400">
                                        No datesheets yet.
                                        <button wire:click="openDatesheetCreate" class="block mx-auto mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium">Create a datesheet →</button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    {{-- ══════════════════════════════════════════════════
         PLAN VIEWER (room-wise charts)
    ══════════════════════════════════════════════════ --}}
    @if ($viewingPlan)
        <div class="fixed inset-0 z-40 overflow-hidden">
            <div class="absolute inset-0 bg-black/30 backdrop-blur-[1.5px]" wire:click="closePlanView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-4xl bg-gray-50 shadow-2xl flex flex-col">

                <button wire:click="closePlanView"
                    class="absolute top-4 right-4 z-20 w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="px-6 pt-6 pb-4 bg-white border-b border-gray-200 flex-shrink-0">
                    <h2 class="text-lg font-bold text-gray-900">{{ $viewingPlan->name }}</h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ $viewingPlan->exam->exam_name ?? '' }} · {{ $viewingPlan->exam_date?->format('d M Y') }}{{ $viewingPlan->session ? ' · ' . $viewingPlan->session : '' }}
                    </p>
                    @if ($viewingPlan->notes)
                        <p class="text-xs text-gray-400 mt-0.5">{{ $viewingPlan->notes }}</p>
                    @endif
                    <div class="flex items-center gap-2 mt-3">
                        <a href="{{ route('admin.seating-plan.print', ['organization' => auth()->user()->organization_id, 'id' => $viewingPlan->id]) }}" target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-gray-900 text-white rounded-md hover:bg-gray-800">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" /></svg>
                            Print / PDF
                        </a>
                        @if ($viewingPlan->conflict_count > 0)
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-red-50 text-red-600 border border-red-100">{{ $viewingPlan->conflict_count }} conflicts</span>
                        @else
                            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 border border-emerald-100">No conflicts</span>
                        @endif
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-6">
                    @foreach ($planRooms as $room)
                        @php
                            $roomAssignments = $planAssignments->where('room_id', $room->id);
                            $cells = [];
                            foreach ($roomAssignments as $a) {
                                if ($a->seat) $cells[$a->seat->row_no][$a->seat->col_no] = $a;
                            }
                            $filled = $roomAssignments->whereNotNull('student_id')->count();
                        @endphp
                        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                            <div class="px-5 py-3 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50 flex flex-wrap items-center justify-between gap-2">
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
                            <div class="p-4 overflow-x-auto">
                                <p class="text-[10px] uppercase tracking-wide text-gray-400 text-center mb-2">⬆ Front (Board)</p>
                                <div class="inline-block min-w-full">
                                    @for ($r = 1; $r <= $room->rows; $r++)
                                        <div class="flex gap-1.5 justify-center mb-1.5">
                                            @for ($c = 1; $c <= $room->columns; $c++)
                                                @php $cell = $cells[$r][$c] ?? null; @endphp
                                                <div class="w-16 h-14 rounded-md border text-[10px] flex flex-col items-center justify-center text-center px-1 leading-tight
                                                    {{ !$cell || !$cell->student_id ? 'bg-gray-50 border-dashed border-gray-200 text-gray-300'
                                                        : ($cell->has_conflict ? 'bg-red-50 border-red-200' : 'bg-emerald-50 border-emerald-200') }}">
                                                    @if ($cell && $cell->student_id)
                                                        <span class="font-bold text-gray-700">{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="truncate w-full text-gray-600">{{ \Illuminate\Support\Str::limit($cell->student->name ?? '', 8) }}</span>
                                                        <span class="text-[9px] text-blue-600 font-medium">{{ $cell->class_label }}</span>
                                                    @else
                                                        <span>{{ $cell->seat->seat_number ?? '' }}</span>
                                                        <span class="text-[9px]">empty</span>
                                                    @endif
                                                </div>
                                            @endfor
                                        </div>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ROOM SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showRoomPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
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
                        @error('roomForm.room_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
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
                            @error('roomForm.rows')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Columns <span class="text-red-500">*</span></label>
                            <input type="number" min="1" max="50" wire:model.live="roomForm.columns"
                                class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('roomForm.columns')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm text-blue-700">
                        Capacity: <strong>{{ (int) ($roomForm['rows'] ?? 0) * (int) ($roomForm['columns'] ?? 0) }} seats</strong>
                    </div>
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
                    <button wire:click="saveRoom" class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">{{ $editRoomId ? 'Update' : 'Add Room' }}</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DATESHEET CREATE SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDatesheetPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDatesheetPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Create Datesheet</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Pick an exam and class, then set the date, time &amp; shift per subject.</p>
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
                            <select wire:model="dsExamId" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500">
                                <option value="">Select exam…</option>
                                @foreach ($exams as $exam)<option value="{{ $exam->id }}">{{ $exam->exam_name }}</option>@endforeach
                            </select>
                            @error('dsExamId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="dsStandardId" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500">
                                <option value="">Select class…</option>
                                @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                            </select>
                            @error('dsStandardId')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section</label>
                            <select wire:model.live="dsSectionId" @disabled(!$dsStandardId) class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white focus:ring-1 focus:ring-blue-500 disabled:opacity-50">
                                <option value="">All sections</option>
                                @foreach ($dsSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                            </select>
                        </div>
                    </div>

                    @if (!$dsStandardId)
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg text-sm text-gray-500 text-center">Select a class to load its subjects.</div>
                    @elseif (empty($dsPapers))
                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-amber-700 text-center">No subjects mapped to this class/section.</div>
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
                                            <td class="px-3 py-2"><input type="date" wire:model="dsPapers.{{ $subjectId }}.exam_date" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-xs"></td>
                                            <td class="px-3 py-2"><input type="time" wire:model="dsPapers.{{ $subjectId }}.start_time" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-xs"></td>
                                            <td class="px-3 py-2"><input type="time" wire:model="dsPapers.{{ $subjectId }}.end_time" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-xs"></td>
                                            <td class="px-3 py-2">
                                                <select wire:model="dsPapers.{{ $subjectId }}.shift" class="w-full border border-gray-200 rounded-md px-2 py-1.5 text-xs bg-white">
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
                    @endif
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDatesheetPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDatesheet" wire:loading.attr="disabled" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveDatesheet">Save Datesheet</span>
                        <span wire:loading wire:target="saveDatesheet">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DATESHEET VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($viewingDatesheet)
        <div class="fixed inset-0 z-50 overflow-hidden">
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
                                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700">Shift {{ $p->shift }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-3 py-8 text-center text-gray-400">No papers.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         GENERATE PLAN SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showGeneratePanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
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
                        @error('generateForm.exam_id')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
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
                        @error('generateForm.name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
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
                        @error('generateForm.standard_ids')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
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
                        @error('generateForm.room_ids')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeGeneratePanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="generatePlan" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="generatePlan">Generate Plan</span>
                        <span wire:loading wire:target="generatePlan">Generating…</span>
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
        ];
    @endphp
    @foreach ($deletes as $d)
        @if ($d['flag'] !== null)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
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
