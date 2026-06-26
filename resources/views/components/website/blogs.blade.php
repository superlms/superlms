@php
    $def      = config('website_pages.blogs', []);
    // Header copy is fixed content (not editable from super-admin).
    $tag      = $def['tag']      ?? 'The EDYONE Blog';
    $title    = $def['title']    ?? '';
    $subtitle = $def['subtitle'] ?? '';
    $blogs    = $blogs ?? collect();
@endphp
@include('components.website.partials.head', ['title' => 'Blogs'])

<style>
  /* ── Blogs-specific ── */
  .blog-grid { max-width: 1180px; margin: 0 auto; display: grid; grid-template-columns: repeat(3, 1fr); gap: 28px; }
  .blog-card {
    background: #fff; border: 1px solid var(--border2); border-radius: var(--radius);
    overflow: hidden; transition: all .28s; box-shadow: var(--shadow3); display: flex; flex-direction: column;
    text-decoration: none;
  }
  .blog-card:hover { transform: translateY(-4px); box-shadow: var(--shadow2); border-color: var(--border); }
  .blog-thumb { height: 180px; background: var(--grad2); display: flex; align-items: center; justify-content: center; font-size: 42px; overflow: hidden; }
  .blog-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .blog-body { padding: 22px 22px 24px; display: flex; flex-direction: column; flex: 1; }
  .blog-cat { font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: var(--violet); margin-bottom: 10px; }
  .blog-heading { font-size: 17px; font-weight: 600; color: var(--text); line-height: 1.4; margin-bottom: 8px; }
  .blog-sub { font-size: 13px; font-weight: 600; color: var(--text2); margin-bottom: 8px; }
  .blog-excerpt { font-size: 13px; color: var(--text3); line-height: 1.7; margin-bottom: 16px; flex: 1; }
  .blog-foot { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--text4); }
  .blog-foot .read-more { color: var(--violet); text-decoration: none; font-weight: 600; }
  .blog-empty { text-align:center; color:var(--text3); font-size:14px; padding:40px 0; }
  @media (max-width: 1024px) { .blog-grid { grid-template-columns: 1fr 1fr; } }
  @media (max-width: 640px) { .blog-grid { grid-template-columns: 1fr; } }
</style>

  @include('components.website.header')

  {{-- ══════════════════ PAGE HEADER ══════════════════ --}}
  <section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
      <span class="section-tag tag-dual">✍ {{ $tag }}</span>
      <h1 class="section-title">{{ $title }}</h1>
      <p class="section-subtitle">{{ $subtitle }}</p>
    </div>
  </section>

  {{-- ══════════════════ BLOG GRID ══════════════════ --}}
  <section class="section">
    @if ($blogs->isEmpty())
      <p class="blog-empty">No articles published yet. Check back soon — we're working on great content for you. ✍️</p>
    @else
      <div class="blog-grid">
        @foreach ($blogs as $blog)
        <a href="{{ route('website.blog.detail', $blog->slug) }}" class="blog-card">
          <div class="blog-thumb">
            @if ($blog->cover_image)
              <img src="{{ $blog->cover_image }}" alt="{{ $blog->title }}" loading="lazy">
            @else
              📝
            @endif
          </div>
          <div class="blog-body">
            @if ($blog->category)<div class="blog-cat">{{ $blog->category }}</div>@endif
            <h3 class="blog-heading">{{ $blog->title }}</h3>
            @if ($blog->heading)<div class="blog-sub">{{ $blog->heading }}</div>@endif
            <p class="blog-excerpt">{{ $blog->excerpt }}</p>
            <div class="blog-foot">
              <span>{{ $blog->created_at?->format('d M Y') }}</span>
              <span class="read-more">Read more →</span>
            </div>
          </div>
        </a>
        @endforeach
      </div>
    @endif
  </section>

  @include('components.website.app-section')
  @include('components.website.footer')
</body>
</html>
