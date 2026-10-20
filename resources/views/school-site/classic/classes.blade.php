@extends('school-site.classic.layout', ['title' => 'Classes'])

@push('styles')
<style>
    .cls { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 18px; }
    .cls article { border: 1px solid var(--line); background: #fff; }
    .cls .ph { aspect-ratio: 16 / 10; }
    .cls h2 { font-size: 16px; color: #222; padding: 12px 14px 4px; }
    .cls dl { display: grid; grid-template-columns: auto 1fr; gap: 2px 10px; padding: 0 14px 14px; font-size: 13px; }
    .cls dt { color: var(--muted); }
    .cls dd { color: #222; font-weight: 500; }
</style>
@endpush

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Classes'])

    @php $lmsClasses = $lms->classes(); @endphp
    <section class="page">
        <div class="wrap">
            {{-- The classes, sections and subjects set up on the Standard page, always as they are now. --}}
            @if ($lmsClasses)
                <div class="card">
                    <h2 class="title" style="margin-bottom:14px;">Classes, Sections &amp; Subjects</h2>
                    @include('school-site.shared.lms-classes', ['classes' => $lmsClasses])
                </div>
            @endif

            @if (! $lmsClasses || ! empty($c['classes']))
            <div class="card">
                @if (empty($c['classes']))
                    <p class="muted-note">Details of our classes will be published here shortly.</p>
                @else
                    <div class="cls">
                        @foreach ($c['classes'] as $k)
                            <article>
                                @include('school-site.classic.partials.ph', ['src' => $k['image'] ?? null, 'alt' => $k['title'] ?? ''])
                                <h2>{{ $k['title'] ?? '' }}</h2>
                                <dl>
                                    @if (!empty($k['age']))<dt>Age</dt><dd>{{ $k['age'] }}</dd>@endif
                                    @if (!empty($k['time']))<dt>Timing</dt><dd>{{ $k['time'] }}</dd>@endif
                                    @if (!empty($k['capacity']))<dt>Seats</dt><dd>{{ $k['capacity'] }}</dd>@endif
                                </dl>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
            @endif
        </div>
    </section>
@endsection
