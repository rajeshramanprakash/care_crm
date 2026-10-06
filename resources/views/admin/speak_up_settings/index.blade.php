@extends('admin.layouts.app')
@section('title', 'Speak Up Settings')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <h4 class="fw-bold mb-3"><i class="fas fa-user-shield"></i> Speak Up Settings</h4>
            @if ($errors->any())
                <div class="alert alert-danger py-2">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
            @endif
            <div class="row">
                <div class="col-lg-7 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold">Secret review URL</h6>
                            <p class="small text-muted mb-2">Only open this URL while logged in as Admin. It asks for the Speak Up password, and every visit is recorded (time, date, IP, browser, device). Do not share it.</p>
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" id="suSecretUrl" value="{{ $secretUrl }}" readonly>
                                <button type="button" class="btn btn-outline-secondary" onclick="navigator.clipboard && navigator.clipboard.writeText(document.getElementById('suSecretUrl').value); this.textContent='Copied';">Copy</button>
                                <a href="{{ $secretUrl }}" class="btn btn-dark" target="_blank" rel="noopener noreferrer">Open</a>
                            </div>
                            <p class="mb-0"><span class="badge bg-primary">{{ $newCount }} new</span> <span class="text-muted small">of {{ $totalCount }} total submissions</span></p>
                        </div>
                    </div>

                    <div class="card mt-3" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold">{{ $setting->hasPassword() ? 'Change Speak Up password' : 'Set Speak Up password' }}</h6>
                            @if ($setting->hasPassword())
                                <p class="small text-muted">Last changed {{ optional($setting->password_changed_at)->format('d M Y, h:i A') ?? '—' }}. Changing it locks out everyone who already unlocked.</p>
                            @else
                                <p class="small text-warning">No password yet — the review page stays closed until you set one.</p>
                            @endif
                            <form method="POST" action="{{ route('admin.speak_up_settings.password') }}" autocomplete="off">
                                @csrf
                                @if ($setting->hasPassword())
                                    <input type="password" name="current_password" class="form-control mb-2" placeholder="Current Speak Up password" required autocomplete="off">
                                @endif
                                <input type="password" name="password" class="form-control mb-2" placeholder="New password (min 8, letters + numbers)" required autocomplete="new-password">
                                <input type="password" name="password_confirmation" class="form-control mb-2" placeholder="Confirm new password" required autocomplete="new-password">
                                <button type="submit" class="btn btn-success">Save password</button>
                            </form>
                            <p class="small text-muted mt-2 mb-0">Forgot it? Run <code>php artisan speakup:reset-password</code> on the server.</p>
                        </div>
                    </div>

                    <div class="card mt-3" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold">Create a new secret URL</h6>
                            <p class="small text-muted">Use this if the URL may have leaked. The old URL stops working immediately.</p>
                            <form method="POST" action="{{ route('admin.speak_up_settings.regenerate') }}" class="d-flex flex-wrap" style="gap:8px;" onsubmit="return confirm('Create a new URL? The old one will stop working.')">
                                @csrf
                                @if ($setting->hasPassword())
                                    <input type="password" name="current_password" class="form-control" style="max-width:260px" placeholder="Speak Up password" required autocomplete="off">
                                @endif
                                <button type="submit" class="btn btn-outline-danger">Generate new URL</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-3">
                    <div class="card" style="border:0;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,.06);">
                        <div class="card-body">
                            <h6 class="fw-bold">Recent access</h6>
                            <ul class="list-unstyled small mb-0">
                                @forelse ($recentLogs as $log)
                                    <li class="border-bottom py-1 {{ $log->success ? '' : 'text-danger' }}">
                                        <b>{{ $log->created_at->format('d M Y, h:i:s A') }}</b> — {{ $log->event_label }}<br>
                                        <span class="text-muted">{{ $log->user_name ?: 'Guest' }} · {{ $log->ip_address }} · {{ $log->browser }} / {{ $log->platform }}</span>
                                    </li>
                                @empty
                                    <li class="text-muted">No access yet.</li>
                                @endforelse
                            </ul>
                            <p class="small text-muted mt-2 mb-0">Full log is inside the secret review page → Access Log.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
