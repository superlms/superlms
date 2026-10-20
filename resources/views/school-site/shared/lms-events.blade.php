{{--
    Every event on the school's calendar this session
    (App\Support\SchoolSiteData::events) — one plain table as Transport's, a
    value a cell: S.No, Date, Day, Event, Details (only when some event has
    a description), Type and Time. The columns share the width by what is in
    them. Param: $events.
--}}
@php
    use App\Support\SchoolSiteData;
    use Carbon\Carbon;

    [, , $session] = SchoolSiteData::session();
    $details = (bool) array_filter(array_column($events, 'description'));
@endphp
@include('school-site.shared.lms-style')
@once
<style>
    .lms-tbl .ev { min-width: 150px; }
    .lms-tbl .dt { min-width: 180px; }
</style>
@endonce
<div class="lms-card">
    <div class="lms-head">
        <h2>Academic Calendar</h2>
        <span class="meta">Session {{ $session }} · {{ count($events) }} {{ \Illuminate\Support\Str::plural('event', count($events)) }}</span>
    </div>
    <div class="lms-scroll">
        <table class="lms-tbl">
            <thead>
                <tr>
                    <th class="sno">S.No</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Event</th>
                    @if ($details)<th>Details</th>@endif
                    <th>Type</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $i => $e)
                    @php $d = Carbon::parse($e['date']); @endphp
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="nw">{{ $d->format('d M Y') }}</td>
                        <td class="nw">{{ $d->format('l') }}</td>
                        <td class="strong ev">{{ $e['title'] }}</td>
                        @if ($details)
                            <td class="desc dt">{!! $e['description'] !== '' ? e(\Illuminate\Support\Str::limit($e['description'], 160)) : '<span class="muted">—</span>' !!}</td>
                        @endif
                        <td class="nw">{{ $e['type'] }}</td>
                        <td class="nw">{{ $e['time'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
