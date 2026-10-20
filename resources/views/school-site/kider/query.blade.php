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
                @include('school-site.kider.partials.enquiry-form', ['site' => $site, 'type' => 'query', 'fields' => [
                    ['name' => 'name', 'label' => 'Your Name', 'required' => true, 'placeholder' => 'e.g. Priya Sharma'],
                    ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true, 'placeholder' => '10-digit mobile'],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'full' => true, 'placeholder' => 'you@example.com'],
                    ['name' => 'message', 'label' => 'Your Question', 'type' => 'textarea', 'required' => true, 'placeholder' => 'What would you like to know?'],
                ]])
            </div>
        </div>
    </section>
@endsection
