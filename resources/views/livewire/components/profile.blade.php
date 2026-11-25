{{-- School Admin → Profile in the Super Admin's My Profile look (user's ask of
     2026-11-25): Home's skin and header row, a chip for each part under it —
     School Profile and School Info — every card a plain white one with an
     icon title, figures split by hairlines and Home's teal. What each part
     holds, the edit form and the slide-in panels work as before. --}}
@php
    $me     = auth()->user();
    $isSub  = $me->role === 'sub-admin';
    $org    = $organization;
    $school = $org->name ?? 'School';
    $mobile = $schoolMobileNo ?: ($org->mobile_number ?? null);
    $email  = $schoolEmail ?: ($org->email ?? null);
    $site   = $websiteUrl ?: null;
    $place  = $schoolAddress ?: ($org->address ?? null);
    $lastUpdated = $schoolInfo && $schoolInfo->updated_at ? $schoolInfo->updated_at->format('d M Y, h:i A') : null;
    $pfTabs = [
        'profile' => ['School Profile', 'building-office-2'],
        'info'    => ['School Info', 'information-circle'],
    ];
    $figures = $figures ?? ['students' => 0, 'teachers' => 0, 'classes' => 0];
@endphp
<div class="sa-skin sa-home min-h-screen">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')
    @include('livewire.super-admin.partials.profile-styles')

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>Profile</h1>
            <p>{{ $school }}@if ($email) · {{ $email }}@endif</p>
        </div>
        <div class="sk-right">
            @if ($lastUpdated)
                <span class="sk-pill" title="School Info last saved"><x-icon name="clock" class="w-3.5 h-3.5" /> Updated · {{ $lastUpdated }}</span>
            @endif
            <span class="sk-pill" style="color:#0f766e;background:#effaf7;border-color:#d8f3ec"><x-icon name="shield-check" class="w-3.5 h-3.5" /> {{ $isSub ? 'Sub-admin' : 'School Admin' }}</span>
        </div>
    </div>

    <nav class="an-chips" aria-label="Profile sections">
        @foreach ($pfTabs as $key => [$label, $icon])
            <button type="button" wire:key="pf-chip-{{ $key }}" wire:click="showTab('{{ $key }}')"
                class="an-chip {{ $activeTab === $key ? 'on' : '' }}" aria-pressed="{{ $activeTab === $key ? 'true' : 'false' }}">
                <x-icon name="{{ $icon }}" /> {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="p-4 sm:p-6 pt-4 sm:pt-5 space-y-5">

    {{-- ══════════════════════════════════════════════════
         A SUB-ADMIN'S OWN CARD (only for scoped sub-admins)
    ══════════════════════════════════════════════════ --}}
    @if ($isSub && $infoMode === 'view')
        @php
            $adminCatalog = collect(config('menu.admin', []))->mapWithKeys(fn ($i) => [$i['link'] => $i['title']]);
            $myAccess = collect((array) $me->permissions)->map(fn ($p) => $adminCatalog[$p] ?? $p)->all();
            $meRows = [
                ['device-phone-mobile', 'Mobile', $me->mobile_number ?: '—'],
                ['phone', 'Alt. mobile', $me->alternative_mobile ?: '—'],
                ['user', 'Gender', $me->gender ? ucfirst((string) $me->gender) : '—'],
                ['cake', 'Date of birth', $me->dob ? \Carbon\Carbon::parse($me->dob)->format('d M Y') : '—'],
                ['calendar-days', 'Joined', $me->date_of_joining ? \Carbon\Carbon::parse($me->date_of_joining)->format('d M Y') : '—'],
            ];
        @endphp
        <div class="sa-card p-5 sm:p-6 min-w-0">
            <div class="flex flex-wrap items-center gap-4 min-w-0">
                <div class="w-16 h-16 rounded-2xl overflow-hidden flex items-center justify-center flex-shrink-0" style="background:#effaf7">
                    @if ($me->image)
                        <img src="{{ $me->image }}" class="w-full h-full object-cover" alt="">
                    @else
                        <span class="text-2xl font-semibold" style="color:#0d9488">{{ strtoupper(mb_substr($me->name ?? 'U', 0, 1)) }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h2 class="sk-display text-lg font-bold leading-tight truncate">{{ $me->name }}</h2>
                    <p class="text-sm text-gray-500 truncate mt-0.5">{{ $me->email }}</p>
                    <span class="pf-chip pf-teal mt-2"><x-icon name="shield-check" /> Sub-admin</span>
                </div>
                <button type="button" wire:click="openPasswordPanel" class="pf-ghost"><x-icon name="lock-closed" class="w-4 h-4" /> Change Password</button>
            </div>
            <dl class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-x-6 gap-y-3 text-sm">
                @foreach ($meRows as [$icon, $label, $value])
                    <div class="flex items-center justify-between xl:block gap-3 min-w-0">
                        <dt class="flex items-center gap-2 text-gray-500 whitespace-nowrap"><x-icon name="{{ $icon }}" class="w-4 h-4 text-gray-400" /> {{ $label }}</dt>
                        <dd class="font-semibold text-gray-900 truncate xl:mt-1">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
            <div class="mt-5 pt-5 border-t border-gray-100">
                <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400 mb-2">Your access</p>
                @if (!empty($myAccess))
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($myAccess as $perm)
                            <span class="pf-chip pf-teal"><x-icon name="check" /> {{ $perm }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400">No functionalities granted.</p>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         SCHOOL PROFILE — the school (logo, name, figures, contact) beside
         its details and its bank details, as the Super Admin set them.
    ══════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'profile' && $infoMode === 'view')
        <div class="pf-main">
            <div class="sa-card p-5 sm:p-6 min-w-0 flex flex-col">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="relative flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 bg-white">
                            @if ($org && $org->logo)
                                <img src="{{ $org->logo }}" class="w-full h-full object-contain p-1.5" alt="{{ $school }}">
                            @else
                                <span class="w-full h-full flex items-center justify-center" style="background:#effaf7;color:#0d9488"><x-icon name="building-office-2" class="w-8 h-8" /></span>
                            @endif
                        </div>
                        <button type="button" wire:click="openLogoPanel" title="Change logo" aria-label="Change logo"
                            class="absolute -bottom-1.5 -right-1.5 w-8 h-8 rounded-full text-white shadow-lg flex items-center justify-center transition-colors" style="background:#0d9488">
                            <x-icon name="camera" class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="sk-display text-lg font-bold leading-tight" title="{{ $school }}">{{ $school }}</h2>
                        @if ($email)<p class="text-sm text-gray-500 truncate mt-0.5" title="{{ $email }}">{{ $email }}</p>@endif
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            @if ($org->education_board ?? null)<span class="pf-chip pf-teal"><x-icon name="academic-cap" /> {{ $org->education_board }}</span>@endif
                            @if ($org->school_code ?? null)<span class="pf-chip pf-gray">Code {{ $org->school_code }}</span>@endif
                        </div>
                    </div>
                </div>

                <div class="pf-figs mt-5">
                    <div><p class="l">Students</p><p class="v">{{ number_format($figures['students']) }}</p></div>
                    <div><p class="l">Teachers</p><p class="v">{{ number_format($figures['teachers']) }}</p></div>
                    <div><p class="l">Classes</p><p class="v">{{ number_format($figures['classes']) }}</p></div>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    @foreach ([['device-phone-mobile', 'Mobile', $mobile], ['envelope', 'Email', $email], ['globe-alt', 'Website', $site], ['map-pin', 'Address', $place]] as [$icon, $label, $value])
                        <div class="flex items-start justify-between gap-3 min-w-0">
                            <dt class="flex items-center gap-2 text-gray-500 whitespace-nowrap"><x-icon name="{{ $icon }}" class="w-4 h-4 text-gray-400" /> {{ $label }}</dt>
                            <dd class="font-semibold text-gray-900 text-right min-w-0 break-words">
                                @if ($label === 'Website' && $value)
                                    <a href="{{ \Illuminate\Support\Str::startsWith($value, ['http://', 'https://']) ? $value : 'https://' . $value }}" target="_blank" rel="noopener" class="text-teal-700 hover:underline">{{ preg_replace('#^https?://#', '', $value) }}</a>
                                @else
                                    {{ $value ?: '—' }}
                                @endif
                            </dd>
                        </div>
                    @endforeach
                </dl>

                @unless ($isSub)
                    <div class="mt-auto pt-5 flex flex-wrap gap-2">
                        <button type="button" wire:click="openPasswordPanel" class="pf-ghost"><x-icon name="lock-closed" class="w-4 h-4" /> Change Password</button>
                        <button type="button" wire:click="openLogoPanel" class="pf-ghost"><x-icon name="photo" class="w-4 h-4" /> Change Logo</button>
                    </div>
                @endunless
            </div>

            <div class="space-y-5 min-w-0">
                @foreach ([
                    ['identification', 'School Details', 'As set when the school was added', [
                        'School Name'        => $org->name ?? null,
                        'Email'              => $org->email ?? null,
                        'Mobile Number'      => $org->mobile_number ?? null,
                        'State'              => $org->state ?? null,
                        'Education Board'    => $org->education_board ?? null,
                        'School Code'        => $org->school_code ?? null,
                        'Affiliation Number' => $org->affiliation_no ?? null,
                        'UDISE Number'       => $org->udise_number ?? null,
                        'Serial Number'      => $org->serial_number ?? null,
                        'Address'            => $org->address ?? null,
                    ]],
                    ['banknotes', 'Bank Details', 'Where the school is paid', [
                        'Bank Name'      => $org->bank_name ?? null,
                        'Account Number' => $org->bank_account_no ?? null,
                        'IFSC Code'      => $org->bank_ifsc ?? null,
                        'Branch'         => $org->bank_branch ?? null,
                        'Account Holder' => $org->bank_holder_name ?? null,
                    ]],
                ] as [$icon, $title, $sub, $rows])
                    <div class="sa-card min-w-0 overflow-hidden">
                        <div class="px-5 pt-5 pb-4 flex items-start gap-3">
                            <span class="pf-icon"><x-icon name="{{ $icon }}" /></span>
                            <div class="min-w-0">
                                <h3 class="sa-card-title">{{ $title }}</h3>
                                <p class="sa-card-sub">{{ $sub }}</p>
                            </div>
                        </div>
                        <dl class="grid grid-cols-1 lg:grid-cols-2 border-t border-gray-100">
                            @foreach ($rows as $label => $value)
                                <div class="px-5 py-3 border-b border-gray-100 lg:odd:border-r flex items-start justify-between gap-3 min-w-0 text-sm">
                                    <dt class="text-gray-500 whitespace-nowrap">{{ $label }}</dt>
                                    <dd class="font-semibold text-right min-w-0 break-words {{ $value ? 'text-gray-900' : 'text-gray-300' }}">{{ $value ?: '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════
         SCHOOL INFO — about, vision and the rest, the management team and
         the documents; Edit opens the form.
    ══════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'info' && $infoMode === 'view')
        @php
            $paragraphSections = array_values(array_filter([
                ['title' => 'About Our School', 'description' => $aboutSchool, 'icon' => 'building-library'],
                ['title' => 'Vision',           'description' => $usmVision,   'icon' => 'eye'],
                ['title' => 'Mission',          'description' => $usmMission,  'icon' => 'flag'],
                ['title' => 'Values',           'description' => $usmValues,   'icon' => 'heart'],
                ['title' => 'Goals',            'description' => $usmGoals,    'icon' => 'trophy'],
            ], fn ($s) => !empty($s['description'])));
            foreach ($customSections as $cs) {
                if (!empty($cs['title']) || !empty($cs['description'])) {
                    $paragraphSections[] = ['title' => $cs['title'] ?? '', 'description' => $cs['description'] ?? '', 'icon' => 'document-text'];
                }
            }
            $hasAnySchoolInfo = count($paragraphSections) > 0 || count($schoolManagement) > 0 || count($uploadedDocuments) > 0;
        @endphp

        <div class="sa-card p-5 min-w-0 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-start gap-3 min-w-0">
                <span class="pf-icon"><x-icon name="information-circle" /></span>
                <div class="min-w-0">
                    <h3 class="sa-card-title">School Info</h3>
                    <p class="sa-card-sub">About, vision, management &amp; documents — as the school's website and app show them</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="pf-chip pf-teal">{{ count($paragraphSections) }} {{ count($paragraphSections) === 1 ? 'section' : 'sections' }}</span>
                <span class="pf-chip pf-sky">{{ count($schoolManagement) }} in management</span>
                <span class="pf-chip pf-gray">{{ count($uploadedDocuments) }} {{ count($uploadedDocuments) === 1 ? 'document' : 'documents' }}</span>
                <button type="button" wire:click="setInfoMode('edit')" class="sk-cta">
                    <x-icon name="{{ $hasAnySchoolInfo ? 'pencil-square' : 'plus' }}" class="w-4 h-4" /> {{ $hasAnySchoolInfo ? 'Edit' : 'Add School Info' }}
                </button>
            </div>
        </div>

        @unless ($hasAnySchoolInfo)
            <div class="sa-card px-6 py-14 text-center">
                <span class="pf-icon mx-auto" style="width:3.5rem;height:3.5rem;border-radius:1rem"><x-icon name="information-circle" class="w-6 h-6" /></span>
                <h3 class="sk-display text-lg font-bold mt-4">No School Info Yet</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Add your school's about, vision, management team and documents to bring this profile to life.</p>
                <button type="button" wire:click="setInfoMode('edit')" class="sk-cta mt-5"><x-icon name="plus" class="w-4 h-4" /> Add School Info</button>
            </div>
        @else
            @if ($paragraphSections)
                <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                    @foreach ($paragraphSections as $i => $section)
                        <div class="sa-card p-5 min-w-0 {{ count($paragraphSections) % 2 === 1 && $loop->last ? 'xl:col-span-2' : '' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="pf-icon"><x-icon name="{{ $section['icon'] }}" /></span>
                                <h3 class="sa-card-title truncate">{{ $section['title'] ?: 'Section ' . ($i + 1) }}</h3>
                                <span class="ml-auto pf-chip pf-gray">{{ $i + 1 }}</span>
                            </div>
                            @if (!empty($section['description']))
                                <p class="mt-3 text-sm text-gray-600 leading-relaxed break-words">{!! nl2br(e($section['description'])) !!}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="pf-two">
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-4 flex items-start gap-3">
                        <span class="pf-icon"><x-icon name="user-group" /></span>
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Management Team</h3>
                            <p class="sa-card-sub">{{ count($schoolManagement) }} {{ count($schoolManagement) === 1 ? 'member' : 'members' }}</p>
                        </div>
                    </div>
                    @if (count($schoolManagement))
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 px-5 pb-5">
                            @foreach ($schoolManagement as $member)
                                <div class="text-center min-w-0">
                                    <div class="w-16 h-16 mx-auto rounded-2xl overflow-hidden flex items-center justify-center" style="background:#effaf7">
                                        @if (!empty($member['photo_path']))
                                            <img src="{{ $member['photo_path'] }}" class="w-full h-full object-cover" alt="">
                                        @else
                                            <span class="text-xl font-semibold" style="color:#0d9488">{{ strtoupper(mb_substr($member['name'] ?? '?', 0, 1)) }}</span>
                                        @endif
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800 truncate mt-2">{{ $member['name'] }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ $member['designation'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="px-5 pb-6 text-sm text-gray-400">No members added yet.</p>
                    @endif
                </div>

                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-4 flex items-start gap-3">
                        <span class="pf-icon"><x-icon name="document-text" /></span>
                        <div class="min-w-0">
                            <h3 class="sa-card-title">School Documents</h3>
                            <p class="sa-card-sub">{{ count($uploadedDocuments) }} {{ count($uploadedDocuments) === 1 ? 'document' : 'documents' }}</p>
                        </div>
                    </div>
                    @if (count($uploadedDocuments))
                        <div class="px-5 pb-3">
                            @foreach ($uploadedDocuments as $document)
                                <div class="an-row">
                                    <span class="an-av" style="background:#effaf7;color:#0d9488"><x-icon name="document" class="w-4 h-4" /></span>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $document['title'] }}</p>
                                        <p class="text-xs text-gray-400 uppercase">{{ $document['file_type'] }}</p>
                                    </div>
                                    <a href="{{ $document['file_path'] }}" target="_blank" rel="noopener" class="pf-ghost">View <x-icon name="arrow-top-right-on-square" class="w-3.5 h-3.5" /></a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="px-5 pb-6 text-sm text-gray-400">No documents uploaded yet.</p>
                    @endif
                </div>
            </div>
        @endunless
    @endif

    {{-- ══════════════════════════════════════════════════════════
         EDIT mode — reachable from either tab (replaces view content
         while the form is open).
    ══════════════════════════════════════════════════════════ --}}
    @if ($infoMode === 'edit')
        <div class="space-y-5">

            {{-- Top action bar — back to view --}}
            <div class="flex items-center justify-between gap-3">
                <button wire:click="setInfoMode('view')" class="pf-ghost">
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
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-3 flex items-center gap-3">
                        <span class="pf-icon text-sm font-bold">
                            {{ $i + 1 }}
                        </span>
                        <h2 class="sa-card-title">{{ $item['label'] }}</h2>
                    </div>
                    <div class="px-5 pb-5">
                        <textarea wire:model="{{ $item['model'] }}" rows="4"
                            class="pf-input"
                            placeholder="{{ $item['placeholder'] }}"></textarea>
                    </div>
                </div>
            @endforeach

            {{-- ── Custom Sections — admin can add additional paragraph cards. ── --}}
            @foreach ($customSections as $index => $section)
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="pf-icon text-sm font-bold">
                                {{ $index + 6 }}
                            </span>
                            <h2 class="sa-card-title truncate">
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
                    <div class="px-5 pb-5 space-y-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Section Title</label>
                            <input type="text" wire:model.defer="customSections.{{ $index }}.title"
                                class="pf-input"
                                placeholder="e.g. Our History">
                            @error('customSections.'.$index.'.title')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                            <textarea wire:model.defer="customSections.{{ $index }}.description" rows="4"
                                class="pf-input"
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
                               border-2 border-dashed border-teal-200 hover:border-teal-400
                               text-teal-700 hover:bg-teal-50 text-sm font-semibold rounded-2xl transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Section
                </button>
            </div>

            {{-- Contact Information --}}
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 gap-3 flex items-center gap-3">
                    <div class="pf-icon">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h2 class="sa-card-title">Contact Information</h2>
                </div>
                <div class="px-5 pb-5 grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">School Email</label>
                        <input type="email" wire:model="schoolEmail"
                            class="pf-input"
                            placeholder="contact@school.edu">
                        @error('schoolEmail')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Mobile Number</label>
                        <input type="text" wire:model="schoolMobileNo" inputmode="numeric"
                            class="pf-input"
                            placeholder="9876543210">
                        @error('schoolMobileNo')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Website URL</label>
                        <input type="url" wire:model="websiteUrl"
                            class="pf-input"
                            placeholder="https://www.yourschool.edu">
                        @error('websiteUrl')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">School Address</label>
                        <textarea wire:model="schoolAddress" rows="2"
                            class="pf-input"
                            placeholder="Full address with city, state, pin code…"></textarea>
                        @error('schoolAddress')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Website Description</label>
                        <textarea wire:model="websiteInfo" rows="2"
                            class="pf-input"
                            placeholder="Brief description of your website…"></textarea>
                    </div>
                </div>
            </div>

            {{-- Management Team --}}
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 gap-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="pf-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="sa-card-title">Management Team</h2>
                            <p class="text-xs text-gray-400">{{ count($schoolManagement) }} member(s)</p>
                        </div>
                    </div>
                    <button wire:click="openMemberPanel()"
                        class="sk-cta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Member
                    </button>
                </div>
                <div class="px-5 pb-5 space-y-3">
                    @forelse ($schoolManagement as $index => $member)
                        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100 hover:border-teal-200 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                @if (!empty($member['photo_path']))
                                    <img src="{{ $member['photo_path'] }}" class="h-12 w-12 rounded-full object-cover border-2 border-white shadow flex-shrink-0">
                                @else
                                    <div class="h-12 w-12 rounded-full bg-teal-50 flex items-center justify-center border-2 border-white shadow flex-shrink-0">
                                        <svg class="h-6 w-6 text-teal-600" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
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
                                    class="p-1.5 rounded-lg border border-gray-200 text-gray-500 hover:bg-teal-50 hover:text-teal-700 transition-colors" title="Edit">
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
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3 gap-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="pf-icon">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="sa-card-title">School Documents</h2>
                            <p class="text-xs text-gray-400">{{ count($uploadedDocuments) }} saved · {{ count($pendingDocuments) }} pending · Max 2MB PDF</p>
                        </div>
                    </div>
                    <button wire:click="openDocumentPanel"
                        class="sk-cta">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Document
                    </button>
                </div>
                <div class="px-5 pb-5 space-y-3">

                    {{-- Saved documents --}}
                    @foreach ($uploadedDocuments as $document)
                        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100 hover:border-teal-200 transition-colors">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-10 h-10 bg-teal-50 rounded-lg flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                                    class="p-1.5 rounded-lg border border-teal-200 text-teal-700 hover:bg-teal-50 transition-colors" title="View">
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
                    class="sk-cta disabled:opacity-60" style="height:44px;padding:0 26px">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span wire:loading.remove wire:target="saveSchoolInfo">Save All Changes</span>
                    <span wire:loading wire:target="saveSchoolInfo">Saving…</span>
                </button>
            </div>
        </div>
    @endif

    </div>{{-- /the page's padded column --}}

    {{-- ══════════════════════════════════════════════════
         MANAGEMENT MEMBER SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showMemberPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeMemberPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">
                            {{ $editMemberIndex !== null ? 'Edit Member' : 'Add Member' }}
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Name, designation and an optional photo</p>
                    </div>
                    <button wire:click="closeMemberPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="newMember.name" placeholder="e.g. Mr. Sharma"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('newMember.name')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Designation <span class="text-red-500">*</span></label>
                            <input type="text" wire:model.defer="newMember.designation" placeholder="e.g. Principal"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                            @error('newMember.designation')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Photo — click-anywhere drop zone over a hidden input --}}
                    <div x-data>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Photo <span class="text-xs font-normal text-gray-400">(Optional · JPG/PNG, max 2 MB)</span>
                        </label>
                        <label for="memberPhotoInput"
                            class="flex items-center gap-3 w-full px-4 py-3.5 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                            @if ($newMemberPhoto instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                                <img src="{{ $newMemberPhoto->temporaryUrl() }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @elseif (!empty($newMember['photo_path']))
                                <img src="{{ $newMember['photo_path'] }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 flex-shrink-0">
                            @else
                                <span class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </span>
                            @endif
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-700">Click to choose a photo</span>
                                <span class="block text-xs text-gray-400">
                                    JPG or PNG, up to 2 MB{{ $editMemberIndex !== null ? ' · leave empty to keep the current one' : '' }}
                                </span>
                            </span>
                        </label>
                        <input id="memberPhotoInput" x-ref="memberPhotoInput" type="file" wire:model="newMemberPhoto"
                            accept="image/*" class="hidden">

                        <div wire:loading wire:target="newMemberPhoto" class="flex items-center gap-2 mt-2 text-xs text-blue-600">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Uploading...
                        </div>

                        @if ($newMemberPhoto instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile)
                            <button type="button"
                                x-on:click="$refs.memberPhotoInput.value = ''; $wire.set('newMemberPhoto', null)"
                                wire:loading.remove wire:target="newMemberPhoto"
                                class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">
                                Remove selected photo
                            </button>
                        @endif
                        @error('newMemberPhoto')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeMemberPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveMember" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveMember">{{ $editMemberIndex !== null ? 'Update Member' : 'Add Member' }}</span>
                        <span wire:loading wire:target="saveMember">Saving...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DOCUMENT SLIDE-IN PANEL
    ══════════════════════════════════════════════════ --}}
    @if ($showDocumentPanel)
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeDocumentPanel"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">

                {{-- Fixed header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-gray-900 truncate">Add Document</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Queued here, saved when you press "Save All Changes"</p>
                    </div>
                    <button wire:click="closeDocumentPanel"
                        class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Scrollable body --}}
                <div class="flex-1 overflow-y-auto px-6 py-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Title <span class="text-red-500">*</span></label>
                        <input type="text" wire:model.defer="newDocument.title" placeholder="e.g. Affiliation Certificate"
                            class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                        @error('newDocument.title')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>

                    {{-- File — click-anywhere drop zone over a hidden input --}}
                    <div x-data>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            PDF File <span class="text-red-500">*</span>
                            <span class="text-xs font-normal text-gray-400">(max 2 MB)</span>
                        </label>
                        <label for="schoolDocInput"
                            class="flex items-center gap-3 w-full px-4 py-4 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors">
                            <span class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-medium text-gray-700">Click to choose a PDF</span>
                                <span class="block text-xs text-gray-400">PDF only, up to 2 MB</span>
                            </span>
                        </label>
                        <input id="schoolDocInput" x-ref="schoolDocInput" type="file" wire:model="newDocumentFile"
                            accept="application/pdf,.pdf" class="hidden">

                        <div wire:loading wire:target="newDocumentFile" class="flex items-center gap-2 mt-2 text-xs text-blue-600">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                            Uploading...
                        </div>

                        @if ($newDocumentFile)
                            <div wire:loading.remove wire:target="newDocumentFile"
                                class="mt-2 flex items-center justify-between gap-3 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs text-emerald-800 truncate">{{ $newDocumentFile->getClientOriginalName() }}</span>
                                    <span class="text-[11px] text-emerald-600 flex-shrink-0">
                                        ({{ number_format($newDocumentFile->getSize() / 1024, 1) }} KB)
                                    </span>
                                </div>
                                <button type="button" title="Remove"
                                    x-on:click="$refs.schoolDocInput.value = ''; $wire.set('newDocumentFile', null)"
                                    class="p-1 rounded text-emerald-700 hover:bg-emerald-100 flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endif
                        @error('newDocumentFile')<p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>@enderror
                    </div>
                </div>

                {{-- Fixed footer --}}
                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeDocumentPanel" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Cancel</button>
                    <button wire:click="saveDocumentPanel" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-medium rounded-md flex items-center gap-1.5 disabled:opacity-60">
                        <span wire:loading.remove wire:target="saveDocumentPanel">Add Document</span>
                        <span wire:loading wire:target="saveDocumentPanel">Adding...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════
         DELETE CONFIRMS
    ══════════════════════════════════════════════════ --}}
    @if ($pendingDeleteMemberIndex !== null)
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="lms-cover fixed inset-x-0 bottom-0 top-16 z-50 flex items-center justify-center p-4">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
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
