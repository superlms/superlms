{{-- A school's fee page (Super Admin → Fees → Manage Fee / Set up Fee) as a
     skeleton, shown in place of the list once it has taken longer than
     ListSkeleton::DELAY_MS to open: the header (Back, the logo, the name with
     the plan under it, the buttons), the four KPI cards, Fee Collection beside
     the donut, the three cards under them and the Payments table. Uses the
     Home's cards (partials.dash-styles, which the page includes). --}}
<div aria-busy="true" data-screen-skeleton>
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-3 sm:min-h-9">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="sa-skel h-8 w-9 sm:w-[72px] rounded-lg flex-shrink-0"></span>
                <span class="sa-skel w-9 h-9 rounded-xl flex-shrink-0"></span>
                <div class="min-w-0 flex-1 space-y-1.5 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
                    <span class="sa-skel block h-5 w-40 sm:w-60"></span>
                    <span class="sa-skel block h-2.5 w-32"></span>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="sa-skel hidden sm:inline-block h-9 w-28 rounded-lg"></span>
                <span class="sa-skel h-9 w-24 rounded-lg"></span>
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        {{-- KPIs --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5">
            @foreach ([60, 52, 66, 58] as $w)
                <div class="sa-card p-5 min-w-0">
                    <div class="flex items-center justify-between gap-2">
                        <span class="sa-skel h-3" style="width: {{ $w }}%"></span>
                        <span class="sa-skel w-8 h-8 rounded-lg flex-shrink-0"></span>
                    </div>
                    <div class="mt-3 flex items-end justify-between gap-3">
                        <span class="sa-skel h-7 w-28"></span>
                        <span class="sa-skel h-10 w-24 rounded-lg"></span>
                    </div>
                    <span class="sa-skel block h-2.5 mt-4" style="width: {{ $w + 20 }}%"></span>
                </div>
            @endforeach
        </div>

        {{-- Fee Collection + who has paid --}}
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
            <div class="sa-card p-5 xl:col-span-2 min-w-0">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-5">
                    <div class="space-y-1.5">
                        <span class="sa-skel block h-4 w-32"></span>
                        <span class="sa-skel block h-2.5 w-56"></span>
                    </div>
                    <span class="sa-skel h-6 w-28 rounded-full"></span>
                </div>
                <span class="sa-skel block h-64 rounded-xl"></span>
                <div class="mt-5 pt-4 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @for ($i = 0; $i < 4; $i++)
                        <div class="space-y-1.5">
                            <span class="sa-skel block h-2.5 w-16"></span>
                            <span class="sa-skel block h-3.5 w-20"></span>
                        </div>
                    @endfor
                </div>
            </div>
            <div class="sa-card p-5 min-w-0">
                <div class="space-y-1.5 mb-6">
                    <span class="sa-skel block h-4 w-28"></span>
                    <span class="sa-skel block h-2.5 w-32"></span>
                </div>
                <div class="flex justify-center">
                    <span class="sa-skel rounded-full" style="width: 148px; height: 148px"></span>
                </div>
                <div class="mt-6 space-y-3">
                    @foreach ([40, 52, 46] as $w)
                        <div class="flex items-center justify-between gap-3">
                            <span class="flex items-center gap-2 flex-1"><span class="sa-skel w-2.5 h-2.5 rounded-full"></span><span class="sa-skel h-3" style="width: {{ $w }}%"></span></span>
                            <span class="sa-skel h-3 w-10"></span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Who pays · the fee · its progress --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @for ($c = 0; $c < 3; $c++)
                <div class="sa-card p-5 min-w-0 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1.5 flex-1">
                            <span class="sa-skel block h-4 w-28"></span>
                            <span class="sa-skel block h-2.5 w-44"></span>
                        </div>
                        <span class="sa-skel w-9 h-9 rounded-xl"></span>
                    </div>
                    <span class="sa-skel block h-10 rounded-xl"></span>
                    <span class="sa-skel block h-3 w-3/4"></span>
                    <span class="sa-skel block h-3 w-1/2"></span>
                </div>
            @endfor
        </div>

        {{-- Payments --}}
        <div class="sa-card min-w-0 overflow-hidden">
            <div class="px-5 pt-5 pb-3 flex items-start justify-between gap-3">
                <div class="space-y-1.5">
                    <span class="sa-skel block h-4 w-24"></span>
                    <span class="sa-skel block h-2.5 w-36"></span>
                </div>
                <span class="sa-skel h-6 w-32 rounded-full"></span>
            </div>
            <div class="border-t border-gray-100 divide-y divide-gray-100">
                @foreach ([70, 84, 62, 90, 76] as $w)
                    <div class="flex items-center gap-4 px-5 py-3">
                        <span class="sa-skel h-3 w-5 flex-shrink-0"></span>
                        <span class="sa-skel h-3 w-20"></span>
                        <span class="sa-skel h-3 w-16"></span>
                        <span class="sa-skel h-5 w-14 rounded-full"></span>
                        <span class="sa-skel h-3 flex-1" style="max-width: {{ $w * 2 }}px"></span>
                        <span class="sa-skel h-6 w-14 rounded-lg ml-auto"></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
