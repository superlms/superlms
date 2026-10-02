{{--
    Teacher attendance for one month as a grid: the dates down the first
    column, one column per teacher, each cell that teacher's status that day.
    Expects: $grid (title, teachers, rows, totals), $statusPill, $statusText.
--}}
@php
    $short = ['present' => 'P', 'absent' => 'A', 'half_day' => 'HD', 'holiday' => 'H', 'not_marked' => '—'];
@endphp
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
    <div class="px-4 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-semibold text-gray-800">Teacher Attendance · {{ $grid['title'] }}</h3>
        <div class="flex flex-wrap items-center gap-2 text-[11px] text-gray-500">
            @foreach (['present', 'absent', 'half_day', 'holiday', 'not_marked'] as $st)
                <span class="inline-flex items-center gap-1">
                    <span class="inline-flex items-center justify-center min-w-[1.5rem] px-1 py-0.5 rounded font-semibold {{ $statusPill($st) }}">{{ $short[$st] }}</span>
                    {{ $statusText($st) }}
                </span>
            @endforeach
        </div>
    </div>

    @if (empty($grid['teachers']))
        <p class="py-12 text-center text-gray-400 text-sm">No teachers found.</p>
    @else
        {{-- The grid is as tall as the room left under the filter bar, so the
             whole card fits the window once the page's title and tabs have slid
             away. At 70% of the window it was taller than that: the page ran on
             a little further and stopped with this card's heading caught half
             behind the filter bar. The height is kept on #main-scroll, outside
             what Livewire re-renders, and measured again whenever the window,
             the page header or the heading changes size. --}}
        <div class="overflow-auto max-h-[70vh]" style="max-height: var(--lms-grid-h, 70vh)"
            x-data="{
                ro: null,
                fit() {
                    const page = document.getElementById('main-scroll');
                    if (!page) return;
                    const head = page.querySelector('div.sticky.top-0');
                    const bar = head ? Array.from(head.children).filter(el => el.classList.contains('bg-gray-50')).pop() : null;
                    const heading = this.$el.previousElementSibling;
                    /* 72 = the page's padding over and under the card, the
                       card's margin and its border. */
                    const room = page.clientHeight - (bar ? bar.offsetHeight : 0) - (heading ? heading.offsetHeight : 0) - 72;
                    page.style.setProperty('--lms-grid-h', Math.max(room, 260) + 'px');
                },
                init() {
                    this.fit();
                    if (typeof ResizeObserver === 'undefined') return;
                    this.ro = new ResizeObserver(() => this.fit());
                    const page = document.getElementById('main-scroll');
                    const head = page ? page.querySelector('div.sticky.top-0') : null;
                    [page, head, this.$el.previousElementSibling].forEach(el => el && this.ro.observe(el));
                },
                destroy() { this.ro && this.ro.disconnect(); },
            }"
            x-on:resize.window.debounce.150ms="fit()">
            <table class="min-w-full text-xs border-separate border-spacing-0">
                <thead>
                    <tr>
                        <th class="sticky top-0 left-0 z-20 bg-gray-50 border-b border-r border-gray-200 px-3 py-2 text-left align-bottom font-semibold text-gray-500 uppercase whitespace-nowrap">Date</th>
                        @foreach ($grid['teachers'] as $t)
                            {{-- Names run bottom to top, so each column is only as wide as a cell. --}}
                            <th class="sticky top-0 z-10 bg-gray-50 border-b border-gray-200 px-1 py-2 align-bottom font-medium text-gray-700" title="{{ $t['name'] }}">
                                <span class="inline-block max-h-40 overflow-hidden text-ellipsis whitespace-nowrap [writing-mode:vertical-rl] rotate-180">{{ $t['name'] }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['rows'] as $row)
                        <tr wire:key="tmg-{{ $row['date'] }}" class="{{ $row['sunday'] ? 'bg-gray-50' : '' }}">
                            <td class="sticky left-0 z-10 border-b border-r border-gray-100 px-3 py-1.5 whitespace-nowrap {{ $row['today'] ? 'bg-blue-50 text-blue-700 font-semibold' : ($row['sunday'] ? 'bg-gray-50 text-gray-400' : 'bg-white text-gray-700') }}">
                                {{ $row['label'] }} <span class="text-gray-400 font-normal">{{ $row['dow'] }}</span>
                            </td>
                            @foreach ($grid['teachers'] as $t)
                                @php $st = $row['cells'][$t['id']] ?? null; @endphp
                                <td class="border-b border-gray-100 px-1 py-1.5 text-center">
                                    @if ($st)
                                        <span class="inline-flex items-center justify-center min-w-[1.75rem] px-1 py-0.5 rounded font-semibold {{ $statusPill($st) }}" title="{{ $t['name'] }} · {{ $row['label'] }} · {{ $statusText($st) }}">{{ $short[$st] ?? '—' }}</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="sticky left-0 z-10 bg-gray-50 border-r border-gray-200 px-3 py-2 font-semibold text-gray-500 uppercase whitespace-nowrap">Total</td>
                        @foreach ($grid['teachers'] as $t)
                            @php $tot = $grid['totals'][$t['id']]; @endphp
                            <td class="bg-gray-50 px-1 py-2 text-center whitespace-nowrap text-[11px] leading-5">
                                <span class="text-emerald-700 font-semibold">P {{ $tot['present'] }}</span>
                                <span class="text-red-600 font-semibold ml-1">A {{ $tot['absent'] }}</span>
                                @if ($tot['half_day'])<span class="text-amber-700 font-semibold ml-1">HD {{ $tot['half_day'] }}</span>@endif
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
