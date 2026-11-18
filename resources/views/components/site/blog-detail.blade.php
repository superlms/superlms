@php
    // Escape each paragraph (XSS-safe), then linkify. Two link styles:
    //   1. Markdown  [label](https://example.com)  → the label becomes the link
    //   2. Any bare  https://…  URL                → the URL itself becomes the link
    // Both are handled in a single pass so a URL inside a [ ]( ) is never double-linked.
    $linkify = function (string $text): string {
        $html = e($text);
        $html = preg_replace_callback(
            '~\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)|(https?:\/\/[^\s<]+)~i',
            function ($m) {
                $isMarkdown = $m[1] !== '';
                $url        = $isMarkdown ? $m[2] : $m[3];
                $label      = $isMarkdown ? $m[1] : $m[3];
                return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="blog-link">' . $label . '</a>';
            },
            $html
        );
        return nl2br($html);
    };

    $paragraphs = $blog->body_paragraphs;
@endphp
@extends('components.site.layout')

@section('title', $blog->title)

@section('content')

    <section class="section" style="padding-top:64px;">
        <div class="container">
            <article class="article">
                <a href="{{ route('website.blogs') }}" class="article-back rv"><x-site.icon name="arrow-left" /> Back to all articles</a>

                @if ($blog->category)<div class="article-cat rv">{{ $blog->category }}</div>@endif
                <h1 class="h1 rv">{{ $blog->title }}</h1>
                @if ($blog->heading)<p class="article-heading rv">{{ $blog->heading }}</p>@endif
                <div class="article-meta rv">Published on {{ $blog->created_at?->format('d M Y') }}</div>

                @if ($blog->cover_image)
                    <div class="article-cover rv"><img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}"></div>
                @endif

                <div class="article-body" style="margin-top:34px;">
                    @foreach ($paragraphs as $para)
                        <p>{!! $linkify($para) !!}</p>
                    @endforeach
                </div>
            </article>
        </div>
    </section>

    @if ($recent->isNotEmpty())
        <section class="section section-soft" style="padding-top:72px;">
            <div class="container">
                <h2 class="h3 rv" style="margin-bottom:28px;">More articles</h2>
                <div class="blog-grid">
                    @foreach ($recent as $i => $r)
                        <a href="{{ route('website.blog.detail', $r->slug) }}" class="blog-card rv" style="--rd:{{ $i * .07 }}s">
                            <div class="blog-thumb">
                                @if ($r->cover_image)<img src="{{ $r->cover_image }}" alt="{{ $r->title }}" loading="lazy">@else <x-site.icon name="pen" /> @endif
                            </div>
                            <div class="blog-body">
                                @if ($r->category)<div class="blog-cat">{{ $r->category }}</div>@endif
                                <h3 class="blog-h">{{ $r->title }}</h3>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

@endsection
