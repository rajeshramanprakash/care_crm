@extends('admin.layouts.app')

@section('title', 'Lead Details')

@section('header-css')
<style>
    .lead-details-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0,0,0,0.08);
    }

    .lead-details-card .card-header {
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        padding: 20px;
    }

    .lead-details-card .card-body {
        padding: 30px;
    }

    .detail-group {
        margin-bottom: 1.5rem;
    }

    .detail-label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 0.5rem;
    }

    .detail-value {
        color: #212529;
        font-size: 1rem;
    }

    .badge {
        padding: 8px 15px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .back-btn {
        margin-top: 20px;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="m-0">Lead Details</h1>
                <a href="{{ route('admin.leads.index') }}" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Leads
                </a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="card lead-details-card">
        <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-group">
                                <div class="detail-label">Lead No</div>
                                <div class="detail-value">{{ $lead->id }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Date</div>
                                <div class="detail-value">{{ date('d-m-Y', strtotime($lead->date)) }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Executive</div>
                                <div class="detail-value">{{ $lead->executive ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Customer Name</div>
                                <div class="detail-value">{{ $lead->customer_name ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Patient Name</div>
                                <div class="detail-value">{{ $lead->patient_name ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Patient Gender</div>
                                <div class="detail-value">
                                    @if($lead->patient_gender)
                                        <span class="badge bg-info">{{ ucfirst($lead->patient_gender) }}</span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Age</div>
                                <div class="detail-value">{{ $lead->age ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Contact Type</div>
                                <div class="detail-value">{{ ucfirst($lead->contact_type) ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Contact No</div>
                                <div class="detail-value">{{ $lead->contact_no ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Last Call Status</div>
                                <div class="detail-value">
                                    @if($lead->last_call_status)
                                        @php
                                            $callStatusClass = match(strtolower($lead->last_call_status)) {
                                                'answered' => 'bg-success',
                                                'busy' => 'bg-warning',
                                                'no answer', 'noanswer' => 'bg-danger',
                                                'failed' => 'bg-secondary',
                                                default => 'bg-info'
                                            };
                                            $callStatusText = match(strtolower($lead->last_call_status)) {
                                                'answered' => 'Answered',
                                                'busy' => 'Busy',
                                                'no answer', 'noanswer' => 'Missed',
                                                'failed' => 'Failed',
                                                default => ucfirst($lead->last_call_status)
                                            };
                                        @endphp
                                        <span class="badge {{ $callStatusClass }}">
                                            {{ $callStatusText }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-group">
                                <div class="detail-label">Location</div>
                                <div class="detail-value">{{ $lead->location ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Lead Source</div>
                                <div class="detail-value">{{ strtoupper($lead->lead_source) ?? '-' }}</div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Query</div>
                                <div class="detail-value">{{ ucfirst($lead->query) ?? '-' }}</div>
                            </div>
                            @if($lead->query_remarks)
                            <div class="detail-group">
                                <div class="detail-label">Query Remarks</div>
                                <div class="detail-value">{{ $lead->query_remarks }}</div>
                            </div>
                            @endif
                            <div class="detail-group">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    @php
                                        $statusClass = match($lead->status) {
                                            'follow-up' => 'bg-info',
                                            'future prospect' => 'bg-warning',
                                            'prospect' => 'bg-success',
                                            'no response' => 'bg-secondary',
                                            'price issue' => 'bg-secondary',
                                            'duplicate' => 'bg-danger',
                                            'spam' => 'bg-dark',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">
                                        {{ ucfirst($lead->status) ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            @if($lead->status_remarks)
                            <div class="detail-group">
                                <div class="detail-label">Status Remarks</div>
                                <div class="detail-value">{{ $lead->status_remarks }}</div>
                            </div>
                            @endif
                            <div class="detail-group">
                                <div class="detail-label">Stage</div>
                                <div class="detail-value">
                                    @php
                                        $stageClass = match($lead->stage) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-warning',
                                            'closed' => 'bg-danger',
                                            'profile required' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                    @endphp
                                    <span class="badge {{ $stageClass }}">
                                        {{ ucfirst($lead->stage) ?? '-' }}
                                    </span>
                                </div>
                            </div>
                            @if($lead->status === 'future prospect' && $lead->future_prospect_date)
                            <div class="detail-group">
                                <div class="detail-label">Future Contact Date</div>
                                <div class="detail-value">{{ date('d-m-Y', strtotime($lead->future_prospect_date)) }}</div>
                            </div>
                            @endif
                            @if($lead->status === 'prospect' && $lead->prospect_close_rate !== null)
                            <div class="detail-group">
                                <div class="detail-label">Close Rate</div>
                                <div class="detail-value">{{ $lead->prospect_close_rate }}%</div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
