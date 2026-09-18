@extends('admin.layouts.app')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <style>
        .otp-log-table code { font-size: 0.9rem; }
        .badge-verified { background: #d1fae5; color: #065f46; border: 1px solid #86efac; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fcd34d; }
    </style>
@endsection

@section('content')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Verify No.</h1>
                    <p class="text-muted mb-0 small">Doctor registration — mobile OTP sent / verified (timestamp &amp; IP)</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <form method="get" action="{{ route('subadmin.doctor_registration_otp_logs.index') }}" class="form-inline flex-wrap gap-2">
                        <input type="text" name="mobile" class="form-control form-control-sm mr-2 mb-1"
                               placeholder="Filter mobile" maxlength="10" value="{{ $filterMobile }}">
                        <select name="status" class="form-control form-control-sm mr-2 mb-1">
                            <option value="">All statuses</option>
                            <option value="verified" @selected($filterStatus === 'verified')>Verified</option>
                            <option value="pending" @selected($filterStatus === 'pending')>Pending</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary mb-1">Filter</button>
                        <a href="{{ route('subadmin.doctor_registration_otp_logs.index') }}" class="btn btn-sm btn-secondary mb-1">Reset</a>
                    </form>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped mb-0 otp-log-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Mobile</th>
                                <th>OTP</th>
                                <th>Sent at</th>
                                <th>Sent IP</th>
                                <th>Verified at</th>
                                <th>Verified IP</th>
                                <th>Attempts</th>
                                <th>Last attempt</th>
                                <th>MSG91 Request ID</th>
                                <th>MSG91 status</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <td>{{ $log->id }}</td>
                                <td><strong>{{ $log->mobile }}</strong></td>
                                <td><code>{{ $log->otp }}</code></td>
                                <td>{{ $log->sent_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                <td>{{ $log->sent_ip ?: '—' }}</td>
                                <td>{{ $log->verified_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                <td>{{ $log->verified_ip ?: '—' }}</td>
                                <td>{{ $log->verify_attempts }}</td>
                                <td>
                                    @if($log->last_verify_attempt_at)
                                        {{ $log->last_verify_attempt_at->format('d M Y, h:i A') }}
                                        @if($log->last_verify_attempt_ip)
                                            <br><span class="text-muted small">{{ $log->last_verify_attempt_ip }}</span>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($log->msg91_request_id)
                                        <code style="font-size:0.75rem;">{{ $log->msg91_request_id }}</code>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    {{ $log->msg91_delivery_status ?: '—' }}
                                    @if($log->msg91_last_error)
                                        <br><span class="text-danger small">{{ Str::limit($log->msg91_last_error, 80) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($log->isVerified())
                                        <span class="badge badge-verified">Verified</span>
                                    @else
                                        <span class="badge badge-pending">Pending</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">No OTP logs yet. They appear when doctors use Send OTP on the registration form.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                <div class="card-footer">
                    {{ $logs->links() }}
                </div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
