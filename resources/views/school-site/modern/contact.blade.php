{{-- Contact → Contact Us for Vibrant and Breeze: how to reach the school, the map and the enquiry form (Classic's data and form). --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Contact'])
@php
    use App\Support\ModernSite;

    $sw     = fn ($i) => ModernSite::swatch($tpl, $i, $theme['primary']);
    $tel    = ModernSite::tel($c);
    $mapSrc = \App\Models\SchoolWebsite::mapSrc($c);
    $aff    = trim(($c['affiliation_no'] ? 'Aff. No. ' . $c['affiliation_no'] : '') . ($c['affiliation_no'] && $c['school_code'] ? ' · ' : '') . ($c['school_code'] ? 'School Code ' . $c['school_code'] : ''));
@endphp

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Contact'])

    <section class="pg">
        <div class="wrap">
            <div class="contact-grid" @if (! $mapSrc) style="grid-template-columns:minmax(0,1fr);" @endif>
                <div class="info-list">
                    <div class="info-card"><span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'school'])</span><div><span>School</span><b>{{ $c['school_name'] }}</b></div></div>
                    @if (!empty($c['address']))<div class="info-card"><span class="badge-ico" style="{{ $sw(1) }}">@include('school-site.modern.partials.icon', ['n' => 'pin'])</span><div><span>Address</span><b>{{ $c['address'] }}</b></div></div>@endif
                    @if (!empty($c['phone']))<div class="info-card"><span class="badge-ico" style="{{ $sw(2) }}">@include('school-site.modern.partials.icon', ['n' => 'phone'])</span><div><span>Contact</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>@endif
                    @if (!empty($c['email']))<div class="info-card"><span class="badge-ico" style="{{ $sw(3) }}">@include('school-site.modern.partials.icon', ['n' => 'mail'])</span><div><span>Email</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>@endif
                    @if ($aff !== '')<div class="info-card"><span class="badge-ico" style="{{ $sw(4) }}">@include('school-site.modern.partials.icon', ['n' => 'award'])</span><div><span>{{ $c['board'] ?: 'CBSE' }} Affiliation</span><b>{{ $aff }}</b></div></div>@endif
                </div>
                @if ($mapSrc)
                    <div class="map-card">@include('school-site.classic.partials.map', ['height' => 440])</div>
                @endif
            </div>

            <div class="panel" style="margin-top:24px;">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">Write to us</span>
                        <h2 class="h2">Send Us a Message</h2>
                    </div>
                </div>
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
