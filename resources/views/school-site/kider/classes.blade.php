@extends('school-site.kider.layout')
@php
    use App\Models\SchoolWebsite;
    $classes = !empty($c['classes']) ? $c['classes'] : [
        ['title' => 'Art & Drawing', 'age' => '3-5 Years', 'time' => '9-10 AM',  'capacity' => '30 Kids'],
        ['title' => 'Music & Dance', 'age' => '4-6 Years', 'time' => '10-11 AM', 'capacity' => '30 Kids'],
        ['title' => 'Sport & Games', 'age' => '5-7 Years', 'time' => '11-12 AM', 'capacity' => '30 Kids'],
        ['title' => 'Story Telling', 'age' => '3-5 Years', 'time' => '12-1 PM',  'capacity' => '30 Kids'],
        ['title' => 'Numbers & Maths','age'=> '5-7 Years', 'time' => '1-2 PM',   'capacity' => '30 Kids'],
        ['title' => 'Reading & Writing','age'=>'5-7 Years','time' => '2-3 PM',    'capacity' => '30 Kids'],
    ];
    $clsImgs = ['classes-1.jpg','classes-2.jpg','classes-3.jpg','classes-4.jpg','classes-5.jpg','classes-6.jpg'];
@endphp

@section('content')
    @include('school-site.kider.partials.page-header', ['heading' => 'Our Classes'])

    <div class="container-fluid py-5">
        <div class="container">
            <div class="text-center mx-auto mb-5 wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
                <h1 class="mb-3">School Classes &amp; Programmes</h1>
                <p>Engaging, age-appropriate programmes designed to help every child learn, play and grow.</p>
            </div>
            <div class="row g-4">
                @foreach ($classes as $i => $cls)
                <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.{{ ($i % 3) + 1 }}s">
                    <div class="classes-item">
                        <div class="bg-light rounded-circle w-75 mx-auto p-3">
                            <img class="img-fluid rounded-circle" src="{{ SchoolWebsite::media($cls['image'] ?? null, $clsImgs[$i % count($clsImgs)]) }}" alt="">
                        </div>
                        <div class="bg-light rounded p-4 pt-5 mt-n5">
                            <span class="d-block text-center h3 mt-3 mb-4">{{ $cls['title'] ?? '' }}</span>
                            <div class="row g-1">
                                <div class="col-4"><div class="border-top border-3 border-primary pt-2"><h6 class="text-primary mb-1">Age:</h6><small>{{ $cls['age'] ?? '-' }}</small></div></div>
                                <div class="col-4"><div class="border-top border-3 border-success pt-2"><h6 class="text-success mb-1">Time:</h6><small>{{ $cls['time'] ?? '-' }}</small></div></div>
                                <div class="col-4"><div class="border-top border-3 border-warning pt-2"><h6 class="text-warning mb-1">Capacity:</h6><small>{{ $cls['capacity'] ?? '-' }}</small></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
