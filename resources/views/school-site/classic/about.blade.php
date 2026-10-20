{{--
    About Us → About School: the about text, vision, mission, values and goals
    are the School Profile's (SchoolWebsite::profileTexts); the heading, the
    second paragraph, history and philosophy are Website Data's.
--}}
@extends('school-site.classic.layout', ['title' => 'About School'])

@push('styles')
<style>
    .vm { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
    .vm > div { border: 1px solid var(--line); border-top: 3px solid var(--primary); padding: 18px 20px; background: #FCFCFD; }
    .vm h3 { font-size: 15px; font-weight: 700; color: #222; margin-bottom: 6px; }
    .vm p { color: #444; }
    @media (max-width: 720px) { .vm { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'About School'])

    <section class="page">
        <div class="wrap">
            <div class="card">
                <h2 class="title">{{ $c['about_heading'] }}</h2>
                <div class="prose" style="margin-top:14px;">
                    @include('school-site.classic.partials.paras', ['text' => $c['about_text']])
                    @if (!empty($c['about_text2']))
                        @include('school-site.classic.partials.paras', ['text' => $c['about_text2']])
                    @endif
                    @if (!empty($c['history_text']))
                        <h3>Our History</h3>
                        @include('school-site.classic.partials.paras', ['text' => $c['history_text']])
                    @endif
                    @if (!empty($c['philosophy']))
                        <h3>Our Philosophy</h3>
                        @include('school-site.classic.partials.paras', ['text' => $c['philosophy']])
                    @endif
                </div>

                {{-- Vision, Mission, Values and Goals: the School Profile's. --}}
                @php $vm = array_filter(['Our Vision' => $c['vision'] ?? '', 'Our Mission' => $c['mission'] ?? '', 'Our Values' => $c['values'] ?? '', 'Our Goals' => $c['goals'] ?? ''], fn ($t) => trim((string) $t) !== ''); @endphp
                @if ($vm)
                    <div class="vm">
                        @foreach ($vm as $title => $text)
                            <div><h3>{{ $title }}</h3> <p>{!! nl2br(e($text)) !!}</p></div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
