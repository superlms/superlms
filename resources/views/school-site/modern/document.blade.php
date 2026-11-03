{{--
    One disclosure document on a page of its own (doc/N, from the Documents
    menu) for Vibrant and Breeze, as on Classic: a PDF shown in the page
    (a tile to open it on a phone), a photo as it is, a YouTube link played
    in the page, anything else as a download. Receives $document = {title, file, date}.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => $document['title']])

@php
    $docUrl  = \App\Models\SchoolWebsite::media($document['file']);
    $path    = strtolower((string) parse_url($docUrl, PHP_URL_PATH));
    $isPdf   = str_ends_with($path, '.pdf');
    $isImage = (bool) preg_match('/\.(jpe?g|png|gif|webp)$/', $path);
    $youtube = preg_match('#(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})#', $docUrl, $yt) ? $yt[1] : null;
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => $document['title']])

    <section class="pg">
        <div class="wrap">
            <div class="panel">
                @if ($youtube)
                    <div style="position:relative;aspect-ratio:16/9;background:#000;border-radius:var(--r-md);overflow:hidden;">
                        <iframe src="https://www.youtube-nocookie.com/embed/{{ $youtube }}" title="{{ $document['title'] }}" style="position:absolute;inset:0;width:100%;height:100%;border:0;"
                                allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                    </div>
                @elseif ($isPdf)
                    <object class="doc-embed" data="{{ $docUrl }}" type="application/pdf" style="width:100%;height:80vh;min-height:520px;border:1px solid var(--line);border-radius:var(--r-md);">
                        <p class="muted-note">This document cannot be shown here — use Click to Download below.</p>
                    </object>
                    <a class="doc-tap" href="{{ $docUrl }}" target="_blank" rel="noopener">
                        <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
                        <b>{{ $document['title'] }}</b>
                        <span>PDF — tap to open</span>
                    </a>
                @elseif ($isImage)
                    <a href="{{ $docUrl }}" target="_blank" rel="noopener"><img src="{{ $docUrl }}" alt="{{ $document['title'] }}" style="margin:0 auto;border-radius:var(--r-md);border:1px solid var(--line);"></a>
                @endif

                <p class="btns" style="justify-content:center;margin-top:22px;">
                    @unless ($youtube)
                        <a class="btn" href="{{ $docUrl }}" target="_blank" rel="noopener">@include('school-site.modern.partials.icon', ['n' => 'download']) Click to Download</a>
                    @endunless
                    <a class="btn btn-ghost" href="{{ url('disclosures') }}">All Mandatory Disclosures</a>
                </p>
                @if (!empty($document['date']))
                    <p style="text-align:center;color:var(--muted);font-size:13px;margin-top:10px;">Updated {{ $document['date'] }}</p>
                @endif
            </div>
        </div>
    </section>
@endsection
