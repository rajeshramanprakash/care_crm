@extends('vendor.layouts.app')
@section('title', $page_heading ?? 'Payment Details')

@section('main')
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <h1 class="m-0">{{ $page_heading ?? 'Payment Details' }}</h1>
                <button type="button" class="btn btn-info btn-lg" onclick="viewStatement()" title="View Statement (PDF)">
                    <i class="fas fa-file-pdf"></i> Statement
                </button>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3>₹{{ number_format($totalPayment ?? 0, 2) }}</h3>
                            <p>Total Payment</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3>₹{{ number_format($completedPayments ?? 0, 2) }}</h3>
                            <p>Received</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3>₹{{ number_format($pendingPayment ?? 0, 2) }}</h3>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 class="card-title">Leads Payment Status</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Customer Name</th>
                                    <th>Contact No.</th>
                                    <th>Location</th>
                                    <th>Query</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Payment Amount</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($leadsWithPayments ?? [] as $lead)
                                <tr>
                                    <td>{{ $lead['lead_id'] ?? 'N/A' }}</td>
                                    <td>{{ $lead['customer_name'] ?? 'N/A' }}</td>
                                    <td>{{ $lead['contact_no'] ?? '—' }}</td>
                                    <td>{{ $lead['location'] ?? '—' }}</td>
                                    <td>{{ $lead['query'] ?? '—' }}</td>
                                    <td>{{ isset($lead['deployment_from_date']) && $lead['deployment_from_date'] ? \Carbon\Carbon::parse($lead['deployment_from_date'])->format('d M Y') : '—' }}</td>
                                    <td>{{ isset($lead['deployment_to_date']) && $lead['deployment_to_date'] ? \Carbon\Carbon::parse($lead['deployment_to_date'])->format('d M Y') : '—' }}</td>
                                    <td>₹{{ number_format($lead['vendor_payment'] ?? 0, 2) }}</td>
                                    <td>
                                        @if(($lead['payment_status'] ?? '') == 'Completed')
                                            <span class="badge bg-success">Completed</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="9" class="text-center py-4 text-muted">No payment records found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if(isset($vendorPayments) && $vendorPayments->count() > 0)
            <div class="card mt-4">
                <div class="card-header bg-secondary"><h3 class="card-title text-white">Payment History</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Transaction ID</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($vendorPayments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : '—' }}</td>
                                    <td>₹{{ number_format($payment->amount ?? 0, 2) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method ?? '—')) }}</td>
                                    <td>{{ $payment->transaction_id ?? '—' }}</td>
                                    <td><span class="badge badge-{{ $payment->status == 'completed' ? 'success' : 'warning' }}">{{ $payment->status ?? 'N/A' }}</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </section>
</div>

<!-- Statement Modal (view + download PDF) -->
<div class="modal fade" id="statementModal" tabindex="-1" aria-labelledby="statementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: #dc3545; color: white;">
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
    var statementUrl = "{{ route('vendor.statement') }}";
    var downloadUrl = statementUrl + "?print=1";
    document.getElementById('statementFrame').src = statementUrl;
    document.getElementById('statementDownloadBtn').href = downloadUrl;
    var modal = new bootstrap.Modal(document.getElementById('statementModal'));
    modal.show();
}
</script>
@endsection
