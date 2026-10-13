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
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                            <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $aboutApp->heading ?? 'About App' }}</h1>
                            @if ($aboutApp->company_name || $aboutApp->company_cin)
                                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">
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
                <div class="divide-y divide-gray-200">

                    {{-- Content Sections --}}
                    @php $shown = 0; @endphp
                    @foreach ($aboutApp->content ?? [] as $i => $section)
                        @if (!empty($section['title']) || !empty($section['description']))
                            @php $shown++; @endphp
                            <section class="py-5 first:pt-0">
                                @if (!empty($section['title']))
                                    <h2 class="text-base font-semibold text-black break-words">
                                        <span class="tabular-nums mr-1">{{ $shown }}.</span>{{ $section['title'] }}
                                    </h2>
                                @endif
                                @if (!empty($section['description']))
                                    <p class="text-sm text-black leading-relaxed break-words {{ !empty($section['title']) ? 'mt-2' : '' }}">
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
                                        <span class="w-24 sm:w-32 flex-shrink-0 text-xs text-gray-400 uppercase tracking-wider pt-0.5">{{ $contact['type'] ?? '' }}</span>
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
                            <p class="mt-2 text-sm text-black leading-relaxed break-words">{{ $aboutApp->address }}</p>
                        </section>
                    @endif

                    {{-- Core Team --}}
                    @if (!empty($aboutApp->core_team))
                        <section class="py-5 first:pt-0">
                            <h2 class="text-base font-semibold text-black">
                                Our Core Team <span class="ml-1 text-xs font-normal text-gray-400">{{ count($aboutApp->core_team) }} members</span>
                            </h2>
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-3">
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
                            <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4 gap-3">
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
         Plain and minimal, in the view's look, across the whole page: a header strip with Cancel / Save,
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
        {{-- Header strip: the title with Cancel / Save, the same height as
             every Super Admin header; the parts below run across the page. --}}
        <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                    <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">{{ $aboutApp ? 'Edit' : 'Create' }} About App</h1>
                    <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">Everything on this page is saved together with Save</p>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button type="button" wire:click="setTab('view')"
                        class="flex-1 sm:flex-none px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save,logo"
                        class="flex-1 sm:flex-none px-5 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 rounded-md disabled:opacity-60">
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="px-4 sm:px-6">

            <div class="divide-y divide-gray-200">

                {{-- Basic Information --}}
                <section class="py-6">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">Basic Information</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-3">
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

    {{-- ══════════ ADD / EDIT SLIDE-INS ══════════
         Contact, team member, social media and document — each in the Students
         form's look: a wide panel, plain labels over bordered boxes, a flat
         two-column grid, a photo / file row, Cancel and a dark Save in the
         footer. Same fields, bindings and saves as before. --}}
    @php
        $aaIn = 'w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500';
        $aaLb = 'block text-sm font-medium text-gray-700 mb-1.5';
        $aaNote = 'mt-1 text-xs text-gray-400';
        $aaErr = 'mt-1 text-xs text-red-500';
        $aaFile = 'flex-1 min-w-0 text-sm';
        $aaClose = '<svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>';
        // A just-picked file's preview, or nothing if it cannot be previewed.
        $aaPreview = function ($file) {
            try {
                return $file && method_exists($file, 'temporaryUrl') ? $file->temporaryUrl() : null;
            } catch (\Throwable $e) {
                return null;
            }
        };
    @endphp

    {{-- ══════════ CONTACT SLIDE-IN PANEL ══════════ --}}
    @if ($showContactModal)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeContactModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editContactIndex !== null ? 'Edit Contact' : 'Add Contact' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editContactIndex !== null ? 'Update the contact detail below' : 'Fill in the contact detail below' }}</p>
                    </div>
                    <button wire:click="closeContactModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">{!! $aaClose !!}</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $aaLb }}">Type <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="newContact.type" placeholder="e.g. Email, Phone"
                                    class="{{ $aaIn }} @error('newContact.type') border-red-400 @enderror">
                                @error('newContact.type')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aaLb }}">Value <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="newContact.value" placeholder="e.g. info@app.com"
                                    class="{{ $aaIn }} @error('newContact.value') border-red-400 @enderror">
                                @error('newContact.value')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeContactModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveContact" type="button" wire:loading.attr="disabled" wire:target="saveContact"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveContact">{{ $editContactIndex !== null ? 'Update Contact' : 'Add Contact' }}</span>
                        <span wire:loading wire:target="saveContact">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ TEAM SLIDE-IN PANEL ══════════ --}}
    @if ($showTeamModal)
        @php
            $teamNew = $aaPreview($newTeamMemberImage);
            $teamOld = $editTeamIndex !== null ? ($core_team[$editTeamIndex]['image'] ?? null) : null;
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeTeamModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editTeamIndex !== null ? 'Edit Team Member' : 'New Team Member' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $editTeamIndex !== null ? 'Update the member details below' : 'Fill in the member details below' }}</p>
                    </div>
                    <button wire:click="closeTeamModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">{!! $aaClose !!}</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                        {{-- Photo (single inline row, as on Students) --}}
                        <div>
                            <label class="{{ $aaLb }}">
                                Profile Photo <span class="text-gray-400 font-normal">(Optional, max 2 MB{{ $editTeamIndex !== null ? ' · leave empty to keep the current one' : '' }})</span>
                            </label>
                            <div class="flex items-center gap-3">
                                @if ($teamNew || $teamOld)
                                    <img src="{{ $teamNew ?: $teamOld }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0" alt="">
                                @else
                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                    </div>
                                @endif
                                <input type="file" wire:model="newTeamMemberImage" accept="image/*" class="{{ $aaFile }}">
                            </div>
                            <div wire:loading wire:target="newTeamMemberImage" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('newTeamMemberImage')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $aaLb }}">Full Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="newTeamMember.name" placeholder="e.g. Annant Dagur"
                                    class="{{ $aaIn }} @error('newTeamMember.name') border-red-400 @enderror">
                                @error('newTeamMember.name')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aaLb }}">Position / Role <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="newTeamMember.position" placeholder="e.g. CEO, Lead Developer"
                                    class="{{ $aaIn }} @error('newTeamMember.position') border-red-400 @enderror">
                                @error('newTeamMember.position')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="{{ $aaLb }}">Profile / Social URL</label>
                                <input type="text" wire:model="newTeamMember.link" placeholder="instagram.com/username · linkedin.com/in/... · https://..."
                                    class="{{ $aaIn }} @error('newTeamMember.link') border-red-400 @enderror">
                                <p class="{{ $aaNote }}">A full URL or just domain.com/path — https:// is added for you.</p>
                                @error('newTeamMember.link')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <label class="{{ $aaLb }}">Description</label>
                            <textarea wire:model="newTeamMember.description" rows="3" placeholder="Brief bio or description"
                                class="{{ $aaIn }} resize-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeTeamModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveTeamMember" type="button" wire:loading.attr="disabled" wire:target="saveTeamMember, newTeamMemberImage"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveTeamMember">{{ $editTeamIndex !== null ? 'Update Member' : 'Add Member' }}</span>
                        <span wire:loading wire:target="saveTeamMember">Saving...</span>
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
            $iconNew = $aaPreview($newSocialMediaIcon);
            $iconOld = $editSocialIndex !== null ? ($social_media[$editSocialIndex]['icon'] ?? null) : null;
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeSocialModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editSocialIndex !== null ? 'Edit Social Media' : 'Add Social Media' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Any platform, any URL</p>
                    </div>
                    <button wire:click="closeSocialModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">{!! $aaClose !!}</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                        {{-- Icon (single inline row, as the Students photo): the
                             custom icon if one is picked or saved, else the
                             platform's own logo. --}}
                        <div>
                            <label class="{{ $aaLb }}">
                                Custom Icon <span class="text-gray-400 font-normal">(Optional, max 1 MB — overrides the platform's logo)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-full border border-gray-200 bg-white p-2.5 flex items-center justify-center flex-shrink-0 overflow-hidden">
                                    @if ($iconNew || $iconOld)
                                        <img src="{{ $iconNew ?: $iconOld }}" class="w-full h-full object-contain" alt="">
                                    @elseif ($currentPlatform !== '')
                                        <x-social-platform-icon :platform="$currentPlatform" class="w-full h-full block" />
                                    @else
                                        <svg class="w-6 h-6 text-gray-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                    @endif
                                </div>
                                <input type="file" wire:model="newSocialMediaIcon" accept="image/*" class="{{ $aaFile }}">
                            </div>
                            <div wire:loading wire:target="newSocialMediaIcon" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('newSocialMediaIcon')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="{{ $aaLb }}">Platform <span class="text-red-500">*</span></label>
                                <input type="text" list="known-social-platforms" wire:model.live.debounce.300ms="newSocialMedia.platform"
                                    placeholder="Facebook, Behance, Dribbble…"
                                    class="{{ $aaIn }} @error('newSocialMedia.platform') border-red-400 @enderror">
                                <datalist id="known-social-platforms">
                                    @foreach ($knownPlatforms as $p)
                                        <option value="{{ ucfirst($p) }}"></option>
                                    @endforeach
                                </datalist>
                                <p class="{{ $aaNote }}">
                                    @if ($currentPlatform === '')
                                        Suggested platforms come with their own logo.
                                    @elseif ($hasKnownIcon)
                                        {{ ucfirst($currentPlatform) }}'s logo will be used.
                                    @else
                                        A custom platform — pick an icon above to show its logo.
                                    @endif
                                </p>
                                @error('newSocialMedia.platform')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="{{ $aaLb }}">URL <span class="text-red-500">*</span></label>
                                <input type="text" wire:model.defer="newSocialMedia.url" placeholder="instagram.com/username · https://..."
                                    class="{{ $aaIn }} @error('newSocialMedia.url') border-red-400 @enderror">
                                <p class="{{ $aaNote }}">A full URL or just domain.com/path — https:// is added for you.</p>
                                @error('newSocialMedia.url')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeSocialModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveSocialMedia" type="button" wire:loading.attr="disabled" wire:target="saveSocialMedia, newSocialMediaIcon"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveSocialMedia">{{ $editSocialIndex !== null ? 'Update Social Media' : 'Add Social Media' }}</span>
                        <span wire:loading wire:target="saveSocialMedia">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════ DOCUMENT SLIDE-IN PANEL ══════════ --}}
    @if ($showDocumentModal)
        @php
            $docOld = $editDocumentIndex !== null ? ($documents[$editDocumentIndex] ?? null) : null;
            $docKind = strtoupper((string) ($docOld['file_type'] ?? 'file'));
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDocumentModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-3xl bg-white shadow-2xl flex flex-col">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900">{{ $editDocumentIndex !== null ? 'Edit Document' : 'Add Document' }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5">PDF, DOC or DOCX, up to 2 MB</p>
                    </div>
                    <button wire:click="closeDocumentModal" type="button"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">{!! $aaClose !!}</button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="px-6 py-6 space-y-5">

                        <div>
                            <label class="{{ $aaLb }}">Title <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="newDocument.title" placeholder="e.g. Company Brochure"
                                class="{{ $aaIn }} @error('newDocument.title') border-red-400 @enderror">
                            @error('newDocument.title')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                        </div>

                        {{-- File (single inline row, as the Students photo): the
                             saved file's kind and size beside the picker. --}}
                        <div>
                            <label class="{{ $aaLb }}">
                                File @if ($editDocumentIndex === null)<span class="text-red-500">*</span>@endif
                                <span class="text-gray-400 font-normal">(PDF, DOC, DOCX — max 2 MB{{ $docOld ? ' · leave empty to keep the current one' : '' }})</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-lg bg-gray-100 text-gray-500 flex flex-col items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="text-[9px] font-bold leading-none mt-0.5">{{ $docOld ? $docKind : 'DOC' }}</span>
                                </div>
                                <input type="file" wire:model="newDocumentFile" accept=".pdf,.doc,.docx" class="{{ $aaFile }}">
                            </div>
                            @if ($docOld && !empty($docOld['file_path']))
                                <p class="{{ $aaNote }}">
                                    Current file: {{ $docKind }}@if (!empty($docOld['file_size'])) · {{ number_format($docOld['file_size'] / 1024, 0) }} KB @endif ·
                                    <a href="{{ $docOld['file_path'] }}" target="_blank" class="text-blue-600 hover:underline">View ↗</a>
                                </p>
                            @endif
                            <div wire:loading wire:target="newDocumentFile" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('newDocumentFile')<p class="{{ $aaErr }}">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDocumentModal" type="button" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDocument" type="button" wire:loading.attr="disabled" wire:target="saveDocument, newDocumentFile"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveDocument">{{ $editDocumentIndex !== null ? 'Update Document' : 'Add Document' }}</span>
                        <span wire:loading wire:target="saveDocument">Saving...</span>
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
