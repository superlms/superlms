{{-- Skeleton rows of a Super Admin list's table, shaped as its real rows: a
     short bar for S.No, a round photo with a name and a line under it, bars
     of differing length for the other columns, and the dot and ⋮ at the end
     (or the dot and the row's buttons).

     $cols = the row's cells in order: 'sno' | 'person' | 'actions' (dot, ⋮) |
             'buttons' (dot, four icon buttons) | 'arrow' (dot, the › button) |
             a number (how much of its column the bar may fill, in %);
     $rows = how many rows (default 8). --}}
@php
    $rows = $rows ?? 8;
    // Each row's bars differ a little, as real names and numbers do.
    $vary = [100, 74, 88, 62, 94, 80, 68, 90, 58, 84];
@endphp
@for ($r = 0; $r < $rows; $r++)
    <tr aria-hidden="true" data-skeleton-row>
        @foreach ($cols as $c => $col)
            @php $w = $vary[($r * 3 + $c) % count($vary)]; @endphp
            @if ($col === 'sno')
                <td class="pl-4 pr-2 py-3"><span class="sa-skel h-3 w-5 align-middle"></span></td>
            @elseif ($col === 'person')
                <td class="px-3 py-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="sa-skel w-8 h-8 rounded-full flex-shrink-0"></span>
                        <div class="flex-1 min-w-0 space-y-1.5">
                            <span class="sa-skel block h-3" style="width: {{ max(45, $w - 10) }}%"></span>
                            <span class="sa-skel block h-2.5" style="width: {{ max(25, $w - 50) }}%"></span>
                        </div>
                    </div>
                </td>
            @elseif ($col === 'actions')
                <td class="px-2 py-3">
                    <div class="flex items-center justify-end gap-2.5 pr-2">
                        <span class="sa-skel w-2 h-2 rounded-full"></span>
                        <span class="sa-skel w-1 h-4 rounded-full"></span>
                    </div>
                </td>
            @elseif ($col === 'buttons' || $col === 'arrow')
                <td class="px-2 py-3">
                    <div class="flex items-center justify-end gap-1">
                        <span class="sa-skel w-2 h-2 rounded-full mr-1"></span>
                        @if ($col === 'arrow')
                            <span class="sa-skel w-9 h-9 rounded-lg"></span>
                        @else
                            @for ($b = 0; $b < 4; $b++)
                                <span class="sa-skel w-7 h-7 rounded-lg"></span>
                            @endfor
                        @endif
                    </div>
                </td>
            @else
                <td class="px-3 py-3"><span class="sa-skel block h-3" style="width: {{ max(20, (int) round($col * $w / 100)) }}%"></span></td>
            @endif
        @endforeach
    </tr>
@endfor
