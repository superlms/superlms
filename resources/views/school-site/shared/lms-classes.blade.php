{{--
    The school's classes, each with its sections and subjects as plain text,
    as set up on the Standard page (App\Support\SchoolSiteData::classes) —
    one table in the Mark Attendance list's look. A column is only as wide as
    what is in it; subjects take the rest. Param: $classes.
--}}
@php
    $sections = array_map(fn ($k) => implode(', ', $k['sections']), $classes);
    $fit = fn (array $texts, int $max) => max(array_map('mb_strlen', $texts) ?: [0]) <= $max ? 'fit' : '';
    $sectionCount = array_sum(array_map(fn ($k) => count($k['sections']), $classes));
@endphp
@include('school-site.shared.lms-style')
<div class="lms-card">
    <div class="lms-head">
        <h2>Classes, Sections &amp; Subjects</h2>
        <span class="meta">{{ count($classes) }} {{ \Illuminate\Support\Str::plural('class', count($classes)) }}@if ($sectionCount) · {{ $sectionCount }} {{ \Illuminate\Support\Str::plural('section', $sectionCount) }}@endif</span>
    </div>
    <div class="lms-scroll">
        <table class="lms-tbl">
            <thead>
                <tr>
                    <th class="sno">S.No</th>
                    <th class="{{ $fit(array_column($classes, 'name'), 22) }}">Class</th>
                    <th class="{{ $fit($sections, 26) }}">Sections</th>
                    <th>Subjects</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classes as $i => $k)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="strong {{ $fit(array_column($classes, 'name'), 22) }}">{{ $k['name'] }}</td>
                        <td class="{{ $fit($sections, 26) }}">{!! $sections[$i] !== '' ? e($sections[$i]) : '<span class="muted">—</span>' !!}</td>
                        <td class="desc">{!! $k['subjects'] ? e(implode(', ', $k['subjects'])) : '<span class="muted">—</span>' !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
