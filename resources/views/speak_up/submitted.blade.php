@extends($submitter['layout'])
@section('title', 'Speak Up — Submitted')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    @include('speak_up._shell_top')
                    <div class="card su-card">
                        <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-check-circle mr-2 me-2"></i>Submitted successfully</h3></div>
                        <div class="card-body text-center">
                            <p class="mb-1">Reference number</p>
                            <div class="su-key mb-3">{{ $receipt['reference_no'] }}</div>
                            <p class="mb-1">Follow-up key</p>
                            <div class="su-key mb-3">{{ $receipt['follow_up_key'] }}</div>
                            <div class="alert alert-warning text-left text-start">
                                <b>Save both now.</b> The follow-up key is shown only once and cannot be recovered.
                                Use them on <a href="{{ route('speak_up.status') }}">Check Status</a> to see the status and replies from Admin.
                                @if ($receipt['is_anonymous'])
                                    <br>This submission is anonymous — nobody (including Admin) can see who sent it.
                                @else
                                    <br>You can also follow it anytime from <a href="{{ route('speak_up.mine') }}">My Submissions</a>.
                                @endif
                            </div>
                            @if ($receipt['is_anonymous'])
                                <div class="alert alert-success text-left text-start d-none" id="suSavedNote">
                                    <i class="fas fa-check"></i> Saved on this device. You can track it in <a href="{{ route('speak_up.mine') }}">My Submissions</a> on this browser.
                                    On another phone / computer, use Check Status with the key.
                                </div>
                            @endif
                            <a href="{{ route('speak_up.create') }}" class="btn btn-outline-dark">Submit another</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@if ($receipt['is_anonymous'])
    @include('speak_up._device_store')
    <script>
        if (window.SpeakUpDevice.save(@json($receipt['reference_no']), @json($receipt['follow_up_key']))) {
            document.getElementById('suSavedNote').classList.remove('d-none');
        }
    </script>
@endif
@endsection
