{{-- Monthly attendance calendar + totals. Expects $calendar = ['weeks'=>[], 'totals'=>[]] --}}
@php
    $t = $calendar['totals'];
    // Percent is provided by the admin builder; fall back for callers (Accounts)
    // whose totals only carry working/present/half counts.
    $calPercent = $t['percent'] ?? (
        ($t['working_days'] ?? 0) > 0
            ? round((($t['present_days'] ?? 0) + 0.5 * ($t['half_days'] ?? 0)) / $t['working_days'] * 100, 1)
            : 0
    );
@endphp
<div class="space-y-5">
    {{-- Totals --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-blue-600">{{ $calendar['totals']['working_days'] }}</p>
            <p class="text-xs uppercase tracking-wide text-blue-400 mt-0.5">Working Days</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-emerald-600">{{ $calendar['totals']['present_days'] }}</p>
            <p class="text-xs uppercase tracking-wide text-emerald-400 mt-0.5">Present</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-red-600">{{ $calendar['totals']['absent_days'] }}</p>
            <p class="text-xs uppercase tracking-wide text-red-400 mt-0.5">Absent</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-amber-500">{{ $calendar['totals']['half_days'] ?? 0 }}</p>
            <p class="text-xs uppercase tracking-wide text-amber-400 mt-0.5">Half Day</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-indigo-600">{{ $calendar['totals']['holidays'] ?? 0 }}</p>
            <p class="text-xs uppercase tracking-wide text-indigo-400 mt-0.5">Holiday</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <p class="text-2xl font-bold text-gray-800">{{ $calPercent }}%</p>
            <p class="text-xs uppercase tracking-wide text-gray-400 mt-0.5">Attendance</p>
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-300"></span> Present</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-100 border border-red-300"></span> Absent</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Half Day</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-indigo-100 border border-indigo-300"></span> Holiday</span>
        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-50 border border-gray-200"></span> Not marked</span>
    </div>

    {{-- Calendar --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 overflow-x-auto">
        <table class="w-full min-w-[560px] border-collapse">
            <thead>
                <tr>
                    @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow)
                        <th class="text-xs font-semibold text-gray-400 uppercase pb-2 text-center">{{ $dow }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($calendar['weeks'] as $week)
                    <tr>
                        @foreach ($week as $cell)
                            <td class="p-1 align-top">
                                @if ($cell)
                                    @php
                                        $cls = match ($cell['status']) {
                                            'present'  => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                                            'absent'   => 'bg-red-50 border-red-200 text-red-700',
                                            'half_day' => 'bg-amber-50 border-amber-200 text-amber-700',
                                            'holiday'  => 'bg-indigo-50 border-indigo-200 text-indigo-700',
                                            default    => 'bg-gray-50 border-gray-200 text-gray-300',
                                        };
                                        $label = match ($cell['status']) {
                                            'present'  => 'P',
                                            'absent'   => 'A',
                                            'half_day' => 'HD',
                                            'holiday'  => 'H',
                                            default    => '·',
                                        };
                                    @endphp
                                    <div class="h-16 rounded-lg border {{ $cls }} flex flex-col items-center justify-center">
                                        <span class="text-sm font-bold">{{ $cell['day'] }}</span>
                                        <span class="text-[10px] font-semibold uppercase">{{ $label }}</span>
                                    </div>
                                @else
                                    <div class="h-16"></div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
