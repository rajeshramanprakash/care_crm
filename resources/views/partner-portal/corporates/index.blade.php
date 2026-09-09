@php
    $routePrefix = $partner_type === 'broker' ? 'broker' : 'insurer';
@endphp

<div class="mb-3 p-3 rounded border" style="background:#fff5eb;border-color:#fed7aa!important;">
    <strong><i class="fas fa-link mr-1"></i> Corporate login link</strong> (same for all corporates you create)
    <div class="input-group mt-2">
        <input type="text" class="form-control form-control-sm" id="corporateLoginUrl" readonly value="{{ $login_url }}">
        <div class="input-group-append">
            <button type="button" class="btn btn-sm btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('corporateLoginUrl').value); this.textContent='Copied!';">Copy</button>
        </div>
    </div>
    <small class="text-muted d-block mt-1">Share this link with the corporate user along with the username and password you set for them.</small>
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Corporate accounts</h5>
    <a href="{{ route($routePrefix.'.corporates.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Add corporate
    </a>
</div>

@if($items->isEmpty())
    <p class="text-muted mb-0">No corporate accounts yet. Create one and share the login link above.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>Corporate name</th>
                    <th>Username</th>
                    <th>Employees</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td><strong>{{ $item->corporate_name }}</strong></td>
                        <td><code>{{ $item->username }}</code></td>
                        <td class="text-center">
                            <a href="{{ route($routePrefix.'.corporates.employees.index', $item) }}" class="btn btn-sm btn-outline-secondary py-0 px-2">
                                {{ $item->employees_count ?? 0 }}
                            </a>
                        </td>
                        <td>
                            @if($item->is_active)
                                <span class="badge badge-success">Active</span>
                            @else
                                <span class="badge badge-secondary">Inactive</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            <a href="{{ route($routePrefix.'.corporates.edit', $item) }}" class="btn btn-xs btn-info btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="{{ route($routePrefix.'.corporates.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this corporate account?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
