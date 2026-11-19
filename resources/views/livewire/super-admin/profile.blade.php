{{-- Super Admin → My Profile in Home / Analytics' look (user's ask,
     2026-11-19): Home's skin and header, a chip for each part under it, every
     card a plain white one (the dark account card is gone) and Home's teal in
     place of the panel's violet. The fields are the login's (a grey edge, teal
     while typing and once typed). Each part is drawn hidden and x-show opens
     the picked one; the URL's #hash keeps it. --}}
@php
    $pfTabs = array_filter([
        'account'  => ['Account', 'user-circle'],
        'security' => ['Sign-in & Password', 'lock-closed'],
        'activity' => ['Login Activity', 'finger-print'],
        'platform' => $isMain ? ['Platform', 'building-office-2'] : null,
        'website'  => $isMain ? ['Website', 'globe-alt'] : null,
    ]);
@endphp
<div class="sa-skin sa-home min-h-screen"
    x-data="{
        tabs: {{ \Illuminate\Support\Js::from(array_keys($pfTabs)) }},
        tab: 'account',
        init() {
            let h = location.hash.slice(1);
            if (h === 'otp-numbers') h = 'security';
            if (this.tabs.includes(h)) this.tab = h;
        },
        go(t) { this.tab = t; history.replaceState(null, '', '#' + t); this.$nextTick(() => window.dispatchEvent(new Event('resize'))); },
    }">
    @include('livewire.super-admin.partials.dash-styles')
    @include('livewire.super-admin.partials.home-skin')

    {{-- Profile's own bits on Home's skin: the fields carry their name on the
         border, in the login's look; chips are one family. --}}
    <style>
        /* In Tailwind's components layer, so utilities (pl-10, pr-11 …) still win. */
        @layer components {
        .pf-field { position: relative; display: block; }
        .pf-input {
            width: 100%; height: 2.875rem; border: 1.5px solid #d6dedb; border-radius: 9999px; background: #fff;
            padding: 0 1.125rem; font-family: 'Geist', 'Segoe UI', system-ui, sans-serif; font-size: .9375rem; color: #111827;
            transition: border-color .15s, color .15s;
        }
        textarea.pf-input { height: auto; min-height: 5.75rem; border-radius: 1.125rem; padding: .85rem 1.125rem .7rem; resize: vertical; line-height: 1.45; }
        .pf-input::placeholder { color: #9ca3af; }
        .pf-input:focus { outline: none; box-shadow: none; border-color: #0d9488; }
        .pf-input.sa-filled { border-color: #0d9488; color: #0d9488; }
        .pf-label {
            position: absolute; left: 1rem; top: 0; transform: translateY(-50%);
            max-width: calc(100% - 2rem); padding: 0 .375rem; background: #fff;
            font-size: .75rem; font-weight: 600; line-height: 1rem; color: #6b7280;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none; transition: color .15s;
        }
        .pf-field:focus-within .pf-label, .pf-field:has(.sa-filled) .pf-label { color: #0f766e; }
        .pf-field.has-error .pf-input { border-color: #dc2626; color: #dc2626; }
        .pf-field.has-error .pf-label { color: #dc2626; }
        .pf-err { margin: .35rem 0 0 1.125rem; font-size: .75rem; line-height: 1rem; color: #dc2626; }

        .pf-chip {
            display: inline-flex; align-items: center; gap: .35rem; height: 1.5rem; padding: 0 .6rem;
            border-radius: 9999px; border: 1px solid transparent; white-space: nowrap;
            font-size: .6875rem; font-weight: 600; line-height: 1; letter-spacing: .01em;
        }
        .pf-chip > svg { width: .8rem; height: .8rem; flex-shrink: 0; }
        .pf-chip.pf-dot::before { content: ''; width: .375rem; height: .375rem; border-radius: 9999px; background: currentColor; }
        .pf-chip.pf-teal    { background: #effaf7; color: #0f766e; border-color: #d8f3ec; }
        .pf-chip.pf-sky     { background: #f0f9ff; color: #0369a1; border-color: #dff1fd; }
        .pf-chip.pf-emerald { background: #ecfdf5; color: #047857; border-color: #d3f5e5; }
        .pf-chip.pf-amber   { background: #fffbeb; color: #b45309; border-color: #fdefc8; }
        .pf-chip.pf-rose    { background: #fff1f2; color: #be123c; border-color: #ffe1e5; }
        .pf-chip.pf-gray    { background: #f6f8f7; color: #4b5b56; border-color: #e8edeb; }

        .pf-icon { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; background: #effaf7; color: #0d9488; }
        .pf-icon svg { width: 1rem; height: 1rem; }
        /* A light pill button (Choose image, Cancel …). */
        .pf-ghost { display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 14px; border-radius: 9999px; border: 1px solid #e3e8e6; background: #fff; font-size: 13.5px; font-weight: 500; color: #44544f; white-space: nowrap; cursor: pointer; transition: border-color .2s, color .2s, background .2s; }
        .pf-ghost:hover { border-color: #b1e6db; color: #0f766e; background: #effaf7; }
        .pf-round { width: 32px; height: 32px; border-radius: 9999px; display: inline-flex; align-items: center; justify-content: center; color: #8b9893; transition: background .2s, color .2s; flex-shrink: 0; }
        .pf-round:hover { background: #effaf7; color: #0f766e; }
        .pf-round.danger:hover { background: #fff1f2; color: #e11d48; }
        .pf-round svg { width: 16px; height: 16px; }
        /* Figures in one card, split by hairlines (no small cards). */
        .pf-figs { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid #eef1f0; border-radius: 16px; overflow: hidden; }
        .pf-figs > * { padding: 12px 14px; min-width: 0; border-left: 1px solid #eef1f0; }
        .pf-figs > :first-child { border-left: 0; }
        .pf-figs .l { font-size: 11.5px; color: #6b7c76; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pf-figs .v { font-size: 17px; font-weight: 600; color: #0c2620; margin-top: 2px; font-variant-numeric: tabular-nums; }
        .pf-figs .v small { font-size: 11.5px; font-weight: 400; color: #95a39e; }
        .pf-glance { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .pf-glance > * { padding: 16px 18px; border-top: 1px solid #f0f3f2; border-left: 1px solid #f0f3f2; min-width: 0; display: block; transition: background .2s; }
        .pf-glance > :nth-child(-n+3) { border-top: 0; }
        .pf-glance > :nth-child(3n+1) { border-left: 0; }
        .pf-glance > a:hover { background: #f8fbfa; }
        @media (max-width: 639px) {
            .pf-glance { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .pf-glance > * { border-left: 1px solid #f0f3f2 !important; border-top: 1px solid #f0f3f2 !important; }
            .pf-glance > :nth-child(2n+1) { border-left: 0 !important; }
            .pf-glance > :nth-child(-n+2) { border-top: 0 !important; }
        }
        .pf-two { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 1024px) { .pf-two { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); } }
        .pf-main { display: grid; gap: 20px; grid-template-columns: minmax(0, 1fr); }
        @media (min-width: 1280px) { .pf-main { grid-template-columns: minmax(0, 1fr) minmax(0, 2fr); } }
        @media (min-width: 1280px) { .pf-main.wide-first { grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); } }
        }
    </style>

    @php
        $roleLabel = \Illuminate\Support\Str::headline((string) $user->role);
        $initial   = strtoupper(mb_substr($user->name ?? 'U', 0, 1));
        $photoUrl  = $tempPhotoUrl ?: $user->image;
        $fmtPhone  = fn ($p) => $p ? \App\Support\OtpNumbers::format((string) $p) : '—';

        $accountRows = [['device-phone-mobile', 'Primary mobile', $fmtPhone($user->mobile_number)]];
        if (!empty($user->dob)) {
            $accountRows[] = ['cake', 'Date of birth', \Carbon\Carbon::parse($user->dob)->format('d M Y')];
        }
        if (!empty($user->date_of_joining)) {
            $accountRows[] = ['calendar-days', 'Joined', \Carbon\Carbon::parse($user->date_of_joining)->format('d M Y')];
        }
        if (!empty($user->gender)) {
            $accountRows[] = ['user', 'Gender', ucfirst((string) $user->gender)];
        }
        $lastLogin = $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never';
        $accountRows[] = ['clock', 'Last login', $lastLogin];

        $tones = [
            'teal'    => ['#effaf7', '#0d9488'],
            'sky'     => ['#f0f9ff', '#0284c7'],
            'emerald' => ['#ecfdf5', '#059669'],
            'amber'   => ['#fffbeb', '#d97706'],
            'rose'    => ['#fff1f2', '#e11d48'],
            'ink'     => ['#f3f4f6', '#111827'],
        ];

        $passwords = [
            ['currentPassword', 'current', 'Current password',     $showCurrentPassword, 'current-password'],
            ['newPassword',     'new',     'New password',         $showNewPassword,     'new-password'],
            ['confirmPassword', 'confirm', 'Confirm new password', $showConfirmPassword, 'new-password'],
        ];

        // Login activity: a chip per role and per way in.
        $roleChip = fn ($role) => $role === 'super-admin' ? ['teal', 'Super Admin'] : ['sky', \Illuminate\Support\Str::headline((string) $role ?: 'Unknown')];
        $methodChip = [
            'otp'        => ['emerald', 'shield-check', 'Password + OTP'],
            'password'   => ['amber', 'key', 'Password'],
            'remembered' => ['gray', 'clock', 'Remembered'],
        ];
        $loginDays = collect($logins['rows'])->groupBy('day');
        $dayMax    = max(1, (int) collect($logins['days'])->max('count'));
        $dayName   = function ($day) {
            $d = \Carbon\Carbon::parse($day);
            return $d->isToday() ? 'Today' : ($d->isYesterday() ? 'Yesterday' : $d->format('l, d M Y'));
        };
        $mine = collect($logins['rows'])->where('email', \App\Models\User::cleanEmail((string) $user->email))->count();

        // Platform details form: key => [icon, placeholder, input type, columns]
        $detailFields = [
            'platform_name'   => ['sparkles',          'SuperLMS',                    'text',  1],
            'company_name'    => ['building-office',   'Registered company name',     'text',  1],
            'company_cin'     => ['identification',    'U80301UP2021PTC123456',       'text',  1],
            'company_gstin'   => ['receipt-percent',   '09ABCDE1234F1Z5',             'text',  1],
            'company_email'   => ['envelope',          'hello@superlms.in',           'email', 1],
            'company_mobile'  => ['phone',             '10-digit mobile number',      'tel',   1],
            'company_website' => ['globe-alt',         'https://superlms.in',         'url',   2],
            'company_address' => ['map-pin',           'Building, street, city, state, PIN', 'textarea', 2],
        ];

        // The 15 days' sign-ins as bars (Account and Login Activity share them).
        $bars = function () use ($logins, $dayMax) {
            $html = '<div class="flex items-end gap-1 sm:gap-1.5 h-20">';
            foreach ($logins['days'] as $d) {
                $h  = $d['count'] ? max(10, round($d['count'] / $dayMax * 100)) : 6;
                $bg = $d['count'] ? ($d['today'] ? '#0d9488' : '#99e0d3') : '#eef2f1';
                $html .= '<div class="flex-1 min-w-0 h-full flex flex-col justify-end" title="' . e($d['label'] . ' · ' . $d['count'] . ' ' . \Illuminate\Support\Str::plural('login', $d['count'])) . '">'
                    . '<div class="w-full rounded-md" style="height: ' . $h . '%; background: ' . $bg . '"></div></div>';
            }
            $html .= '</div><div class="mt-1.5 flex gap-1 sm:gap-1.5">';
            foreach ($logins['days'] as $d) {
                $html .= '<span class="flex-1 min-w-0 text-center text-[10px] tabular-nums ' . ($d['today'] ? 'font-semibold text-teal-700' : 'text-gray-400') . '">' . e($d['short']) . '</span>';
            }

            return $html . '</div>';
        };
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="sk-head">
        <div class="sk-title">
            <h1>My Profile</h1>
            <p>{{ $roleLabel }} · {{ $user->email }}</p>
        </div>
        <div class="sk-right">
            <span class="sk-pill" title="Your last sign-in"><x-icon name="clock" class="w-3.5 h-3.5" /> Last login · {{ $lastLogin }}</span>
            <span class="sk-pill" style="color:#0f766e;background:#effaf7;border-color:#d8f3ec"><x-icon name="shield-check" class="w-3.5 h-3.5" /> {{ $roleLabel }}</span>
        </div>
    </div>

    <nav class="an-chips" aria-label="Profile sections">
        @foreach ($pfTabs as $key => [$label, $icon])
            <button type="button" class="an-chip" :class="tab === '{{ $key }}' && 'on'" x-on:click="go('{{ $key }}')" :aria-pressed="tab === '{{ $key }}'">
                <x-icon name="{{ $icon }}" /> {{ $label }}
            </button>
        @endforeach
    </nav>

    <div class="p-4 sm:p-6 pt-4 sm:pt-5">

        {{-- ═══════════════════════════ ACCOUNT ═══════════════════════════ --}}
        <section class="space-y-5" data-pf="account" x-show="tab === 'account'" style="display: none">
            <div class="pf-main">

                {{-- You --}}
                <div class="sa-card p-5 sm:p-6 min-w-0 flex flex-col" data-pf-account>
                    <div class="flex items-center gap-4 min-w-0">
                        <div class="relative flex-shrink-0">
                            <div class="w-20 h-20 rounded-2xl overflow-hidden flex items-center justify-center" style="background:#effaf7">
                                @if ($photoUrl)
                                    <img src="{{ $photoUrl }}" class="w-full h-full object-cover" alt="">
                                @else
                                    <span class="text-3xl font-semibold" style="color:#0d9488">{{ $initial }}</span>
                                @endif
                            </div>
                            <label title="Change photo" aria-label="Change photo"
                                class="absolute -bottom-1.5 -right-1.5 w-8 h-8 rounded-full text-white shadow-lg flex items-center justify-center cursor-pointer transition-colors" style="background:#0d9488">
                                <x-icon name="camera" class="w-4 h-4" />
                                <input type="file" wire:model="photo" accept="image/*" class="hidden">
                            </label>
                        </div>
                        <div class="min-w-0 flex-1">
                            {{-- Your name: what Quick Links greets and the sidebar's card shows. --}}
                            @if ($editingName)
                                <form wire:submit="saveName" class="flex items-center gap-1.5 min-w-0" data-name-form>
                                    <label class="pf-field flex-1 min-w-0 {{ $errors->has('newName') ? 'has-error' : '' }}">
                                        <input id="pf-name" type="text" wire:model="newName" maxlength="60" autocomplete="name" placeholder="Your name"
                                            x-init="$el.focus(); $el.select()" x-on:keydown.escape="$wire.cancelNameEdit()" class="pf-input" style="height: 2.5rem">
                                        <span class="pf-label">Your name</span>
                                    </label>
                                    <button type="submit" title="Save name" aria-label="Save name" class="pf-round" style="background:#0d9488;color:#fff">
                                        <x-icon name="check" />
                                    </button>
                                    <button type="button" wire:click="cancelNameEdit" title="Cancel" aria-label="Cancel" class="pf-round">
                                        <x-icon name="x-mark" />
                                    </button>
                                </form>
                                @error('newName') <p class="pf-err">{{ $message }}</p> @enderror
                            @else
                                <div class="flex items-center gap-1 min-w-0">
                                    <h2 class="sk-display text-lg font-bold leading-tight truncate" title="{{ $user->name }}">{{ $user->name }}</h2>
                                    <button type="button" wire:click="startNameEdit" title="Change your name" aria-label="Change your name" class="pf-round">
                                        <x-icon name="pencil-square" />
                                    </button>
                                </div>
                            @endif
                            <p class="text-sm text-gray-500 truncate mt-0.5" title="{{ $user->email }}">{{ $user->email }}</p>
                            <span class="pf-chip pf-teal mt-2"><x-icon name="shield-check" /> {{ $roleLabel }}</span>
                        </div>
                    </div>

                    <div wire:loading wire:target="photo" class="mt-4 text-xs text-gray-400">Uploading photo…</div>
                    @error('photo') <p class="mt-4 text-xs text-rose-600">{{ $message }}</p> @enderror
                    @if ($photo && !$errors->has('photo'))
                        <div class="mt-4 flex items-center gap-2" wire:loading.remove wire:target="photo">
                            <button wire:click="savePhoto" wire:loading.attr="disabled" wire:target="savePhoto" class="sk-cta">
                                <span wire:loading.remove wire:target="savePhoto">Save photo</span>
                                <span wire:loading wire:target="savePhoto">Saving…</span>
                            </button>
                            <button wire:click="cancelPhoto" class="pf-ghost">Cancel</button>
                        </div>
                    @endif

                    {{-- Where the sign-in code goes, and how often this account came in. --}}
                    <div class="pf-figs mt-5">
                        <div>
                            <p class="l">OTP numbers</p>
                            <p class="v">{{ count($numbers) }} <small>of {{ $maxNumbers }}</small></p>
                        </div>
                        <div>
                            <p class="l">Code by</p>
                            <p class="v" style="font-size:14px; margin-top:4px">{{ count($numbers) ? 'Email + WhatsApp' : 'Email' }}</p>
                        </div>
                        <div title="Your sign-ins in the last 15 days">
                            <p class="l">Logins</p>
                            <p class="v">{{ $mine }} <small>· 15 days</small></p>
                        </div>
                    </div>

                    <dl class="mt-5 space-y-3 text-sm">
                        @foreach ($accountRows as [$icon, $label, $value])
                            <div class="flex items-center justify-between gap-3 min-w-0">
                                <dt class="flex items-center gap-2 text-gray-500 whitespace-nowrap">
                                    <x-icon name="{{ $icon }}" class="w-4 h-4 text-gray-400" />
                                    {{ $label }}
                                </dt>
                                <dd class="font-semibold text-gray-900 tabular-nums text-right truncate" title="{{ $value }}">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @if (!empty($grantedAccess))
                        <div class="mt-5 pt-5 border-t border-gray-100">
                            <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400 mb-2">Your access</p>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach ($grantedAccess as $perm)
                                    <span class="pf-chip pf-teal"><x-icon name="check" /> {{ $perm }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- The last 15 days' sign-ins, and the latest few. --}}
                <div class="sa-card p-5 min-w-0 flex flex-col">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="pf-icon"><x-icon name="finger-print" /></span>
                            <div class="min-w-0">
                                <h3 class="sa-card-title">Sign-ins</h3>
                                <p class="sa-card-sub">{{ $logins['all'] ? 'Everyone on the Super Admin panel' : 'Your sign-ins' }} · since {{ $logins['since'] }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span class="pf-chip pf-teal"><x-icon name="arrow-right-on-rectangle" />{{ $logins['total'] }} {{ \Illuminate\Support\Str::plural('login', $logins['total']) }}</span>
                            <span class="pf-chip pf-emerald pf-dot">{{ $logins['today'] }} today</span>
                        </div>
                    </div>
                    {!! $bars() !!}

                    <div class="mt-5 pt-4 border-t border-gray-100 flex-1">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-gray-400 mb-1">Latest</p>
                        @forelse (array_slice($logins['rows'], 0, 4) as $row)
                            @php
                                [$rTone]                = $roleChip($row['role']);
                                [$mTone, $mIcon, $mLbl] = $methodChip[$row['method']] ?? ['gray', 'question-mark-circle', ucfirst((string) $row['method'] ?: 'Unknown')];
                            @endphp
                            <div class="an-row">
                                <span class="an-av" style="background: {{ $tones[$rTone][0] }}; color: {{ $tones[$rTone][1] }}">{{ strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $row['name'] }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $row['browser'] }}@if ($row['os']) · {{ $row['os'] }}@endif · {{ $row['ago'] }}</p>
                                </div>
                                <span class="pf-chip pf-{{ $mTone }} hidden sm:inline-flex"><x-icon name="{{ $mIcon }}" />{{ $mLbl }}</span>
                            </div>
                        @empty
                            <p class="py-6 text-center text-sm text-gray-400">No sign-ins in the last 15 days yet.</p>
                        @endforelse
                    </div>
                    <div class="pt-3">
                        <button type="button" class="pf-ghost" x-on:click="go('activity')">All login activity <x-icon name="arrow-right" class="w-3.5 h-3.5" /></button>
                    </div>
                </div>
            </div>

            @if ($isMain)
                {{-- The platform at a glance: one card, its figures split by hairlines. --}}
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="px-5 pt-5 pb-4 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="pf-icon"><x-icon name="squares-2x2" /></span>
                            <div class="min-w-0">
                                <h3 class="sa-card-title">Platform at a Glance</h3>
                                <p class="sa-card-sub">{{ $platform['name'] }} today</p>
                            </div>
                        </div>
                    </div>
                    <div class="pf-glance border-t border-gray-100">
                        @foreach ([
                            ['Schools',        $platform['schools'],  'On ' . $platform['name'],                 'building-office-2', route('super-admin.schools')],
                            ['Active schools', $platform['active'],   'of ' . $platform['schools'] . ' schools', 'check-badge',       route('super-admin.schools')],
                            ['Students',       $platform['students'], 'Across all schools',                      'users',             route('super-admin.students')],
                            ['Teachers',       $platform['teachers'], 'Across all schools',                      'academic-cap',      route('super-admin.teachers')],
                            ['School admins',  $platform['admins'],   'Admins and sub-admins',                   'identification',    null],
                            ['Super admins',   $platform['super'],    'You and your team',                       'shield-check',      null],
                        ] as [$label, $value, $sub, $icon, $href])
                            <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif>
                                <p class="flex items-center gap-2 text-xs font-medium text-gray-500 truncate"><x-icon name="{{ $icon }}" class="w-4 h-4 text-teal-600" /> {{ $label }}</p>
                                <p class="sk-display text-2xl font-bold mt-1.5 tabular-nums">{{ $value }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 truncate" title="{{ $sub }}">{{ $sub }}</p>
                            </{{ $href ? 'a' : 'div' }}>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>

        {{-- ═══════════════════════ SIGN-IN & PASSWORD ═══════════════════════ --}}
        <section data-pf="security" x-show="tab === 'security'" style="display: none">
            <div class="pf-two">
                {{-- OTP numbers --}}
                <div id="otp-numbers" class="sa-card p-5 min-w-0 flex flex-col">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="pf-icon"><x-icon name="device-phone-mobile" /></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="sa-card-title">OTP Numbers</h3>
                            <p class="sa-card-sub">The sign-in code goes to your email and, on WhatsApp, to the primary number only</p>
                        </div>
                        <span class="pf-chip pf-teal tabular-nums">{{ count($numbers) }} of {{ $maxNumbers }}</span>
                    </div>

                    <div>
                        @forelse ($numbers as $n)
                            @php $key = $n['primary'] ? 'p' : (string) $n['id']; @endphp
                            <div wire:key="otp-number-{{ $key }}" class="an-row">
                                @if ($editing === $key)
                                    <div class="flex items-start gap-2 pt-2 w-full">
                                        <div class="flex-1 min-w-0">
                                            <label class="pf-field {{ $errors->has('editNumber') ? 'has-error' : '' }}">
                                                <input type="tel" wire:model="editNumber" wire:keydown.enter="saveEdit" inputmode="tel" maxlength="16" class="pf-input">
                                                <span class="pf-label">{{ $n['primary'] ? 'Primary number' : 'Number' }}</span>
                                            </label>
                                            @error('editNumber') <p class="pf-err">{{ $message }}</p> @enderror
                                        </div>
                                        <button wire:click="saveEdit" wire:loading.attr="disabled" class="sk-cta" style="height: 2.875rem">Save</button>
                                        <button wire:click="cancelEdit" title="Cancel" aria-label="Cancel" class="pf-round" style="width: 2.875rem; height: 2.875rem"><x-icon name="x-mark" /></button>
                                    </div>
                                @else
                                    <span class="an-av" style="{{ $n['primary'] ? '' : 'background:#f3f5f4;color:#8b9893' }}"><x-icon name="device-phone-mobile" class="w-4 h-4" /></span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 tabular-nums truncate">{{ \App\Support\OtpNumbers::format($n['phone']) }}</p>
                                        <div class="mt-1">
                                            @if ($n['primary'])
                                                <span class="pf-chip pf-emerald pf-dot">Primary · gets the OTP</span>
                                            @else
                                                <button wire:click="makePrimary({{ $n['id'] }})" class="pf-chip pf-gray hover:bg-teal-50 hover:text-teal-700 transition-colors">
                                                    <x-icon name="star" />
                                                    Make primary
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <button wire:click="startEdit('{{ $key }}')" title="Edit" aria-label="Edit" class="pf-round"><x-icon name="pencil-square" /></button>
                                    <button wire:click="deleteNumber('{{ $key }}')"
                                        wire:confirm="Delete {{ \App\Support\OtpNumbers::format($n['phone']) }}?{{ $n['primary'] ? ' The next number becomes primary.' : '' }}"
                                        title="Delete" aria-label="Delete" class="pf-round danger"><x-icon name="trash" /></button>
                                @endif
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-gray-400">No number yet — the code goes by email only. Add one below.</p>
                        @endforelse
                    </div>

                    <div class="mt-auto pt-5">
                        <div class="pt-5 border-t border-gray-100">
                            @if (count($numbers) < $maxNumbers)
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <label class="pf-field {{ $errors->has('newNumber') ? 'has-error' : '' }}">
                                            <input type="tel" wire:model="newNumber" wire:keydown.enter="addNumber" inputmode="tel" maxlength="16"
                                                placeholder="10-digit mobile number" class="pf-input">
                                            <span class="pf-label">Add a number</span>
                                        </label>
                                        @error('newNumber') <p class="pf-err">{{ $message }}</p> @enderror
                                    </div>
                                    <button wire:click="addNumber" wire:loading.attr="disabled" wire:target="addNumber" class="sk-cta" style="height: 2.875rem">
                                        <x-icon name="plus" class="w-4 h-4" />
                                        Add
                                    </button>
                                </div>
                            @else
                                <p class="text-xs text-gray-400">All {{ $maxNumbers }} numbers are in use — delete one to add another.</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Password --}}
                <div class="sa-card p-5 min-w-0 flex flex-col">
                    <div class="flex items-start gap-3 mb-6">
                        <span class="pf-icon"><x-icon name="lock-closed" /></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="sa-card-title">Change Password</h3>
                            <p class="sa-card-sub">At least 8 characters with upper and lower case, a number and a symbol</p>
                        </div>
                    </div>

                    <div class="space-y-5">
                        @foreach ($passwords as [$model, $field, $label, $shown, $autocomplete])
                            <div>
                                <label class="pf-field {{ $errors->has($model) ? 'has-error' : '' }}">
                                    <input type="{{ $shown ? 'text' : 'password' }}" wire:model="{{ $model }}" autocomplete="{{ $autocomplete }}"
                                        @if ($field === 'confirm') wire:keydown.enter="updatePassword" @endif
                                        class="pf-input pr-12">
                                    <span class="pf-label">{{ $label }}</span>
                                    <button type="button" wire:click="togglePasswordVisibility('{{ $field }}')"
                                        title="{{ $shown ? 'Hide' : 'Show' }}" aria-label="{{ $shown ? 'Hide' : 'Show' }} password"
                                        class="absolute inset-y-0 right-1.5 w-10 flex items-center justify-center text-gray-400 hover:text-teal-700">
                                        <x-icon name="{{ $shown ? 'eye-slash' : 'eye' }}" class="w-4 h-4" />
                                    </button>
                                </label>
                                @error($model) <p class="pf-err">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-auto pt-6">
                        <button wire:click="updatePassword" wire:loading.attr="disabled" wire:target="updatePassword" class="sk-cta w-full justify-center" style="height: 2.875rem">
                            <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                            <span wire:loading wire:target="updatePassword">Updating…</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- ═══════════════════════════ LOGIN ACTIVITY ═══════════════════════════ --}}
        <section data-pf="activity" x-show="tab === 'activity'" style="display: none">
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="p-5 flex flex-col lg:flex-row lg:items-end gap-5">
                    <div class="flex items-start gap-3 lg:w-80 flex-shrink-0">
                        <span class="pf-icon"><x-icon name="finger-print" /></span>
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Login Activity</h3>
                            <p class="sa-card-sub">{{ $logins['all'] ? 'Every sign-in to the Super Admin panel' : 'Your sign-ins to the Super Admin panel' }} · since {{ $logins['since'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="pf-chip pf-teal"><x-icon name="arrow-right-on-rectangle" />{{ $logins['total'] }} {{ \Illuminate\Support\Str::plural('login', $logins['total']) }}</span>
                                <span class="pf-chip pf-emerald pf-dot">{{ $logins['today'] }} today</span>
                                @if ($logins['all'])
                                    <span class="pf-chip pf-sky"><x-icon name="users" />{{ count($logins['accounts']) }} {{ \Illuminate\Support\Str::plural('account', count($logins['accounts'])) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Sign-ins a day, the last 15 days --}}
                    <div class="flex-1 min-w-0">{!! $bars() !!}</div>
                </div>

                @if ($logins['all'] && count($logins['accounts']) > 0)
                    <div class="px-5 pb-4 flex flex-wrap gap-2">
                        @foreach ($logins['accounts'] as $acc)
                            @php [$tone] = $roleChip($acc['role']); @endphp
                            <span class="inline-flex items-center gap-2 h-8 pl-1 pr-3 rounded-full border border-gray-100 bg-gray-50/80 max-w-full" title="{{ $acc['email'] }} · last {{ $acc['last'] }}">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-semibold flex-shrink-0" style="background: {{ $tones[$tone][0] }}; color: {{ $tones[$tone][1] }}">{{ strtoupper(mb_substr($acc['name'], 0, 1)) }}</span>
                                <span class="text-xs font-medium text-gray-700 truncate">{{ $acc['name'] }}</span>
                                <span class="text-[11px] font-semibold text-gray-400 tabular-nums">{{ $acc['count'] }}</span>
                            </span>
                        @endforeach
                    </div>
                @endif

                <div class="border-t border-gray-100 overflow-x-auto max-h-[32rem] overflow-y-auto">
                    <table class="w-full text-sm min-w-[760px]">
                        <thead class="sticky top-0 z-10 bg-white text-gray-400 text-[11px] uppercase tracking-wide">
                            <tr class="border-b border-gray-100">
                                <th class="px-5 py-2.5 text-left font-medium">Time</th>
                                <th class="px-3 py-2.5 text-left font-medium">Who</th>
                                <th class="px-3 py-2.5 text-left font-medium">Email</th>
                                <th class="px-3 py-2.5 text-left font-medium">Signed in with</th>
                                <th class="px-3 py-2.5 text-left font-medium">Device</th>
                                <th class="px-5 py-2.5 text-left font-medium">IP</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($loginDays as $day => $rows)
                                <tr wire:key="login-day-{{ $day }}">
                                    <td colspan="6" class="px-5 pt-3.5 pb-1.5 bg-gray-50/60 border-b border-gray-100">
                                        <span class="text-xs font-semibold text-gray-700">{{ $dayName($day) }}</span>
                                        <span class="text-[11px] text-gray-400 ml-1">· {{ $rows->count() }} {{ \Illuminate\Support\Str::plural('login', $rows->count()) }}</span>
                                    </td>
                                </tr>
                                @foreach ($rows as $row)
                                    @php
                                        [$rTone, $rLabel]       = $roleChip($row['role']);
                                        [$mTone, $mIcon, $mLbl] = $methodChip[$row['method']] ?? ['gray', 'question-mark-circle', ucfirst((string) $row['method'] ?: 'Unknown')];
                                    @endphp
                                    <tr wire:key="login-{{ $row['id'] }}" class="border-b border-gray-50 hover:bg-gray-50/70">
                                        <td class="px-5 py-3 whitespace-nowrap">
                                            <p class="font-semibold text-gray-900 tabular-nums">{{ $row['time'] }}</p>
                                            <p class="text-[11px] text-gray-400">{{ $row['ago'] }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <span class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-semibold flex-shrink-0" style="background: {{ $tones[$rTone][0] }}; color: {{ $tones[$rTone][1] }}">{{ strtoupper(mb_substr($row['name'], 0, 1)) }}</span>
                                                <div class="min-w-0">
                                                    <p class="font-medium text-gray-800 truncate max-w-[11rem]" title="{{ $row['name'] }}">{{ $row['name'] }}</p>
                                                    <span class="pf-chip pf-{{ $rTone }} mt-0.5" style="height:1.25rem">{{ $rLabel }}</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-gray-600 truncate max-w-[14rem]" title="{{ $row['email'] }}">{{ $row['email'] }}</td>
                                        <td class="px-3 py-3"><span class="pf-chip pf-{{ $mTone }}"><x-icon name="{{ $mIcon }}" />{{ $mLbl }}</span></td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-2 text-gray-600 whitespace-nowrap">
                                                <x-icon name="{{ $row['mobile'] ? 'device-phone-mobile' : 'computer-desktop' }}" class="w-4 h-4 text-gray-400" />
                                                {{ $row['browser'] }}@if ($row['os'])<span class="text-gray-400"> · {{ $row['os'] }}</span>@endif
                                            </div>
                                        </td>
                                        <td class="px-5 py-3 text-gray-500 font-mono text-xs whitespace-nowrap">{{ $row['ip'] }}</td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400">No sign-ins in the last 15 days yet — every login from now on is listed here.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        @if ($isMain)
            {{-- ═══════════════════════════ PLATFORM ═══════════════════════════ --}}
            <section data-pf="platform" x-show="tab === 'platform'" style="display: none">
                <div class="pf-main wide-first">

                    {{-- Platform details --}}
                    <div class="sa-card p-5 min-w-0 flex flex-col">
                        <div class="flex items-start gap-3 mb-6">
                            <span class="pf-icon"><x-icon name="building-office-2" /></span>
                            <div class="flex-1 min-w-0">
                                <h3 class="sa-card-title">Platform Details</h3>
                                <p class="sa-card-sub">Who runs {{ $details['platform_name'] ?? 'SuperLMS' }} — invoices use these where Store's own settings are blank</p>
                            </div>
                            @if (!empty($details['company_gstin']))
                                <span class="pf-chip pf-emerald hidden sm:inline-flex"><x-icon name="check-badge" />GST registered</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5">
                            @foreach ($detailFields as $key => [$icon, $placeholder, $type, $cols])
                                <div class="{{ $cols === 2 ? 'sm:col-span-2' : '' }} min-w-0">
                                    <label class="pf-field {{ $errors->has('details.' . $key) ? 'has-error' : '' }}">
                                        <x-icon name="{{ $icon }}" class="w-4 h-4 text-gray-400 absolute left-4 {{ $type === 'textarea' ? 'top-3.5' : 'top-1/2 -translate-y-1/2' }} pointer-events-none" />
                                        @if ($type === 'textarea')
                                            <textarea wire:model="details.{{ $key }}" rows="3" maxlength="500" placeholder="{{ $placeholder }}" class="pf-input pl-11"></textarea>
                                        @else
                                            <input type="{{ $type }}" wire:model="details.{{ $key }}" placeholder="{{ $placeholder }}"
                                                @if (in_array($key, ['company_cin', 'company_gstin'], true)) style="text-transform: uppercase" maxlength="{{ $key === 'company_cin' ? 25 : 20 }}" @endif
                                                @if ($type === 'tel') inputmode="tel" maxlength="16" @endif
                                                class="pf-input pl-11 {{ in_array($key, ['company_cin', 'company_gstin', 'company_mobile'], true) ? 'tabular-nums tracking-wide' : '' }}">
                                        @endif
                                        <span class="pf-label">{{ $detailLabels[$key] }}</span>
                                    </label>
                                    @error('details.' . $key) <p class="pf-err">{{ $message }}</p> @enderror
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 pt-5 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                            <p class="text-xs text-gray-400">CIN 21 characters · GSTIN 15 · mobile 10 digits</p>
                            <button wire:click="saveDetails" wire:loading.attr="disabled" wire:target="saveDetails" class="sk-cta" style="height: 2.75rem; padding: 0 22px">
                                <span wire:loading.remove wire:target="saveDetails">Save Details</span>
                                <span wire:loading wire:target="saveDetails">Saving…</span>
                            </button>
                        </div>
                    </div>

                    {{-- Platform logo --}}
                    <div class="sa-card p-5 min-w-0 flex flex-col">
                        <div class="flex items-start gap-3 mb-5">
                            <span class="pf-icon"><x-icon name="photo" /></span>
                            <div class="flex-1 min-w-0">
                                <h3 class="sa-card-title">Platform Logo</h3>
                                <p class="sa-card-sub">Login pages, sidebars and the website · PNG, JPG, WEBP or SVG up to 2 MB</p>
                            </div>
                            <span class="pf-chip {{ $customLogo ? 'pf-teal' : 'pf-gray' }} pf-dot">{{ $customLogo ? 'Custom' : 'Original' }}</span>
                        </div>

                        <div class="rounded-2xl p-4 flex items-center justify-center gap-4 min-h-[9.5rem]" style="background:#f6f8f7">
                            <div class="text-center">
                                <div class="w-24 h-24 rounded-2xl bg-white flex items-center justify-center p-2" style="box-shadow: 0 1px 2px rgba(12,38,32,.05)">
                                    <img src="{{ $logoUrl }}" alt="Current logo" class="max-w-full max-h-full object-contain">
                                </div>
                                <p class="text-[11px] text-gray-400 mt-1.5">Current</p>
                            </div>
                            @if ($logoPreview)
                                <x-icon name="arrow-right" class="w-5 h-5 text-gray-300 flex-shrink-0" />
                                <div class="text-center">
                                    <div class="w-24 h-24 rounded-2xl bg-white flex items-center justify-center p-2" style="border: 2px solid #5eead4">
                                        <img src="{{ $logoPreview }}" alt="New logo" class="max-w-full max-h-full object-contain">
                                    </div>
                                    <p class="text-[11px] font-medium mt-1.5" style="color:#0f766e">New</p>
                                </div>
                            @endif
                        </div>

                        <div wire:loading wire:target="logo" class="mt-3 text-xs text-gray-400">Uploading…</div>
                        @error('logo') <p class="mt-3 text-xs text-rose-600">{{ $message }}</p> @enderror

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <label class="pf-ghost">
                                <x-icon name="arrow-up-tray" class="w-4 h-4" />
                                Choose image or SVG
                                <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
                            </label>
                            @if ($logo && !$errors->has('logo'))
                                <button wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo" class="sk-cta">
                                    <span wire:loading.remove wire:target="saveLogo">Save Logo</span>
                                    <span wire:loading wire:target="saveLogo">Saving…</span>
                                </button>
                            @endif
                            @if ($customLogo)
                                <button wire:click="resetLogo" wire:confirm="Go back to the original SuperLMS logo?" class="text-sm text-gray-500 hover:text-teal-700 px-2">Use original logo</button>
                            @endif
                        </div>

                        <div class="mt-auto pt-5">
                            <div class="rounded-2xl px-4 py-3 min-w-0" style="background:#effaf7">
                                <p class="text-[11px] font-medium" style="color:#0d9488">Website</p>
                                <a href="{{ $platform['website'] }}" target="_blank" rel="noopener"
                                    class="mt-0.5 flex items-center gap-1.5 text-sm font-semibold min-w-0" style="color:#0f4a43">
                                    <span class="truncate">{{ $platform['website'] }}</span>
                                    <x-icon name="arrow-top-right-on-square" class="w-3.5 h-3.5 flex-shrink-0" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ═══════════════════════════ WEBSITE ═══════════════════════════ --}}
            <section data-pf="website" x-show="tab === 'website'" style="display: none">
                <div class="sa-card min-w-0 overflow-hidden">
                    <div class="p-5 flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <span class="pf-icon"><x-icon name="globe-alt" /></span>
                            <div class="min-w-0">
                                <h3 class="sa-card-title">Website Data</h3>
                                <p class="sa-card-sub">What superlms.in shows and has collected</p>
                            </div>
                        </div>
                        {{-- The look every visitor gets; both designs are kept in full. --}}
                        <div class="flex items-center gap-1 p-1 rounded-full flex-shrink-0" style="background:#eef2f1" title="Which design superlms.in is shown in">
                            <span class="hidden sm:inline px-2 text-[11px] font-medium text-gray-500">Design</span>
                            @foreach (['new' => 'New', 'classic' => 'Classic'] as $key => $label)
                                <button type="button" wire:click="setWebsiteDesign('{{ $key }}')"
                                    @if ($websiteDesign !== $key) wire:confirm="Show superlms.in in the {{ strtolower($label) }} design to every visitor?" @endif
                                    class="h-8 px-3.5 rounded-full text-xs font-semibold transition-colors {{ $websiteDesign === $key ? 'bg-white shadow-sm' : 'text-gray-500 hover:text-gray-800' }}"
                                    @if ($websiteDesign === $key) style="color:#0f766e" @endif>{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <table class="an-cmp" style="margin: 0 20px 8px; width: calc(100% - 40px)">
                        <thead><tr><th>What</th><th>Count</th><th>Latest</th><th></th></tr></thead>
                        <tbody>
                            @foreach ($websiteData as $row)
                                <tr>
                                    <td><span class="flex items-center gap-2.5"><span class="pf-icon" style="width:28px;height:28px;border-radius:9px"><x-icon name="{{ $row['icon'] }}" /></span>{{ $row['label'] }}</span></td>
                                    <td class="font-semibold">{{ $row['count'] }}</td>
                                    <td class="text-gray-500">{{ $row['latest'] ?: 'None yet' }}</td>
                                    <td style="width: 1%">
                                        @if ($row['url'])
                                            <a href="{{ $row['url'] }}" class="pf-round" title="Open {{ $row['label'] }}" aria-label="Open {{ $row['label'] }}"><x-icon name="arrow-right" /></a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</div>
