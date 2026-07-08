<div class="min-h-screen bg-gray-50">

    {{-- ══════════ TABS ══════════ --}}
    <div class="bg-white border-b border-gray-200 px-3 sm:px-6 sticky top-0 z-40 overflow-x-auto">
        <nav class="flex gap-1 min-w-max">
            <button wire:click="setTab('view')"
                class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                       {{ $activeTab === 'view'
                           ? 'border-blue-500 text-blue-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                View About App
            </button>
            <button wire:click="setTab('edit')"
                class="py-3 sm:py-3.5 px-3 sm:px-5 text-sm font-semibold border-b-2 transition-colors whitespace-nowrap
                       {{ $activeTab === 'edit'
                           ? 'border-purple-500 text-purple-700'
                           : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                {{ $aboutApp ? 'Edit' : 'Create' }} About App
            </button>
        </nav>
    </div>

    {{-- ══════════ VIEW TAB ══════════ --}}
    @if ($activeTab === 'view')
        @if (!$aboutApp)
            <div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-5">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Information Available</h3>
                <p class="text-gray-400 text-sm max-w-sm mb-5">App information has not been configured yet.</p>
                <button wire:click="setTab('edit')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700
                           text-white text-sm font-semibold rounded-lg shadow-sm transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Now
                </button>
            </div>
        @else
            {{-- COMPACT HEADER (fees style) --}}
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-4 sm:py-5">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $aboutApp->logo ?: asset('website-image/Group 11525.png') }}"
                            alt="App Logo"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0"
                            onerror="this.src='{{ asset('website-image/Group 11525.png') }}'">
                        <div class="min-w-0">
                            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ $aboutApp->heading ?? 'About App' }}</h1>
                            <p class="text-sm text-gray-500 mt-0.5 truncate">{{ $aboutApp->sub_heading ?? '' }}</p>
                            @if ($aboutApp->company_name || $aboutApp->company_cin)
                                <p class="text-xs text-gray-400 mt-0.5 truncate">
                                    {{ $aboutApp->company_name }}
                                    @if ($aboutApp->company_cin)
                                        · CIN: {{ $aboutApp->company_cin }}
                                    @endif
                                </p>
                            @endif
                        </div>
                    </div>
                    <button wire:click="setTab('edit')"
                        class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                               text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit
                    </button>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-3 sm:px-6 py-4 sm:py-8 space-y-4 sm:space-y-6">

                {{-- Content Sections --}}
                @if (!empty($aboutApp->content))
                    @foreach ($aboutApp->content as $i => $section)
                        @if (!empty($section['title']) || !empty($section['description']))
                            <div
                                class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                        hover:border-indigo-200 hover:shadow-md transition-all duration-200">
                                @if (!empty($section['title']))
                                    <div
                                                        class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center gap-3
                                                bg-gradient-to-r from-indigo-50 to-blue-50">
                                        <span
                                            class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600
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
                        @endif
                    @endforeach
                @endif

                {{-- Contact Details --}}
                @if (!empty($aboutApp->contact_details))
                    <div
                        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-blue-200 hover:shadow-md transition-all duration-200">
                        <div
                            class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-sky-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Contact Details</h2>
                        </div>
                        <div class="px-4 sm:px-6 py-5 space-y-3">
                            @foreach ($aboutApp->contact_details as $contact)
                                @php $type = strtolower($contact['type'] ?? ''); @endphp
                                <div
                                    class="flex items-center gap-4 p-3 bg-gray-50 rounded-xl border border-gray-100
                                            hover:border-blue-100 hover:bg-blue-50 transition-all duration-200">
                                    <div
                                        class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0
                                        {{ $type === 'email' ? 'bg-blue-100' : ($type === 'phone' ? 'bg-green-100' : 'bg-gray-100') }}">
                                        @if ($type === 'email')
                                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        @elseif ($type === 'phone')
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p
                                            class="text-xs font-semibold uppercase tracking-wider
                                            {{ $type === 'email' ? 'text-blue-500' : ($type === 'phone' ? 'text-green-500' : 'text-gray-400') }}">
                                            {{ $contact['type'] }}
                                        </p>
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $contact['value'] }}
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Address --}}
                @if ($aboutApp->address)
                    <div
                        class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden
                                hover:border-green-200 hover:shadow-md transition-all duration-200">
                        <div
                            class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-green-50 to-emerald-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Address</h2>
                        </div>
                        <div class="px-4 sm:px-6 py-5">
                            <p class="text-sm text-gray-600 leading-relaxed">{{ $aboutApp->address }}</p>
                        </div>
                    </div>
                @endif

                {{-- Core Team --}}
                @if (!empty($aboutApp->core_team))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div
                            class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-yellow-50 to-amber-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-amber-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-900">Our Core Team</h2>
                                <p class="text-xs text-gray-400">{{ count($aboutApp->core_team) }} members</p>
                            </div>
                        </div>
                        <div class="px-4 sm:px-6 py-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                                @foreach ($aboutApp->core_team as $member)
                                    @php $memberUrl = $member['link'] ?? null; @endphp
                                    <div class="group relative bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-lg hover:-translate-y-1
                                                hover:border-indigo-200 transition-all duration-200 p-6 text-center">
                                        {{-- Avatar with colored ring --}}
                                        <div class="w-20 h-20 sm:w-24 sm:h-24 mx-auto mb-4 rounded-full p-1
                                                    bg-gradient-to-br from-indigo-400 via-purple-400 to-pink-400
                                                    shadow-md group-hover:scale-105 transition-transform duration-200">
                                            @if (!empty($member['image']))
                                                <img src="{{ $member['image'] }}" alt="{{ $member['name'] ?? '' }}"
                                                    class="w-full h-full rounded-full object-cover border-2 border-white">
                                            @else
                                                <div class="w-full h-full rounded-full bg-gray-100 border-2 border-white flex items-center justify-center">
                                                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Name --}}
                                        <h3 class="font-semibold text-base text-gray-900 leading-tight">{{ $member['name'] ?? '' }}</h3>

                                        {{-- Designation --}}
                                        @if (!empty($member['position']))
                                            <p class="mt-1 text-xs sm:text-sm font-medium bg-gradient-to-r from-indigo-600 to-pink-600 bg-clip-text text-transparent">
                                                {{ $member['position'] }}
                                            </p>
                                        @endif

                                        {{-- Description (compact, optional) --}}
                                        @if (!empty($member['description']))
                                            <p class="mt-3 text-xs text-gray-500 leading-relaxed line-clamp-2">
                                                {{ $member['description'] }}
                                            </p>
                                        @endif

                                        {{-- View Profile button --}}
                                        @if ($memberUrl)
                                            <a href="{{ $memberUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="mt-4 inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold
                                                       text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-full
                                                       hover:bg-indigo-100 hover:border-indigo-300 transition-colors">
                                                View Profile
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6" />
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Social Media --}}
                @if (!empty($aboutApp->social_media))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div
                            class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-sky-50
                                    flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Follow Us On Social Media</h2>
                        </div>
                        <div class="px-4 sm:px-6 py-6">
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4">
                                @foreach ($aboutApp->social_media as $social)
                                    <a href="{{ $social['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
                                        class="group flex flex-col items-center gap-2 px-3 py-4 bg-gray-50 border border-gray-200
                                               rounded-2xl hover:shadow-md hover:border-blue-300 hover:bg-blue-50/50
                                               hover:-translate-y-0.5 transition-all duration-200">
                                        <div class="w-12 h-12 bg-white rounded-xl p-2 border border-gray-100 shadow-sm
                                                    flex items-center justify-center group-hover:scale-110 transition-transform">
                                            @if (!empty($social['icon']))
                                                <img src="{{ $social['icon'] }}" class="w-full h-full object-contain">
                                            @else
                                                <x-social-platform-icon :platform="$social['platform'] ?? ''" class="w-full h-full block" />
                                            @endif
                                        </div>
                                        <span class="text-xs sm:text-sm font-semibold text-gray-700 capitalize group-hover:text-blue-700 transition-colors text-center truncate w-full">
                                            {{ $social['platform'] }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Documents --}}
                @if (!empty($aboutApp->documents))
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 flex items-center gap-3">
                            <div class="w-8 h-8 bg-cyan-500 rounded-xl flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h2 class="text-base font-semibold text-gray-900">Documents</h2>
                            <span class="ml-auto text-xs text-gray-400">{{ count($aboutApp->documents) }} file(s)</span>
                        </div>
                        <div class="p-4 sm:p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            @foreach ($aboutApp->documents as $doc)
                                @php
                                    $ext = strtolower($doc['file_type'] ?? '');
                                    $extColor = match(true) {
                                        in_array($ext, ['pdf']) => ['ring' => 'bg-red-50 text-red-600 border-red-100', 'pill' => 'bg-red-100 text-red-700'],
                                        in_array($ext, ['doc', 'docx']) => ['ring' => 'bg-blue-50 text-blue-600 border-blue-100', 'pill' => 'bg-blue-100 text-blue-700'],
                                        default => ['ring' => 'bg-cyan-50 text-cyan-600 border-cyan-100', 'pill' => 'bg-cyan-100 text-cyan-700'],
                                    };
                                @endphp
                                <a href="{{ $doc['file_path'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
                                    class="group block p-4 bg-gray-50 rounded-2xl border border-gray-200 hover:border-cyan-300 hover:bg-white hover:shadow-md hover:-translate-y-0.5 transition-all">
                                    <div class="flex items-start gap-3">
                                        <div class="w-12 h-12 rounded-xl {{ $extColor['ring'] }} border flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v6h6" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate group-hover:text-cyan-700">{{ $doc['title'] ?? 'Document' }}</p>
                                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold uppercase rounded {{ $extColor['pill'] }}">
                                                    {{ $doc['file_type'] ?? 'FILE' }}
                                                </span>
                                                @if (!empty($doc['file_size']))
                                                    <span class="text-xs text-gray-400">{{ number_format($doc['file_size'] / 1024, 0) }} KB</span>
                                                @endif
                                            </div>
                                        </div>
                                        <svg class="w-4 h-4 text-gray-300 group-hover:text-cyan-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

            </div>
        @endif
    @endif

    {{-- ══════════ EDIT TAB ══════════ --}}
    @if ($activeTab === 'edit')
        <div class="max-w-5xl mx-auto px-6 py-8 space-y-6">

            {{-- Basic Information --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50
                            flex items-center gap-3">
                    <div class="w-8 h-8 bg-indigo-500 rounded-xl flex items-center justify-center shadow-sm">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 class="text-base font-semibold text-gray-900">Basic Information</h2>
                </div>
                <div class="p-4 sm:p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Heading *</label>
                        <input type="text" wire:model.defer="heading" placeholder="App name or heading"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('heading')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Sub Heading</label>
                        <input type="text" wire:model.defer="sub_heading" placeholder="Tagline or subtitle"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Company Name</label>
                        <input type="text" wire:model.defer="company_name" placeholder="e.g. Super Learnings Private Limited"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('company_name')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Company CIN</label>
                        <input type="text" wire:model.defer="company_cin" placeholder="Corporate Identification Number"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400">
                        @error('company_cin')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Logo</label>
                        @if ($logoPreview)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $logoPreview }}"
                                    class="w-14 h-14 rounded-xl object-contain border border-gray-200">
                                <span class="text-xs text-gray-400">Current logo</span>
                            </div>
                        @endif
                        <input type="file" wire:model="logo" accept="image/*"
                            class="block w-full text-sm text-gray-500
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700
                                   hover:file:bg-indigo-100 transition-colors">
                        @error('logo')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Address</label>
                        <textarea wire:model.defer="address" rows="2" placeholder="Office or contact address"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                   focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400"></textarea>
                    </div>
                </div>
            </div>

            {{-- Content Sections --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-purple-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-purple-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Content Sections</h2>
                            <p class="text-xs text-gray-400">{{ count($content) }} section(s)</p>
                        </div>
                    </div>
                    <button wire:click="addContentSection"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-purple-600 hover:bg-purple-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Section
                    </button>
                </div>
                <div class="p-4 sm:p-6 space-y-4">
                    @foreach ($content as $index => $section)
                        <div class="border border-gray-200 rounded-xl p-4 hover:border-purple-200 transition-colors">
                            <div class="flex items-center justify-between mb-3">
                                <span class="flex items-center gap-2">
                                    <span
                                        class="w-6 h-6 bg-purple-100 text-purple-700 text-xs font-bold
                                                 rounded-full flex items-center justify-center">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">Section {{ $index + 1 }}</span>
                                </span>
                                @if (count($content) > 1)
                                    <button wire:click="removeContentSection({{ $index }})"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Title</label>
                                    <input type="text" wire:model="content.{{ $index }}.title"
                                        placeholder="Section title"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                               focus:ring-2 focus:ring-purple-400 focus:border-purple-400">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                                    <textarea wire:model="content.{{ $index }}.description" rows="3" placeholder="Section description..."
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm
                                               focus:ring-2 focus:ring-purple-400 focus:border-purple-400"></textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Contact Details --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-sky-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">Contact Details</h2>
                    </div>
                    <button wire:click="openContactModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Contact
                    </button>
                </div>
                <div class="p-4 sm:p-6 space-y-2">
                    @forelse ($contact_details as $index => $contact)
                        <div
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-200
                                    hover:border-blue-200 transition-colors">
                            <div class="min-w-0">
                                <span
                                    class="text-xs font-semibold text-gray-500 uppercase">{{ $contact['type'] }}</span>
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $contact['value'] }}</p>
                            </div>
                            <div class="flex items-center gap-1 flex-shrink-0 ml-3">
                                <button wire:click="openContactModal({{ $index }})"
                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors"
                                    title="Edit">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                <button wire:click="removeContact({{ $index }})"
                                    class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                    title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-4">No contacts added yet.</p>
                    @endforelse
                </div>
            </div>

            {{-- Core Team --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-yellow-50 to-amber-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Core Team</h2>
                            <p class="text-xs text-gray-400">{{ count($core_team) }} members</p>
                        </div>
                    </div>
                    <button wire:click="openTeamModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-500 hover:bg-amber-600
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Member
                    </button>
                </div>
                <div class="p-4 sm:p-6">
                    @if (count($core_team))
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4">
                            @foreach ($core_team as $index => $member)
                                <div
                                    class="border border-gray-200 rounded-xl p-4 hover:border-amber-200 transition-colors">
                                    <div class="flex items-center gap-3 mb-3">
                                        @if (!empty($member['image']))
                                            <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}"
                                                class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                        @else
                                            <div
                                                class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-6 h-6 text-amber-500" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="1.5"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">
                                                {{ $member['name'] }}</p>
                                            <p class="text-xs text-gray-500">{{ $member['position'] ?? '' }}</p>
                                        </div>
                                    </div>
                                    @if (!empty($member['description']))
                                        <p class="text-xs text-gray-500 mb-3 line-clamp-2">
                                            {{ $member['description'] }}</p>
                                    @endif
                                    <div class="flex items-center gap-1">
                                        <button wire:click="openTeamModal({{ $index }})"
                                            class="flex-1 flex items-center justify-center gap-1 py-1.5 text-xs font-medium
                                                   text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button wire:click="removeTeamMember({{ $index }})"
                                            class="flex-1 flex items-center justify-center gap-1 py-1.5 text-xs font-medium
                                                   text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            Remove
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 text-center py-4">No team members added yet.</p>
                    @endif
                </div>
            </div>

            {{-- Social Media --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div
                    class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-sky-50
                            flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                            </svg>
                        </div>
                        <h2 class="text-base font-semibold text-gray-900">Social Media</h2>
                    </div>
                    <button wire:click="openSocialModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-blue-600 hover:bg-blue-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4v16m8-8H4" />
                        </svg>
                        Add Social
                    </button>
                </div>
                <div class="p-4 sm:p-6">
                    @if (count($social_media))
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($social_media as $index => $social)
                                <div class="group relative bg-white border border-gray-200 rounded-2xl p-4 hover:border-blue-300 hover:shadow-md transition-all">
                                    <div class="flex items-start gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-gray-50 border border-gray-200 p-2 flex items-center justify-center flex-shrink-0">
                                            @if (!empty($social['icon']))
                                                <img src="{{ $social['icon'] }}" class="w-full h-full object-contain">
                                            @else
                                                <x-social-platform-icon :platform="$social['platform'] ?? ''" class="w-full h-full block" />
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 capitalize truncate">
                                                {{ $social['platform'] ?? 'Link' }}
                                            </p>
                                            <p class="text-xs text-gray-500 truncate" title="{{ $social['url'] ?? '' }}">
                                                {{ $social['url'] ?? '' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center gap-1.5">
                                        @if (!empty($social['url']))
                                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer"
                                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold
                                                       text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-100 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                                </svg>
                                                Open
                                            </a>
                                        @endif
                                        <button wire:click="openSocialModal({{ $index }})"
                                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold
                                                   text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button wire:click="removeSocialMedia({{ $index }})"
                                            class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 border border-red-100 rounded-lg transition-colors"
                                            title="Remove">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 text-center py-6">No social media links added yet.</p>
                    @endif
                </div>
            </div>

            {{-- Documents --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-100 bg-gradient-to-r from-cyan-50 to-blue-50 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-cyan-500 rounded-xl flex items-center justify-center shadow-sm">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-base font-semibold text-gray-900">Documents</h2>
                            <p class="text-xs text-gray-400">{{ count($documents) }} document(s) · Max 2MB per file</p>
                        </div>
                    </div>
                    <button wire:click="openDocumentModal()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-cyan-600 hover:bg-cyan-700
                               text-white text-sm font-semibold rounded-lg transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Document
                    </button>
                </div>
                <div class="p-4 sm:p-6">
                    @if (count($documents))
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
                            @foreach ($documents as $index => $doc)
                                @php
                                    $ext = strtolower($doc['file_type'] ?? '');
                                    $extColor = match(true) {
                                        in_array($ext, ['pdf']) => ['ring' => 'bg-red-50 text-red-600 border-red-100', 'pill' => 'bg-red-100 text-red-700'],
                                        in_array($ext, ['doc', 'docx']) => ['ring' => 'bg-blue-50 text-blue-600 border-blue-100', 'pill' => 'bg-blue-100 text-blue-700'],
                                        default => ['ring' => 'bg-cyan-50 text-cyan-600 border-cyan-100', 'pill' => 'bg-cyan-100 text-cyan-700'],
                                    };
                                @endphp
                                <div class="group bg-white border border-gray-200 rounded-2xl p-4 hover:border-cyan-300 hover:shadow-md transition-all">
                                    <div class="flex items-start gap-3">
                                        <div class="w-12 h-12 rounded-xl {{ $extColor['ring'] }} border flex items-center justify-center flex-shrink-0">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 3v6h6" />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-semibold text-gray-900 truncate" title="{{ $doc['title'] ?? '' }}">
                                                {{ $doc['title'] ?? 'Document' }}
                                            </p>
                                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                                <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold uppercase rounded {{ $extColor['pill'] }}">
                                                    {{ $doc['file_type'] ?? 'FILE' }}
                                                </span>
                                                @if (!empty($doc['file_size']))
                                                    <span class="text-xs text-gray-400">{{ number_format($doc['file_size'] / 1024, 0) }} KB</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mt-3 flex items-center gap-1.5">
                                        @if (!empty($doc['file_path']))
                                            <a href="{{ $doc['file_path'] }}" target="_blank" rel="noopener noreferrer"
                                                class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold
                                                       text-cyan-700 bg-cyan-50 hover:bg-cyan-100 border border-cyan-100 rounded-lg transition-colors">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                View
                                            </a>
                                        @endif
                                        <button wire:click="openDocumentModal({{ $index }})"
                                            class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-1.5 text-xs font-semibold
                                                   text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-100 rounded-lg transition-colors">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button wire:click="removeDocument({{ $index }})"
                                            class="inline-flex items-center justify-center w-8 h-8 text-red-500 hover:bg-red-50 border border-red-100 rounded-lg transition-colors"
                                            title="Delete">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 border-2 border-dashed border-gray-200 rounded-xl">
                            <svg class="w-10 h-10 mx-auto text-gray-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-sm text-gray-400">No documents uploaded.</p>
                            <p class="text-xs text-gray-300 mt-1">PDF, DOC, DOCX up to 2MB.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Save Button --}}
            <div class="flex justify-stretch sm:justify-end pb-6">
                <button wire:click="save"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600
                           hover:from-green-700 hover:to-emerald-700 text-white font-semibold rounded-xl
                           shadow-md hover:shadow-lg transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save All Changes
                </button>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRMS (standardized) ══════════ --}}
    @if ($pendingDeleteContactIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelRemoveContact"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete contact?</h3>
                        <p class="text-sm text-gray-500">Remove this contact detail? This action cannot be undone.</p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelRemoveContact" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeRemoveContact" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingDeleteTeamIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelRemoveTeamMember"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete team member?</h3>
                        <p class="text-sm text-gray-500">
                            @if (isset($core_team[$pendingDeleteTeamIndex]))
                                Remove <strong>{{ $core_team[$pendingDeleteTeamIndex]['name'] ?? 'this member' }}</strong>? Their photo on S3 will also be deleted.
                            @else
                                This action cannot be undone.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelRemoveTeamMember" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeRemoveTeamMember" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    @if ($pendingDeleteSocialIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelRemoveSocialMedia"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete social link?</h3>
                        <p class="text-sm text-gray-500">
                            @if (isset($social_media[$pendingDeleteSocialIndex]))
                                Remove <strong class="capitalize">{{ $social_media[$pendingDeleteSocialIndex]['platform'] ?? 'this link' }}</strong>? This action cannot be undone.
                            @else
                                This action cannot be undone.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelRemoveSocialMedia" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeRemoveSocialMedia" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md">Delete</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ CONTACT SLIDE-IN PANEL ══════════ --}}
    @if ($showContactModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeContactModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editContactIndex !== null ? 'Edit Contact' : 'Add Contact' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Add or update a contact detail</p>
                    </div>
                    <button wire:click="closeContactModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newContact.type" placeholder="e.g. Email, Phone"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('newContact.type')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Value <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newContact.value" placeholder="e.g. info@app.com"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('newContact.value')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-4 sm:px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeContactModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveContact"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">
                        {{ $editContactIndex !== null ? 'Update' : 'Add Contact' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ TEAM SLIDE-IN PANEL ══════════ --}}
    @if ($showTeamModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTeamModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editTeamIndex !== null ? 'Edit Team Member' : 'Add Team Member' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Fill in the member details below</p>
                    </div>
                    <button wire:click="closeTeamModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form Body (scrollable) --}}
                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-5">

                    {{-- Profile Photo — simple file picker --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Profile Photo</label>
                        <input type="file" wire:model="newTeamMemberImage" accept="image/*"
                            class="block w-full text-sm text-gray-600
                                   file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0
                                   file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700
                                   hover:file:bg-amber-100 border border-gray-300 rounded-md">
                        <p class="text-xs text-gray-400 mt-1">
                            JPG / PNG up to 2MB{{ $editTeamIndex !== null ? ' · Leave empty to keep current photo' : '' }}
                        </p>
                        <div wire:loading wire:target="newTeamMemberImage"
                            class="flex items-center gap-1.5 text-xs text-amber-600 mt-1">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Uploading…
                        </div>
                        @error('newTeamMemberImage')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror

                        {{-- Current photo preview (compact) --}}
                        @if ($editTeamIndex !== null && !empty($core_team[$editTeamIndex]['image']) && !$newTeamMemberImage)
                            <div class="mt-2 flex items-center gap-2">
                                <img src="{{ $core_team[$editTeamIndex]['image'] }}"
                                    class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                <span class="text-xs text-gray-400">Current photo</span>
                            </div>
                        @endif
                    </div>

                    {{-- Basic Info --}}
                    <div class="bg-gray-50 rounded-xl p-4 space-y-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Basic Information</p>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Full Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newTeamMember.name" placeholder="e.g. Annant Dagur"
                                class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400
                                       @error('newTeamMember.name') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                            @error('newTeamMember.name')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Position / Role <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="newTeamMember.position" placeholder="e.g. CEO, Lead Developer"
                                class="w-full border rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400
                                       @error('newTeamMember.position') border-red-400 bg-red-50 @else border-gray-300 @enderror">
                            @error('newTeamMember.position')
                                <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            <textarea wire:model="newTeamMember.description" rows="3"
                                placeholder="Brief bio or description..."
                                class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-400 focus:border-amber-400 resize-none"></textarea>
                        </div>
                    </div>

                    {{-- Social / Profile Link --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Profile / Social URL</label>
                        <input type="text" wire:model="newTeamMember.link"
                            placeholder="instagram.com/username  ·  linkedin.com/in/...  ·  https://..."
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                        <p class="text-xs text-gray-400 mt-1">You can paste a full URL or just <code class="text-[11px] bg-gray-50 px-1 py-0.5 rounded">domain.com/path</code> — we'll add <code class="text-[11px] bg-gray-50 px-1 py-0.5 rounded">https://</code> for you.</p>
                        @error('newTeamMember.link')
                            <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-200 bg-white flex-shrink-0 flex items-center gap-3">
                    <button wire:click="saveTeamMember" wire:loading.attr="disabled"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 bg-amber-500 hover:bg-amber-600
                               text-white text-sm font-semibold rounded-xl transition-colors shadow-sm">
                        <span wire:loading.remove wire:target="saveTeamMember">
                            {{ $editTeamIndex !== null ? 'Update Member' : 'Add Member' }}
                        </span>
                        <span wire:loading wire:target="saveTeamMember" class="flex items-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Saving…
                        </span>
                    </button>
                    <button wire:click="closeTeamModal" type="button"
                        class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-xl transition-colors">
                        Cancel
                    </button>
                </div>

            </div>
        </div>
    @endif

    {{-- ══════════ SOCIAL SLIDE-IN PANEL ══════════ --}}
    @if ($showSocialModal)
        @php
            $currentPlatform = strtolower(trim($newSocialMedia['platform'] ?? ''));
            $knownPlatforms = ['facebook', 'twitter', 'x', 'instagram', 'linkedin', 'youtube', 'github', 'whatsapp', 'telegram', 'discord', 'tiktok', 'pinterest', 'snapchat', 'reddit'];
            $hasKnownIcon = in_array($currentPlatform, $knownPlatforms, true);
        @endphp
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSocialModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editSocialIndex !== null ? 'Edit Social Media' : 'Add Social Media' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Any platform · any URL works</p>
                    </div>
                    <button wire:click="closeSocialModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-5">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Platform <span class="text-red-500">*</span></label>
                        <input type="text" list="known-social-platforms" wire:model.live.debounce.300ms="newSocialMedia.platform"
                            placeholder="Type any platform name (Facebook, Behance, Dribbble, etc.)"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm bg-white
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <datalist id="known-social-platforms">
                            @foreach ($knownPlatforms as $p)
                                <option value="{{ ucfirst($p) }}"></option>
                            @endforeach
                        </datalist>
                        <p class="text-xs text-gray-400 mt-1">Suggested platforms have built-in logos. Pick anything you like.</p>
                        @error('newSocialMedia.platform')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    @if ($currentPlatform !== '')
                        <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 flex items-center gap-3">
                            <div class="w-12 h-12 bg-white rounded-lg p-2 flex items-center justify-center shadow-sm">
                                <x-social-platform-icon :platform="$currentPlatform" class="w-full h-full block" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Logo Preview</p>
                                <p class="text-sm text-gray-700 capitalize">
                                    {{ $hasKnownIcon ? ucfirst($currentPlatform) . ' logo will be used.' : 'Custom platform — upload an icon below to override the default.' }}
                                </p>
                            </div>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">URL <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newSocialMedia.url"
                            placeholder="instagram.com/username  ·  https://..."
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm
                                   focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">You can paste a full URL or just <code class="text-[11px] bg-gray-50 px-1 py-0.5 rounded">domain.com/path</code> — we'll add <code class="text-[11px] bg-gray-50 px-1 py-0.5 rounded">https://</code> for you.</p>
                        @error('newSocialMedia.url')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Custom Icon <span class="text-gray-400 font-normal">(optional — overrides auto logo)</span>
                        </label>
                        @if ($editSocialIndex !== null && !empty($social_media[$editSocialIndex]['icon']))
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $social_media[$editSocialIndex]['icon'] }}"
                                    class="w-10 h-10 object-contain rounded border border-gray-200 bg-white p-1">
                                <span class="text-xs text-gray-500">Current custom icon</span>
                            </div>
                        @endif
                        <input type="file" wire:model="newSocialMediaIcon" accept="image/*"
                            class="block w-full text-sm text-gray-500
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                                   hover:file:bg-blue-100 transition-colors border border-gray-300 rounded-md">
                        @error('newSocialMediaIcon')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-4 sm:px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSocialModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSocialMedia"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md">
                        {{ $editSocialIndex !== null ? 'Update' : 'Add Social Media' }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DOCUMENT SLIDE-IN PANEL ══════════ --}}
    @if ($showDocumentModal)
        <div class="fixed inset-0 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDocumentModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Panel Header --}}
                <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ $editDocumentIndex !== null ? 'Edit Document' : 'Add Document' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Upload PDF/DOC/DOCX up to 2MB. Files stored on S3.</p>
                    </div>
                    <button wire:click="closeDocumentModal"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 space-y-5">

                    @if ($editDocumentIndex !== null && !empty($documents[$editDocumentIndex]['file_path']))
                        <div class="bg-cyan-50 border border-cyan-100 rounded-lg px-4 py-3 flex items-center gap-3">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-cyan-700 uppercase tracking-wide">Current File</p>
                                <p class="text-sm text-gray-700 truncate">
                                    {{ strtoupper($documents[$editDocumentIndex]['file_type'] ?? 'FILE') }}
                                    @if (!empty($documents[$editDocumentIndex]['file_size']))
                                        · {{ number_format($documents[$editDocumentIndex]['file_size'] / 1024, 0) }} KB
                                    @endif
                                </p>
                            </div>
                            <a href="{{ $documents[$editDocumentIndex]['file_path'] }}" target="_blank"
                                class="text-xs text-cyan-700 font-semibold hover:underline">View</a>
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newDocument.title" placeholder="e.g. Company Brochure"
                            class="w-full border border-gray-300 rounded-md px-3.5 py-2.5 text-sm
                                   focus:ring-1 focus:ring-cyan-500 focus:border-cyan-500">
                        @error('newDocument.title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            File <span class="text-red-500">*</span>
                            <span class="text-gray-400 font-normal">(PDF, DOC, DOCX — max 2MB)</span>
                        </label>
                        <input type="file" wire:model="newDocumentFile" accept=".pdf,.doc,.docx"
                            class="block w-full text-sm text-gray-500
                                   file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0
                                   file:text-sm file:font-semibold file:bg-cyan-50 file:text-cyan-700
                                   hover:file:bg-cyan-100 transition-colors border border-gray-300 rounded-md">
                        <div wire:loading wire:target="newDocumentFile" class="flex items-center gap-1.5 text-xs text-cyan-600 mt-1.5">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Uploading…
                        </div>
                        @error('newDocumentFile')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="px-4 sm:px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDocumentModal" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDocument" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove wire:target="saveDocument">{{ $editDocumentIndex !== null ? 'Update' : 'Add Document' }}</span>
                        <span wire:loading wire:target="saveDocument">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE DOCUMENT CONFIRM ══════════ --}}
    @if ($pendingDeleteDocumentIndex !== null)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-[1.5px]" wire:click="cancelRemoveDocument"></div>
            <div class="relative bg-white rounded-xl shadow-2xl w-full max-w-sm p-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-base font-semibold text-gray-900 mb-1">Delete document?</h3>
                        <p class="text-sm text-gray-500">
                            @if (isset($documents[$pendingDeleteDocumentIndex]))
                                Remove <strong>"{{ $documents[$pendingDeleteDocumentIndex]['title'] ?? 'this document' }}"</strong>? The S3 file will also be deleted.
                            @else
                                This action cannot be undone.
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button wire:click="cancelRemoveDocument" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="executeRemoveDocument" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md disabled:opacity-60 flex items-center gap-1.5">
                        <span wire:loading.remove>Delete</span>
                        <span wire:loading>Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
