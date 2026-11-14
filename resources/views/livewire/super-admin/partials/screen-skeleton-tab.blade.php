{{-- The body of a page's tab as a skeleton (Super Admin → Schools → a school),
     shown while another tab, or a student or teacher of it, takes longer than
     ListSkeleton::DELAY_MS to open.

     $kind = 'fields' (two columns of name and value, as Overview, the
     settings tabs) | 'table' (a toolbar over rows, as Students, Teachers,
     Fees Analytics). --}}
@php
    $vary = [72, 58, 84, 66, 90, 62, 78, 54, 86, 70];
@endphp
<div class="p-5" aria-hidden="true" data-tab-skeleton>
    @if (($kind ?? 'fields') === 'table')
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div class="flex items-center gap-3">
                @for ($i = 0; $i < 3; $i++)
                    <span class="sa-skel h-14 w-28 rounded-xl"></span>
                @endfor
            </div>
            <span class="sa-skel h-8 w-52 rounded-lg"></span>
        </div>
        <div class="rounded-lg border border-gray-100 overflow-hidden">
            <div class="flex items-center gap-4 px-3 py-2.5 bg-gray-50 border-b border-gray-100">
                @foreach ([6, 28, 18, 16, 16, 12] as $w)
                    <span class="sa-skel h-2.5" style="width: {{ $w }}%"></span>
                @endforeach
            </div>
            @for ($r = 0; $r < 8; $r++)
                <div class="flex items-center gap-4 px-3 py-3 border-b border-gray-100 last:border-0">
                    <span class="sa-skel h-3 flex-shrink-0" style="width: 6%"></span>
                    <div class="flex items-center gap-2.5 min-w-0" style="width: 28%">
                        <span class="sa-skel w-8 h-8 rounded-full flex-shrink-0"></span>
                        <div class="flex-1 space-y-1.5">
                            <span class="sa-skel block h-3" style="width: {{ $vary[$r] }}%"></span>
                            <span class="sa-skel block h-2.5" style="width: {{ $vary[($r + 4) % 10] - 30 }}%"></span>
                        </div>
                    </div>
                    <span class="sa-skel h-3" style="width: {{ round(18 * $vary[($r + 1) % 10] / 100) }}%"></span>
                    <span class="sa-skel h-3" style="width: {{ round(16 * $vary[($r + 2) % 10] / 100) }}%"></span>
                    <span class="sa-skel h-3" style="width: {{ round(16 * $vary[($r + 3) % 10] / 100) }}%"></span>
                    <span class="sa-skel h-5 w-14 rounded-full"></span>
                </div>
            @endfor
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-10 gap-y-2">
            @for ($c = 0; $c < 2; $c++)
                <div>
                    <div class="mb-2 pb-2 border-b border-gray-100"><span class="sa-skel block h-2.5 w-32"></span></div>
                    <div class="divide-y divide-gray-100">
                        @for ($r = 0; $r < 5; $r++)
                            <div class="flex items-center gap-4 py-3">
                                <span class="w-28 flex-shrink-0"><span class="sa-skel block h-2.5" style="width: {{ $vary[($c * 5 + $r) % 10] }}%"></span></span>
                                <span class="sa-skel block h-3" style="width: {{ round(0.6 * $vary[($c * 5 + $r + 3) % 10]) }}%"></span>
                            </div>
                        @endfor
                    </div>
                </div>
            @endfor
        </div>
    @endif
</div>
