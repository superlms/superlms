<div class="min-h-screen bg-gray-50">
    @include('livewire.super-admin.partials.dash-styles')

    @php
        // ─── Profile on one page in Home's look (2026-11-04): no tabs — the
        //     account (dark card), OTP numbers and password in one row, then
        //     the platform's figures, logo and website data for the main
        //     super admin. ───
        $roleLabel = \Illuminate\Support\Str::headline((string) $user->role);
        $initial   = strtoupper(mb_substr($user->name ?? 'U', 0, 1));
        $photoUrl  = $tempPhotoUrl ?: $user->image;
        $fmtPhone  = fn ($p) => $p ? \App\Support\OtpNumbers::format((string) $p) : '—';

        $details = [['device-phone-mobile', 'Primary mobile', $fmtPhone($user->mobile_number)]];
        if (!empty($user->dob)) {
            $details[] = ['cake', 'Date of birth', \Carbon\Carbon::parse($user->dob)->format('d M Y')];
        }
        if (!empty($user->date_of_joining)) {
            $details[] = ['calendar-days', 'Joined', \Carbon\Carbon::parse($user->date_of_joining)->format('d M Y')];
        }
        if (!empty($user->gender)) {
            $details[] = ['user', 'Gender', ucfirst((string) $user->gender)];
        }
        $details[] = ['clock', 'Last login', $user->last_login_at ? $user->last_login_at->format('d M Y, h:i A') : 'Never'];

        $input = 'w-full rounded-xl border bg-gray-50/70 px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 transition focus:bg-white focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500';

        $tones = [
            'violet'  => ['#f5f3ff', '#7c3aed'],
            'sky'     => ['#f0f9ff', '#0284c7'],
            'emerald' => ['#ecfdf5', '#059669'],
            'amber'   => ['#fffbeb', '#d97706'],
            'rose'    => ['#fff1f2', '#e11d48'],
            'ink'     => ['#f3f4f6', '#111827'],
        ];

        $passwords = [
            ['currentPassword', 'current', 'Current password', $showCurrentPassword, 'current-password'],
            ['newPassword',     'new',     'New password',     $showNewPassword,     'new-password'],
            ['confirmPassword', 'confirm', 'Confirm new password', $showConfirmPassword, 'new-password'],
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
                        <span class="mt-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-white/10 text-violet-200 text-xs font-medium">
                            <x-icon name="shield-check" class="w-3.5 h-3.5" />
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

                {{-- Where the sign-in code goes, at a glance. --}}
                <div class="mt-6 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-white/[.06] px-3.5 py-3 min-w-0">
                        <p class="text-[11px] text-white/50 truncate">OTP numbers</p>
                        <p class="text-lg font-semibold tabular-nums mt-0.5">{{ count($numbers) }} <span class="text-xs font-normal text-white/40">of {{ $maxNumbers }}</span></p>
                    </div>
                    <div class="rounded-xl bg-white/[.06] px-3.5 py-3 min-w-0">
                        <p class="text-[11px] text-white/50 truncate">Sign-in code by</p>
                        <p class="text-sm font-semibold mt-1 leading-snug break-words">{{ count($numbers) ? 'Email + WhatsApp' : 'Email' }}</p>
                    </div>
                </div>

                <div class="mt-auto pt-6">
                    <dl class="pt-5 border-t border-white/10 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-x-8 gap-y-3 text-sm">
                    @foreach ($details as [$icon, $label, $value])
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
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-white/10 text-white/90 text-xs font-medium">{{ $perm }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- OTP numbers --}}
            <div id="otp-numbers" class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">OTP Numbers</h3>
                        <p class="sa-card-sub">The sign-in code goes to your email and, on WhatsApp, to the primary number only</p>
                    </div>
                    <span class="sa-chip tabular-nums">{{ count($numbers) }} / {{ $maxNumbers }}</span>
                </div>

                <div class="space-y-2.5">
                    @forelse ($numbers as $n)
                        @php $key = $n['primary'] ? 'p' : (string) $n['id']; @endphp
                        <div wire:key="otp-number-{{ $key }}"
                            class="rounded-xl px-3.5 py-3 {{ $n['primary'] ? 'bg-violet-50 ring-1 ring-violet-100' : 'bg-gray-50' }}">
                            @if ($editing === $key)
                                <div class="flex items-start gap-2">
                                    <div class="flex-1 min-w-0">
                                        <input type="tel" wire:model="editNumber" wire:keydown.enter="saveEdit" inputmode="tel" maxlength="16"
                                            class="{{ $input }} bg-white {{ $errors->has('editNumber') ? 'border-rose-400' : 'border-gray-200' }}">
                                        @error('editNumber') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                    <button wire:click="saveEdit" wire:loading.attr="disabled"
                                        class="px-3.5 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">Save</button>
                                    <button wire:click="cancelEdit" title="Cancel" aria-label="Cancel"
                                        class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-400 hover:text-gray-700 hover:bg-white transition-colors">
                                        <x-icon name="x-mark" class="w-4 h-4" />
                                    </button>
                                </div>
                            @else
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-9 h-9 rounded-xl bg-white shadow-sm flex items-center justify-center flex-shrink-0 {{ $n['primary'] ? 'text-violet-600' : 'text-gray-400' }}">
                                        <x-icon name="device-phone-mobile" class="w-4 h-4" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 tabular-nums truncate">{{ \App\Support\OtpNumbers::format($n['phone']) }}</p>
                                        @if ($n['primary'])
                                            <p class="text-[11px] font-medium text-violet-600 mt-0.5">Primary · gets the OTP</p>
                                        @else
                                            <button wire:click="makePrimary({{ $n['id'] }})" class="text-[11px] font-medium text-gray-400 hover:text-violet-600 mt-0.5 transition-colors">Make primary</button>
                                        @endif
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

                <div class="mt-auto pt-4">
                    <div class="pt-4 border-t border-gray-100">
                        @if (count($numbers) < $maxNumbers)
                            <label for="new-otp-number" class="block text-xs font-medium text-gray-500 mb-1.5">Add a number</label>
                            <div class="flex gap-2">
                                <input id="new-otp-number" type="tel" wire:model="newNumber" wire:keydown.enter="addNumber" inputmode="tel" maxlength="16"
                                    placeholder="10-digit mobile number"
                                    class="{{ $input }} flex-1 min-w-0 {{ $errors->has('newNumber') ? 'border-rose-400' : 'border-gray-200' }}">
                                <button wire:click="addNumber" wire:loading.attr="disabled" wire:target="addNumber"
                                    class="inline-flex items-center gap-1 px-3.5 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">
                                    <x-icon name="plus" class="w-4 h-4" />
                                    Add
                                </button>
                            </div>
                            @error('newNumber') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        @else
                            <p class="text-xs text-gray-400">All {{ $maxNumbers }} numbers are in use — delete one to add another.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Password --}}
            <div class="sa-card p-5 min-w-0 flex flex-col">
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="sa-card-title">Change Password</h3>
                        <p class="sa-card-sub">Min 8 characters — upper &amp; lower case, a number and a symbol</p>
                    </div>
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:#f5f3ff;color:#7c3aed">
                        <x-icon name="lock-closed" class="w-4 h-4" />
                    </span>
                </div>

                <div class="space-y-3.5">
                    @foreach ($passwords as [$model, $field, $label, $shown, $autocomplete])
                        <div>
                            <label for="pw-{{ $field }}" class="block text-xs font-medium text-gray-500 mb-1.5">{{ $label }}</label>
                            <div class="relative">
                                <input id="pw-{{ $field }}" type="{{ $shown ? 'text' : 'password' }}" wire:model="{{ $model }}" autocomplete="{{ $autocomplete }}"
                                    @if ($field === 'confirm') wire:keydown.enter="updatePassword" @endif
                                    class="{{ $input }} pr-11 {{ $errors->has($model) ? 'border-rose-400' : 'border-gray-200' }}">
                                <button type="button" wire:click="togglePasswordVisibility('{{ $field }}')"
                                    title="{{ $shown ? 'Hide' : 'Show' }}" aria-label="{{ $shown ? 'Hide' : 'Show' }} password"
                                    class="absolute inset-y-0 right-0 w-11 flex items-center justify-center text-gray-400 hover:text-gray-700">
                                    <x-icon name="{{ $shown ? 'eye-slash' : 'eye' }}" class="w-4 h-4" />
                                </button>
                            </div>
                            @error($model) <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>

                <div class="mt-auto pt-5">
                    <button wire:click="updatePassword" wire:loading.attr="disabled" wire:target="updatePassword"
                        class="w-full py-2.5 rounded-xl bg-gray-900 hover:bg-gray-800 disabled:opacity-60 text-white text-sm font-semibold transition-colors">
                        <span wire:loading.remove wire:target="updatePassword">Update Password</span>
                        <span wire:loading wire:target="updatePassword">Updating…</span>
                    </button>
                </div>
            </div>
        </div>

        @if ($isMain)
            {{-- ═══════════════ Platform figures ═══════════════ --}}
            <div>
                <div class="flex items-baseline justify-between gap-3 mb-3">
                    <h3 class="sa-card-title">Platform Details</h3>
                    <p class="text-xs text-gray-400 truncate">{{ $platform['name'] }}</p>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-5">
                    @foreach ([
                        ['Schools',              $platform['schools'],  'On ' . $platform['name'],                'building-office-2', 'violet',  route('super-admin.schools')],
                        ['Active schools',       $platform['active'],   'of ' . $platform['schools'] . ' schools', 'check-badge',       'emerald', route('super-admin.schools')],
                        ['Students',             $platform['students'], 'Across all schools',                     'users',             'sky',     route('super-admin.students')],
                        ['Teachers',             $platform['teachers'], 'Across all schools',                     'academic-cap',      'amber',   route('super-admin.teachers')],
                        ['School admins',        $platform['admins'],   'Admins and sub-admins',                  'identification',    'rose',    null],
                        ['Super admins',         $platform['super'],    'You and your team',                      'shield-check',      'ink',     null],
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

            {{-- ═══════════════ Logo · website data ═══════════════ --}}
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

                {{-- Platform logo --}}
                <div class="sa-card p-5 min-w-0 flex flex-col">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Platform Logo</h3>
                            <p class="sa-card-sub">On the login pages, the sidebars and the website · PNG, JPG, WEBP or SVG, up to 2 MB</p>
                        </div>
                        <span class="sa-chip flex-shrink-0">{{ $customLogo ? 'Custom' : 'Original' }}</span>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4 flex items-center justify-center gap-4 min-h-[9rem]">
                        <div class="text-center">
                            <div class="w-24 h-24 rounded-xl bg-white border border-gray-100 flex items-center justify-center p-2">
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
                        <label class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-gray-200 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 cursor-pointer transition-colors">
                            <x-icon name="arrow-up-tray" class="w-4 h-4" />
                            Choose image or SVG
                            <input type="file" wire:model="logo" accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml" class="hidden">
                        </label>
                        @if ($logo && !$errors->has('logo'))
                            <button wire:click="saveLogo" wire:loading.attr="disabled" wire:target="saveLogo"
                                class="px-3.5 py-2 rounded-xl bg-violet-600 hover:bg-violet-700 disabled:opacity-60 text-white text-sm font-medium transition-colors">
                                <span wire:loading.remove wire:target="saveLogo">Save Logo</span>
                                <span wire:loading wire:target="saveLogo">Saving…</span>
                            </button>
                        @endif
                        @if ($customLogo)
                            <button wire:click="resetLogo" wire:confirm="Go back to the original SuperLMS logo?"
                                class="px-3 py-2 text-sm text-gray-500 hover:text-gray-800">Use original logo</button>
                        @endif
                    </div>

                    <div class="mt-auto pt-4">
                        <div class="rounded-xl bg-violet-50 px-3.5 py-3 min-w-0">
                            <p class="text-xs text-violet-500">Website</p>
                            <a href="{{ $platform['website'] }}" target="_blank" rel="noopener"
                                class="mt-1 flex items-center gap-1.5 text-sm font-semibold text-violet-800 hover:text-violet-950 min-w-0">
                                <span class="truncate">{{ $platform['website'] }}</span>
                                <x-icon name="arrow-top-right-on-square" class="w-3.5 h-3.5 flex-shrink-0" />
                            </a>
                        </div>
                    </div>
                </div>

                {{-- Website data --}}
                <div class="sa-card p-5 xl:col-span-2 min-w-0">
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div class="min-w-0">
                            <h3 class="sa-card-title">Website Data</h3>
                            <p class="sa-card-sub">What superlms.in shows and has collected</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach ($websiteData as $row)
                            <{{ $row['url'] ? 'a' : 'div' }} @if ($row['url']) href="{{ $row['url'] }}" @endif
                                class="group rounded-xl bg-gray-50 px-4 py-3.5 min-w-0 flex items-start gap-3 transition-colors {{ $row['url'] ? 'hover:bg-violet-50' : '' }}">
                                <span class="w-9 h-9 rounded-xl bg-white shadow-sm text-violet-600 flex items-center justify-center flex-shrink-0">
                                    <x-icon name="{{ $row['icon'] }}" class="w-4 h-4" />
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="text-xs font-medium text-gray-500 truncate">{{ $row['label'] }}</p>
                                        @if ($row['url'])
                                            <x-icon name="arrow-right" class="w-3.5 h-3.5 text-gray-300 group-hover:text-violet-600 transition-colors flex-shrink-0" />
                                        @endif
                                    </div>
                                    <p class="text-xl leading-tight font-semibold text-gray-900 tabular-nums mt-1">{{ $row['count'] }}</p>
                                    <p class="text-[11px] text-gray-400 mt-0.5 truncate">{{ $row['latest'] ? 'Latest ' . $row['latest'] : 'None yet' }}</p>
                                </div>
                            </{{ $row['url'] ? 'a' : 'div' }}>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
