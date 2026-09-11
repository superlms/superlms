{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the fee-type tabs and the grey "Filter by:" band, meant to
     be @included inside the host's own sticky header (Accounts\Payments or
     the Admin Fee "Payments" tab).

     Both hosts feed this via App\Livewire\Concerns\HandlesPayments, so
     editing this one file keeps the two pages in sync. Same shape as
     fee-structure-header.blade.php.
══════════════════════════════════════════════════════════════════ --}}

@php
    $paymentPresets = [
        'today'      => 'Today',
        'yesterday'  => 'Yesterday',
        '7'          => 'Last 7 Days',
        '15'         => 'Last 15 Days',
        '30'         => 'Last 30 Days',
        'last_month' => 'Last Month',
    ];
    $paymentFiltersDirty = $paymentStandardId || $paymentSectionId || $paymentStudentSearch
        || $paymentModeFilter || $feeTypeFilter || $datePreset;
@endphp

{{-- Tabs: All | Academic | Transport | Penalty --}}
<div class="border-t border-gray-200 px-4 sm:px-6">
    <div class="flex gap-1">
        @foreach (['' => 'All', 'academic' => 'Academic', 'transport' => 'Transport', 'penalty' => 'Penalty'] as $tab => $label)
            <button wire:click="setFeeTypeFilter('{{ $tab }}')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $feeTypeFilter === $tab ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
        @endforeach
    </div>
</div>

{{-- Grey filter band — same style as the Fee Structure page --}}
<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3 space-y-2.5">

    {{-- Row 1 — date range --}}
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 mr-1">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>

        @foreach ($paymentPresets as $key => $label)
            <button wire:click="setDatePreset('{{ $key }}')"
                class="text-xs rounded-md border px-2.5 py-1.5 transition-colors {{ $datePreset === $key ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-100' }}">{{ $label }}</button>
        @endforeach

        <input type="date" wire:model.live="dateFrom"
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
        <span class="text-xs text-gray-400">to</span>
        <input type="date" wire:model.live="dateTo"
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
    </div>

    {{-- Row 2 — class, section, mode, student --}}
    <div class="flex flex-wrap items-center gap-2">
        <select wire:model.live="paymentStandardId"
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All Classes</option>
            @foreach ($standards as $std)
                <option value="{{ $std->id }}">{{ $std->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="paymentSectionId" @disabled(!$paymentStandardId)
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">All Sections</option>
            @foreach ($paymentSections as $sec)
                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="paymentModeFilter"
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All Modes</option>
            <option value="cash">Cash</option>
            <option value="online">Online</option>
            <option value="upi">UPI</option>
            <option value="cheque">Cheque</option>
            <option value="bank_transfer">Bank Transfer</option>
        </select>

        <input wire:model.live.debounce.300ms="paymentStudentSearch" type="text" placeholder="Search name or adm no…"
            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-52 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

        @if ($paymentFiltersDirty)
            <button wire:click="resetPaymentFilters"
                class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Clear
            </button>
        @endif
    </div>
</div>
