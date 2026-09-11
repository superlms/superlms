{{--
    Board result page — the last few years of results, which CBSE asks
    schools to publish. Receives $results = [{year, appeared, passed}];
    the pass percentage is computed rather than stored, so it can never
    disagree with the two counts next to it.
--}}
@extends('school-site.kider.layout')

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
        ->sortByDesc('year')->values();

    $best = $rows->max('pct') ?: 0;
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Result',
        'tag'     => 'Academics',
        'sub'     => 'Board examination results at ' . $c['school_name'] . '.',
    ])

    <section class="section">
        <div class="section-inner">
            @if ($rows->isEmpty())
                <div style="max-width:560px;margin:0 auto;text-align:center;">
                    <p class="section-subtitle" style="margin:0 auto;">Results for the latest session will be published here shortly.</p>
                </div>
            @else
                <div class="res-card">
                    <table class="res-table">
                        <thead>
                            <tr>
                                <th>Year</th>
                                <th style="text-align:right;">Students Appeared</th>
                                <th style="text-align:right;">Students Passed</th>
                                <th style="width:210px;">Pass Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $r)
                                <tr>
                                    <td class="res-year">{{ $r['year'] }}</td>
                                    <td style="text-align:right;" class="res-num">{{ number_format($r['appeared']) }}</td>
                                    <td style="text-align:right;" class="res-num">{{ number_format($r['passed']) }}</td>
                                    <td>
                                        <div class="res-bar-wrap">
                                            <div class="res-bar"><span style="width: {{ $r['pct'] }}%"></span></div>
                                            <span class="res-pct">{{ $r['pct'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="res-stats">
                    <div><strong>{{ $rows->count() }}</strong><span>Sessions published</span></div>
                    <div><strong>{{ number_format($rows->sum('appeared')) }}</strong><span>Students appeared</span></div>
                    <div><strong>{{ number_format($rows->sum('passed')) }}</strong><span>Students passed</span></div>
                    <div><strong>{{ $best }}%</strong><span>Best pass rate</span></div>
                </div>
            @endif
        </div>
    </section>

    <style>
        .res-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-lg); box-shadow: var(--shadow3); overflow: hidden; }
        .res-table { width: 100%; border-collapse: collapse; }
        .res-table thead th {
            text-align: left; padding: 15px 20px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px; color: #fff; background: var(--grad1);
        }
        .res-table tbody tr { border-bottom: 1px solid var(--border2); }
        .res-table tbody tr:last-child { border-bottom: none; }
        .res-table tbody tr:hover { background: var(--faint); }
        .res-table td { padding: 15px 20px; vertical-align: middle; }
        .res-year { font-family: 'Baloo 2', cursive; font-size: 19px; font-weight: 700; color: var(--text); }
        .res-num { font-size: 14.5px; color: var(--text2); font-variant-numeric: tabular-nums; }
        .res-bar-wrap { display: flex; align-items: center; gap: 12px; }
        .res-bar { flex: 1; height: 7px; border-radius: 99px; background: var(--faint); overflow: hidden; }
        .res-bar span { display: block; height: 100%; border-radius: 99px; background: var(--grad1); }
        .res-pct { font-size: 13.5px; font-weight: 700; color: var(--primary); width: 52px; text-align: right; font-variant-numeric: tabular-nums; }

        .res-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 26px; }
        .res-stats > div { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius); padding: 20px; text-align: center; box-shadow: var(--shadow3); }
        .res-stats strong { display: block; font-family: 'Baloo 2', cursive; font-size: 26px; font-weight: 800; color: var(--primary); }
        .res-stats span { font-size: 12.5px; color: var(--text3); }
        @media (max-width: 760px) { .res-stats { grid-template-columns: repeat(2, 1fr); } }
    </style>
@endsection
