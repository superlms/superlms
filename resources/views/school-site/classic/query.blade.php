@extends('school-site.classic.layout', ['title' => 'Query'])

@section('content')
    @include('school-site.classic.partials.banner', ['heading' => 'Query'])

    <section class="page">
        <div class="wrap" style="max-width:860px;">
            <div class="card">
                <p style="color:var(--muted);margin-bottom:18px;">Have a question about the school? Write to us and we will get back to you.</p>
                @include('school-site.classic.partials.form', [
                    'kind'   => 'Query',
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
