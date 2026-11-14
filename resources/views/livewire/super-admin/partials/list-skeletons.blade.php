{{-- The Super Admin lists that show a skeleton when their link is slow to
     open (partials.panel-ui, part 4): one template per page, by its path —
     Students, Teachers, Schools and Fees. Each page's header, filters and
     columns are given once in App\Support\ListSkeleton::pages() and used for
     the page skeleton, for Back from a school (Schools, Fees) and, through
     its columns, for the skeleton rows a list shows while a filter, a search,
     a sort or another page is loading. --}}
@foreach (\App\Support\ListSkeleton::pages() as $route => $page)
    @if (\Illuminate\Support\Facades\Route::has($route))
        <template data-skeleton-for="{{ parse_url(route($route), PHP_URL_PATH) }}">
            @include('livewire.super-admin.partials.list-page-skeleton', $page)
        </template>
    @endif
@endforeach
