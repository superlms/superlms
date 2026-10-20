{{--
    The school's fee structure as set on Fee → Fee Structure
    (App\Support\SchoolSiteData::fees): a class (or one section of it) a row
    with its total, opening on its particulars. Param: $fees.
--}}
@php use App\Support\SchoolSiteData; @endphp
@include('school-site.shared.lms-style')
<div class="lms lms-fees">
    @foreach ($fees as $i => $g)
        <details class="lms-fee" @if ($i === 0) open @endif>
            <summary>
                <span>
                    <span class="lms-strong">{{ $g['class'] }}</span>
                    <span class="lms-muted">· {{ $g['section'] ?: 'All Sections' }}@if ($g['year'] !== '') · {{ $g['year'] }}@endif</span>
                </span>
                <span class="lms-amt">{{ SchoolSiteData::money($g['total']) }}</span>
            </summary>
            <table>
                @foreach ($g['rows'] as $r)
                    <tr>
                        <td>{{ $r['name'] }}</td>
                        <td class="lms-num">{{ SchoolSiteData::money($r['amount']) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total</td>
                    <td class="lms-num">{{ SchoolSiteData::money($g['total']) }}</td>
                </tr>
            </table>
        </details>
    @endforeach
</div>
