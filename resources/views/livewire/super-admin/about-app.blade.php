<div class="min-h-screen bg-white">

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
            <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="{{ $aboutApp->logo ?: asset('website-image/Group 11525.png') }}"
                            alt="App Logo"
                            class="w-12 h-12 rounded-xl object-contain border border-gray-200 shadow-sm bg-white p-1 flex-shrink-0"
                            onerror="this.src='{{ asset('website-image/Group 11525.png') }}'">
                        <div class="min-w-0">
                            <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $aboutApp->heading ?? 'About App' }}</h1>
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

            {{-- As the Terms & Conditions view: a plain document out of its cards —
                 "1. Heading", the text under it, a hairline between one section
                 and the next. Contact, address, team, social links and documents
                 follow as plain sections; the team, links and files sit in simple
                 bordered boxes (no colours, shadows or lift). --}}
            <div class="px-4 sm:px-6 py-6">
                <div class="max-w-4xl divide-y divide-gray-200">

                    {{-- Content Sections --}}
                    @php $shown = 0; @endphp
                    @foreach ($aboutApp->content ?? [] as $i => $section)
                        @if (!empty($section['title']) || !empty($section['description']))
                            @php $shown++; @endphp
                            <section class="py-5 first:pt-0">
                                @if (!empty($section['title']))
                                    <h2 class="text-base font-semibold text-black">
                                        <span class="tabular-nums mr-1">{{ $shown }}.</span>{{ $section['title'] }}
                                    </h2>
                                @endif
                                @if (!empty($section['description']))
                                    <p class="text-sm text-black leading-relaxed {{ !empty($section['title']) ? 'mt-2' : '' }}">
                                        {!! nl2br(e($section['description'])) !!}
                                    </p>
                                @endif
                            </section>
                        @endif
                    @endforeach

                    {{-- Contact Details --}}
                    @if (!empty($aboutApp->contact_details))
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">Contact Details</h2>
                            <div class="mt-3 space-y-2">
                                @foreach ($aboutApp->contact_details as $contact)
                                    <div class="flex gap-3 text-sm">
                                        <span class="w-32 flex-shrink-0 text-xs text-gray-400 uppercase tracking-wider pt-0.5">{{ $contact['type'] ?? '' }}</span>
                                        <span class="min-w-0 text-black break-words">{{ $contact['value'] ?? '' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Address --}}
                    @if ($aboutApp->address)
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">Address</h2>
                            <p class="mt-2 text-sm text-black leading-relaxed">{{ $aboutApp->address }}</p>
                        </section>
                    @endif

                    {{-- Core Team --}}
                    @if (!empty($aboutApp->core_team))
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">
                                Our Core Team <span class="ml-1 text-xs font-normal text-gray-400">{{ count($aboutApp->core_team) }} members</span>
                            </h2>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($aboutApp->core_team as $member)
                                    @php $memberUrl = $member['link'] ?? null; @endphp
                                    <div class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg">
                                        @if (!empty($member['image']))
                                            <img src="{{ $member['image'] }}" alt="{{ $member['name'] ?? '' }}"
                                                class="w-12 h-12 rounded-full object-cover flex-shrink-0">
                                        @else
                                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-black truncate">{{ $member['name'] ?? '' }}</p>
                                            @if (!empty($member['position']))
                                                <p class="text-xs text-gray-500 truncate">{{ $member['position'] }}</p>
                                            @endif
                                            @if (!empty($member['description']))
                                                <p class="mt-1 text-xs text-gray-500 leading-relaxed line-clamp-2">{{ $member['description'] }}</p>
                                            @endif
                                            @if ($memberUrl)
                                                <a href="{{ $memberUrl }}" target="_blank" rel="noopener noreferrer"
                                                    class="mt-1 inline-block text-xs font-medium text-blue-600 hover:underline">View Profile ↗</a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Social Media --}}
                    @if (!empty($aboutApp->social_media))
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">Follow Us On Social Media</h2>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($aboutApp->social_media as $social)
                                    <a href="{{ $social['url'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center gap-2 px-3 py-2 border border-gray-200 rounded-lg text-sm text-black hover:bg-gray-50 transition-colors">
                                        <span class="w-5 h-5 flex items-center justify-center flex-shrink-0">
                                            @if (!empty($social['icon']))
                                                <img src="{{ $social['icon'] }}" class="w-full h-full object-contain">
                                            @else
                                                <x-social-platform-icon :platform="$social['platform'] ?? ''" class="w-full h-full block" />
                                            @endif
                                        </span>
                                        <span class="capitalize">{{ $social['platform'] ?? '' }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    {{-- Documents --}}
                    @if (!empty($aboutApp->documents))
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">
                                Documents <span class="ml-1 text-xs font-normal text-gray-400">{{ count($aboutApp->documents) }} file(s)</span>
                            </h2>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($aboutApp->documents as $doc)
                                    @php
                                        $ext = strtolower($doc['file_type'] ?? '');
                                        $extTile = match (true) {
                                            $ext === 'pdf'                 => 'bg-red-50 text-red-600',
                                            in_array($ext, ['doc', 'docx']) => 'bg-blue-50 text-blue-600',
                                            default                        => 'bg-gray-100 text-gray-600',
                                        };
                                    @endphp
                                    <a href="{{ $doc['file_path'] ?? '#' }}" target="_blank" rel="noopener noreferrer"
                                        class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                                        <span class="w-10 h-10 rounded-lg {{ $extTile }} flex flex-col items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-[9px] font-bold leading-none mt-0.5 uppercase">{{ $doc['file_type'] ?? 'FILE' }}</span>
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block text-sm font-semibold text-black truncate">{{ $doc['title'] ?? 'Document' }}</span>
                                            @if (!empty($doc['file_size']))
                                                <span class="block text-xs text-gray-400">{{ number_format($doc['file_size'] / 1024, 0) }} KB</span>
                                            @endif
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                </div>
            </div>
        @endif
    @endif

    {{-- ══════════ EDIT TAB ══════════
         Plain and minimal, in the view's look: a title row with Cancel / Save,
         then each part under its own heading with hairlines between — the
         fields as the panel's forms, lists in simple bordered boxes with the
         Students list's action icons. Same fields, slide-ins, removes (with
         their confirms) and save as before. --}}
    @if ($activeTab === 'edit')
        @php
            $aIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
            $aLb = 'block text-sm font-medium text-gray-700 mb-1.5';
            $aAdd = 'inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-800';
            $aEdit = 'p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors';
            $aDel = 'p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors';
            $aView = 'p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors';
            $plus = '<svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>';
            $penIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>';
            $binIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>';
            $eyeIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>';
        @endphp
        <div class="px-4 sm:px-6 py-6">
            <div class="max-w-4xl">

                {{-- Title row --}}
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-5 border-b border-gray-200">
                    <div class="min-w-0">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $aboutApp ? 'Edit' : 'Create' }} About App</h1>
                        <p class="text-xs text-gray-400 mt-0.5">Everything on this page is saved together with Save</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button type="button" wire:click="setTab('view')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,logo"
                            class="px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-200">

                    {{-- Basic Information --}}
                    <section class="py-6">
                        <h2 class="text-base font-semibold text-gray-900 mb-4">Basic Information</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $aLb }}">Heading <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="heading" placeholder="App name or heading"
                                    class="{{ $aIn }} @error('heading') border-red-400 @enderror">
                                @error('heading')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aLb }}">Sub Heading</label>
                                <input type="text" wire:model.defer="sub_heading" placeholder="Tagline or subtitle" class="{{ $aIn }}">
                            </div>
                            <div>
                                <label class="{{ $aLb }}">Company Name</label>
                                <input type="text" wire:model.defer="company_name" placeholder="e.g. Super Learnings Private Limited"
                                    class="{{ $aIn }} @error('company_name') border-red-400 @enderror">
                                @error('company_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aLb }}">Company CIN</label>
                                <input type="text" wire:model.defer="company_cin" placeholder="Corporate Identification Number"
                                    class="{{ $aIn }} @error('company_cin') border-red-400 @enderror">
                                @error('company_cin')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aLb }}">Logo</label>
                                <div class="flex items-center gap-3">
                                    @if ($logoPreview)
                                        <img src="{{ $logoPreview }}" class="w-12 h-12 rounded-md object-contain border border-gray-200 flex-shrink-0">
                                    @endif
                                    <input type="file" wire:model="logo" accept="image/*" class="flex-1 min-w-0 text-sm">
                                </div>
                                <div wire:loading wire:target="logo" class="text-xs text-blue-600 mt-1">Uploading…</div>
                                @error('logo')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aLb }}">Address</label>
                                <textarea wire:model.defer="address" rows="2" placeholder="Office or contact address" class="{{ $aIn }} resize-none"></textarea>
                            </div>
                        </div>
                    </section>

                    {{-- Content Sections --}}
                    <section class="py-6">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h2 class="text-base font-semibold text-gray-900">Content Sections</h2>
                            <span class="text-xs text-gray-400">{{ count($content) }} {{ count($content) === 1 ? 'section' : 'sections' }}</span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach ($content as $index => $section)
                                <div class="py-4 flex items-start gap-3" wire:key="about-content-{{ $index }}">
                                    <span class="w-6 pt-2.5 text-sm font-semibold text-gray-400 tabular-nums flex-shrink-0">{{ $index + 1 }}.</span>
                                    <div class="flex-1 min-w-0 space-y-2">
                                        <input type="text" wire:model="content.{{ $index }}.title" placeholder="Section title" aria-label="Title {{ $index + 1 }}"
                                            class="{{ $aIn }} font-medium text-gray-900">
                                        <textarea wire:model="content.{{ $index }}.description" rows="3" placeholder="Write this section…" aria-label="Text {{ $index + 1 }}"
                                            class="{{ $aIn }} leading-relaxed resize-y"></textarea>
                                    </div>
                                    @if (count($content) > 1)
                                        <button type="button" wire:click="removeContentSection({{ $index }})" title="Delete section"
                                            class="mt-1.5 p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors flex-shrink-0">{!! $binIcon !!}</button>
                                    @else
                                        <span class="w-7 flex-shrink-0"></span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <button type="button" wire:click="addContentSection" class="{{ $aAdd }} mt-2">{!! $plus !!} Add section</button>
                    </section>

                    {{-- Contact Details --}}
                    <section class="py-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-base font-semibold text-gray-900">Contact Details</h2>
                            <button type="button" wire:click="openContactModal()" class="{{ $aAdd }}">{!! $plus !!} Add contact</button>
                        </div>
                        @if (count($contact_details))
                            <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                                @foreach ($contact_details as $index => $contact)
                                    <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                        <div class="min-w-0 grid grid-cols-3 gap-3 flex-1 text-sm">
                                            <span class="text-xs text-gray-400 uppercase tracking-wider pt-0.5">{{ $contact['type'] }}</span>
                                            <span class="col-span-2 text-gray-800 font-medium truncate">{{ $contact['value'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button type="button" wire:click="openContactModal({{ $index }})" title="Edit" class="{{ $aEdit }}">{!! $penIcon !!}</button>
                                            <button type="button" wire:click="removeContact({{ $index }})" title="Delete" class="{{ $aDel }}">{!! $binIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400">No contacts added yet.</p>
                        @endif
                    </section>

                    {{-- Core Team --}}
                    <section class="py-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-base font-semibold text-gray-900">Core Team</h2>
                            <button type="button" wire:click="openTeamModal()" class="{{ $aAdd }}">{!! $plus !!} Add member</button>
                        </div>
                        @if (count($core_team))
                            <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                                @foreach ($core_team as $index => $member)
                                    <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if (!empty($member['image']))
                                                <img src="{{ $member['image'] }}" alt="{{ $member['name'] }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0">
                                            @endif
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 truncate">{{ $member['name'] }}</p>
                                                <p class="text-xs text-gray-400 truncate">
                                                    {{ $member['position'] ?? '' }}@if (!empty($member['position']) && !empty($member['description'])) · @endif{{ $member['description'] ?? '' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            <button type="button" wire:click="openTeamModal({{ $index }})" title="Edit" class="{{ $aEdit }}">{!! $penIcon !!}</button>
                                            <button type="button" wire:click="removeTeamMember({{ $index }})" title="Delete" class="{{ $aDel }}">{!! $binIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400">No team members added yet.</p>
                        @endif
                    </section>

                    {{-- Social Media --}}
                    <section class="py-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="text-base font-semibold text-gray-900">Social Media</h2>
                            <button type="button" wire:click="openSocialModal()" class="{{ $aAdd }}">{!! $plus !!} Add link</button>
                        </div>
                        @if (count($social_media))
                            <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                                @foreach ($social_media as $index => $social)
                                    <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-7 h-7 flex items-center justify-center flex-shrink-0">
                                                @if (!empty($social['icon']))
                                                    <img src="{{ $social['icon'] }}" class="w-full h-full object-contain">
                                                @else
                                                    <x-social-platform-icon :platform="$social['platform'] ?? ''" class="w-full h-full block" />
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-900 capitalize truncate">{{ $social['platform'] ?? 'Link' }}</p>
                                                <p class="text-xs text-gray-400 truncate" title="{{ $social['url'] ?? '' }}">{{ $social['url'] ?? '' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            @if (!empty($social['url']))
                                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener noreferrer" title="Open" class="{{ $aView }}">{!! $eyeIcon !!}</a>
                                            @endif
                                            <button type="button" wire:click="openSocialModal({{ $index }})" title="Edit" class="{{ $aEdit }}">{!! $penIcon !!}</button>
                                            <button type="button" wire:click="removeSocialMedia({{ $index }})" title="Delete" class="{{ $aDel }}">{!! $binIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400">No social media links added yet.</p>
                        @endif
                    </section>

                    {{-- Documents --}}
                    <section class="py-6">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="min-w-0">
                                <h2 class="text-base font-semibold text-gray-900">Documents</h2>
                                <p class="text-xs text-gray-400 mt-0.5">PDF, DOC or DOCX · up to 2 MB each</p>
                            </div>
                            <button type="button" wire:click="openDocumentModal()" class="{{ $aAdd }} flex-shrink-0">{!! $plus !!} Add document</button>
                        </div>
                        @if (count($documents))
                            <div class="border border-gray-200 rounded-md divide-y divide-gray-100">
                                @foreach ($documents as $index => $doc)
                                    <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 truncate" title="{{ $doc['title'] ?? '' }}">{{ $doc['title'] ?? 'Document' }}</p>
                                            <p class="text-xs text-gray-400">
                                                {{ strtoupper($doc['file_type'] ?? 'FILE') }}@if (!empty($doc['file_size'])) · {{ number_format($doc['file_size'] / 1024, 0) }} KB @endif
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0">
                                            @if (!empty($doc['file_path']))
                                                <a href="{{ $doc['file_path'] }}" target="_blank" rel="noopener noreferrer" title="View" class="{{ $aView }}">{!! $eyeIcon !!}</a>
                                            @endif
                                            <button type="button" wire:click="openDocumentModal({{ $index }})" title="Edit" class="{{ $aEdit }}">{!! $penIcon !!}</button>
                                            <button type="button" wire:click="removeDocument({{ $index }})" title="Delete" class="{{ $aDel }}">{!! $binIcon !!}</button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400">No documents uploaded.</p>
                        @endif
                    </section>
                </div>

                {{-- Save --}}
                <div class="pt-4 border-t border-gray-200 flex justify-end pb-6">
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,logo"
                        class="w-full sm:w-auto px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DELETE CONFIRMS (standardized) ══════════ --}}
    @if ($pendingDeleteContactIndex !== null)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
