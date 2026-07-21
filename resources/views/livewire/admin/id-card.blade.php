<div class="min-h-screen bg-gray-50">

    {{-- ══════════════ HEADER (white, sticky) ══════════════ --}}
    <div class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 sm:px-6 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-gray-900">ID Card</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Generate &amp; manage {{ $cardType }} identity cards</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden lg:flex items-center gap-4 text-sm text-gray-500 mr-3 divide-x divide-gray-200">
                        <span class="pr-4">Total {{ ucfirst($cardType) }}s: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                        <span class="px-4">Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                        <span class="pl-4">Remaining: <strong class="text-amber-500">{{ $this->analytics['remaining'] }}</strong></span>
                    </div>
                    <button wire:click="openGenerate" class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-semibold rounded-lg shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                        <span class="hidden sm:inline">Generate ID Cards</span><span class="sm:hidden">Generate</span>
                    </button>
                </div>
            </div>

            {{-- mobile analytics --}}
            <div class="flex lg:hidden items-center gap-3 sm:gap-4 text-xs text-gray-500 mt-3 flex-wrap">
                <span>Total: <strong class="text-gray-800">{{ $this->analytics['total'] }}</strong></span>
                <span>Issued: <strong class="text-emerald-600">{{ $this->analytics['issued'] }}</strong></span>
                <span>Remaining: <strong class="text-amber-500">{{ $this->analytics['remaining'] }}</strong></span>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-t border-gray-200 px-4 sm:px-6">
            <nav class="flex gap-1">
                @foreach (['student' => 'Students', 'teacher' => 'Teachers', 'employee' => 'Employees'] as $t => $label)
                    <button wire:click="switchCardType('{{ $t }}')"
                        class="py-3.5 px-5 text-sm font-semibold border-b-2 transition-colors {{ $cardType === $t ? 'border-violet-500 text-violet-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Filter bar --}}
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3">
            <div class="flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
                    Filter by:
                </div>
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="Name, card no{{ $cardType === 'student' ? ', admission' : ($cardType === 'teacher' ? ', employee id' : ', mobile') }}…"
                    class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-56 focus:ring-2 focus:ring-violet-500 focus:border-violet-500" />
                @if ($cardType === 'student')
                    <select wire:model.live="standardFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                        <option value="">All Classes</option>
                        @foreach ($standards as $std)<option value="{{ $std->id }}">{{ $std->name }}</option>@endforeach
                    </select>
                    <select wire:model.live="sectionFilter" @disabled($sections->isEmpty()) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50">
                        <option value="">All Sections</option>
                        @foreach ($sections as $sec)<option value="{{ $sec->id }}">{{ $sec->name }}</option>@endforeach
                    </select>
                @endif
                <select wire:model.live="statusFilter" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
                <select wire:model.live="perPage" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
                    <option value="50">50 / page</option>
                    <option value="100">100 / page</option>
                    <option value="200">200 / page</option>
                </select>
                @if ($search || $standardFilter || $sectionFilter || $statusFilter)
                    <button wire:click="resetFilters" class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        Clear
                    </button>
                @endif
                <span class="ml-auto text-xs text-gray-500">Total: <strong class="text-gray-700">{{ $cards->total() }}</strong> card(s)</span>
            </div>
        </div>
    </div>

    {{-- ══════════════ TABLE ══════════════ --}}
    <div class="p-4 sm:p-6">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-12">S.No</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ ucfirst($cardType) }}</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Card No.</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Expiry</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($cards as $i => $card)
                            @php
                                if ($cardType === 'student') {
                                    $person = $card->studentDetail;
                                    $name   = $person?->full_name ?? ($person?->user?->name ?? '—');
                                    $img    = $person?->image ?? ($person?->user?->image ?? null);
                                    $ident  = $person?->admission_no;
                                } elseif ($cardType === 'teacher') {
                                    $person = $card->teacherDetail;
                                    $name   = $person?->user?->name ?? '—';
                                    $img    = $person?->user?->image;
                                    $ident  = $person?->employee_id;
                                } else {
                                    $person = $card->adminEmployee;
                                    $name   = $person?->name ?? '—';
                                    $img    = $person?->photo;
                                    $ident  = $person?->designation ?? ('EMP-' . ($person?->id ?? ''));
                                }
                                $imgUrl = $img ? (\Illuminate\Support\Str::startsWith($img, ['http://','https://','data:']) ? $img : \Illuminate\Support\Facades\Storage::url($img)) : null;
                            @endphp
                            <tr wire:key="card-{{ $cardType }}-{{ $card->id }}" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 py-3 text-sm text-gray-500 tabular-nums">{{ $cards->firstItem() + $i }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        @if ($imgUrl)
                                            <img src="{{ $imgUrl }}" class="w-9 h-9 rounded-full object-cover border border-gray-200">
                                        @else
                                            <div class="w-9 h-9 rounded-full bg-violet-100 flex items-center justify-center text-violet-600 text-xs font-bold">{{ strtoupper(substr($name, 0, 1)) }}</div>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="font-semibold text-sm text-gray-900 truncate">{{ $name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $ident ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $card->card_number }}</td>
                                <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $card->expiry_date?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full uppercase tracking-wide {{ $card->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">{{ $card->status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-center gap-1">
                                        <button wire:click="showCard({{ $card->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-violet-50 hover:text-violet-600 hover:border-violet-200" title="View">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>
                                        <a href="{{ route('admin.id-card.print', ['organization' => auth()->user()->organization_id, 'type' => $cardType, 'id' => $card->id]) }}" target="_blank"
                                            class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-emerald-50 hover:text-emerald-600 hover:border-emerald-200" title="Download / Print">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        </a>
                                        <button wire:click="editCard({{ $card->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-amber-50 hover:text-amber-600 hover:border-amber-200" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                        </button>
                                        <button wire:click="confirmDelete({{ $card->id }})" class="p-1.5 rounded-md border border-gray-200 text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-200" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-16 text-center">
                                    <div class="w-12 h-12 mx-auto mb-3 bg-gray-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0z" /></svg>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-800">No ID cards found</p>
                                    <button wire:click="openGenerate" class="mt-2 text-xs text-violet-600 hover:underline">Generate ID cards</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($cards->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $cards->links() }}</div>
            @endif
        </div>
    </div>

    {{-- ══════════════ VIEW CARD MODAL ══════════════ --}}
    @if ($showViewModal && $viewCard)
        @php $c = app(\App\Services\IdCardService::class)->cardViewData($viewCard, $viewType); @endphp
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeViewModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-2xl bg-white shadow-2xl flex flex-col" wire:click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="text-base font-bold text-gray-800">{{ ucfirst($viewType) }} ID Card</h3>
                    <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600"><x-icon name="x-mark" class="h-5 w-5" /></button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-6 bg-gray-50">
                    @include('admin.id-cards._card', ['c' => $c])
                </div>
                <div class="px-6 py-4 border-t border-gray-100 bg-white flex justify-end gap-2 flex-shrink-0">
                    <a href="{{ route('admin.id-card.print', ['organization' => auth()->user()->organization_id, 'type' => $viewType, 'id' => $viewCard->id]) }}" target="_blank"
                        class="px-5 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">Download / Print</a>
                    <button wire:click="closeViewModal" class="px-5 py-2 text-sm bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">Close</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════ GENERATE SLIDE-IN PANEL ══════════════ --}}
    @if ($showGenerateModal)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeGenerate"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col" wire:click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="text-base font-bold text-gray-800">Generate ID Cards</h3>
                    <button wire:click="closeGenerate" class="text-gray-400 hover:text-gray-600"><x-icon name="x-mark" class="h-5 w-5" /></button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                    {{-- Step 1: type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Type <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['student' => 'Students', 'teacher' => 'Teachers', 'employee' => 'Employees'] as $t => $label)
                                <button type="button" wire:click="$set('genType', '{{ $t }}')"
                                    class="px-2 py-2.5 text-sm font-semibold rounded-lg border transition {{ $genType === $t ? 'border-violet-500 bg-violet-50 text-violet-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                        @error('genType')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    {{-- Step 2 (student only): classes --}}
                    @if ($genType === 'student')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Classes <span class="text-gray-400 font-normal">(leave empty for all)</span></label>
                            <div class="border border-gray-200 rounded-lg max-h-56 overflow-y-auto divide-y divide-gray-100">
                                @forelse ($standards as $std)
                                    <label class="flex items-center gap-2.5 px-3 py-2.5 cursor-pointer hover:bg-gray-50">
                                        <input type="checkbox" wire:model="genStandardIds" value="{{ $std->id }}"
                                            class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                                        <span class="text-sm text-gray-700">{{ $std->name }}</span>
                                    </label>
                                @empty
                                    <p class="px-3 py-3 text-sm text-gray-400">No classes found.</p>
                                @endforelse
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500">Selected: {{ count(array_filter($genStandardIds)) ?: 'All classes' }}</p>
                        </div>
                    @else
                        <div class="bg-violet-50 border border-violet-100 rounded-lg p-3 text-sm text-violet-700">
                            Cards will be generated for <strong>all {{ $genType }}s</strong> who don't have an active card.
                        </div>
                    @endif

                    {{-- Expiry --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Card Expiry Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="genExpiryDate" min="{{ now()->addDay()->format('Y-m-d') }}"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                        @error('genExpiryDate')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-start gap-2.5">
                        <x-icon name="information-circle" class="h-5 w-5 text-amber-500 flex-shrink-0 mt-0.5" />
                        <p class="text-xs text-amber-700">After the first batch is issued, any newly-added {{ $genType }}s automatically get a card every midnight using this expiry date.</p>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeGenerate" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="generateCards" wire:loading.attr="disabled" class="px-5 py-2 text-sm bg-violet-600 text-white rounded-lg hover:bg-violet-700 font-semibold shadow-sm transition disabled:opacity-60">
                        <span wire:loading.remove wire:target="generateCards">Generate Cards</span>
                        <span wire:loading wire:target="generateCards">Generating…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════ EDIT SLIDE-IN PANEL ══════════════ --}}
    @if ($showEditModal)
        <div class="fixed inset-0 z-[9999] overflow-hidden">
            <div class="absolute inset-0 bg-black/[0.06] backdrop-blur-[1.5px]" wire:click="closeEditModal"></div>
            <div class="absolute top-0 right-0 bottom-0 w-full max-w-md bg-white shadow-2xl flex flex-col" wire:click.stop>
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 flex-shrink-0">
                    <h3 class="text-base font-bold text-gray-800">Edit ID Card <span class="text-gray-400 font-normal">({{ ucfirst($cardType) }})</span></h3>
                    <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-600"><x-icon name="x-mark" class="h-5 w-5" /></button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Expiry Date <span class="text-red-500">*</span></label>
                        <input type="date" wire:model="editExpiryDate" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                        @error('editExpiryDate')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Status <span class="text-red-500">*</span></label>
                        <select wire:model="editStatus" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        @error('editStatus')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2 flex-shrink-0">
                    <button wire:click="closeEditModal" class="px-4 py-2 text-sm border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="saveEdit" class="px-5 py-2 text-sm bg-violet-600 hover:bg-violet-700 text-white rounded-lg font-semibold shadow-sm transition">Update</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════ DELETE MODAL ══════════════ --}}
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center px-4" style="background:rgba(0,0,0,0.45);backdrop-filter:blur(4px);">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" wire:click.stop>
                <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                    <x-icon name="exclamation-triangle" class="h-7 w-7 text-red-500" />
                </div>
                <h3 class="text-base font-bold text-gray-800 mb-1">Delete ID Card?</h3>
                <p class="text-sm text-gray-500 mb-5">This action cannot be undone.</p>
                <div class="flex justify-center gap-3">
                    <button wire:click="closeDeleteModal" class="px-4 py-2 text-sm border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button wire:click="deleteCard" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold shadow transition">Delete</button>
                </div>
            </div>
        </div>
    @endif
</div>
