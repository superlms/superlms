{{--
    Teacher attendance for one month as a grid: one row per teacher, the
    month's dates across as columns, each cell that teacher's status that day.
    The table is laid out to the card's width (table-fixed), so every date of
    the month and the names fit without scrolling sideways; a long name is cut
    with "…" (the whole name on hover). Totals sit in the last column.
    Expects: $grid (title, teachers, rows, totals), $statusPill, $statusText.
--}}
@php
    $short = ['present' => 'P', 'absent' => 'A', 'half_day' => '½', 'holiday' => 'H', 'not_marked' => '·'];
    // The Mark Attendance panel's soft tints.
    $tint = [
        'present'    => 'bg-emerald-50 text-emerald-700',
        'absent'     => 'bg-red-50 text-red-600',
        'half_day'   => 'bg-amber-50 text-amber-700',
        'holiday'    => 'bg-indigo-50 text-indigo-700',
        'not_marked' => 'text-gray-300',
    ];
@endphp
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-100 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
        <h3 class="text-sm font-semibold text-gray-800">Teacher Attendance · {{ $grid['title'] }}</h3>
        <p class="text-xs text-gray-400">P present · A absent · ½ half day · H holiday · <span class="text-gray-300">·</span> not marked</p>
    </div>

    @if (empty($grid['teachers']))
        <p class="py-12 text-center text-gray-400 text-sm">No teachers found.</p>
    @else
        {{-- The grid is as tall as the room left under the filter bar, so the
             whole card fits the window once the page's title and tabs have slid
             away; many teachers scroll up and down inside it, never sideways.
             The height is kept on #main-scroll, outside what Livewire
             re-renders, and measured again whenever the window, the page
             header or the heading changes size. --}}
        <div class="overflow-y-auto overflow-x-hidden max-h-[70vh]" style="max-height: var(--lms-grid-h, 70vh)"
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
            <table class="w-full table-fixed text-[10px] border-separate border-spacing-0">
                <colgroup>
                    <col class="w-32 lg:w-44">
                    @foreach ($grid['rows'] as $row)
                        <col>
                    @endforeach
                    <col class="w-20">
                </colgroup>
                <thead>
                    <tr>
                        <th class="sticky top-0 z-10 bg-gray-50 border-b border-r border-gray-200 px-3 py-2 text-left font-semibold text-gray-500 uppercase text-[10px]">Teacher</th>
                        @foreach ($grid['rows'] as $row)
                            <th class="sticky top-0 z-10 border-b border-gray-200 px-0 py-1.5 text-center font-medium leading-tight
                                       {{ $row['today'] ? 'bg-blue-50 text-blue-700' : ($row['sunday'] ? 'bg-gray-100 text-gray-400' : 'bg-gray-50 text-gray-600') }}"
                                title="{{ $row['label'] }} · {{ $row['dow'] }}">
                                <span class="block tabular-nums">{{ (int) substr($row['date'], 8, 2) }}</span>
                                <span class="block text-[8px] text-gray-400 font-normal">{{ substr($row['dow'], 0, 1) }}</span>
                            </th>
                        @endforeach
                        <th class="sticky top-0 z-10 bg-gray-50 border-b border-l border-gray-200 px-1 py-2 text-center font-semibold text-gray-500 uppercase text-[10px]">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['teachers'] as $t)
                        @php $tot = $grid['totals'][$t['id']]; @endphp
                        <tr wire:key="tmg-{{ $t['id'] }}" class="hover:bg-gray-50/60">
                            <td class="border-b border-r border-gray-100 px-3 py-1.5 text-xs text-gray-800 truncate" title="{{ $t['name'] }}">{{ $t['name'] }}</td>
                            @foreach ($grid['rows'] as $row)
                                @php $st = $row['cells'][$t['id']] ?? null; @endphp
                                <td class="border-b border-gray-100 p-[1px] text-center {{ $row['sunday'] ? 'bg-gray-50' : '' }}">
                                    @if ($st)
                                        <span class="block rounded-sm py-1 leading-none font-medium {{ $tint[$st] ?? $tint['not_marked'] }}"
                                            title="{{ $t['name'] }} · {{ $row['label'] }} · {{ $statusText($st) }}">{{ $short[$st] ?? '·' }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="border-b border-l border-gray-100 px-1.5 py-1 text-center leading-4 tabular-nums whitespace-nowrap">
                                <span class="text-gray-700">P {{ $tot['present'] }}</span><span class="text-gray-300"> · </span><span class="{{ $tot['absent'] ? 'text-red-600' : 'text-gray-400' }}">A {{ $tot['absent'] }}</span>
                                @if ($tot['half_day'])<span class="block text-amber-700">½ {{ $tot['half_day'] }}</span>@endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
