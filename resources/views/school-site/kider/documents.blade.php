{{--
    Document table — Mandatory Disclosures, Fee Structure, Academic Calendar.

    CBSE requires every affiliated school to publish the disclosure set on its
    website, so when a school has uploaded nothing yet the page still lists the
    required rows and marks each one "Not uploaded" instead of showing a blank
    page. Receives $heading and $documents = [{title, file, date}].
--}}
@extends('school-site.kider.layout')

@php use App\Models\SchoolWebsite; @endphp

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => $heading,
        'tag'     => 'Public Information',
        'sub'     => $heading === 'Mandatory Disclosures'
            ? 'Documents published as required under the CBSE Affiliation Bye-Laws.'
            : null,
    ])

    <section class="section">
        <div class="section-inner">
            <div class="doc-card">
                <table class="doc-table">
                    <thead>
                        <tr>
                            <th style="width:56px;">S. No.</th>
                            <th>Document / Information</th>
                            <th style="width:140px;">Updated</th>
                            <th style="width:150px;">Download</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($documents as $i => $doc)
                            @php $url = SchoolWebsite::media($doc['file'] ?? null); @endphp
                            <tr>
                                <td class="doc-num">{{ $i + 1 }}</td>
                                <td class="doc-title">{{ $doc['title'] ?? '—' }}</td>
                                <td class="doc-date">{{ $doc['date'] ?? '—' }}</td>
                                <td>
                                    @if ($url)
                                        <a class="doc-btn" href="{{ $url }}" target="_blank" rel="noopener">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 10v6m0 0l-3-3m3 3l3-3M17 21H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Download
                                        </a>
                                    @else
                                        <span class="doc-pending">Not uploaded</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding:56px 20px;text-align:center;">
                                    <p style="font-weight:600;color:var(--text2);">Nothing published yet</p>
                                    <p style="font-size:13.5px;color:var(--text4);margin-top:6px;">Documents will appear here once the school uploads them.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (!empty($c['phone']) || !empty($c['email']))
                <p style="text-align:center;font-size:13.5px;color:var(--text4);margin-top:22px;">
                    For a certified copy of any document, contact the school office
                    @if(!empty($c['phone'])) on {{ $c['phone'] }}@endif@if(!empty($c['email'])) or write to {{ $c['email'] }}@endif.
                </p>
            @endif
        </div>
    </section>

    <style>
        .doc-card { background: #fff; border: 1px solid var(--border2); border-radius: var(--radius-lg); box-shadow: var(--shadow3); overflow: hidden; }
        .doc-table { width: 100%; border-collapse: collapse; }
        .doc-table thead th {
            text-align: left; padding: 15px 20px; font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px; color: #fff; background: var(--grad1);
        }
        .doc-table tbody tr { border-bottom: 1px solid var(--border2); }
        .doc-table tbody tr:last-child { border-bottom: none; }
        .doc-table tbody tr:hover { background: var(--faint); }
        .doc-table td { padding: 15px 20px; vertical-align: middle; }
        .doc-num { font-size: 13px; color: var(--text4); font-variant-numeric: tabular-nums; }
        .doc-title { font-size: 14.5px; color: var(--text2); font-weight: 500; line-height: 1.55; }
        .doc-date { font-size: 13px; color: var(--text3); white-space: nowrap; }
        .doc-btn {
            display: inline-flex; align-items: center; gap: 6px; padding: 8px 15px;
            border-radius: var(--radius-pill); background: var(--grad1); color: #fff;
            font-size: 12.5px; font-weight: 600; white-space: nowrap; transition: transform .25s;
        }
        .doc-btn:hover { transform: translateY(-2px); }
        .doc-pending { font-size: 12.5px; color: var(--text4); font-style: italic; white-space: nowrap; }
        @media (max-width: 760px) {
            .doc-table thead { display: none; }
            .doc-table tbody tr { display: block; padding: 14px 16px; }
            .doc-table td { display: block; padding: 3px 0; }
            .doc-num { display: none !important; }
        }
    </style>
@endsection
