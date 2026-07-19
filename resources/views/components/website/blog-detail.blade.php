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
@include('components.website.partials.head', ['title' => $blog->title])

<style>
  .article-wrap { max-width: 820px; margin: 0 auto; }
  .article-back { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600;
    color:var(--violet); text-decoration:none; margin-bottom:22px; }
  .article-back:hover { text-decoration:underline; }
  .article-cat { font-size:12px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:var(--violet); margin-bottom:14px; }
  .article-title { font-family:'Cormorant Garamond',serif; font-size:clamp(30px,4.5vw,46px); font-weight:700;
    color:var(--text); line-height:1.15; margin-bottom:14px; }
  .article-heading { font-size:18px; font-weight:600; color:var(--text2); line-height:1.5; margin-bottom:18px; }
  .article-meta { font-size:13px; color:var(--text4); margin-bottom:28px; }
  .article-cover { width:100%; border-radius:20px; overflow:hidden; margin-bottom:32px; box-shadow:var(--shadow2);
    max-height:440px; }
  .article-cover img { width:100%; height:100%; object-fit:cover; display:block; }
  .article-body { font-size:16px; color:var(--text2); line-height:1.95; }
  .article-body p { margin:0 0 20px; }
  .article-body p:last-child { margin-bottom:0; }
  .article-body .blog-link { color:#2563EB; text-decoration:underline; word-break:break-word; }
  .article-body .blog-link:hover { color:#1D4ED8; }

  /* Recent posts */
  .recent-wrap { max-width:1180px; margin:0 auto; }
  .recent-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:24px; margin-top:32px; }
  .recent-card { background:#fff; border:1px solid var(--border2); border-radius:var(--radius); overflow:hidden;
    text-decoration:none; transition:all .25s; box-shadow:var(--shadow3); display:flex; flex-direction:column; }
  .recent-card:hover { transform:translateY(-3px); box-shadow:var(--shadow2); border-color:var(--border); }
  .recent-thumb { height:140px; background:var(--grad2); display:flex; align-items:center; justify-content:center; font-size:34px; overflow:hidden; }
  .recent-thumb img { width:100%; height:100%; object-fit:cover; }
  .recent-body { padding:16px 18px 18px; }
  .recent-cat { font-size:10px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:var(--violet); margin-bottom:6px; }
  .recent-title { font-size:15px; font-weight:600; color:var(--text); line-height:1.4; }
  @media (max-width:900px) { .recent-grid { grid-template-columns:1fr 1fr; } }
  @media (max-width:560px) { .recent-grid { grid-template-columns:1fr; } }
</style>

  @include('components.website.header')

  {{-- ══════════════════ ARTICLE ══════════════════ --}}
  <section class="section" style="padding-top:120px;">
    <div class="article-wrap">
      <a href="{{ route('website.blogs') }}" class="article-back">← Back to all articles</a>

      @if ($blog->category)<div class="article-cat">{{ $blog->category }}</div>@endif
      <h1 class="article-title">{{ $blog->title }}</h1>
      @if ($blog->heading)<p class="article-heading">{{ $blog->heading }}</p>@endif
      <div class="article-meta">Published on {{ $blog->created_at?->format('d M Y') }}</div>

      @if ($blog->cover_image)
        <div class="article-cover"><img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}"></div>
      @endif

      <div class="article-body">
        @foreach ($paragraphs as $para)
          <p>{!! $linkify($para) !!}</p>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ══════════════════ RECENT POSTS ══════════════════ --}}
  @if ($recent->isNotEmpty())
  <section class="section" style="background:var(--bg3);padding-top:60px;padding-bottom:80px;">
    <div class="recent-wrap">
      <h2 class="section-title" style="font-size:clamp(1.6rem,3vw,2.2rem);">More articles</h2>
      <div class="recent-grid">
        @foreach ($recent as $r)
        <a href="{{ route('website.blog.detail', $r->slug) }}" class="recent-card">
          <div class="recent-thumb">
            @if ($r->cover_image)<img src="{{ $r->cover_image }}" alt="{{ $r->title }}" loading="lazy">@else 📝 @endif
          </div>
          <div class="recent-body">
            @if ($r->category)<div class="recent-cat">{{ $r->category }}</div>@endif
            <div class="recent-title">{{ $r->title }}</div>
          </div>
        </a>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  @include('components.website.app-section')
  @include('components.website.footer')
</body>
</html>
