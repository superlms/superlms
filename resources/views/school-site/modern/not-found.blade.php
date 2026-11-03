@extends('school-site.' . $tpl . '.layout', ['title' => 'Page not found'])

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Page Not Found'])

    <section class="pg">
        <div class="wrap">
            <div class="panel nf">
                <b>404</b>
                <p class="lead-text" style="margin:12px auto 24px;">The page you are looking for isn’t available.</p>
                <a class="btn" href="{{ url('/') }}">Back to Home @include('school-site.modern.partials.icon', ['n' => 'arrow'])</a>
            </div>
        </div>
    </section>
@endsection
