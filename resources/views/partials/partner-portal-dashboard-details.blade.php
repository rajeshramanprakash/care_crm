@php
    $portalTitle = ($portal_type ?? 'insurer') === 'broker' ? 'Broker' : 'Insurer';
    $companyDocs = is_array($user->company_documents ?? null) ? array_values(array_filter($user->company_documents)) : [];
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0"><i class="fas fa-user-circle text-primary mr-2" style="color:#ea8a2b!important;"></i> Welcome, {{ $user->name }}</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">Your {{ $portalTitle }} account details as registered by Carelix admin.</p>

                <h6 class="text-uppercase text-muted small font-weight-bold mb-3">Profile</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless mb-0 partner-details-table">
                        <tbody>
                            <tr>
                                <th scope="row" class="text-muted" style="width:38%;">Full name</th>
                                <td>{{ $user->name ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">Company name</th>
                                <td>{{ $user->company_name ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">Username</th>
                                <td><code>{{ $user->username }}</code></td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">Email</th>
                                <td>
                                    @if($user->email)
                                        <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">Mobile no.</th>
                                <td>{{ $user->mobile ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th scope="row" class="text-muted">Account status</th>
                                <td>
                                    @if($user->is_active)
                                        <span class="badge badge-success">Active</span>
                                    @else
                                        <span class="badge badge-secondary">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h6 class="mb-0"><i class="fas fa-folder-open mr-2" style="color:#ea8a2b;"></i> Documents (from admin)</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <strong class="d-block mb-2">MOU</strong>
                    @if($user->mou_file)
                        <a href="{{ asset('storage/'.$user->mou_file) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-file-pdf mr-1"></i> View MOU (PDF)
                        </a>
                    @else
                        <span class="text-muted">Not uploaded yet.</span>
                    @endif
                </div>

                <div>
                    <strong class="d-block mb-2">Documents of company</strong>
                    @if(count($companyDocs) > 0)
                        <ul class="list-group list-group-flush border rounded">
                            @foreach($companyDocs as $docPath)
                                @php
                                    $ext = strtolower(pathinfo($docPath, PATHINFO_EXTENSION));
                                    $icon = $ext === 'pdf' ? 'fa-file-pdf text-danger' : 'fa-file-image text-primary';
                                @endphp
                                <li class="list-group-item d-flex align-items-center justify-content-between py-2">
                                    <span><i class="fas {{ $icon }} mr-2"></i>{{ basename($docPath) }}</span>
                                    <a href="{{ asset('storage/'.$docPath) }}" target="_blank" rel="noopener" class="btn btn-xs btn-outline-secondary btn-sm">View</a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <span class="text-muted">No company documents uploaded yet.</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light border-0" style="background:var(--carelix-primary-soft,#fff5eb)!important;border:1px solid #fed7aa!important;">
            <div class="card-body">
                <h6 class="text-uppercase text-muted mb-3">Quick actions</h6>
                <a href="{{ ($portal_type ?? 'insurer') === 'broker' ? route('broker.password') : route('insurer.password') }}" class="btn btn-primary btn-block btn-sm mb-2">
                    <i class="fas fa-key mr-1"></i> Reset password
                </a>
                <p class="small text-muted mb-0 mt-3">
                    <i class="fas fa-info-circle mr-1"></i>
                    To update profile or documents, contact your Carelix administrator.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    .partner-details-table th { font-weight: 600; vertical-align: top; padding-top: 0.35rem; padding-bottom: 0.35rem; }
    .partner-details-table td { vertical-align: top; padding-top: 0.35rem; padding-bottom: 0.35rem; }
</style>
