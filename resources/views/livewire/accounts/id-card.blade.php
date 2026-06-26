<div class="min-h-screen bg-gray-50/50">

    {{-- ═══ STICKY HEADER ═══ --}}
    <div class="sticky top-0 z-30 bg-white border-b border-gray-200 shadow-sm">
        <div class="px-6 pt-4 pb-3 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-emerald-700 leading-tight">ID Cards</h1>
                <p class="text-xs text-gray-400 mt-0.5">Generate and manage student &amp; teacher ID cards</p>
            </div>
            <x-button emerald label="Bulk Generate" icon="plus"
                wire:click="openBulkModal" />
        </div>

        {{-- Analytics Strip --}}
        <div class="px-6 pb-3 grid grid-cols-4 gap-3">
            <div class="flex items-center gap-3 bg-emerald-50 rounded-xl px-4 py-3 border border-emerald-100">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="identification" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Student Cards</p>
                    <p class="text-lg font-bold text-emerald-700">{{ $totalStudentCards }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-green-50 rounded-xl px-4 py-3 border border-green-100">
                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="check-circle" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Active (Students)</p>
                    <p class="text-lg font-bold text-green-700">{{ $activeStudentCards }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-blue-50 rounded-xl px-4 py-3 border border-blue-100">
                <div class="w-8 h-8 rounded-lg bg-blue-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="identification" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Teacher Cards</p>
                    <p class="text-lg font-bold text-blue-700">{{ $totalTeacherCards }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 bg-indigo-50 rounded-xl px-4 py-3 border border-indigo-100">
                <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center flex-shrink-0">
                    <x-icon name="check-circle" class="w-4 h-4 text-white" />
                </div>
                <div>
                    <p class="text-xs text-gray-500 font-medium">Active (Teachers)</p>
                    <p class="text-lg font-bold text-indigo-700">{{ $activeTeacherCards }}</p>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="px-6 flex gap-1 border-t border-gray-100 pt-2 pb-1">
            <button wire:click="setTab('student')"
                class="px-4 py-1.5 text-sm font-medium rounded-lg transition-colors
                    {{ $activeTab === 'student'
                        ? 'bg-emerald-600 text-white'
                        : 'text-gray-500 hover:bg-gray-100' }}">
                Students
            </button>
            <button wire:click="setTab('teacher')"
                class="px-4 py-1.5 text-sm font-medium rounded-lg transition-colors
                    {{ $activeTab === 'teacher'
                        ? 'bg-emerald-600 text-white'
                        : 'text-gray-500 hover:bg-gray-100' }}">
                Teachers
            </button>
        </div>
    </div>

    <div class="p-6 space-y-5">

        @if(session('success'))
            <div class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-700 text-sm flex items-center gap-2">
                <x-icon name="check-circle" class="w-4 h-4 text-green-500" />
                {{ session('success') }}
            </div>
        @endif

        {{-- ─── Filter Bar ─────────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm px-5 py-4">
            <div class="flex flex-wrap items-end gap-3">

                {{-- Search --}}
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
                    <div class="relative">
                        <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                        <input wire:model.live="search" type="text"
                            placeholder="Card number or name…"
                            class="w-full pl-9 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none" />
                    </div>
                </div>

                @if($activeTab === 'student')
                    {{-- Class --}}
                    <div class="min-w-[140px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Class</label>
                        <select wire:model.live="filterStandard"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                            <option value="">All Classes</option>
                            @foreach($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Section --}}
                    @if($filterSections->count())
                    <div class="min-w-[130px]">
                        <label class="block text-xs font-medium text-gray-500 mb-1">Section</label>
                        <select wire:model.live="filterSection"
                            class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                            <option value="">All Sections</option>
                            @foreach($filterSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                @endif

                {{-- Status --}}
                <div class="min-w-[130px]">
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select wire:model.live="filterStatus"
                        class="w-full py-2 px-3 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="expired">Expired</option>
                        <option value="revoked">Revoked</option>
                    </select>
                </div>

                {{-- Clear --}}
                <button wire:click="resetFilters"
                    class="px-3 py-2 text-sm text-gray-500 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1">
                    <x-icon name="x-mark" class="w-4 h-4" /> Clear
                </button>
            </div>
        </div>

        {{-- ─── ID Cards Table ──────────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100">
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">#</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">Card Number</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                            @if($activeTab === 'student')
                                <th class="text-left px-4 py-3 font-semibold text-gray-600">Class / Section</th>
                            @else
                                <th class="text-left px-4 py-3 font-semibold text-gray-600">Email</th>
                            @endif
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">Issue Date</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">Expiry Date</th>
                            <th class="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                            <th class="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($cards as $card)
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-4 py-3 text-gray-400 text-xs">{{ $loop->iteration + ($cards->currentPage() - 1) * $cards->perPage() }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $card->card_number }}</td>
                                <td class="px-4 py-3">
                                    @if($activeTab === 'student')
                                        <span class="font-medium text-gray-800">{{ $card->studentDetail?->full_name ?? '—' }}</span>
                                        @if($card->studentDetail?->admission_no)
                                            <span class="block text-xs text-gray-400">{{ $card->studentDetail->admission_no }}</span>
                                        @endif
                                    @else
                                        <span class="font-medium text-gray-800">{{ $card->teacherDetail?->user?->name ?? '—' }}</span>
                                    @endif
                                </td>
                                @if($activeTab === 'student')
                                    <td class="px-4 py-3 text-gray-600">
                                        {{ $card->studentDetail?->standard?->name ?? '—' }}
                                        @if($card->studentDetail?->section)
                                            – {{ $card->studentDetail->section->name }}
                                        @endif
                                    </td>
                                @else
                                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $card->teacherDetail?->user?->email ?? '—' }}</td>
                                @endif
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ $card->issue_date ? \Carbon\Carbon::parse($card->issue_date)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 text-xs">
                                    {{ $card->expiry_date ? \Carbon\Carbon::parse($card->expiry_date)->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $statusColors = [
                                            'active'  => 'bg-green-100 text-green-700',
                                            'expired' => 'bg-red-100 text-red-700',
                                            'revoked' => 'bg-gray-100 text-gray-600',
                                        ];
                                        $sc = $statusColors[$card->status] ?? 'bg-gray-100 text-gray-600';
                                    @endphp
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                                        {{ ucfirst($card->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button wire:click="viewCard({{ $card->id }})"
                                            class="p-1.5 text-blue-500 hover:bg-blue-50 rounded-lg transition-colors" title="View">
                                            <x-icon name="eye" class="w-4 h-4" />
                                        </button>
                                        @if($pendingDeleteId === $card->id)
                                            <div class="flex items-center gap-1">
                                                <button wire:click="doDelete"
                                                    class="px-2 py-1 text-xs bg-red-500 text-white rounded-lg hover:bg-red-600">Confirm</button>
                                                <button wire:click="cancelDelete"
                                                    class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200">Cancel</button>
                                            </div>
                                        @else
                                            <button wire:click="deleteCard({{ $card->id }})"
                                                class="p-1.5 text-red-400 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                                <x-icon name="trash" class="w-4 h-4" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-gray-400">
                                    <x-icon name="identification" class="w-10 h-10 mx-auto mb-2 text-gray-200" />
                                    <p class="text-sm">No ID cards found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($cards->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">
                    {{ $cards->links() }}
                </div>
            @endif
        </div>

    </div>

    {{-- ═══ BULK GENERATE MODAL ═══ --}}
    @if($showBulkModal)
    <div class="fixed inset-0 z-[999] flex items-center justify-center px-4 py-6"
         style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);"
         wire:click="closeBulkModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]"
             wire:click.stop>

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Bulk Generate ID Cards</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $activeTab === 'student' ? 'Generate cards for students without active cards' : 'Generate cards for teachers without active cards' }}
                    </p>
                </div>
                <button wire:click="closeBulkModal"
                    class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 overflow-y-auto space-y-4">

                {{-- Card Prefix --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Card Prefix <span class="text-red-500">*</span></label>
                    <input wire:model="cardPrefix" type="text" maxlength="10"
                        placeholder="e.g. ID"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none" />
                    @error('cardPrefix') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Validity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Validity (Months) <span class="text-red-500">*</span></label>
                    <input wire:model="validityMonths" type="number" min="1" max="60"
                        placeholder="12"
                        class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none" />
                    @error('validityMonths') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                @if($activeTab === 'student')
                    {{-- Class --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Class <span class="text-gray-400 font-normal">(optional — leave blank for all)</span></label>
                        <select wire:model.live="bulkStandard"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                            <option value="">All Classes</option>
                            @foreach($standards as $std)
                                <option value="{{ $std->id }}">{{ $std->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Section --}}
                    @if($bulkSections->count())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Section <span class="text-gray-400 font-normal">(optional)</span></label>
                        <select wire:model="bulkSection"
                            class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 outline-none">
                            <option value="">All Sections</option>
                            @foreach($bulkSections as $sec)
                                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                @endif

                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-700">
                    <x-icon name="information-circle" class="w-4 h-4 inline mr-1" />
                    Cards will only be generated for {{ $activeTab === 'student' ? 'students' : 'teachers' }} who don't already have an active card.
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 flex-shrink-0">
                <button wire:click="closeBulkModal"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <button wire:click="bulkGenerateCards"
                    class="px-5 py-2 text-sm font-semibold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors flex items-center gap-2">
                    <x-icon name="sparkles" class="w-4 h-4" />
                    Generate Cards
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══ VIEW MODAL ═══ --}}
    @if($showViewModal && $viewCard)
    <div class="fixed inset-0 z-[999] flex items-center justify-center px-4 py-6"
         style="background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);"
         wire:click="closeViewModal">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md flex flex-col max-h-[90vh]"
             wire:click.stop>

            {{-- Header --}}
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between flex-shrink-0">
                <h2 class="text-lg font-bold text-gray-800">ID Card Details</h2>
                <button wire:click="closeViewModal"
                    class="p-2 text-gray-400 hover:bg-gray-100 rounded-lg transition-colors">
                    <x-icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 overflow-y-auto space-y-4">

                {{-- Card Number --}}
                <div class="bg-emerald-50 border border-emerald-100 rounded-xl px-4 py-3 text-center">
                    <p class="text-xs text-gray-500 mb-1">Card Number</p>
                    <p class="text-xl font-bold font-mono text-emerald-700">{{ $viewCard->card_number }}</p>
                </div>

                <dl class="divide-y divide-gray-100">
                    @if($activeTab === 'student')
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-500 font-medium">Student Name</dt>
                            <dd class="text-gray-800 font-semibold">{{ $viewCard->studentDetail?->full_name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-500 font-medium">Admission No</dt>
                            <dd class="text-gray-700">{{ $viewCard->studentDetail?->admission_no ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-500 font-medium">Class</dt>
                            <dd class="text-gray-700">
                                {{ $viewCard->studentDetail?->standard?->name ?? '—' }}
                                @if($viewCard->studentDetail?->section)
                                    – {{ $viewCard->studentDetail->section->name }}
                                @endif
                            </dd>
                        </div>
                    @else
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-500 font-medium">Teacher Name</dt>
                            <dd class="text-gray-800 font-semibold">{{ $viewCard->teacherDetail?->user?->name ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 text-sm">
                            <dt class="text-gray-500 font-medium">Email</dt>
                            <dd class="text-gray-700">{{ $viewCard->teacherDetail?->user?->email ?? '—' }}</dd>
                        </div>
                    @endif

                    <div class="flex justify-between py-2 text-sm">
                        <dt class="text-gray-500 font-medium">Issue Date</dt>
                        <dd class="text-gray-700">
                            {{ $viewCard->issue_date ? \Carbon\Carbon::parse($viewCard->issue_date)->format('d M Y') : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <dt class="text-gray-500 font-medium">Expiry Date</dt>
                        <dd class="text-gray-700">
                            {{ $viewCard->expiry_date ? \Carbon\Carbon::parse($viewCard->expiry_date)->format('d M Y') : '—' }}
                        </dd>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <dt class="text-gray-500 font-medium">Status</dt>
                        <dd>
                            @php
                                $statusColors = [
                                    'active'  => 'bg-green-100 text-green-700',
                                    'expired' => 'bg-red-100 text-red-700',
                                    'revoked' => 'bg-gray-100 text-gray-600',
                                ];
                                $sc = $statusColors[$viewCard->status] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $sc }}">
                                {{ ucfirst($viewCard->status) }}
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex justify-end flex-shrink-0">
                <button wire:click="closeViewModal"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
