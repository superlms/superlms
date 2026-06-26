{{-- Inner-page hero header. Param: $heading --}}
<div class="container-fluid bg-primary py-5 mb-5" style="margin-top:-1px;">
    <div class="container py-5">
        <div class="row justify-content-center py-4">
            <div class="col-lg-10 text-center">
                <h1 class="display-4 text-white animated slideInDown mb-3">{{ $heading }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center mb-0">
                        <li class="breadcrumb-item"><a class="text-white" href="{{ url('/') }}">Home</a></li>
                        <li class="breadcrumb-item text-white active" aria-current="page">{{ $heading }}</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>
