@extends('school-site.classic.layout', ['title' => 'Our Team'])

{{-- The team on the School Profile (SchoolWebsite::resolvedContent): a plain
     card for each member — round photo, name, designation. --}}
@push('styles')
<style>
    .team { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 18px; }
    .team figure { border: 1px solid var(--line); background: #fff; text-align: center; padding: 22px 14px 18px; transition: box-shadow .2s, border-color .2s; }
    .team figure:hover { border-color: color-mix(in srgb, var(--primary) 35%, var(--line)); box-shadow: 0 6px 18px rgba(0,0,0,.06); }
    .team .ph { width: 116px; height: 116px; margin: 0 auto 14px; border-radius: 50%; box-shadow: 0 0 0 4px var(--light); }
    .team b { display: block; font-size: 15px; font-weight: 600; color: #222; line-height: 1.35; overflow-wrap: anywhere; }
    .team span { display: block; margin-top: 4px; font-size: 11.5px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--primary); overflow-wrap: anywhere; }
    @media (max-width: 520px) {
        .team { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .team figure { padding: 16px 8px 14px; }
        .team .ph { width: 84px; height: 84px; margin-bottom: 10px; }
        .team b { font-size: 13.5px; }
        .team span { font-size: 10.5px; }
    }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Our Team'])

    <section class="page">
        <div class="wrap">
            <div class="card">
                @if (empty($c['team']))
                    <p class="muted-note">Our team will be introduced here shortly.</p>
                @else
                    <div class="team">
                        @foreach ($c['team'] as $m)
                            <figure>
                                @include('school-site.classic.partials.ph', ['src' => $m['photo'] ?? null, 'alt' => $m['name'] ?? ''])
                                <figcaption>
                                    <b>{{ $m['name'] ?? '' }}</b>
                                    @if (!empty($m['role']))<span>{{ $m['role'] }}</span>@endif
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
