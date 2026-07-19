@extends('school-site.kider.layout')

@section('content')
    <section class="page-header" style="padding-bottom:120px;">
        <div class="grid-bg"></div>
        <div class="page-header-content">
            <span class="section-tag">Error 404</span>
            <h1 class="section-title" style="font-size:clamp(3rem,9vw,6rem);"><span class="gradient-text">404</span></h1>
            <p class="section-subtitle" style="margin:0 auto 28px;">Oops! The page you are looking for isn’t available.</p>
            <a class="btn btn-primary btn-lg" href="{{ url('/') }}">← Back to Home</a>
        </div>
    </section>
@endsection
