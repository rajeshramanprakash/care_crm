@extends('operation_manager.layouts.app')

@php
    $page_title = $lead->customer_name ?: 'N/A';
    $page_title .= " | $lead->contact_no | View Lead | Operation CRM";
    $current_date = date('Y-m-d');
    $active_task_count = 0;
    $elem_class_help = '';
    $elem_text_help = '';
    $elem_class = '';
    $elem_text = '';
    $auth_user = Auth::user();
@endphp

@section('header-css')
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <style>
        .select2-container--default .select2-selection--multiple .select2-selection__choice {
            color: black;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
        }

        .disabled-option {
            color: red;
            font-weight: bold;
        } 

        .bg-blue {
            background-color: #F7941D;
        }

        /* Vendor Contact Tooltip Styles */
        .vendor-tooltip {
            position: fixed;
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .vendor-tooltip.show {
            opacity: 1;
        }

        .vendor-tooltip::before {
            content: '';
            position: absolute;
            left: -5px;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #333;
        }

        /* Prevent text selection on buttons */
        button, .btn {
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }

        /* Disabled button style */
        button:disabled, .btn:disabled {
            cursor: not-allowed;
            opacity: 0.65;
        }
    </style>
@endsection

@section('title', $page_title)

@section('main')
<div class="content-wrapper pb-5" style="height: 55vh; overflow-y: auto;">
    <section class="content">
            <div id="view_lead_card_container" class="card text-sm">
                <div class="card-header text-light bg-orange mt-2">
                    <button class="btn btn-xs text-light px-2 m-1" data-bs-toggle="modal"
                        data-bs-target="#manageRmMessageModal">
                        <i class="fa fa-plus"></i> Add Remark
                    </button>
                </div>
                <div class="card-body">
                    <div class="container-fluid">
                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange" style="">
                                <h3 class="card-title text-light">Lead Information</h3>
                                <button class="btn p-0 text-light float-right edit-btn" title="Edit"
                                    data-id="{{ $lead->id }}">
                                    <i class="fa fa-edit" style="font-size: 15px;"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row align-items-start g-2">
                                    <div class="col-lg-8 col-md-7">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Date/Time: </span>
                                        <span class="mx-1">{{ $lead->date_time }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Executive: </span>
                                        <span class="mx-1">{{ $lead->executive_name }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Customer Name: </span>
                                        <span class="mx-1">{{ $lead->customer_name }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Contact No.: </span>
                                        <span class="mx-1">{{ $lead->contact_no }}</span>
                                        <div class="phone_action_btns d-flex"
                                            style="position: absolute; top: -3px; left: 12rem;">
                                            <a href="#" class="d-flex">
                                                <div> </div>&nbsp;&nbsp;&nbsp;<i class="fab fa-whatsapp"
                                                    onclick="handle_whatsapp_msg({{ $lead->contact_no }})"
                                                    style="font-size: 25px; color: green;"></i>
                                            </a>
                                            <a href="tel:{{ $lead->contact_no }}" class="text-primary text-bold mx-1"
                                                style="font-size: 20px;">
                                                <i class="fa fa-phone-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Address: </span>
                                        <span class="mx-1">{{ $lead->address ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Location: </span>
                                        <span class="mx-1">{{ $lead->location ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Query: </span>
                                        <span class="mx-1">{{ $lead->query ?: 'N/A' }}</span>
                                    </div>
                                    @if($lead->query_remark)
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Query Remark: </span>
                                        <span class="mx-1">{{ $lead->query_remark }}</span>
                                    </div>
                                    @endif
                                    @include('partials.operation-lead-b2b-corporate-bulk-qty')
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Status: </span>
                                        <span class="mx-1">
                                            <span
                                                class="badge bg-{{ $lead->status == 'closed' ? 'success' : ($lead->status == 'follow up' ? 'warning' : 'info') }}">
                                                {{ ucfirst($lead->status) }}
                                            </span>
                                        </span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Shift Type: </span>
                                        <span class="mx-1">{{ $lead->shift_type ?: 'N/A' }}</span>
                                    </div>
                                    @if($lead->status === 'future prospect')
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Future contact date &amp; time: </span>
                                        <span class="mx-1">{{ $lead->future_prospect_date ? $lead->future_prospect_date->format('d-m-Y H:i') : 'N/A' }}</span>
                                    </div>
                                    @endif
                                    @if($lead->status === 'follow up')
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Follow-up date &amp; time: </span>
                                        <span class="mx-1">{{ $lead->follow_up_date ? $lead->follow_up_date->format('d-m-Y H:i') : 'N/A' }}</span>
                                    </div>
                                    @endif
                                    @if($lead->price_issue_remark)
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Price Issue Remark: </span>
                                        <span class="mx-1">{{ $lead->price_issue_remark }}</span>
                                    </div>
                                    @endif
                                    @if($lead->inactive_remark)
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Inactive Remark: </span>
                                        <span class="mx-1">{{ $lead->inactive_remark }}</span>
                                    </div>
                                    @endif
                                    @if($lead->last_call_status_display)
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Last Call Status: </span>
                                        <span class="mx-1">
                                            <span class="badge badge-{{ $lead->last_call_status_display == 'answered' ? 'warning' : ($lead->last_call_status_display == 'no answer' ? 'warning' : ($lead->last_call_status_display == 'busy' ? 'info' : ($lead->last_call_status_display == 'failed' ? 'danger' : ($lead->last_call_status_display == 'completed' ? 'primary' : 'secondary')))) }}">
                                                {{ ucfirst($lead->last_call_status_display) }}
                                            </span>
                                        </span>
                                    </div>
                                    @endif
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Patient Name: </span>
                                        <span class="mx-1">{{ $lead->patient_name ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Patient Gender: </span>
                                        <span class="mx-1">{{ $lead->patient_gender ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Age: </span>
                                        <span class="mx-1">{{ $lead->age ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Closed Rate: </span>
                                        <span class="mx-1">{{ $lead->closed_rate ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Vendor Closed Rate:
                                        </span>
                                        <span class="mx-1">{{ $lead->vendor_closed_rate ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Total Received: </span>
                                        <span class="mx-1">
                                            @php
                                                $totalReceived = $receivedPayments->sum('amount');
                                                $color = $totalReceived > 0 ? 'text-success' : 'text-dark';
                                            @endphp
                                            <strong class="{{ $color }}">₹{{ number_format($totalReceived, 2) }}</strong>
                                        </span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Total Outstanding: </span>
                                        <span class="mx-1">
                                            @php
                                                $totalInvoiced = $paymentInvoices->sum('payment_amount');
                                                $totalOutstanding = $totalInvoiced - $totalReceived;
                                                // If negative (extra payment), show green without minus
                                                $color = $totalOutstanding > 0 ? 'text-danger' : 'text-success';
                                                $displayValue = abs($totalOutstanding);
                                                $prefix = $totalOutstanding < 0 ? 'Extra: ' : '';
                                            @endphp
                                            <strong class="{{ $color }}">{{ $prefix }}₹{{ number_format($displayValue, 2) }}</strong>
                                        </span>
                                    </div>
                                </div>
                                    </div>
                                    @if(!empty($operationStatusRemarks) || !empty($crmLeadStatusRemarks))
                                    <div class="col-lg-4 col-md-5 clsr-sidebar-col d-flex flex-column gap-2">
                                        @if(!empty($operationStatusRemarks))
                                            @include('partials.crm-lead-sales-status-remarks', [
                                                'remarks' => $operationStatusRemarks,
                                                'sidebar' => true,
                                                'inColumn' => true,
                                                'panelTitle' => 'Operation Status Updates',
                                            ])
                                        @endif
                                        @if(!empty($crmLeadStatusRemarks))
                                            @include('partials.crm-lead-sales-status-remarks', [
                                                'remarks' => $crmLeadStatusRemarks,
                                                'sidebar' => true,
                                                'inColumn' => true,
                                                'panelTitle' => 'Sales / Manager Status',
                                            ])
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>



                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange" style="">
                                <h3 class="card-title text-light">Payment Information</h3>
                                <button class="btn p-0 text-light float-right edit-payment-btn" title="Edit Payment"
                                    data-id="{{ $lead->id }}">
                                    <i class="fa fa-edit" style="font-size: 15px;"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Payment Plan: </span>
                                        <span class="mx-1">{{ $lead->payment_plan ?: 'N/A' }}</span>
                                    </div>
                                    <div class="col-sm-6">
                                        <span class="text-bold mx-1" style="color: #F7941D">Ongoing/Stopped:
                                        </span>
                                        <span class="mx-1">{{ $lead->ongoing_stopped ?: 'N/A' }}</span>
                                    </div>
                                </div>
                                @if($lead->ongoing_stopped === 'stopped' && $lead->stopped_remark)
                                <div class="row mt-2">
                                    <div class="col-12">
                                        <span class="text-bold mx-1" style="color: #F7941D">Stopped Remark: </span>
                                        <span class="mx-1">{{ $lead->stopped_remark }}</span>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Payment Invoices Section --}}
                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange" style="">
                                <h3 class="card-title text-light">Payment Invoices (Auto-Generated)</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                    <table class="table table-bordered table-striped table-hover" style="min-width: 1200px; font-size: 0.9rem;">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="min-width: 150px;">Invoice ID</th>
                                                <th style="min-width: 140px;">From Date</th>
                                                <th style="min-width: 140px;">To Date</th>
                                                <th style="min-width: 100px;">Work Days</th>
                                                <th style="min-width: 120px;">Payment Amount</th>
                                                <th style="min-width: 120px;">Received Amount</th>
                                                <th style="min-width: 120px;">Outstanding</th>
                                                <th style="min-width: 100px;">Status</th>
                                                <th style="min-width: 150px;">Remark</th>
                                                <th style="min-width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($paymentInvoices as $invoice)
                                                @php
                                                    $totalReceived = $invoice->receivedPayments->sum('amount');
                                                    $outstanding = $invoice->payment_amount - $totalReceived;
                                                    // If outstanding is negative (extra payment), show green color and absolute value
                                                    // If outstanding is positive (pending payment), show red color
                                                    // If exactly paid, show green
                                                    $outstandingColor = $outstanding > 0 ? 'text-danger' : 'text-success';
                                                    $outstandingDisplay = abs($outstanding); // Always show absolute value
                                                    $outstandingPrefix = $outstanding < 0 ? 'Extra: ' : ($outstanding > 0 ? '' : '');
                                                @endphp
                                                <tr>
                                                    <td><strong>{{ $invoice->invoice_id }}</strong></td>
                                                    <td>{{ $invoice->from_date ? date('d-M-Y', strtotime($invoice->from_date)) : 'N/A' }}</td>
                                                    <td>{{ $invoice->to_date ? date('d-M-Y', strtotime($invoice->to_date)) : 'N/A' }}</td>
                                                    <td>{{ $invoice->work_days }} days</td>
                                                    <td>₹{{ number_format($invoice->payment_amount, 2) }}</td>
                                                    <td>₹{{ number_format($totalReceived, 2) }}</td>
                                                    <td><strong class="{{ $outstandingColor }}">{{ $outstandingPrefix }}₹{{ number_format($outstandingDisplay, 2) }}</strong></td>
                                                    <td>
                                                        @if($totalReceived >= $invoice->payment_amount)
                                                            <span class="badge bg-success">Paid</span>
                                                        @elseif($totalReceived > 0)
                                                            <span class="badge bg-warning text-dark">Partially Paid</span>
                                                        @else
                                                            <span class="badge bg-secondary">Unpaid</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $invoice->remark ?: 'N/A' }}</td>
                                                    <td>
                                                        <a href="{{ route('operation-manager.operation_leads.payment_invoice.show', $invoice->id) }}" target="_blank" class="btn btn-sm btn-info" title="View Invoice">
                                                            <i class="fa fa-file-invoice"></i> View Invoice
                                                        </a>
                                                        <button class="btn btn-sm btn-success mt-1" 
                                                            onclick="openAddPaymentModal({{ $invoice->id }})" 
                                                            title="Add Payment">
                                                            <i class="fa fa-plus"></i> Add Payment
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="10" class="text-center">
                                                        No payment invoices generated yet. Invoices will be automatically created when deployment is in progress.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- Received Payments Section --}}
                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange" style="">
                                <h3 class="card-title text-light">Received Payments</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                    <table class="table table-bordered table-striped table-hover" style="min-width: 1200px; font-size: 0.9rem;">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="min-width: 150px;">Invoice ID</th>
                                                <th style="min-width: 120px;">Amount</th>
                                                <th style="min-width: 140px;">Received Date</th>
                                                <th style="min-width: 120px;">UTR Number</th>
                                                <th style="min-width: 100px;">Screenshot</th>
                                                <th style="min-width: 150px;">Remark</th>
                                                <th style="min-width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($receivedPayments as $payment)
                                                <tr>
                                                    <td><strong>{{ $payment->paymentInvoice->invoice_id ?? 'N/A' }}</strong></td>
                                                    <td><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
                                                    <td>{{ $payment->received_date ? date('d-M-Y H:i', strtotime($payment->received_date)) : 'N/A' }}</td>
                                                    <td>{{ $payment->utr_number ?: 'N/A' }}</td>
                                                    <td>
                                                        @if ($payment->screenshot)
                                                            <a href="{{ asset('storage/' . $payment->screenshot) }}"
                                                                target="_blank" class="btn btn-sm btn-info">
                                                                <i class="fa fa-image"></i> View
                                                            </a>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>{{ $payment->remark ?: 'N/A' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-primary"
                                                                onclick="editReceivedPayment({{ $payment->id }})" 
                                                                title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" 
                                                                onclick="deleteReceivedPayment({{ $payment->id }})" 
                                                                title="Delete">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">No payments received yet</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange" style="">
                                <h3 class="card-title text-light">Deployment Details</h3>
                                <button class="btn p-0 text-light float-right" title="Add Deployment Detail"
                                    data-bs-toggle="modal" data-bs-target="#addDeploymentDetailModal">
                                    <i class="fa fa-plus" style="font-size: 15px;"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="overflow-x: auto; max-width: 100%;">
                                    <table class="table table-bordered table-striped table-hover" style="min-width: 1200px; font-size: 0.9rem;">
                                        <thead class="table-dark">
                                            <tr>
                                                <th style="min-width: 100px;">Invoice No.</th>
                                                <th style="min-width: 120px;">Deployment Date</th>
                                                <th style="min-width: 140px;">From Date & Time</th>
                                                <th style="min-width: 140px;">To Date & Time</th>
                                                <th style="min-width: 120px;">Deployment Status</th>
                                                <th style="min-width: 100px;">Duty Hours</th>
                                                <th style="min-width: 120px;">Vendor</th>
                                                <th style="min-width: 120px;">Staff Name</th>
                                                <th style="min-width: 120px;">Staff Number</th>
                                                <th style="min-width: 120px;">Payment Term</th>
                                                <th style="min-width: 120px;">Vendor Rate/Day</th>
                                                <th style="min-width: 120px;">Vendor Payment</th>
                                                <th style="min-width: 100px;">Verify Payment</th>
                                                <th style="min-width: 150px;">Remark</th>
                                                <th style="min-width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($deploymentDetails as $deployment)
                                                <tr>
                                                    <td><strong>{{ $deployment->id }}</strong></td>
                                                    <td>{{ $deployment->deployment_date ? date('d-M-Y H:i', strtotime($deployment->deployment_date)) : 'N/A' }}</td>
                                                    <td>{{ $deployment->deployment_from_date ? date('d-M-Y H:i', strtotime($deployment->deployment_from_date)) : 'N/A' }}</td>
                                                    <td>{{ $deployment->deployment_to_date ? date('d-M-Y H:i', strtotime($deployment->deployment_to_date)) : 'N/A' }}</td>
                                                    <td>{{ $deployment->deployment_status ?: 'N/A' }}</td>
                                                    <td>{{ $deployment->duty_hours ?: 'N/A' }}</td>
                                                    <td>
                                                        @if($deployment->freelance_staff_id && $deployment->freelanceStaff)
                                                            {{ $deployment->freelanceStaff->name }} (Freelance)
                                                        @elseif($deployment->vendor)
                                                            {{ $deployment->vendor->name }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                    <td>{{ $deployment->staff_name ?: 'N/A' }}</td>
                                                    <td>{{ $deployment->staff_number ?: 'N/A' }}</td>
                                                    <td>{{ $deployment->payment_term ?: 'N/A' }}</td>
                                                    <td>₹{{ number_format($deployment->vendor_rate_per_day ?: 0, 2) }}</td>
                                                    <td>
                                                        @php
                                                            $calculatedPayment = $deployment->vendor_payment ?? $deployment->calculated_vendor_payment;
                                                        @endphp
                                                        <strong id="deployment-payment-{{ $deployment->id }}">₹{{ number_format($calculatedPayment, 2) }}</strong>
                                                        @if($deployment->payment_term)
                                                            <br><small class="text-muted">({{ $deployment->getPaymentTermDays() }} days)</small>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="form-check">
                                                            <input class="form-check-input verify-payment-checkbox"
                                                                   type="checkbox"
                                                                   data-deployment-id="{{ $deployment->id }}"
                                                                   {{ $deployment->verify_payment ? 'checked' : '' }}
                                                                   onchange="toggleVerifyPayment({{ $deployment->id }}, this.checked)"
                                                                   title="Verify Payment">
                                                        </div>
                                                    </td>
                                                    <td>{{ $deployment->remark ?: 'N/A' }}</td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-sm btn-info"
                                                                onclick="viewDeploymentDetail({{ $deployment->id }})"
                                                                title="View Details">
                                                                <i class="fa fa-eye"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-primary"
                                                                onclick="editDeploymentDetail({{ $deployment->id }})"
                                                                title="Edit">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-warning"
                                                                onclick="openAbsentDatesModal({{ $deployment->id }})"
                                                                title="Mark Absent Days">
                                                                A
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="15" class="text-center">No deployment details found</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        {{-- Unified Call Logs + Recordings (Customer + Active Deployment) --}}
                        @php
                            $decodeCallLogs = function ($value) {
                                if (empty($value)) {
                                    return [];
                                }

                                if (is_array($value)) {
                                    return $value;
                                }

                                if (is_string($value)) {
                                    $decoded = json_decode($value, true);
                                    return is_array($decoded) ? $decoded : [];
                                }

                                return [];
                            };

                            $customerLogs = $decodeCallLogs($lead->recording_url ?? null);
                            $deploymentLogs = $decodeCallLogs($lead->active_deployment_recording_url ?? null);

                            $allCallLogs = [];
                            foreach ($customerLogs as $item) {
                                $allCallLogs[] = ['source' => 'customer', 'entry' => $item];
                            }
                            foreach ($deploymentLogs as $item) {
                                $allCallLogs[] = ['source' => 'deployment', 'entry' => $item];
                            }

                            $allowedStatuses = ['answered', 'no answer', 'noanswer', 'missed', 'busy', 'failed'];
                            $allCallLogs = array_values(array_filter($allCallLogs, function ($item) use ($allowedStatuses) {
                                $rec = $item['entry'] ?? [];
                                $meta = [];

                                if (!empty($rec['metadata'])) {
                                    if (is_array($rec['metadata'])) {
                                        $meta = $rec['metadata'];
                                    } elseif (is_string($rec['metadata'])) {
                                        $tmp = json_decode($rec['metadata'], true);
                                        $meta = is_array($tmp) ? $tmp : [];
                                    }
                                }

                                $status = strtolower(trim((string)($rec['dialstatus'] ?? ($meta['dialstatus'] ?? ''))));
                                return in_array($status, $allowedStatuses, true);
                            }));

                            usort($allCallLogs, function ($a, $b) {
                                $getTime = function ($item) {
                                    $rec = $item['entry'] ?? [];
                                    $meta = [];
                                    if (!empty($rec['metadata'])) {
                                        if (is_array($rec['metadata'])) {
                                            $meta = $rec['metadata'];
                                        } elseif (is_string($rec['metadata'])) {
                                            $tmp = json_decode($rec['metadata'], true);
                                            $meta = is_array($tmp) ? $tmp : [];
                                        }
                                    }
                                    $raw = $rec['datetime'] ?? ($meta['datetime'] ?? '');
                                    $ts = $raw ? strtotime((string)$raw) : false;
                                    return $ts ?: 0;
                                };
                                return $getTime($b) <=> $getTime($a);
                            });
                        @endphp
                        <div class="card mb-5">
                            <div class="card-header text-light bg-orange">
                                <h3 class="card-title text-light">
                                    <i class="fa fa-phone"></i> Call Logs & Recordings
                                </h3>
                                <span class="badge badge-light float-right" style="font-size: 14px;">
                                    {{ count($allCallLogs) }} Call(s)
                                </span>
                            </div>
                            <div class="card-body">
                                @if (count($allCallLogs) > 0)
                                    @foreach ($allCallLogs as $item)
                                        @php
                                            $rec = $item['entry'] ?? [];
                                            $source = $item['source'] ?? 'customer';

                                            $meta = [];
                                            if (!empty($rec['metadata'])) {
                                                if (is_array($rec['metadata'])) {
                                                    $meta = $rec['metadata'];
                                                } elseif (is_string($rec['metadata'])) {
                                                    $tmp = json_decode($rec['metadata'], true);
                                                    $meta = is_array($tmp) ? $tmp : [];
                                                }
                                            }

                                            $dialRaw = strtolower(trim((string)($rec['dialstatus'] ?? ($meta['dialstatus'] ?? ''))));
                                            $dialText = $dialRaw === 'noanswer' || $dialRaw === 'no answer' ? 'Missed' : ucfirst($dialRaw ?: 'N/A');
                                            $badgeClass = $dialRaw === 'answered' ? 'success' : ($dialRaw === 'busy' ? 'warning' : 'danger');

                                            $audioUrl = $rec['url'] ?? '';
                                            $dateText = $rec['datetime'] ?? ($meta['datetime'] ?? 'N/A');
                                            $agentText = $meta['caller_agent'] ?? 'N/A';
                                            $callTypeText = $meta['call_direction'] ?? 'N/A';
                                            $durationText = isset($meta['duration']) ? ((string)$meta['duration'] . 's') : 'N/A';
                                        @endphp
                                        <div class="mb-3 p-3" style="background-color: #f8f9fa; border-left: 4px solid #F7941D; border-radius: 8px;">
                                            @if(!empty($audioUrl))
                                                <audio controls preload="metadata" style="vertical-align:middle;max-width:520px;width:500px;">
                                                    <source src="{{ $audioUrl }}" type="audio/mpeg">
                                                    <source src="{{ $audioUrl }}" type="audio/mp3">
                                                    <source src="{{ $audioUrl }}" type="audio/wav">
                                                    Your browser does not support the audio element.
                                                </audio>
                                                <a href="{{ $audioUrl }}" target="_blank" class="btn btn-sm btn-outline-primary ms-2" title="Download">
                                                    <i class="fa fa-download"></i> Download
                                                </a>
                                            @else
                                                <div class="text-muted mb-2">
                                                    <i class="fa fa-info-circle"></i> Recording file not available for this call.
                                                </div>
                                            @endif

                                            <div class="mt-2">
                                                <span class="badge badge-info" style="color:#000;">
                                                    <i class="fa fa-calendar"></i> {{ $dateText }}
                                                </span>
                                                <span class="badge badge-secondary" style="color:#000;">
                                                    <i class="fa fa-user"></i> {{ $agentText }}
                                                </span>
                                                <span class="badge badge-{{ $badgeClass }}" style="color:#000;">
                                                    <i class="fa fa-phone-alt"></i> {{ $dialText }}
                                                </span>
                                                <span class="badge badge-primary" style="color:#fff;">
                                                    <i class="fa fa-route"></i> {{ ucfirst((string)$callTypeText) }}
                                                </span>
                                                <span class="badge badge-dark">
                                                    <i class="fa fa-clock"></i> {{ $durationText }}
                                                </span>
                                                <span class="badge badge-light" style="color:#000;">
                                                    {{ $source === 'deployment' ? 'Active Deployment' : 'Customer' }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center text-muted py-4">
                                        <i class="fa fa-phone-slash fa-3x mb-3" style="opacity: 0.3;"></i>
                                        <p>No answered/missed call logs found for this lead.</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>



    <!-- Add Received Payment Modal -->
    <div class="modal fade" id="addReceivedPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Received Payment</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form id="addReceivedPaymentForm" enctype="multipart/form-data">
                    <div class="modal-body text-sm">
                        @csrf
                        <input type="hidden" id="invoice_id" name="invoice_id">
                        <div class="form-group mb-3">
                            <label for="invoice_info">Invoice</label>
                            <input type="text" class="form-control" id="invoice_info" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="form-group mb-3">
                            <label for="amount">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="received_date">Received Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="received_date" name="received_date" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="utr_number">UTR Number</label>
                            <input type="text" class="form-control" id="utr_number" name="utr_number" placeholder="Enter UTR number">
                        </div>
                        <div class="form-group mb-3">
                            <label for="screenshot">Screenshot/Payment Proof <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="screenshot" name="screenshot" required accept="image/*,application/pdf">
                            <small class="text-muted">Please upload a screenshot of the payment transaction (required)</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="remark">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3" placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Received Payment Modal -->
    <div class="modal fade" id="editReceivedPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Received Payment</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form id="editReceivedPaymentForm" method="post" enctype="multipart/form-data">
                    <div class="modal-body text-sm">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_payment_id" name="payment_id">
                        <div class="form-group mb-3">
                            <label for="edit_invoice_info">Invoice</label>
                            <input type="text" class="form-control" id="edit_invoice_info" readonly style="background-color: #f8f9fa;">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_amount">Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="edit_amount" name="amount" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_received_date">Received Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="edit_received_date" name="received_date" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_utr_number">UTR Number</label>
                            <input type="text" class="form-control" id="edit_utr_number" name="utr_number" placeholder="Enter UTR number">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_screenshot">Screenshot/Payment Proof</label>
                            <input type="file" class="form-control" id="edit_screenshot" name="screenshot" accept="image/*,application/pdf">
                            <small class="text-muted">Leave empty to keep existing screenshot</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_remark">Remark</label>
                            <textarea class="form-control" id="edit_remark" name="remark" rows="3" placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light" style="background-color: var(--wb-dark-red);">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editOperationLeadModal" tabindex="-1" aria-labelledby="editOperationLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOperationLeadModalLabel" style="color: #F7941D !important;">Edit Operation Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editOperationLeadForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_lead_id" name="id">
                        <div class="row" id="editOperationLeadFields">
                            <!-- Fields will be loaded here -->
                        </div>
                        <div class="row">
                            <div class="col-12">
                                @include('partials.lead-status-remarks-fields', [
                                    'fieldPrefix' => 'edit',
                                    'aiGenerateUrlTemplate' => url('/operation-manager/operation-leads/__ID__/status-remark/generate-ai'),
                                ])
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateOperationLead">Update Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Payment Information Modal -->
    <div class="modal fade" id="editPaymentInfoModal" tabindex="-1" aria-labelledby="editPaymentInfoModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPaymentInfoModalLabel">Edit Payment Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editPaymentInfoForm">
                        <input type="hidden" id="edit_payment_lead_id" name="id">
                        <div class="mb-3">
                            <label for="payment_plan" class="form-label">Payment Plan</label>
                            <select class="form-control" id="payment_plan" name="payment_plan">
                                <option value="">Select Payment Plan</option>
                                <option value="3 Days">3 Days</option>
                                <option value="1 Week">1 Week</option>
                                <option value="15 Days">15 Days</option>
                                <option value="1 Month">1 Month</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="ongoing_stopped" class="form-label">Ongoing/Stopped</label>
                            <select class="form-control" id="ongoing_stopped" name="ongoing_stopped" onchange="toggleStoppedRemark()">
                                <option value="">Select Status</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="stopped">Stopped</option>
                            </select>
                        </div>
                        <div class="mb-3 d-none" id="stopped_remark_group">
                            <label for="stopped_remark" class="form-label">Stopped Remark</label>
                            <input type="text" class="form-control" id="stopped_remark" name="stopped_remark" placeholder="Enter remark for stopped status">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updatePaymentBtn">Update Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Deployment Detail Modal -->
    <div class="modal fade" id="addDeploymentDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Add Deployment Detail</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form id="addDeploymentDetailForm" enctype="multipart/form-data">
                    <div class="modal-body text-sm">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="deployment_date">Deployment Date</label>
                            <input type="datetime-local" class="form-control" id="deployment_date"
                                name="deployment_date" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="deployment_from_date">From Date & Time</label>
                            <input type="datetime-local" class="form-control" id="deployment_from_date"
                                name="deployment_from_date" onchange="calculateVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="deployment_to_date">To Date & Time</label>
                            <input type="datetime-local" class="form-control" id="deployment_to_date"
                                name="deployment_to_date" onchange="calculateVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="deployment_status">Deployment Status</label>
                            <select class="form-control" id="deployment_status" name="deployment_status" required>
                                <option value="">Select Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Stop">Stop</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="duty_hours">Duty Hours</label>
                            <select class="form-control" id="duty_hours" name="duty_hours">
                                <option value="">Select Duty Hours</option>
                                <option value="12hr">12 Hours</option>
                                <option value="24hr">24 Hours</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="vendor_id">Vendor / Freelance Staff</label>
                            <select class="form-control" id="vendor_id" name="vendor_id">
                                <option value="">Select Vendor / Freelance Staff</option>
                                @foreach ($filteredVendors as $vendor)
                                    <option value="{{ $vendor->id }}" data-contact="{{ $vendor->contact_no ?: 'N/A' }}" data-name="{{ $vendor->name }}" data-is-freelance="{{ isset($vendor->is_freelance) ? ($vendor->is_freelance ? 'true' : 'false') : 'false' }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only vendors and active freelance staff providing {{ $lead->query }} service in {{ $lead->location }} are shown</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="staff_name">Staff Name</label>
                            <input type="text" class="form-control" id="staff_name" name="staff_name">
                        </div>
                        <div class="form-group mb-3">
                            <label for="staff_number">Staff Number</label>
                            <input type="text" class="form-control" id="staff_number" name="staff_number" placeholder="Enter staff contact number">
                        </div>
                        <div class="form-group mb-3">
                            <label for="payment_term">Payment Term</label>
                            <select class="form-control" id="payment_term" name="payment_term">
                                <option value="">Select Payment Term</option>
                                <option value="15 day advance">15 Day Advance</option>
                                <option value="1 week advance">1 Week Advance</option>
                                <option value="per day advance">Per Day Advance</option>
                                <option value="post 1 week">Post 1 Week</option>
                                <option value="post 15 days">Post 15 Days</option>
                                <option value="post one month">Post One Month</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="vendor_rate_per_day">Vendor Rate Per Day</label>
                            <input type="number" step="0.01" class="form-control" id="vendor_rate_per_day"
                                name="vendor_rate_per_day" onchange="calculateVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="vendor_payment">Vendor Payment</label>
                            <input type="number" step="0.01" class="form-control" id="vendor_payment" name="vendor_payment" readonly style="background-color: #f8f9fa;">
                            <small class="text-muted">Automatically calculated based on work days and rate per day</small>
                        </div>
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="verify_payment" name="verify_payment" value="1">
                                <label class="form-check-label" for="verify_payment">
                                    Verify Payment
                                </label>
                            </div>
                            <small class="text-muted">Check this to include this payment in vendor's total earned amount</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="remark">Remark</label>
                            <textarea class="form-control" id="remark" name="remark" rows="3" placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light"
                            style="background-color: var(--wb-dark-red);">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Deployment Detail Modal -->
    <div class="modal fade" id="editDeploymentDetailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Edit Deployment Detail</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <form id="editDeploymentDetailForm" method="post" enctype="multipart/form-data">
                    <div class="modal-body text-sm">
                        @csrf
                        @method('PUT')
                        <div class="form-group mb-3">
                            <label for="edit_deployment_date">Deployment Date</label>
                            <input type="datetime-local" class="form-control" id="edit_deployment_date"
                                name="deployment_date" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_deployment_from_date">From Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_deployment_from_date"
                                name="deployment_from_date" onchange="calculateEditVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_deployment_to_date">To Date & Time</label>
                            <input type="datetime-local" class="form-control" id="edit_deployment_to_date"
                                name="deployment_to_date" onchange="calculateEditVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_deployment_status">Deployment Status</label>
                            <select class="form-control" id="edit_deployment_status" name="deployment_status" required>
                                <option value="">Select Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Stop">Stop</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_duty_hours">Duty Hours</label>
                            <select class="form-control" id="edit_duty_hours" name="duty_hours">
                                <option value="">Select Duty Hours</option>
                                <option value="12hr">12 Hours</option>
                                <option value="24hr">24 Hours</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_vendor_id">Vendor / Freelance Staff</label>
                            <select class="form-control" id="edit_vendor_id" name="vendor_id">
                                <option value="">Select Vendor / Freelance Staff</option>
                                @foreach ($filteredVendors as $vendor)
                                    <option value="{{ $vendor->id }}" data-contact="{{ $vendor->contact_no ?: 'N/A' }}" data-name="{{ $vendor->name }}" data-is-freelance="{{ isset($vendor->is_freelance) ? ($vendor->is_freelance ? 'true' : 'false') : 'false' }}">{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only vendors and active freelance staff providing {{ $lead->query }} service in {{ $lead->location }} are shown</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_staff_name">Staff Name</label>
                            <input type="text" class="form-control" id="edit_staff_name" name="staff_name">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_staff_number">Staff Number</label>
                            <input type="text" class="form-control" id="edit_staff_number" name="staff_number" placeholder="Enter staff contact number">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_payment_term">Payment Term</label>
                            <select class="form-control" id="edit_payment_term" name="payment_term">
                                <option value="">Select Payment Term</option>
                                <option value="15 day advance">15 Day Advance</option>
                                <option value="1 week advance">1 Week Advance</option>
                                <option value="per day advance">Per Day Advance</option>
                                <option value="post 1 week">Post 1 Week</option>
                                <option value="post 15 days">Post 15 Days</option>
                                <option value="post one month">Post One Month</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_vendor_rate_per_day">Vendor Rate Per Day</label>
                            <input type="number" step="0.01" class="form-control" id="edit_vendor_rate_per_day"
                                name="vendor_rate_per_day" onchange="calculateEditVendorPayment()">
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_vendor_payment">Vendor Payment</label>
                            <input type="number" step="0.01" class="form-control" id="edit_vendor_payment" name="vendor_payment" readonly style="background-color: #f8f9fa;">
                            <small class="text-muted">Automatically calculated based on work days and rate per day</small>
                        </div>
                        <div class="form-group mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit_verify_payment" name="verify_payment" value="1">
                                <label class="form-check-label" for="edit_verify_payment">
                                    Verify Payment
                                </label>
                            </div>
                            <small class="text-muted">Check this to include this payment in vendor's total earned amount</small>
                        </div>
                        <div class="form-group mb-3">
                            <label for="edit_remark">Remark</label>
                            <textarea class="form-control" id="edit_remark" name="remark" rows="3" placeholder="Enter any additional remarks..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-sm text-light"
                            style="background-color: var(--wb-dark-red);">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Payment Detail Modal -->
    <div class="modal fade" id="viewPaymentDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Payment Detail Information</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="viewPaymentDetailBody">
                    <!-- Payment details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Deployment Detail Modal -->
    <div class="modal fade" id="viewDeploymentDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Deployment Detail Information</h4>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body" id="viewDeploymentDetailBody">
                    <!-- Deployment details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deployment Absent Days Modal -->
    <div class="modal fade" id="deploymentAbsentModal" tabindex="-1" aria-labelledby="deploymentAbsentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deploymentAbsentModalLabel">Mark Absent Days</h5>
                    <button type="button" class="btn text-secondary" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fa fa-times"></i>
                    </button>
                </div>
                <div class="modal-body text-sm">
                    <p class="mb-2">
                        Click on a date to toggle <strong>Absent</strong>. Click <strong>Save</strong> to apply changes. Vendor payment will update after save based on
                        <span id="absentRatePerDayLabel">rate per day</span>.
                    </p>
                    <div id="absentDatesContainer" class="d-flex flex-wrap gap-2">
                        <!-- Dates will be injected here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm bg-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-success" id="saveAbsentDatesBtn" style="display: none;">Save</button>
                </div>
            </div>
        </div>
    </div>

<!-- Vendor Contact Tooltip -->
<div id="vendorTooltip" class="vendor-tooltip"></div>

@endsection

@section('footer-script')
    @include('includes.lead-status-remarks-assets', ['part' => 'all'])
    <script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
    <script>
        function normalizeDateTimeLocalForApi(value) {
            if (!value) return '';
            const str = String(value).trim();
            if (!str) return '';
            if (str.includes('T')) {
                const parts = str.split('T');
                const date = parts[0] || '';
                const time = parts[1] || '';
                if (!date || !time) return '';
                const hm = time.slice(0, 5);
                return `${date} ${hm}:00`;
            }
            return str;
        }

        // Global state variables for double-click prevention
        let isDeleting = false;
        let isSubmittingDeployment = false;
        let isUpdatingDeployment = false;

        // Global double-click prevention for all buttons
        $(document).ready(function() {
            // Prevent double-click on all buttons with onclick handlers
            $(document).on('click', 'button[onclick], a.btn[onclick]', function(e) {
                const $btn = $(this);
                if ($btn.data('clicking')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    return false;
                }
                $btn.data('clicking', true);
                setTimeout(function() {
                    $btn.data('clicking', false);
                }, 2000);
            });
        });

        // Vendor Contact Tooltip Functionality
        $(document).ready(function() {
            const tooltip = $('#vendorTooltip');

            // Handle vendor dropdown hover events
            $(document).on('mouseenter', 'select[name="vendor_id"] option', function() {
                const contact = $(this).data('contact');
                if (contact && contact !== 'N/A') {
                    const select = $(this).parent();
                    const selectOffset = select.offset();
                    const selectWidth = select.outerWidth();

                    tooltip.text('Contact: ' + contact)
                           .css({
                               'left': selectOffset.left + selectWidth + 10,
                               'top': selectOffset.top + ($(this).index() * 20) + 10
                           })
                           .addClass('show');
                }
            });

            $(document).on('mouseleave', 'select[name="vendor_id"] option', function() {
                tooltip.removeClass('show');
            });

            // Also handle when hovering over the select element itself
            $(document).on('mouseenter', 'select[name="vendor_id"]', function() {
                const selectedOption = $(this).find('option:selected');
                const contact = selectedOption.data('contact');
                if (contact && contact !== 'N/A') {
                    const selectOffset = $(this).offset();
                    const selectWidth = $(this).outerWidth();

                    tooltip.text('Contact: ' + contact)
                           .css({
                               'left': selectOffset.left + selectWidth + 10,
                               'top': selectOffset.top + 10
                           })
                           .addClass('show');
                }
            });

            $(document).on('mouseleave', 'select[name="vendor_id"]', function() {
                tooltip.removeClass('show');
            });

            // Hide tooltip when modal is closed
            $('.modal').on('hidden.bs.modal', function() {
                tooltip.removeClass('show');
            });

            // Reset form states when modals are opened
            $('#addReceivedPaymentModal').on('show.bs.modal', function() {
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', false);
                $submitBtn.html('Submit');
            });

            $('#editReceivedPaymentModal').on('show.bs.modal', function() {
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', false);
                $submitBtn.html('Update');
            });

            $('#addDeploymentDetailModal').on('show.bs.modal', function() {
                isSubmittingDeployment = false;
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', false);
                $submitBtn.html('Submit');
            });

            $('#editDeploymentDetailModal').on('show.bs.modal', function() {
                isUpdatingDeployment = false;
                const $submitBtn = $(this).find('button[type="submit"]');
                $submitBtn.prop('disabled', false);
                $submitBtn.html('Update');
            });
        });

        // Edit Operation Lead
        $(document).on('click', '.edit-btn', function() {
            var leadId = $(this).data('id');
            console.log('Editing lead ID:', leadId);

            $.get('{{ url("operation-manager/operation-leads") }}/' + leadId + '/edit', function(lead) {
                console.log('Edit data received:', lead);

                // Define all editable fields for lead information
                const editableFields = [
                    'date_time', 'executive', 'customer_name', 'contact_no', 'address', 'location',
                    'query', 'query_remark', 'status', 'follow_up_date', 'future_prospect_date', 'shift_type', 'price_issue_remark', 'inactive_remark', 'closed_remark', 'patient_name', 'age', 'closed_rate', 'vendor_closed_rate'
                ];

                let fields = '';
                for (const [key, value] of Object.entries(lead)) {
                    if (!editableFields.includes(key)) continue;

                    if (key === 'status') {
                        fields +=
                            `<div class='col-md-6 mb-3'><label class='form-label'>Status</label><select class='form-control' name='status' required onchange="toggleStatusRemark()"><option value='profile' ${value==='profile'?'selected':''}>Profile</option><option value='profile pending' ${value==='profile pending'?'selected':''}>Profile Pending</option><option value='profile shared' ${value==='profile shared'?'selected':''}>Profile Shared</option><option value='closed' ${value==='closed'?'selected':''}>Closed</option><option value='follow up' ${value==='follow up'?'selected':''}>Follow Up</option><option value='future prospect' ${value==='future prospect'?'selected':''}>Future Prospect</option><option value='inactive' ${value==='inactive'?'selected':''}>Inactive</option><option value='price issue' ${value==='price issue'?'selected':''}>Price Issue</option></select></div>`;
                        // Always add separate remark fields after status field
                        fields +=
                            `<div class='col-md-6 mb-3 d-none' id='edit_price_issue_remark_group'><label class='form-label'>Price Issue Remark</label><input type='text' class='form-control' name='price_issue_remark' value='${lead.price_issue_remark || ''}' placeholder='Enter remark for price issue status'></div>`;
                        fields +=
                            `<div class='col-md-6 mb-3 d-none' id='edit_inactive_remark_group'><label class='form-label'>Inactive Remark</label><input type='text' class='form-control' name='inactive_remark' value='${lead.inactive_remark || ''}' placeholder='Enter remark for inactive status'></div>`;
                        fields +=
                            `<div class='col-md-6 mb-3 d-none' id='edit_closed_remark_group'><label class='form-label'>Closed Remark</label><input type='text' class='form-control' name='closed_remark' value='${lead.closed_remark || ''}' placeholder='Enter remark for closed status'></div>`;
                        var fpValShow = formatShowOpFpForInput(lead.future_prospect_date);
                        var fuValShow = formatShowOpFpForInput(lead.follow_up_date);
                        fields +=
                            `<div class='col-md-6 mb-3 d-none' id='edit_future_prospect_date_group'><label class='form-label'>Future contact date & time</label><input type='datetime-local' class='form-control' name='future_prospect_date' step='60' value='${fpValShow}'></div>`;
                        fields +=
                            `<div class='col-md-6 mb-3 d-none' id='edit_follow_up_date_group'><label class='form-label'>Follow-up date & time</label><input type='datetime-local' class='form-control' name='follow_up_date' step='60' value='${fuValShow}'></div>`;
                    } else if (key === 'shift_type') {
                        fields += `<div class='col-md-6 mb-3'><label class='form-label'>Shift Type</label><select class='form-control' name='shift_type'><option value=''>Select Shift Type</option><option value='12hr' ${value==='12hr'?'selected':''}>12hr</option><option value='24hr' ${value==='24hr'?'selected':''}>24hr</option><option value='both' ${value==='both'?'selected':''}>Both</option></select></div>`;
                    } else if (key === 'price_issue_remark' || key === 'inactive_remark' || key === 'closed_remark' || key === 'query_remark' || key === 'future_prospect_date' || key === 'follow_up_date') {
                        // Skip these as they're handled above with status/query field
                        continue;
                    } else if (key === 'last_call_status') {
                        fields += `<div class='col-md-6 mb-3'><label class='form-label'>Last Call Status</label><select class='form-control' name='last_call_status'><option value=''>Select Call Status</option><option value='answered' ${value === 'answered' ? 'selected' : ''}>Answered</option><option value='no answer' ${value === 'no answer' ? 'selected' : ''}>No Answer</option><option value='busy' ${value === 'busy' ? 'selected' : ''}>Busy</option><option value='failed' ${value === 'failed' ? 'selected' : ''}>Failed</option><option value='completed' ${value === 'completed' ? 'selected' : ''}>Completed</option></select></div>`;
                    } else if (key === 'query') {
                        let queryOptions = '';
                        @foreach ($services as $service)
                            queryOptions += `<option value='{{ $service->name }}' ${value==='{{ $service->name }}'?'selected':''}>{{ $service->name }}</option>`;
                        @endforeach
                        fields += `<div class='col-md-6 mb-3'><label class='form-label'>Query</label><select class='form-control' name='query' required onchange="toggleQueryRemark()">${queryOptions}</select></div>`;
                    } else if (key === 'query_remark') {
                        fields += `<div class='col-md-6 mb-3 d-none' id='edit_query_remark_group'><label class='form-label'>Query Remark</label><input type='text' class='form-control' name='query_remark' value='${value || ''}' placeholder='Enter remark for selected query'></div>`;
                    } else if (key === 'location') {
                        let locationOptions = '';
                        @foreach ($locations as $location)
                            locationOptions += `<option value='{{ $location->name }}' data-state='{{ $location->state ?? '' }}' data-city-name='{{ $location->name }}' ${value==='{{ $location->name }}'?'selected':''}>{{ $location->name }}</option>`;
                        @endforeach
                        fields += `<div class='col-md-6 mb-3'><label class='form-label'>Location</label><select class='form-control location-select' name='location' required>${locationOptions}</select></div>`;
                    } else if (key === 'executive') {
                        let executiveOptions = '';
                        @foreach ($executives as $executive)
                            executiveOptions += `<option value='{{ $executive->id }}' ${value=={{ $executive->id }}?'selected':''}>{{ $executive->f_name }}</option>`;
                        @endforeach
                        fields += `<div class='col-md-6 mb-3'><label class='form-label'>Executive</label><select class='form-control' name='executive' required><option value=''>Select Executive</option>${executiveOptions}</select></div>`;
                    } else if (key === 'date_time') {
                        fields +=
                            `<div class='col-md-6 mb-3'><label class='form-label'>Date/Time</label><input type='datetime-local' class='form-control' name='date_time' value='${value ? value.slice(0, 16) : ''}' required></div>`;
                    } else if (key === 'age') {
                        fields +=
                            `<div class='col-md-6 mb-3'><label class='form-label'>Age</label><input type='number' class='form-control' name='age' value='${value ?? ''}'></div>`;
                    } else if (key === 'closed_rate' || key === 'vendor_closed_rate') {
                        fields +=
                            `<div class='col-md-6 mb-3'><label class='form-label'>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</label><input type='number' step='0.01' class='form-control' name='${key}' value='${value ?? ''}'></div>`;
                    } else {
                        fields +=
                            `<div class='col-md-6 mb-3'><label class='form-label'>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</label><input type='text' class='form-control' name='${key}' value='${value ?? ''}'></div>`;
                    }
                }

                if (lead.related_lead && lead.related_lead.query_remarks) {
                    fields += `
                <div class='col-12 mb-3'>
                    <div class='alert alert-light border'>
                        <strong><i class='fas fa-comment me-2'></i>Sales/Manager Query Remarks:</strong><br>
                        <div class='mt-2 p-2 bg-white rounded'>${lead.related_lead.query_remarks}</div>
                    </div>
                </div>`;
                }
                if (window.LeadStatusRemarks && lead.crm_lead_status_remarks && lead.crm_lead_status_remarks.length) {
                    fields += LeadStatusRemarks.renderOperationReadOnlyBlock(lead.crm_lead_status_remarks);
                }

                $('#edit_lead_id').val(lead.id);
                $('#editOperationLeadFields').html(fields);
                if (typeof initLocationSelects === 'function') {
                    initLocationSelects('#editOperationLeadFields');
                }

                if (window.LeadStatusRemarks) {
                    LeadStatusRemarks.onEditLeadLoaded('edit', lead, { leadId: lead.id, operationOwn: true });
                }

                var modal = new bootstrap.Modal(document.getElementById('editOperationLeadModal'));
                modal.show();

                // Show/hide status remark field based on current status
                setTimeout(function() {
                    toggleStatusRemark();
                    toggleQueryRemark();
                }, 100);
            }).fail(function(xhr, status, error) {
                console.error('Edit error:', xhr.responseText);
                alert('Error loading lead for editing: ' + xhr.responseText);
            });
        });

        // Update Operation Lead
        $('#updateOperationLead').on('click', function() {
            if (window.LeadStatusRemarks && !window.LeadStatusRemarks.validateBeforeSave('edit')) {
                return;
            }
            const $btn = $(this);
            
            // Prevent double click
            if ($btn.prop('disabled')) {
                return false;
            }
            
            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            
            var leadId = $('#edit_lead_id').val();
            var form = $('#editOperationLeadForm')[0];
            var formData = new FormData(form);
            var status = String(formData.get('status') || '').toLowerCase();
            var followUpRaw = formData.get('follow_up_date');
            var futureProspectRaw = formData.get('future_prospect_date');

            if (status === 'follow up') {
                if (!followUpRaw) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Please set Follow-up date & time');
                    return;
                }
                var normalizedFollow = normalizeDateTimeLocalForApi(followUpRaw);
                if (!normalizedFollow) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Please set valid Follow-up date & time');
                    return;
                }
                formData.set('follow_up_date', normalizedFollow);
            } else {
                formData.delete('follow_up_date');
            }

            if (status === 'future prospect') {
                if (!futureProspectRaw) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Please set Future contact date & time');
                    return;
                }
                var normalizedFuture = normalizeDateTimeLocalForApi(futureProspectRaw);
                if (!normalizedFuture) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Please set valid Future contact date & time');
                    return;
                }
                formData.set('future_prospect_date', normalizedFuture);
            } else {
                formData.delete('future_prospect_date');
            }

            $.ajax({
                url: '{{ url("operation-manager/operation-leads") }}/' + leadId,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-HTTP-Method-Override': 'PUT'
                },
                success: function(response) {
                    alert(response.message);
                    $('#editOperationLeadModal').modal('hide');
                    window.location.reload();
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Error: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON
                        .message : 'Could not update lead.'));
                }
            });
        });

        // Old payment detail functions removed - now using received payments system

        // Edit Vendor Info
        $('.edit-vendor-btn').on('click', function() {
            var leadId = $(this).data('id');
            $.get('{{ url("operation-manager/operation-leads") }}/' + leadId + '/edit', function(lead) {
                $('#edit_vendor_lead_id').val(lead.id);
                $('#vendor_name').val(lead.vendor_name || '');
                $('#staff_name').val(lead.staff_name || '');
                $('#vendor_closed_rate').val(lead.vendor_closed_rate || '');
                $('#editVendorModal').modal('show');
            });
        });

        $('#updateVendorBtn').on('click', function() {
            const $btn = $(this);
            
            // Prevent double click
            if ($btn.prop('disabled')) {
                return false;
            }
            
            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            
            var leadId = $('#edit_vendor_lead_id').val();
            var data = {
                vendor_name: $('#vendor_name').val(),
                staff_name: $('#staff_name').val(),
                vendor_closed_rate: $('#vendor_closed_rate').val(),
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: 'PUT'
            };
            $.ajax({
                url: '{{ url("operation-manager/operation-leads") }}/' + leadId,
                type: 'POST',
                data: data,
                success: function(response) {
                    alert(response.message);
                    $('#editVendorModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    alert('Error updating vendor info');
                }
            });
        });

        // Edit Payment Info
        $('.edit-payment-btn').on('click', function() {
            var leadId = $(this).data('id');
            $.ajax({
                url: '{{ url("operation-manager/operation-leads") }}/' + leadId + '/edit',
                type: 'GET',
                success: function(response) {
                    $('#edit_payment_lead_id').val(leadId);
                    $('#payment_plan').val(response.payment_plan || '');
                    $('#ongoing_stopped').val(response.ongoing_stopped || '');
                    $('#stopped_remark').val(response.stopped_remark || '');
                    $('#editPaymentInfoModal').modal('show');
                    
                    // Show/hide stopped remark field based on current status
                    setTimeout(function() {
                        toggleStoppedRemark();
                    }, 100);
                },
                error: function(xhr) {
                    toastr.error('Error loading payment information');
                }
            });
        });

        $('#updatePaymentBtn').on('click', function() {
            const $btn = $(this);
            
            // Prevent double click
            if ($btn.prop('disabled')) {
                return false;
            }
            
            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            
            var leadId = $('#edit_payment_lead_id').val();
            var data = {
                payment_plan: $('#payment_plan').val(),
                ongoing_stopped: $('#ongoing_stopped').val(),
                stopped_remark: $('#stopped_remark').val(),
                _token: $('meta[name="csrf-token"]').attr('content'),
                _method: 'PUT'
            };

            $.ajax({
                url: '{{ url("operation-manager/operation-leads") }}/' + leadId,
                type: 'POST',
                data: data,
                success: function(response) {
                    toastr.success('Payment information updated successfully');
                    $('#editPaymentInfoModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                    
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        toastr.error('Error: ' + xhr.responseJSON.message);
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        var errorMessages = [];
                        for (var field in xhr.responseJSON.errors) {
                            errorMessages.push(xhr.responseJSON.errors[field].join(', '));
                        }
                        toastr.error('Validation errors: ' + errorMessages.join('; '));
                    } else {
                        toastr.error('Error updating payment information');
                    }
                }
            });
        });

        // Open modal to add payment for an invoice
        function openAddPaymentModal(invoiceId) {
            // Find the specific invoice row by iterating through invoice table rows
            let invoiceInfo = 'Invoice #' + invoiceId;
            
            // Search in payment invoices table
            $('#view_lead_card_container').find('table').each(function() {
                $(this).find('tbody tr').each(function() {
                    const $row = $(this);
                    const $firstCell = $row.find('td:first strong');
                    if ($firstCell.length && $firstCell.text().includes('INV-')) {
                        // Check if this is the row by looking for a button with this invoiceId
                        const $addBtn = $row.find(`button[onclick*="${invoiceId}"]`);
                        if ($addBtn.length) {
                            invoiceInfo = $firstCell.text();
                        }
                    }
                });
            });
            
            $('#addReceivedPaymentForm')[0].reset();
            $('#invoice_id').val(invoiceId);
            $('#invoice_info').val(invoiceInfo);
            
            // Set current date/time as default
            const now = new Date();
            const dateTimeStr = now.toISOString().slice(0, 16);
            $('#received_date').val(dateTimeStr);
            
            $('#addReceivedPaymentModal').modal('show');
        }

        // Add Received Payment Form Submission
        $('#addReceivedPaymentForm').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Prevent double submission
            if ($submitBtn.prop('disabled')) {
                return false;
            }
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
            
            const invoiceId = $('#invoice_id').val();
            var formData = new FormData(this);

            $.ajax({
                url: `/operation-manager/operation-leads/invoice/${invoiceId}/received-payment`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    toastr.success('Payment received successfully');
                    $('#addReceivedPaymentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    // Re-enable button on error
                    $submitBtn.prop('disabled', false);
                    $submitBtn.html(originalText);
                    
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.message) {
                        alert('Validation Error: ' + xhr.responseJSON.message);
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        let errorMsg = 'Validation Errors:\n';
                        Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                            errorMsg += '- ' + xhr.responseJSON.errors[key][0] + '\n';
                        });
                        alert(errorMsg);
                    } else {
                        alert('Error adding payment');
                    }
                }
            });
        });

        // Edit Received Payment
        function editReceivedPayment(id) {
            $.ajax({
                url: `/operation-manager/operation-leads/received-payment/${id}/edit`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        const payment = response.payment;
                        
                        $('#edit_payment_id').val(payment.id);
                        $('#edit_invoice_info').val(payment.payment_invoice.invoice_id || 'N/A');
                        $('#edit_amount').val(payment.amount);
                        $('#edit_received_date').val(payment.received_date ? payment.received_date.slice(0, 16) : '');
                        $('#edit_utr_number').val(payment.utr_number || '');
                        $('#edit_remark').val(payment.remark || '');
                        
                        $('#editReceivedPaymentModal').modal('show');
                    }
                },
                error: function(xhr) {
                    alert('Error loading payment data');
                }
            });
        }

        // Edit Received Payment Form Submission
        $('#editReceivedPaymentForm').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            
            // Prevent double submission
            if ($submitBtn.prop('disabled')) {
                return false;
            }
            
            // Disable submit button and show loading state
            $submitBtn.prop('disabled', true);
            const originalText = $submitBtn.html();
            $submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Updating...');
            
            const paymentId = $('#edit_payment_id').val();
            var formData = new FormData(this);

            $.ajax({
                url: `/operation-manager/operation-leads/received-payment/${paymentId}`,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success('Payment updated successfully');
                    $('#editReceivedPaymentModal').modal('hide');
                    location.reload();
                },
                error: function(xhr) {
                    // Re-enable button on error
                    $submitBtn.prop('disabled', false);
                    $submitBtn.html(originalText);
                    alert('Error updating payment');
                }
            });
        });

        // Delete Received Payment
        function deleteReceivedPayment(id) {
            if (isDeleting) {
                return false;
            }
            
            if (!confirm('Are you sure you want to delete this payment?')) {
                return;
            }

            isDeleting = true;

            $.ajax({
                url: `/operation-manager/operation-leads/received-payment/${id}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    toastr.success('Payment deleted successfully');
                    location.reload();
                },
                error: function(xhr) {
                    isDeleting = false;
                    alert('Error deleting payment');
                }
            });
        }

        // Deployment Details Form Handling
        document.getElementById('addDeploymentDetailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isSubmittingDeployment) {
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            isSubmittingDeployment = true;
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Submitting...';
            
            const formData = new FormData(this);
            formData.append('operation_lead_id', '{{ $lead->id }}');

            fetch('{{ route('operation-manager.operation_leads.deployment.store', $lead->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        $('#addDeploymentDetailModal').modal('hide');
                        location.reload();
                    } else {
                        isSubmittingDeployment = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        toastr.error(data.message);
                    }
                })
                .catch(error => {
                    isSubmittingDeployment = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    console.error('Error:', error);
                    toastr.error('An error occurred while adding deployment detail');
                });
        });

        function editDeploymentDetail(id) {
            fetch(`{{ url('operation-manager/operation-leads/deployment') }}/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const deployment = data.deployment;
                        const form = document.getElementById('editDeploymentDetailForm');
                        form.action = `{{ url('operation-manager/operation-leads/deployment') }}/${id}`;

                        // Format dates for datetime-local input with null checks
                        if (deployment.deployment_date) {
                            const deploymentDate = new Date(deployment.deployment_date);
                            document.getElementById('edit_deployment_date').value = deploymentDate.toISOString().slice(0, 16);
                        }

                        if (deployment.deployment_from_date) {
                            const fromDate = new Date(deployment.deployment_from_date);
                            document.getElementById('edit_deployment_from_date').value = fromDate.toISOString().slice(0, 16);
                        }

                        if (deployment.deployment_to_date) {
                            const toDate = new Date(deployment.deployment_to_date);
                            document.getElementById('edit_deployment_to_date').value = toDate.toISOString().slice(0, 16);
                        }

                        document.getElementById('edit_deployment_status').value = deployment.deployment_status || '';
                        document.getElementById('edit_duty_hours').value = deployment.duty_hours || '';
                        // Handle vendor selection (regular vendor or freelance staff)
                        if (deployment.freelance_staff_id) {
                            document.getElementById('edit_vendor_id').value = 'freelance_' + deployment.freelance_staff_id;
                        } else {
                            document.getElementById('edit_vendor_id').value = deployment.vendor_id || '';
                        }
                        document.getElementById('edit_staff_name').value = deployment.staff_name || '';
                        document.getElementById('edit_staff_number').value = deployment.staff_number || '';
                        document.getElementById('edit_payment_term').value = deployment.payment_term || '';
                        document.getElementById('edit_vendor_rate_per_day').value = deployment.vendor_rate_per_day || '';
                        document.getElementById('edit_remark').value = deployment.remark || '';

                        // Set verify payment checkbox
                        document.getElementById('edit_verify_payment').checked = deployment.verify_payment || false;

                        // Calculate vendor payment
                        calculateEditVendorPayment();

                        $('#editDeploymentDetailModal').modal('show');
                    } else {
                        toastr.error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    toastr.error('An error occurred while fetching deployment detail');
                });
        }

        document.getElementById('editDeploymentDetailForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (isUpdatingDeployment) {
                return false;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            isUpdatingDeployment = true;
            submitBtn.disabled = true;
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Updating...';
            
            const formData = new FormData(this);
            const url = this.action;

            fetch(url, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        toastr.success(data.message);
                        $('#editDeploymentDetailModal').modal('hide');
                        location.reload();
                    } else {
                        isUpdatingDeployment = false;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                        toastr.error(data.message);
                    }
                })
                .catch(error => {
                    isUpdatingDeployment = false;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    console.error('Error:', error);
                    toastr.error('An error occurred while updating deployment detail');
                });
        });

        // Date validation removed for old payment detail forms

        // Date validation for Deployment Details
        document.getElementById('deployment_from_date').addEventListener('change', function() {
            const fromDate = this.value;
            const toDateInput = document.getElementById('deployment_to_date');
            if (fromDate && toDateInput.value && fromDate >= toDateInput.value) {
                alert('To Date & Time must be after From Date & Time');
                this.value = '';
            }
        });

        document.getElementById('deployment_to_date').addEventListener('change', function() {
            const toDate = this.value;
            const fromDateInput = document.getElementById('deployment_from_date');
            if (toDate && fromDateInput.value && fromDateInput.value >= toDate) {
                alert('To Date & Time must be after From Date & Time');
                this.value = '';
            }
        });

        // Date validation for Edit Deployment Details
        document.getElementById('edit_deployment_from_date').addEventListener('change', function() {
            const fromDate = this.value;
            const toDateInput = document.getElementById('edit_deployment_to_date');
            if (fromDate && toDateInput.value && fromDate >= toDateInput.value) {
                alert('To Date & Time must be after From Date & Time');
                this.value = '';
            }
        });

        document.getElementById('edit_deployment_to_date').addEventListener('change', function() {
            const toDate = this.value;
            const fromDateInput = document.getElementById('edit_deployment_from_date');
            if (toDate && fromDateInput.value && fromDateInput.value >= toDate) {
                alert('To Date & Time must be after From Date & Time');
                this.value = '';
            }
        });

        // Old payment calculation functions removed - now using invoice system

        // Calculate vendor payment for Add Deployment form
        function calculateVendorPayment() {
            const fromDate = document.getElementById('deployment_from_date').value;
            const toDate = document.getElementById('deployment_to_date').value;
            const ratePerDay = parseFloat(document.getElementById('vendor_rate_per_day').value) || 0;

            if (fromDate && toDate && ratePerDay > 0) {
                const startDate = new Date(fromDate);
                const endDate = new Date(toDate);

                // Calculate work days (inclusive of both start and end dates)
                const timeDiff = endDate.getTime() - startDate.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 for inclusive

                const vendorPayment = daysDiff * ratePerDay;
                document.getElementById('vendor_payment').value = vendorPayment.toFixed(2);
            } else {
                document.getElementById('vendor_payment').value = '0.00';
            }
        }

        // Calculate vendor payment for Edit Deployment form
        function calculateEditVendorPayment() {
            const fromDate = document.getElementById('edit_deployment_from_date').value;
            const toDate = document.getElementById('edit_deployment_to_date').value;
            const ratePerDay = parseFloat(document.getElementById('edit_vendor_rate_per_day').value) || 0;

            if (fromDate && toDate && ratePerDay > 0) {
                const startDate = new Date(fromDate);
                const endDate = new Date(toDate);

                // Calculate work days (inclusive of both start and end dates)
                const timeDiff = endDate.getTime() - startDate.getTime();
                const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 for inclusive

                const vendorPayment = daysDiff * ratePerDay;
                document.getElementById('edit_vendor_payment').value = vendorPayment.toFixed(2);
            } else {
                document.getElementById('edit_vendor_payment').value = '0.00';
            }
        }

        // Old view payment detail function removed

        // View Deployment Detail Function
        function viewDeploymentDetail(id) {
            fetch(`{{ url('operation-manager/operation-leads/deployment') }}/${id}/edit`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const deployment = data.deployment;

                        // Populate view modal
                        document.getElementById('viewDeploymentDetailBody').innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Deployment Date:</strong> ${deployment.deployment_date ? new Date(deployment.deployment_date).toLocaleString() : 'N/A'}</p>
                                    <p><strong>From Date & Time:</strong> ${deployment.deployment_from_date ? new Date(deployment.deployment_from_date).toLocaleString() : 'N/A'}</p>
                                    <p><strong>To Date & Time:</strong> ${deployment.deployment_to_date ? new Date(deployment.deployment_to_date).toLocaleString() : 'N/A'}</p>
                                    <p><strong>Deployment Status:</strong> ${deployment.deployment_status || 'N/A'}</p>
                                    <p><strong>Duty Hours:</strong> ${deployment.duty_hours || 'N/A'}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Vendor:</strong> ${deployment.freelance_staff_id && deployment.freelance_staff ? deployment.freelance_staff.name + ' (Freelance)' : (deployment.vendor ? deployment.vendor.name : 'N/A')}</p>
                                    <p><strong>Staff Name:</strong> ${deployment.staff_name || 'N/A'}</p>
                                    <p><strong>Staff Number:</strong> ${deployment.staff_number || 'N/A'}</p>
                                    <p><strong>Payment Term:</strong> ${deployment.payment_term || 'N/A'}</p>
                                    <p><strong>Vendor Rate Per Day:</strong> ₹${(deployment.vendor_rate_per_day || 0).toFixed(2)}</p>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <p><strong>Vendor Payment:</strong> ₹${(deployment.vendor_payment || 0).toFixed(2)}</p>
                                    <p><strong>Remark:</strong> ${deployment.remark || 'N/A'}</p>
                                </div>
                            </div>
                        `;

                        new bootstrap.Modal(document.getElementById('viewDeploymentDetailModal')).show();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading deployment detail');
                });
        }

        // Open Absent Days Modal for a deployment (local state; Save persists)
        var absentModalState = { deploymentId: null, absentSet: {} };

        function openAbsentDatesModal(deploymentId) {
            const container = document.getElementById('absentDatesContainer');
            const rateLabel = document.getElementById('absentRatePerDayLabel');
            const saveBtn = document.getElementById('saveAbsentDatesBtn');
            if (container) {
                container.innerHTML = '<span class="text-muted">Loading dates...</span>';
            }
            if (saveBtn) saveBtn.style.display = 'none';

            fetch(`{{ route('operation-manager.operation_leads.deployment.dates', ['id' => 'DEP_ID_PLACEHOLDER']) }}`.replace('DEP_ID_PLACEHOLDER', deploymentId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Could not load dates for this deployment.');
                    return;
                }

                if (rateLabel) {
                    rateLabel.textContent = `₹${parseFloat(data.rate_per_day).toFixed(2)} per day`;
                }

                absentModalState.deploymentId = deploymentId;
                absentModalState.absentSet = {};
                data.dates.forEach(function(item) {
                    if (item.absent) absentModalState.absentSet[item.date] = true;
                });

                if (!container) return;
                container.innerHTML = '';
                data.dates.forEach(function(item) {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'btn btn-sm m-1 ' + (item.absent ? 'btn-danger' : 'btn-outline-secondary');
                    btn.textContent = item.label;
                    btn.title = item.absent ? 'Click to mark present' : 'Click to mark absent';
                    btn.dataset.date = item.date;
                    btn.onclick = function() {
                        toggleAbsentLocal(item.date, btn);
                    };
                    container.appendChild(btn);
                });

                if (saveBtn) saveBtn.style.display = 'inline-block';
                saveBtn.onclick = function() { saveAbsentDates(deploymentId); };

                const modal = new bootstrap.Modal(document.getElementById('deploymentAbsentModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Error loading absent dates:', error);
                alert('Error loading dates for this deployment.');
            });
        }

        function toggleAbsentLocal(date, btn) {
            if (absentModalState.absentSet[date]) {
                delete absentModalState.absentSet[date];
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-outline-secondary');
                btn.title = 'Click to mark absent';
            } else {
                absentModalState.absentSet[date] = true;
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-danger');
                btn.title = 'Click to mark present';
            }
        }

        function saveAbsentDates(deploymentId) {
            const saveBtn = document.getElementById('saveAbsentDatesBtn');
            const absentDates = Object.keys(absentModalState.absentSet || {}).sort();
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving...';
            }
            fetch(`{{ route('operation-manager.operation_leads.deployment.save-absent', ['id' => 'DEP_ID_PLACEHOLDER']) }}`.replace('DEP_ID_PLACEHOLDER', deploymentId), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ absent_dates: absentDates })
            })
            .then(response => response.json())
            .then(data => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
                if (!data.success) {
                    alert(data.message || 'Could not save absent dates.');
                    return;
                }
                const paymentEl = document.getElementById('deployment-payment-' + deploymentId);
                if (paymentEl) {
                    paymentEl.textContent = '₹' + parseFloat(data.vendor_payment).toFixed(2);
                }
                toastr.success(data.message || 'Absent days saved successfully.');
                document.getElementById('deploymentAbsentModal').querySelector('[data-bs-dismiss="modal"]').click();
                location.reload();
            })
            .catch(error => {
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save';
                }
                console.error('Error saving absent dates:', error);
                alert('Error saving absent dates.');
            });
        }

        function formatShowOpFpForInput(v) {
            if (!v) return '';
            var d = new Date(v);
            if (isNaN(d.getTime())) return '';
            var p = function(n) { return String(n).padStart(2, '0'); };
            return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' + p(d.getHours()) + ':' + p(d.getMinutes());
        }

        // Function to toggle status remark fields in edit modal
        function toggleStatusRemark() {
            var statusSelect = document.querySelector('#editOperationLeadModal select[name="status"]');
            var priceIssueRemarkGroup = document.getElementById('edit_price_issue_remark_group');
            var inactiveRemarkGroup = document.getElementById('edit_inactive_remark_group');
            var closedRemarkGroup = document.getElementById('edit_closed_remark_group');
            var fpGroup = document.getElementById('edit_future_prospect_date_group');
            var fpInput = fpGroup ? fpGroup.querySelector('input[name="future_prospect_date"]') : null;
            var fuGroup = document.getElementById('edit_follow_up_date_group');
            var fuInput = fuGroup ? fuGroup.querySelector('input[name="follow_up_date"]') : null;
            if (statusSelect) {
                var status = statusSelect.value;
                if (priceIssueRemarkGroup) {
                    priceIssueRemarkGroup.classList.toggle('d-none', status !== 'price issue');
                }
                if (inactiveRemarkGroup) {
                    inactiveRemarkGroup.classList.toggle('d-none', status !== 'inactive');
                }
                if (closedRemarkGroup) {
                    closedRemarkGroup.classList.toggle('d-none', status !== 'closed');
                }
                if (fpGroup) {
                    fpGroup.classList.toggle('d-none', status !== 'future prospect');
                }
                if (fpInput) {
                    fpInput.required = (status === 'future prospect');
                }
                if (fuGroup) {
                    fuGroup.classList.toggle('d-none', status !== 'follow up');
                }
                if (fuInput) {
                    fuInput.required = (status === 'follow up');
                }
                var remarksGroup = document.getElementById('edit_status_remarks_group');
                if (remarksGroup) {
                    remarksGroup.style.display = status ? '' : 'none';
                }
            }
        }

        // Function to toggle query remark field in edit modal
        function toggleQueryRemark() {
            var querySelect = document.querySelector('#editOperationLeadModal select[name="query"]');
            var queryRemarkGroup = document.getElementById('edit_query_remark_group');
            if (querySelect && queryRemarkGroup) {
                var query = querySelect.value;
                // Show query remark field for any selected query
                queryRemarkGroup.classList.toggle('d-none', !query || query === '');
            }
        }

        // Function to toggle stopped remark field in payment modal
        function toggleStoppedRemark() {
            var ongoingStoppedSelect = document.getElementById('ongoing_stopped');
            var stoppedRemarkGroup = document.getElementById('stopped_remark_group');
            if (ongoingStoppedSelect && stoppedRemarkGroup) {
                var status = ongoingStoppedSelect.value;
                stoppedRemarkGroup.classList.toggle('d-none', status !== 'stopped');
            }
        }

        // Function to update freelancer status
        function updateFreelancerStatus(freelancerId, status) {
            if (!confirm(`Are you sure you want to set this freelancer as ${status}?`)) {
                return;
            }

            $.ajax({
                url: '{{ route("operation.operation_leads.update_freelancer_status") }}',
                type: 'POST',
                data: {
                    freelancer_id: freelancerId,
                    status: status,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        // Only refresh the modal content, don't trigger the vendor count button click
                        refreshVendorModalContent();
                        // Update the vendor count in the table
                        updateVendorCountInTable();
                    } else {
                        toastr.error(response.message || 'Failed to update freelancer status');
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    toastr.error('Error updating freelancer status. Please try again.');
                }
            });
        }

        // Function to refresh only the vendor modal content
        function refreshVendorModalContent() {
            // Get the current modal data
            const currentModal = $('#vendorDetailsModal');
            if (currentModal.length && currentModal.hasClass('show')) {
                // Get the current lead data from the modal
                const leadInfo = $('#vendorDetailsContent').find('.lead-info-card');
                if (leadInfo.length) {
                    const customerName = leadInfo.find('.info-item').eq(0).find('span').text().replace('Customer: ', '');
                    const location = leadInfo.find('.info-item').eq(1).find('span').text().replace('Location: ', '');
                    const query = leadInfo.find('.info-item').eq(2).find('span').text().replace('Service: ', '');

                    // Get the lead ID from the current vendor count button
                    const currentVendorBtn = $('.vendor-count-btn').filter(function() {
                        return $(this).data('location') === location && $(this).data('query') === query;
                    }).first();

                    if (currentVendorBtn.length) {
                        const leadId = currentVendorBtn.data('lead-id');

                        // Refresh only the vendor modal content
                        $.ajax({
                            url: '{{ route("operation.operation_leads.vendor_details") }}',
                            type: 'GET',
                            data: {
                                lead_id: leadId,
                                location: location,
                                query: query
                            },
                            success: function(response) {
                                if (response.success) {
                                    let vendorHtml = `
                                        <div class="lead-info-card">
                                            <h6><i class="fas fa-info-circle me-2"></i>Lead Information</h6>
                                            <div class="info-item">
                                                <i class="fas fa-user"></i>
                                                <span><strong>Customer:</strong> ${response.lead_info.customer_name}</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><strong>Location:</strong> ${response.lead_info.location}</span>
                                            </div>
                                            <div class="info-item">
                                                <i class="fas fa-question-circle"></i>
                                                <span><strong>Service:</strong> ${response.lead_info.query}</span>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h5 class="mb-0">
                                                <i class="fas fa-users me-2" style="color: #F7941D;"></i>
                                                Available Professionals
                                            </h5>
                                            <span class="badge fs-6" style="background-color: #F7941D;">${response.vendors.length} Found</span>
                                        </div>
                                    `;

                                    if (response.vendors.length > 0) {
                                        response.vendors.forEach(function(vendor, index) {
                                            const vendorType = vendor.is_freelance ? 'Freelancer' : 'Vendor';
                                            const typeClass = vendor.is_freelance ? 'freelancer' : 'vendor';
                                            const typeIcon = vendor.is_freelance ? 'fas fa-user-tie' : 'fas fa-building';

                                            // Status badge for freelancers
                                            let statusBadge = '';
                                            if (vendor.is_freelance && vendor.status) {
                                                const statusClass = vendor.status === 'active' ? 'badge-success' :
                                                                  vendor.status === 'inactive' ? 'badge-warning' : 'badge-danger';
                                                const statusText = vendor.status === 'active' ? 'Active' :
                                                                 vendor.status === 'inactive' ? 'Inactive' : 'Blacklisted';
                                                statusBadge = `<span class="badge ${statusClass} ms-2">${statusText}</span>`;
                                            }

                                            // Action buttons for freelancers
                                            let actionButtons = '';
                                            if (vendor.is_freelance) {
                                                actionButtons = `
                                                    <div class="action-buttons-group">
                                                        <button class="btn btn-sm btn-warning action-btn" onclick="updateFreelancerStatus('${vendor.id}', 'inactive')" title="Set Inactive">
                                                            <i class="fas fa-pause-circle"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger action-btn" onclick="updateFreelancerStatus('${vendor.id}', 'blacklist')" title="Blacklist">
                                                            <i class="fas fa-ban"></i>
                                                        </button>
                                                    </div>
                                                `;
                                            }

                                            vendorHtml += `
                                                <div class="vendor-card" style="animation-delay: ${index * 0.1}s;">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-1">
                                                            <div class="vendor-avatar">
                                                                <i class="${typeIcon}" style="color: white; font-size: 1.5rem;"></i>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="vendor-name d-flex align-items-center">
                                                                ${vendor.name}
                                                                ${statusBadge}
                                                            </div>
                                                            <span class="vendor-type-badge ${typeClass}">
                                                                <i class="fas fa-tag me-1"></i>${vendorType}
                                                            </span>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="vendor-contact">
                                                                <i class="fas fa-phone"></i>
                                                                <span>${vendor.contact_no}</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4 text-end">
                                                            <div class="action-buttons d-flex align-items-center justify-content-end gap-2">
                                                                ${vendor.contact_no !== 'N/A' ? `
                                                                    <button class="call-btn-vendor" onclick="makeCall('${vendor.contact_no}')" title="Call ${vendor.name}">
                                                                        <i class="fas fa-phone me-1"></i>Call Now
                                                                    </button>
                                                                ` : `
                                                                    <span class="text-muted">
                                                                        <i class="fas fa-phone-slash me-1"></i>No Contact
                                                                    </span>
                                                                `}
                                                                ${actionButtons}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            `;
                                        });
                                    } else {
                                        vendorHtml += `
                                            <div class="empty-state">
                                                <i class="fas fa-users-slash"></i>
                                                <h5>No Professionals Available</h5>
                                                <p>Sorry, we couldn't find any vendors or freelancers matching your criteria for this location and service.</p>
                                                <small class="text-muted">Try expanding your search criteria or contact support for assistance.</small>
                                            </div>
                                        `;
                                    }

                                    $('#vendorDetailsContent').html(vendorHtml);
                                }
                            },
                            error: function(xhr) {
                                console.error('Error refreshing modal:', xhr);
                            }
                        });
                    }
                }
            }
        }

        // Function to update vendor count in the table
        function updateVendorCountInTable() {
            // Get all vendor count buttons and update their counts
            $('.vendor-count-btn').each(function() {
                const $btn = $(this);
                const leadId = $btn.data('lead-id');
                const location = $btn.data('location');
                const query = $btn.data('query');

                $.ajax({
                    url: '{{ route("operation.operation_leads.updated_vendor_count") }}',
                    type: 'GET',
                    data: {
                        lead_id: leadId,
                        location: location,
                        query: query
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update the button text with new count
                            $btn.html(`<i class="fas fa-users"></i> ${response.count}`);
                        }
                    },
                    error: function(xhr) {
                        console.error('Error updating vendor count:', xhr);
                    }
                });
            });
        }

        // Toggle Verify Payment Function
        function toggleVerifyPayment(deploymentId, isChecked) {
            $.ajax({
                url: `{{ url('operation-manager/operation-leads/deployment') }}/${deploymentId}/toggle-verify-payment`,
                type: 'POST',
                data: {
                    verify_payment: isChecked,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    } else {
                        toastr.error(response.message || 'Failed to update payment verification status');
                        // Revert checkbox state on error
                        $(`input[data-deployment-id="${deploymentId}"]`).prop('checked', !isChecked);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    toastr.error('Error updating payment verification status. Please try again.');
                    // Revert checkbox state on error
                    $(`input[data-deployment-id="${deploymentId}"]`).prop('checked', !isChecked);
                }
            });
        }

        // Function to handle audio loading issues
        function handleAudioLoading() {
            $('audio').each(function() {
                const audio = this;
                const url = $(audio).find('source').first().attr('src');
                
                // Handle audio events
                audio.addEventListener('loadedmetadata', function() {
                    console.log('Audio metadata loaded for:', url);
                });
                
                audio.addEventListener('error', function(e) {
                    console.error('Audio loading error for:', url, e);
                    
                    // Show fallback message
                    if (!$(audio).next('.audio-fallback').length) {
                        $(audio).after(`
                            <div class="audio-fallback" style="color: #dc3545; font-size: 12px; margin-top: 5px;">
                                <i class="fa fa-exclamation-triangle"></i> Audio playback not supported. 
                                <a href="${url}" target="_blank" class="text-primary">Click to download</a>
                            </div>
                        `);
                    }
                });
                
                // Try to load the audio
                audio.load();
            });
        }

        // Function to stop all audio
        function stopAllAudio() {
            $('audio').each(function() {
                if (!this.paused) {
                    this.pause();
                    this.currentTime = 0;
                    console.log('Audio stopped and reset');
                }
            });
        }

        // Initialize audio loading when page loads
        $(document).ready(function() {
            setTimeout(function() {
                handleAudioLoading();
            }, 1000);
            
            // Add play event handler to stop other audio when one starts playing
            $(document).on('play', 'audio', function() {
                $('audio').not(this).each(function() {
                    if (!this.paused) {
                        this.pause();
                        this.currentTime = 0;
                    }
                });
            });
            
            // Stop all audio when page is about to unload (navigation)
            $(window).on('beforeunload', function() {
                stopAllAudio();
            });

            // Auto-fill staff name and number when vendor/freelancer is selected
            $('#vendor_id, #edit_vendor_id').on('change', function() {
                const selectedOption = $(this).find('option:selected');
                const selectedValue = selectedOption.val();
                const vendorName = selectedOption.data('name') || '';
                const contactNo = selectedOption.data('contact') || '';
                
                // Check if it's a vendor (has [ vendor ] suffix) or freelancer
                const isVendor = vendorName.includes('[ vendor ]');
                const isFreelance = !isVendor && vendorName !== '';
                
                console.log('Vendor selection changed:', {
                    selectedValue: selectedValue,
                    vendorName: vendorName,
                    contactNo: contactNo,
                    isVendor: isVendor,
                    isFreelance: isFreelance
                });
                
                // Get the form context (add or edit)
                const isEditForm = $(this).attr('id') === 'edit_vendor_id';
                const staffNameField = isEditForm ? '#edit_staff_name' : '#staff_name';
                const staffNumberField = isEditForm ? '#edit_staff_number' : '#staff_number';
                
                if (isFreelance && vendorName && vendorName !== '') {
                    // Auto-fill for freelancers
                    console.log('Auto-filling for freelancer:', vendorName);
                    $(staffNameField).val(vendorName);
                    if (contactNo && contactNo !== 'N/A') {
                        $(staffNumberField).val(contactNo);
                    }
                } else {
                    // Clear fields for vendors or when no selection
                    console.log('Clearing fields for vendor or no selection');
                    $(staffNameField).val('');
                    $(staffNumberField).val('');
                }
            });
        });
    </script>
@endsection
