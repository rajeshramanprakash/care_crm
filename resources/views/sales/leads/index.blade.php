@extends('sales.layouts.app')

@section('title', 'Sales Leads')

@section('header-css')
    <!-- Primary CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css">
    <!-- Fallback CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net@1.13.1/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.4.0/css/responsive.dataTables.min.css">
    <style>
        .content-wrapper {
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-body {
            padding: 20px;
        }

        /* Custom Table Scroll CSS (match admin/manager — avoid tbody display:block; it misaligns DataTables columns) */
        .custom-table-scroll-x {
            width: 100%;
            overflow-x: auto;
        }
        .custom-table-scroll-x table {
            min-width: 1400px;
            width: 100%;
            table-layout: fixed;
        }

        .table {
            margin-bottom: 0;
            width: 100%;
        }

        .table thead th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            color: #495057;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px 10px;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table tbody td {
            padding: 12px 10px;
            vertical-align: middle;
            font-size: 0.9rem;
            border-bottom: 1px solid #dee2e6;
        }

        .table tbody tr:hover {
            background-color: #f8f9fa;
            transition: all 0.2s ease;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-active {
            background-color: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-closed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons .btn {
            padding: 6px 10px;
            font-size: 0.85rem;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .action-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .editable {
            cursor: pointer;
            position: relative;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }

        .editable:hover {
            background-color: #f8f9fa;
        }

        .editable::after {
            content: '✎';
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .editable:hover::after {
            opacity: 1;
        }

        /* DataTables Custom Styling */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 15px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 6px 12px;
            margin: 0 2px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            background: #fff;
            color: #F7941D !important;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #F07F28;
            color: white !important;
            border: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e9ecef;
            border: 1px solid #dee2e6;
        }

        /* Modal Styling */
        .modal-content {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            border-radius: 10px 10px 0 0;
            padding: 15px 20px;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
            padding: 15px 20px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border-radius: 5px;
            border: 1px solid #ced4da;
            padding: 8px 12px;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }

        .btn-primary {
            background-color: #F7941D;
            border: none;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .btn-secondary {
            background-color: #6c757d;
            border: none;
            padding: 8px 16px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .custom-close {
            background: #ea8a2b;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 1;
            transition: background 0.2s;
        }
        .custom-close:hover {
            background: #d97a1a;
            color: #fff;
        }

        .themed-close {
            background: #ea8a2b !important;
            color: #fff !important;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            opacity: 1 !important;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px #ea8a2b22;
            margin-left: 10px;
        }
        .themed-close:hover, .themed-close:focus {
            background: #d97a1a !important;
            color: #fff !important;
            outline: none;
            box-shadow: 0 4px 12px #ea8a2b44;
        }
        .themed-close span {
            line-height: 1;
            font-weight: bold;
        }

        /* Table header */
        table thead th {
            color: #b0b0b0;
            font-weight: 600;
            font-size: 1rem;
            background: #fff;
            border-bottom: 2px solid #f2f2f2;
            letter-spacing: 0.01em;
            padding: 16px 10px;
        }

        /* Table body */
        table tbody td {
            color: #444;
            font-size: 1.05rem;
            background: #fff;
            border-bottom: 1px solid #f2f2f2;
            padding: 18px 10px;
            vertical-align: middle;
        }

        /* Table row hover */
        table tbody tr:hover {
            background: #fafbfc;
        }

        /* Status badges */
        .badge, .status-badge {
            border-radius: 8px !important;
            font-size: 0.7rem !important;
            font-weight: 500;
            padding: 4px 5px !important;
            border: 1.5px solid transparent;
            background: none !important;
            display: inline-block;
            text-align: center;
            vertical-align: middle;
        }

        .badge.bg-success, .badge.status-active {
            color: #1abc9c;
            border-color: #1abc9c;
            background: #eafaf6;
        }

        .badge.bg-danger, .badge.status-inactive {
            color: #ff4d4f;
            border-color: #ff4d4f;
            background: #fff0f0;
        }

        /* Action icon */
        .table .fa-pen-to-square, .table .fa-edit {
            color: #b0b0b0;
            font-size: 1.3rem;
            transition: color 0.2s;
            cursor: pointer;
        }
        .table .fa-pen-to-square:hover, .table .fa-edit:hover {
            color: #1abc9c;
        }

        /* Remove table borders for a clean look */
        .table, .table th, .table td {
            border: none !important;
        }

        /* Optional: Remove table shadow if any */
        .card, .custom-table-scroll-x {
            box-shadow: none !important;
        }

        /* Make table more compact and modern */
        table.dataTable {
            border-collapse: separate !important;
            border-spacing: 0;
            background: #fff;
            font-size: 1rem;
            border-radius: 12px;
            overflow: hidden;
        }

        table.dataTable thead th {
            color: #b0b0b0;
            font-weight: 400;
            font-size: 14px;
            background: #fff;
            border-bottom: 2px solid #f2f2f2 !important;
            letter-spacing: 0.01em;
            padding: 14px 10px;
            white-space: nowrap;
        }

        table.dataTable tbody td {
            color: #444;
            font-size: 14px;
            background: #fff;
            border-bottom: 1px solid #f2f2f2 !important;
            padding: 14px 10px;
            vertical-align: middle;
            white-space: normal;   /* Allow wrapping */
            word-break: break-word; /* Break long words if needed */
            max-width: 180px;      /* Or increase as needed */
        }

        table.dataTable tbody tr:hover {
            background: #f8f9fa;
        }

        .badge, .status-badge {
            border-radius: 8px !important;
            font-size: 1rem !important;
            font-weight: 500;
            padding: 6px 18px !important;
            border: 1.5px solid transparent;
            display: inline-block;
            text-align: center;
        }

        .badge.bg-success, .badge.status-active, .badge.bg-info {
            color: #1abc9c !important;
            border-color: #1abc9c !important;
            background: #eafaf6 !important;
        }

        .badge.bg-danger, .badge.status-inactive {
            color: #ff4d4f !important;
            border-color: #ff4d4f !important;
            background: #fff0f0 !important;
        }

        .badge.bg-info, .badge.status-followup {
            color: #3498db !important;
            border-color: #3498db !important;
            background: #eaf4fb !important;
        }

        /* Remove background, border, and padding from call and WhatsApp icons */
        .table .fa-phone,
        .table .fa-whatsapp {
            background: none !important;
            border: none !important;
            border-radius: 0 !important;
            padding: 0 !important;
            margin: 0 6px 0 0;
            color: #27ae60 !important; /* or your preferred green */
            font-size: 1.3rem;
            box-shadow: none !important;
            transition: color 0.2s;
            vertical-align: middle;
        }

        .table .fa-phone:hover,
        .table .fa-whatsapp:hover {
            color: #1abc9c !important; /* slightly lighter green on hover */
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border-radius: 6px !important;
            border: 1px solid #e0e0e0 !important;
            background: #fff !important;
            color: #888 !important;
            margin: 0 2px;
            padding: 6px 12px;
            font-weight: 500;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #fd7e14 !important;
            color: #fff !important;
            border: none !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #eafaf6 !important;
            color: #fd7e14 !important;
        }

        .dataTables_wrapper .dataTables_length select,
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 6px;
            border: 1px solid #e0e0e0;
            padding: 6px 12px;
            font-size: 1rem;
        }

        .dataTables_wrapper .dataTables_info {
            color: #b0b0b0;
            font-size: 0.95rem;
            padding-top: 10px;
        }

        /* Responsive fix for horizontal scroll */
        .custom-table-scroll-x {
            border-radius: 12px;
            box-shadow: none;
            background: #fff;
            margin-bottom: 0;
        }

        .action-btn {
            background: none;
            border: none;
            padding: 0;
            margin: 0 4px;
            cursor: pointer;
            outline: none;
            box-shadow: none;
            vertical-align: middle;
        }

        .action-btn i {
            color: #b0b0b0;
            font-size: 20px;
            transition: color 0.2s;
        }

        .action-btn.edit-btn i {
            /* Edit icon color */
            color: #b0b0b0;
        }
        .action-btn.update-btn i {
            /* Update icon color */
            color: #1abc9c;
        }
        .action-btn.delete-btn i {
            /* Delete icon color */
            color: #ff4d4f;
        }

        .action-btn:hover i {
            color: #1abc9c; /* or any highlight color you prefer */
        }
        .action-btn.delete-btn:hover i {
            color: #ff4d4f;
        }

        /* --- New, Clean Stage Badge Colors --- */
        .status-badge {
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            padding: 6px 16px;
            border: 1.5px solid transparent;
            display: inline-block;
            text-align: center;
            text-transform: capitalize;
        }

        .status-badge.status-active {
            color: #1abc9c;
            border-color: #1abc9c;
            background: #eafaf6;
        }
        .status-badge.status-inactive {
            color: #f39c12; /* Orange for Inactive */
            border-color: #f39c12;
            background: #fef9e7;
        }
        .status-badge.status-closed {
            color: #e74c3c; /* Red for Closed */
            border-color: #e74c3c;
            background: #fdf2f2;
        }
        .status-badge.status-profile-required {
            color: #3498db; /* Blue for Profile Required */
            border-color: #3498db;
            background: #eaf4fb;
        }
        .status-badge.status-default {
            color: #95a5a6; /* Gray for default */
            border-color: #95a5a6;
            background: #f8f9fa;
        }

        /* Contact No column styling */
        .contact-cell {
            min-width: 200px;
        }

        .contact-icons {
            color: #27ae60;
            font-size: 1.3rem;
            margin-right: 6px;
            cursor: pointer;
        }
    </style>
@endsection
@section('navbar-right-links')
<li class="nav-item">
    <a class="nav-link filter-toggle" title="Filters" data-widget="control-sidebar"
        href="javascript:void(0);" role="button">
        <i class="fas fa-filter"></i>
    </a>
</li>

@endsection

@section('main')
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h1 class="m-0">Sales Leads</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLeadModal">
                        <i class="fas fa-plus"></i> Add New Lead
                    </button>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <div class="custom-table-scroll-x">
                            <table id="leads-table" class="table">
        <thead>
            <tr>
                <th>Lead No</th>
                <th>Date</th>
                <th>Executive</th>
                <th>Customer</th>
                <th>P.Name</th>
                <th>P.Gender</th>
                <th class="contact-cell">Contact No</th>
                <th>Last Call</th>
                <th>Location</th>
                <th>Lead Source</th>
                <th>Query</th>
                <th>Status</th>
                <th>Stage</th>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Edit Lead Modal -->
    <div class="modal fade" id="editLeadModal" tabindex="-1" role="dialog" aria-labelledby="editLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #d97a1a">
                    <h5 class="modal-title" id="editLeadModalLabel" style="color: #ffffff">Edit Lead</h5>
                    <button type="button" class="themed-close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editLeadForm">
                        <input type="hidden" id="edit_lead_id" name="id">
                        <input type="hidden" id="edit_date" name="date">
                        <input type="hidden" id="edit_executive" name="executive" required>
                        <input type="hidden" id="edit_contact_type" name="contact_type">
                        <input type="hidden" id="edit_contact_no" name="contact_no" required>
                        <input type="hidden" id="edit_lead_source" name="lead_source" required>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="edit_customer_name" name="customer_name">
                        </div>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_patient_name" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="edit_patient_name" name="patient_name" placeholder="Enter patient name if different from customer">
                        </div>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_patient_gender" class="form-label">Patient Gender</label>
                            <select class="form-control" id="edit_patient_gender" name="patient_gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="edit_age" name="age" min="0" max="150" placeholder="Enter age">
                        </div>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_location" class="form-label">Location</label>
                            <select class="form-control location-select" id="edit_location" name="location">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3 bg-light rounded">
                            <label for="edit_query" class="form-label">Query</label>
                            <select class="form-control" id="edit_query" name="query">
                                <option value="">Select Query</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->name }}">{{ $service->name }}</option>
                                @endforeach
                                <option value="job request">Job Request</option>
                            </select>
                        </div>

                        <div class="form-group mb-3" id="edit_query_remarks_group" style="display: none;">
                            <label for="edit_query_remarks" class="form-label">Query Remarks</label>
                            <textarea class="form-control" id="edit_query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-control" id="edit_status" name="status">
                                <option value="">Select Status</option>
                                <option value="follow-up">Follow-up</option>
                                <option value="future prospect">Future Prospect</option>
                                <option value="prospect">Prospect</option>
                                <option value="no response">No Response</option>
                                <option value="price issue">Price Issue</option>
                                <option value="duplicate">Duplicate</option>
                                <option value="spam">Spam</option>
                            </select>
                        </div>

                        <!-- Status Remarks Field -->
                        @include('partials.lead-status-remarks-fields', [
                            'fieldPrefix' => 'edit',
                            'aiGenerateUrlTemplate' => url('/sales/leads/__ID__/status-remark/generate-ai'),
                        ])

                        <div class="form-group mb-3">
                            <label for="edit_stage" class="form-label">Stage</label>
                            <select class="form-control" id="edit_stage" name="stage">
                                <option value="">Select Stage</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="closed">Closed</option>
                                <option value="profile required">Profile Required</option>
                            </select>
                        </div>

                        <div class="form-group mb-3" id="edit_inactive_stage_remark_group" style="display: none;">
                            <label for="edit_inactive_stage_remark" class="form-label">Inactive Stage Remark</label>
                            <textarea class="form-control" id="edit_inactive_stage_remark" name="inactive_stage_remark" rows="3" placeholder="Enter remark when stage is Inactive (visible to Admin & Manager)"></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_shift_type" class="form-label">Shift Type</label>
                            <select class="form-control" id="edit_shift_type" name="shift_type">
                                <option value="">Select Shift Type</option>
                                <option value="12hr">12hr</option>
                                <option value="24hr">24hr</option>
                                <option value="both">Both</option>
                            </select>
                        </div>

                        <!-- Future Prospect Date Field (Hidden by default) -->
                        <div class="form-group mb-3" id="edit_future_prospect_date_group" style="display: none;">
                            <label for="edit_future_prospect_date" class="form-label">Future contact date &amp; time</label>
                            <input type="datetime-local" class="form-control" id="edit_future_prospect_date" name="future_prospect_date" step="60">
                        </div>
                        <div class="form-group mb-3" id="edit_follow_up_date_group" style="display: none;">
                            <label for="edit_follow_up_date" class="form-label">Follow-up date &amp; time</label>
                            <input type="datetime-local" class="form-control" id="edit_follow_up_date" name="follow_up_date" step="60">
                        </div>

                        <!-- Prospect Close Rate Field (Hidden by default) -->
                        <div class="form-group mb-3" id="edit_prospect_close_rate_group" style="display: none;">
                            <label for="edit_prospect_rate" class="form-label">Rate (₹)</label>
                            <input type="number" class="form-control" id="edit_prospect_rate" name="prospect_rate" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateLead">Update Lead</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Lead Modal -->
    <div class="modal fade" id="addLeadModal" tabindex="-1" role="dialog" aria-labelledby="addLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#FD7E14;">
                    <h5 class="modal-title" id="addLeadModalLabel" style="color:#fff;">Add New Lead</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color: #FD7E14; color:#fff;">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="addLeadForm">

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}">
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="executive" class="form-label">Executive</label>
                            <input type="text" class="form-control" id="executive" value="{{ Auth::user()->name }}" readonly>
                            <input type="hidden" name="executive" value="{{ Auth::id() }}" required>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="customer_name" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customer_name" name="customer_name">
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="patient_name" class="form-label">Patient Name</label>
                            <input type="text" class="form-control" id="patient_name" name="patient_name" placeholder="Enter patient name if different from customer">
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="patient_gender" class="form-label">Patient Gender</label>
                            <select class="form-control" id="patient_gender" name="patient_gender">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="age" class="form-label">Age</label>
                            <input type="number" class="form-control" id="age" name="age" min="0" max="150" placeholder="Enter age">
                        </div>


                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="contact_no" class="form-label">Contact No</label>
                            <input type="text" class="form-control" id="contact_no" name="contact_no" required>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="contact_type" class="form-label">Contact Type</label>
                            <select class="form-control" id="contact_type" name="contact_type">
                                <option value="">Select Contact Type</option>
                                <option value="call">Call</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="email">Email</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="location" class="form-label">Location</label>
                            <select class="form-control location-select" id="location" name="location">
                                <option value="">Select Location</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location->name }}" data-state="{{ $location->state ?? '' }}" data-city-name="{{ $location->name }}">{{ $location->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="lead_source" class="form-label">Lead Source</label>
                            <select class="form-control" id="lead_source" name="lead_source" required>
                                <option value="">Select Lead Source</option>
                                <option value="web">Web</option>
                                <option value="ivrs">IVRS</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="manual">Manual</option>
                                <option value="crm">CRM</option>
                                <option value="app">App</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="query" class="form-label">Query</label>
                            <select class="form-control" id="query" name="query">
                                <option value="">Select Query</option>
                                @foreach ($services as $service)
                                    <option value="{{ $service->name }}">{{ $service->name }}</option>
                                @endforeach
                                <option value="job request">Job Request</option>
                            </select>
                        </div>

                        <div class="form-group mb-3" id="query_remarks_group" style="display: none;">
                            <label for="query_remarks" class="form-label">Query Remarks</label>
                            <textarea class="form-control" id="query_remarks" name="query_remarks" rows="3" placeholder="Enter remarks for the selected query"></textarea>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">Select Status</option>
                                <option value="follow-up">Follow-up</option>
                                <option value="future prospect">Future Prospect</option>
                                <option value="prospect">Prospect</option>
                                <option value="no response">No Response</option>
                                <option value="price issue">Price Issue</option>
                                <option value="duplicate">Duplicate</option>
                                <option value="spam">Spam</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded">
                            <label for="stage" class="form-label">Stage</label>
                            <select class="form-control" id="stage" name="stage">
                                <option value="">Select Stage</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="closed">Closed</option>
                                <option value="profile required">Profile Required</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 p-3 bg-light rounded" id="inactive_stage_remark_group" style="display: none;">
                            <label for="inactive_stage_remark" class="form-label">Inactive Stage Remark</label>
                            <textarea class="form-control" id="inactive_stage_remark" name="inactive_stage_remark" rows="3" placeholder="Enter remark when stage is Inactive (visible to Admin & Manager)"></textarea>
                        </div>

                        <!-- Future Prospect Date Field (Hidden by default) -->
                        <div class="form-group mb-3" id="future_prospect_date_group" style="display: none;">
                            <label for="future_prospect_date" class="form-label">Future contact date &amp; time</label>
                            <input type="datetime-local" class="form-control" id="future_prospect_date" name="future_prospect_date" step="60">
                        </div>
                        <div class="form-group mb-3" id="follow_up_date_group" style="display: none;">
                            <label for="follow_up_date" class="form-label">Follow-up date &amp; time</label>
                            <input type="datetime-local" class="form-control" id="follow_up_date" name="follow_up_date" step="60">
                        </div>

                        <!-- Prospect Close Rate Field (Hidden by default) -->
                        <div class="form-group mb-3" id="prospect_close_rate_group" style="display: none;">
                            <label for="prospect_rate" class="form-label">Rate (₹)</label>
                            <input type="number" class="form-control" id="prospect_rate" name="prospect_rate" min="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveLead">Save Lead</button>
                </div>
            </div>
        </div>
    </div>

    <aside class="control-sidebar control-sidebar-dark">
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
                                                value="{{ $location->name }}" {{ isset($filter_params['location']) &&
                                                in_array($location->name, $filter_params['location']) ? 'checked' : '' }}>
                                            <label for="team_member_{{ $location->name }}" class="custom-control-label">{{ $location->display_label }}</label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseLeadSource"
                                aria-expanded="true" aria-controls="collapseLeadSource">Lead Source</button>
                        </h2>
                        <div id="collapseLeadSource" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["web", "ivrs", "whatsapp", "manual", "crm", "app"] as $source)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_lead_source_{{ $source }}" name="lead_source[]"
                                        value="{{ $source }}" {{ isset($filter_params['lead_source']) && in_array($source, $filter_params['lead_source']) ? 'checked' : '' }}>
                                    <label for="filter_lead_source_{{ $source }}" class="custom-control-label">{{ ucfirst($source) }}</label>
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
                        <div id="collapseQuery" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach ($services as $service)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_query_{{ str_replace(' ', '_', $service->name) }}" name="query_filter[]"
                                        value="{{ $service->name }}" {{ isset($filter_params['query_filter']) && in_array($service->name, $filter_params['query_filter']) ? 'checked' : '' }}>
                                    <label for="filter_query_{{ str_replace(' ', '_', $service->name) }}" class="custom-control-label">{{ $service->name }}</label>
                                </div>
                                @endforeach
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_query_job_request" name="query_filter[]"
                                        value="job request" {{ isset($filter_params['query_filter']) && in_array('job request', $filter_params['query_filter']) ? 'checked' : '' }}>
                                    <label for="filter_query_job_request" class="custom-control-label">Job Request</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseStatus"
                                aria-expanded="true" aria-controls="collapseStatus">Status</button>
                        </h2>
                        <div id="collapseStatus" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["follow-up", "future prospect", "prospect", "no response","price issue", "duplicate", "spam"] as $status)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_status_{{ str_replace(' ', '_', $status) }}" name="status[]"
                                        value="{{ $status }}" {{ isset($filter_params['status']) && in_array($status, $filter_params['status']) ? 'checked' : '' }}>
                                    <label for="filter_status_{{ str_replace(' ', '_', $status) }}" class="custom-control-label">{{ ucfirst($status) }}</label>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="btn btn-block btn-sm btn-secondary text-left text-bold text-light"
                                type="button" data-bs-toggle="collapse" data-bs-target="#collapseStage"
                                aria-expanded="true" aria-controls="collapseStage">Stage</button>
                        </h2>
                        <div id="collapseStage" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                            <div class="accordion-body pl-2 pb-4">
                                @foreach (["active", "inactive", "closed", "profile required"] as $stage)
                                <div class="custom-control custom-checkbox my-1">
                                    <input class="custom-control-input" type="checkbox"
                                        id="filter_stage_{{ str_replace(' ', '_', $stage) }}" name="stage[]"
                                        value="{{ $stage }}" {{ isset($filter_params['stage']) && in_array($stage, $filter_params['stage']) ? 'checked' : '' }}>
                                    <label for="filter_stage_{{ str_replace(' ', '_', $stage) }}" class="custom-control-label">{{ ucfirst($stage) }}</label>
                                </div>
                                @endforeach
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

    <!-- View Lead Modal -->
    <div class="modal fade" id="viewLeadModal" tabindex="-1" role="dialog" aria-labelledby="viewLeadModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#FD7E14;">
                    <h5 class="modal-title" id="viewLeadModalLabel" style="color:#fff; ">Lead Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color:#FD7E14; color:white;">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Lead No:</label>
                                <p id="view_id"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Date:</label>
                                <p id="view_date"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Executive:</label>
                                <p id="view_executive"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Customer Name:</label>
                                <p id="view_customer_name"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Patient Name:</label>
                                <p id="view_patient_name"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Patient Gender:</label>
                                <p id="view_patient_gender"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Age:</label>
                                <p id="view_age"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Contact Type:</label>
                                <p id="view_contact_type"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Contact No:</label>
                                <p id="view_contact_no"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Last Call Status:</label>
                                <p id="view_last_call_status"></p>
                            </div>
                        </div>
                        <div class="col-md-6 ">
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Location:</label>
                                <p id="view_location"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Lead Source:</label>
                                <p id="view_lead_source"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Query:</label>
                                <p id="view_query"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded" id="view_query_remarks_group" style="display: none;">
                                <label class="fw-bold" style="color:#FD7E14;">Query Remarks:</label>
                                <p id="view_query_remarks"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Status:</label>
                                <p id="view_status"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded" id="view_status_remarks_group" style="display: none;">
                                <label class="fw-bold" style="color:#FD7E14;">Status Remarks:</label>
                                <div id="view_status_remarks_history" class="mt-2"></div>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Stage:</label>
                                <p id="view_stage"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded" id="view_inactive_stage_remark_group" style="display: none;">
                                <label class="fw-bold" style="color:#FD7E14;">Inactive Stage Remark:</label>
                                <p id="view_inactive_stage_remark"></p>
                            </div>
                            <div class="mb-3 p-2 bg-light rounded">
                                <label class="fw-bold" style="color:#FD7E14;">Shift Type:</label>
                                <p id="view_shift_type"></p>
                            </div>
                            <div class="mb-3" id="view_future_date_group">
                                <label class="fw-bold">Future contact date &amp; time:</label>
                                <p id="view_future_prospect_date"></p>
                            </div>
                            <div class="mb-3" id="view_close_rate_group">
                                <label class="fw-bold">Rate:</label>
                                <p id="view_prospect_rate"></p>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="mb-3" id="view_recordings_group" style="display:none;">
                                <label class="fw-bold">Call Recordings:</label>
                                <div id="view_recordings"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Rates Modal -->
    <div class="modal fade" id="serviceRatesModal" tabindex="-1" role="dialog" aria-labelledby="serviceRatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header" style="background-color:#FD7E14;">
                    <h5 class="modal-title" id="serviceRatesModalLabel" style="color:#fff;">Service Rates & Description</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="background-color:#FD7E14; color:#fff;">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="serviceRatesContent">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-2">Loading service rates...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @include('whatsapp.chat')

@endsection

@section('footer-script')
    <!-- Primary CDN -->
    <script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js"></script>
    <!-- Fallback CDN -->
    <script src="https://cdn.jsdelivr.net/npm/datatables.net@1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/datatables.net-responsive@2.4.0/js/dataTables.responsive.min.js"></script>
    <script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
<script>
        function formatFutureProspectForDatetimeLocal(val) {
            if (!val) return '';
            var m = moment(val);
            return m.isValid() ? m.format('YYYY-MM-DDTHH:mm') : '';
        }

        function normalizeDateTimeLocalForApi(value) {
            if (!value) return '';
            var m = moment(value, ['YYYY-MM-DDTHH:mm', 'YYYY-MM-DD HH:mm:ss', moment.ISO_8601], true);
            if (!m.isValid()) return '';
            return m.format('YYYY-MM-DD HH:mm:ss');
        }

        $(function() {
            // Set up CSRF token for all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Initialize DataTable
            var table = $('#leads-table').DataTable({
        processing: true,
        serverSide: false,
                responsive: false,
                scrollX: true,
                ajax: {
                    url: '{{ route("sales.leads.getLeads") }}',
                    dataSrc: 'data',
                    data: function(d) {
                        // Only add filter params, do not overwrite DataTables params
                        var filterData = $('#filters-form').serializeArray();
                        var filterFields = {};
                        // Collect all filter fields as arrays (flat, never nested)
                        filterData.forEach(function(item) {
                            // @csrf hidden field becomes _token[]=... on GET requests; omit it (header already set)
                            if (item.name === '_token') return;
                            // Remove [] from name if present
                            var name = item.name.replace(/\[\]$/, '');
                            if (filterFields[name]) {
                                filterFields[name].push(item.value);
                            } else {
                                filterFields[name] = [item.value];
                            }
                        });
                        // Add to d only if at least one value is selected
                        Object.keys(filterFields).forEach(function(key) {
                            // Only add if at least one non-empty value
                            var values = filterFields[key].filter(function(v) { return v !== ''; });
                            if (values.length > 0) {
                                d[key] = values;
                            } else {
                                delete d[key];
                            }
                        });
                    },
                    error: function(xhr, error, thrown) {
                        toastr.error('Error loading leads data. Please try again.');
                    }
                },
        columns: [
                    { data: 'lead_code', name: 'lead_code' },
                    
                    {
                        data: 'date',
                        name: 'date',
                        render: function(data) {
                            return moment(data).format('DD-MMM HH:mm');
                        }
                    },
                    { data: 'executive', name: 'executive' },
                    { data: 'customer_name', name: 'customer_name' },
                    { data: 'patient_name', name: 'patient_name' },
                    { data: 'patient_gender', name: 'patient_gender',
                        render: function(data, type, row) {
                            if (!data) return '-';
                            let genderClass = '';
                            let genderText = '';
                            switch(data.toLowerCase()) {
                                case 'male': genderClass = 'badge bg-primary'; genderText = 'Male'; break;
                                case 'female': genderClass = 'badge bg-danger'; genderText = 'Female'; break;
                                case 'other': genderClass = 'badge bg-secondary'; genderText = 'Other'; break;
                                default: return data;
                            }
                            return `<span class="${genderClass}">${genderText}</span>`;
                        }
                    },
                    { data: 'contact_no', name: 'contact_no',
                        render: function(data, type, row) {
                            return `
                                <div class="">
                                    <span class="">${data}</span>
                                    <i onclick="makeCall('${data}')" class="call-btn m-1" title="Call">
                                        <i class="fas fa-phone contact-icons"></i>
                                    </i>
                                    <i class="fab fa-whatsapp contact-icons" onclick="handle_whatsapp_msg('${data}')" id="what_id-${data}" title="WhatsApp"></i>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        name: 'last_call_status',
                        render: function(data, type, row) {
                            // Get last call status from database or recordings
                            let lastCallStatus = row.last_call_status;

                            // If no last_call_status in database, try to get from recordings
                            if (!lastCallStatus && row.recording_url) {
                                try {
                                    let recordings = JSON.parse(row.recording_url);
                                    if (Array.isArray(recordings) && recordings.length > 0) {
                                        let lastRecording = recordings[recordings.length - 1];
                                        if (lastRecording.metadata) {
                                            let metadata = JSON.parse(lastRecording.metadata);
                                            lastCallStatus = metadata.dialstatus;
                                        }
                                    }
                                } catch (e) {
                                    // Ignore parsing errors
                                }
                            }

                            if (!lastCallStatus) return '-';

                            let statusClass = '';
                            let displayText = '';

                            switch (lastCallStatus.toLowerCase()) {
                                case 'answered':
                                    statusClass = 'badge bg-success';
                                    displayText = 'Answered';
                                    break;
                                case 'busy':
                                    statusClass = 'badge bg-warning';
                                    displayText = 'Busy';
                                    break;
                                case 'no answer':
                                case 'noanswer':
                                    statusClass = 'badge bg-danger';
                                    displayText = 'Missed';
                                    break;
                                case 'failed':
                                    statusClass = 'badge bg-secondary';
                                    displayText = 'Failed';
                                    break;
                                default:
                                    statusClass = 'badge bg-info';
                                    displayText = lastCallStatus.charAt(0).toUpperCase() + lastCallStatus.slice(1);
                            }
                            return `<span class="${statusClass}">${displayText}</span>`;
                        }
                    },
                    { data: 'location', name: 'location' },
                    {
                        data: 'lead_source',
                        name: 'lead_source',
                        render: function(data, type, row) {
                            var source = (data && String(data).trim()) ? String(data).trim() : '';
                            if (!source && row.computed_lead_source) {
                                source = String(row.computed_lead_source).trim();
                            }
                            return source ? source.toUpperCase() : '-';
                        }
                    },
                    { data: 'query', name: 'query' },
                    {
                        data: 'status',
                        name: 'status',
                        render: function(data, type, row) {
                            let statusClass = '';
                            switch(data) {
                                case 'follow-up':
                                    statusClass = 'badge bg-info';
                                    break;
                                case 'future prospect':
                                    statusClass = 'badge bg-warning';
                                    break;
                                case 'prospect':
                                    statusClass = 'badge bg-success';
                                    break;
                                case 'price issue':
                                    statusClass = 'badge bg-secondary';
                                    break;
                                case 'no response':
                                    statusClass = 'badge bg-secondary';
                                    break;
                                case 'duplicate':
                                    statusClass = 'badge bg-danger';
                                    break;
                                case 'spam':
                                    statusClass = 'badge bg-dark';
                                    break;
                                default:
                                    statusClass = 'badge bg-secondary';
                            }
                            return `<span class="${statusClass}">${data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'}</span>`;
                        }
                    },
                    {
                        data: 'stage',
                        name: 'stage',
                        render: function(data, type, row) {
                            let stageClass = '';
                            switch(data) {
                                case 'active': stageClass = 'bg-success'; break;
                                case 'inactive': stageClass = 'bg-warning'; break;
                                case 'closed': stageClass = 'bg-danger'; break;
                                case 'profile required': stageClass = 'bg-info'; break;
                                default: stageClass = 'bg-secondary';
                            }
                            return `<span class="badge ${stageClass}">${data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'}</span>`;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group" role="group">
                                    <button class="action-btn view-btn" data-id="${row.id}" title="View">
                                        <i class="fa fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" data-id="${row.id}" title="Edit">
                                        <i class="fa fa-pen-to-square"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'desc']]
            });

            $('#filters-form').on('submit', function(e) {
                e.preventDefault();
                table.ajax.reload();
            });

            // Initialize Bootstrap 5 Modal
            var editLeadModal = new bootstrap.Modal(document.getElementById('editLeadModal'));

            // Handle edit button click
            $(document).on('click', '.edit-btn', function() {
                var leadId = $(this).data('id');
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/sales/leads/' + leadId + '/edit',
                    type: 'GET',
                    success: function(response) {
                        // Set all fields (including hidden ones)
                        $('#edit_lead_id').val(response.id);
                        $('#edit_date').val(response.date);
                        $('#edit_executive').val(response.executive || '');
                        $('#edit_contact_type').val(response.contact_type || '');
                        $('#edit_contact_no').val(response.contact_no || '');
                        $('#edit_lead_source').val(response.lead_source || '');

                        // Set visible editable fields
                        $('#edit_customer_name').val(response.customer_name || '');
                        $('#edit_patient_name').val(response.patient_name || '');
                        $('#edit_patient_gender').val(response.patient_gender || '');
                        $('#edit_age').val(response.age || '');
                        $('#edit_location').val(response.location || '').trigger('change');
                        $('#edit_query').val(response.query || '');
                        $('#edit_status').val(response.status || '');
                        $('#edit_stage').val(response.stage || 'active');
                        $('#edit_shift_type').val(response.shift_type || '');
                        $('#edit_inactive_stage_remark').val(response.inactive_stage_remark || '');

                        // Set remarks fields
                        $('#edit_query_remarks').val(response.query_remarks || '');
                        if (window.LeadStatusRemarks) {
                            LeadStatusRemarks.onEditLeadLoaded('edit', response, { leadId: response.id });
                        }

                        // Show/hide remarks fields based on selections
                        if (response.query && response.query !== 'job request') {
                            $('#edit_query_remarks_group').show();
                        }
                        if (response.status) {
                            $('#edit_status_remarks_group').show();
                        }
                        if (response.stage === 'inactive') {
                            $('#edit_inactive_stage_remark_group').show();
                        } else {
                            $('#edit_inactive_stage_remark_group').hide();
                        }

                        // Trigger query change logic to handle job request case
                        $('#edit_query').trigger('change');

                        $('#edit_status').trigger('change');
                        $('#edit_follow_up_date').val(formatFutureProspectForDatetimeLocal(response.follow_up_date));
                        $('#edit_future_prospect_date').val(formatFutureProspectForDatetimeLocal(response.future_prospect_date));
                        $('#edit_prospect_rate').val(response.prospect_rate != null && response.prospect_rate !== '' ? response.prospect_rate : '');

                        // Show modal
                        editLeadModal.show();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading lead data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-edit"></i>');
                    }
                });
            });

            // Handle update button click
            $('#updateLead').on('click', function() {
                if (window.LeadStatusRemarks && !window.LeadStatusRemarks.validateBeforeSave('edit')) {
                    return;
                }
                var $btn = $(this);
                var formData = $('#editLeadForm').serializeArray();
                var data = {};

                // Convert form data to object and handle special fields
                $(formData).each(function(index, obj){
                    data[obj.name] = obj.value;
                });

                // Only include future_prospect_date if status is future prospect
                if (data.status === 'future prospect') {
                    if (!data.future_prospect_date) {
                        toastr.error('Please select future contact date and time');
                        return;
                    }
                    var normalizedFuture = normalizeDateTimeLocalForApi(data.future_prospect_date);
                    if (!normalizedFuture) {
                        toastr.error('Invalid future contact date and time');
                        return;
                    }
                    data.future_prospect_date = normalizedFuture;
                } else {
                    delete data.future_prospect_date;
                }
                if (data.status === 'follow-up') {
                    if (!data.follow_up_date) {
                        toastr.error('Please select follow-up date and time');
                        return;
                    }
                    var normalizedFollowUp = normalizeDateTimeLocalForApi(data.follow_up_date);
                    if (!normalizedFollowUp) {
                        toastr.error('Invalid follow-up date and time');
                        return;
                    }
                    data.follow_up_date = normalizedFollowUp;
                } else {
                    delete data.follow_up_date;
                }

                // Only include prospect_rate if status is prospect
                if (data.status !== 'prospect') {
                    delete data.prospect_rate;
                }

                var leadId = $('#edit_lead_id').val();

                // Disable button and show loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: '/sales/leads/' + leadId,
                    type: 'PUT',
                    data: data,
                    success: function(response) {
                        toastr.success(response.message || 'Lead updated successfully');
                        var editLeadModal = bootstrap.Modal.getInstance(document.getElementById('editLeadModal'));
                        editLeadModal.hide();
                        table.ajax.reload(null, false);
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error updating lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('Update Lead');
                    }
                });
            });

            // Handle editable field click
            $(document).on('click', '.editable', function() {
                var leadId = $(this).data('id');
                $('.edit-btn[data-id="' + leadId + '"]').click();
            });

            // Handle modal close
            $('#editLeadModal').on('hidden.bs.modal', function() {
                $('#editLeadForm')[0].reset();
    });

            // Handle save button click for new lead
            $('#saveLead').on('click', function() {
                var $btn = $(this);
                var formData = $('#addLeadForm').serializeArray();
                var data = {};

                // Convert form data to object and handle special fields
                $(formData).each(function(index, obj){
                    data[obj.name] = obj.value;
                });

                // Only include future_prospect_date if status is future prospect
                if (data.status === 'future prospect') {
                    if (!data.future_prospect_date) {
                        toastr.error('Please select future contact date and time');
                        return;
                    }
                    var normalizedFuture = normalizeDateTimeLocalForApi(data.future_prospect_date);
                    if (!normalizedFuture) {
                        toastr.error('Invalid future contact date and time');
                        return;
                    }
                    data.future_prospect_date = normalizedFuture;
                } else {
                    delete data.future_prospect_date;
                }
                if (data.status === 'follow-up') {
                    if (!data.follow_up_date) {
                        toastr.error('Please select follow-up date and time');
                        return;
                    }
                    var normalizedFollowUp = normalizeDateTimeLocalForApi(data.follow_up_date);
                    if (!normalizedFollowUp) {
                        toastr.error('Invalid follow-up date and time');
                        return;
                    }
                    data.follow_up_date = normalizedFollowUp;
                } else {
                    delete data.follow_up_date;
                }

                // Only include prospect_rate if status is prospect
                if (data.status !== 'prospect') {
                    delete data.prospect_rate;
                }

                // Disable button and show loading state
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');

                $.ajax({
                    url: '{{ route("sales.leads.store") }}',
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        toastr.success(response.message || 'Lead created successfully');
                        var addLeadModal = bootstrap.Modal.getInstance(document.getElementById('addLeadModal'));
                        addLeadModal.hide();
                        table.ajax.reload(null, false);
                        $('#addLeadForm')[0].reset();
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error creating lead';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false).html('Save Lead');
                    }
                });
            });

            // Handle add modal close
            $('#addLeadModal').on('hidden.bs.modal', function() {
                $('#addLeadForm')[0].reset();
            });

            // Handle location change in add form
            $('#location').on('change', function() {
                showServiceRatesAdd();
            });

            // Handle query change in add form
            $('#query').on('change', function() {
                const selectedQuery = $(this).val();
                if (selectedQuery && selectedQuery !== 'job request') {
                    $('#query_remarks_group').show();
                    $('#query_remarks').prop('required', true);
                } else {
                    $('#query_remarks_group').hide();
                    $('#query_remarks').prop('required', false);
                }
                showServiceRatesAdd();
            });

            // Function to show service rates modal for add form
            function showServiceRatesAdd() {
                const location = $('#location').val();
                const query = $('#query').val();

                if (location && query && query !== 'job request') {
                    // Show loading toast
                    toastr.info('Loading service rates...', 'Please wait');

                    // Fetch service rates
                    $.ajax({
                        url: '/api/locations/service-rates',
                        type: 'GET',
                        data: {
                            location: location,
                            service: query
                        },
                        success: function(response) {
                            if (response.success) {
                                const data = response.data;
                                let ratesHtml = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><strong>Service:</strong> ${data.service_name}</h6>
                                            <h6 class="text-primary"><strong>Location:</strong> ${data.location_name}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-success"><strong>Pricing:</strong></h6>
                                            <ul class="list-unstyled">
                                                <li><strong>12hr:</strong> ₹${data.prices.price_12hr || 'N/A'}</li>
                                                <li><strong>24hr:</strong> ₹${data.prices.price_24hr || 'N/A'}</li>
                                                <li><strong>One-time:</strong> ₹${data.prices.price_onetime || 'N/A'}</li>
                                            </ul>
                                        </div>
                                    </div>
                                `;

                                if (data.service_description) {
                                    ratesHtml += `
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-info"><strong>Description:</strong></h6>
                                                <p class="text-muted">${data.service_description}</p>
                                            </div>
                                        </div>
                                    `;
                                }

                                $('#serviceRatesContent').html(ratesHtml);
                                // Show modal only when data is available
                                $('#serviceRatesModal').modal('show');
                                toastr.success('Service rates loaded successfully!');
                            } else {
                                // Show toast message instead of modal when no data
                                toastr.warning(response.message || 'Service rates not available for this location and service combination.', 'No Data Found');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Error loading service rates. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            // For known not-found cases show warning instead of hard error
                            if (xhr.status === 404 || xhr.status === 422) {
                                toastr.warning(msg, 'Service Rates');
                            } else {
                                toastr.error(msg, 'Error');
                            }
                        }
                    });
                }
            }

            // Handle status change in add form
            $('#status').on('change', function() {
                const selectedStatus = $(this).val();

                // Hide both conditional fields first
                $('#future_prospect_date_group').hide();
                $('#follow_up_date_group').hide();
                $('#prospect_close_rate_group').hide();
                $('#future_prospect_date').prop('required', false);
                $('#follow_up_date').prop('required', false);
                $('#prospect_rate').prop('required', false);

                // Show relevant field based on selection
                if (selectedStatus === 'follow-up') {
                    $('#follow_up_date_group').show();
                    $('#follow_up_date').prop('required', true);
                } else if (selectedStatus === 'future prospect') {
                    $('#future_prospect_date_group').show();
                    $('#future_prospect_date').prop('required', true);
                } else if (selectedStatus === 'prospect') {
                    $('#prospect_close_rate_group').show();
                    $('#prospect_rate').prop('required', true);
                }
            });

            // Handle status change in edit form
            $('#edit_status').on('change', function() {
                const selectedStatus = $(this).val();

                // Hide both conditional fields first
                $('#edit_future_prospect_date_group').hide();
                $('#edit_follow_up_date_group').hide();
                $('#edit_prospect_close_rate_group').hide();
                $('#edit_status_remarks_group').hide();
                $('#edit_future_prospect_date').prop('required', false);
                $('#edit_follow_up_date').prop('required', false);
                $('#edit_prospect_rate').prop('required', false);

                // Show relevant field based on selection
                if (selectedStatus === 'follow-up') {
                    $('#edit_follow_up_date_group').show();
                    $('#edit_follow_up_date').prop('required', true);
                } else if (selectedStatus === 'future prospect') {
                    $('#edit_future_prospect_date_group').show();
                    $('#edit_future_prospect_date').prop('required', true);
                } else if (selectedStatus === 'prospect') {
                    $('#edit_prospect_close_rate_group').show();
                    $('#edit_prospect_rate').prop('required', true);
                }

                // Show status remarks for any status selection
                if (selectedStatus) {
                    $('#edit_status_remarks_group').show();
                    $('#edit_new_status_remark').prop('required', false);
                } else {
                    $('#edit_status_remarks_group').hide();
                    $('#edit_new_status_remark').prop('required', false);
                }
            });

            // Handle stage change in edit form – show Inactive Stage Remark when stage is inactive
            $('#edit_stage').on('change', function() {
                if ($(this).val() === 'inactive') {
                    $('#edit_inactive_stage_remark_group').show();
                } else {
                    $('#edit_inactive_stage_remark_group').hide();
                }
            });

            // Handle stage change in add form – show Inactive Stage Remark when stage is inactive
            $('#stage').on('change', function() {
                if ($(this).val() === 'inactive') {
                    $('#inactive_stage_remark_group').show();
                } else {
                    $('#inactive_stage_remark_group').hide();
                }
            });

            // Handle query change in edit form
            $('#edit_query').on('change', function() {
                const selectedQuery = $(this).val();
                if (selectedQuery && selectedQuery !== 'job request') {
                    $('#edit_query_remarks_group').show();
                    $('#edit_query_remarks').prop('required', true);
                } else {
                    $('#edit_query_remarks_group').hide();
                    $('#edit_query_remarks').prop('required', false);
                }

                // Always show Status, Status Remarks, and Stage fields
                $('#edit_status').closest('.form-group').show();
                $('#edit_stage').closest('.form-group').show();
                // Only show status remarks if status is selected
                if ($('#edit_status').val()) {
                    $('#edit_status_remarks_group').show();
                }

                // Show service rates if both location and query are selected
                showServiceRates();
            });

            // Handle location change in edit form
            $('#edit_location').on('change', function() {
                // Show service rates if both location and query are selected
                showServiceRates();
            });

            // Function to show service rates modal
            function showServiceRates() {
                const location = $('#edit_location').val();
                const query = $('#edit_query').val();

                if (location && query && query !== 'job request') {
                    // Show loading toast
                    toastr.info('Loading service rates...', 'Please wait');

                    // Fetch service rates
                    $.ajax({
                        url: '/api/locations/service-rates',
                        type: 'GET',
                        data: {
                            location: location,
                            service: query
                        },
                        success: function(response) {
                            if (response.success) {
                                const data = response.data;
                                let ratesHtml = `
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="text-primary"><strong>Service:</strong> ${data.service_name}</h6>
                                            <h6 class="text-primary"><strong>Location:</strong> ${data.location_name}</h6>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="text-success"><strong>Pricing:</strong></h6>
                                            <ul class="list-unstyled">
                                                <li><strong>12hr:</strong> ₹${data.prices.price_12hr || 'N/A'}</li>
                                                <li><strong>24hr:</strong> ₹${data.prices.price_24hr || 'N/A'}</li>
                                                <li><strong>One-time:</strong> ₹${data.prices.price_onetime || 'N/A'}</li>
                                            </ul>
                                        </div>
                                    </div>
                                `;

                                if (data.service_description) {
                                    ratesHtml += `
                                        <div class="row mt-3">
                                            <div class="col-12">
                                                <h6 class="text-info"><strong>Description:</strong></h6>
                                                <p class="text-muted">${data.service_description}</p>
                                            </div>
                                        </div>
                                    `;
                                }

                                $('#serviceRatesContent').html(ratesHtml);
                                // Show modal only when data is available
                                $('#serviceRatesModal').modal('show');
                                toastr.success('Service rates loaded successfully!');
                            } else {
                                // Show toast message instead of modal when no data
                                toastr.warning(response.message || 'Service rates not available for this location and service combination.', 'No Data Found');
                            }
                        },
                        error: function(xhr) {
                            let msg = 'Error loading service rates. Please try again.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                msg = xhr.responseJSON.message;
                            }
                            if (xhr.status === 404 || xhr.status === 422) {
                                toastr.warning(msg, 'Service Rates');
                            } else {
                                toastr.error(msg, 'Error');
                            }
                        }
                    });
                }
            }

            // Handle view button click
            $(document).on('click', '.view-btn', function() {
                var leadId = $(this).data('id');
                var $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: '/sales/leads/' + leadId + '/edit',
                    type: 'GET',
                    success: function(response) {
                        // Set all fields
                        $('#view_id').text(response.lead_code);
                        $('#view_date').text(moment(response.date).format('DD-MM-YYYY'));
                        $('#view_executive').text(response.executive || '-');
                        $('#view_customer_name').text(response.customer_name || '-');
                        $('#view_patient_name').text(response.patient_name || '-');
                        $('#view_patient_gender').text(response.patient_gender ? response.patient_gender.charAt(0).toUpperCase() + response.patient_gender.slice(1) : '-');
                        $('#view_age').text(response.age || '-');
                        $('#view_contact_type').text(response.contact_type ? response.contact_type.charAt(0).toUpperCase() + response.contact_type.slice(1) : '-');
                        $('#view_contact_no').text(response.contact_no || '-');

                                                // Set last call status with badge
                        let lastCallStatus = response.last_call_status;

                        // If no last_call_status in database, try to get from recordings
                        if (!lastCallStatus && response.recording_url) {
                            try {
                                let recordings = JSON.parse(response.recording_url);
                                if (Array.isArray(recordings) && recordings.length > 0) {
                                    let lastRecording = recordings[recordings.length - 1];
                                    if (lastRecording.metadata) {
                                        let metadata = JSON.parse(lastRecording.metadata);
                                        lastCallStatus = metadata.dialstatus;
                                    }
                                }
                            } catch (e) {
                                // Ignore parsing errors
                            }
                        }

                        if (lastCallStatus) {
                            let callStatusClass = '';
                            let callStatusText = '';

                            switch (lastCallStatus.toLowerCase()) {
                                case 'answered':
                                    callStatusClass = 'bg-success';
                                    callStatusText = 'Answered';
                                    break;
                                case 'busy':
                                    callStatusClass = 'bg-warning';
                                    callStatusText = 'Busy';
                                    break;
                                case 'no answer':
                                case 'noanswer':
                                    callStatusClass = 'bg-danger';
                                    callStatusText = 'Missed';
                                    break;
                                case 'failed':
                                    callStatusClass = 'bg-secondary';
                                    callStatusText = 'Failed';
                                    break;
                                default:
                                    callStatusClass = 'bg-info';
                                    callStatusText = lastCallStatus.charAt(0).toUpperCase() + lastCallStatus.slice(1);
                            }
                            $('#view_last_call_status').html(`<span class="badge ${callStatusClass}">${callStatusText}</span>`);
                        } else {
                            $('#view_last_call_status').text('-');
                        }

                        $('#view_location').text(response.location || '-');
                        $('#view_lead_source').text(response.lead_source ? response.lead_source.toUpperCase() : '-');
                        $('#view_query').text(response.query ? response.query.charAt(0).toUpperCase() + response.query.slice(1) : '-');

                        // Set query remarks
                        if (response.query_remarks && response.query_remarks.trim() !== '') {
                            $('#view_query_remarks').text(response.query_remarks);
                            $('#view_query_remarks_group').show();
                        } else {
                            $('#view_query_remarks_group').hide();
                        }

                        // Set status with badge
                        let statusClass = '';
                        switch(response.status) {
                            case 'follow-up': statusClass = 'bg-info'; break;
                            case 'future prospect': statusClass = 'bg-warning'; break;
                            case 'prospect': statusClass = 'bg-success'; break;
                            case 'price issue': statusClass = 'bg-secondary'; break;
                            case 'no response': statusClass = 'bg-secondary'; break;
                            case 'duplicate': statusClass = 'bg-danger'; break;
                            case 'spam': statusClass = 'bg-dark'; break;
                            default: statusClass = 'bg-secondary';
                        }
                        $('#view_status').html(`<span class="badge ${statusClass}">${response.status ? response.status.charAt(0).toUpperCase() + response.status.slice(1) : '-'}</span>`);

                        if (window.LeadStatusRemarks) {
                            LeadStatusRemarks.onViewLeadLoaded('view', response);
                        }

                        // Set stage with badge
                        let stageClass = '';
                        switch(response.stage) {
                            case 'active': stageClass = 'bg-success'; break;
                            case 'inactive': stageClass = 'bg-warning'; break;
                            case 'closed': stageClass = 'bg-danger'; break;
                            case 'profile required': stageClass = 'bg-info'; break;
                            default: stageClass = 'bg-secondary';
                        }
                        $('#view_stage').html(`<span class="badge ${stageClass}">${response.stage ? response.stage.charAt(0).toUpperCase() + response.stage.slice(1) : '-'}</span>`);

                        if (response.stage === 'inactive') {
                            $('#view_inactive_stage_remark_group').show();
                            $('#view_inactive_stage_remark').text(response.inactive_stage_remark || '-');
                        } else {
                            $('#view_inactive_stage_remark_group').hide();
                        }

                        // Set shift type
                        $('#view_shift_type').text(response.shift_type || '-');

                        // Handle conditional fields
                        if (response.status === 'future prospect' && response.future_prospect_date) {
                            $('#view_future_date_group').show();
                            $('#view_future_date_group label').text('Future contact date & time:');
                            $('#view_future_prospect_date').text(moment(response.future_prospect_date).format('DD-MM-YYYY HH:mm'));
                        } else if (response.status === 'follow-up' && response.follow_up_date) {
                            $('#view_future_date_group').show();
                            $('#view_future_date_group label').text('Follow-up date & time:');
                            $('#view_future_prospect_date').text(moment(response.follow_up_date).format('DD-MM-YYYY HH:mm'));
                        } else {
                            $('#view_future_date_group').hide();
                            $('#view_future_date_group label').text('Future contact date & time:');
                        }

                        if (response.status === 'prospect' && response.prospect_rate !== null) {
                            $('#view_close_rate_group').show();
                            $('#view_prospect_rate').text('₹' + parseFloat(response.prospect_rate).toLocaleString('en-IN', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            }));
                        } else {
                            $('#view_close_rate_group').hide();
                        }

                        // Handle call recordings
                        let recordingsArr = [];
                        if (Array.isArray(response.recordings)) {
                            recordingsArr = response.recordings;
                        } else if (response.recording_url) {
                            try {
                                recordingsArr = typeof response.recording_url === 'string'
                                    ? JSON.parse(response.recording_url)
                                    : response.recording_url;
                            } catch (e) {
                                recordingsArr = [];
                            }
                        }

                        if (!Array.isArray(recordingsArr)) {
                            recordingsArr = [];
                        }
                        if (Array.isArray(recordingsArr) && recordingsArr.length > 0) {
                            $('#view_recordings_group').show();
                            let html = '';
                            recordingsArr.forEach(function(rec, idx) {
                                let meta = {};
                                try {
                                    meta = typeof rec.metadata === 'string'
                                        ? JSON.parse(rec.metadata)
                                        : (rec.metadata || {});
                                } catch(e) {
                                    meta = {};
                                }
                                html += `<div class=\"mb-2\">\n  <div class=\"d-flex align-items-center\">\n    <audio controls style=\"vertical-align:middle;max-width:520px;width:500px;\">\n      <source src=\"${rec.url}\" type=\"audio/wav\">\n      Your browser does not support the audio element.\n    </audio>\n  </div>\n  <div class=\"d-flex align-items-center mt-1\">\n    <a href=\"${rec.url}\" target=\"_blank\" class=\"btn btn-sm btn-outline-primary ms-2\" title=\"Download\">\n      <i class=\"fa fa-download\"></i>\n    </a>\n    <span class=\"text-muted ms-2\">\n      ${(meta.datetime ? 'Date: ' + meta.datetime : '')}\n      ${(meta.caller_agent ? ' | Agent: ' + meta.caller_agent : '')}\n      ${(meta.dialstatus ? ' | Dial Status: ' + meta.dialstatus : '')}\n      ${(meta.call_direction ? ' | Call Type: ' + meta.call_direction.charAt(0).toUpperCase() + meta.call_direction.slice(1) : '')}\n    </span>\n  </div>\n</div>`;
                            });
                            $('#view_recordings').html(html);
                        } else {
                            $('#view_recordings_group').hide();
                        }

                        // Show modal
                        $('#viewLeadModal').modal('show');
                    },
                    error: function(xhr) {
                        let errorMessage = 'Error loading lead data';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        toastr.error(errorMessage);
                    },
                    complete: function() {
                        $btn.prop('disabled', false).html('<i class="fas fa-eye"></i>');
                    }
                });
    });
});

function makeCall(customerNumber) {
    const $btn = $(event.target).closest('.call-btn');
    if (!confirm('Are you sure you want to make this call?')) return;
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
</script>
@endsection
