{{--
    Boards for Vibrant, Breeze and Mint (the menu's last item): the Class
    9–12 students' login — name, father's first name and date of birth — and
    then their details, class, fee and the school's payment QR, as on Classic
    (shared.boards).
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Boards'])

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Boards'])

    <section class="pg">
        <div class="wrap">
            @include('school-site.shared.boards')
        </div>
    </section>
@endsection
