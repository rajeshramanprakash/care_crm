@extends('corporate.layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-0">
            <div class="card-body">
                <h4 class="mb-2">Welcome, {{ $user->corporate_name }}</h4>
                <p class="text-muted">You are signed in to the Carelix Corporate portal.</p>

                <table class="table table-sm table-borderless mt-3 mb-0">
                    <tr>
                        <th class="text-muted" style="width:40%;">Corporate name</th>
                        <td>{{ $user->corporate_name }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Username</th>
                        <td><code>{{ $user->username }}</code></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Created by</th>
                        <td>{{ $user->createdByLabel() }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Account status</th>
                        <td>
                            @if($user->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Login URL</th>
                        <td><a href="{{ $login_url }}" target="_blank" rel="noopener">{{ $login_url }}</a></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="p-3 rounded" style="background:#fff5eb;border:1px solid #fed7aa;">
            <h6 class="text-uppercase text-muted mb-2">Quick actions</h6>
            <a href="{{ route('corporate.password') }}" class="btn btn-primary btn-block btn-sm"><i class="fas fa-key mr-1"></i> Reset password</a>
        </div>
    </div>
</div>
@endsection
