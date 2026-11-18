@php
    $def      = config('website_pages.blogs', []);
    // Header copy is fixed content (not editable from super-admin).
    $tag      = $def['tag']      ?? 'The SUPERLMS Blog';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
    $blogs    = $blogs ?? collect();
@endphp
@extends('components.site.layout')

@section('title', 'Blogs')

@section('content')

    <section class="phero">
        <div class="container">
            <span class="eyebrow rv">{{ $tag }}</span>
            <h1 class="h1 rv" style="--rd:.05s">{{ $title }}</h1>
            <p class="lead rv" style="--rd:.1s">{{ $subtitle }}</p>
        </div>
    </section>

    <section class="section" style="padding-top:24px;">
        <div class="container">
            @if ($blogs->isEmpty())
                <p class="empty">No articles published yet. Check back soon — we're working on great content for you. ✍️</p>
            @else
                <div class="blog-grid">
                    @foreach ($blogs as $i => $blog)
                        <a href="{{ route('website.blog.detail', $blog->slug) }}" class="blog-card rv" style="--rd:{{ ($i % 3) * .07 }}s">
                            <div class="blog-thumb">
                                @if ($blog->cover_image)
                                    <img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}" loading="lazy">
                                @else
                                    <x-site.icon name="pen" />
                                @endif
                            </div>
                            <div class="blog-body">
                                @if ($blog->category)<div class="blog-cat">{{ $blog->category }}</div>@endif
                                <h3 class="blog-h">{{ $blog->title }}</h3>
                                @if ($blog->heading)<div class="blog-sub">{{ $blog->heading }}</div>@endif
                                <p class="blog-ex">{{ $blog->excerpt }}</p>
                                <div class="blog-foot">
                                    <span>{{ $blog->created_at?->format('d M Y') }}</span>
                                    <span class="rm">Read more →</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

@endsection
