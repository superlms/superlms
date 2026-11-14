<div class="min-h-screen bg-gray-50">
    {{-- ══════════ EXPORT — the school admin's "Export as…" box: the school first, then Excel or PDF ══════════ --}}
    @if ($showExportPicker)
        <x-admin.export-picker excel="exportTeachers" pdf="exportTeachersPdf">
            <div class="mb-5">
                <label class="block text-xs font-medium text-gray-500 mb-1">School <span class="text-red-500">*</span></label>
                <select wire:model.live="exportSchool"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Select school</option>
                    @foreach ($exportSchools as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>
                @error('exportSchool')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
            </div>
        </x-admin.export-picker>
    @endif

    @php
        $filtered = $search || $filterOrganization || $filterGender || $filterStatus !== '';
        // The filter bar's boxes, as on Fees, a size smaller.
        $sel      = 'text-xs min-w-0 max-w-[11rem] bg-white border border-gray-200 rounded-md px-2 py-1 text-gray-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    @endphp

    {{-- ══════════ HEADER in the Fees look: the title, the figures in one line and Add,
         then the filter bar (search, the filters, Clear and Export) — as on Students ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-50">
        @include('livewire.super-admin.partials.list-header', [
            'title' => 'Teachers', 'stats' => $stats, 'addLabel' => 'Add New Teacher', 'addAction' => 'openAddPanel',
        ])

        {{-- FILTER BAR — the Fees bar, a little smaller --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2" data-filter-bar>
            <div class="flex flex-wrap items-center gap-2">
                <div class="flex items-center gap-1 text-xs font-semibold text-gray-600">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    Filter by:
                </div>

                <div class="relative flex-[1_1_8rem] min-w-0 max-w-52">
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" /></svg>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search name, username, email, mobile…"
                        class="text-xs bg-white border border-gray-200 rounded-md pl-7 pr-2.5 py-1 text-gray-700 w-full focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <select wire:model.live="filterOrganization" class="{{ $sel }}">
                    <option value="">All Schools</option>
                    @foreach ($organizations as $org)
                        <option value="{{ $org->id }}">{{ $org->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filterGender" class="{{ $sel }}">
                    <option value="">All Genders</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>

                <select wire:model.live="filterStatus" class="{{ $sel }}">
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>

                @if ($filtered)
                    <button wire:click="clearFilters"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif

                {{-- Export: the school admin's "Export as…" box, with the school picked in it first. --}}
                <button wire:click="openExportPicker" title="Export a school's teachers as Excel or PDF"
                    class="sm:ml-auto inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-md border transition-colors border-gray-600 bg-gray-600 text-white hover:bg-gray-700 hover:border-gray-700">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 13v8m0 0l-3-3m3 3l3-3M20 16.6A5 5 0 0018 7h-1.26A8 8 0 104 15.25" /></svg>
                    Export
                </button>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4">

        {{-- ══════════ DESKTOP TABLE in the Students look — fixed layout: every column fits the screen
             (no side scroll on a wide screen), long text is cut with … and shown in full on hover.
             S.No, the teacher (qualification under the name), Username, Mobile, Email, Gender,
             Class Teacher and School; the status is the dot beside the row's ⋮. ══════════ --}}
        @php
            $th = 'px-3 py-2.5 text-left text-[11px] font-medium text-gray-500 truncate';
            $td = 'px-3 py-3 truncate';
            // What reloads the list: while one of these takes longer than a
            // moment, skeleton rows stand in for the rows (the panel's
            // auto-refresh is not one of them, so it never flickers).
            $reloads = 'search,filterOrganization,filterGender,filterStatus,gotoPage,nextPage,previousPage,clearFilters';
        @endphp
        <div class="hidden md:block rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto xl:overflow-visible rounded-xl">
                <table class="w-full min-w-[960px] xl:min-w-0 table-fixed text-[13px] text-gray-700">
                    <thead>
                        <tr class="bg-gray-50/80">
                            <th class="pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500 rounded-tl-xl">S.No</th>
                            <th class="{{ $th }} w-[20%]">Teacher</th>
                            <th class="{{ $th }} w-[120px]">Username</th>
                            <th class="{{ $th }} w-[104px]">Mobile</th>
                            <th class="{{ $th }}">Email</th>
                            <th class="{{ $th }} w-[72px]">Gender</th>
                            <th class="{{ $th }} w-[120px]">Class Teacher</th>
                            <th class="{{ $th }}">School</th>
                            <th class="px-2 py-2.5 w-[60px] rounded-tr-xl"><span class="sr-only">Status and actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 hidden" wire:loading.delay.long.class.remove="hidden" wire:target="{{ $reloads }}">
                        @include('livewire.super-admin.partials.list-skeleton-rows', ['cols' => \App\Support\ListSkeleton::TEACHERS, 'rows' => 10])
                    </tbody>
                    <tbody class="divide-y divide-gray-100" wire:loading.delay.long.class="hidden" wire:target="{{ $reloads }}">
                        @forelse ($teachers as $index => $teacher)
                            @php
                                $name     = $teacher->user?->name;
                                $username = $teacher->user?->username;
                                $school   = $teacher->user?->organization?->name;
                                $email    = $teacher->user?->email;
                                $mobile   = $teacher->user?->mobile_number ?: $teacher->phone;
                                $gender   = $teacher->user?->gender;
                                // As the school panel shows it: Class 8-A, or Class 6 A & B.
                                $classOf  = implode(', ', \App\Support\SectionNames::classLines($teacher->assignedClasses));
                            @endphp
                            <tr wire:key="sa-tch-row-{{ $teacher->id }}" class="hover:bg-gray-50/70 transition-colors">
                                <td class="pl-4 pr-2 py-3 text-gray-400 tabular-nums">{{ $teachers->firstItem() + $index }}</td>

                                <td class="{{ $td }}" title="{{ $name }}{{ $teacher->qualification ? ' — ' . $teacher->qualification : '' }}">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        @if ($teacher->user?->image)
                                            <img src="{{ $teacher->user->image }}" loading="lazy" alt=""
                                                class="w-8 h-8 rounded-full object-cover object-top border border-gray-200 flex-shrink-0">
                                        @else
                                            <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-600 text-[11px] font-semibold flex items-center justify-center flex-shrink-0">
                                                {{ strtoupper(substr($name ?? 'T', 0, 1)) }}
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-medium text-gray-900 truncate leading-tight">{{ $name ?: '—' }}</p>
                                            <p class="text-[11px] text-gray-400 truncate mt-0.5">{{ $teacher->qualification ?: '—' }}</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="{{ $td }}" title="{{ $username }}">{{ $username ?: '—' }}</td>
                                <td class="{{ $td }} tabular-nums">{{ $mobile ?: '—' }}</td>
                                <td class="{{ $td }} text-gray-500" title="{{ $email }}">{{ $email ?: '—' }}</td>
                                <td class="{{ $td }}">{{ $gender ? ucfirst($gender) : '—' }}</td>
                                <td class="{{ $td }} {{ $classOf !== '' ? '' : 'text-gray-400' }}" title="{{ $classOf }}">{{ $classOf !== '' ? $classOf : '—' }}</td>
                                <td class="{{ $td }}" title="{{ $school }}">{{ $school ?: '—' }}</td>

                                <td class="px-2 py-3">
                                    @include('livewire.super-admin.partials.list-row-actions', [
                                        'active'  => (bool) $teacher->user?->is_active,
                                        'actions' => [
                                            ['View', 'viewTeacher(' . (int) $teacher->user_id . ')', 'view'],
                                            ['Edit', 'openEditPanel(' . (int) $teacher->id . ')', 'edit'],
                                            ['Delete', 'onDeleteTeacherSuperAdmin(' . (int) $teacher->id . ')', 'delete'],
                                        ],
                                    ])
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-14 text-center">
                                    <p class="text-gray-500 text-sm">No teachers found</p>
                                    @if ($filtered)
                                        <button wire:click="clearFilters" class="mt-2 text-xs text-gray-900 underline underline-offset-2 font-medium">Clear filters</button>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @include('livewire.super-admin.partials.list-pagination', ['paginator' => $teachers])
        </div>

        {{-- ══════════ MOBILE CARDS (skeleton cards while the list reloads) ══════════ --}}
        <div class="md:hidden space-y-3 hidden" wire:loading.delay.long.class.remove="hidden" wire:target="{{ $reloads }}">
            @include('livewire.super-admin.partials.list-skeleton-cards', ['cards' => 4])
        </div>
        <div class="md:hidden space-y-3" wire:loading.delay.long.class="hidden" wire:target="{{ $reloads }}">
            @forelse ($teachers as $index => $teacher)
                @php $mActive = (bool) $teacher->user?->is_active; @endphp
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden" wire:key="sa-tch-card-m-{{ $teacher->id }}">
                    <div class="flex items-center gap-3 p-4 border-b border-gray-100">
                        <span class="text-xs font-semibold text-gray-400 w-6 text-center tabular-nums flex-shrink-0">{{ $teachers->firstItem() + $index }}</span>
                        @if ($teacher->user?->image)
                            <img src="{{ $teacher->user->image }}" alt=""
                                class="w-10 h-10 rounded-full object-cover object-top border border-gray-200 flex-shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-gray-600">{{ strtoupper(substr($teacher->user?->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $teacher->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 truncate">{{ $teacher->user?->organization?->name ?? '—' }}</p>
                        </div>
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $mActive ? 'bg-emerald-500' : 'bg-rose-500' }}"
                            title="{{ $mActive ? 'Active' : 'Inactive' }}" role="img" aria-label="{{ $mActive ? 'Active' : 'Inactive' }}"></span>
                    </div>

                    <div class="px-4 py-3 grid grid-cols-2 gap-2 text-sm">
                        <div>
                            <p class="text-xs text-gray-400">Mobile</p>
                            <p class="text-gray-700 font-medium">{{ $teacher->user?->mobile_number ?: ($teacher->phone ?: '—') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-400">Username</p>
                            <p class="text-gray-700 font-medium truncate">{{ $teacher->user?->username ?: '—' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-400">Class Teacher</p>
                            <p class="text-gray-700 truncate">{{ implode(', ', \App\Support\SectionNames::classLines($teacher->assignedClasses)) ?: '—' }}</p>
                        </div>
                    </div>

                    <div class="flex items-center border-t border-gray-100 divide-x divide-gray-100">
                        <button wire:click="viewTeacher({{ $teacher->user_id }})"
                            class="flex-1 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">View</button>
                        <button wire:click="openEditPanel({{ $teacher->id }})"
                            class="flex-1 py-2.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Edit</button>
                        <button wire:click="onDeleteTeacherSuperAdmin({{ $teacher->id }})"
                            class="flex-1 py-2.5 text-xs font-medium text-rose-600 hover:bg-rose-50">Delete</button>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-xl border border-gray-200 p-8 text-center">
                    <p class="text-gray-500 text-sm">No teachers found</p>
                    @if ($filtered)
                        <button wire:click="clearFilters" class="mt-3 text-sm text-gray-900 underline underline-offset-2 font-medium">Clear filters</button>
                    @endif
                </div>
            @endforelse

            {{-- Pagination Mobile --}}
            @if ($teachers->hasPages())
                <div class="flex items-center justify-between bg-white rounded-xl border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500">{{ $teachers->firstItem() }}–{{ $teachers->lastItem() }} of {{ number_format($teachers->total()) }}</p>
                    <div class="flex items-center gap-1.5">
                        @if (!$teachers->onFirstPage())
                            <button wire:click="previousPage" class="h-8 px-3 text-xs text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Previous</button>
                        @endif
                        <span class="inline-flex items-center justify-center min-w-8 h-8 px-2 text-xs bg-gray-900 text-white rounded-lg">{{ $teachers->currentPage() }}</span>
                        @if ($teachers->hasMorePages())
                            <button wire:click="nextPage" class="h-8 px-3 text-xs text-gray-700 border border-gray-200 rounded-lg hover:bg-gray-50">Next</button>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT — the school panel's teacher slide-in (flat 2-col grid,
         header + footer fixed). Same fields and saves as before; the school
         picker stays first since the Super Admin adds for any school.
    ══════════════════════════════════════════════════ --}}
    @php
        $saIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $saLb = 'block text-sm font-medium text-gray-700 mb-1.5';
    @endphp
    @foreach ([
        ['open' => $showAddPanel, 'p' => 'add', 'close' => 'closeAddPanel', 'save' => 'saveNewTeacher'],
        ['open' => $showEditPanel, 'p' => 'edit', 'close' => 'closeEditPanel', 'save' => 'saveEditTeacher'],
    ] as $form)
        @if ($form['open'])
            @php
                $p        = $form['p'];
                $isEdit   = $p === 'edit';
                $image    = $isEdit ? $editImage : $addImage;
                $imageUrl = $isEdit ? $editImageUrl : null;
                $states   = $isEdit ? $editStates : $addStates;
                $cities   = $isEdit ? $editCities : $addCities;
            @endphp
            <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden" wire:key="sa-teacher-{{ $p }}-panel">
                <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="{{ $form['close'] }}"></div>
                <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                    {{-- Fixed header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $isEdit ? 'Edit Teacher' : 'New Teacher' }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5">{{ $isEdit ? 'Update teacher details' : 'A password is generated and emailed to the teacher on save' }}</p>
                        </div>
                        <button wire:click="{{ $form['close'] }}" type="button" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>

                    {{-- Scrollable body --}}
                    <div class="flex-1 overflow-y-auto px-6 py-6 space-y-5">

                        {{-- School --}}
                        <div>
                            <label class="{{ $saLb }}">School <span class="text-red-500">*</span></label>
                            <select wire:model.defer="{{ $p }}OrgId" class="{{ $saIn }} @error($p . 'OrgId') border-red-400 @enderror">
                                <option value="">Select School</option>
                                @foreach ($organizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                            @error($p . 'OrgId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- Profile image --}}
                        <div>
                            <label class="{{ $saLb }}">Teacher Profile Image</label>
                            <div class="flex items-center gap-3">
                                @if ($image)
                                    <img src="{{ $image->temporaryUrl() }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @elseif ($imageUrl)
                                    <img src="{{ $imageUrl }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-teal-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                                <input type="file" wire:model="{{ $p }}Image" accept="image/*" class="flex-1 text-sm">
                            </div>
                            <div wire:loading wire:target="{{ $p }}Image" class="text-xs text-blue-600 mt-1">Uploading...</div>
                            @error($p . 'Image')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- Personal info grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $saLb }}">Full Name <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Name" type="text" maxlength="50" placeholder="Enter full name"
                                    class="{{ $saIn }} @error($p . 'Name') border-red-400 @enderror">
                                @error($p . 'Name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Email <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Email" type="email" placeholder="teacher@school.com"
                                    class="{{ $saIn }} @error($p . 'Email') border-red-400 @enderror">
                                @error($p . 'Email')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Mobile <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Mobile" type="tel" maxlength="10" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit mobile"
                                    class="{{ $saIn }} @error($p . 'Mobile') border-red-400 @enderror">
                                @error($p . 'Mobile')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Employee ID <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}EmployeeId" type="text" maxlength="20" placeholder="e.g. EMP001"
                                    class="{{ $saIn }} @error($p . 'EmployeeId') border-red-400 @enderror">
                                @error($p . 'EmployeeId')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Date of Birth <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Dob" type="date" min="1940-01-01" max="{{ now()->subYear()->format('Y-m-d') }}"
                                    class="{{ $saIn }} @error($p . 'Dob') border-red-400 @enderror">
                                @error($p . 'Dob')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Date of Joining <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}DateOfJoining" type="date" min="1970-01-01" max="{{ now()->format('Y-m-d') }}"
                                    class="{{ $saIn }} @error($p . 'DateOfJoining') border-red-400 @enderror">
                                @error($p . 'DateOfJoining')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Gender <span class="text-red-500">*</span></label>
                                <select wire:model.defer="{{ $p }}Gender" class="{{ $saIn }} @error($p . 'Gender') border-red-400 @enderror">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                                @error($p . 'Gender')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Qualification <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Qualification" type="text" maxlength="50" placeholder="e.g. B.Ed, M.Sc"
                                    class="{{ $saIn }} @error($p . 'Qualification') border-red-400 @enderror">
                                @error($p . 'Qualification')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Emergency Contact <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}EmergencyContact" type="tel" maxlength="10" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)" placeholder="10-digit mobile"
                                    class="{{ $saIn }} @error($p . 'EmergencyContact') border-red-400 @enderror">
                                @error($p . 'EmergencyContact')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">Pincode <span class="text-red-500">*</span></label>
                                <input wire:model.defer="{{ $p }}Pincode" type="text" maxlength="6" inputmode="numeric"
                                    oninput="this.value=this.value.replace(/\D/g,'').slice(0,6)" placeholder="6-digit pincode"
                                    class="{{ $saIn }} @error($p . 'Pincode') border-red-400 @enderror">
                                @error($p . 'Pincode')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $saLb }}">State</label>
                                <select wire:model.live="{{ $p }}State" class="{{ $saIn }}">
                                    <option value="">Select State</option>
                                    @foreach ($states as $st)
                                        <option value="{{ $st }}">{{ $st }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $saLb }}">City</label>
                                <select wire:model.defer="{{ $p }}City" @disabled(empty($cities)) class="{{ $saIn }} disabled:opacity-50">
                                    <option value="">Select City</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city }}">{{ $city }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Address --}}
                        <div>
                            <label class="{{ $saLb }}">Address <span class="text-red-500">*</span></label>
                            <textarea wire:model.defer="{{ $p }}Address" rows="3" placeholder="House no., Street, Area"
                                class="{{ $saIn }} resize-none @error($p . 'Address') border-red-400 @enderror"></textarea>
                            @error($p . 'Address')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        {{-- Active toggle --}}
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model.defer="{{ $p }}Active" class="rounded">
                            <span class="text-sm text-gray-700">Active (can log in)</span>
                        </label>
                    </div>

                    {{-- Fixed footer --}}
                    <div class="px-6 py-3.5 border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2 flex-shrink-0">
                        <button wire:click="{{ $form['close'] }}" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md order-3 sm:order-1">Cancel</button>
                        <button wire:click="{{ $form['save'] }}" type="button" wire:loading.attr="disabled" wire:target="{{ $form['save'] }}"
                            class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center justify-center gap-1.5 order-1 sm:order-3">
                            <span wire:loading.remove wire:target="{{ $form['save'] }}">{{ $isEdit ? 'Update Teacher' : 'Create Teacher' }}</span>
                            <span wire:loading wire:target="{{ $form['save'] }}">Saving...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

    {{-- ══════════════════════════════════════════════════
         VIEW — the school panel's view: one plain label / value list
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal && !empty($viewData))
        @php
            $tUser = $viewData['user'] ?? null;
            $tDet  = $viewData['detail'] ?? null;
            // Dates as the school panel shows them (12 Sep 2024); whatever can't
            // be read as a date is shown as it is.
            $viewDate = function ($d) {
                if (!$d) {
                    return 'N/A';
                }
                try {
                    return \Carbon\Carbon::parse($d)->format('d M Y');
                } catch (\Throwable $e) {
                    return (string) $d;
                }
            };
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $tUser->name ?? 'Teacher Details' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            {{ ($tUser->email ?? '') !== '' ? $tUser->email . ' · ' : '' }}{{ ($tUser->is_active ?? false) ? 'Active' : 'Inactive' }}
                        </p>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="flex justify-center pb-2">
                        @if ($teacherImageUrl)
                            <img src="{{ $teacherImageUrl }}" alt="{{ $tUser->name ?? '' }}"
                                class="w-24 h-24 rounded-full object-cover object-top border border-gray-200">
                        @else
                            <div class="w-24 h-24 rounded-full bg-teal-100 flex items-center justify-center">
                                <span class="text-3xl font-semibold text-teal-600">{{ strtoupper(substr($tUser->name ?? 'T', 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>

                    @foreach ([
                        'School'            => $viewData['school_name'] ?? 'N/A',
                        'Employee ID'       => $tDet->employee_id ?? 'N/A',
                        'Mobile'            => $tUser->mobile_number ?? 'N/A',
                        'Gender'            => ($tUser->gender ?? null) ? ucfirst($tUser->gender) : 'N/A',
                        'Date of Birth'     => $viewDate($tUser->dob ?? null),
                        'Emergency Contact' => $tDet->emergency_contact ?? 'N/A',
                        'Date of Joining'   => $viewDate($tDet->date_of_joining ?? null),
                        'Qualification'     => $tDet->qualification ?? 'N/A',
                        'Subjects'          => ($viewData['subjects'] ?? '') !== '' ? $viewData['subjects'] : 'N/A',
                        'Address'           => $tDet->address ?? 'N/A',
                        'City'              => $tDet->city ?? 'N/A',
                        'State'             => $tDet->state ?? 'N/A',
                        'Pincode'           => $tDet->pincode ?? 'N/A',
                    ] as $label => $value)
                        <div class="grid grid-cols-3 gap-3 text-sm">
                            <span class="text-xs text-gray-400 uppercase tracking-wider">{{ $label }}</span>
                            <span class="col-span-2 text-gray-800 font-medium break-words">{{ $value === '—' ? 'N/A' : $value }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-between flex-shrink-0">
                    @if (!empty($tDet?->id))
                        <button wire:click="editFromView({{ $tDet->id }})" type="button"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Edit</button>
                    @else
                        <span></span>
                    @endif
                    <div class="flex items-center gap-2">
                        {{-- Only when the super admin has Send Credentials on for the school (WhatsApp tab). --}}
                        @if (\App\Services\SchoolWhatsApp::credentialsOn((int) ($viewData['user']->organization_id ?? 0)))
                        {{-- Send Credentials: the WhatsApp welcome again — admission no. / username and password (2026-10-14). --}}
                        <button type="button" wire:click="sendCredentials" wire:confirm="Send the login details again on WhatsApp?"
                            wire:loading.attr="disabled" wire:target="sendCredentials"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 text-sm font-medium text-emerald-700 border border-emerald-200 hover:bg-emerald-50 rounded-md whitespace-nowrap disabled:opacity-60">
                            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48 0 1.46 1.07 2.88 1.22 3.08.15.2 2.1 3.2 5.08 4.48.71.31 1.26.49 1.7.63.71.23 1.36.2 1.87.12.57-.08 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 01-4.8-1.31l-.34-.2-3.57.94.95-3.48-.22-.36a9.39 9.39 0 01-1.44-5.01c0-5.2 4.23-9.43 9.44-9.43 2.52 0 4.89.98 6.67 2.77a9.37 9.37 0 012.76 6.67c0 5.2-4.23 9.43-9.44 9.43m8.03-17.47A11.28 11.28 0 0012.05.7C5.79.7.7 5.79.7 12.04c0 2 .52 3.95 1.52 5.67L.6 23.6l6.03-1.58a11.33 11.33 0 005.42 1.38h.01c6.25 0 11.34-5.09 11.34-11.34 0-3.03-1.18-5.88-3.32-8.02" /></svg>
                            Send Credentials
                        </button>
                        @endif
                        <button wire:click="closeViewModal" type="button"
                            class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM — the school panel's popup
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-[9999] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete teacher?</h3>
                        <p class="text-sm text-gray-500">This will permanently delete the teacher and their account. This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md transition-colors">Cancel</button>
                    <button wire:click="doDeleteTeacher({{ $deleteTargetId }})" wire:loading.attr="disabled" wire:target="doDeleteTeacher"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="doDeleteTeacher">Delete Teacher</span>
                        <span wire:loading wire:target="doDeleteTeacher">Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
