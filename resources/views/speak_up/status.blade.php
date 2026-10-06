@extends($submitter['layout'])
@section('title', 'Speak Up — Status')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    @include('speak_up._shell_top')
                    @if (! $submission)
                        <div class="card su-card">
                            <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-search mr-2 me-2"></i>Check Status</h3></div>
                            <div class="card-body">
                                @if ($errors->any())
                                    <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                                @endif
                                <form method="POST" action="{{ route('speak_up.status.check') }}">
                                    @csrf
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold fw-bold">Reference number</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="SPK-2026-000001" value="{{ old('reference_no') }}" required autocomplete="off">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="font-weight-bold fw-bold">Follow-up key</label>
                                        <input type="text" name="follow_up_key" class="form-control" placeholder="XXXX-XXXX-XXXX" required autocomplete="off">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Check</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="card su-card">
                            <div class="card-header"><h3 class="card-title mb-0">{{ $submission->reference_no }} — {{ $submission->subject }}</h3></div>
                            <div class="card-body">
                                <p class="mb-2">
                                    @include('speak_up._status_badge')
                                    <span class="text-muted small ml-2 ms-2">{{ $submission->category_label }} · {{ $submission->created_at->format('d M Y, h:i A') }} · {{ $submission->is_anonymous ? 'Anonymous' : 'With my name' }}</span>
                                </p>
                                <div class="su-reply" style="background:#e8f5e9">{{ $submission->message }}</div>
                                <h6 class="mt-3 font-weight-bold fw-bold">Replies from Admin</h6>
                                @forelse ($submission->publicReplies as $reply)
                                    <div class="su-reply"><div class="small text-muted mb-1">{{ $reply->created_at->format('d M Y, h:i A') }}</div>{{ $reply->message }}</div>
                                @empty
                                    <p class="text-muted">No reply yet.</p>
                                @endforelse
                                @if ($submission->is_anonymous && ! empty($followUpKey))
                                    <div class="mt-3 d-none" id="suSaveBox">
                                        <button type="button" class="btn btn-sm btn-outline-success" id="suSaveBtn"><i class="fas fa-save"></i> Save on this device</button>
                                        <span class="small text-muted ml-2 ms-2">Track it from My Submissions on this browser.</span>
                                    </div>
                                    <div class="mt-3 small text-success d-none" id="suSavedMsg"><i class="fas fa-check"></i> Saved on this device — it shows in <a href="{{ route('speak_up.mine') }}">My Submissions</a>.</div>
                                @endif
                            </div>
                        </div>
                        @if ($submission->is_anonymous && ! empty($followUpKey))
                            @include('speak_up._device_store')
                            <script>
                                (function () {
                                    var ref = @json($submission->reference_no), key = @json($followUpKey);
                                    var box = document.getElementById('suSaveBox'), done = document.getElementById('suSavedMsg');
                                    if (window.SpeakUpDevice.has(ref)) { done.classList.remove('d-none'); return; }
                                    box.classList.remove('d-none');
                                    document.getElementById('suSaveBtn').addEventListener('click', function () {
                                        if (window.SpeakUpDevice.save(ref, key)) { box.classList.add('d-none'); done.classList.remove('d-none'); }
                                    });
                                })();
                            </script>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
