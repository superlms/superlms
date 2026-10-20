@extends('school-site.classic.layout', ['title' => 'Contact'])

@push('styles')
<style>
    .contact { display: grid; grid-template-columns: 1fr 1.3fr; gap: 28px; align-items: start; }
    .contact h2 { font-size: 18px; color: #222; margin-bottom: 10px; }
    .contact p { margin-bottom: 6px; color: #444; }
    .contact b { color: #222; }
    @media (max-width: 820px) { .contact { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Contact'])

    <section class="page">
        <div class="wrap">
            <div class="card contact">
                <div>
                    <h2>{{ $c['school_name'] }}</h2>
                    @if (!empty($c['address']))<p>{{ $c['address'] }}</p>@endif
                    @if (!empty($c['email']))<p><b>Email:</b> <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></p>@endif
                    @if (!empty($c['phone']))<p><b>Contact:</b> {{ $c['phone'] }}</p>@endif
                    @if (!empty($c['affiliation_no']))<p><b>{{ $c['board'] ?: 'CBSE' }} Affiliation No.:</b> {{ $c['affiliation_no'] }}</p>@endif
                    @if (!empty($c['school_code']))<p><b>School Code:</b> {{ $c['school_code'] }}</p>@endif
                </div>
                @include('school-site.classic.partials.map', ['height' => 320])
            </div>

            <div class="card">
                <h2 class="title" style="margin-bottom:16px;">Send Us a Message</h2>
                @include('school-site.classic.partials.form', [
                    'kind'   => 'Contact',
                    'type'   => 'contact',
                    'fields' => [
                        ['name' => 'name', 'label' => 'Name', 'required' => true],
                        ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true],
                        ['name' => 'email', 'label' => 'Email ID', 'type' => 'email', 'wide' => true],
                        ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                    ],
                ])
            </div>
        </div>
    </section>
@endsection
