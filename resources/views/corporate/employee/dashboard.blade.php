@extends('corporate.employee.layouts.app')
@section('title', 'Dashboard')
@section('page_title', 'My profile')

@section('content')
<h4 class="mb-3">Welcome, {{ $employee->employee_name }}</h4>
<p class="text-muted">{{ $employee->employeeLoginHint() }}</p>

<table class="table table-sm table-bordered">
    <tr><th class="bg-light" style="width:35%;">Employee ID</th><td><code>{{ $employee->employee_id }}</code></td></tr>
    <tr><th class="bg-light">Corporate</th><td>{{ $employee->corporate->corporate_name ?? '—' }}</td></tr>
    <tr><th class="bg-light">Date of Birth</th><td>{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th class="bg-light">Phone</th><td>{{ $employee->phone_number ?? '—' }}</td></tr>
    <tr><th class="bg-light">Gender</th><td>{{ $employee->gender ?? '—' }}</td></tr>
    <tr><th class="bg-light">Email</th><td>{{ $employee->email ?? '—' }}</td></tr>
    <tr><th class="bg-light">Relationship</th><td>{{ $employee->relationship ?? '—' }}</td></tr>
    <tr><th class="bg-light">Issuance date</th><td>{{ $employee->issuance_date?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th class="bg-light">Last Working Date</th><td>{{ $employee->last_working_date?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th class="bg-light">SI Limit</th><td>{{ $employee->si_limit ?? '—' }}</td></tr>
    <tr><th class="bg-light">Active From</th><td>{{ $employee->active_from?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th class="bg-light">Active To</th><td>{{ $employee->active_to?->format('d M Y') ?? '—' }}</td></tr>
    <tr><th class="bg-light">Room Limit</th><td>{{ $employee->room_limit ?? '—' }}</td></tr>
    <tr><th class="bg-light">Policy Terms</th>
        <td>
            @if($employee->policy_terms_file)
                <a href="{{ $employee->policyTermsUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View document</a>
            @else
                —
            @endif
        </td>
    </tr>
</table>
@endsection
