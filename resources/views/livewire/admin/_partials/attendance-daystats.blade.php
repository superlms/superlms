{{-- Single-day attendance analytics. Expects $stats = tally array. --}}
<div>
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Analytics</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $stats['total'] }}</p>
            <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Total</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $stats['present'] }}</p>
            <p class="text-xs uppercase tracking-wide text-emerald-400 mt-0.5">Present</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $stats['absent'] }}</p>
            <p class="text-xs uppercase tracking-wide text-red-400 mt-0.5">Absent</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-500">{{ $stats['half_day'] }}</p>
            <p class="text-xs uppercase tracking-wide text-amber-400 mt-0.5">Half Day</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-indigo-600">{{ $stats['holiday'] }}</p>
            <p class="text-xs uppercase tracking-wide text-indigo-400 mt-0.5">Holiday</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-400">{{ $stats['not_marked'] }}</p>
            <p class="text-xs uppercase tracking-wide text-gray-300 mt-0.5">Not Marked</p>
        </div>
    </div>
</div>
