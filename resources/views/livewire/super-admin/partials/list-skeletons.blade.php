{{-- The Super Admin lists that show a skeleton the moment their link is
     clicked (partials.panel-ui, part 4): one template per page, by its path.
     Each list's columns are given once here and used both for the page
     skeleton and for the skeleton rows the page shows while a filter, a
     search, a sort or another page is loading (super-admin.student / .teacher). --}}
@php
    $th = 'px-3 py-2.5 text-left text-[11px] font-medium text-gray-500 truncate';
    $sno = 'pl-4 pr-2 py-2.5 w-14 text-left text-[11px] font-medium text-gray-500';
    $act = 'px-2 py-2.5 w-[60px]';
    $pages = [
        'super-admin.students' => [
            'title'   => 'Students',
            'filters' => 5,
            'headers' => [['S.No', $sno], ['Student', "$th w-[22%]"], ['Admission No', "$th w-[118px]"], ['Father\'s Name', $th],
                          ['Mobile', "$th w-[104px]"], ['Email', $th], ['Gender', "$th w-[78px]"], ['School', $th], ['', $act]],
            'cols'    => \App\Support\ListSkeleton::STUDENTS,
        ],
        'super-admin.teachers' => [
            'title'   => 'Teachers',
            'filters' => 3,
            'headers' => [['S.No', $sno], ['Teacher', "$th w-[20%]"], ['Username', "$th w-[120px]"], ['Mobile', "$th w-[104px]"],
                          ['Email', $th], ['Gender', "$th w-[72px]"], ['Class Teacher', "$th w-[120px]"], ['School', $th], ['', $act]],
            'cols'    => \App\Support\ListSkeleton::TEACHERS,
        ],
    ];
@endphp
@foreach ($pages as $route => $page)
    @if (\Illuminate\Support\Facades\Route::has($route))
        <template data-skeleton-for="{{ parse_url(route($route), PHP_URL_PATH) }}">
            @include('livewire.super-admin.partials.list-page-skeleton', $page)
        </template>
    @endif
@endforeach
