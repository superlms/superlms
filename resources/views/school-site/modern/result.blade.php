{{--
    Board result page for Vibrant and Breeze: the pass percentage of each
    year as a bar, then the table (Classic's data: [{year, appeared, passed}];
    the percentage is worked out, so it never disagrees with the counts).
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Result'])

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
    $bars = $rows->slice(-8)->values();
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Result'])

    <section class="pg">
        <div class="wrap">
            <div class="panel">
                @if ($rows->isEmpty())
                    <p class="muted-note">Results for the latest session will be published here shortly.</p>
                @else
                    <div class="panel-head">
                        <div>
                            <span class="eyebrow">Board Results</span>
                            <h2 class="h2">Pass percentage by <span class="hl">year</span></h2>
                        </div>
                    </div>
                    <div class="bars" role="img" aria-label="Pass percentage by year">
                        @foreach ($bars as $r)
                            <div class="bar"><b>{{ number_format($r['pct'], 1) }}%</b><i style="height: {{ max(3, round($r['pct'] * 0.72, 1)) }}%"></i><span>{{ $r['year'] }}</span></div>
                        @endforeach
                    </div>
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
                                        <td class="strong">{{ $r['year'] }}</td>
                                        <td style="text-align:right;">{{ number_format($r['appeared']) }}</td>
                                        <td style="text-align:right;">{{ number_format($r['passed']) }}</td>
                                        <td style="text-align:right;font-weight:800;color:var(--primary);">{{ number_format($r['pct'], 1) }}</td>
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
