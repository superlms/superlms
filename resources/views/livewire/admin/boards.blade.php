<div class="min-h-screen bg-gray-50">
    @php
        $pc = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.') . '%';
        $rs = fn ($n) => '₹' . number_format((float) $n, fmod((float) $n, 1) ? 2 : 0);
    @endphp

    {{-- ══════════ HEADER — the Class 9–12 students, a count a class; Students / Settings ══════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <h1 class="text-lg sm:text-xl font-bold text-gray-900">Boards</h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 divide-x divide-gray-200">
                @foreach ($standards as $s)
                    <span class="{{ $loop->first ? '' : 'pl-4' }}">{{ $s->name }}: <strong class="text-gray-800">{{ (int) ($counts[$s->id] ?? 0) }}</strong></span>
                @endforeach
                <span class="{{ $standards->isEmpty() ? '' : 'pl-4' }}">Total: <strong class="text-gray-800">{{ $total }}</strong></span>
            </div>
        </div>

        <div class="border-t border-gray-200 px-4 sm:px-6">
            <div class="flex gap-1">
                @foreach (['students' => ['Students', 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                           'settings' => ['Settings', 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z']] as $key => [$label, $icon])
                    <button wire:click="setTab('{{ $key }}')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $tab === $key ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                            {{ $label }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($tab === 'students')
            {{-- Filter bar: a class (9 to 12), its section, then a name, number or ID. --}}
            <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
                <div class="flex flex-wrap items-center gap-3">
                    <select wire:model.live="filterStandard"
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 min-w-[120px]">
                        <option value="">All Classes (9–12)</option>
                        @foreach ($standards as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                    </select>
                    <select data-sole-section wire:model.live="filterSection" @disabled($filterStandard === '')
                        class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed min-w-[120px]">
                        <option value="">All Sections</option>
                        @foreach ($filterSections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                    <div class="relative">
                        <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z" /></svg>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name, father, adm. no., Apaar ID"
                            class="text-xs bg-white border border-gray-200 rounded-md pl-8 pr-2.5 py-1.5 text-gray-700 w-64">
                    </div>
                    @if ($filterStandard !== '' || $filterSection !== '' || $search !== '')
                        <button wire:click="clearFilters"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            Clear
                        </button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    @if ($tab === 'settings')
        {{-- ══════════ SETTINGS — the two fee rules of the website's Boards login ══════════ --}}
        <div class="p-4 sm:p-6">
            <div class="max-w-2xl bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-900">Fee needed on the website's Boards login</h2>
                    <p class="text-xs text-gray-500 mt-1">How much of their fee a Class 9–12 student must have paid before the Boards page of your website shows them their board roll number, and before it gives their admit card. Until then they see a message to pay the fee on your school's QR code. 0% asks for nothing.</p>
                </div>
                <form wire:submit.prevent="saveSettings" class="px-5 py-5 space-y-5">
                    @foreach (['rollPercent' => ['Board Roll Number', 'shows when this much of the fee is paid'], 'admitPercent' => ['Admit Card', 'can be downloaded when this much of the fee is paid']] as $field => [$label, $help])
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }} <span class="text-gray-400 font-normal text-xs">— {{ $help }}</span></label>
                            <div class="flex items-center gap-2 max-w-[220px]">
                                <input type="number" min="0" max="100" step="0.01" wire:model.defer="{{ $field }}"
                                    class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error($field) border-red-400 @enderror">
                                <span class="text-sm font-semibold text-gray-500">%</span>
                            </div>
                            @error($field)<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                    <div class="pt-2 border-t border-gray-100 flex items-center justify-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="saveSettings"
                            class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60">Save</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        {{-- ══════════ LIST — Class 9 to 12, by class, section and roll number ══════════ --}}
        <div class="p-4 sm:p-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                @if ($students->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Class</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Parents</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date of Birth</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mobile</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aadhar No</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Apaar / Reg. No</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Board Roll No</th>
                                    <th class="px-3 py-3 whitespace-nowrap text-center text-xs font-semibold text-gray-500 uppercase tracking-wider"><span class="sr-only">Open</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($students as $st)
                                    @php
                                        $sName = $st->full_name ?: ($st->user->name ?? '—');
                                        $sImg  = ($st->user?->image) ?: $st->image;
                                        $brd   = $boardRows[$st->id] ?? null;
                                    @endphp
                                    <tr class="hover:bg-gray-50/70 transition-colors" wire:key="bd-{{ $st->id }}">
                                        <td class="px-3 py-3 text-sm text-gray-400 tabular-nums">{{ $students->firstItem() + $loop->index }}</td>
                                        <td class="px-3 py-3">
                                            <div class="flex items-center gap-3">
                                                @if ($sImg)
                                                    <img src="{{ $sImg }}" class="w-9 h-9 rounded-full object-cover border border-gray-200 flex-shrink-0" alt="">
                                                @else
                                                    <div class="w-9 h-9 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0">
                                                        <span class="text-xs font-semibold text-teal-700">{{ mb_strtoupper(mb_substr($sName, 0, 1)) }}</span>
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="text-sm font-semibold text-gray-900 truncate max-w-[12rem]">{{ $sName }}</p>
                                                    <p class="text-xs text-gray-400 truncate">{{ $st->admission_no ?: '—' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <p class="text-sm text-gray-800">{{ $st->standard->name ?? '—' }}{{ $st->section?->name ? ' - ' . $st->section->name : '' }}</p>
                                            <p class="text-xs text-gray-400 tabular-nums">Roll {{ $st->roll_no ?: '—' }}</p>
                                        </td>
                                        <td class="px-3 py-3">
                                            <p class="text-sm text-gray-800 truncate max-w-[13rem]" title="Father">{{ $st->father_name ?: '—' }}</p>
                                            <p class="text-xs text-gray-400 truncate max-w-[13rem]" title="Mother">{{ $st->mother_name ?: '—' }}</p>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <p class="text-sm text-gray-800 tabular-nums">{{ $st->dob ? $st->dob->format('d M Y') : '—' }}</p>
                                            <p class="text-xs text-gray-400">{{ $st->gender ? ucfirst($st->gender) : '—' }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-sm text-gray-700 tabular-nums whitespace-nowrap">{{ $st->phone ?: '—' }}</td>
                                        <td class="px-3 py-3 text-sm text-gray-700 tabular-nums whitespace-nowrap">{{ $st->aadhar_no ?: '—' }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <p class="text-sm text-gray-800" title="Apaar ID">{{ $st->appar_id ?: '—' }}</p>
                                            <p class="text-xs text-gray-400" title="Registration Number">{{ $st->registration_number ?: '—' }}</p>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <p class="text-sm font-semibold {{ $brd?->roll_number ? 'text-gray-900' : 'text-gray-300' }} tabular-nums">{{ $brd?->roll_number ?: '—' }}</p>
                                            @if ($brd?->admit_card_path)
                                                <p class="text-[11px] font-medium text-teal-700">Admit card ✓</p>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <button wire:click="view({{ $st->id }})" title="Open {{ $sName }}" aria-label="Open {{ $sName }}"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($students->hasPages())
                        <div class="px-5 py-3 border-t border-gray-100">{{ $students->links() }}</div>
                    @endif
                @else
                    <div class="p-12 text-center">
                        <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-icon name="academic-cap" class="w-7 h-7 text-gray-400" />
                        </div>
                        @if ($standards->isEmpty())
                            <p class="text-sm font-medium text-gray-700">No Class 9 to 12 here yet</p>
                            <p class="text-xs text-gray-400 mt-1">Classes named 9, 10, 11 or 12 (or IX, X, XI, XII) on the Standard page show their students here.</p>
                        @else
                            <p class="text-sm font-medium text-gray-700">No students found</p>
                            <p class="text-xs text-gray-400 mt-1">Class 9 to 12 students show here.</p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════ A STUDENT — every detail, the fee, and the board roll number + admit card to save ══════════ --}}
    @if ($viewing)
        @php
            $vf = $viewing['fee']['totals'] ?? ['net' => 0, 'paid' => 0, 'remaining' => 0, 'concession' => 0];
            $vb = $viewing['board'];
        @endphp
        <div class="fixed inset-x-0 bottom-0 top-16 z-50 overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.04] backdrop-blur-[1.5px]" wire:click="closeView"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-xl bg-white shadow-2xl flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 flex-shrink-0">
                    <div class="flex items-center gap-3 min-w-0">
                        @if ($viewing['photo'])
                            <img src="{{ $viewing['photo'] }}" class="w-10 h-10 rounded-full object-cover border border-gray-200 flex-shrink-0" alt="">
                        @else
                            <div class="w-10 h-10 rounded-full bg-teal-50 flex items-center justify-center flex-shrink-0">
                                <span class="text-sm font-semibold text-teal-700">{{ mb_strtoupper(mb_substr($viewing['name'] ?: '?', 0, 1)) }}</span>
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 truncate">{{ $viewing['name'] }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                {{ $viewing['class']['Class'] ?? '—' }}{{ isset($viewing['class']['Section']) ? ' - ' . $viewing['class']['Section'] : '' }}
                                · Adm {{ $viewing['class']['Admission No'] ?? '—' }}
                            </p>
                        </div>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-md text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form id="board-form" wire:submit.prevent="saveBoard" class="flex-1 overflow-y-auto px-6 py-6 space-y-5">
                    {{-- The two fields: board roll number and admit card. --}}
                    <div class="rounded-xl border border-blue-100 bg-blue-50/40 p-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Board Roll Number</label>
                            <input type="text" wire:model.defer="boardRoll" maxlength="40" placeholder="Enter the board roll number"
                                class="w-full px-3.5 py-2.5 border border-gray-300 rounded-md text-sm bg-white focus:ring-1 focus:ring-blue-500 focus:border-blue-500 @error('boardRoll') border-red-400 @enderror">
                            @error('boardRoll')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Admit Card <span class="text-gray-400 font-normal text-xs">(PDF or picture, max 5 MB)</span></label>
                            @if ($vb['admit_url'])
                                <div class="flex items-center justify-between gap-3 bg-white border border-gray-200 rounded-lg px-3 py-2 mb-2">
                                    <a href="{{ $vb['admit_url'] }}" target="_blank" rel="noopener" data-no-download-loader class="text-sm text-blue-600 hover:underline truncate">{{ $vb['admit_name'] }}</a>
                                    <button type="button" wire:click="removeAdmitCard" wire:confirm="Remove this admit card?" class="text-xs font-medium text-red-600 hover:underline flex-shrink-0">Remove</button>
                                </div>
                            @endif
                            <input type="file" wire:model="admitCard" accept="application/pdf,image/jpeg,image/png,image/webp"
                                class="block w-full text-sm text-gray-600 file:mr-3 file:px-3 file:py-1.5 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-white file:text-gray-700 file:ring-1 file:ring-gray-300 hover:file:bg-gray-50">
                            <div wire:loading wire:target="admitCard" class="text-xs text-blue-600 mt-1">Uploading…</div>
                            @error('admitCard')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                        </div>
                        <p class="text-[11px] text-gray-500 leading-relaxed">
                            Fee paid: <strong class="text-gray-700">{{ $pc($vb['paid_pct']) }}</strong>.
                            On the website the roll number shows at {{ $pc($vb['roll_need']) }}
                            @if ($vb['roll_ok'])<span class="text-emerald-600 font-medium">(showing)</span>@else<span class="text-amber-600 font-medium">(not yet)</span>@endif,
                            the admit card at {{ $pc($vb['admit_need']) }}
                            @if ($vb['admit_ok'])<span class="text-emerald-600 font-medium">(showing)</span>@else<span class="text-amber-600 font-medium">(not yet)</span>@endif.
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-gray-200 px-3 py-2.5">
                            <p class="text-[11px] text-gray-500">Total Fee</p>
                            <p class="text-sm font-semibold text-gray-800">{{ $rs($vf['net']) }}</p>
                        </div>
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                            <p class="text-[11px] text-emerald-700">Paid</p>
                            <p class="text-sm font-semibold text-emerald-700">{{ $rs($vf['paid']) }}</p>
                        </div>
                        <div class="rounded-lg border px-3 py-2.5 {{ $vf['remaining'] > 0 ? 'border-red-200 bg-red-50' : 'border-gray-200' }}">
                            <p class="text-[11px] {{ $vf['remaining'] > 0 ? 'text-red-700' : 'text-gray-500' }}">Balance</p>
                            <p class="text-sm font-semibold {{ $vf['remaining'] > 0 ? 'text-red-700' : 'text-gray-800' }}">{{ $rs($vf['remaining']) }}</p>
                        </div>
                    </div>

                    @foreach (['Personal Details' => $viewing['personal'], 'Class Details' => $viewing['class']] as $heading => $rows)
                        <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                            <p class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 rounded-t-xl">{{ $heading }}</p>
                            @foreach ($rows as $label => $value)
                                <div class="px-4 py-2.5 grid grid-cols-5 gap-3">
                                    <p class="col-span-2 text-xs text-gray-500 pt-0.5">{{ $label }}</p>
                                    <p class="col-span-3 text-sm text-gray-800 break-words">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    @php
                        $heads = $viewing['fee']['academic']['rows'] ?? [];
                        $route = $viewing['fee']['transport']['route'] ?? null;
                    @endphp
                    @if ($heads || $route)
                        <div class="rounded-xl border border-gray-200 divide-y divide-gray-100">
                            <p class="px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50 rounded-t-xl">Fee</p>
                            @foreach ($heads as $h)
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <p class="text-sm text-gray-700">{{ $h['fee_name'] }}@if (!empty($h['is_dues'])) <span class="text-gray-400">(Last Year Dues)</span>@endif</p>
                                    <p class="text-sm text-gray-800 tabular-nums">{{ $rs($h['amount']) }}</p>
                                </div>
                            @endforeach
                            @if ($route)
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <p class="text-sm text-gray-700">Transport — {{ $route['name'] }} <span class="text-gray-400">({{ $route['months'] }} × {{ $rs($route['monthly']) }})</span></p>
                                    <p class="text-sm text-gray-800 tabular-nums">{{ $rs($viewing['fee']['transport']['gross']) }}</p>
                                </div>
                            @endif
                            @if ($vf['concession'] > 0)
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <p class="text-sm text-gray-700">Concession</p>
                                    <p class="text-sm text-gray-800 tabular-nums">− {{ $rs($vf['concession']) }}</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </form>

                <div class="px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2 flex-shrink-0">
                    <button type="button" wire:click="closeView" class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md">Close</button>
                    <button type="submit" form="board-form" wire:loading.attr="disabled" wire:target="saveBoard,admitCard"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md disabled:opacity-60">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
