{{-- ══════════════════════════════════════════════════════════════════
     FEE CYCLE — tabs + filter row, meant to be @included directly inside
     the host's own sticky header (Accounts\FeeCycles's whole header, or
     Admin\Fee's shared header under the 'cycle' tab) — attendance-style.
     Shared by both hosts via App\Livewire\Concerns\HandlesFeeCycles, so
     editing this one file keeps both pages in sync.
══════════════════════════════════════════════════════════════════ --}}
<div class="border-t border-gray-200 px-4 sm:px-6">
    <div class="flex gap-1">
        @foreach (['cycle' => 'Fee Cycle', 'calculator' => 'Calculator'] as $tab => $label)
            <button wire:click="switchCycleTab('{{ $tab }}')"
                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors {{ $cycleTab === $tab ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">{{ $label }}</button>
        @endforeach
    </div>
</div>

@if ($cycleTab === 'cycle')
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3 flex flex-wrap items-center gap-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-gray-200 text-xs font-medium text-gray-600">
            Total <strong class="text-gray-900">{{ $totalCycles }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-gray-200 text-xs font-medium text-gray-600">
            Academic <strong class="text-gray-900">{{ $academicCycles }}</strong>
        </span>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white border border-gray-200 text-xs font-medium text-gray-600">
            Transport <strong class="text-gray-900">{{ $transportCycles }}</strong>
        </span>
        <button wire:click="openCycleModal()"
            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg shadow-sm ml-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
            Add Fee Cycle
        </button>
    </div>
@else
    {{-- Calculator's own filter band — class, section, installment --}}
    <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-3 flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-1.5 text-sm font-semibold text-gray-700">
            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
            Filter by:
        </div>
        <select wire:model.live="calcStandardId" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">Select class…</option>
            @foreach ($standards as $std)
                <option value="{{ $std->id }}">{{ $std->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="calcSectionId" @disabled(!$calcStandardId) class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">
            <option value="">All sections</option>
            @foreach ($calcSections as $sec)
                <option value="{{ $sec->id }}">{{ $sec->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="calcSerial" class="text-xs bg-white border border-gray-200 rounded-md px-2.5 py-1.5 text-gray-700">
            <option value="">All installments</option>
            @foreach ($cycles->where('fee_type', 'academic')->where('is_token', false) as $cy)
                <option value="{{ $cy->payment_serial }}">Installment {{ $cy->payment_serial }}</option>
            @endforeach
        </select>
    </div>
@endif
