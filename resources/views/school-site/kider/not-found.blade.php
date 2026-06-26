@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'Page Not Found'])
    <div class="container-fluid py-5">
        <div class="container py-5 text-center">
            <h1 class="display-1 text-primary">404</h1>
            <h2 class="mb-4">Oops! This page isn’t available.</h2>
            <a class="btn btn-primary rounded-pill py-3 px-5" href="{{ url('/') }}">Back to Home</a>
        </div>
    </div>
@endsection
