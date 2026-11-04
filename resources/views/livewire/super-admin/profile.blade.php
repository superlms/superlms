<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')

    {{-- Profile's own look (2026-11-04): every field carries its name on its
         border, and chips are one family — a soft tint, a hairline border,
         an icon or a dot. --}}
    <style>
        /* In Tailwind's components layer, so utilities (pl-10, pr-11 …) still win. */
        @layer components {
        .pf-field { position: relative; display: block; }
        .pf-input {
            width: 100%; border: 1px solid #e5e7eb; border-radius: .75rem; background: #fff;
            padding: .8rem .95rem .7rem; font-size: .875rem; line-height: 1.25rem; color: #111827;
            transition: border-color .15s, box-shadow .15s;
        }
        .pf-input::placeholder { color: #c3c7cf; }
        .pf-input:hover { border-color: #d5d9e0; }
        .pf-input:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 4px rgba(139, 92, 246, .12); }
        textarea.pf-input { resize: vertical; min-height: 5.75rem; }
        .pf-label {
            position: absolute; left: .7rem; top: 0; transform: translateY(-50%);
            max-width: calc(100% - 1.4rem); padding: 0 .35rem; background: #fff;
            font-size: .6875rem; font-weight: 600; line-height: 1rem; letter-spacing: .01em; color: #6b7280;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; pointer-events: none; transition: color .15s;
        }
        .pf-field:focus-within .pf-label { color: #7c3aed; }
        .pf-field.has-error .pf-input { border-color: #fda4af; }
        .pf-field.has-error .pf-label { color: #e11d48; }
        .pf-err { margin-top: .35rem; font-size: .75rem; line-height: 1rem; color: #e11d48; }

        .pf-chip {
            display: inline-flex; align-items: center; gap: .35rem; height: 1.5rem; padding: 0 .6rem;
            border-radius: 9999px; border: 1px solid transparent; white-space: nowrap;
            font-size: .6875rem; font-weight: 600; line-height: 1; letter-spacing: .01em;
        }
        .pf-chip > svg { width: .8rem; height: .8rem; flex-shrink: 0; }
        .pf-chip.pf-dot::before { content: ''; width: .375rem; height: .375rem; border-radius: 9999px; background: currentColor; }
        .pf-chip.pf-violet  { background: #f5f3ff; color: #6d28d9; border-color: #e9e3fe; }
        .pf-chip.pf-sky     { background: #f0f9ff; color: #0369a1; border-color: #dff1fd; }
        .pf-chip.pf-emerald { background: #ecfdf5; color: #047857; border-color: #d3f5e5; }
        .pf-chip.pf-amber   { background: #fffbeb; color: #b45309; border-color: #fdefc8; }
        .pf-chip.pf-rose    { background: #fff1f2; color: #be123c; border-color: #ffe1e5; }
        .pf-chip.pf-gray    { background: #f8fafc; color: #475569; border-color: #e9edf2; }
        .pf-chip.pf-glass   { background: rgba(255, 255, 255, .08); color: #ede9fe; border-color: rgba(255, 255, 255, .16); }

        .pf-icon { width: 2.25rem; height: 2.25rem; border-radius: .75rem; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
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
        $accountRows[] = ['clock', 'Last login', $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never'];

        $tones = [
            'violet'  => ['#f5f3ff', '#7c3aed'],
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
        $roleChip = fn ($role) => $role === 'super-admin' ? ['violet', 'Super Admin'] : ['sky', \Illuminate\Support\Str::headline((string) $role ?: 'Unknown')];
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
    @endphp

    {{-- ══════════════════════════════ HEADER ══════════════════════════════ --}}
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
            <div class="min-w-0 sm:flex sm:items-baseline sm:gap-2">
                <h1 class="min-w-0 text-lg sm:text-xl font-bold text-gray-900 truncate">My Profile</h1>
                <p class="text-xs text-gray-400 mt-0.5 sm:mt-0 truncate">{{ $roleLabel }} · {{ $user->email }}</p>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">

        {{-- ═══════════════ Account (dark) · OTP numbers · password ═══════════════
             Three across from xl; below that the account card takes a row of
             its own and the other two share the next. --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">

            {{-- Account --}}
            <div class="sa-card-dark p-5 sm:p-6 md:col-span-2 xl:col-span-1 min-w-0 flex flex-col">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="relative flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl overflow-hidden bg-white/10 ring-4 ring-white/10 flex items-center justify-center">
                            @if ($photoUrl)
                                <img src="{{ $photoUrl }}" class="w-full h-full object-cover" alt="">
                            @else
                                <span class="text-3xl font-semibold text-white">{{ $initial }}</span>
                            @endif
                        </div>
                        <label title="Change photo" aria-label="Change photo"
                            class="absolute -bottom-1.5 -right-1.5 w-8 h-8 rounded-full bg-white text-indigo-900 shadow-lg flex items-center justify-center cursor-pointer hover:bg-violet-50 transition-colors">
                            <x-icon name="camera" class="w-4 h-4" />
                            <input type="file" wire:model="photo" accept="image/*" class="hidden">
                        </label>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-lg font-semibold text-white leading-tight truncate" title="{{ $user->name }}">{{ $user->name }}</h2>
                        <p class="text-sm text-white/60 truncate mt-0.5" title="{{ $user->email }}">{{ $user->email }}</p>
                        <span class="pf-chip pf-glass mt-2">
                            <x-icon name="shield-check" />
                            {{ $roleLabel }}
                        </span>
                    </div>
                </div>

                <div wire:loading wire:target="photo" class="mt-4 text-xs text-white/60">Uploading photo…</div>
                @error('photo') <p class="mt-4 text-xs text-rose-300">{{ $message }}</p> @enderror
                @if ($photo && !$errors->has('photo'))
                    <div class="mt-4 flex items-center gap-2" wire:loading.remove wire:target="photo">
                        <button wire:click="savePhoto" wire:loading.attr="disabled" wire:target="savePhoto"
                            class="px-3.5 py-2 rounded-lg bg-white text-indigo-950 text-sm font-semibold hover:bg-violet-50 disabled:opacity-60 transition-colors">
                            <span wire:loading.remove wire:target="savePhoto">Save photo</span>
                            <span wire:loading wire:target="savePhoto">Saving…</span>
                        </button>
                        <button wire:click="cancelPhoto" class="px-3 py-2 rounded-lg text-sm text-white/70 hover:text-white hover:bg-white/10 transition-colors">Cancel</button>
                    </div>
                @endif

                {{-- Where the sign-in code goes, and how often this account came in. --}}
                <div class="mt-6 grid grid-cols-3 gap-2.5">
                    <div class="rounded-xl bg-white/[.06] ring-1 ring-white/[.08] px-3 py-2.5 min-w-0">
                        <p class="text-[11px] text-white/50 truncate">OTP numbers</p>
                        <p class="text-base font-semibold tabular-nums mt-0.5">{{ count($numbers) }}<span class="text-xs font-normal text-white/40"> / {{ $maxNumbers }}</span></p>
                    </div>
                    <div class="rounded-xl bg-white/[.06] ring-1 ring-white/[.08] px-3 py-2.5 min-w-0">
                        <p class="text-[11px] text-white/50 truncate">Code by</p>
                        <p class="text-sm font-semibold mt-1 leading-snug break-words">{{ count($numbers) ? 'Email + WhatsApp' : 'Email' }}</p>
                    </div>
                    @php $mine = collect($logins['rows'])->where('email', \App\Models\User::cleanEmail((string) $user->email))->count(); @endphp
                    <div class="rounded-xl bg-white/[.06] ring-1 ring-white/[.08] px-3 py-2.5 min-w-0">
                        <p class="text-[11px] text-white/50 truncate" title="Your sign-ins in the last 15 days">Logins</p>
                        <p class="text-base font-semibold tabular-nums mt-0.5">{{ $mine }}<span class="text-xs font-normal text-white/40"> · 15d</span></p>
                    </div>
                </div>

                <div class="mt-auto pt-6">
                    <dl class="pt-5 border-t border-white/10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-x-8 gap-y-3 text-sm">
                        @foreach ($accountRows as [$icon, $label, $value])
                            <div class="flex items-center justify-between gap-3 min-w-0">
                                <dt class="flex items-center gap-2 text-white/60 whitespace-nowrap">
                                    <x-icon name="{{ $icon }}" class="w-4 h-4 text-white/40" />
                                    {{ $label }}
                                </dt>
                                <dd class="font-semibold tabular-nums text-right truncate" title="{{ $value }}">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                @if (!empty($grantedAccess))
                    <div class="mt-5 pt-5 border-t border-white/10">
                        <p class="text-[11px] font-medium uppercase tracking-wide text-white/50 mb-2">Your access</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach ($grantedAccess as $perm)
                                <span class="pf-chip pf-glass">
                                    <x-icon name="check" />
                                    {{ $perm }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- OTP numbers --}}
            <div id="otp-numbers" class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start gap-3 mb-5">
                    <span class="pf-icon" style="background:#f5f3ff;color:#7c3aed"><x-icon name="device-phone-mobile" class="w-4 h-4" /></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="sa-card-title">OTP Numbers</h3>
                        <p class="sa-card-sub">The sign-in code goes to your email and, on WhatsApp, to the primary number only</p>
                    </div>
                    <span class="pf-chip pf-violet tabular-nums">{{ count($numbers) }} of {{ $maxNumbers }}</span>
                </div>

                <div class="space-y-2.5">
                    @forelse ($numbers as $n)
                        @php $key = $n['primary'] ? 'p' : (string) $n['id']; @endphp
                        <div wire:key="otp-number-{{ $key }}"
                            class="rounded-xl border px-3.5 py-3 {{ $editing === $key ? 'border-violet-200 bg-white' : ($n['primary'] ? 'border-violet-100 bg-violet-50/60' : 'border-gray-100 bg-gray-50/70') }}">
                            @if ($editing === $key)
                                <div class="flex items-start gap-2 pt-1.5">
                                    <div class="flex-1 min-w-0">
                                        <label class="pf-field {{ $errors->has('editNumber') ? 'has-error' : '' }}">
                                            <input type="tel" wire:model="editNumber" wire:keydown.enter="saveEdit" inputmode="tel" maxlength="16" class="pf-input">
                                            <span class="pf-label">{{ $n['primary'] ? 'Primary number' : 'Number' }}</span>
                                        </label>
                                        @error('editNumber') <p class="pf-err">{{ $message }}</p> @enderror
                                    </div>
                                    <button wire:click="saveEdit" wire:loading.attr="disabled"
                                        class="h-11 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">Save</button>
                                    <button wire:click="cancelEdit" title="Cancel" aria-label="Cancel"
                                        class="w-11 h-11 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition-colors">
                                        <x-icon name="x-mark" class="w-4 h-4" />
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-9 h-9 rounded-xl bg-white shadow-sm ring-1 ring-black/[.03] flex items-center justify-center flex-shrink-0 {{ $n['primary'] ? 'text-violet-600' : 'text-gray-400' }}">
                                        <x-icon name="device-phone-mobile" class="w-4 h-4" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 tabular-nums truncate">{{ \App\Support\OtpNumbers::format($n['phone']) }}</p>
                                        <div class="mt-1">
                                            @if ($n['primary'])
                                                <span class="pf-chip pf-emerald pf-dot">Primary · gets the OTP</span>
                                            @else
                                                <button wire:click="makePrimary({{ $n['id'] }})" class="pf-chip pf-gray hover:border-violet-200 hover:bg-violet-50 hover:text-violet-700 transition-colors">
                                                    <x-icon name="star" />
                                                    Make primary
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-0.5 flex-shrink-0">
                                        <button wire:click="startEdit('{{ $key }}')" title="Edit" aria-label="Edit"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-800 hover:bg-white transition-colors">
                                            <x-icon name="pencil-square" class="w-4 h-4" />
                                        </button>
                                        <button wire:click="deleteNumber('{{ $key }}')"
                                            wire:confirm="Delete {{ \App\Support\OtpNumbers::format($n['phone']) }}?{{ $n['primary'] ? ' The next number becomes primary.' : '' }}"
                                            title="Delete" aria-label="Delete"
                                            class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-rose-600 hover:bg-white transition-colors">
                                            <x-icon name="trash" class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
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
                                <button wire:click="addNumber" wire:loading.attr="disabled" wire:target="addNumber"
                                    class="h-11 inline-flex items-center gap-1 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">
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
                <div class="flex items-start gap-3 mb-5">
                    <span class="pf-icon" style="background:#f3f4f6;color:#111827"><x-icon name="lock-closed" class="w-4 h-4" /></span>
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
                                    class="pf-input pr-11">
                                <span class="pf-label">{{ $label }}</span>
                                <button type="button" wire:click="togglePasswordVisibility('{{ $field }}')"
                                    title="{{ $shown ? 'Hide' : 'Show' }}" aria-label="{{ $shown ? 'Hide' : 'Show' }} password"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-gray-400 hover:text-gray-700">
                                    <x-icon name="{{ $shown ? 'eye-slash' : 'eye' }}" class="w-4 h-4" />
                                </button>
                            </label>
                            @error($model) <p class="pf-err">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-auto pt-5">
                    <button wire:click="updatePassword" wire:loading.attr="disabled" wire:target="updatePassword"
                        class="w-full h-11 rounded-xl bg-gray-900 hover:bg-gray-800 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Updating…</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════ Login activity — last 15 days ═══════════════ --}}
        <div class="sa-card min-w-0 overflow-hidden">
            <div class="p-5 flex flex-col lg:flex-row lg:items-end gap-5">
                <div class="flex items-start gap-3 lg:w-80 flex-shrink-0">
                    <span class="pf-icon" style="background:#ecfdf5;color:#059669"><x-icon name="finger-print" class="w-4 h-4" /></span>
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Login Activity</h3>
                        <p class="sa-card-sub">{{ $logins['all'] ? 'Every sign-in to the Super Admin panel' : 'Your sign-ins to the Super Admin panel' }} · since {{ $logins['since'] }}</p>
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span class="pf-chip pf-violet"><x-icon name="arrow-right-on-rectangle" />{{ $logins['total'] }} {{ \Illuminate\Support\Str::plural('login', $logins['total']) }}</span>
                            <span class="pf-chip pf-emerald pf-dot">{{ $logins['today'] }} today</span>
                            @if ($logins['all'])
                                <span class="pf-chip pf-sky"><x-icon name="users" />{{ count($logins['accounts']) }} {{ \Illuminate\Support\Str::plural('account', count($logins['accounts'])) }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Sign-ins a day, the last 15 days --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-end gap-1 sm:gap-1.5 h-20">
                        @foreach ($logins['days'] as $d)
                            <div class="flex-1 min-w-0 h-full flex flex-col justify-end" title="{{ $d['label'] }} · {{ $d['count'] }} {{ \Illuminate\Support\Str::plural('login', $d['count']) }}">
                                <div class="w-full rounded-md transition-all"
                                    style="height: {{ $d['count'] ? max(10, round($d['count'] / $dayMax * 100)) : 6 }}%; background: {{ $d['count'] ? ($d['today'] ? '#7c3aed' : '#c4b5fd') : '#f1f2f6' }}"></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-1.5 flex gap-1 sm:gap-1.5">
                        @foreach ($logins['days'] as $d)
                            <span class="flex-1 min-w-0 text-center text-[10px] tabular-nums {{ $d['today'] ? 'font-semibold text-violet-600' : 'text-gray-400' }}">{{ $d['short'] }}</span>
                        @endforeach
                    </div>
                </div>
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

            <div class="border-t border-gray-100 overflow-x-auto max-h-[28rem] overflow-y-auto">
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

        @if ($isMain)
            {{-- ═══════════════ Platform details · logo ═══════════════ --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                {{-- Platform details --}}
                <div class="sa-card p-5 xl:col-span-2 min-w-0 flex flex-col">
                    <div class="flex items-start gap-3 mb-6">
                        <span class="pf-icon" style="background:#f0f9ff;color:#0284c7"><x-icon name="building-office-2" class="w-4 h-4" /></span>
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
                                    <x-icon name="{{ $icon }}" class="w-4 h-4 text-gray-400 absolute left-3.5 {{ $type === 'textarea' ? 'top-3.5' : 'top-1/2 -translate-y-1/2' }} pointer-events-none" />
                                    @if ($type === 'textarea')
                                        <textarea wire:model="details.{{ $key }}" rows="3" maxlength="500" placeholder="{{ $placeholder }}" class="pf-input pl-10"></textarea>
                                    @else
                                        <input type="{{ $type }}" wire:model="details.{{ $key }}" placeholder="{{ $placeholder }}"
                                            @if (in_array($key, ['company_cin', 'company_gstin'], true)) style="text-transform: uppercase" maxlength="{{ $key === 'company_cin' ? 25 : 20 }}" @endif
                                            @if ($type === 'tel') inputmode="tel" maxlength="16" @endif
                                            class="pf-input pl-10 {{ in_array($key, ['company_cin', 'company_gstin', 'company_mobile'], true) ? 'tabular-nums tracking-wide' : '' }}">
                                    @endif
                                    <span class="pf-label">{{ $detailLabels[$key] }}</span>
                                </label>
                                @error('details.' . $key) <p class="pf-err">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6 pt-5 border-t border-gray-100 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
                        <p class="text-xs text-gray-400">CIN 21 characters · GSTIN 15 · mobile 10 digits</p>
                        <button wire:click="saveDetails" wire:loading.attr="disabled" wire:target="saveDetails"
                            class="h-11 px-5 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                            <span wire:loading.remove wire:target="saveDetails">Save Details</span>
                            <span wire:loading wire:target="saveDetails">Saving…</span>
                        </button>
                    </div>
                </div>

                {{-- Platform logo --}}
                <div class="sa-card p-5 min-w-0 flex flex-col">
                    <div class="flex items-start gap-3 mb-5">
                        <span class="pf-icon" style="background:#f5f3ff;color:#7c3aed"><x-icon name="photo" class="w-4 h-4" /></span>
                        <div class="flex-1 min-w-0">
                            <h3 class="sa-card-title">Platform Logo</h3>
                            <p class="sa-card-sub">Login pages, sidebars and the website · PNG, JPG, WEBP or SVG up to 2 MB</p>
                        </div>
                        <span class="pf-chip {{ $customLogo ? 'pf-violet' : 'pf-gray' }} pf-dot">{{ $customLogo ? 'Custom' : 'Original' }}</span>
                    </div>

                    <div class="rounded-xl bg-gray-50 ring-1 ring-gray-100 p-4 flex items-center justify-center gap-4 min-h-[9.5rem]">
                        <div class="text-center">
                            <div class="w-24 h-24 rounded-xl bg-white ring-1 ring-gray-100 flex items-center justify-center p-2">
                                <img src="{{ $logoUrl }}" alt="Current logo" class="max-w-full max-h-full object-contain">
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1.5">Current</p>
                        </div>
                        @if ($logoPreview)
                            <x-icon name="arrow-right" class="w-5 h-5 text-gray-300 flex-shrink-0" />
                            <div class="text-center">
                                <div class="w-24 h-24 rounded-xl bg-white border-2 border-violet-300 flex items-center justify-center p-2">
                                    <img src="{{ $logoPreview }}" alt="New logo" class="max-w-full max-h-full object-contain">
                                </div>
                                <p class="text-[11px] font-medium text-violet-600 mt-1.5">New</p>
                            </div>
                        @endif
                    </div>

                    <div wire:loading wire:target="logo" class="mt-3 text-xs text-gray-400">Uploading…</div>
                    @error('logo') <p class="mt-3 text-xs text-rose-500">{{ $message }}</p> @enderror

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        <label class="h-10 inline-flex items-center gap-1.5 px-3.5 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                            <x-icon name="arrow-up-tray" class="w-4 h-4" />
                            Choose image or SVG
                            <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
                        </label>
                        @if ($logo && !$errors->has('logo'))
                            <button wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo"
                                class="h-10 px-4 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">
                                <span wire:loading.remove wire:target="saveLogo">Save Logo</span>
                                <span wire:loading wire:target="saveLogo">Saving…</span>
                            </button>
                        @endif
                        @if ($customLogo)
                            <button wire:click="resetLogo" wire:confirm="Go back to the original SuperLMS logo?"
                                class="h-10 px-3 text-sm text-gray-500 hover:text-gray-800">Use original logo</button>
                        @endif
                    </div>

                    <div class="mt-auto pt-5">
                        <div class="rounded-xl bg-violet-50/70 ring-1 ring-violet-100 px-3.5 py-3 min-w-0">
                            <p class="text-[11px] font-medium text-violet-500">Website</p>
                            <a href="{{ $platform['website'] }}" target="_blank" rel="noopener"
                                class="mt-0.5 flex items-center gap-1.5 text-sm font-semibold text-violet-800 hover:text-violet-950 min-w-0">
                                <span class="truncate">{{ $platform['website'] }}</span>
                                <x-icon name="arrow-top-right-on-square" class="w-3.5 h-3.5 flex-shrink-0" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ═══════════════ Platform at a glance ═══════════════ --}}
            <div>
                <div class="flex items-baseline justify-between gap-3 mb-3">
                    <h3 class="sa-card-title">Platform at a Glance</h3>
                    <p class="text-xs text-gray-400 truncate">{{ $platform['name'] }}</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-5">
                    @foreach ([
                        ['Schools',       $platform['schools'],  'On ' . $platform['name'],                'building-office-2', 'violet',  route('super-admin.schools')],
                        ['Active schools', $platform['active'],  'of ' . $platform['schools'] . ' schools', 'check-badge',       'emerald', route('super-admin.schools')],
                        ['Students',      $platform['students'], 'Across all schools',                     'users',             'sky',     route('super-admin.students')],
                        ['Teachers',      $platform['teachers'], 'Across all schools',                     'academic-cap',      'amber',   route('super-admin.teachers')],
                        ['School admins', $platform['admins'],   'Admins and sub-admins',                  'identification',    'rose',    null],
                        ['Super admins',  $platform['super'],    'You and your team',                      'shield-check',      'ink',     null],
                    ] as [$label, $value, $sub, $icon, $tone, $href])
                        @php [$tint, $ink] = $tones[$tone]; @endphp
                        <{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" @endif class="sa-card px-4 py-4 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-medium text-gray-500 truncate" title="{{ $label }}">{{ $label }}</p>
                                <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: {{ $tint }}; color: {{ $ink }}">
                                    <x-icon name="{{ $icon }}" class="w-3.5 h-3.5" />
                                </span>
                            </div>
                            <p class="text-2xl leading-none font-semibold mt-2.5 tabular-nums text-gray-900">{{ $value }}</p>
                            <p class="text-xs text-gray-400 mt-1.5 truncate" title="{{ $sub }}">{{ $sub }}</p>
                        </{{ $href ? 'a' : 'div' }}>
                    @endforeach
                </div>
            </div>

            {{-- ═══════════════ Website data ═══════════════ --}}
            <div class="sa-card p-5 min-w-0">
                <div class="flex items-start gap-3 mb-5">
                    <span class="pf-icon" style="background:#fffbeb;color:#d97706"><x-icon name="globe-alt" class="w-4 h-4" /></span>
                    <div class="flex-1 min-w-0">
                        <h3 class="sa-card-title">Website Data</h3>
                        <p class="sa-card-sub">What superlms.in shows and has collected</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3">
                    @foreach ($websiteData as $row)
                        <{{ $row['url'] ? 'a' : 'div' }} @if ($row['url']) href="{{ $row['url'] }}" @endif
                            class="group rounded-xl bg-gray-50/80 ring-1 ring-gray-100 px-4 py-3.5 min-w-0 transition-colors {{ $row['url'] ? 'hover:bg-violet-50 hover:ring-violet-100' : '' }}">
                            <div class="flex items-center justify-between gap-2">
                                <span class="w-8 h-8 rounded-lg bg-white shadow-sm ring-1 ring-black/[.03] text-violet-600 flex items-center justify-center flex-shrink-0">
                                    <x-icon name="{{ $row['icon'] }}" class="w-4 h-4" />
                                </span>
                                @if ($row['url'])
                                    <x-icon name="arrow-right" class="w-3.5 h-3.5 text-gray-300 group-hover:text-violet-600 transition-colors flex-shrink-0" />
                                @endif
                            </div>
                            <p class="text-xs font-medium text-gray-500 truncate mt-3">{{ $row['label'] }}</p>
                            <p class="text-xl leading-tight font-semibold text-gray-900 tabular-nums mt-0.5">{{ $row['count'] }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $row['latest'] ? 'Latest ' . $row['latest'] : 'None yet' }}</p>
                        </{{ $row['url'] ? 'a' : 'div' }}>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
