@extends('operation.layouts.app')

@section('title', 'Operation Leads')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
    <style>
        /* Main container and card styling */
        .content-wrapper {
            background: #f8f9fa;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            padding: 15px;
        }

        .card-header {
            background: #fff;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .card-body {
            padding: 0;
        }

        /* Table styling to match the image */
        .table.dataTable {
            border-collapse: collapse !important;
            width: 100% !important;
            background: #fff;
        }

        .table thead th {
            color: #888;
            font-weight: 500;
            font-size: 11px;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
            letter-spacing: 0.02em;
            padding: 0.6rem 0.4rem;
            text-transform: uppercase;
            white-space: nowrap;
            text-align: center;
        }

        .table tbody td {
            color: #444;
            font-size: 12px;
            border-top: 1px solid #f0f0f0;
            padding: 0.6rem 0.4rem;
            vertical-align: middle;
            text-align: center;
        }

        .table tbody tr:first-child td {
            border-top: none;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-responsive {
            /* Remove default table-responsive styling for scroll */
        }

        /* Custom Table Scroll CSS */
        .custom-table-scroll-x {
            width: 100%;
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: none;
            background: #fff;
            margin-bottom: 0;
        }
        .custom-table-scroll-x table {
            min-width: 1400px;
            width: 100%;
            table-layout: auto;
        }
        table thead, table tfoot {
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 2;
        }
        table tbody {
            display: block;
            max-height: 60vh;
            overflow-y: auto;
            overflow-x: hidden !important;
            width: 100%;
        }
        table thead, table tfoot, table tbody tr {
            display: table;
            width: 100%;
            table-layout: auto;
        }

        /* Contact No column styling */
        .contact-cell {
            min-width: 200px;
            white-space: nowrap !important;
            overflow: visible !important;
        }
        .contact-icons {
            color: #27ae60;
            font-size: 1rem;
            margin-left: 6px;
            cursor: pointer;
            vertical-align: middle;
        }

        /* Customer name column styling */
        .customer-cell {
            text-align: left !important;
            min-width: 180px;
            max-width: 200px;
        }

        /* Lead ID column styling */
        .lead-id-cell {
            text-align: left !important;
            min-width: 130px;
            font-weight: 500;
        }

        /* Date/Time column styling */
        .datetime-cell {
            text-align: left !important;
            min-width: 160px;
            font-size: 11px;
        }

        /* Executive column styling */
        .executive-cell {
            text-align: left !important;
            min-width: 120px;
        }

        /* Location column styling */
        .location-cell {
            text-align: left !important;
            min-width: 120px;
        }

        /* Query column styling */
        .query-cell {
            text-align: center !important;
            min-width: 120px;
        }

        /* Status column styling */
        .status-cell {
            text-align: center !important;
            min-width: 120px;
        }

        /* Last Call column styling */
        .last-call-cell {
            text-align: center !important;
            min-width: 100px;
        }

        /* V. Name column styling */
        .vname-cell {
            text-align: left !important;
            min-width: 120px;
        }

        /* A/F Vendors column styling */
        .vendors-cell {
            text-align: center !important;
            min-width: 120px;
        }

        /* On/Stop column styling */
        .ongoing-cell {
            text-align: center !important;
            min-width: 100px;
        }

        /* Action column styling */
        .action-cell {
            text-align: center !important;
            min-width: 100px;
        }

        /* Status & Query Badge Styling */
        .status-badge {
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 6px 16px;
            border: 1.5px solid transparent;
            display: inline-block;
            min-width: 100px;
            text-align: center;
            text-transform: capitalize;
        }

        /* --- Status Colors --- */
        .badge-profile-shared {
            color: #3498db;
            border-color: #3498db !important;
            background: #a0d6fb !important;
        }

        .badge-closed {
            color: #1abc9c;
            border-color: #1abc9c !important;
            background: #9ff9e7 !important;
        }

        .badge-follow-up {
            color: #f39c12;
            border-color: #f39c12 !important;
            background: #ecce9e !important;
        }

        .badge-inactive {
            color: #e74c3c;
            border-color: #e74c3c !important;
            background: #ffb2aa !important;
        }

        /* --- Query Colors --- */
        .badge-attendant {
            color: #9b59b6;
            border-color: #9b59b6 !important;
            background: #eab6ff !important;
        }

        .badge-nurse {
            color: #e91e63;
            border-color: #e91e63 !important;
            background: rgb(243, 180, 201) !important;
        }

        .badge-on-call-nurse {
            color: #009688;
            border-color: #009688 !important;
            background: rgb(168, 247, 243) !important;
        }

        .badge-medicine {
            color: #3f51b5;
            border-color: #3f51b5 !important;
            background: #a2aef7 !important;
        }

        .badge-job-request {
            color: #8bc34a;
            border-color: #8bc34a !important;
            background: #d1faa1 !important;
        }

        .badge-doctor {
            color: #03a9f4;
            border-color: #03a9f4 !important;
            background: #b3e3fa !important;
        }

        .badge-physiotherapy {
            color: #ff9800;
            border-color: #ff9800 !important;
            background: rgb(250, 212, 150) !important;
        }

        .badge-default {
            color: #9e9e9e;
            border-color: #9e9e9e !important;
            background: #eccccc !important;
        }

        /* Action Button Styling */
        .action-btn {
            background: #f0f2f5;
            border: 1px solid #e0e2e5;
            border-radius: 8px;
            color: #606770;
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            font-size: 1rem;
        }

        .action-btn:hover {
            background: #e4e6e9;
            color: #000;
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

        /* Vendor Count Button Styling */
        .vendor-count-btn {
            background: linear-gradient(135deg, #F7941D, #e67e22);
            border: none;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .vendor-count-btn:hover {
            background: linear-gradient(135deg, #e67e22, #d35400);
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            color: white;
        }

        .vendor-count-btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(247, 148, 29, 0.25);
        }

        /* Vendor Details Modal Styling */
        .vendor-details-modal {
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            border: none;
            overflow: hidden;
        }

        .vendor-modal-header {
            background: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
            color: white;
            border: none;
            padding: 1.5rem 2rem;
            position: relative;
        }

        .vendor-modal-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .vendor-icon {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .vendor-modal-body {
            background: #f8f9fa;
            padding: 2rem;
            min-height: 400px;
        }

        .vendor-modal-footer {
            background: white;
            border-top: 1px solid #e9ecef;
            padding: 1rem 2rem;
        }

        /* Lead Info Card */
        .lead-info-card {
            background: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .lead-info-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .lead-info-card h6 {
            color: rgba(255,255,255,0.9);
            font-weight: 600;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }

        .lead-info-card .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .lead-info-card .info-item i {
            width: 20px;
            margin-right: 0.75rem;
            opacity: 0.8;
        }

        /* Vendor Cards */
        .vendor-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            opacity: 0;
            transform: translateY(20px);
            animation: slideInUp 0.5s ease forwards;
        }

        @keyframes slideInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .vendor-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
            transition: width 0.3s ease;
        }

        .vendor-card:hover {
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            border-color: #F7941D;
        }

        .vendor-card:hover::before {
            width: 6px;
        }

        .vendor-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #F7941D 0%, #e67e22 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            box-shadow: 0 4px 12px rgba(247, 148, 29, 0.3);
        }

        .vendor-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .vendor-type-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vendor-type-badge.vendor {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
        }

        .vendor-type-badge.freelancer {
            background: linear-gradient(135deg, #f39c12, #e67e22);
            color: white;
        }

        .vendor-contact {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .vendor-contact i {
            color: #F7941D;
            margin-right: 0.5rem;
        }

        .call-btn-vendor {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .call-btn-vendor:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
            color: white;
        }

        .call-btn-vendor:disabled {
            background: #6c757d;
            transform: none;
            box-shadow: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }

        .empty-state h5 {
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Loading State */
        .loading-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #6c757d;
        }

        .loading-state i {
            font-size: 2rem;
            color: #F7941D;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Action Buttons Styling */
        .action-buttons {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .action-buttons-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }

        .action-btn.btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .action-btn.btn-success:hover {
            background: linear-gradient(135deg, #20c997, #28a745);
            color: white;
        }

        .action-btn.btn-warning {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: white;
        }

        .action-btn.btn-warning:hover {
            background: linear-gradient(135deg, #fd7e14, #ffc107);
            color: white;
        }

        .action-btn.btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .action-btn.btn-danger:hover {
            background: linear-gradient(135deg, #c82333, #dc3545);
            color: white;
        }

        /* Status Badge Styling */
        .badge-success {
            background-color: #28a745 !important;
            color: white !important;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }

        .badge-warning {
            background-color: #ffc107 !important;
            color: #212529 !important;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }

        .badge-danger {
            background-color: #dc3545 !important;
            color: white !important;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .vendor-modal-body {
                padding: 1rem;
            }

            .vendor-card {
                padding: 1rem;
            }

            .vendor-modal-header {
                padding: 1rem;
            }

            .action-buttons {
                justify-content: space-between;
                gap: 0.5rem;
            }

            .action-buttons-group {
                gap: 0.3rem;
            }

            .action-btn {
                width: 28px;
                height: 28px;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 576px) {
            .action-btn {
                width: 26px;
                height: 26px;
                font-size: 0.65rem;
            }
        }
    </style>

<!-- Vendor Contact Tooltip -->
<div id="vendorTooltip" class="vendor-tooltip"></div>

@endsection
@section('navbar-right-links')
<li class="nav-item">
    <a class="nav-link filter-toggle" title="Filters" data-widget="control-sidebar"
        href="javascript:void(0);" role="button">
        <i class="fas fa-filter"></i>
    </a>
</li>
@endsection

@section('content')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="m-0">Operation Leads</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#createOperationLeadModal">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="custom-table-scroll-x">
                            <table id="operation-leads-table" class="table text-sm">
                                <thead class="sticky_head bg-light">
                                    <tr>
                                        <th>Lead Id</th>
                                        <th>Date/Time</th>
                                        <th>Executive</th>
                                        <th>Customer</th>
                                        <th class="contact-cell">Contact No</th>
                                        <th>Location</th>
                                        <th>Query</th>
                                        <th>Status</th>
                                        <th>Last Call</th>
                                        <th>V. Name</th>
                                        <th>A/F Vendors</th>
                                        <th>On/Stop</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via DataTables -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Create Operation Lead Modal -->
    <div class="modal fade" id="createOperationLeadModal" tabindex="-1" role="dialog"
        aria-labelledby="createOperationLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="createOperationLeadModalLabel">Add Operation Lead</h5>
                    <button type="button" class="btn" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="createOperationLeadForm" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="date_time" class="form-label">Date/Time</label>
                                    <input type="datetime-local" class="form-control" id="date_time" name="date_time"
                                        required>
                                </div>
                                <div class="form-group mb-3 d-none">
                                    <label for="executive" class="form-label">Executive</label>
                                    <input type="text" class="form-control" id="executive" name="executive"
                                        value="{{ Auth::user()->id }}" readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="customer_name" class="form-label">Customer Name</label>
                                    <input type="text" class="form-control" id="customer_name" name="customer_name"
                                        required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="contact_no" class="form-label">Contact No</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="contact_no" name="contact_no"
                                            required>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <select class="form-control" id="location" name="location" required>
                                        <option value="Delhi">Delhi</option>
                                        <option value="Noida">Noida</option>
                                        <option value="Gurgaon">Gurgaon</option>
                                        <option value="Faridabad">Faridabad</option>
                                        <option value="Gaziabad">Gaziabad</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address">
                                </div>

                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="query" class="form-label">Query</label>
                                    <select class="form-control" id="query" name="query" required onchange="toggleQueryRemark()">
                                        <option value="">Select Query</option>
                                        @foreach ($services as $service)
                                            <option value="{{ $service->name }}">{{ $service->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mb-3 d-none" id="query_remark_group">
                                    <label for="query_remark" class="form-label">Query Remark</label>
                                    <input type="text" class="form-control" id="query_remark" name="query_remark" placeholder="Enter remark for selected query">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control" id="status" name="status" required
                                        onchange="toggleStatusRemark()">
                                        <option value="profile">Profile</option>
                                        <option value="profile pending">Profile Pending</option>
                                        <option value="profile shared">Profile Shared</option>
                                        <option value="closed">Closed</option>
                                        <option value="follow up">Follow Up</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="price issue">Price Issue</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3 d-none" id="closed_remark_group">
                                    <label for="closed_remark" class="form-label">Closed Remark</label>
                                    <input type="text" class="form-control" id="closed_remark"
                                        name="closed_remark" placeholder="Enter remark for closed status">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="shift_type" class="form-label">Shift Type</label>
                                    <select class="form-control" id="shift_type" name="shift_type">
                                        <option value="">Select Shift Type</option>
                                        <option value="12hr">12hr</option>
                                        <option value="24hr">24hr</option>
                                        <option value="both">Both</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3 d-none" id="price_issue_remark_group">
                                    <label for="price_issue_remark" class="form-label">Price Issue Remark</label>
                                    <input type="text" class="form-control" id="price_issue_remark"
                                        name="price_issue_remark" placeholder="Enter remark for price issue status">
                                </div>
                                <div class="form-group mb-3 d-none" id="inactive_remark_group">
                                    <label for="inactive_remark" class="form-label">Inactive Remark</label>
                                    <input type="text" class="form-control" id="inactive_remark"
                                        name="inactive_remark" placeholder="Enter remark for inactive status">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="patient_name" class="form-label">Patient Name</label>
                                    <input type="text" class="form-control" id="patient_name" name="patient_name">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="age" name="age">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="closed_rate" class="form-label">Closed Rate</label>
                                    <input type="number" step="0.01" class="form-control" id="closed_rate"
                                        name="closed_rate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="vendor_name" class="form-label">Vendor / Freelance Staff</label>
                                    <select class="form-control" id="vendor_id" name="vendor_id" required>
                                        <option value="">Select Vendor / Freelance Staff</option>
                                        @foreach ($vendors as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Vendors and active freelance staff will be filtered based on selected location and query</small>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="staff_name" class="form-label">Staff Name</label>
                                    <input type="text" class="form-control" id="staff_name" name="staff_name" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="vendor_closed_rate" class="form-label">Vendor Closed Rate</label>
                                    <input type="number" step="0.01" class="form-control" id="vendor_closed_rate"
                                        name="vendor_closed_rate">
                                </div>
                                <div class="form-group mb-3">
                                    <label for="payment_plan" class="form-label">Payment Plan</label>
                                    <select class="form-control" id="payment_plan" name="payment_plan" required>
                                        <option value="3 Days">3 Days</option>
                                        <option value="1 Week">1 Week</option>
                                        <option value="15 Days">15 Days</option>
                                        <option value="1 Month">1 Month</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3 d-none" id="ongoing_stopped_group">
                                    <label for="ongoing_stopped" class="form-label">Ongoing/Stopped</label>
                                    <select class="form-control" id="ongoing_stopped" name="ongoing_stopped">
                                        <option value="ongoing">Ongoing</option>
                                        <option value="stopped">Stopped</option>
                                    </select>
                                </div>
                                <div class="form-group mb-3 d-none" id="stopped_details_group">
                                    <label for="stopped_date_time" class="form-label">Stopped Date/Time</label>
                                    <input type="datetime-local" class="form-control" id="stopped_date_time"
                                        name="stopped_date_time">
                                    <label for="stopped_remark" class="form-label mt-2">Stopped Remark</label>
                                    <input type="text" class="form-control" id="stopped_remark"
                                        name="stopped_remark">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveOperationLead">Save Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Operation Lead Modal -->
    <div class="modal fade" id="viewOperationLeadModal" tabindex="-1" aria-labelledby="viewOperationLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewOperationLeadModalLabel">Operation Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewOperationLeadBody">
                    <!-- Details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Operation Lead Modal -->
    <div class="modal fade" id="editOperationLeadModal" tabindex="-1" aria-labelledby="editOperationLeadModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editOperationLeadModalLabel">Edit Operation Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editOperationLeadForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit_lead_id" name="id">
                        <div class="row" id="editOperationLeadFields">
                            <!-- Fields will be loaded here -->
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

    <aside class="control-sidebar control-sidebar-dark" style="display: none;">
        <div class="p-3 control-sidebar-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Filters</h5>
                <button type="button" class="btn btn-sm btn-outline-light control-sidebar-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>            <hr class="mb-2">
            <form id="filters-form" method="post">
                @csrf
                <div class="accordion text-sm" id="accordionExample">
                    <div class="accordion-item">
                        <div class="accordion text-sm" id="accordionExample">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse3"
                                        aria-expanded="true" aria-controls="collapse3">Vendors</button>
                                </h2>
                                <div id="collapse3"
                                    class="accordion-collapse collapse {{ isset($filter_params['vendor']) ? 'show' : '' }}"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach ($vendors as $vendor)
                                            <div class="custom-control custom-checkbox my-1">
                                                <input class="custom-control-input" type="checkbox"
                                                    id="team_member_{{ $vendor->id }}" name="vendor[]"
                                                    value="{{ $vendor->id }}"
                                                    {{ isset($filter_params['vendor']) && in_array($vendor->id, $filter_params['vendor']) ? 'checked' : '' }}>
                                                <label for="team_member_{{ $vendor->id }}"
                                                    class="custom-control-label">{{ $vendor->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapse2"
                                        aria-expanded="true" aria-controls="collapse2">Location</button>
                                </h2>
                                <div id="collapse2"
                                    class="accordion-collapse collapse {{ isset($filter_params['location']) ? 'show' : '' }}"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach ($locations as $location)
                                            <div class="custom-control custom-checkbox my-1">
                                                <input class="custom-control-input" type="checkbox"
                                                    id="team_member_{{ $location->name }}" name="location[]"
                                                    value="{{ $location->name }}"
                                                    {{ isset($filter_params['location']) && in_array($location->name, $filter_params['location']) ? 'checked' : '' }}>
                                                <label for="team_member_{{ $location->name }}"
                                                    class="custom-control-label">{{ $location->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapseQuery"
                                        aria-expanded="true" aria-controls="collapseQuery">Query</button>
                                </h2>
                                <div id="collapseQuery" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach ($services as $service)
                                            <div class="custom-control custom-checkbox my-1">
                                                <input class="custom-control-input" type="checkbox"
                                                    id="filter_query_{{ str_replace(' ', '_', strtolower($service->name)) }}"
                                                    name="query_filter[]" value="{{ $service->name }}"
                                                    {{ isset($filter_params['query_filter']) && in_array($service->name, $filter_params['query_filter']) ? 'checked' : '' }}>
                                                <label for="filter_query_{{ str_replace(' ', '_', strtolower($service->name)) }}"
                                                    class="custom-control-label">{{ $service->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus"
                                        aria-expanded="true" aria-controls="collapseStatus">Status</button>
                                </h2>
                                <div id="collapseStatus" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach (['follow-up', 'future prospect', 'prospect', 'no response','price issue', 'duplicate', 'spam'] as $status)
                                            <div class="custom-control custom-checkbox my-1">
                                                <input class="custom-control-input" type="checkbox"
                                                    id="filter_status_{{ str_replace(' ', '_', $status) }}"
                                                    name="status[]" value="{{ $status }}"
                                                    {{ isset($filter_params['status']) && in_array($status, $filter_params['status']) ? 'checked' : '' }}>
                                                <label for="filter_status_{{ str_replace(' ', '_', $status) }}"
                                                    class="custom-control-label">{{ ucfirst($status) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                        type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseongoing_stopped" aria-expanded="true"
                                        aria-controls="collapseongoing_stopped">Ongoing/Stopped</button>
                                </h2>
                                <div id="collapseongoing_stopped" class="accordion-collapse collapse"
                                    data-bs-parent="#accordionExample">
                                    <div class="accordion-body pl-2 pb-4">
                                        @foreach (['stopped', 'ongoing'] as $ongoing_stopped)
                                            <div class="custom-control custom-checkbox my-1">
                                                <input class="custom-control-input" type="checkbox"
                                                    id="filter_ongoing_stopped_{{ str_replace(' ', '_', $ongoing_stopped) }}"
                                                    name="ongoing_stopped[]" value="{{ $ongoing_stopped }}"
                                                    {{ isset($filter_params['ongoing_stopped']) && in_array($ongoing_stopped, $filter_params['ongoing_stopped']) ? 'checked' : '' }}>
                                                <label
                                                    for="filter_ongoing_stopped_{{ str_replace(' ', '_', $ongoing_stopped) }}"
                                                    class="custom-control-label">{{ ucfirst($ongoing_stopped) }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="my-5">
                        <button type="submit" class="btn btn-sm btn-block"
                            style="background-color: var(--wb-renosand);">Apply</button>
                            <button type="button" class="btn btn-sm btn-block btn-secondary mt-2" onclick="window.location.reload();">Reset</button>

                        </div>
            </form>
        </div>
    </aside>
    <!-- Vendor Details Modal -->
    <div class="modal fade" id="vendorDetailsModal" tabindex="-1" aria-labelledby="vendorDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content vendor-details-modal">
                <div class="modal-header vendor-modal-header">
                    <div class="d-flex align-items-center">
                        <div class="vendor-icon me-3">
                            <i class="fas fa-users" style="color: #f38e1f;"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0" id="vendorDetailsModalLabel" style="color: #f38e1f;">Available Vendors & Freelancers</h5>
                            <small class="text-dark opacity-75">Matching professionals for your lead</small>
                        </div>
                    </div>
                    {{-- <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close">&times;</button> --}}
                </div>
                <div class="modal-body vendor-modal-body">
                    <div id="vendorDetailsContent">
                        <!-- Vendor details will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer vendor-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    @include('whatsapp.chat')
@endsection

@section('footer-script')
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
    <script>
        function makeCall(customerNumber) {
            if (!confirm('Are you sure you want to make this call?')) return;
            const $btn = $(event.target).closest('.call-btn');
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

            $.ajax({
                url: `/call-outbound/${customerNumber}`,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        toastr.success('Call initiated successfully');
                    } else {
                        toastr.error(response.message || 'Failed to initiate call');
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error initiating call';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    toastr.error(errorMessage);
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-phone"></i>');
                }
            });
        }

        function handle_whatsapp_msg(id) {
            const elementToUpdate = document.querySelector(`#what_id-${id}`);
            if (elementToUpdate) {
                elementToUpdate.outerHTML =
                    `<i class="fab fa-whatsapp" onclick="handle_whatsapp_msg(${id})" style="font-size: 25px; color: green;"></i>`;
            }
            const form_title = document.querySelector(`#form_title_modal`);
            form_title.innerHTML = `Whatsapp Messages of ${id}`;
            const manageWhatsappChatModal = new bootstrap.Modal(document.getElementById('wa_msg'));
            wamsg(id);
            manageWhatsappChatModal.show();
            const wa_status_url = `{{ route('whatsapp_chat.status') }}`;
            const wa_status_data = {
                mobile: id
            };
            fetch(wa_status_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(wa_status_data),
                })
                .then(response => response.json())
                .then(data => {})
                .catch((error) => {});
        }

        $(function() {
            // Helper function to create slug for CSS classes
            function slugify(text) {
                if (!text) return 'default';
                return text.toString().toLowerCase()
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/[^\w\-]+/g, '') // Remove all non-word chars
                    .replace(/\-\-+/g, '-'); // Replace multiple - with single -
            }

            // DataTable initialization
            var table = $('#operation-leads-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('operation.operation_leads.getLeads') }}',
                    data: function(d) {
                        // Remove all previous filter params
                        $('#filters-form').find('input, select').each(function() {
                            delete d[this.name];
                        });
                        // Add new filter params
                        var formData = $('#filters-form').serializeArray();
                        $.each(formData, function(i, obj) {
                            if (d[obj.name] !== undefined) {
                                if (!Array.isArray(d[obj.name])) {
                                    d[obj.name] = [d[obj.name]];
                                }
                                d[obj.name].push(obj.value);
                            } else {
                                d[obj.name] = obj.value;
                            }
                        });
                    },
                    error: function(xhr, error, thrown) {
                        toastr.error('Error loading leads data. Please try again.');
                    }
                },
                responsive: false, // Plus button hatane ke liye
                scrollX: true, // Horizontal scroll add karne ke liye
                scrollCollapse: true,
                fixedColumns: false,
                columns: [{
                        data: 'lead_id',
                        name: 'lead_id',
                        className: 'lead-id-cell'
                    },
                    {
                        data: 'date_time',
                        name: 'date_time',
                        className: 'datetime-cell'
                    },
                    {
                        data: 'executive_name',
                        name: 'executive_name',
                        className: 'executive-cell'
                    },
                    {
                        data: 'customer_name',
                        name: 'customer_name',
                        className: 'customer-cell',
                        render: function(data, type, row) {
                            if (row.patient_name) {
                                return `${data} (${row.patient_name})`;
                            }
                            return data;
                        }
                    },
                    {
                        data: 'contact_no',
                        name: 'contact_no',
                        className: 'contact-cell',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex align-items-center justify-content-center">
                                    <span>${data}</span>
                                    <button onclick="makeCall('${data}')" class="btn btn-sm call-btn" title="Call">
                                        <i class="fas fa-phone contact-icons"></i>
                                    </button>
                                    <i class="fab fa-whatsapp contact-icons" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" title="WhatsApp"></i>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'location',
                        name: 'location',
                        className: 'location-cell'
                    },
                    {
                        data: 'query',
                        name: 'query',
                        className: 'query-cell',
                        render: function(data, type, row) {
                            if (!data) return '<span class="status-badge badge-default">-</span>';
                            return `<span class="status-badge badge-${slugify(data)}">${data}</span>`;
                        }
                    },
                    {
                        data: 'status',
                        name: 'status',
                        className: 'status-cell',
                        render: function(data, type, row) {
                            if (!data) return '<span class="status-badge badge-default">-</span>';
                            return `<span class="status-badge badge-${slugify(data)}">${data}</span>`;
                        }
                    },
                    {
                        data: 'last_call_status',
                        name: 'last_call_status',
                        className: 'last-call-cell',
                        render: function(data, type, row) {
                            if (!data) return '<span class="text-muted">-</span>';
                            return `<span style="color: #000; font-weight: 500;">${data}</span>`;
                        }
                    },
                    {
                        data: 'vendor_name',
                        name: 'vendor_name',
                        className: 'vname-cell'
                    },
                    {
                        data: 'available_vendors_count',
                        name: 'available_vendors_count',
                        className: 'vendors-cell',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'ongoing_stopped',
                        name: 'ongoing_stopped',
                        className: 'ongoing-cell'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'action-cell',
                        render: function(data, type, row) {
                            return `
                        <div class="btn-group" role="group">
                            <a href="{{ url('operation/operation-leads') }}/${row.id}" class="btn action-btn view-btn" data-id="${row.id}" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button class="btn action-btn edit-btn" data-id="${row.id}" title="Edit">
                                <i class="fas fa-pen"></i>
                            </button>
                        </div>
                    `;
                        }
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 25
            });

            $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Save Operation Lead AJAX
            $('#saveOperationLead').on('click', function() {
                var form = $('#createOperationLeadForm')[0];
                var formData = new FormData(form);

                $.ajax({
                    url: '{{ route('operation.operation_leads.store') }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert(response.message);
                        // $('#createOperationLeadModal').modal('hide');
                        // $('#createOperationLeadForm')[0].reset();
                        if ($.fn.DataTable.isDataTable('#operation-leads-table')) {
                            $('#operation-leads-table').DataTable().ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr
                            .responseJSON.message : 'Could not save lead.'));
                    }
                });
            });


            // Edit Operation Lead
            $(document).on('click', '.edit-btn', function() {
                var leadId = $(this).data('id');
                console.log('Editing lead ID:', leadId);

                $.get('{{ url("operation/operation-leads") }}/' + leadId + '/edit', function(lead) {
                    console.log('Edit data received:', lead);

                    // Define editable fields for coordinate role
                    const editableFields = [
                        'query', 'query_remark', 'status', 'shift_type', 'price_issue_remark', 'inactive_remark', 'closed_remark', 'patient_name', 'age', 'closed_rate',
                        'vendor_name', 'staff_name',
                    ];

                    let fields = '';
                    for (const [key, value] of Object.entries(lead)) {
                        if (!editableFields.includes(key)) continue;

                        if (key === 'query') {
                            let queryOptions = '';
                            @foreach ($services as $service)
                                queryOptions += `<option value='{{ $service->name }}' ${value==='{{ $service->name }}'?'selected':''}>{{ $service->name }}</option>`;
                            @endforeach
                            fields += `<div class='col-md-6 mb-3'><label class='form-label'>Query</label><select class='form-control' name='query' required onchange="toggleEditQueryRemark()">${queryOptions}</select></div>`;
                        } else if (key === 'query_remark') {
                            fields += `<div class='col-md-6 mb-3 d-none' id='edit_query_remark_group'><label class='form-label'>Query Remark</label><input type='text' class='form-control' name='query_remark' value='${value || ''}' placeholder='Enter remark for selected query'></div>`;
                        } else if (key === 'status') {
                            fields +=
                                `<div class='col-md-6 mb-3'><label class='form-label'>Status</label><select class='form-control' name='status' required onchange="toggleEditStatusRemark()"><option value='profile' ${value==='profile'?'selected':''}>Profile</option><option value='profile pending' ${value==='profile pending'?'selected':''}>Profile Pending</option><option value='profile shared' ${value==='profile shared'?'selected':''}>Profile Shared</option><option value='closed' ${value==='closed'?'selected':''}>Closed</option><option value='follow up' ${value==='follow up'?'selected':''}>Follow Up</option><option value='inactive' ${value==='inactive'?'selected':''}>Inactive</option><option value='price issue' ${value==='price issue'?'selected':''}>Price Issue</option></select></div>`;
                        } else if (key === 'shift_type') {
                            fields += `<div class='col-md-6 mb-3'><label class='form-label'>Shift Type</label><select class='form-control' name='shift_type'><option value=''>Select Shift Type</option><option value='12hr' ${value==='12hr'?'selected':''}>12hr</option><option value='24hr' ${value==='24hr'?'selected':''}>24hr</option><option value='both' ${value==='both'?'selected':''}>Both</option></select></div>`;
                        } else if (key === 'price_issue_remark') {
                            fields +=
                                `<div class='col-md-6 mb-3 d-none' id='edit_price_issue_remark_group'><label class='form-label'>Price Issue Remark</label><input type='text' class='form-control' name='price_issue_remark' value='${value || ''}' placeholder='Enter remark for price issue status'></div>`;
                        } else if (key === 'inactive_remark') {
                            fields +=
                                `<div class='col-md-6 mb-3 d-none' id='edit_inactive_remark_group'><label class='form-label'>Inactive Remark</label><input type='text' class='form-control' name='inactive_remark' value='${value || ''}' placeholder='Enter remark for inactive status'></div>`;
                        } else if (key === 'closed_remark') {
                            fields +=
                                `<div class='col-md-6 mb-3 d-none' id='edit_closed_remark_group'><label class='form-label'>Closed Remark</label><input type='text' class='form-control' name='closed_remark' value='${value || ''}' placeholder='Enter remark for closed status'></div>`;
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

                    // Add readonly fields for reference
                    fields = `
                <div class='col-12 mb-3'>
                    <div class='alert alert-info'>
                        <strong>Reference Information:</strong><br>
                        <strong>Customer:</strong> ${lead.customer_name || ''} |
                        <strong>Contact:</strong> ${lead.contact_no || ''} |
                        <strong>Executive:</strong> ${lead.executive || ''} |
                        <strong>Date/Time:</strong> ${lead.date_time || ''}
                    </div>
                </div>
            ` + fields;

                    // Add Sales and Manager Query Remarks if available
                    if (lead.related_lead && lead.related_lead.query_remarks) {
                        fields += `
                <div class='col-12 mb-3'>
                    <div class='alert'>
                        <strong><i class='fas fa-comment me-2'></i>Sales/Manager Query Remarks:</strong><br>
                        <div class='mt-2 p-2 bg-light rounded'>
                            ${lead.related_lead.query_remarks}
                        </div>
                    </div>
                </div>
            `;
                    }

                    $('#edit_lead_id').val(lead.id);
                    $('#editOperationLeadFields').html(fields);

                    var modal = new bootstrap.Modal(document.getElementById(
                        'editOperationLeadModal'));
                    modal.show();

                    // Show/hide status remark field based on current status
                    setTimeout(function() {
                        toggleEditStatusRemark();
                        toggleEditQueryRemark();
                    }, 100);
                }).fail(function(xhr, status, error) {
                    console.error('Edit error:', xhr.responseText);
                    alert('Error loading lead for editing: ' + xhr.responseText);
                });
            });

            // Update Operation Lead
            $('#updateOperationLead').on('click', function() {
                var leadId = $('#edit_lead_id').val();
                var form = $('#editOperationLeadForm')[0];
                var formData = new FormData(form);

                $.ajax({
                    url: '{{ url("operation/operation-leads") }}/' + leadId,
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
                        $('#operation-leads-table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr
                            .responseJSON.message : 'Could not update lead.'));
                    }
                });
            });

            // Delete Operation Lead
            $(document).on('click', '.delete-btn', function() {
                if (!confirm('Are you sure you want to delete this lead?')) return;
                var leadId = $(this).data('id');
                $.ajax({
                    url: '{{ url("operation/operation-leads") }}/' + leadId,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert(response.message);
                        $('#operation-leads-table').DataTable().ajax.reload();
                    },
                    error: function(xhr) {
                        alert('Error: ' + (xhr.responseJSON && xhr.responseJSON.message ? xhr
                            .responseJSON.message : 'Could not delete lead.'));
                    }
                });
            });
        });

        // Dropdown toggle logic
        window.toggleDropdown = function(btn) {
            document.querySelectorAll('.action-dropdown').forEach(function(drop) {
                if (drop.contains(btn)) {
                    drop.classList.toggle('open');
                } else {
                    drop.classList.remove('open');
                }
            });
        };
        document.addEventListener('click', function() {
            document.querySelectorAll('.action-dropdown').forEach(function(drop) {
                drop.classList.remove('open');
            });
        });
        document.querySelectorAll('.action-dropdown-menu').forEach(function(menu) {
            menu.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        function callNumber() {
            var number = document.getElementById('contact_no').value;
            if (number) window.open('tel:' + number, '_blank');
        }

        function whatsappChat() {
            var number = document.getElementById('contact_no').value;
            if (number) window.open('https://wa.me/' + number, '_blank');
        }

        function toggleStatusRemark() {
            var status = document.getElementById('status').value;
            document.getElementById('price_issue_remark_group').classList.toggle('d-none', status !== 'price issue');
            document.getElementById('inactive_remark_group').classList.toggle('d-none', status !== 'inactive');
            document.getElementById('closed_remark_group').classList.toggle('d-none', status !== 'closed');
            document.getElementById('ongoing_stopped_group').classList.toggle('d-none', status !== 'closed');
        }

        function toggleQueryRemark() {
            var query = document.getElementById('query').value;
            document.getElementById('query_remark_group').classList.toggle('d-none', !query || query === '');
        }

        function toggleEditStatusRemark() {
            var statusSelect = document.querySelector('#editOperationLeadModal select[name="status"]');
            var priceIssueRemarkGroup = document.getElementById('edit_price_issue_remark_group');
            var inactiveRemarkGroup = document.getElementById('edit_inactive_remark_group');
            var closedRemarkGroup = document.getElementById('edit_closed_remark_group');
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
            }
        }

        function toggleEditQueryRemark() {
            var querySelect = document.querySelector('#editOperationLeadModal select[name="query"]');
            var queryRemarkGroup = document.getElementById('edit_query_remark_group');
            if (querySelect && queryRemarkGroup) {
                var query = querySelect.value;
                queryRemarkGroup.classList.toggle('d-none', !query || query === '');
            }
        }
        document.getElementById('status').addEventListener('change', function() {
            toggleStatusRemark();
        });
        document.getElementById('ongoing_stopped').addEventListener('change', function() {
            var val = this.value;
            document.getElementById('stopped_details_group').classList.toggle('d-none', val !== 'stopped');
        });

        function generateInvoice() {
            // Placeholder for PDF generation logic
            alert('Invoice PDF generated (placeholder)!');
            document.getElementById('invoice').value = 'Invoice_' + new Date().toISOString().slice(0, 10) + '.pdf';
        }

        // Function to filter vendors based on location and query
        function filterVendors() {
            const location = document.getElementById('location').value;
            const query = document.getElementById('query').value;
            const vendorSelect = document.getElementById('vendor_id');

            if (location && query) {
                // Show loading state
                vendorSelect.innerHTML = '<option value="">Loading vendors...</option>';
                vendorSelect.disabled = true;

                // Fetch filtered vendors
                fetch(`{{ route('operation.operation_leads.filtered_vendors') }}?location=${encodeURIComponent(location)}&service=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            vendorSelect.innerHTML = '<option value="">Select Vendor</option>';
                            data.vendors.forEach(vendor => {
                                const option = document.createElement('option');
                                option.value = vendor.id;
                                option.textContent = vendor.name;
                                option.setAttribute('data-contact', vendor.contact_no || 'N/A');
                                option.setAttribute('data-is-freelance', vendor.is_freelance ? 'true' : 'false');
                                vendorSelect.appendChild(option);
                            });

                            if (data.vendors.length === 0) {
                                vendorSelect.innerHTML = '<option value="">No vendors available for this location and service</option>';
                            }
                        } else {
                            vendorSelect.innerHTML = '<option value="">Error loading vendors</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        vendorSelect.innerHTML = '<option value="">Error loading vendors</option>';
                    })
                    .finally(() => {
                        vendorSelect.disabled = false;
                    });
            } else {
                // Reset to all vendors if location or query is not selected
                vendorSelect.innerHTML = '<option value="">Select Vendor</option>';
                @foreach ($vendors as $vendor)
                    vendorSelect.innerHTML += '<option value="{{ $vendor->id }}" data-contact="{{ $vendor->contact_no ?: 'N/A' }}">{{ $vendor->name }}</option>';
                @endforeach
            }
        }

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
        });

        // Add event listeners for location and query changes
        document.addEventListener('DOMContentLoaded', function() {
            const locationSelect = document.getElementById('location');
            const querySelect = document.getElementById('query');

            if (locationSelect) {
                locationSelect.addEventListener('change', filterVendors);
            }
            if (querySelect) {
                querySelect.addEventListener('change', filterVendors);
            }
        });

        // Handle vendor count button click
        $(document).on('click', '.vendor-count-btn', function() {
            const leadId = $(this).data('lead-id');
            const location = $(this).data('location');
            const query = $(this).data('query');

            // Show loading state
            $('#vendorDetailsContent').html(`
                <div class="loading-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h5>Loading vendor details...</h5>
                    <p>Please wait while we fetch the available professionals</p>
                </div>
            `);
            $('#vendorDetailsModal').modal('show');

            // Fetch vendor details
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
                    } else {
                        $('#vendorDetailsContent').html(`
                            <div class="empty-state">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h5>Error Loading Details</h5>
                                <p>We encountered an error while loading vendor details. Please try again.</p>
                            </div>
                        `);
                    }
                },
                error: function(xhr) {
                    console.error('Error:', xhr);
                    $('#vendorDetailsContent').html(`
                        <div class="empty-state">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h5>Connection Error</h5>
                            <p>Unable to connect to the server. Please check your internet connection and try again.</p>
                        </div>
                    `);
                }
            });
        });

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
    </script>
@endsection
