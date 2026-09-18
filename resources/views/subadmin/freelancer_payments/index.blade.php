@extends('admin.layouts.app')

@section('title', 'Freelancer Payment History | Admin')

@section('header-css')
<style>
    .search-section {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid #dee2e6;
        padding: 20px;
        margin-bottom: 20px;
    }

    .search-input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .search-input:focus {
        outline: none;
        border-color: #80bdff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
    }

    .freelancer-table {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        border: 1px solid #dee2e6;
        overflow: hidden;
    }

    .table-header {
        background: #fe992e;
        color: white;
        padding: 20px;
    }

    .table-title {
        color: white;
        font-size: 18px;
        font-weight: 600;
        margin: 0;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }

    .table th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        font-size: 12px;
        padding: 12px 16px;
        text-align: left;
        border-bottom: 2px solid #dee2e6;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .table td {
        padding: 12px 16px;
        border-bottom: 1px solid #dee2e6;
        color: #212529;
        font-size: 13px;
        vertical-align: middle;
    }

    .table tr:hover {
        background: #f8f9fa;
    }

    .freelancer-info {
        display: flex;
        flex-direction: column;
    }

    .freelancer-name {
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
    }

    .freelancer-contact {
        font-size: 12px;
        color: #6c757d;
    }

    .stats-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
    }

    .stat-label {
        color: #6c757d;
        font-weight: 500;
    }

    .stat-value {
        font-weight: 600;
        color: #212529;
    }

    .action-buttons {
        display: flex;
        gap: 8px;
        justify-content: center;
    }

    .btn-freelancer {
        padding: 8px 12px;
        border: none;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
        text-align: center;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        min-width: 80px;
    }

    .btn-history {
        background: #17a2b8;
        color: white;
    }

    .btn-history:hover {
        background: #138496;
        color: white;
    }

    .btn-add-payment {
        background: #28a745;
        color: white;
    }

    .btn-add-payment:hover {
        background: #218838;
        color: white;
    }

    .btn-payment-details {
        background: #6f42c1;
        color: white;
    }

    .btn-payment-details:hover {
        background: #5a32a3;
        color: white;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .badge-success {
        background: #d4edda;
        color: #155724;
    }

    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }

    .badge-danger {
        background: #f8d7da;
        color: #721c24;
    }

    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    .btn-primary {
        background: #007bff;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease-in-out;
    }

    .btn-primary:hover {
        background: #0056b3;
        color: white;
    }

    .alert {
        padding: 12px 16px;
        border-radius: 4px;
        margin-bottom: 20px;
        font-size: 14px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 48px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    @media (max-width: 768px) {
        .action-buttons {
            flex-direction: column;
            gap: 4px;
        }

        .btn-freelancer {
            min-width: 60px;
            font-size: 11px;
            padding: 6px 8px;
        }

        .table-responsive {
            font-size: 12px;
        }
    }
</style>
@endsection

@section('main')
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Freelancer Payment History</h1>
            <div>
                <a href="{{ route('subadmin.freelancer_payments.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-list"></i> All Payments
                </a>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <div class="row">
                <div class="col-md-6">
                    <label for="freelancerSearch" class="form-label fw-bold mb-2">Search Freelancers</label>
                    <input type="text" id="freelancerSearch" class="search-input" placeholder="Search by freelancer name, contact number...">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="clearSearch()">
                            <i class="fas fa-times"></i> Clear
                        </button>
                        <span class="text-muted align-self-center" id="searchResults">
                            Showing all freelancers
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Freelancers Table -->
        <div class="freelancer-table">
            <div class="table-header">
                <h3 class="table-title">Freelancer Payment History</h3>
            </div>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table" id="freelancersTable">
                    <thead>
                        <tr>
                            <th>Freelancer</th>
                            <th>Contact</th>
                            <th>Total Paid</th>
                            <th>Payments Count</th>
                            <th>Last Payment</th>
                            <th>Statement</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($freelancers as $freelancer)
                        <tr class="freelancer-row" data-freelancer-id="{{ $freelancer->id }}" data-freelancer-name="{{ strtolower($freelancer->name) }}" data-freelancer-contact="{{ strtolower($freelancer->contact_no ?? '') }}">
                            <td>
                                <div class="freelancer-info">
                                    <div class="freelancer-name">{{ $freelancer->name }}</div>
                                    <div class="freelancer-contact">{{ $freelancer->contact_no ?: 'No Contact' }}</div>
                                </div>
                            </td>
                            <td>{{ $freelancer->contact_no ?: 'No Contact' }}</td>
                            <td>
                                <div class="stats-info">
                                    <div class="stat-row">
                                        <span class="stat-label">Total:</span>
                                        <span class="stat-value">₹{{ number_format($freelancer->total_paid ?? 0, 2) }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="stats-info">
                                    <div class="stat-row">
                                        <span class="stat-label">Count:</span>
                                        <span class="stat-value">{{ $freelancer->payments->count() }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="stats-info">
                                    <div class="stat-row">
                                        <span class="stat-label">Last:</span>
                                        <span class="stat-value">{{ $freelancer->last_payment_date ? $freelancer->last_payment_date->format('d-M-Y') : 'No Payments' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <a href="javascript:void(0)" onclick="viewFreelancerStatement({{ $freelancer->id }}, this.getAttribute('data-freelancer-name'))" data-freelancer-name="{{ e($freelancer->name) }}" class="btn-freelancer btn-history" title="View Statement (PDF)">
                                    <i class="fas fa-file-pdf"></i>
                                    PDF
                                </a>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-freelancer btn-history" onclick="viewFreelancerHistory({{ $freelancer->id }}, '{{ $freelancer->name }}')" title="View Payment History">
                                        <i class="fas fa-history"></i>
                                        History
                                    </button>
                                    <button class="btn-freelancer btn-add-payment" onclick="addFreelancerPayment({{ $freelancer->id }}, '{{ $freelancer->name }}')" title="Add New Payment">
                                        <i class="fas fa-plus"></i>
                                        Add
                                    </button>
                                    <button class="btn-freelancer btn-payment-details" onclick="viewFreelancerPaymentDetails({{ $freelancer->id }}, '{{ $freelancer->name }}')" title="View Payment Details">
                                        <i class="fas fa-rupee-sign"></i>
                                        Details
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h4>No Freelancers Found</h4>
                                    <p>There are no freelancers with verified payments available to display payment history.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Select Leads for Payment Modal -->
<div class="modal fade" id="selectLeadsModal" tabindex="-1" aria-labelledby="selectLeadsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: #fe992e; color: white;">
                <h5 class="modal-title" id="selectLeadsModalLabel"><i class="fas fa-list-check"></i> Select Leads for Payment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="selectLeadsFreelancerName" class="mb-3"></h6>
                <p class="text-muted small">Select the lead(s) for which you are making this payment. Total amount will be calculated automatically.</p>
                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                    <table class="table table-sm">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th><input type="checkbox" id="selectAllLeads" title="Select all"></th>
                                <th>Lead ID</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>From – To Date</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody id="selectLeadsBody">
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 p-3 bg-light rounded">
                    <strong>Total Selected Amount:</strong>
                    <span id="selectedLeadsTotal" class="fs-5 text-success">₹ 0.00</span>
                </div>
                <div class="mt-2">
                    <button type="button" class="btn btn-primary" id="btnProceedToPayment" disabled>
                        <i class="fas fa-arrow-right"></i> Proceed to Payment Form
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment History Modal -->
<div class="modal fade" id="paymentHistoryModal" tabindex="-1" aria-labelledby="paymentHistoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="paymentHistoryModalLabel">Payment History</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 id="freelancerNameDisplay" class="mb-3"></h6>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th>Screenshot</th>
                                <th>Invoice</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="paymentHistoryBody">
                            <!-- Payment history will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Payment Modal -->
<div class="modal fade" id="addPaymentModal" tabindex="-1" aria-labelledby="addPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPaymentModalLabel">Add New Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addPaymentForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="payment_job_request_id" name="job_request_id">
                <div id="addPaymentDeploymentIdsContainer"></div>
                <div class="modal-body">
                    <h6 id="addPaymentFreelancerName" class="mb-3"></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Amount (₹) *</label>
                                <input type="number" class="form-control" name="amount" id="add_payment_amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-control" name="payment_method" id="payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Payment Date *</label>
                                <input type="date" class="form-control" name="payment_date" max="{{ date('Y-m-d') }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3" id="transaction_id_group">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" name="transaction_id" id="transaction_id" placeholder="Enter transaction ID">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3" id="reference_number_group">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference_number" id="reference_number" placeholder="Enter reference number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Payment Screenshot</label>
                                <input type="file" class="form-control" name="screenshot" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Enter payment description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitPaymentBtn">
                        <span class="btn-text">Record Payment</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="loading-spinner"></span> Recording...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Payment Modal -->
<div class="modal fade" id="viewPaymentModal" tabindex="-1" aria-labelledby="viewPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewPaymentModalLabel">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewPaymentBody">
                <!-- Payment details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-labelledby="editPaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPaymentModalLabel">Edit Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editPaymentForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" id="edit_payment_id" name="payment_id">
                <input type="hidden" id="edit_job_request_id" name="job_request_id">
                <div class="modal-body">
                    <h6 id="editPaymentFreelancerName" class="mb-3"></h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Amount (₹) *</label>
                                <input type="number" class="form-control" name="amount" id="edit_amount" step="0.01" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Payment Method *</label>
                                <select class="form-control" name="payment_method" id="edit_payment_method" required>
                                    <option value="">Select Method</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="upi">UPI</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Payment Date *</label>
                                <input type="date" class="form-control" name="payment_date" id="edit_payment_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Status *</label>
                                <select class="form-control" name="status" id="edit_status" required>
                                    <option value="pending">Pending</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3" id="edit_transaction_id_group">
                                <label class="form-label">Transaction ID</label>
                                <input type="text" class="form-control" name="transaction_id" id="edit_transaction_id" placeholder="Enter transaction ID">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3" id="edit_reference_number_group">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" name="reference_number" id="edit_reference_number" placeholder="Enter reference number">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">New Screenshot</label>
                                <input type="file" class="form-control" name="screenshot" accept="image/*">
                                <small class="text-muted">Leave empty to keep existing screenshot</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label class="form-label">Current Screenshot</label>
                                <div id="currentScreenshotDisplay">
                                    <!-- Current screenshot will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3" placeholder="Enter payment description"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="updatePaymentBtn">
                        <span class="btn-text">Update Payment</span>
                        <span class="btn-loading" style="display: none;">
                            <span class="loading-spinner"></span> Updating...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Screenshot View Modal -->
<div class="modal fade" id="screenshotModal" tabindex="-1" aria-labelledby="screenshotModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="screenshotModalLabel">Payment Screenshot</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="screenshotImage" src="" alt="Payment Screenshot" style="max-width: 100%; height: auto;">
            </div>
        </div>
    </div>
</div>

<!-- Invoice View Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" aria-labelledby="invoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceModalLabel"><i class="fas fa-file-invoice"></i> Payment Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="invoiceFrame" style="width:100%; height: 75vh; border: none;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- Freelancer Payment Details Modal -->
<div class="modal fade" id="freelancerPaymentDetailsModal" tabindex="-1" aria-labelledby="freelancerPaymentDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="freelancerPaymentDetailsModalLabel">
                    <i class="fas fa-rupee-sign"></i> Freelancer Payment Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="freelancerPaymentDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Freelancer Statement Modal (PDF view + Download) -->
<div class="modal fade" id="freelancerStatementModal" tabindex="-1" aria-labelledby="freelancerStatementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background: #fe992e; color: white;">
                <h5 class="modal-title" id="freelancerStatementModalLabel">
                    <i class="fas fa-file-pdf"></i> Statement – <span id="freelancerStatementName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="freelancerStatementFrame" style="width:100%; height: 75vh; border: none;"></iframe>
            </div>
            <div class="modal-footer">
                <a id="freelancerStatementDownloadBtn" href="#" target="_blank" class="btn btn-danger">
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
$(document).ready(function() {
    // Set default payment date to today
    $('input[name="payment_date"]').val('{{ date("Y-m-d") }}');

    // Payment method change handler for add payment modal
    $('#payment_method').on('change', function() {
        togglePaymentFields($(this).val(), 'add');
    });

    // Payment method change handler for edit payment modal
    $('#edit_payment_method').on('change', function() {
        togglePaymentFields($(this).val(), 'edit');
    });

    // Payment form submission
    $('#addPaymentForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = $('#submitPaymentBtn');
        const btnText = submitBtn.find('.btn-text');
        const btnLoading = submitBtn.find('.btn-loading');

        // Show loading state
        btnText.hide();
        btnLoading.show();
        submitBtn.prop('disabled', true);

        $.ajax({
            url: '{{ route("subadmin.freelancer_payments.store") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    $('#addPaymentModal').modal('hide');
                    $('#addPaymentForm')[0].reset();
                    $('input[name="payment_date"]').val('{{ date("Y-m-d") }}');

                    // Refresh the freelancer card stats
                    const freelancerId = $('#payment_job_request_id').val();
                    refreshFreelancerStats(freelancerId);

                    // Refresh freelancer payment details modal if it's open
                    refreshFreelancerPaymentDetailsModal(freelancerId);
                } else {
                    showAlert('error', response.message || 'Error recording payment');
                }
            },
            error: function(xhr) {
                let message = 'Error recording payment';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                showAlert('error', message);
            },
            complete: function() {
                // Reset button state
                btnText.show();
                btnLoading.hide();
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Edit payment form submission
    $('#editPaymentForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const paymentId = $('#edit_payment_id').val();
        const submitBtn = $('#updatePaymentBtn');
        const btnText = submitBtn.find('.btn-text');
        const btnLoading = submitBtn.find('.btn-loading');

        // Show loading state
        btnText.hide();
        btnLoading.show();
        submitBtn.prop('disabled', true);

        $.ajax({
            url: `/subadmin/freelancer-payments/${paymentId}`,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    $('#editPaymentModal').modal('hide');

                    // Refresh the payment history modal
                    const freelancerId = $('#edit_job_request_id').val();
                    const freelancerName = $('#editPaymentFreelancerName').text();
                    if (freelancerId && freelancerName) {
                        viewFreelancerHistory(freelancerId, freelancerName);
                        refreshFreelancerStats(freelancerId);
                        refreshFreelancerPaymentDetailsModal(freelancerId);
                    }
                } else {
                    showAlert('error', response.message || 'Error updating payment');
                }
            },
            error: function(xhr) {
                let message = 'Error updating payment';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                showAlert('error', message);
            },
            complete: function() {
                // Reset button state
                btnText.show();
                btnLoading.hide();
                submitBtn.prop('disabled', false);
            }
        });
    });
});

// View freelancer payment history
function viewFreelancerHistory(freelancerId, freelancerName) {
    $('#freelancerNameDisplay').text(freelancerName);
    $('#paymentHistoryBody').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
    $('#paymentHistoryModal').modal('show');

    $.ajax({
        url: `/subadmin/freelancer-payments/freelancer/${freelancerId}`,
        type: 'GET',
        success: function(response) {
            if (response.payments && response.payments.length > 0) {
                let html = '';
                response.payments.forEach(function(payment) {
                    html += `
                        <tr>
                            <td>${payment.formatted_payment_date}</td>
                            <td><strong>₹${parseFloat(payment.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                            <td><span class="badge badge-info">${payment.payment_method_text}</span></td>
                            <td>${payment.transaction_id || '-'}</td>
                            <td><span class="${payment.status_badge}">${payment.status}</span></td>
                            <td>
                                ${payment.screenshot ?
                                    `<img src="/storage/${payment.screenshot}" alt="Screenshot" class="screenshot-preview" onclick="viewScreenshot('/storage/${payment.screenshot}')" style="width: 30px; height: 30px; border-radius: 4px; cursor: pointer;">` :
                                    '-'
                                }
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="viewInvoice(${payment.id})" title="View Invoice"><i class="fas fa-file-invoice"></i></button>
                                    <a href="/subadmin/freelancer-payments/${payment.id}/invoice?print=1" target="_blank" class="btn btn-outline-danger btn-sm" title="Download PDF"><i class="fas fa-file-pdf"></i></a>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary" onclick="viewPayment(${payment.id})" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-warning" onclick="editPayment(${payment.id})" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" onclick="deletePayment(${payment.id})" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                $('#paymentHistoryBody').html(html);
            } else {
                $('#paymentHistoryBody').html(`
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No payments found for this freelancer</p>
                            </div>
                        </td>
                    </tr>
                `);
            }
        },
        error: function() {
            $('#paymentHistoryBody').html(`
                <tr>
                    <td colspan="8" class="text-center text-danger">
                        Error loading payment history
                    </td>
                </tr>
            `);
        }
    });
}

// Add payment: first open Select Leads modal, then proceed to payment form
let currentAddFreelancerId = null;
let currentAddFreelancerName = null;

function addFreelancerPayment(freelancerId, freelancerName) {
    currentAddFreelancerId = freelancerId;
    currentAddFreelancerName = freelancerName;
    $('#selectLeadsFreelancerName').text(freelancerName);
    $('#selectLeadsBody').html('<tr><td colspan="6" class="text-center">Loading leads...</td></tr>');
    $('#selectedLeadsTotal').text('₹ 0.00');
    $('#btnProceedToPayment').prop('disabled', true).text('Proceed to Payment Form');
    $('#selectAllLeads').prop('checked', false);
    $('#selectLeadsModal').modal('show');

    fetch(`/subadmin/operation-leads/freelancer-payment-details/${freelancerId}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success || !data.leads || data.leads.length === 0) {
                $('#selectLeadsBody').html('<tr><td colspan="6" class="text-center text-muted">No verified leads found for this freelancer. You can still add payment without selecting leads.</td></tr>');
                $('#btnProceedToPayment').prop('disabled', false).off('click').on('click', function() {
                    proceedToPaymentForm([]);
                }).text('Proceed (no leads selected)');
                return;
            }
            let rows = '';
            data.leads.forEach(lead => {
                const fromDate = lead.deployment_from_date ? new Date(lead.deployment_from_date).toLocaleDateString('en-IN') : '-';
                const toDate = lead.deployment_to_date ? new Date(lead.deployment_to_date).toLocaleDateString('en-IN') : '-';
                const amt = parseFloat(lead.vendor_payment || 0).toFixed(2);
                rows += `<tr>
                    <td><input type="checkbox" class="lead-checkbox" data-amount="${amt}" data-id="${lead.deployment_detail_id}"></td>
                    <td>#${lead.lead_id}</td>
                    <td>${lead.customer_name || '-'}</td>
                    <td>${lead.service || '-'}</td>
                    <td>${fromDate} – ${toDate}</td>
                    <td class="text-end">₹${parseFloat(amt).toLocaleString('en-IN', {minimumFractionDigits: 2})}</td>
                </tr>`;
            });
            $('#selectLeadsBody').html(rows);
            $('#btnProceedToPayment').prop('disabled', true).off('click').on('click', function() {
                const ids = [];
                $('#selectLeadsBody .lead-checkbox:checked').each(function() { ids.push(parseInt($(this).data('id'), 10)); });
                proceedToPaymentForm(ids);
            }).text('Proceed to Payment Form');
            $('#selectLeadsBody .lead-checkbox').on('change', updateSelectLeadsTotal);
            $('#selectAllLeads').off('change').on('change', function() {
                $('#selectLeadsBody .lead-checkbox').prop('checked', this.checked);
                updateSelectLeadsTotal();
            });
        })
        .catch(() => {
            $('#selectLeadsBody').html('<tr><td colspan="6" class="text-center text-danger">Error loading leads.</td></tr>');
            $('#btnProceedToPayment').prop('disabled', false).off('click').on('click', function() {
                proceedToPaymentForm([]);
            }).text('Proceed (no leads selected)');
        });
}

function updateSelectLeadsTotal() {
    let total = 0;
    $('#selectLeadsBody .lead-checkbox:checked').each(function() {
        total += parseFloat($(this).data('amount')) || 0;
    });
    $('#selectedLeadsTotal').text('₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2}));
    $('#btnProceedToPayment').prop('disabled', total <= 0);
}

function proceedToPaymentForm(deploymentDetailIds) {
    $('#selectLeadsModal').modal('hide');
    let total = 0;
    if (deploymentDetailIds.length) {
        $('#selectLeadsBody .lead-checkbox:checked').each(function() {
            total += parseFloat($(this).data('amount')) || 0;
        });
    }
    $('#addPaymentFreelancerName').text(currentAddFreelancerName);
    $('#payment_job_request_id').val(currentAddFreelancerId);
    $('#add_payment_amount').val(total > 0 ? total.toFixed(2) : '');
    $('#addPaymentDeploymentIdsContainer').empty();
    deploymentDetailIds.forEach(id => {
        $('#addPaymentDeploymentIdsContainer').append('<input type="hidden" name="deployment_detail_ids[]" value="' + id + '">');
    });
    $('#addPaymentForm')[0].reset();
    $('#payment_job_request_id').val(currentAddFreelancerId);
    $('#add_payment_amount').val(total > 0 ? total.toFixed(2) : '');
    deploymentDetailIds.forEach(id => {
        $('#addPaymentDeploymentIdsContainer').append('<input type="hidden" name="deployment_detail_ids[]" value="' + id + '">');
    });
    $('input[name="payment_date"]').val('{{ date("Y-m-d") }}');
    $('#transaction_id_group').hide();
    $('#reference_number_group').hide();
    $('#addPaymentModal').modal('show');
}

// View payment details
function viewPayment(id) {
    $.get(`/subadmin/freelancer-payments/${id}`, function(response) {
        const payment = response;
        const html = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Freelancer:</strong> ${payment.jobRequest.name}</p>
                    <p><strong>Contact:</strong> ${payment.jobRequest.contact_no}</p>
                    <p><strong>Amount:</strong> ₹${parseFloat(payment.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                    <p><strong>Payment Method:</strong> ${payment.payment_method_text}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Date:</strong> ${payment.formatted_payment_date}</p>
                    <p><strong>Status:</strong> <span class="${payment.status_badge}">${payment.status}</span></p>
                    <p><strong>Transaction ID:</strong> ${payment.transaction_id || '-'}</p>
                    <p><strong>Reference:</strong> ${payment.reference_number || '-'}</p>
                </div>
            </div>
            ${payment.description ? `<p><strong>Description:</strong> ${payment.description}</p>` : ''}
            ${payment.screenshot ? `
                <div class="mt-3">
                    <strong>Screenshot:</strong><br>
                    <img src="/storage/${payment.screenshot}" alt="Screenshot" style="max-width: 100%; height: auto; border-radius: 8px;">
                </div>
            ` : ''}
        `;

        $('#viewPaymentBody').html(html);
        $('#viewPaymentModal').modal('show');
    });
}

// Edit payment
function editPayment(id) {
    // Fetch payment details and populate edit form
    $.get(`/subadmin/freelancer-payments/${id}/edit`, function(response) {
        console.log('Edit payment response:', response); // Debug log
        const payment = response.payment;

        // Populate form fields
        $('#edit_payment_id').val(payment.id);
        $('#edit_job_request_id').val(payment.job_request_id);
        $('#edit_amount').val(payment.amount);
        $('#edit_payment_method').val(payment.payment_method);
        $('#edit_payment_date').val(payment.payment_date);
        $('#edit_status').val(payment.status);
        $('#edit_transaction_id').val(payment.transaction_id || '');
        $('#edit_reference_number').val(payment.reference_number || '');
        $('#edit_description').val(payment.description || '');
        $('#editPaymentFreelancerName').text(payment.jobRequest ? payment.jobRequest.name : 'Unknown Freelancer');

        // Display current screenshot if exists
        if (payment.screenshot) {
            $('#currentScreenshotDisplay').html(`
                <img src="/storage/${payment.screenshot}" alt="Current Screenshot"
                     style="max-width: 100px; height: auto; border-radius: 4px; cursor: pointer;"
                     onclick="viewScreenshot('/storage/${payment.screenshot}')">
                <br><small class="text-muted">Click to view full size</small>
            `);
        } else {
            $('#currentScreenshotDisplay').html('<span class="text-muted">No screenshot uploaded</span>');
        }

        // Show edit modal
        $('#editPaymentModal').modal('show');

        // Trigger field visibility based on selected payment method
        togglePaymentFields(payment.payment_method, 'edit');
    }).fail(function() {
        showAlert('error', 'Error loading payment details for editing');
    });
}

// Delete payment
function deletePayment(id) {
    if (!confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
        return;
    }

    $.ajax({
        url: `/subadmin/freelancer-payments/${id}`,
        type: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.status === 'success') {
                showAlert('success', response.message);
                // Refresh the payment history modal
                const freelancerName = $('#freelancerNameDisplay').text();
                if (freelancerName) {
                    // Get freelancer ID from the current payment history modal
                    const freelancerRow = $('.freelancer-row').filter(function() {
                        return $(this).find('.freelancer-name').text() === freelancerName;
                    });
                    const freelancerId = freelancerRow.data('freelancer-id');
                    if (freelancerId) {
                        viewFreelancerHistory(freelancerId, freelancerName);
                        refreshFreelancerStats(freelancerId);
                        refreshFreelancerPaymentDetailsModal(freelancerId);
                    }
                }
            } else {
                showAlert('error', response.message || 'Error deleting payment');
            }
        },
        error: function(xhr) {
            let message = 'Error deleting payment';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }
            showAlert('error', message);
        }
    });
}

// View screenshot
function viewScreenshot(src) {
    $('#screenshotImage').attr('src', src);
    $('#screenshotModal').modal('show');
}

// View freelancer payment invoice
function viewInvoice(paymentId) {
    document.getElementById('invoiceFrame').src = '/subadmin/freelancer-payments/' + paymentId + '/invoice';
    $('#invoiceModal').modal('show');
}

// View freelancer statement in modal (PDF style) with download button
function viewFreelancerStatement(freelancerId, freelancerName) {
    const statementUrl = '{{ route("subadmin.freelancer_payments.statement", ["freelancerId" => "__FID__"]) }}'.replace('__FID__', freelancerId);
    const downloadUrl = statementUrl + '?print=1';
    document.getElementById('freelancerStatementName').textContent = freelancerName || 'Freelancer';
    document.getElementById('freelancerStatementFrame').src = statementUrl;
    document.getElementById('freelancerStatementDownloadBtn').href = downloadUrl;
    $('#freelancerStatementModal').modal('show');
}

// Search functionality
$(document).ready(function() {
    $('#freelancerSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        const freelancerRows = $('.freelancer-row');
        let visibleCount = 0;

        freelancerRows.each(function() {
            const freelancerName = $(this).data('freelancer-name');
            const freelancerContact = $(this).data('freelancer-contact');

            const matches = freelancerName.includes(searchTerm) ||
                           freelancerContact.includes(searchTerm);

            if (matches) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        // Update search results count
        if (searchTerm === '') {
            $('#searchResults').text('Showing all freelancers');
        } else {
            $('#searchResults').text(`Found ${visibleCount} freelancer(s)`);
        }
    });
});

// Clear search
function clearSearch() {
    $('#freelancerSearch').val('');
    $('.freelancer-row').show();
    $('#searchResults').text('Showing all freelancers');
}

// Refresh freelancer statistics
function refreshFreelancerStats(freelancerId) {
    $.get(`/subadmin/freelancer-payments/freelancer/${freelancerId}`, function(response) {
        const freelancer = response.freelancer;
        const totalPaid = response.totalPaid;
        const paymentCount = response.payments.length;

        // Update the freelancer table row stats
        const freelancerRow = $(`.freelancer-row[data-freelancer-id="${freelancerId}"]`);
        freelancerRow.find('.stat-value').first().text(`₹${parseFloat(totalPaid).toLocaleString('en-IN', {minimumFractionDigits: 2})}`);
        freelancerRow.find('.stat-value').eq(1).text(paymentCount);
    });
}

// Refresh freelancer payment details modal if it's open
function refreshFreelancerPaymentDetailsModal(freelancerId) {
    // Check if the freelancer payment details modal is currently open
    const modal = document.getElementById('freelancerPaymentDetailsModal');
    if (modal && modal.classList.contains('show')) {
        // Get the freelancer name from the modal title or current context
        const freelancerRow = $(`.freelancer-row[data-freelancer-id="${freelancerId}"]`);
        const freelancerName = freelancerRow.find('.freelancer-name').text();

        if (freelancerName) {
            // Refresh the freelancer payment details
            viewFreelancerPaymentDetails(freelancerId, freelancerName);
        }
    }
}

// Toggle payment fields based on payment method
function togglePaymentFields(paymentMethod, modalType) {
    const prefix = modalType === 'edit' ? 'edit_' : '';

    // Hide all optional fields initially
    $(`#${prefix}transaction_id_group`).hide();
    $(`#${prefix}reference_number_group`).hide();

    // Show fields based on payment method
    switch(paymentMethod) {
        case 'bank_transfer':
            $(`#${prefix}transaction_id_group`).show();
            $(`#${prefix}reference_number_group`).show();
            break;
        case 'upi':
            $(`#${prefix}transaction_id_group`).show();
            $(`#${prefix}reference_number_group`).hide();
            break;
        case 'cheque':
            $(`#${prefix}transaction_id_group`).hide();
            $(`#${prefix}reference_number_group`).show();
            break;
        default:
            // No method selected, hide all optional fields
            break;
    }
}

// View Freelancer Payment Details Function
function viewFreelancerPaymentDetails(freelancerId, freelancerName) {
    // Show loading state
    document.getElementById('freelancerPaymentDetailsContent').innerHTML = `
        <div class="text-center p-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading freelancer payment details...</p>
        </div>
    `;

    // Show modal
    $('#freelancerPaymentDetailsModal').modal('show');

    // Fetch freelancer payment details
    fetch(`/subadmin/operation-leads/freelancer-payment-details/${freelancerId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFreelancerPaymentDetailsDisplay(data, freelancerName);
            } else {
                document.getElementById('freelancerPaymentDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        Error loading freelancer payment details: ${data.message || 'Unknown error'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('freelancerPaymentDetailsContent').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Error loading freelancer payment details. Please try again.
                </div>
            `;
        });
}

// Update freelancer payment details display
function updateFreelancerPaymentDetailsDisplay(data, freelancerName) {
    const content = document.getElementById('freelancerPaymentDetailsContent');

    let html = `
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user"></i> Freelancer Information</h5>
                        <p class="card-text"><strong>Name:</strong> ${freelancerName}</p>
                        <p class="card-text"><strong>Contact:</strong> ${data.freelancer.contact_no || 'N/A'}</p>
                        <p class="card-text"><strong>Expected Salary:</strong> ₹${data.freelancer.expected_salary || 0}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-rupee-sign"></i> Payment Summary</h5>
                        <p class="card-text"><strong>Total Earned (Verified Only):</strong> ₹${parseFloat(data.total_earned || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                        <small class="text-muted">Only payments verified through deployment details are included</small>
                        <p class="card-text"><strong>Payments Made:</strong> ₹${parseFloat(data.total_payments_made || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</p>
                        <p class="card-text"><strong>Remaining Amount:</strong> <span style="color: ${data.remaining_amount >= 0 ? '#fff' : '#ffcccb'}">₹${parseFloat(data.remaining_amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</span></p>
                        <p class="card-text"><strong>Total Leads:</strong> ${data.total_leads || 0}</p>
                        <p class="card-text"><strong>Active Deployments:</strong> ${data.active_deployments || 0}</p>
                    </div>
                </div>
            </div>
        </div>
    `;

    if (data.leads && data.leads.length > 0) {
        html += `
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Lead Details & Payments (Verified Only)</h5>
                    <small class="text-muted">Only deployment details with verified payments are shown</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Lead ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Location</th>
                                    <th>Deployment Date</th>
                                    <th>From Date</th>
                                    <th>To Date</th>
                                    <th>Days</th>
                                    <th>Rate/Day</th>
                                    <th>Payment</th>
                                    <th>Verified</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
        `;

        data.leads.forEach(lead => {
            const deploymentDate = lead.deployment_date ? new Date(lead.deployment_date).toLocaleDateString('en-IN') : 'N/A';
            const fromDate = lead.deployment_from_date ? new Date(lead.deployment_from_date).toLocaleDateString('en-IN') : 'N/A';
            const toDate = lead.deployment_to_date ? new Date(lead.deployment_to_date).toLocaleDateString('en-IN') : 'N/A';

            // Calculate days
            let days = 0;
            if (lead.deployment_from_date && lead.deployment_to_date) {
                const startDate = new Date(lead.deployment_from_date);
                const endDate = new Date(lead.deployment_to_date);
                const timeDiff = endDate.getTime() - startDate.getTime();
                days = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
            }

            // Status badge
            let statusBadge = '';
            switch(lead.deployment_status) {
                case 'Completed':
                    statusBadge = '<span class="badge bg-success">Completed</span>';
                    break;
                case 'In Progress':
                    statusBadge = '<span class="badge bg-warning">In Progress</span>';
                    break;
                case 'Pending':
                    statusBadge = '<span class="badge bg-info">Pending</span>';
                    break;
                case 'Cancelled':
                    statusBadge = '<span class="badge bg-danger">Cancelled</span>';
                    break;
                case 'Stop':
                    statusBadge = '<span class="badge bg-secondary">Stopped</span>';
                    break;
                default:
                    statusBadge = '<span class="badge bg-light text-dark">Unknown</span>';
            }

            html += `
                <tr>
                    <td><strong>#${lead.lead_id || 'N/A'}</strong></td>
                    <td>${lead.customer_name || 'N/A'}</td>
                    <td>${lead.service || 'N/A'}</td>
                    <td>${lead.location || 'N/A'}</td>
                    <td>${deploymentDate}</td>
                    <td>${fromDate}</td>
                    <td>${toDate}</td>
                    <td><span class="badge bg-primary">${days} days</span></td>
                    <td>₹${lead.vendor_rate_per_day || 0}</td>
                    <td><strong class="text-success">₹${lead.vendor_payment || 0}</strong></td>
                    <td><span class="badge bg-success"><i class="fas fa-check"></i> Verified</span></td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });

        html += `
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        `;
    } else {
        html += `
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                No deployment details found for this freelancer.
            </div>
        `;
    }

    content.innerHTML = html;
}

// Show alert
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
    const alertHtml = `<div class="alert ${alertClass}">${message}</div>`;

    // Remove existing alerts
    $('.alert').remove();

    // Add new alert at the top
    $('.content-wrapper .container-fluid').prepend(alertHtml);

    // Auto remove after 5 seconds
    setTimeout(() => {
        $('.alert').fadeOut();
    }, 5000);
}
</script>
@endsection
