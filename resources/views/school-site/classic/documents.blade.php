{{--
    Document table — Mandatory Disclosures, Fee Structure, Academic Calendar.
    A disclosure with a file opens on a page of its own (doc/N, the same pages
    the Documents menu lists); rows not uploaded yet say so, so the CBSE list
    is never blank. Receives $heading and $documents = [{title, file, date}].
--}}
@extends('school-site.classic.layout', ['title' => $heading])

@php use App\Models\SchoolWebsite; @endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => $heading])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @if ($current === 'disclosures')
                    <p style="color:var(--muted);margin-bottom:16px;">Information and documents published as required under the {{ $c['board'] ?: 'CBSE' }} Affiliation Bye-Laws.</p>
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
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        @if ($url && $current === 'disclosures')
                                            <a href="{{ url('doc/' . ($i + 1)) }}" style="color:var(--primary);font-weight:500;">{{ $doc['title'] ?? '—' }}</a>
                                        @else
                                            {{ $doc['title'] ?? '—' }}
                                        @endif
                                    </td>
                                    <td>{{ $doc['date'] ?? '' ?: '—' }}</td>
                                    <td>
                                        @if ($url)
                                            <a class="btn" style="padding:6px 14px;font-size:12px;" href="{{ $url }}" target="_blank" rel="noopener">Download</a>
                                        @else
                                            <span style="color:#999;font-style:italic;font-size:12.5px;">Not uploaded</span>
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
                    <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:18px;">
                        For a certified copy of any document, contact the school office
                        @if (!empty($c['phone'])) on {{ $c['phone'] }}@endif @if (!empty($c['email'])) or write to {{ $c['email'] }}@endif.
                    </p>
                @endif
            </div>
        </div>
    </section>
@endsection
