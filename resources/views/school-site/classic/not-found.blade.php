@extends('school-site.classic.layout', ['title' => 'Page not found'])

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Page Not Found'])

    <section class="page">
        <div class="wrap">
            <div class="card" style="text-align:center;">
                <p style="font-size:42px;font-weight:800;color:var(--primary);line-height:1;">404</p>
                <p style="color:var(--muted);margin:10px 0 18px;">The page you are looking for isn’t available.</p>
                <a class="btn" href="{{ url('/') }}">Back to Home</a>
            </div>
        </div>
    </section>
@endsection
