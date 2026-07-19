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
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Our Classes',
        'tag' => 'Programmes',
        'sub' => 'Engaging, age-appropriate programmes designed to help every child learn, play and grow.',
    ])

    <section class="section">
        <div class="section-inner">
            <div class="cards-grid">
                @foreach ($classes as $i => $cls)
                    <div class="class-card reveal">
                        <div class="class-thumb"><img src="{{ SchoolWebsite::media($cls['image'] ?? null, $clsImgs[$i % count($clsImgs)]) }}" alt=""></div>
                        <div class="class-body">
                            <p class="class-name">{{ $cls['title'] ?? '' }}</p>
                            <div class="class-meta">
                                <div><p class="k">Age</p><p class="v">{{ $cls['age'] ?? '-' }}</p></div>
                                <div><p class="k">Time</p><p class="v">{{ $cls['time'] ?? '-' }}</p></div>
                                <div><p class="k">Capacity</p><p class="v">{{ $cls['capacity'] ?? '-' }}</p></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
