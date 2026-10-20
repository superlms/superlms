{{--
    Every event on the school's calendar this session
    (App\Support\SchoolSiteData::events) — one table in the Mark Attendance
    list's look, by date under a line for each month: the date with its day
    under it, the event with its description, its type and its time. Short columns are only
    as wide as what is in them; the event takes the rest. Param: $events.
--}}
@php
    use App\Support\SchoolSiteData;
    use Carbon\Carbon;

    [, , $session] = SchoolSiteData::session();
    $month = null;
@endphp
@include('school-site.shared.lms-style')
@once
<style>
    .lms-tbl tr.grp td { padding: 8px 16px; background: #FAFAFB; border-top: 1px solid #EEF0F3; font-size: 11.5px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase; color: #6B7280; }
    .lms-tbl tbody tr.grp:hover td { background: #FAFAFB; }
    .lms-tbl .sub { display: block; margin-top: 2px; font-size: 12.5px; color: #6B7280; font-weight: 400; }
    .lms-tbl .ev { min-width: 180px; }
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
                    <th class="fit">Date</th>
                    <th>Event</th>
                    <th class="fit">Type</th>
                    <th class="fit">Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($events as $i => $e)
                    @php $d = Carbon::parse($e['date']); @endphp
                    @if ($d->format('Y-m') !== $month)
                        @php $month = $d->format('Y-m'); @endphp
                        <tr class="grp"><td colspan="5">{{ $d->format('F Y') }}</td></tr>
                    @endif
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="fit">{{ $d->format('d M Y') }} <span class="sub">{{ $d->format('D') }}</span></td>
                        <td class="strong ev">
                            {{ $e['title'] }}
                            @if ($e['description'] !== '')<span class="sub">{{ \Illuminate\Support\Str::limit($e['description'], 160) }}</span>@endif
                        </td>
                        <td class="fit">{{ $e['type'] }}</td>
                        <td class="fit">{{ $e['time'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
