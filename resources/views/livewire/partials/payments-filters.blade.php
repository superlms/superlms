{{--
    Payments filter bar — shared by Accounts\Payments and the Admin Fee
    "Payments" tab. Needs: $standards, $paymentSections, $datePreset and the
    HandlesPayments filter properties.
--}}
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-4 space-y-3">
    <div class="flex items-center gap-1.5 mb-1">
        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
        </svg>
        <span class="text-xs font-semibold text-gray-600 uppercase tracking-wider">Filters</span>
    </div>

    {{-- Quick date-range presets --}}
    <div class="flex flex-wrap items-center gap-1.5">
        @foreach ([
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7' => 'Last 7 Days',
            '15' => 'Last 15 Days',
            '30' => 'Last 30 Days',
            'last_month' => 'Last Month',
        ] as $key => $label)
            <button type="button" wire:click="setDatePreset('{{ $key }}')"
                class="px-2.5 py-1 rounded-full text-xs font-medium border transition
                    {{ $datePreset === $key
                        ? 'bg-emerald-600 border-emerald-600 text-white'
                        : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Row 1: Date, Class, Section --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Date From</label>
            <input type="date" wire:model.live="dateFrom"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Date To</label>
            <input type="date" wire:model.live="dateTo"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Class</label>
            <select wire:model.live="paymentStandardId"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All Classes</option>
                @foreach($standards as $std)
                    <option value="{{ $std->id }}">{{ $std->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Section</label>
            <select wire:model.live="paymentSectionId"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All Sections</option>
                @foreach($paymentSections as $sec)
                    <option value="{{ $sec->id }}">{{ $sec->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Row 2: Search, Mode, Type --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search Student</label>
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="paymentStudentSearch"
                    placeholder="Name or Adm No"
                    class="w-full pl-8 rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
            </div>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Payment Mode</label>
            <select wire:model.live="paymentModeFilter"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All Modes</option>
                <option value="cash">Cash</option>
                <option value="online">Online</option>
                <option value="upi">UPI</option>
                <option value="cheque">Cheque</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="waiver">Waiver</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Fee Type</label>
            <select wire:model.live="feeTypeFilter"
                class="w-full rounded-lg border-gray-300 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                <option value="">All Types</option>
                <option value="academic">Academic</option>
                <option value="transport">Transport</option>
                <option value="penalty">Penalty</option>
            </select>
        </div>
    </div>
</div>
