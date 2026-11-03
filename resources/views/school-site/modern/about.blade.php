{{--
    About Us → About School, for Vibrant and Breeze — Classic's data: the
    about text, vision, mission, values and goals are the School Profile's
    (SchoolWebsite::profileTexts); the heading, the second paragraph,
    history and philosophy are Website Data's. Beside the text: a photo and
    the school's facts; under it the four as cards.
--}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'About School'])

@php
    use App\Support\ModernSite;

    $sw    = fn ($i) => ModernSite::swatch($tpl, $i, $theme['primary']);
    $pool  = ModernSite::photos($c);
    $board = trim((string) ($c['board'] ?? ''));
    $facts = array_filter([
        ['school', 'Board', $board !== '' ? $board : null],
        ['award', 'Affiliation No.', $c['affiliation_no'] ?: null],
        ['grid', 'School Code', $c['school_code'] ?: null],
        ['book', 'Medium', trim((string) ($c['medium'] ?? '')) ?: null],
        ['users', 'Classes', ModernSite::classRange($lms, $c) ?: null],
    ], fn ($f) => $f[2] !== null);
    $vm = array_filter([
        'Our Vision'  => ['eye', $c['vision'] ?? ''],
        'Our Mission' => ['target', $c['mission'] ?? ''],
        'Our Values'  => ['heart', $c['values'] ?? ''],
        'Our Goals'   => ['flag', $c['goals'] ?? ''],
    ], fn ($t) => trim((string) $t[1]) !== '');
    $more = array_filter(['Our History' => ['clock', $c['history_text'] ?? ''], 'Our Philosophy' => ['sparkle', $c['philosophy'] ?? '']],
        fn ($t) => trim((string) $t[1]) !== '');
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'About School'])

    <section class="pg">
        <div class="wrap">
            <div class="split">
                <div class="panel">
                    <span class="eyebrow">About School</span>
                    <h2 class="h2">{!! ModernSite::highlight($c['about_heading'], $c['school_name']) !!}</h2>
                    <div class="prose" style="margin-top:18px;">
                        @include('school-site.classic.partials.paras', ['text' => $c['about_text']])
                        @if (!empty($c['about_text2']))
                            @include('school-site.classic.partials.paras', ['text' => $c['about_text2']])
                        @endif
                    </div>
                </div>
                <aside class="about-side">
                    @include('school-site.classic.partials.ph', ['src' => $pool[0] ?? null, 'alt' => $c['school_name'], 'lb' => true])
                    @if ($facts)
                        <ul class="facts">
                            @foreach (array_values($facts) as $i => [$icon, $factName, $value])
                                <li><span class="badge-ico" style="{{ $sw($i) }}">@include('school-site.modern.partials.icon', ['n' => $icon])</span><div><span>{{ $factName }}</span><b>{{ $value }}</b></div></li>
                            @endforeach
                        </ul>
                    @endif
                </aside>
            </div>

            @if ($more)
                <div class="grid-2" style="margin-top:24px;">
                    @foreach (array_values($more) as $i => [$icon, $text])
                        <div class="tile">
                            <span class="badge-ico" style="{{ $sw($i + 3) }}">@include('school-site.modern.partials.icon', ['n' => $icon])</span>
                            <h3 class="h3">{{ array_keys($more)[$i] }}</h3>
                            <div class="prose">@include('school-site.classic.partials.paras', ['text' => $text])</div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Vision, Mission, Values and Goals: the School Profile's. --}}
            @if ($vm)
                <div class="tiles" style="margin-top:24px;">
                    @foreach (array_values($vm) as $i => [$icon, $text])
                        <div class="tile">
                            <span class="badge-ico" style="{{ $sw($i) }}">@include('school-site.modern.partials.icon', ['n' => $icon])</span>
                            <h3 class="h3">{{ array_keys($vm)[$i] }}</h3>
                            <p>{!! nl2br(e($text)) !!}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
