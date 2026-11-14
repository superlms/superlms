{{-- A whole Super Admin list page as a skeleton, shown once its link (or
     Back to it) has taken longer than ListSkeleton::DELAY_MS, until the page
     itself arrives: the header (the title, the figures as bars, the Add
     button), the filter bar, the table with its real column names over
     skeleton rows — and on a phone, skeleton cards.

     $title; $headers = [[name, th classes], …]; $cols (as list-skeleton-rows);
     $filters = how many filter boxes the bar has; $export (default true):
     the bar ends in Export. --}}
<div class="min-h-screen bg-gray-50" aria-busy="true" data-page-skeleton>
    <div class="bg-white border-b border-gray-200">
        <div class="px-4 sm:px-6 py-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:min-h-9">
                <h1 class="text-lg sm:text-xl font-bold text-gray-900">{{ $title }}</h1>
                <div class="flex items-center gap-4">
                    <div class="hidden lg:flex items-center gap-6">
                        @foreach ([20, 24, 22, 22, 20] as $w)
                            <span class="sa-skel h-3.5" style="width: {{ $w * 4 }}px"></span>
                        @endforeach
                    </div>
                    <span class="sa-skel h-9 w-40 rounded-lg"></span>
                </div>
            </div>
        </div>
        <div class="border-t border-gray-200 bg-gray-50 px-4 sm:px-6 py-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-gray-600">Filter by:</span>
                <span class="sa-skel h-6 w-52 rounded-md"></span>
                @for ($f = 0; $f < $filters; $f++)
                    <span class="sa-skel h-6 w-28 rounded-md"></span>
                @endfor
                @if ($export ?? true)
                    <span class="sa-skel h-6 w-36 rounded-md sm:ml-auto"></span>
                @endif
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-4">
        <div class="hidden md:block rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full min-w-[960px] xl:min-w-0 table-fixed text-[13px] text-gray-700">
                <thead>
                    <tr class="bg-gray-50/80">
                        @foreach ($headers as [$name, $class])
                            <th class="{{ $class }}">{{ $name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @include('livewire.super-admin.partials.list-skeleton-rows', ['cols' => $cols, 'rows' => 10])
                </tbody>
            </table>
        </div>

        <div class="md:hidden space-y-3">
            @include('livewire.super-admin.partials.list-skeleton-cards', ['cards' => 4])
        </div>
    </div>
</div>
