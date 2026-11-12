{{-- Skeleton cards of a Super Admin list on a phone, shaped as its real cards:
     a round photo with a name and a line under it, then two short facts.
     $cards = how many (default 4). --}}
@for ($i = 0; $i < ($cards ?? 4); $i++)
    <div class="bg-white rounded-xl border border-gray-200 p-4 space-y-3" aria-hidden="true" data-skeleton-card>
        <div class="flex items-center gap-3">
            <span class="sa-skel w-10 h-10 rounded-full flex-shrink-0"></span>
            <div class="flex-1 space-y-1.5">
                <span class="sa-skel block h-3.5" style="width: {{ [66, 54, 72, 60][$i % 4] }}%"></span>
                <span class="sa-skel block h-2.5 w-1/3"></span>
            </div>
            <span class="sa-skel w-2 h-2 rounded-full"></span>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <span class="sa-skel block h-3 w-3/4"></span>
            <span class="sa-skel block h-3 w-2/3"></span>
        </div>
    </div>
@endfor
