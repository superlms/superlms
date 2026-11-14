{{-- A school's page (Super Admin → Schools → View) as a skeleton, shown in
     place of the list once View has taken longer than ListSkeleton::DELAY_MS:
     the header (Back, the logo, the name with the email under it, the status
     and the buttons), the tab bar with its real tabs (the first one open) and
     the first tab's fields.

     $tabs = the tabs' names; $actions = how many buttons the header has. --}}
<div aria-busy="true" data-screen-skeleton>
    <div class="bg-white border-b border-gray-200 px-4 sm:px-6 py-3">
        <div class="flex items-center justify-between gap-4 sm:min-h-9">
            <div class="flex items-center gap-3 min-w-0 flex-1">
                <span class="sa-skel h-8 w-9 sm:w-[72px] rounded-lg flex-shrink-0"></span>
                <span class="sa-skel w-9 h-9 rounded-xl flex-shrink-0"></span>
                <div class="min-w-0 flex-1 space-y-1.5 sm:space-y-0 sm:flex sm:items-center sm:gap-3">
                    <span class="sa-skel block h-4 w-40 sm:w-56"></span>
                    <span class="sa-skel block h-2.5 w-28 sm:w-36"></span>
                </div>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <span class="sa-skel hidden sm:inline-block h-6 w-16 rounded-full"></span>
                @for ($i = 0; $i < ($actions ?? 2); $i++)
                    <span class="sa-skel h-8 w-16 rounded-lg"></span>
                @endfor
            </div>
        </div>
    </div>

    <div class="p-4 sm:p-6 space-y-5">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            @if (!empty($tabs))
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    @foreach ($tabs as $i => $label)
                        <span class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 {{ $i === 0 ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400' }}">{{ $label }}</span>
                    @endforeach
                </div>
            @endif
            @include('livewire.super-admin.partials.screen-skeleton-tab', ['kind' => 'fields'])
        </div>
    </div>
</div>
