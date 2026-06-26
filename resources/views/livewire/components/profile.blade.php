<div class="min-h-screen bg-gray-50">

    {{-- ══════════════════════════════════════════════════
         SUB-ADMIN PERSONAL DETAILS (only for scoped sub-admins)
    ══════════════════════════════════════════════════ --}}
    @if (auth()->user()->role === 'sub-admin')
        @php
            $me = auth()->user();
            $adminCatalog = collect(config('menu.admin', []))
                ->mapWithKeys(fn($i) => [$i['link'] => $i['title']]);
            $myAccess = collect((array) $me->permissions)
                ->map(fn($p) => $adminCatalog[$p] ?? $p)
                ->all();
        @endphp
        <div class="px-4 sm:px-6 pt-4 sm:pt-6">
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-4">
                    @if ($me->image)
                        <img src="{{ $me->image }}" class="w-14 h-14 rounded-full object-cover border border-gray-200" alt="">
                    @else
                        <span class="w-14 h-14 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xl font-bold">{{ strtoupper(substr($me->name ?? '', 0, 1)) }}</span>
                    @endif
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 truncate">{{ $me->name }}</h2>
                        <p class="text-sm text-gray-500 truncate">{{ $me->email }}</p>
                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-xs font-medium">Sub-admin</span>
                    </div>
                </div>
                <div class="px-5 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Mobile</dt>
                        <dd class="text-gray-800 font-medium">{{ $me->mobile_number ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Alt. Mobile</dt>
                        <dd class="text-gray-800 font-medium">{{ $me->alternative_mobile ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Gender</dt>
                        <dd class="text-gray-800 font-medium capitalize">{{ $me->gender ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Date of Birth</dt>
                        <dd class="text-gray-800 font-medium">{{ $me->dob ? \Carbon\Carbon::parse($me->dob)->format('d M Y') : '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Date of Joining</dt>
                        <dd class="text-gray-800 font-medium">{{ $me->date_of_joining ? \Carbon\Carbon::parse($me->date_of_joining)->format('d M Y') : '—' }}</dd>
                    </div>
                    <div class="flex justify-between sm:block">
                        <dt class="text-gray-500">Organization</dt>
                        <dd class="text-gray-800 font-medium truncate">{{ $organization->name ?? '—' }}</dd>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-gray-100">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Granted Access</h4>
                    @if (!empty($myAccess))
                        <div class="flex flex-wrap gap-2">
                            @foreach ($myAccess as $perm)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">{{ $perm }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-gray-400">No functionalities granted.</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         HEADER — compact (about-app sized) logo + name + contact
                  + 2 tabs (School Profile / School Info)
    ══════════════════════════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-3 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-4">

                {{-- Compact logo with small "change" overlay (about-app sized) --}}
                <div class="relative w-12 h-12 mx-auto sm:mx-0 flex-shrink-0">
                    @if ($organization && $organization->logo)
                        <img src="{{ $organization->logo }}"
                             class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1">
                    @else
                        <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100">
                            <x-icon name="building-office-2" class="w-6 h-6 text-blue-500" />
                        </div>
                    @endif
                    <button type="button" wire:click="openLogoPanel"
                            title="Change logo"
                            class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-blue-600 hover:bg-blue-700
                                   text-white flex items-center justify-center shadow ring-2 ring-white transition">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </button>
                </div>

                {{-- Name + contact strip --}}
                <div class="flex-1 min-w-0 text-center sm:text-left">
                    <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate leading-tight">
                        {{ $organization->name ?? 'School Profile' }}
                    </h1>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 justify-center sm:justify-start text-xs">
                        @if ($schoolMobileNo ?: $organization->mobile_number ?? null)
                            <span class="inline-flex items-center gap-1 text-gray-500">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ $schoolMobileNo ?: $organization->mobile_number }}
                            </span>
                        @endif
                        @if ($schoolEmail ?: $organization->email ?? null)
                            <span class="inline-flex items-center gap-1 text-gray-500 break-all">
                                <svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ $schoolEmail ?: $organization->email }}
                            </span>
                        @endif
                        @if ($schoolAddress ?: $organization->address ?? null)
                            <span class="inline-flex items-start gap-1 text-gray-500">
                                <svg class="w-3 h-3 text-gray-400 mt-0.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="text-left">{{ $schoolAddress ?: $organization->address }}</span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs — only 2 now: School Profile, School Info --}}
        <div class="border-t border-gray-200 px-3 sm:px-6">
            <div class="flex gap-1 overflow-x-auto">
                <button wire:click="showTab('profile')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $activeTab === 'profile' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        School Profile
                    </span>
                </button>
                <button wire:click="showTab('info')"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                           {{ $activeTab === 'info' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        School Info
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         TAB: School Info — descriptive cards (about / vision /
         management / documents). Read-only on this tab; the form
         is reached via the Add / Edit button at the top.
    ══════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'info' && $infoMode === 'view')
        @php
            $hasAnySchoolInfo = $aboutSchool
                || $usmVision || $usmMission || $usmValues || $usmGoals
                || count($customSections) > 0
                || count($schoolManagement) > 0
                || count($uploadedDocuments) > 0;
            $lastUpdated = $schoolInfo && $schoolInfo->updated_at
                ? $schoolInfo->updated_at->format('d M Y, h:i A')
                : null;
        @endphp

        {{-- Empty state — about-app-style "no information" panel with an Add button. --}}
        @unless ($hasAnySchoolInfo)
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No School Info Yet</h3>
                <p class="text-gray-400 text-sm max-w-sm mb-5">
                    Add your school's about, vision, management team and documents to bring this profile to life.
                </p>
                <button wire:click="setInfoMode('edit')"
                    class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add School Info
                </button>
            </div>
        @else

            {{-- ══════════════════════════════════════════════════
                 Compact header (matches admin about-app template)
            ══════════════════════════════════════════════════ --}}
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        @if ($organization && $organization->logo)
                            <img src="{{ $organization->logo }}" alt="School logo"
                                class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <x-icon name="building-office-2" class="w-6 h-6 text-indigo-500" />
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">School Info</h1>
                            <p class="text-sm text-gray-500 mt-0.5 truncate">About, vision, management &amp; documents</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                        @if ($lastUpdated)
                            <span class="inline-block px-4 py-1.5 bg-indigo-50 text-indigo-700 text-sm font-semibold rounded-full border border-indigo-100">
                                Last updated: {{ $lastUpdated }}
                            </span>
                        @endif
                        <button wire:click="setInfoMode('edit')"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit
                        </button>
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-3 sm:px-6 py-4 sm:py-8 space-y-4 sm:space-y-6">

                {{-- ── Numbered paragraph sections (super-admin about-app style) ──
                     About School + Vision + Mission + Values + Goals + any extra
                     custom sections, each rendered as a full-width paragraph card. --}}
                @php
                    $paragraphSections = array_values(array_filter([
                        ['title' => 'About Our School', 'description' => $aboutSchool],
                        ['title' => 'Vision',           'description' => $usmVision],
                        ['title' => 'Mission',          'description' => $usmMission],
                        ['title' => 'Values',           'description' => $usmValues],
                        ['title' => 'Goals',            'description' => $usmGoals],
                    ], fn ($s) => !empty($s['description'])));

                    foreach ($customSections as $cs) {
                        if (!empty($cs['title']) || !empty($cs['description'])) {
                            $paragraphSections[] = [
                                'title'       => $cs['title'] ?? '',
                                'description' => $cs['description'] ?? '',
                            ];
                        }
                    }
                @endphp

                @foreach ($paragraphSections as $i => $section)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-indigo-200 hover:shadow-md transition-all duration-200">
                        @if (!empty($section['title']))
                            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                        bg-gradient-to-r from-indigo-50 to-blue-50">
                                <span class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                                             text-white text-sm font-bold rounded-xl flex items-center
                                             justify-center flex-shrink-0 shadow-sm">
                                    {{ $i + 1 }}
                                </span>
                                <h2 class="text-base font-semibold text-gray-900">{{ $section['title'] }}</h2>
                            </div>
                        @endif
                        @if (!empty($section['description']))
                            <div class="px-4 sm:px-6 py-5">
                                <p class="text-sm text-gray-600 leading-relaxed">
                                    {!! nl2br(e($section['description'])) !!}
                                </p>
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- ── Management Team ── --}}
                @if (count($schoolManagement) > 0)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-amber-200 hover:shadow-md transition-all duration-200">
                        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-yellow-50 to-amber-50 flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Management Team</h2>
                        </div>
                        <div class="px-4 sm:px-6 py-5">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                @foreach ($schoolManagement as $member)
                                    <div class="text-center">
                                        @if (!empty($member['photo_path']))
                                            <img src="{{ $member['photo_path'] }}"
                                                class="w-16 h-16 rounded-full mx-auto object-cover border border-gray-200 shadow-sm mb-2">
                                        @else
                                            <div class="w-16 h-16 rounded-full mx-auto bg-amber-100 flex items-center justify-center mb-2">
                                                <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <p class="text-sm font-medium text-gray-800">{{ $member['name'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $member['designation'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── School Documents ── --}}
                @if (count($uploadedDocuments) > 0)
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-rose-200 hover:shadow-md transition-all duration-200">
                        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-rose-50 to-red-50 flex items-center gap-3">
                            <div class="w-8 h-8 bg-rose-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">School Documents</h2>
                        </div>
                        <div class="px-4 sm:px-6 py-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                @foreach ($uploadedDocuments as $document)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-8 h-8 bg-rose-100 rounded flex items-center justify-center shrink-0">
                                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">{{ $document['title'] }}</p>
                                                <p class="text-xs text-gray-400">{{ strtoupper($document['file_type']) }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ $document['file_path'] }}" target="_blank"
                                           class="ml-3 shrink-0 text-xs px-3 py-1 bg-rose-600 text-white rounded hover:bg-rose-700 transition">View</a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endunless
    @endif

    {{-- ══════════════════════════════════════════════════════════
         TAB: School Profile — one unified School Information card
              (org logo header + School Details → divider → Bank Details
               → Change Password)
    ══════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'profile' && $infoMode === 'view')
        <div class="max-w-5xl mx-auto p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-5">

            {{-- One unified School Information card (accounts/profile template).
                 Mirrors every field super-admin sets when adding a school. --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">

                {{-- Card header: org LOGO + title + Change Password button --}}
                <div class="px-5 sm:px-6 py-4 flex items-center justify-between border-b border-gray-50 gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        @if ($organization && $organization->logo)
                            <img src="{{ $organization->logo }}" alt="{{ $organization->name }}"
                                 class="w-10 h-10 rounded-xl object-cover border border-gray-100 bg-white flex-shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100 flex-shrink-0">
                                <x-icon name="building-office-2" class="w-5 h-5 text-blue-500" />
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold text-gray-900 truncate">School Information</h2>
                            <p class="text-xs text-gray-400">Contact, bank &amp; credentials</p>
                        </div>
                    </div>
                    <button wire:click="openPasswordPanel"
                            class="inline-flex items-center gap-1.5 px-3 sm:px-3.5 py-1.5 sm:py-2 bg-blue-600 hover:bg-blue-700
                                   text-white text-xs sm:text-sm font-semibold rounded-lg shadow-sm transition-colors flex-shrink-0">
                        <x-icon name="lock-closed" class="w-3.5 h-3.5 sm:w-4 sm:h-4" />
                        Change Password
                    </button>
                </div>

                {{-- Stacked sections: School Details first, then Bank Details (with divider). --}}
                <div>
                    {{-- School Details — every field the super-admin sets when adding a school. --}}
                    <div class="px-5 sm:px-6 py-5">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">School Details</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                            @foreach ([
                                'School Name'         => $organization->name ?? null,
                                'Email'               => $organization->email ?? null,
                                'Mobile Number'       => $organization->mobile_number ?? null,
                                'State'               => $organization->state ?? null,
                                'Education Board'     => $organization->education_board ?? null,
                                'School Code'         => $organization->school_code ?? null,
                                'Affiliation Number'  => $organization->affiliation_no ?? null,
                                'UDISE Number'        => $organization->udise_number ?? null,
                                'Serial Number'       => $organization->serial_number ?? null,
                                'Address'             => $organization->address ?? null,
                            ] as $label => $value)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-sm text-gray-400 flex-shrink-0">{{ $label }}</span>
                                    <span class="text-sm text-gray-700 text-right break-words">{{ $value ?: '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Divider between School Details and Bank Details --}}
                    <div class="border-t border-gray-100"></div>

                    {{-- Bank Details — every field super-admin sets in Bank Details modal. --}}
                    <div class="px-5 sm:px-6 py-5">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-3">Bank Details</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2.5">
                            @foreach ([
                                'Bank Name'        => $organization->bank_name ?? null,
                                'Account Number'   => $organization->bank_account_no ?? null,
                                'IFSC Code'        => $organization->bank_ifsc ?? null,
                                'Branch'           => $organization->bank_branch ?? null,
                                'Account Holder'   => $organization->bank_holder_name ?? null,
                            ] as $label => $value)
                                <div class="flex justify-between items-start gap-3">
                                    <span class="text-sm text-gray-400 flex-shrink-0">{{ $label }}</span>
                                    <span class="text-sm text-gray-700 text-right break-words">{{ $value ?: '—' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         EDIT mode — reachable from either tab (replaces view content
         while the form is open).
    ══════════════════════════════════════════════════════════ --}}
    @if ($infoMode === 'edit')
        <div class="max-w-5xl mx-auto p-3 sm:p-4 md:p-6 space-y-4 sm:space-y-6">

            {{-- Top action bar — back to view --}}
            <div class="flex items-center justify-between gap-3 -mb-2">
                <button wire:click="setInfoMode('view')"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300
                               hover:bg-gray-50 text-gray-700 text-xs sm:text-sm font-medium rounded-lg transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to View
                </button>
                <span class="text-xs text-gray-400">Editing School Info</span>
            </div>

            {{-- ── Paragraph sections — About / Vision / Mission / Values / Goals
                  rendered identically as separate full-width paragraph cards. ── --}}
            @foreach ([
                ['label' => 'About School', 'model' => 'aboutSchool', 'placeholder' => 'Describe your school…'],
                ['label' => 'Vision',       'model' => 'usmVision',   'placeholder' => 'Long-term vision for your school…'],
                ['label' => 'Mission',      'model' => 'usmMission',  'placeholder' => 'Mission statement…'],
                ['label' => 'Values',       'model' => 'usmValues',   'placeholder' => 'Core values your school upholds…'],
                ['label' => 'Goals',        'model' => 'usmGoals',    'placeholder' => 'Key goals and objectives…'],
            ] as $i => $item)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                bg-gradient-to-r from-indigo-50 to-blue-50">
                        <span class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                                     text-white text-sm font-bold rounded-xl flex items-center
                                     justify-center flex-shrink-0 shadow-sm">
                            {{ $i + 1 }}
                        </span>
                        <h2 class="text-base font-semibold text-gray-900">{{ $item['label'] }}</h2>
                    </div>
                    <div class="p-6">
                        <textarea wire:model="{{ $item['model'] }}" rows="4"
                            class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition"
                            placeholder="{{ $item['placeholder'] }}"></textarea>
                    </div>
                </div>
            @endforeach

            {{-- ── Custom Sections — admin can add additional paragraph cards. ── --}}
            @foreach ($customSections as $index => $section)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3
                                bg-gradient-to-r from-indigo-50 to-blue-50">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
                                         text-white text-sm font-bold rounded-xl flex items-center
                                         justify-center flex-shrink-0 shadow-sm">
                                {{ $index + 6 }}
                            </span>
                            <h2 class="text-base font-semibold text-gray-900 truncate">
                                {{ trim($section['title'] ?? '') !== '' ? $section['title'] : 'Custom Section' }}
                            </h2>
                        </div>
                        <button type="button" wire:click="confirmDeleteSection({{ $index }})"
                                title="Delete section"
                                class="p-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors flex-shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                    <div class="p-6 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Section Title</label>
                            <input type="text" wire:model.defer="customSections.{{ $index }}.title"
                                class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition"
                                placeholder="e.g. Our History">
                            @error('customSections.'.$index.'.title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            <textarea wire:model.defer="customSections.{{ $index }}.description" rows="4"
                                class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition"
                                placeholder="Write the paragraph content for this section…"></textarea>
                            @error('customSections.'.$index.'.description')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add Section button --}}
            <div>
                <button type="button" wire:click="addCustomSection"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-3
                               border-2 border-dashed border-indigo-200 hover:border-indigo-400
                               text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50
                               text-sm font-semibold rounded-2xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Section
                </button>
            </div>

            {{-- Contact Information --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-sky-50 flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900">Contact Information</h2>
                </div>
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">School Email</label>
                        <input type="email" wire:model="schoolEmail"
                            class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 transition"
                            placeholder="contact@school.edu">
                        @error('schoolEmail')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mobile Number</label>
                        <input type="text" wire:model="schoolMobileNo" inputmode="numeric"
                            class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 transition"
                            placeholder="9876543210">
                        @error('schoolMobileNo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Website URL</label>
                        <input type="url" wire:model="websiteUrl"
                            class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 transition"
                            placeholder="https://www.yourschool.edu">
                        @error('websiteUrl')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">School Address</label>
                        <textarea wire:model="schoolAddress" rows="2"
                            class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 transition"
                            placeholder="Full address with city, state, pin code…"></textarea>
                        @error('schoolAddress')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Website Description</label>
                        <textarea wire:model="websiteInfo" rows="2"
                            class="w-full p-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-400 transition"
                            placeholder="Brief description of your website…"></textarea>
                    </div>
                </div>
            </div>

            {{-- Management Team --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-yellow-50 to-amber-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Management Team</h2>
                            <p class="text-xs text-gray-400">{{ count($schoolManagement) }} member(s)</p>
                        </div>
                    </div>
                    <button wire:click="openMemberPanel()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Member
                    </button>
                </div>
                <div class="p-6 space-y-3">
                    @forelse ($schoolManagement as $index => $member)
                        <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-amber-200 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                @if (!empty($member['photo_path']))
                                    <img src="{{ $member['photo_path'] }}" class="h-12 w-12 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                                @else
                                    <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center border-2 border-white shadow flex-shrink-0">
                                        <svg class="h-6 w-6 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $member['name'] }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $member['designation'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button wire:click="openMemberPanel({{ $index }})"
                                    class="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 transition-colors" title="Edit">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="confirmDeleteMember({{ $index }})"
                                    class="p-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors" title="Remove">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-sm text-gray-400">No members added yet.</p>
                            <p class="text-xs text-gray-300 mt-1">Click "Add Member" to add team members.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- School Documents --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-cyan-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">School Documents</h2>
                            <p class="text-xs text-gray-400">{{ count($uploadedDocuments) }} saved · {{ count($pendingDocuments) }} pending · Max 2MB PDF</p>
                        </div>
                    </div>
                    <button wire:click="openDocumentPanel"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-cyan-600 hover:bg-cyan-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Document
                    </button>
                </div>
                <div class="p-6 space-y-3">

                    {{-- Saved documents --}}
                    @foreach ($uploadedDocuments as $document)
                        <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200 hover:border-cyan-200 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $document['title'] }}</p>
                                    <p class="text-xs text-gray-400 uppercase">{{ $document['file_type'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <a href="{{ $document['file_path'] }}" target="_blank"
                                    class="p-1.5 rounded-lg border border-cyan-200 text-cyan-600 hover:bg-cyan-50 transition-colors" title="View">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <button wire:click="confirmDeleteDocument({{ $document['id'] }})"
                                    class="p-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors" title="Delete">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @endforeach

                    {{-- Pending documents (queued, not yet saved) --}}
                    @foreach ($pendingDocuments as $index => $pending)
                        <div class="flex items-center justify-between gap-3 p-3 bg-amber-50 rounded-xl border border-amber-200">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $pending['title'] }}</p>
                                    <p class="text-xs text-amber-700 uppercase font-semibold">Pending Save</p>
                                </div>
                            </div>
                            <button wire:click="removePendingDocument({{ $index }})"
                                class="p-1.5 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 transition-colors flex-shrink-0" title="Remove">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    @endforeach

                    @if (count($uploadedDocuments) === 0 && count($pendingDocuments) === 0)
                        <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm text-gray-400">No documents uploaded yet.</p>
                            <p class="text-xs text-gray-300 mt-1">PDF only · max 2 MB per file.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Save Button --}}
            <div class="flex justify-end pt-2 pb-6">
                <button wire:click="saveSchoolInfo" wire:loading.attr="disabled"
                    class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl text-sm font-semibold transition shadow-md hover:shadow-lg flex items-center gap-2 disabled:opacity-60">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span wire:loading.remove wire:target="saveSchoolInfo">Save All Changes</span>
                    <span wire:loading wire:target="saveSchoolInfo">Saving…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         MANAGEMENT MEMBER SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showMemberPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeMemberPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <button wire:click="closeMemberPanel"
                    class="absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-200 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-500 transition-colors shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex-1 overflow-y-auto px-6 pt-6 pb-6 space-y-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editMemberIndex !== null ? 'Edit Member' : 'Add Member' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Fill in the member details below</p>
                    </div>

                    {{-- Photo --}}
                    <div class="flex items-center gap-4">
                        @if ($newMemberPhoto instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                            <img src="{{ $newMemberPhoto->temporaryUrl() }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                        @elseif (!empty($newMember['photo_path']))
                            <img src="{{ $newMember['photo_path'] }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                        @else
                            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center border-2 border-white shadow flex-shrink-0">
                                <svg class="w-7 h-7 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        @endif
                        <div class="flex-1">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Photo</label>
                            <input type="file" wire:model="newMemberPhoto" accept="image/*"
                                class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 border border-gray-300 rounded-md">
                            <p class="text-xs text-gray-400 mt-1">JPG/PNG up to 2MB. Leave empty to keep current.</p>
                            <div wire:loading wire:target="newMemberPhoto" class="text-xs text-amber-600 mt-1">Uploading…</div>
                            @error('newMemberPhoto')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newMember.name" placeholder="e.g. Mr. Sharma"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                        @error('newMember.name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newMember.designation" placeholder="e.g. Principal"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                        @error('newMember.designation')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeMemberPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveMember" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveMember">{{ $editMemberIndex !== null ? 'Update' : 'Add Member' }}</span>
                        <span wire:loading wire:target="saveMember">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DOCUMENT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDocumentPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDocumentPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                <button wire:click="closeDocumentPanel"
                    class="absolute top-4 right-4 z-20 w-9 h-9 flex items-center justify-center rounded-full bg-white border border-gray-200 hover:bg-red-50 hover:border-red-300 text-gray-500 hover:text-red-500 transition-colors shadow-md">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="flex-1 overflow-y-auto px-6 pt-6 pb-6 space-y-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Add Document</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload a PDF up to 2MB. Will be saved when you click "Save All Changes".</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newDocument.title" placeholder="e.g. Affiliation Certificate"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500">
                        @error('newDocument.title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            File <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(PDF only — max 2MB)</span>
                        </label>
                        <input type="file" wire:model="newDocumentFile" accept=".pdf"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700 hover:file:bg-cyan-100 border border-gray-300 rounded-md">
                        <div wire:loading wire:target="newDocumentFile" class="text-xs text-cyan-600 mt-1.5">Uploading…</div>
                        @error('newDocumentFile')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDocumentPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDocumentPanel" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveDocumentPanel">Queue Document</span>
                        <span wire:loading wire:target="saveDocumentPanel">Queueing…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRMS
    ══════════════════════════════════════════════════ --}}
    @if ($pendingDeleteMemberIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteMember"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Remove member?</h3>
                        <p class="text-sm text-gray-500">
                            @if (isset($schoolManagement[$pendingDeleteMemberIndex]))
                                Remove <strong>{{ $schoolManagement[$pendingDeleteMemberIndex]['name'] ?? 'this member' }}</strong>? Their photo on S3 will be deleted.
                            @else
                                This action cannot be undone.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteMember" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeDeleteMember" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Remove</button>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingDeleteDocumentId !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteDocument"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete document?</h3>
                        <p class="text-sm text-gray-500">The PDF will be permanently removed from S3 and the school records.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteDocument" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeDeleteDocument" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRM — custom section
    ══════════════════════════════════════════════════ --}}
    @if ($pendingDeleteSectionIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelDeleteSection"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Remove section?</h3>
                        <p class="text-sm text-gray-500">
                            @php $sec = $customSections[$pendingDeleteSectionIndex] ?? null; @endphp
                            @if ($sec && !empty(trim($sec['title'] ?? '')))
                                Remove <strong>{{ $sec['title'] }}</strong>? Click "Save All Changes" afterwards to make it permanent.
                            @else
                                This section will be removed from the form. Save changes to make it permanent.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelDeleteSection" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeDeleteSection" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Remove</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         LOGO CHANGE — slide-in panel (admin Exams template:
         top-bar title + inline close, no floating X)
    ══════════════════════════════════════════════════ --}}
    @if ($showLogoPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeLogoPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Top bar header (title + subtitle + inline close button) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Change School Logo</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload a square JPG or PNG, up to 2 MB</p>
                    </div>
                    <button wire:click="closeLogoPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4"
                     x-data="{ isUploading: false }"
                     x-on:livewire-upload-start="isUploading = true"
                     x-on:livewire-upload-finish="isUploading = false"
                     x-on:livewire-upload-error="isUploading = false">

                    {{-- Preview --}}
                    <div class="flex items-center gap-4">
                        @if ($tempPhotoUrl)
                            <img src="{{ $tempPhotoUrl }}"
                                 class="w-20 h-20 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        @elseif ($organization && $organization->logo)
                            <img src="{{ $organization->logo }}"
                                 class="w-20 h-20 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0">
                        @else
                            <div class="w-20 h-20 rounded-xl bg-blue-50 flex items-center justify-center border border-blue-100 flex-shrink-0">
                                <x-icon name="building-office-2" class="w-9 h-9 text-blue-500" />
                            </div>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-800">Current logo</p>
                            <p class="text-xs text-gray-400">Pick a file below to replace it.</p>
                        </div>
                    </div>

                    {{-- File picker --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            New logo <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(JPG/PNG — max 2 MB)</span>
                        </label>
                        <input type="file" wire:model="photo" accept="image/*"
                               class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-300 rounded-md">
                        <div wire:loading wire:target="photo" class="text-xs text-blue-600 mt-1.5">Uploading…</div>
                        @error('photo')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Footer actions --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeLogoPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="savePhoto" wire:loading.attr="disabled" @disabled(!$photo)
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="savePhoto">Save Logo</span>
                        <span wire:loading wire:target="savePhoto">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         CHANGE PASSWORD — slide-in panel (admin Exams template)
    ══════════════════════════════════════════════════ --}}
    @if ($showPasswordPanel)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closePasswordPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Top bar header (title + subtitle + inline close button) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Change Password</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Set a new strong password for the admin login</p>
                    </div>
                    <button wire:click="closePasswordPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4"
                     x-data="{ pwd: @entangle('newPassword').live }">
                    @foreach ([
                        ['label' => 'Current Password', 'model' => 'currentPassword', 'show' => $showCurrentPassword, 'toggle' => 'current', 'error' => 'currentPassword'],
                        ['label' => 'New Password',     'model' => 'newPassword',     'show' => $showNewPassword,     'toggle' => 'new',     'error' => 'newPassword'],
                        ['label' => 'Confirm Password', 'model' => 'confirmPassword', 'show' => $showConfirmPassword, 'toggle' => 'confirm', 'error' => null],
                    ] as $field)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                {{ $field['label'] }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input wire:model.live.debounce.150ms="{{ $field['model'] }}"
                                       type="{{ $field['show'] ? 'text' : 'password' }}"
                                       placeholder="{{ $field['label'] }}"
                                       autocomplete="new-password"
                                       class="w-full px-3.5 py-2.5 pr-10 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                <button type="button" wire:click="togglePasswordVisibility('{{ $field['toggle'] }}')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($field['show'])
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                            @if ($field['error'])
                                @error($field['error'])<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endforeach

                    {{-- Strong-password live checklist --}}
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Password must include</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ([
                                ['label' => 'At least 8 characters', 'test' => 'pwd && pwd.length >= 8'],
                                ['label' => 'An uppercase letter',   'test' => '/[A-Z]/.test(pwd || "")'],
                                ['label' => 'A lowercase letter',    'test' => '/[a-z]/.test(pwd || "")'],
                                ['label' => 'A number',              'test' => '/[0-9]/.test(pwd || "")'],
                                ['label' => 'A symbol (!@#$…)',      'test' => '/[^A-Za-z0-9]/.test(pwd || "")'],
                            ] as $rule)
                                <div class="flex items-center gap-1.5 text-xs"
                                     :class="{{ $rule['test'] }} ? 'text-emerald-600' : 'text-gray-400'">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="leading-tight">{{ $rule['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer actions --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closePasswordPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="updatePassword" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
