@extends('admin.layouts.app')
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
                            <h6 class="fw-bold">Review</h6>
                            @if ($feedback->reviewer)
                                <p class="small text-muted">Last reviewed by {{ trim($feedback->reviewer->f_name . ' ' . $feedback->reviewer->l_name) }} on {{ optional($feedback->reviewed_at)->format('d M Y, h:i A') }}</p>
                            @endif
                            @can('edit_customer_feedback')
                                <form method="POST" action="{{ route($rp . '.customer_feedback.update', $feedback->id) }}">
                                    @csrf
                                    <select name="status" class="form-control form-select mb-2">
                                        @foreach (\App\Models\CustomerFeedback::STATUSES as $k => $l)
                                            <option value="{{ $k }}" @selected($feedback->status === $k)>{{ $l }}</option>
                                        @endforeach
                                    </select>
                                    <textarea name="admin_note" class="form-control mb-2" rows="4" maxlength="3000" placeholder="Internal note (customer cannot see)">{{ $feedback->admin_note }}</textarea>
                                    <button type="submit" class="btn btn-primary w-100">Save</button>
                                </form>
                            @else
                                <p class="mb-1"><b>Status:</b> {{ \App\Models\CustomerFeedback::STATUSES[$feedback->status] ?? $feedback->status }}</p>
                                <p class="small" style="white-space:pre-wrap">{{ $feedback->admin_note }}</p>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
