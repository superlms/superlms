{{-- Inner-page hero header. Params: $heading, optional $tag, $sub --}}
<section class="page-header">
    <div class="grid-bg"></div>
    <div class="page-header-content">
        <span class="section-tag">{{ $tag ?? $c['school_name'] }}</span>
        <h1 class="section-title">{{ $heading }}</h1>
        @if (!empty($sub))
            <p class="section-subtitle" style="margin:0 auto;">{{ $sub }}</p>
        @endif
        <nav class="breadcrumb-nav"><a href="{{ url('/') }}">Home</a> / {{ $heading }}</nav>
    </div>
</section>
