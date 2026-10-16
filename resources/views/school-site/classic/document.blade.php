{{--
    One disclosure document on a page of its own (doc/N, from the Documents
    menu): a PDF shown in the page, a photo shown as it is, a YouTube link
    (an inspection video) played in the page, anything else as a download.
    Receives $document = {title, file, date}.
--}}
@extends('school-site.classic.layout', ['title' => $document['title']])

@php
    $docUrl  = \App\Models\SchoolWebsite::media($document['file']);
    $path    = strtolower((string) parse_url($docUrl, PHP_URL_PATH));
    $isPdf   = str_ends_with($path, '.pdf');
    $isImage = (bool) preg_match('/\.(jpe?g|png|gif|webp)$/', $path);
    $youtube = preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#', $docUrl, $yt) ? $yt[1] : null;
@endphp

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => $document['title']])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @if ($youtube)
                    <div style="position:relative;aspect-ratio:16/9;background:#000;">
                        <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtube }}" title="{{ $document['title'] }}" style="position:absolute;inset:0;width:100%;height:100%;border:0;"
                                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                    </div>
                @elseif ($isPdf)
                    <object data="{{ $docUrl }}" type="application/pdf" style="width:100%;height:80vh;min-height:520px;border:1px solid var(--line);">
                        <p class="muted-note">This document cannot be shown here — use Click to Download below.</p>
                    </object>
                @elseif ($isImage)
                    <a href="{{ $docUrl }}" target="_blank" rel="noopener"><img src="{{ $docUrl }}" alt="{{ $document['title'] }}" style="margin:0 auto;border:1px solid var(--line);"></a>
                @endif

                <p style="display:flex;justify-content:center;gap:10px;flex-wrap:wrap;margin-top:18px;">
                    @unless ($youtube)
                        <a class="btn" href="{{ $docUrl }}" target="_blank" rel="noopener">Click to Download</a>
                    @endunless
                    <a class="btn btn-ghost" href="{{ url('disclosures') }}">All Mandatory Disclosures</a>
                </p>
                @if (!empty($document['date']))
                    <p style="text-align:center;color:var(--muted);font-size:12.5px;margin-top:8px;">Updated {{ $document['date'] }}</p>
                @endif
            </div>
        </div>
    </section>
@endsection
