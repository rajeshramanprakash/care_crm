@extends('customer.layouts.app')
@section('title', 'Payment Details')

@section('content')
@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card payment-page-card">
                        <div class="card-header payment-header">
                            <h3 class="card-title text-white mb-0"><i class="fas fa-rupee-sign mr-2"></i> Payment Details</h3>
                        </div>
                        <div class="card-body payment-body">
                            @php
                                $hasScreenshots = $receivedPayments->whereNotNull('screenshot')->count() > 0;
                            @endphp

                            <!-- Payment Summary Cards -->
                            <div class="row mb-2">
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="summary-card summary-card-info">
                                        <div class="summary-card-content">
                                            <div>
                                                <p class="summary-label mb-1">Total Invoice Amount</p>
                                                <h3 class="summary-amount mb-0">₹{{ number_format($totalInvoiceAmount, 2) }}</h3>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-file-invoice"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="summary-card summary-card-success">
                                        <div class="summary-card-content">
                                            <div>
                                                <p class="summary-label mb-1">Total Paid</p>
                                                <h3 class="summary-amount mb-0">₹{{ number_format($totalReceivedAmount, 2) }}</h3>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="summary-card summary-card-warning">
                                        <div class="summary-card-content">
                                            <div>
                                                <p class="summary-label mb-1">Advance Paid</p>
                                                <h3 class="summary-amount mb-0">₹{{ number_format($totalAdvanceAmount, 2) }}</h3>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-forward"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3">
                                    <div class="summary-card summary-card-danger">
                                        <div class="summary-card-content">
                                            <div>
                                                <p class="summary-label mb-1">Outstanding</p>
                                                <h3 class="summary-amount mb-0">₹{{ number_format($totalOutstandingAmount, 2) }}</h3>
                                            </div>
                                            <div class="summary-icon">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Invoices Table -->
                            @if($paymentInvoices->count() > 0)
                            <div class="row mb-4 mt-2">
                                <div class="col-12">
                                    <div class="section-title-wrap">
                                        <h5 class="section-title mb-3"><i class="fas fa-file-invoice mr-2"></i>Payment Invoices</h5>
                                    </div>
                                    <div class="table-responsive table-card">
                                        <table class="table payment-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Invoice ID</th>
                                                    <th>From Date</th>
                                                    <th>To Date</th>
                                                    <th>Work Days</th>
                                                    <th>Invoice Amount</th>
                                                    <th>Paid Amount</th>
                                                    <th>Outstanding</th>
                                                    <th>Status</th>
                                                    <th>Invoice</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($paymentInvoices as $invoice)
                                                @php
                                                    $paidAmount = $invoice->receivedPayments->sum('amount');
                                                    $outstanding = $invoice->payment_amount - $paidAmount;
                                                    $status = $outstanding <= 0 ? 'Paid' : ($paidAmount > 0 ? 'Partial' : 'Pending');
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $invoice->invoice_id }}</strong></td>
                                                    <td>{{ \Carbon\Carbon::parse($invoice->from_date)->format('d M Y, h:i A') }}</td>
                                                    <td>{{ \Carbon\Carbon::parse($invoice->to_date)->format('d M Y, h:i A') }}</td>
                                                    <td>{{ $invoice->work_days ?? 0 }} days</td>
                                                    <td><strong>₹{{ number_format($invoice->payment_amount, 2) }}</strong></td>
                                                    <td class="text-success"><strong>₹{{ number_format($paidAmount, 2) }}</strong></td>
                                                    <td class="{{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                                                        <strong>₹{{ number_format($outstanding, 2) }}</strong>
                                                    </td>
                                                    <td>
                                                        @if($status === 'Paid')
                                                            <span class="badge badge-success-soft">Paid</span>
                                                        @elseif($status === 'Partial')
                                                            <span class="badge badge-warning-soft">Partial</span>
                                                        @else
                                                            <span class="badge badge-danger-soft">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('customer.payment_invoice.show', $invoice->id) }}" target="_blank" class="btn btn-sm btn-view mr-1 mb-1 mb-md-0" title="View Invoice">
                                                            <i class="fas fa-file-invoice mr-1"></i>View
                                                        </a>
                                                        <a href="{{ route('customer.payment_invoice.show', $invoice->id) }}?download=1" class="btn btn-sm btn-download" title="Download PDF">
                                                            <i class="fas fa-download mr-1"></i>Download
                                                        </a>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Payment History Table -->
                            @if($receivedPayments->count() > 0)
                            <div class="row">
                                <div class="col-12">
                                    <div class="section-title-wrap">
                                        <h5 class="section-title mb-3"><i class="fas fa-history mr-2"></i>Payment History</h5>
                                    </div>
                                    <div class="table-responsive table-card">
                                        <table class="table payment-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Invoice ID</th>
                                                    <th>Amount</th>
                                                    <th>UTR Number</th>
                                                    <th>Remark</th>
                                                    @if($hasScreenshots)
                                                    <th>Screenshot</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($receivedPayments as $payment)
                                                <tr>
                                                    <td>{{ \Carbon\Carbon::parse($payment->received_date)->format('d M Y, h:i A') }}</td>
                                                    <td>
                                                        @if($payment->paymentInvoice)
                                                            <strong>{{ $payment->paymentInvoice->invoice_id }}</strong>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-success"><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
                                                    <td>{{ $payment->utr_number ?? 'N/A' }}</td>
                                                    <td>{{ $payment->remark ?? 'N/A' }}</td>
                                                    @if($hasScreenshots)
                                                    <td>
                                                        @if($payment->screenshot)
                                                            <a href="{{ Storage::url($payment->screenshot) }}" target="_blank" class="btn btn-sm btn-view">
                                                                <i class="fas fa-eye mr-1"></i>View
                                                            </a>
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    @endif
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="row">
                                <div class="col-12">
                                    <div class="empty-state empty-info">
                                        <i class="fas fa-info-circle mb-2"></i>
                                        <h5 class="mb-2">No Payment History Found</h5>
                                        <p class="mb-0">You do not have any payment records yet.</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            @if($paymentInvoices->count() === 0 && $receivedPayments->count() === 0)
                            <div class="row">
                                <div class="col-12">
                                    <div class="empty-state empty-warning">
                                        <i class="fas fa-exclamation-triangle mb-2"></i>
                                        <h5 class="mb-2">No Payment Information Available</h5>
                                        <p class="mb-0">Payment details will appear here once invoices are generated.</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@push('styles')
<style>
    .payment-page-card {
        border: 0;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        overflow: hidden;
    }
    .payment-header {
        background: linear-gradient(90deg, #ff8a00 0%, #ff7a18 100%);
        border: 0;
        padding: 1rem 1.25rem;
    }
    .payment-body {
        background: #f4f6fb;
        padding: 1.25rem;
    }
    .summary-card {
        border-radius: 14px;
        color: #fff;
        min-height: 108px;
        padding: 1rem 1.1rem;
        box-shadow: 0 8px 20px rgba(15, 23, 42, 0.16);
        transition: all 0.2s ease;
    }
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.2);
    }
    .summary-card-info {
        background: linear-gradient(135deg, #00b4db 0%, #158db6 100%);
    }
    .summary-card-success {
        background: linear-gradient(135deg, #2ecc71 0%, #1f9d58 100%);
    }
    .summary-card-warning {
        background: linear-gradient(135deg, #ffc107 0%, #f39c12 100%);
    }
    .summary-card-danger {
        background: linear-gradient(135deg, #ff5f6d 0%, #d7263d 100%);
    }
    .summary-card-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    .summary-label {
        font-size: 0.8rem;
        letter-spacing: 0.3px;
        font-weight: 600;
        opacity: 0.92;
        text-transform: uppercase;
    }
    .summary-amount {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1.2;
    }
    .summary-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .section-title {
        color: #1f2937;
        font-weight: 700;
        font-size: 1.1rem;
    }
    .table-card {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    }
    .payment-table thead th {
        background: #f8fafc;
        color: #334155;
        border-bottom: 1px solid #dbe2ea;
        font-size: 0.82rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.35px;
        white-space: nowrap;
    }
    .payment-table td {
        vertical-align: middle;
        border-top: 1px solid #edf2f7;
        color: #374151;
        font-size: 0.92rem;
    }
    .payment-table tbody tr:hover {
        background-color: #f8fbff;
    }
    .badge {
        font-size: 0.78rem;
        font-weight: 600;
        letter-spacing: 0.2px;
        padding: 0.45rem 0.72rem;
        border-radius: 999px;
    }
    .badge-success-soft {
        background: rgba(34, 197, 94, 0.16);
        color: #15803d;
    }
    .badge-warning-soft {
        background: rgba(245, 158, 11, 0.18);
        color: #b45309;
    }
    .badge-danger-soft {
        background: rgba(239, 68, 68, 0.16);
        color: #b91c1c;
    }
    .btn-view,
    .btn-download {
        border: 0;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 0.38rem 0.7rem;
        transition: all 0.2s ease;
    }
    .btn-view {
        background: #0ea5e9;
        color: #fff;
    }
    .btn-view:hover {
        color: #fff;
        background: #0284c7;
    }
    .btn-download {
        background: #6b7280;
        color: #fff;
    }
    .btn-download:hover {
        color: #fff;
        background: #4b5563;
    }
    .empty-state {
        border-radius: 12px;
        text-align: center;
        padding: 1.5rem 1rem;
        border: 1px solid transparent;
    }
    .empty-state i {
        font-size: 2rem;
        display: block;
    }
    .empty-info {
        background: #ecfeff;
        border-color: #a5f3fc;
        color: #0f766e;
    }
    .empty-warning {
        background: #fffbeb;
        border-color: #fde68a;
        color: #92400e;
    }
    @media (max-width: 767px) {
        .payment-body {
            padding: 0.95rem;
        }
        .summary-amount {
            font-size: 1.55rem;
        }
        .summary-icon {
            width: 44px;
            height: 44px;
            font-size: 1.1rem;
        }
    }
</style>
@endpush
@endsection

