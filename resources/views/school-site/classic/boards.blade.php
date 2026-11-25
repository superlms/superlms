{{--
    Boards (the menu's last item): the Class 9–12 students' login — name,
    father's first name and date of birth — and then their details, class,
    fee and the school's payment QR (shared.boards).
--}}
@extends('school-site.classic.layout', ['title' => 'Boards'])

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Boards'])

    <section class="page">
        <div class="wrap" style="max-width:1000px;">
            @include('school-site.shared.boards')
        </div>
    </section>
@endsection
