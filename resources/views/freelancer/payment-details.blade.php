@extends('freelancer.layouts.app')
@section('title', 'Payment Details | Freelancer')

@section('header-css')
<style>
    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        text-align: center;
    }
    .stat-card h4 {
        font-size: 1rem;
        margin-bottom: 10px;
        opacity: 0.9;
    }
    .stat-card h2 {
        font-size: 2rem;
        font-weight: bold;
        margin: 0;
    }
    .badge-payment-completed {
        background-color: #28a745;
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
    .badge-payment-pending {
        background-color: #ffc107;
        color: #000;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.85rem;
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Payment Details</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button type="button" class="btn btn-info btn-lg" onclick="viewStatement()" title="View Statement (PDF)">
                        <i class="fas fa-file-pdf"></i> Statement
                    </button>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <!-- Payment Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h4>Total Payment</h4>
                        <h2>₹{{ number_format($totalPayment ?? 0, 2) }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                        <h4>Received Payment</h4>
                        <h2>₹{{ number_format($completedPayments ?? 0, 2) }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                        <h4>Pending Payment</h4>
                        <h2>₹{{ number_format($pendingPayment ?? 0, 2) }}</h2>
                    </div>
                </div>
            </div>

            <!-- Leads Payment Status -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary">
                            <h3 class="card-title text-white"><i class="fas fa-list mr-2"></i>Leads Payment Status</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Lead ID</th>
                                            <th>Customer Name</th>
                                            <th>Contact No.</th>
                                            <th>Location</th>
                                            <th>Query</th>
                                            <th>Duty Hours</th>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Payment Amount</th>
                                            <th>Payment Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($leadsWithPayments as $lead)
                                        <tr>
                                            <td>{{ $lead['lead_id'] }}</td>
                                            <td>{{ $lead['customer_name'] }}</td>
                                            <td>{{ $lead['contact_no'] }}</td>
                                            <td>{{ $lead['location'] }}</td>
                                            <td>{{ $lead['query'] }}</td>
                                            <td>
                                                @if($lead['duty_hours'])
                                                    {{ $lead['duty_hours'] == '12hr' ? '12 Hours' : '24 Hours' }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($lead['deployment_from_date'])
                                                    {{ \Carbon\Carbon::parse($lead['deployment_from_date'])->format('d M Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>
                                                @if($lead['deployment_to_date'])
                                                    {{ \Carbon\Carbon::parse($lead['deployment_to_date'])->format('d M Y') }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>₹{{ number_format($lead['vendor_payment'], 2) }}</td>
                                            <td>
                                                @if($lead['payment_status'] == 'Completed')
                                                    <span class="badge-payment-completed">Completed</span>
                                                @else
                                                    <span class="badge-payment-pending">Pending</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <p class="text-muted">No payment records found.</p>
                                            </td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            @if(isset($freelancerPayments) && $freelancerPayments->count() > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-info">
                            <h3 class="card-title text-white"><i class="fas fa-history mr-2"></i>Payment History</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Lead ID</th>
                                            <th>Amount</th>
                                            <th>Payment Method</th>
                                            <th>Transaction ID</th>
                                            <th>Reference Number</th>
                                            <th>Description</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($freelancerPayments as $payment)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}</td>
                                            <td>
                                                @if(isset($payment->allocated_leads) && count($payment->allocated_leads) > 0)
                                                    {{ implode(', ', $payment->allocated_leads) }}
                                                @else
                                                    N/A
                                                @endif
                                            </td>
                                            <td>₹{{ number_format($payment->amount, 2) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                            <td>{{ $payment->transaction_id ?? 'N/A' }}</td>
                                            <td>{{ $payment->reference_number ?? 'N/A' }}</td>
                                            <td>{{ $payment->description ?? 'N/A' }}</td>
                                            <td>
                                                @if($payment->status == 'completed')
                                                    <span class="badge-payment-completed">Completed</span>
                                                @elseif($payment->status == 'pending')
                                                    <span class="badge-payment-pending">Pending</span>
                                                @else
                                                    <span class="badge bg-danger">Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>

<!-- Statement Modal (same as admin: view + download PDF) -->
<div class="modal fade" id="statementModal" tabindex="-1" aria-labelledby="statementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: #fe992e; color: white;">
                <h5 class="modal-title" id="statementModalLabel">
                    <i class="fas fa-file-pdf"></i> Statement
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="statementFrame" style="width:100%; height: 75vh; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <a id="statementDownloadBtn" href="#" target="_blank" class="btn btn-danger">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('footer-script')
<script>
function viewStatement() {
    var statementUrl = "{{ route('freelancer.statement') }}";
    var downloadUrl = statementUrl + "?print=1";
    document.getElementById('statementFrame').src = statementUrl;
    document.getElementById('statementDownloadBtn').href = downloadUrl;
    var modal = new bootstrap.Modal(document.getElementById('statementModal'));
    modal.show();
}
</script>
@endsection

