{{--
    Online Admission → Classes for Vibrant and Breeze: the classes with their
    sections and subjects from the Standard page; under them Website Data's
    class cards, when the school has added any (Classic's data).
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Classes'])

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Classes'])

    @php $lmsClasses = $lms->classes(); @endphp
    <section class="pg">
        <div class="wrap">
            @if ($lmsClasses)
                @include('school-site.shared.lms-classes', ['classes' => $lmsClasses])
            @endif

            @if (!empty($c['classes']))
                <div class="cls" @if ($lmsClasses) style="margin-top:24px;" @endif>
                    @foreach ($c['classes'] as $k)
                        <article>
                            @include('school-site.classic.partials.ph', ['src' => $k['image'] ?? null, 'alt' => $k['title'] ?? ''])
                            <h2 class="h3">{{ $k['title'] ?? '' }}</h2>
                            <dl>
                                @if (!empty($k['age']))<dt>Age</dt><dd>{{ $k['age'] }}</dd>@endif
                                @if (!empty($k['time']))<dt>Timing</dt><dd>{{ $k['time'] }}</dd>@endif
                                @if (!empty($k['capacity']))<dt>Seats</dt><dd>{{ $k['capacity'] }}</dd>@endif
                            </dl>
                        </article>
                    @endforeach
                </div>
            @elseif (! $lmsClasses)
                @include('school-site.shared.lms-style')
                <div class="lms-card"><p class="lms-empty">Details of our classes will be published here shortly.</p></div>
            @endif
        </div>
    </section>
@endsection
