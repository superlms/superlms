<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         STICKY HEADER — header + tabs + filters stay pinned
         on scroll (same behaviour as the Students page).
    ══════════════════════════════════════════════════ --}}
    <div class="sticky top-0 z-40">

    {{-- ══════════════════════════════════════════════════
         HEADER
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-6 py-5">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Academic Structure</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage classes, sections & subjects</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button wire:click="onStandard"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-purple-600 hover:bg-purple-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Class
                </button>
                <button wire:click="onSection"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Section
                </button>
                <button wire:click="onSubject"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Subject
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════
         TABS
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-6">
        <nav class="flex gap-1">
            @php
                $tabs = [
                    ['key' => 'standard', 'label' => 'Classes',  'color' => 'purple',  'count' => $filteredStandards->total()],
                    ['key' => 'section',  'label' => 'Sections', 'color' => 'blue',    'count' => $filteredSections->total()],
                    ['key' => 'subject',  'label' => 'Subjects', 'color' => 'emerald', 'count' => $filteredSubjects->total()],
                ];
            @endphp
            @foreach ($tabs as $tab)
                <button wire:click="showTab('{{ $tab['key'] }}')"
                    class="relative py-3.5 px-4 text-sm font-semibold transition-colors border-b-2
                       {{ $activeTab === $tab['key']
                           ? 'border-' . $tab['color'] . '-500 text-' . $tab['color'] . '-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    {{ $tab['label'] }}
                    @if ($tab['count'] > 0)
                        <span class="ml-1.5 px-1.5 py-0.5 text-xs rounded-full
                    {{ $activeTab === $tab['key']
                        ? 'bg-' . $tab['color'] . '-100 text-' . $tab['color'] . '-700'
                        : 'bg-gray-100 text-gray-600' }}">
                            {{ $tab['count'] }}
                        </span>
                    @endif
                    @if ($tab['key'] === 'section' && $filterStandard)
                        <span class="ml-1 w-2 h-2 bg-blue-500 rounded-full inline-block"></span>
                    @endif
                    @if ($tab['key'] === 'subject' && ($filterSubjectStandard || $filterSection))
                        <span class="ml-1 w-2 h-2 bg-emerald-500 rounded-full inline-block"></span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ══════════════════════════════════════════════════
         FILTERS — Exams template style
    ══════════════════════════════════════════════════ --}}
    <div class="border-t border-gray-200 border-b border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter by:
            </div>

            <input wire:model.live.debounce.300ms="search" type="text"
                placeholder="Search {{ $activeTab === 'standard' ? 'classes' : ($activeTab === 'section' ? 'sections' : 'subjects') }}..."
                class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-48 focus:ring-2 focus:ring-purple-500 focus:border-purple-500" />

            @if ($activeTab === 'section')
                <select wire:model.live="filterStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach ($allStandards as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            @endif

            @if ($activeTab === 'subject')
                <select wire:model.live="filterSubjectStandard"
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">Select Class</option>
                    @foreach ($allStandards as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>

                <span class="text-gray-300">→</span>

                <select wire:model.live="filterSection" @disabled(!$filterSubjectStandard)
                    class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                    <option value="">Select Section</option>
                    @foreach ($availableSections as $sec)
                        <option value="{{ $sec->id }}">{{ $sec->name }}@if ($sec->code) ({{ $sec->code }})@endif</option>
                    @endforeach
                </select>
            @endif

            <select wire:model.live="filterStatus"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>

            <select wire:model.live="perPage"
                class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>

            @if ($search || $filterStandard || $filterStatus || $filterSubjectStandard || $filterSection)
                <button wire:click="resetFilters"
                    class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Clear
                </button>
            @endif
        </div>
    </div>
    </div>{{-- end sticky header --}}

    {{-- ══════════════════════════════════════════════════
         CONTENT
    ══════════════════════════════════════════════════ --}}
    <div class="p-4 sm:p-6">

        <div wire:loading.flex class="justify-center py-16">
            <div class="text-center">
                <div class="w-10 h-10 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto"></div>
                <p class="text-sm text-gray-500 mt-3">Loading...</p>
            </div>
        </div>

        <div wire:loading.remove>

            {{-- ════════════════ CLASSES (List) ════════════════ --}}
            @if ($activeTab === 'standard')
                @if ($filteredStandards->count())
                    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                        <div class="grid grid-cols-12 px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <div class="col-span-1">#</div>
                            <div class="col-span-2">Class</div>
                            <div class="col-span-2">Code</div>
                            <div class="col-span-3">Sections</div>
                            <div class="col-span-1">Board</div>
                            <div class="col-span-1 text-center">Status</div>
                            <div class="col-span-2 text-right">Actions</div>
                        </div>
                        @foreach ($filteredStandards as $idx => $std)
                            @php
                                $sectionNames = $std->sections->pluck('name')->all();
                                $sectionShow  = array_slice($sectionNames, 0, 3);
                                $sectionMore  = max(0, count($sectionNames) - count($sectionShow));
                            @endphp
                            <div class="grid grid-cols-12 items-center px-4 py-3 border-b border-gray-100 last:border-0 hover:bg-purple-50/40 transition-colors cursor-pointer"
                                wire:click="drillIntoClass({{ $std->id }})">
                                <div class="col-span-1 text-sm text-gray-500">{{ $filteredStandards->firstItem() + $idx }}</div>
                                <div class="col-span-2 flex items-center gap-2.5">
                                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $std->name }}</p>
                                </div>
                                <div class="col-span-2 text-sm text-gray-700">{{ $std->code }}</div>
                                <div class="col-span-3 text-sm text-gray-600">
                                    @if (count($sectionShow))
                                        <div class="flex flex-wrap items-center gap-1">
                                            @foreach ($sectionShow as $name)
                                                <span class="inline-block px-1.5 py-0.5 text-xs bg-blue-50 text-blue-700 rounded font-medium">{{ $name }}</span>
                                            @endforeach
                                            @if ($sectionMore > 0)
                                                <span class="text-xs text-gray-400" title="+{{ $sectionMore }} more">...</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400">No sections</span>
                                    @endif
                                </div>
                                <div class="col-span-1 text-sm text-gray-500 truncate">{{ $std->board }}</div>
                                <div class="col-span-1 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full font-medium
                                        {{ $std->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                        {{ $std->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="col-span-2 flex items-center justify-end gap-1" wire:click.stop>
                                    <button wire:click.stop="onViewStandardAdmin({{ $std->id }})"
                                        class="p-1.5 rounded-lg hover:bg-green-50 text-green-600" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="editStandard({{ $std->id }})"
                                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="onDeleteStandard({{ $std->id }})"
                                        class="p-1.5 rounded-lg hover:bg-red-50 text-red-500" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="drillIntoClass({{ $std->id }})"
                                        class="p-1.5 rounded-lg hover:bg-purple-50 text-purple-600" title="Open sections">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No classes found</p>
                        <p class="text-xs text-gray-400 mt-1">
                            {{ ($search || $filterStatus) ? 'No classes match your filters.' : 'You haven\'t added any classes yet.' }}
                        </p>
                    </div>
                @endif
            @endif

            {{-- ════════════════ SECTIONS (List) ════════════════ --}}
            @if ($activeTab === 'section')
                @if (!$filterStandard)
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">Select a class to view sections</p>
                        <p class="text-xs text-gray-400 mt-1">Use the <strong>Class</strong> filter above, or click a class from the Classes tab.</p>
                    </div>
                @elseif ($filteredSections->count())
                    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                        <div class="grid grid-cols-12 px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <div class="col-span-1">#</div>
                            <div class="col-span-3">Section</div>
                            <div class="col-span-2">Code</div>
                            <div class="col-span-3">Class</div>
                            <div class="col-span-1 text-center">Status</div>
                            <div class="col-span-2 text-right">Actions</div>
                        </div>
                        @foreach ($filteredSections as $idx => $section)
                            <div class="grid grid-cols-12 items-center px-4 py-3 border-b border-gray-100 last:border-0 hover:bg-blue-50/40 transition-colors cursor-pointer"
                                wire:click="drillIntoSection({{ $section->id }})">
                                <div class="col-span-1 text-sm text-gray-500">{{ $filteredSections->firstItem() + $idx }}</div>
                                <div class="col-span-3 flex items-center gap-2.5">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $section->name }}</p>
                                </div>
                                <div class="col-span-2 text-sm text-gray-700">{{ $section->code }}</div>
                                <div class="col-span-3 text-sm text-gray-600">{{ $section->standard->name ?? '—' }}</div>
                                <div class="col-span-1 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full font-medium
                                        {{ $section->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                        {{ $section->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="col-span-2 flex items-center justify-end gap-1" wire:click.stop>
                                    <button wire:click.stop="onViewSectionAdmin({{ $section->id }})"
                                        class="p-1.5 rounded-lg hover:bg-green-50 text-green-600" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="editSection({{ $section->id }})"
                                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="onDeleteSection({{ $section->id }})"
                                        class="p-1.5 rounded-lg hover:bg-red-50 text-red-500" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="drillIntoSection({{ $section->id }})"
                                        class="p-1.5 rounded-lg hover:bg-blue-100 text-blue-600" title="Open subjects">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No sections in this class</p>
                        <p class="text-xs text-gray-400 mt-1">Use <strong>Add Section</strong> above to create one.</p>
                    </div>
                @endif
            @endif

            {{-- ════════════════ SUBJECTS (List) ════════════════ --}}
            @if ($activeTab === 'subject')
                @if (!$filterSection)
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">Select a section to view subjects</p>
                        <p class="text-xs text-gray-400 mt-1">Pick a <strong>class</strong> then a <strong>section</strong> from the filters above, or drill in from the Sections tab.</p>
                    </div>
                @elseif ($filteredSubjects->count())
                    <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                        <div class="grid grid-cols-12 px-4 py-2.5 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <div class="col-span-1">#</div>
                            <div class="col-span-3">Subject</div>
                            <div class="col-span-2">Code</div>
                            <div class="col-span-3">Class · Sections</div>
                            <div class="col-span-1 text-center">Status</div>
                            <div class="col-span-2 text-right">Actions</div>
                        </div>
                        @foreach ($filteredSubjects as $idx => $subject)
                            <div class="grid grid-cols-12 items-center px-4 py-3 border-b border-gray-100 last:border-0 hover:bg-emerald-50/40 transition-colors">
                                <div class="col-span-1 text-sm text-gray-500">{{ $filteredSubjects->firstItem() + $idx }}</div>
                                <div class="col-span-3 flex items-center gap-2.5">
                                    @if ($subject->image)
                                        <img src="{{ $subject->image }}" alt="{{ $subject->name }}"
                                            class="w-8 h-8 rounded-lg object-cover border border-gray-100 flex-shrink-0">
                                    @else
                                        <div class="w-8 h-8 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                            </svg>
                                        </div>
                                    @endif
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $subject->name }}</p>
                                </div>
                                <div class="col-span-2 text-sm text-gray-700">{{ $subject->code }}</div>
                                <div class="col-span-3 text-sm text-gray-600 truncate">
                                    {{ $subject->standards->pluck('name')->implode(', ') ?: '—' }}
                                    @if ($subject->sections->count())
                                        <span class="text-gray-400">· {{ $subject->sections->pluck('name')->implode(', ') }}</span>
                                    @endif
                                </div>
                                <div class="col-span-1 text-center">
                                    <span class="inline-block px-2 py-0.5 text-xs rounded-full font-medium
                                        {{ $subject->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600' }}">
                                        {{ $subject->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="col-span-2 flex items-center justify-end gap-1" wire:click.stop>
                                    <button wire:click.stop="onViewSubjectAdmin({{ $subject->id }})"
                                        class="p-1.5 rounded-lg hover:bg-green-50 text-green-600" title="View">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="editSubject({{ $subject->id }})"
                                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    <button wire:click.stop="onDeleteSubject({{ $subject->id }})"
                                        class="p-1.5 rounded-lg hover:bg-red-50 text-red-500" title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="bg-white rounded-xl border border-gray-200 px-4 py-16 text-center">
                        <p class="text-sm font-medium text-gray-600">No subjects in this section</p>
                        <p class="text-xs text-gray-400 mt-1">Use <strong>Add Subject</strong> above to create one.</p>
                    </div>
                @endif
            @endif

            @php
                $paginatedResult = $activeTab === 'standard'
                    ? $filteredStandards
                    : ($activeTab === 'section' ? $filteredSections : $filteredSubjects);
            @endphp
            @if ($paginatedResult->hasPages())
                <div class="mt-6 pt-4 border-t border-gray-100">
                    {{ $paginatedResult->links() }}
                </div>
            @endif

        </div>{{-- end wire:loading.remove --}}
    </div>

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT CLASS — SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($openStandard)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Class' : 'New Class' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update class details' : 'Create a new class' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveStandard" class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class Name <span class="text-red-500">*</span></label>
                        <input wire:model.defer="standardName" type="text" placeholder="e.g. Class 10"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                        @error('standardName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Code <span class="text-red-500">*</span></label>
                        <input wire:model.defer="standardCode" type="text" placeholder="e.g. STD-10"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                        @error('standardCode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Display Order</label>
                        <input wire:model.defer="standardOrder" type="number" placeholder="0"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-purple-500 focus:border-purple-500">
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.defer="standardActive" class="rounded">
                        Active
                    </label>
                </form>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveStandard" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveStandard">{{ $editId ? 'Update Class' : 'Create Class' }}</span>
                        <span wire:loading wire:target="saveStandard">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SECTION — SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($openSection)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Section' : 'New Section' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update section details' : 'Add a section to a class' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveSection" class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Section Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="sectionName" type="text" placeholder="e.g. A"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('sectionName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Code <span class="text-red-500">*</span></label>
                            <input wire:model.defer="sectionCode" type="text" placeholder="e.g. SEC-A"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('sectionCode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                        <select wire:model.defer="selectedStandard"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                            <option value="">Select Class</option>
                            @foreach ($standards as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('selectedStandard')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" wire:model.defer="sectionActive" class="rounded">
                        Active
                    </label>
                </form>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSection" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSection">{{ $editId ? 'Update Section' : 'Create Section' }}</span>
                        <span wire:loading wire:target="saveSection">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         ADD / EDIT SUBJECT — SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($openSubject)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editId ? 'Edit Subject' : 'New Subject' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editId ? 'Update subject details' : 'Add a subject and assign it to sections' }}</p>
                    </div>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form wire:submit.prevent="saveSubject" class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject Name <span class="text-red-500">*</span></label>
                            <input wire:model.defer="subjectName" type="text" placeholder="e.g. Mathematics"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('subjectName')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Code <span class="text-red-500">*</span></label>
                            <input wire:model.defer="subjectCode" type="text" placeholder="e.g. MATH"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500">
                            @error('subjectCode')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Class <span class="text-red-500">*</span></label>
                            <select wire:model.live="selectedStandardForSubject"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm">
                                <option value="">Select Class</option>
                                @foreach ($standards as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                            @error('selectedStandardForSubject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Sections <span class="text-red-500">*</span></label>
                            <div class="border border-gray-300 rounded-md px-3 py-2 max-h-32 overflow-y-auto bg-white {{ !$selectedStandardForSubject ? 'opacity-60' : '' }}">
                                @if ($selectedStandardForSubject && $sections->count())
                                    @foreach ($sections as $sec)
                                        <label class="flex items-center gap-2 py-1 text-sm">
                                            <input type="checkbox" wire:model="selectedSectionsForSubject" value="{{ $sec->id }}" class="rounded">
                                            {{ $sec->name }}
                                            @if ($sec->code) <span class="text-xs text-gray-400">({{ $sec->code }})</span> @endif
                                        </label>
                                    @endforeach
                                @else
                                    <span class="text-xs text-gray-400">{{ $selectedStandardForSubject ? 'No sections in this class.' : 'Select a class first.' }}</span>
                                @endif
                            </div>
                            @error('selectedSectionsForSubject')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject Image</label>
                        @if ($subjectImagePreview)
                            <div class="mb-2 flex items-center gap-3">
                                <img src="{{ $subjectImagePreview }}" class="w-16 h-16 rounded-md object-cover border">
                                <button type="button"
                                    wire:click="$set('subjectImagePreview', null); $set('subjectImageUrl', null)"
                                    class="text-xs text-red-600 border border-red-200 px-2 py-1 rounded-md hover:bg-red-50">Remove</button>
                            </div>
                        @endif
                        <input type="file" wire:model="subjectImage" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                        <p class="text-xs text-gray-400 mt-1">PNG, JPG up to 2MB</p>
                        <div wire:loading wire:target="subjectImage" class="text-xs text-purple-600 mt-1">Uploading...</div>
                        @error('subjectImage')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
                        <textarea wire:model.defer="subjectDescription" rows="3" placeholder="Optional description"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm resize-none"></textarea>
                    </div>

                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.defer="isMandatory" class="rounded">
                            Mandatory Subject
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" wire:model.defer="subjectActive" class="rounded">
                            Active
                        </label>
                    </div>
                </form>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSubject" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSubject">{{ $editId ? 'Update Subject' : 'Create Subject' }}</span>
                        <span wire:loading wire:target="saveSubject">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         VIEW SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showViewModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewModalTitle }}</h2>
                    </div>
                    <button wire:click="closeViewModal" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    @foreach ($viewData as $key => $val)
                        @if (!in_array($key, ['image', 'detail_image']) && $val !== null && $val !== '')
                            <div class="grid grid-cols-3 gap-3 text-sm">
                                <span class="text-xs text-gray-400 uppercase tracking-wider">{{ str_replace('_', ' ', $key) }}</span>
                                <span class="col-span-2 text-gray-800 font-medium break-words">{{ $val }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if (!empty($viewData['image']))
                        <div>
                            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Image</p>
                            <img src="{{ $viewData['image'] }}" class="w-32 h-32 rounded-md object-cover border">
                        </div>
                    @endif
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM OVERLAY
    ══════════════════════════════════════════════════ --}}
    @if ($showDeleteConfirm)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDelete"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">
                            @switch($deleteTargetType)
                                @case('class') Delete class? @break
                                @case('section') Delete section? @break
                                @case('subject') Delete subject? @break
                                @default Confirm delete?
                            @endswitch
                        </h3>
                        <p class="text-sm text-gray-500">
                            @switch($deleteTargetType)
                                @case('class')
                                    All sections must be deleted first. Any students still assigned will be set to Inactive and need a new class.
                                @break
                                @case('section')
                                    All subjects added to this section will be deleted automatically. Students in this section will need a new section assigned.
                                @break
                                @case('subject')
                                    This will permanently remove the subject and its section mappings.
                                @break
                                @default
                                    Are you sure? This action cannot be undone.
                            @endswitch
                        </p>
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
