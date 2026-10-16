@extends('school-site.kider.layout')

@section('content')
    @include('school-site.kider.partials.page-header', [
        'heading' => 'Query',
        'tag' => 'Ask Us',
        'sub' => 'Write to us with any question about the school and we will get back to you.',
    ])

    <section class="section">
        <div class="section-inner" style="max-width:760px;">
            <div class="reveal">
                @include('school-site.kider.partials.enquiry-form', ['site' => $site])
            </div>
        </div>
    </section>
@endsection
