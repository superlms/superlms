{{--
    Document table for Vibrant and Breeze — Mandatory Disclosures and any
    other document page, as on Classic: a disclosure with a file opens on a
    page of its own (doc/N, the Documents menu's pages); rows not uploaded
    yet say so. Receives $heading and $documents = [{title, file, date}].
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => $heading])

@php use App\Models\SchoolWebsite; @endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => $heading])

    <section class="pg">
        <div class="wrap">
            <div class="panel">
                @if ($current === 'disclosures')
                    <div class="panel-head">
                        <div>
                            <span class="eyebrow">{{ $c['board'] ?: 'CBSE' }} Bye-Laws</span>
                            <p class="lead-text" style="margin-top:10px;">Information and documents published as required under the {{ $c['board'] ?: 'CBSE' }} Affiliation Bye-Laws.</p>
                        </div>
                    </div>
                @endif
                <div class="tbl-wrap">
                    <table class="tbl">
                        <thead>
                            <tr>
                                <th style="width:64px;">S. No.</th>
                                <th>Document / Information</th>
                                <th style="width:140px;">Updated</th>
                                <th style="width:170px;">Click to Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($documents as $i => $doc)
                                @php $url = SchoolWebsite::media($doc['file'] ?? null); @endphp
                                <tr>
                                    <td class="sno">{{ $i + 1 }}</td>
                                    <td class="strong">
                                        @if ($url && $current === 'disclosures')
                                            <a href="{{ url('doc/' . ($i + 1)) }}" style="color:var(--primary);">{{ $doc['title'] ?? '—' }}</a>
                                        @else
                                            {{ $doc['title'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $doc['date'] ?? '' ?: '—' }}</td>
                                    <td>
                                        @if ($url)
                                            <a class="btn btn-sm" href="{{ $url }}" target="_blank" rel="noopener">@include('school-site.modern.partials.icon', ['n' => 'download']) Download</a>
                                        @else
                                            <span style="color:var(--muted);font-style:italic;font-size:13px;">Not uploaded</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="muted-note">Documents will appear here once the school uploads them.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if (!empty($c['phone']) || !empty($c['email']))
                    <p style="text-align:center;color:var(--muted);font-size:13.5px;margin-top:20px;">
                        For a certified copy of any document, contact the school office
                        @if (!empty($c['phone'])) on {{ $c['phone'] }}@endif @if (!empty($c['email'])) or write to {{ $c['email'] }}@endif.
                    </p>
                @endif
            </div>
        </div>
    </section>
@endsection
