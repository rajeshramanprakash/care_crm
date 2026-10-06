@extends($layout)
@section('title', 'Customer Feedback #' . $feedback->id)

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <a href="{{ route($rp . '.customer_feedback.index') }}" class="btn btn-link px-0 mb-2"><i class="fas fa-arrow-left"></i> All feedback</a>
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            @include('admin.customer_feedback._detail', ['feedback' => $feedback])
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            @include('admin.customer_feedback._routing', ['feedback' => $feedback])
                            <hr>
                            <p class="mb-0"><b>Admin status:</b> {{ \App\Models\CustomerFeedback::STATUSES[$feedback->status] ?? $feedback->status }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
