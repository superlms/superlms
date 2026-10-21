{{--
    Board result page. Receives $results = [{year, appeared, passed}]; the pass
    percentage is worked out here, so it never disagrees with the two counts.
--}}
@extends('school-site.classic.layout', ['title' => 'Result'])

@php
    $rows = collect($results)
        ->map(function ($r) {
            $appeared = (int) ($r['appeared'] ?? 0);
            $passed   = (int) ($r['passed'] ?? 0);
            return [
                'year'     => $r['year'] ?? '—',
                'appeared' => $appeared,
                'passed'   => $passed,
                'pct'      => $appeared > 0 ? round($passed / $appeared * 100, 1) : 0,
            ];
        })
        ->sortBy('year')->values();
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Result'])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @if ($rows->isEmpty())
                    <p class="muted-note">Results for the latest session will be published here shortly.</p>
                @else
                    <div class="tbl-wrap">
                        <table class="tbl">
                            <thead>
                                <tr>
                                    <th>Year</th>
                                    <th style="text-align:right;">Appeared Students</th>
                                    <th style="text-align:right;">Passed Students</th>
                                    <th style="text-align:right;">Pass %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $r)
                                    <tr>
                                        <td class="strong" style="font-weight:600;">{{ $r['year'] }}</td>
                                        <td style="text-align:right;">{{ number_format($r['appeared']) }}</td>
                                        <td style="text-align:right;">{{ number_format($r['passed']) }}</td>
                                        <td style="text-align:right;font-weight:600;color:var(--primary);">{{ number_format($r['pct'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
