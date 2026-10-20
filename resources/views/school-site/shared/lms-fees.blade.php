{{--
    The school's fee structure as set on Fee → Fee Structure
    (App\Support\SchoolSiteData::fees) — one table in the Mark Attendance
    list's look: a row a class (or one section of it), a column a fee while
    the classes share a handful of them (else the particulars as a line), and
    the total. Section and session columns only when the fees differ by them.
    With a column a fee, the columns share the width by what is in them (no
    one wide Class column); with the line, the line takes the rest. Params: $fees, optional $note (Website Data's fee note).
--}}
@php
    use App\Support\SchoolSiteData;

    $names = [];
    foreach ($fees as $g) {
        foreach ($g['rows'] as $r) {
            $names[$r['name']] = true;
        }
    }
    $names   = array_keys($names);
    $matrix  = count($names) <= 5;
    $years   = array_values(array_unique(array_filter(array_column($fees, 'year'))));
    $yearCol = count($years) > 1;
    $secCol  = (bool) array_filter(array_column($fees, 'section'));
    $sumOf   = function (array $g) {
        $out = [];
        foreach ($g['rows'] as $r) {
            $out[$r['name']] = ($out[$r['name']] ?? 0) + $r['amount'];
        }
        return $out;
    };
@endphp
@include('school-site.shared.lms-style')
@once
<style>
    .lms-tbl th.num { text-align: right; }
    .lms-tbl th.wrap { white-space: normal; min-width: 84px; }
</style>
@endonce
<div class="lms-card">
    <div class="lms-head">
        <h2>Fee Structure</h2>
        @if (count($years) === 1)<span class="meta">Session {{ $years[0] }}</span>@endif
        @if (!empty($note))<p class="note">{{ $note }}</p>@endif
    </div>
    <div class="lms-scroll">
        <table class="lms-tbl">
            <thead>
                <tr>
                    <th class="sno">S.No</th>
                    <th class="{{ $matrix ? '' : 'fit' }}">Class</th>
                    @if ($secCol)<th class="fit">Section</th>@endif
                    @if ($yearCol)<th class="fit">Session</th>@endif
                    @if ($matrix)
                        @foreach ($names as $n)
                            <th class="num {{ mb_strlen($n) > 18 ? 'wrap' : '' }}">{{ $n }}</th>
                        @endforeach
                    @else
                        <th>Particulars</th>
                    @endif
                    <th class="num {{ $matrix ? '' : 'fit' }}">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($fees as $i => $g)
                    @php $amounts = $sumOf($g); @endphp
                    <tr>
                        <td class="sno">{{ $i + 1 }}</td>
                        <td class="strong nw {{ $matrix ? '' : 'fit' }}">{{ $g['class'] }}</td>
                        @if ($secCol)<td class="fit">{{ $g['section'] ?: 'All' }}</td>@endif
                        @if ($yearCol)<td class="fit">{{ $g['year'] ?: '—' }}</td>@endif
                        @if ($matrix)
                            @foreach ($names as $n)
                                <td class="num">{!! isset($amounts[$n]) ? e(SchoolSiteData::money($amounts[$n])) : '<span class="muted">—</span>' !!}</td>
                            @endforeach
                        @else
                            <td class="desc">{{ implode(' · ', array_map(fn ($n, $a) => $n . ' ' . SchoolSiteData::money($a), array_keys($amounts), $amounts)) }}</td>
                        @endif
                        <td class="num total {{ $matrix ? '' : 'fit' }}">{{ SchoolSiteData::money($g['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
