{{-- PAYMENTS — the header's two figures: Total Fee (academic and transport
     together, for the class / section picked) and Collected (academic and
     transport together, in the dates and filters picked). Same numbers the
     analytics strip under the filters used to show first; it is gone
     (2026-10-14). Needs $headerStats (HandlesPayments::paymentHeaderStats). --}}
<div class="flex items-center gap-4 text-sm text-gray-500 mr-1 divide-x divide-gray-200">
    <span class="whitespace-nowrap">Total Fee: <strong class="text-gray-800">₹{{ number_format($headerStats['total_fee'] ?? 0, 0) }}</strong></span>
    <span class="pl-4 whitespace-nowrap">Collected: <strong class="text-emerald-600">₹{{ number_format($headerStats['total_collected'] ?? 0, 0) }}</strong></span>
</div>
