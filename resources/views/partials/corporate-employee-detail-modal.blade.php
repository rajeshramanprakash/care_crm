@php
    $corp = $employee->relationLoaded('corporate') ? $employee->corporate : ($corporate ?? $employee->corporate);
    $modalId = ($modalIdPrefix ?? 'employeeDetailModal') . $employee->id;
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="{{ $modalId }}Label">
                    {{ $employee->employee_name }} <small class="text-muted">(<code>{{ $employee->employee_id }}</code>)</small>
                </h5>
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-sm table-bordered mb-0">
                    <tbody>
                        <tr>
                            <th class="bg-light" style="width:38%;">Corporate</th>
                            <td>{{ $corp->corporate_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Corporate username</th>
                            <td><code>{{ $corp->username ?? '—' }}</code></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Created by (broker/insurer)</th>
                            <td>{{ $corp?->createdByLabel() ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Employee ID</th>
                            <td><code>{{ $employee->employee_id }}</code></td>
                        </tr>
                        <tr>
                            <th class="bg-light">Employee Name</th>
                            <td>{{ $employee->employee_name }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Date of Birth</th>
                            <td>{{ $employee->date_of_birth?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Phone Number</th>
                            <td>{{ $employee->phone_number ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Gender</th>
                            <td>{{ $employee->gender ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Email</th>
                            <td>{{ $employee->email ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Relationship</th>
                            <td>{{ $employee->relationship ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Issuance date</th>
                            <td>{{ $employee->issuance_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Last Working Date</th>
                            <td>{{ $employee->last_working_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">SI Limit</th>
                            <td>{{ $employee->si_limit ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Active From</th>
                            <td>{{ $employee->active_from?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Active To</th>
                            <td>{{ $employee->active_to?->format('d M Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Room Limit</th>
                            <td>{{ $employee->room_limit ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Policy Terms &amp; Conditions</th>
                            <td>
                                @if($employee->policy_terms_file)
                                    <a href="{{ $employee->policyTermsUrl() }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file mr-1"></i> View document
                                    </a>
                                @else
                                    <span class="text-muted">Not uploaded</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Login password</th>
                            <td class="small text-muted">Set by corporate (stored encrypted)</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Employee login URL</th>
                            <td class="small">
                                <a href="{{ url('/corporate/employee/login') }}" target="_blank" rel="noopener">{{ url('/corporate/employee/login') }}</a>
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Account status</th>
                            <td>
                                @if($employee->is_active)
                                    <span class="badge badge-success">Active</span>
                                @else
                                    <span class="badge badge-secondary">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="bg-light">Added on</th>
                            <td>{{ $employee->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="bg-light">Last updated</th>
                            <td>{{ $employee->updated_at?->format('d M Y, h:i A') ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
