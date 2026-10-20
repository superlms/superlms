{{--
    The school's classes, each with its sections and subjects, as set up on
    the Standard page (App\Support\SchoolSiteData::classes). Param: $classes.
--}}
@include('school-site.shared.lms-style')
<div class="lms lms-scroll">
    <table class="lms-tbl lms-stack">
        <thead>
            <tr>
                <th style="width:22%;">Class</th>
                <th style="width:30%;">Sections</th>
                <th>Subjects</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classes as $k)
                <tr>
                    <td class="lms-strong">{{ $k['name'] }}</td>
                    <td data-label="Sections">
                        @forelse ($k['sections'] as $s)<span class="lms-chip">{{ $s }}</span> @empty<span class="lms-muted">—</span>@endforelse
                    </td>
                    <td data-label="Subjects">
                        @forelse ($k['subjects'] as $s)<span class="lms-chip soft">{{ $s }}</span> @empty<span class="lms-muted">—</span>@endforelse
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
