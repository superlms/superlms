{{-- Contact → Query for Vibrant and Breeze: Classic's question form, with the school's contact beside it. --}}
@extends('school-site.' . $tpl . '.layout', ['title' => 'Query'])

@section('content')
    @include('school-site.' . $tpl . '.partials.banner', ['heading' => 'Query'])

    <section class="pg">
        <div class="wrap">
            <div class="form-split">
                <div class="panel">
                    <span class="eyebrow">Ask us</span>
                    <h2 class="h2">Have a <span class="hl">question?</span></h2>
                    <p class="lead-text" style="margin:10px 0 24px;">Have a question about the school? Write to us and we will get back to you.</p>
                    @include('school-site.classic.partials.form', [
                        'kind'   => 'Query',
                        'type'   => 'query',
                        'fields' => [
                            ['name' => 'name', 'label' => 'Name', 'required' => true],
                            ['name' => 'phone', 'label' => 'Mobile No.', 'type' => 'tel', 'required' => true],
                            ['name' => 'email', 'label' => 'Email ID', 'type' => 'email', 'wide' => true],
                            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'required' => true],
                        ],
                    ])
                </div>
                @include('school-site.modern.partials.reach')
            </div>
        </div>
    </section>
@endsection
