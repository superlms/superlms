{{-- The card beside a form on the modern templates: how to reach the school office (its phone, email and address). --}}
@php
    $sw  = fn ($i) => \App\Support\ModernSite::swatch($tpl, $i, $theme['primary']);
    $tel = \App\Support\ModernSite::tel($c);
@endphp
<aside class="help-card">
    <span class="badge-ico" style="{{ $sw(0) }}">@include('school-site.modern.partials.icon', ['n' => 'phone'])</span>
    <h3 class="h3">Talk to the school office</h3>
    <p>We are happy to help — call, write or drop in during office hours.</p>
    <div class="info-list">
        @if (!empty($c['phone']))<div class="info-card"><div><span>Call</span><a href="tel:{{ $tel }}">{{ $c['phone'] }}</a></div></div>@endif
        @if (!empty($c['email']))<div class="info-card"><div><span>Email</span><a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></div></div>@endif
        @if (!empty($c['address']))<div class="info-card"><div><span>Visit</span><b>{{ $c['address'] }}</b></div></div>@endif
    </div>
</aside>
