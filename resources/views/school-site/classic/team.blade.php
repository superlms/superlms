@extends('school-site.classic.layout', ['title' => 'Our Team'])

@push('styles')
<style>
    .team { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px; }
    .team figure { border: 1px solid var(--line); background: #fff; text-align: center; }
    .team .ph { aspect-ratio: 1; }
    .team figcaption { padding: 10px 10px 12px; }
    .team b { display: block; font-size: 14px; color: #222; }
    .team span { font-size: 12.5px; color: var(--muted); }
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
                                <figcaption><b>{{ $m['name'] ?? '' }}</b><span>{{ $m['role'] ?? '' }}</span></figcaption>
                            </figure>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
