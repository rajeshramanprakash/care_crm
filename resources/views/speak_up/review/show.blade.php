@extends('admin.layouts.app')
@section('title', 'Speak Up ' . $submission->reference_no)

@section('main')
<style>
    .su-msg { border-radius: 10px; padding: 10px 12px; margin-bottom: 8px; word-break: break-word; }
    .su-text, .su-msg.plain { white-space: pre-wrap; }
</style>
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            @include('speak_up.review._nav')
            <a href="{{ route('speak_up.review.cases', $slug) }}" class="btn btn-link px-0 mb-2"><i class="fas fa-arrow-left"></i> All cases</a>
            <div class="row">
                <div class="col-lg-8 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h5 class="fw-bold font-weight-bold mb-1">{{ $submission->reference_no }} — {{ $submission->subject }}</h5>
                            <div class="small text-muted mb-3">{{ $submission->category_label }} · {{ $submission->created_at->format('d M Y, h:i A') }}</div>
                            <div class="su-msg plain" style="background:#f8f9fa;border:1px solid #eee">{{ $submission->message }}</div>

                            <h6 class="fw-bold font-weight-bold mt-4">Replies &amp; notes</h6>
                            @forelse ($submission->replies as $reply)
                                <div class="su-msg" style="background: {{ $reply->is_internal ? '#fff8e1' : '#e8f5e9' }}">
                                    <div class="small mb-1">
                                        <b>{{ $reply->author_name ?: 'Admin' }}</b>
                                        <span class="badge {{ $reply->is_internal ? 'bg-warning badge-warning text-dark' : 'bg-success badge-success' }}">{{ $reply->is_internal ? 'Internal note' : 'Sent to submitter' }}</span>
                                        <span class="text-muted">{{ $reply->created_at->format('d M Y, h:i A') }}</span>
                                    </div>
                                    <div class="su-text">{{ $reply->message }}</div>
                                </div>
                            @empty
                                <p class="text-muted">No replies yet.</p>
                            @endforelse

                            @if ($errors->any())
                                <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                            @endif
                            <form method="POST" action="{{ route('speak_up.review.reply', [$slug, $submission->id]) }}" class="mt-3">
                                @csrf
                                <textarea name="message" class="form-control mb-2" rows="4" maxlength="5000" required placeholder="Write a reply or internal note"></textarea>
                                <div class="d-flex flex-wrap align-items-center" style="gap:12px;">
                                    <label class="mb-0"><input type="radio" name="visibility" value="internal" checked> Internal note (only Admin)</label>
                                    <label class="mb-0"><input type="radio" name="visibility" value="public"> Reply to submitter</label>
                                    <button type="submit" class="btn btn-dark ms-auto ml-auto">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold font-weight-bold">Submitted by</h6>
                            @if ($submission->is_anonymous)
                                <p><span class="badge bg-secondary badge-secondary">Anonymous</span><br><small class="text-muted">No name, ID or IP was stored.</small></p>
                            @else
                                <p class="mb-1">{{ $submission->submitter_name }}</p>
                                <p class="small text-muted">{{ $submission->submitter_role }} · {{ ucfirst($submission->submitter_type) }} #{{ $submission->submitter_id }}</p>
                            @endif
                            <h6 class="fw-bold font-weight-bold mt-3">Status</h6>
                            <form method="POST" action="{{ route('speak_up.review.status', [$slug, $submission->id]) }}">
                                @csrf
                                <select name="status" class="form-control form-select mb-2">
                                    @foreach (\App\Models\SpeakUpSubmission::STATUSES as $key => $label)
                                        <option value="{{ $key }}" @selected($submission->status === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-primary w-100 btn-block">Update status</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
