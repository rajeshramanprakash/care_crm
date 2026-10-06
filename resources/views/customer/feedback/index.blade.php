@extends('customer.layouts.app')
@section('title', 'Feedback')

@section('content')
<style>
    .fb-card { border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,.06); border: 0; }
    .fb-card .card-header { background: linear-gradient(90deg, #ff8a00 0%, #ff7a18 100%); color: #fff; border-radius: 12px 12px 0 0; }
    .fb-stars { display: inline-flex; flex-direction: row-reverse; gap: 4px; }
    .fb-stars input { display: none; }
    .fb-stars label { font-size: 26px; color: #d0d0d0; cursor: pointer; margin: 0; line-height: 1; }
    .fb-stars input:checked ~ label, .fb-stars label:hover, .fb-stars label:hover ~ label { color: #f5a623; }
    .fb-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 8px 0; border-bottom: 1px dashed #eee; }
    .fb-row:last-child { border-bottom: 0; }
    .fb-static-stars { color: #f5a623; letter-spacing: 1px; }
    .fb-static-stars .off { color: #d8d8d8; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-lg-7 mb-3">
                    <div class="card fb-card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-star mr-2"></i>How was your experience?</h3></div>
                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger py-2">
                                    @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                                </div>
                            @endif
                            @if ($pendingServices->isEmpty())
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-clipboard-check fa-2x mb-2 d-block"></i>
                                    You can give feedback once a service is completed.<br>No completed service is waiting for your feedback right now.
                                </div>
                            @else
                            <form method="POST" action="{{ route('customer.feedback.store') }}">
                                @csrf
                                <div class="form-group">
                                    <label class="font-weight-bold">Service <span class="text-danger">*</span></label>
                                    <select name="operation_lead_id" class="form-control" required>
                                        @if ($pendingServices->count() > 1)<option value="">Select the completed service</option>@endif
                                        @foreach ($pendingServices as $service)
                                            <option value="{{ $service->id }}" @selected((int) old('operation_lead_id', $selectedServiceId) === (int) $service->id)>
                                                #{{ $service->id }} — {{ \Illuminate\Support\Str::limit($service->query ?: 'Service', 60) }}{{ $service->patient_name ? ' (' . $service->patient_name . ')' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @foreach (\App\Models\CustomerFeedback::RATING_FIELDS as $field => $label)
                                    <div class="fb-row">
                                        <span class="font-weight-bold">{{ $label }} @if($field === 'overall_rating')<span class="text-danger">*</span>@endif</span>
                                        <span class="fb-stars">
                                            @for ($i = 5; $i >= 1; $i--)
                                                <input type="radio" id="{{ $field }}_{{ $i }}" name="{{ $field }}" value="{{ $i }}" @checked((int) old($field) === $i)>
                                                <label for="{{ $field }}_{{ $i }}" title="{{ $i }} star">&#9733;</label>
                                            @endfor
                                        </span>
                                    </div>
                                @endforeach

                                <div class="form-group mt-3">
                                    <label class="font-weight-bold">Suggestions</label>
                                    <textarea name="suggestions" class="form-control" rows="3" maxlength="3000" placeholder="Anything we can do better?">{{ old('suggestions') }}</textarea>
                                </div>
                                <div class="form-group">
                                    <label class="font-weight-bold">Complaint (if any)</label>
                                    <textarea name="complaint" class="form-control" rows="3" maxlength="3000" placeholder="Tell us if something went wrong">{{ old('complaint') }}</textarea>
                                </div>
                                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane mr-1"></i> Submit Feedback</button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-3">
                    <div class="card fb-card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-history mr-2"></i>My Feedback</h3></div>
                        <div class="card-body">
                            @forelse ($feedbacks as $feedback)
                                <div class="border rounded p-2 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fb-static-stars">
                                            @for ($i = 1; $i <= 5; $i++)<span class="{{ $i <= $feedback->overall_rating ? '' : 'off' }}">&#9733;</span>@endfor
                                        </span>
                                        <small class="text-muted">{{ $feedback->created_at->format('d M Y, h:i A') }}</small>
                                    </div>
                                    @if ($feedback->operationLead)
                                        <small class="text-muted d-block">Service #{{ $feedback->operation_lead_id }} — {{ \Illuminate\Support\Str::limit($feedback->operationLead->query ?: 'Service', 50) }}</small>
                                    @endif
                                    @if ($feedback->suggestions)<div class="small mt-1"><b>Suggestion:</b> {{ $feedback->suggestions }}</div>@endif
                                    @if ($feedback->complaint)<div class="small mt-1"><b>Complaint:</b> {{ $feedback->complaint }}</div>@endif
                                    <span class="badge badge-{{ $feedback->status === 'resolved' ? 'success' : ($feedback->status === 'reviewed' ? 'info' : 'secondary') }}">{{ \App\Models\CustomerFeedback::STATUSES[$feedback->status] ?? $feedback->status }}</span>
                                </div>
                            @empty
                                <p class="text-muted mb-0">You have not given any feedback yet.</p>
                            @endforelse
                            {{ $feedbacks->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
