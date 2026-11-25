{{-- Admin → Analytics: each tab's extra detail (user's ask of 2026-11-25,
     App\Support\AdminAnalyticsDetail). Included once per tab with $part
     (metrics | attendance | performance | admissions | fee | operations)
     and $detail. White cards and plain tables in the page's own look. --}}
@php
    $d      = $detail ?? [];
    $num    = fn ($v) => number_format((float) $v);
    $inr    = fn ($v) => '₹' . number_format((float) $v, 0);
    $pctTxt = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 1), '0'), '.') . '%';
    $tone   = fn ($v, $good = 85, $fair = 70) => $v === null ? '#e5e7eb' : ($v >= $good ? '#0d9488' : ($v >= $fair ? '#f59e0b' : '#f43f5e'));
    $th     = 'px-4 py-2.5 text-[11px] font-semibold uppercase tracking-wide text-gray-400';
    $td     = 'px-4 py-2.5 text-sm';
@endphp

@once
<style>
    .and-table { width: 100%; border-collapse: collapse; }
    .and-table thead tr { background: #fafbfb; border-top: 1px solid #f0f3f2; border-bottom: 1px solid #f0f3f2; }
    .and-table tbody tr { border-bottom: 1px solid #f3f5f4; }
    .and-table tbody tr:last-child { border-bottom: 0; }
    .and-table tfoot tr { border-top: 1px solid #e5eae8; background: #fafbfb; }
    .and-bar { height: 6px; border-radius: 999px; background: #f0f3f2; overflow: hidden; }
    .and-bar > span { display: block; height: 100%; border-radius: 999px; }
    .and-tile { border: 1px solid #eef1f0; border-radius: 14px; padding: 12px 14px; background: #fff; min-width: 0; }
    .and-tile p:first-child { font-size: 11.5px; color: #6b7c76; }
    .and-tile p:last-child { font-size: 18px; font-weight: 600; color: #0c2620; margin-top: 2px; font-variant-numeric: tabular-nums; }
</style>
@endonce

{{-- ═══════════════════════ KEY METRICS ═══════════════════════ --}}
@if ($part === 'metrics')
    @php $g = $d['glance'] ?? []; @endphp
    <div class="sa-card p-5 min-w-0">
        <h3 class="sa-card-title">School at a Glance</h3>
        <p class="sa-card-sub">Everyone and everything on the school's books</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3 mt-4">
            @foreach ([['Students', $g['students'] ?? 0], ['Boys', $g['boys'] ?? 0], ['Girls', $g['girls'] ?? 0], ['Teachers', $g['teachers'] ?? 0],
                       ['Classes', $g['classes'] ?? 0], ['Sections', $g['sections'] ?? 0], ['Subjects', $g['subjects'] ?? 0], ['Bus riders', $g['riders'] ?? 0]] as [$label, $value])
                <div class="and-tile"><p>{{ $label }}</p><p>{{ $num($value) }}</p></div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
        <div class="sa-card min-w-0 overflow-hidden xl:col-span-3">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Students by Class</h3>
                <p class="sa-card-sub">Sections, students, boys and girls, and who joined in the last 30 days</p>
            </div>
            @php $rows = $d['byClass'] ?? []; $most = max(1, collect($rows)->max('students') ?: 1); @endphp
            @if ($rows)
                <div class="overflow-x-auto">
                    <table class="and-table min-w-[560px]">
                        <thead><tr>
                            <th class="{{ $th }} text-left">Class</th><th class="{{ $th }} text-center">Sections</th>
                            <th class="{{ $th }} text-right">Students</th><th class="{{ $th }} text-right">Boys</th>
                            <th class="{{ $th }} text-right">Girls</th><th class="{{ $th }} text-right">New</th><th class="{{ $th }} w-32"></th>
                        </tr></thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="{{ $td }} font-medium text-gray-800">{{ $r['name'] }}</td>
                                    <td class="{{ $td }} text-center text-gray-600 tabular-nums">{{ $r['sections'] }}</td>
                                    <td class="{{ $td }} text-right font-semibold text-gray-900 tabular-nums">{{ $num($r['students']) }}</td>
                                    <td class="{{ $td }} text-right text-gray-600 tabular-nums">{{ $num($r['boys']) }}</td>
                                    <td class="{{ $td }} text-right text-gray-600 tabular-nums">{{ $num($r['girls']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums {{ $r['new'] ? 'text-teal-700 font-medium' : 'text-gray-400' }}">{{ $r['new'] ? '+' . $r['new'] : '—' }}</td>
                                    <td class="{{ $td }}"><div class="and-bar"><span style="width: {{ round($r['students'] / $most * 100) }}%; background: #0d9488"></span></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot><tr>
                            <td class="{{ $td }} font-semibold text-gray-800">Total</td>
                            <td class="{{ $td }} text-center font-semibold tabular-nums">{{ $num(collect($rows)->sum('sections')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('students')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('boys')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('girls')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('new')) }}</td>
                            <td></td>
                        </tr></tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No classes yet.</div>
            @endif
        </div>

        <div class="sa-card min-w-0 overflow-hidden xl:col-span-2">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">This Month vs Last Month</h3>
                <p class="sa-card-sub">{{ now()->format('F') }} so far, against {{ now()->startOfMonth()->subMonthNoOverflow()->format('F') }}</p>
            </div>
            <table class="and-table">
                <thead><tr>
                    <th class="{{ $th }} text-left">Figure</th><th class="{{ $th }} text-right">{{ now()->format('M') }}</th>
                    <th class="{{ $th }} text-right">{{ now()->startOfMonth()->subMonthNoOverflow()->format('M') }}</th><th class="{{ $th }} text-right">Change</th>
                </tr></thead>
                <tbody>
                    @foreach ($d['months'] ?? [] as [$label, $now, $before, $kind])
                        @php
                            $show = fn ($v) => $v === null ? '—' : ($kind === 'money' ? $inr($v) : ($kind === 'pct' ? $pctTxt($v) : $num($v)));
                            $diff = ($now === null || $before === null) ? null : $now - $before;
                        @endphp
                        <tr>
                            <td class="{{ $td }} text-gray-700">{{ $label }}</td>
                            <td class="{{ $td }} text-right font-semibold text-gray-900 tabular-nums">{{ $show($now) }}</td>
                            <td class="{{ $td }} text-right text-gray-500 tabular-nums">{{ $show($before) }}</td>
                            <td class="{{ $td }} text-right tabular-nums text-xs font-medium {{ $diff === null || $diff == 0 ? 'text-gray-400' : ($diff > 0 ? 'text-emerald-600' : 'text-rose-600') }}">
                                @if ($diff === null || $diff == 0) — @else {{ $diff > 0 ? '▲' : '▼' }} {{ $kind === 'money' ? $inr(abs($diff)) : ($kind === 'pct' ? rtrim(rtrim(number_format(abs($diff), 1), '0'), '.') . ' pts' : $num(abs($diff))) }} @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

{{-- ═══════════════════════ ATTENDANCE ═══════════════════════ --}}
@if ($part === 'attendance')
    <div class="sa-card min-w-0 overflow-hidden">
        <div class="px-5 pt-5 pb-3">
            <h3 class="sa-card-title">Today, Class by Class</h3>
            <p class="sa-card-sub">{{ now()->format('l, d M Y') }} · students present, on half day, absent, on holiday and not marked yet</p>
        </div>
        @php $rows = $d['todayClass'] ?? []; @endphp
        @if ($rows)
            <div class="overflow-x-auto">
                <table class="and-table min-w-[680px]">
                    <thead><tr>
                        <th class="{{ $th }} text-left">Class</th><th class="{{ $th }} text-right">Students</th><th class="{{ $th }} text-right">Present</th>
                        <th class="{{ $th }} text-right">Half day</th><th class="{{ $th }} text-right">Absent</th><th class="{{ $th }} text-right">Holiday</th>
                        <th class="{{ $th }} text-right">Not marked</th><th class="{{ $th }} text-right w-40">Present %</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="{{ $td }} font-medium text-gray-800">{{ $r['name'] }}</td>
                                <td class="{{ $td }} text-right tabular-nums">{{ $num($r['students']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-emerald-700">{{ $num($r['present']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-sky-600">{{ $num($r['half']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-rose-600">{{ $num($r['absent']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-indigo-600">{{ $num($r['holiday']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums {{ $r['unmarked'] ? 'text-amber-600 font-medium' : 'text-gray-400' }}">{{ $num($r['unmarked']) }}</td>
                                <td class="{{ $td }}">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="and-bar w-20"><span style="width: {{ $r['pct'] ?? 0 }}%; background: {{ $tone($r['pct']) }}"></span></div>
                                        <span class="w-12 text-right font-semibold tabular-nums">{{ $pctTxt($r['pct']) }}</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @php $c = collect($rows); $w = $c->sum('present') + $c->sum('half') + $c->sum('absent'); @endphp
                    <tfoot><tr>
                        <td class="{{ $td }} font-semibold">All classes</td>
                        @foreach (['students', 'present', 'half', 'absent', 'holiday', 'unmarked'] as $k)
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num($c->sum($k)) }}</td>
                        @endforeach
                        <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $w ? $pctTxt(round(($c->sum('present') + 0.5 * $c->sum('half')) / $w * 100, 1)) : '—' }}</td>
                    </tr></tfoot>
                </table>
            </div>
        @else
            <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No students in any class yet.</div>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        @foreach ([['Students Absent Most', 'Last 30 days · days absent and present', $d['absentees'] ?? [], true],
                   ['Teachers Absent Most', 'Last 30 days · days absent and present', $d['teacherAbs'] ?? [], false]] as [$title, $sub, $list, $isStudent])
            <div class="sa-card min-w-0 overflow-hidden">
                <div class="px-5 pt-5 pb-3"><h3 class="sa-card-title">{{ $title }}</h3><p class="sa-card-sub">{{ $sub }}</p></div>
                @if ($list)
                    <div class="divide-y divide-gray-100 border-t border-gray-100">
                        @foreach ($list as $i => $r)
                            <div class="flex items-center gap-3 px-5 py-2.5">
                                <span class="w-6 text-xs text-gray-400 tabular-nums">{{ $i + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 truncate">{{ $r['name'] }}</p>
                                    @if ($isStudent)<p class="text-xs text-gray-400 truncate">{{ $r['class'] }}</p>@endif
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <p class="text-sm font-semibold text-rose-600 tabular-nums">{{ $r['absent'] }} absent</p>
                                    <p class="text-[11px] text-gray-400 tabular-nums">{{ $r['present'] }} present · {{ $r['pct'] }}%</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">Nobody was absent in the last 30 days.</div>
                @endif
            </div>
        @endforeach

        <div class="sa-card p-5 min-w-0">
            <h3 class="sa-card-title">By Day of the Week</h3>
            <p class="sa-card-sub">Students' attendance · each weekday averaged over 8 weeks</p>
            <div class="space-y-3.5 mt-4">
                @forelse ($d['weekdays'] ?? [] as $r)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700">{{ $r['day'] }} <span class="text-[11px] text-gray-400">· {{ $r['days'] }} {{ $r['days'] === 1 ? 'day' : 'days' }}</span></span>
                            <span class="font-semibold tabular-nums">{{ $pctTxt($r['pct']) }}</span>
                        </div>
                        <div class="and-bar mt-1.5"><span style="width: {{ $r['pct'] ?? 0 }}%; background: {{ $tone($r['pct']) }}"></span></div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400 py-6 text-center">No attendance in the last 8 weeks.</p>
                @endforelse
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════ PERFORMANCE ═══════════════════════ --}}
@if ($part === 'performance')
    @php $ex = (array) ($d['exams'] ?? []) + ['pass' => 33, 'rows' => []]; @endphp
    <div class="sa-card min-w-0 overflow-hidden">
        <div class="px-5 pt-5 pb-3">
            <h3 class="sa-card-title">Exam by Exam</h3>
            <p class="sa-card-sub">The last {{ count($ex['rows']) ?: 10 }} exams with marks · a student passes at {{ rtrim(rtrim(number_format((float) $ex['pass'], 1), '0'), '.') }}% overall · follows the filter above</p>
        </div>
        @if ($ex['rows'])
            <div class="overflow-x-auto">
                <table class="and-table min-w-[700px]">
                    <thead><tr>
                        <th class="{{ $th }} text-left">Exam</th><th class="{{ $th }} text-left">Date</th><th class="{{ $th }} text-right">Students</th>
                        <th class="{{ $th }} text-right">Average</th><th class="{{ $th }} text-right">Passed</th><th class="{{ $th }} text-right">Highest</th>
                        <th class="{{ $th }} text-right">Lowest</th><th class="{{ $th }} text-right">Papers missed</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($ex['rows'] as $r)
                            <tr>
                                <td class="{{ $td }} font-medium text-gray-800">{{ $r['exam'] }}</td>
                                <td class="{{ $td }} text-gray-500 whitespace-nowrap">{{ $r['date'] }}</td>
                                <td class="{{ $td }} text-right tabular-nums">{{ $num($r['students']) }}</td>
                                <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $pctTxt($r['avg']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums" style="color: {{ $tone($r['passPct'], 90, 75) }}">{{ $pctTxt($r['passPct']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-emerald-700">{{ $pctTxt($r['high']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-rose-600">{{ $pctTxt($r['low']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums {{ $r['missed'] ? 'text-amber-600' : 'text-gray-400' }}">{{ $num($r['missed']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No exam marks for the selected filters.</div>
        @endif
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="sa-card min-w-0 overflow-hidden xl:col-span-2">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Subject by Subject</h3>
                <p class="sa-card-sub">Every exam paper of the subject · strongest first · follows the filter above</p>
            </div>
            @php $rows = $d['subjects'] ?? []; @endphp
            @if ($rows)
                <div class="overflow-x-auto max-h-[26rem] overflow-y-auto">
                    <table class="and-table min-w-[560px]">
                        <thead><tr>
                            <th class="{{ $th }} text-left">Subject</th><th class="{{ $th }} text-right">Papers</th><th class="{{ $th }} w-40 text-right">Average</th>
                            <th class="{{ $th }} text-right">Best paper</th><th class="{{ $th }} text-right">Weakest</th><th class="{{ $th }} text-right">Missed</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="{{ $td }} font-medium text-gray-800">{{ $r['subject'] }}</td>
                                    <td class="{{ $td }} text-right tabular-nums text-gray-600">{{ $num($r['papers']) }}</td>
                                    <td class="{{ $td }}">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="and-bar w-16"><span style="width: {{ min(100, $r['avg']) }}%; background: {{ $tone($r['avg'], 75, 50) }}"></span></div>
                                            <span class="w-12 text-right font-semibold tabular-nums">{{ $pctTxt($r['avg']) }}</span>
                                        </div>
                                    </td>
                                    <td class="{{ $td }} text-right tabular-nums text-emerald-700">{{ $pctTxt($r['high']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums text-rose-600">{{ $pctTxt($r['low']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums {{ $r['missed'] ? 'text-amber-600' : 'text-gray-400' }}">{{ $num($r['missed']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No subject marks for the selected filters.</div>
            @endif
        </div>

        <div class="sa-card p-5 min-w-0">
            <h3 class="sa-card-title">How the Marks Spread</h3>
            <p class="sa-card-sub">Students by their overall % · follows the filter above</p>
            @php $bands = $d['bands'] ?? []; $bandMax = max(1, collect($bands)->max('n') ?: 1); @endphp
            <div class="space-y-3.5 mt-4">
                @foreach ($bands as $i => $b)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-700">{{ $b['label'] }}</span>
                            <span class="tabular-nums"><span class="font-semibold">{{ $num($b['n']) }}</span> <span class="text-[11px] text-gray-400">· {{ $pctTxt($b['pct']) }}</span></span>
                        </div>
                        <div class="and-bar mt-1.5"><span style="width: {{ round($b['n'] / $bandMax * 100) }}%; background: {{ ['#0d9488', '#14b8a6', '#5eead4', '#f59e0b', '#fb923c', '#f43f5e'][$i] ?? '#0d9488' }}"></span></div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="sa-card p-5 min-w-0">
        <h3 class="sa-card-title">Class-wise Average</h3>
        <p class="sa-card-sub">Every exam paper of each class · the whole school</p>
        @php $rows = $d['classAvg'] ?? []; @endphp
        @if ($rows)
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3.5 mt-4">
                @foreach ($rows as $r)
                    <div class="min-w-0">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <p class="font-medium text-gray-800 truncate">{{ $r['name'] }} <span class="text-[11px] text-gray-400 font-normal">· {{ $num($r['students']) }} {{ $r['students'] === 1 ? 'student' : 'students' }}</span></p>
                            <p class="font-semibold tabular-nums">{{ $pctTxt($r['avg']) }}</p>
                        </div>
                        <div class="and-bar mt-1.5"><span style="width: {{ min(100, $r['avg']) }}%; background: {{ $tone($r['avg'], 75, 50) }}"></span></div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 py-6 text-center">No exam marks yet.</p>
        @endif
    </div>
@endif

{{-- ═══════════════════════ ADMISSIONS & ENQUIRIES ═══════════════════════ --}}
@if ($part === 'admissions')
    @php $apps = $d['apps'] ?? []; @endphp
    <div class="grid grid-cols-1 xl:grid-cols-7 gap-5">
        <div class="sa-card min-w-0 overflow-hidden xl:col-span-2">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Admitted by Class</h3>
                <p class="sa-card-sub">Apr {{ $admissionYear }} – Mar {{ (int) $admissionYear + 1 }} · follows the year above</p>
            </div>
            @php $rows = $d['admitClass'] ?? []; @endphp
            @if ($rows)
                <table class="and-table">
                    <thead><tr><th class="{{ $th }} text-left">Class</th><th class="{{ $th }} text-right">Admitted</th><th class="{{ $th }} text-right">Boys</th><th class="{{ $th }} text-right">Girls</th></tr></thead>
                    <tbody>
                        @foreach ($rows as $r)
                            <tr>
                                <td class="{{ $td }} font-medium text-gray-800">{{ $r['name'] }}</td>
                                <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num($r['n']) }}</td>
                                <td class="{{ $td }} text-right text-gray-600 tabular-nums">{{ $num($r['boys']) }}</td>
                                <td class="{{ $td }} text-right text-gray-600 tabular-nums">{{ $num($r['girls']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr>
                        <td class="{{ $td }} font-semibold">Total</td>
                        <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('n')) }}</td>
                        <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('boys')) }}</td>
                        <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $num(collect($rows)->sum('girls')) }}</td>
                    </tr></tfoot>
                </table>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No admissions in this school year.</div>
            @endif
        </div>

        <div class="sa-card p-5 min-w-0 xl:col-span-2">
            <h3 class="sa-card-title">Admission Applications</h3>
            <p class="sa-card-sub">More → Admissions · from the panel and the website</p>
            <div class="grid grid-cols-2 gap-3 mt-4">
                @foreach ([['All', $num($apps['total'] ?? 0)], ['This month', $num($apps['thisMonth'] ?? 0)], ['From the website', $num($apps['website'] ?? 0)], ['Fee taken', $inr($apps['collected'] ?? 0)]] as [$label, $value])
                    <div class="and-tile"><p>{{ $label }}</p><p>{{ $value }}</p></div>
                @endforeach
            </div>
            @if (!empty($apps['status']))
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mt-5 mb-1">By status</p>
                @foreach ($apps['status'] as $s)
                    <div class="flex items-center justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                        <span class="text-gray-700">{{ $s['label'] }}</span><span class="font-semibold tabular-nums">{{ $num($s['n']) }}</span>
                    </div>
                @endforeach
            @endif
            @if (!empty($apps['classes']))
                <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mt-5 mb-1">Classes applied for</p>
                @foreach ($apps['classes'] as $c)
                    <div class="flex items-center justify-between py-1.5 text-sm border-b border-gray-100 last:border-0">
                        <span class="text-gray-700">{{ $c['name'] }}</span><span class="font-semibold tabular-nums">{{ $num($c['n']) }}</span>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="sa-card min-w-0 overflow-hidden xl:col-span-3">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Enquiries by Month</h3>
                <p class="sa-card-sub">The last 6 months · by where they came from</p>
            </div>
            @php $thc = 'px-2.5 py-2.5 text-[10.5px] font-semibold uppercase tracking-wide text-gray-400'; $tdc = 'px-2.5 py-2.5 text-sm'; @endphp
            <div class="overflow-x-auto">
                <table class="and-table">
                    <thead><tr>
                        <th class="{{ $thc }} pl-5 text-left">Month</th><th class="{{ $thc }} text-right">Student</th><th class="{{ $thc }} text-right">Teacher</th>
                        <th class="{{ $thc }} text-right">Website</th><th class="{{ $thc }} pr-5 text-right">Admission</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($d['enqMonths'] ?? [] as $r)
                            <tr>
                                <td class="{{ $tdc }} pl-5 text-gray-700 whitespace-nowrap">{{ $r['label'] }}</td>
                                @foreach (['student', 'teacher', 'website', 'admission'] as $k)
                                    <td class="{{ $tdc }} {{ $k === 'admission' ? 'pr-5' : '' }} text-right tabular-nums {{ $r[$k] ? 'font-semibold text-gray-900' : 'text-gray-300' }}">{{ $num($r[$k]) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

{{-- ═══════════════════════ FEE ═══════════════════════ --}}
@if ($part === 'fee')
    <div class="sa-card p-5 min-w-0">
        <h3 class="sa-card-title">Collected Over Time</h3>
        <p class="sa-card-sub">Academic and transport fee that came in</p>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3 mt-4">
            @foreach ($d['feeWindows'] ?? [] as [$label, $amount])
                <div class="and-tile"><p>{{ $label }}</p><p>{{ $inr($amount) }}</p></div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5">
        <div class="sa-card min-w-0 overflow-hidden xl:col-span-3">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Class-wise Fee</h3>
                <p class="sa-card-sub">Total, collected and remaining for each class</p>
            </div>
            @php
                $fc = $feeClassData ?? [];
                $rows = collect($fc['labels'] ?? [])->map(fn ($name, $i) => [
                    'name' => $name, 'collected' => (float) ($fc['collected'][$i] ?? 0), 'remaining' => (float) ($fc['remaining'][$i] ?? 0),
                ])->map(fn ($r) => $r + ['total' => $r['collected'] + $r['remaining']]);
            @endphp
            @if ($rows->count())
                <div class="overflow-x-auto">
                    <table class="and-table min-w-[560px]">
                        <thead><tr>
                            <th class="{{ $th }} text-left">Class</th><th class="{{ $th }} text-right">Total</th><th class="{{ $th }} text-right">Collected</th>
                            <th class="{{ $th }} text-right">Remaining</th><th class="{{ $th }} text-right w-36">Recovered</th>
                        </tr></thead>
                        <tbody>
                            @foreach ($rows as $r)
                                @php $p = $r['total'] > 0 ? round($r['collected'] / $r['total'] * 100, 1) : null; @endphp
                                <tr>
                                    <td class="{{ $td }} font-medium text-gray-800">{{ $r['name'] }}</td>
                                    <td class="{{ $td }} text-right tabular-nums">{{ $inr($r['total']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums text-emerald-700">{{ $inr($r['collected']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums text-rose-600">{{ $inr($r['remaining']) }}</td>
                                    <td class="{{ $td }}">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="and-bar w-14"><span style="width: {{ $p ?? 0 }}%; background: {{ $tone($p, 80, 50) }}"></span></div>
                                            <span class="w-12 text-right font-semibold tabular-nums">{{ $pctTxt($p) }}</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        @php $tot = $rows->sum('total'); @endphp
                        <tfoot><tr>
                            <td class="{{ $td }} font-semibold">All classes</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $inr($tot) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $inr($rows->sum('collected')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $inr($rows->sum('remaining')) }}</td>
                            <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $tot > 0 ? $pctTxt(round($rows->sum('collected') / $tot * 100, 1)) : '—' }}</td>
                        </tr></tfoot>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No fee data available.</div>
            @endif
        </div>

        <div class="sa-card min-w-0 overflow-hidden xl:col-span-2">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">How It Was Paid</h3>
                <p class="sa-card-sub">This session · each payment mode</p>
            </div>
            @if (!empty($d['modes']))
                <table class="and-table">
                    <thead><tr><th class="{{ $th }} text-left">Mode</th><th class="{{ $th }} text-right">Payments</th><th class="{{ $th }} text-right">Amount</th><th class="{{ $th }} text-right">Share</th></tr></thead>
                    <tbody>
                        @foreach ($d['modes'] as $m)
                            <tr>
                                <td class="{{ $td }} font-medium text-gray-800">{{ $m['mode'] }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-gray-600">{{ $num($m['n']) }}</td>
                                <td class="{{ $td }} text-right font-semibold tabular-nums">{{ $inr($m['amount']) }}</td>
                                <td class="{{ $td }} text-right tabular-nums text-gray-600">{{ $pctTxt($m['share']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No payments this session yet.</div>
            @endif
        </div>
    </div>

    <div class="sa-card min-w-0 overflow-hidden">
        <div class="px-5 pt-5 pb-3">
            <h3 class="sa-card-title">Latest Payments</h3>
            <p class="sa-card-sub">The last 10 payments taken</p>
        </div>
        @if (!empty($d['recentPay']))
            <div class="overflow-x-auto">
                <table class="and-table min-w-[640px]">
                    <thead><tr>
                        <th class="{{ $th }} text-left">Date</th><th class="{{ $th }} text-left">Student</th><th class="{{ $th }} text-left">Class</th>
                        <th class="{{ $th }} text-left">Fee</th><th class="{{ $th }} text-left">Mode</th><th class="{{ $th }} text-left">Receipt</th><th class="{{ $th }} text-right">Amount</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($d['recentPay'] as $p)
                            <tr>
                                <td class="{{ $td }} text-gray-500 whitespace-nowrap">{{ $p['date'] }}</td>
                                <td class="{{ $td }} font-medium text-gray-800">{{ $p['name'] }}</td>
                                <td class="{{ $td }} text-gray-600">{{ $p['class'] }}</td>
                                <td class="{{ $td }} text-gray-600">{{ $p['kind'] }}</td>
                                <td class="{{ $td }} text-gray-600">{{ $p['mode'] }}</td>
                                <td class="{{ $td }} text-gray-500">{{ $p['receipt'] ?: '—' }}</td>
                                <td class="{{ $td }} text-right font-semibold tabular-nums text-emerald-700">{{ $inr($p['amount']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No payments yet.</div>
        @endif
    </div>
@endif

{{-- ═══════════════════════ OPERATIONS ═══════════════════════ --}}
@if ($part === 'operations')
    <div class="sa-card p-5 min-w-0">
        <h3 class="sa-card-title">The Day at School</h3>
        <p class="sa-card-sub">{{ now()->format('l, d M Y') }} · the front desk, homework and notices</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
            @foreach ($d['desk'] ?? [] as [$label, $value])
                <div class="and-tile"><p>{{ $label }}</p><p>{{ $num($value) }}</p></div>
            @endforeach
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        <div class="sa-card min-w-0 overflow-hidden">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Homework · Last 7 Days</h3>
                <p class="sa-card-sub">Given to each class, in how many subjects, and the last day</p>
            </div>
            @php $rows = $d['homework'] ?? []; $hwMax = max(1, collect($rows)->max('n') ?: 1); @endphp
            @if ($rows)
                <div class="max-h-[26rem] overflow-y-auto">
                    <table class="and-table">
                        <thead><tr><th class="{{ $th }} text-left">Class</th><th class="{{ $th }} text-right">Homework</th><th class="{{ $th }} text-right">Subjects</th><th class="{{ $th }} text-right">Last</th><th class="{{ $th }} w-28"></th></tr></thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="{{ $td }} font-medium text-gray-800">{{ $r['name'] }}</td>
                                    <td class="{{ $td }} text-right tabular-nums {{ $r['n'] ? 'font-semibold' : 'text-gray-300' }}">{{ $num($r['n']) }}</td>
                                    <td class="{{ $td }} text-right tabular-nums text-gray-600">{{ $num($r['subjects']) }}</td>
                                    <td class="{{ $td }} text-right text-gray-500 whitespace-nowrap">{{ $r['last'] }}</td>
                                    <td class="{{ $td }}"><div class="and-bar"><span style="width: {{ round($r['n'] / $hwMax * 100) }}%; background: #0d9488"></span></div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No classes yet.</div>
            @endif
        </div>

        <div class="sa-card min-w-0 overflow-hidden">
            <div class="px-5 pt-5 pb-3">
                <h3 class="sa-card-title">Latest Complaints</h3>
                <p class="sa-card-sub">More → Complaints · the last 5</p>
            </div>
            @if (!empty($d['complaints']))
                <div class="divide-y divide-gray-100 border-t border-gray-100">
                    @foreach ($d['complaints'] as $c)
                        <div class="px-5 py-3 min-w-0">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-gray-800 truncate">{{ $c['title'] }}</p>
                                <span class="text-[11px] text-gray-400 whitespace-nowrap">{{ $c['date'] }}</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate mt-0.5">{{ $c['name'] }} · {{ $c['class'] }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-10 text-gray-400 text-sm border-t border-gray-100">No complaints recorded.</div>
            @endif
        </div>
    </div>
@endif
