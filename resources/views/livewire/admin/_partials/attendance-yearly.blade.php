{{-- Yearly attendance analytics — 12 month cards + year totals. Expects $yearly. --}}
<div class="space-y-5">
    {{-- Year totals --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $yearly['year'] }}</p>
            <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Year</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $yearly['totals']['working'] }}</p>
            <p class="text-xs uppercase tracking-wide text-blue-400 mt-0.5">Working</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $yearly['totals']['present'] }}</p>
            <p class="text-xs uppercase tracking-wide text-emerald-400 mt-0.5">Present</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $yearly['totals']['absent'] }}</p>
            <p class="text-xs uppercase tracking-wide text-red-400 mt-0.5">Absent</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-500">{{ $yearly['totals']['half_day'] }}</p>
            <p class="text-xs uppercase tracking-wide text-amber-400 mt-0.5">Half Day</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $yearly['totals']['percent'] }}%</p>
            <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Attendance</p>
        </div>
    </div>

    {{-- Month cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        @foreach ($yearly['months'] as $m)
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-bold text-gray-800">{{ $m['label'] }}</p>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $m['working'] > 0 ? 'bg-gray-100 text-gray-700' : 'bg-gray-50 text-gray-300' }}">{{ $m['percent'] }}%</span>
                </div>
                <div class="grid grid-cols-4 gap-1.5 text-center">
                    <div class="rounded-md bg-emerald-50 py-1.5">
                        <p class="text-sm font-bold text-emerald-600">{{ $m['present'] }}</p>
                        <p class="text-[9px] uppercase text-emerald-400">Pre</p>
                    </div>
                    <div class="rounded-md bg-red-50 py-1.5">
                        <p class="text-sm font-bold text-red-600">{{ $m['absent'] }}</p>
                        <p class="text-[9px] uppercase text-red-400">Abs</p>
                    </div>
                    <div class="rounded-md bg-amber-50 py-1.5">
                        <p class="text-sm font-bold text-amber-600">{{ $m['half_day'] }}</p>
                        <p class="text-[9px] uppercase text-amber-400">Half</p>
                    </div>
                    <div class="rounded-md bg-indigo-50 py-1.5">
                        <p class="text-sm font-bold text-indigo-600">{{ $m['holiday'] }}</p>
                        <p class="text-[9px] uppercase text-indigo-400">Hol</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
