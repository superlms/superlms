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

                @if (!empty($c['vision']) || !empty($c['mission']))
                    <div class="vm">
                        @if (!empty($c['vision']))
                            <div><h3>Our Vision</h3><p>{!! nl2br(e($c['vision'])) !!}</p></div>
                        @endif
                        @if (!empty($c['mission']))
                            <div><h3>Our Mission</h3><p>{!! nl2br(e($c['mission'])) !!}</p></div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
