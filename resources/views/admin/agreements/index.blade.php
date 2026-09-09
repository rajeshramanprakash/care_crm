@extends('admin.layouts.app')
@section('title', 'All Agreements & Partners')

@section('header-css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<style>
    .agreements-master-page .card {
        border-radius: 14px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        border: 1px solid #eef0f2;
    }
    .stat-card {
        border-radius: 12px;
        padding: 1.1rem 1.25rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.08);
    }
    .stat-card .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    .stat-card .stat-val {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1.2;
        color: #1e293b;
    }
    .stat-card .stat-label {
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        font-weight: 600;
    }
    .partner-id-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        background: #f1f5f9;
        color: #0f172a;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 0.82rem;
        border: 1px solid #cbd5e1;
    }
    .agreement-no-badge {
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        background: #fff7ed;
        color: #c2410c;
        padding: 4px 8px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 0.8rem;
        border: 1px solid #ffedd5;
    }
    .agreement-no-placeholder {
        font-style: italic;
        color: #94a3b8;
        font-size: 0.8rem;
    }
    .filter-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem 1.2rem;
        margin-bottom: 1.25rem;
    }
    .dataTables_wrapper .dataTables_filter {
        display: none !important;
    }
</style>
@endsection

@section('content')
<div class="content-wrapper agreements-master-page p-3 p-md-4">
    <section class="content-header mb-3">
        <div class="container-fluid p-0">
            <h1 class="font-weight-bold text-dark mb-1">All Agreements &amp; Partners</h1>
            <p class="text-muted mb-0">Master database of all registered Doctors, Freelancers, and Vendors with Agreement Numbers &amp; Partner IDs.</p>
        </div>
    </section>

    {{-- Statistics Row --}}
    <div class="row mb-4">
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Total Partners</div>
                    <div class="stat-val">{{ $totalPartners }}</div>
                </div>
                <div class="stat-icon bg-light text-primary">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Doctors</div>
                    <div class="stat-val">{{ $totalDoctors }}</div>
                </div>
                <div class="stat-icon bg-primary text-white">
                    <i class="fas fa-user-md"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Freelancers</div>
                    <div class="stat-val">{{ $totalFreelancers }}</div>
                </div>
                <div class="stat-icon bg-info text-white">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-3 mb-xl-0">
            <div class="stat-card d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-label">Vendors</div>
                    <div class="stat-val">{{ $totalVendors }}</div>
                </div>
                <div class="stat-icon bg-warning text-dark">
                    <i class="fas fa-store"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Listing Card --}}
    <div class="card">
        <div class="card-body">
            {{-- Filter Bar --}}
            <div class="filter-bar">
                <div class="row align-items-end g-2">
                    <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-uppercase text-muted mb-1">Category</label>
                        <select id="filterCategory" class="form-control form-control-sm rounded">
                            <option value="all">All Categories (Doctor, Freelancer, Vendor)</option>
                            <option value="doctor">Doctor (doc)</option>
                            <option value="freelancer">Freelancer (frl)</option>
                            <option value="vendor">Vendor (ven)</option>
                        </select>
                    </div>
                    <div class="col-lg-3 col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-uppercase text-muted mb-1">Search (ID / Agreement / Name)</label>
                        <input type="text" id="filterSearch" class="form-control form-control-sm rounded" placeholder="Search partner ID, name, mobile, etc.">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-uppercase text-muted mb-1">From Date</label>
                        <input type="date" id="filterFromDate" class="form-control form-control-sm rounded">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2 mb-md-0">
                        <label class="small font-weight-bold text-uppercase text-muted mb-1">To Date</label>
                        <input type="date" id="filterToDate" class="form-control form-control-sm rounded">
                    </div>
                    <div class="col-lg-2 col-md-4 text-end">
                        <button type="button" id="resetFiltersBtn" class="btn btn-sm btn-outline-secondary w-100">
                            <i class="fas fa-undo mr-1"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table id="agreements-master-table" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Lead ID</th>
                            <th>Agreement Number</th>
                            <th>Name</th>
                            <th>Contact No</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('footer-script')
<script src="https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script>
$(function() {
    var table = $('#agreements-master-table').DataTable({
        processing: true,
        searching: false,
        ajax: {
            url: '{{ route('admin.agreements.data') }}',
            data: function(d) {
                d.category = $('#filterCategory').val();
                d.search_query = $('#filterSearch').val();
                d.from_date = $('#filterFromDate').val();
                d.to_date = $('#filterToDate').val();
            },
            dataSrc: 'data'
        },
        columns: [
            { data: 'category_badge', name: 'category' },
            { 
                data: 'partner_id', 
                name: 'partner_id',
                render: function(d) {
                    return '<span class="partner-id-badge">' + d + '</span>';
                }
            },
            { 
                data: 'agreement_number', 
                name: 'agreement_number',
                render: function(d) {
                    if (!d || d.indexOf('Will be generated') !== -1) {
                        return '<span class="agreement-no-placeholder"><i class="far fa-clock mr-1"></i>' + d + '</span>';
                    }
                    return '<span class="agreement-no-badge">' + d + '</span>';
                }
            },
            { data: 'name', name: 'name', render: function(d) { return '<strong>' + d + '</strong>'; } },
            { data: 'contact_no', name: 'contact_no' },
            { data: 'email', name: 'email' },
            { data: 'location', name: 'location' },
            { data: 'created_at', name: 'created_at' },
            { 
                data: 'status', 
                name: 'status',
                render: function(d) {
                    var st = String(d || '').toLowerCase();
                    var badgeClass = 'badge-secondary';
                    if (st === 'active' || st === 'approved') badgeClass = 'badge-success';
                    if (st === 'pending') badgeClass = 'badge-warning text-dark';
                    if (st === 'inactive' || st === 'rejected') badgeClass = 'badge-danger';
                    return '<span class="badge badge-pill ' + badgeClass + ' text-capitalize px-2 py-1">' + d + '</span>';
                }
            },
            { 
                data: 'detail_url', 
                name: 'action', 
                orderable: false, 
                searchable: false,
                className: 'text-end',
                render: function(d) {
                    return '<a href="' + d + '" class="btn btn-sm btn-outline-primary"><i class="fas fa-external-link-alt mr-1"></i>View</a>';
                }
            }
        ],
        order: [[7, 'desc']]
    });

    // Real-time filtering handlers
    $('#filterCategory, #filterFromDate, #filterToDate').on('change', function() {
        table.ajax.reload();
    });

    var searchTimer;
    $('#filterSearch').on('keyup input', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            table.ajax.reload();
        }, 300);
    });

    $('#resetFiltersBtn').on('click', function() {
        $('#filterCategory').val('all');
        $('#filterSearch').val('');
        $('#filterFromDate').val('');
        $('#filterToDate').val('');
        table.ajax.reload();
    });
});
</script>
@endsection
