{{--
    Payments analytics strip — one horizontal line, re-computed from the same
    filters as the listing below. Needs: $headerStats.
--}}
<div class="flex flex-nowrap items-stretch gap-2 overflow-x-auto pb-1">
    {{-- Total Fee --}}
    <div class="flex-shrink-0 min-w-[128px] bg-gray-50 rounded-xl border border-gray-200 px-3 py-2.5">
        <p class="text-[10px] font-medium text-gray-500 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Total Fee</p>
        <p class="text-sm font-bold text-gray-800 leading-tight whitespace-nowrap">₹{{ number_format($headerStats['total_fee'] ?? 0, 0) }}</p>
        <p class="text-[10px] text-gray-400 mt-0.5 whitespace-nowrap">Acad + Transport</p>
    </div>

    {{-- Total Collected --}}
    <div class="flex-shrink-0 min-w-[128px] bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl px-3 py-2.5">
        <p class="text-[10px] font-medium text-emerald-100 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Total Collected</p>
        <p class="text-sm font-bold text-white leading-tight whitespace-nowrap">₹{{ number_format($headerStats['total_collected'] ?? 0, 0) }}</p>
        <p class="text-[10px] text-emerald-200 mt-0.5 whitespace-nowrap">In filtered range</p>
    </div>

    <div class="w-px bg-gray-200 flex-shrink-0"></div>

    {{-- Academic total / collected / remaining --}}
    <div class="flex-shrink-0 min-w-[120px] bg-emerald-50 rounded-xl border border-emerald-200 px-3 py-2.5">
        <p class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Academic Total</p>
        <p class="text-sm font-bold text-emerald-700 leading-tight whitespace-nowrap">₹{{ number_format($headerStats['total_academic_fee'] ?? 0, 0) }}</p>
    </div>
    <div class="flex-shrink-0 min-w-[120px] bg-emerald-50 rounded-xl border border-emerald-200 px-3 py-2.5">
        <p class="text-[10px] font-medium text-emerald-600 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Academic Collected</p>
        <p class="text-sm font-bold text-emerald-700 leading-tight whitespace-nowrap">₹{{ number_format($headerStats['academic_collected'] ?? 0, 0) }}</p>
    </div>
    <div class="flex-shrink-0 min-w-[120px] rounded-xl border px-3 py-2.5 {{ ($headerStats['academic_remaining'] ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200' }}">
        <p class="text-[10px] font-medium uppercase tracking-wider leading-none mb-1 whitespace-nowrap {{ ($headerStats['academic_remaining'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-500' }}">Academic Remaining</p>
        <p class="text-sm font-bold leading-tight whitespace-nowrap {{ ($headerStats['academic_remaining'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-700' }}">₹{{ number_format($headerStats['academic_remaining'] ?? 0, 0) }}</p>
    </div>

    <div class="w-px bg-gray-200 flex-shrink-0"></div>

    {{-- Transport total / collected / remaining --}}
    <div class="flex-shrink-0 min-w-[120px] bg-blue-50 rounded-xl border border-blue-200 px-3 py-2.5">
        <p class="text-[10px] font-medium text-blue-600 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Transport Total</p>
        <p class="text-sm font-bold text-blue-700 leading-tight whitespace-nowrap">₹{{ number_format($headerStats['total_transport_fee'] ?? 0, 0) }}</p>
    </div>
    <div class="flex-shrink-0 min-w-[120px] bg-blue-50 rounded-xl border border-blue-200 px-3 py-2.5">
        <p class="text-[10px] font-medium text-blue-600 uppercase tracking-wider leading-none mb-1 whitespace-nowrap">Transport Collected</p>
        <p class="text-sm font-bold text-blue-700 leading-tight whitespace-nowrap">₹{{ number_format($headerStats['transport_collected'] ?? 0, 0) }}</p>
    </div>
    <div class="flex-shrink-0 min-w-[120px] rounded-xl border px-3 py-2.5 {{ ($headerStats['transport_remaining'] ?? 0) > 0 ? 'bg-red-50 border-red-200' : 'bg-gray-50 border-gray-200' }}">
        <p class="text-[10px] font-medium uppercase tracking-wider leading-none mb-1 whitespace-nowrap {{ ($headerStats['transport_remaining'] ?? 0) > 0 ? 'text-red-500' : 'text-gray-500' }}">Transport Remaining</p>
        <p class="text-sm font-bold leading-tight whitespace-nowrap {{ ($headerStats['transport_remaining'] ?? 0) > 0 ? 'text-red-600' : 'text-gray-700' }}">₹{{ number_format($headerStats['transport_remaining'] ?? 0, 0) }}</p>
    </div>
</div>
