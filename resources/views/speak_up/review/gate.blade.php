@extends('admin.layouts.app')
@section('title', 'Speak Up Review')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-5">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card" style="border:0;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.08);">
                        <div class="card-body p-4 text-center">
                            <div style="font-size:42px;color:#2f3e46"><i class="fas fa-user-shield"></i></div>
                            <h4 class="fw-bold font-weight-bold mt-2">Speak Up — Confidential</h4>
                            @if (! $hasPassword)
                                <p class="text-muted">Speak Up password is not set yet.</p>
                                <a href="{{ route('admin.speak_up_settings.index') }}" class="btn btn-primary">Set password</a>
                            @else
                                <p class="text-muted small">This area is protected. Every visit is recorded with time, IP and device.</p>
                                @if ($errors->any())
                                    <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                                @endif
                                @if ($lockedFor > 0)
                                    <div class="alert alert-warning py-2 small">Locked for {{ ceil($lockedFor / 60) }} more minute(s) after too many wrong attempts.</div>
                                @endif
                                <form method="POST" action="{{ route('speak_up.review.unlock', $slug) }}" id="suGateForm" autocomplete="off">
                                    @csrf
                                    <input type="hidden" name="client_info" id="suClientInfo">
                                    <input type="password" name="password" class="form-control mb-3" placeholder="Speak Up password" required autofocus autocomplete="new-password" @disabled($lockedFor > 0)>
                                    <button type="submit" class="btn btn-dark w-100 btn-block" @disabled($lockedFor > 0)><i class="fas fa-unlock-alt"></i> Open</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@include('speak_up.review._client_info')
<script>
    (function () {
        var info = window.speakUpClientInfo();
        var field = document.getElementById('suClientInfo');
        if (field) field.value = info;
        var body = new FormData();
        body.append('client_info', info);
        body.append('_token', @json(csrf_token()));
        fetch(@json(route('speak_up.review.device', $slug)), { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json' } }).catch(function () {});
    })();
</script>
@endsection
