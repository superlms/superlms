{{--
    The school's transport routes as set up on Transport
    (App\Support\SchoolSiteData::transport) — one table in the Mark
    Attendance list's look: a row a route with its pickup and drop times, the
    monthly fare and the annual fare (eleven months, June off). The columns
    share the width by what is in them. Param: $routes.
--}}
@php use App\Support\SchoolSiteData; @endphp
@include('school-site.shared.lms-style')
<div class="lms-card">
    <div class="lms-head">
        <h2>Transport Routes</h2>
        <span class="meta">{{ count($routes) }} {{ \Illuminate\Support\Str::plural('route', count($routes)) }}</span>
        <p class="note">Annual fare is the monthly fare for 11 months (no fare for June).</p>
    </div>
    <div class="lms-scroll">
        <table class="lms-tbl">
            <thead>
                <tr>
                    <th class="sno">S.No</th>
                    <th>Route</th>
                    <th>Pickup Time</th>
                    <th>Drop Time</th>
                    <th class="num">Monthly Fare</th>
                    <th class="num">Annual Fare</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($routes as $i => $r)
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="strong">{{ $r['name'] }}</td>
                        <td class="nw">{!! $r['pickup'] ? e($r['pickup']) : '<span class="muted">—</span>' !!}</td>
                        <td class="nw">{!! $r['drop'] ? e($r['drop']) : '<span class="muted">—</span>' !!}</td>
                        <td class="num">{!! $r['monthly'] > 0 ? e(SchoolSiteData::money($r['monthly'])) : '<span class="muted">—</span>' !!}</td>
                        <td class="num total">{!! $r['annual'] > 0 ? e(SchoolSiteData::money($r['annual'])) : '<span class="muted">—</span>' !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
