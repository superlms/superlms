{{-- ══════════════════════════════════════════════════════════════════
     PAYMENTS — the fee-type tabs, the grey "Filter by:" band and the
     analytics strip attached under it. Meant to be @included inside the
     host's own sticky header (Accounts\Payments or the Admin Fee
     "Payments" tab).

     Both hosts feed this via App\Livewire\Concerns\HandlesPayments, so
     editing this one file keeps the two pages in sync. Same shape as
     fee-structure-header.blade.php.
══════════════════════════════════════════════════════════════════ --}}

@php
    $paymentFiltersDirty = $paymentStandardId || $paymentSectionId || $paymentStudentSearch
        || $paymentModeFilter || $feeTypeFilter || $datePreset !== 'this_month';
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

{{-- Grey filter band — one line: range dropdown, the start/end dates it
     fills in (both editable), then class, section, mode and search. --}}
<div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2.5">
    <div class="flex flex-wrap items-center gap-2">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700 shrink-0">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            <span class="hidden 2xl:inline">Filter by:</span>
        </div>

        <select wire:model.live="datePreset"
            class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            @foreach ($datePresets as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Always editable: a preset fills these in, typing in them switches
             the dropdown to Custom Range. --}}
        <input type="date" wire:model.live="dateFrom" title="Start date"
            class="text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700">
        <span class="text-xs text-gray-400">–</span>
        <input type="date" wire:model.live="dateTo" title="End date"
            class="text-xs bg-white border border-gray-200 rounded-md px-2 py-1.5 text-gray-700">

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

        <input wire:model.live.debounce.300ms="paymentStudentSearch" type="text" placeholder="Search student…"
            class="text-xs bg-white border border-gray-200 rounded-md px-3 py-1.5 text-gray-700 w-36 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">

        @if ($paymentFiltersDirty)
            <button wire:click="resetPaymentFilters"
                class="ml-auto inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-red-600 bg-white border border-red-200 rounded-md hover:bg-red-50">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                Clear
            </button>
        @endif
    </div>
</div>

{{-- Analytics, attached straight under the filters --}}
@include('livewire.partials.payments-analytics')
