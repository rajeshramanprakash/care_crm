@extends('postsales.layouts.app')

@section('title', $page_heading)

@section('header-css')
<link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
<style>
.status-filter-box {
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.status-filter-box:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.status-filter-box.active {
    border: 2px solid #007bff;
    box-shadow: 0 0 10px rgba(0,123,255,0.3);
}

.status-filter-box .count {
    font-size: 12px;
    margin-top: 5px;
    opacity: 0.8;
}

.status-filter-box.active .count {
    font-weight: bold;
}
</style>
@endsection
@section('main')
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="d-flex justify-content-between mb-2">
                <h1 class="m-0">{{ $page_heading }}</h1>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <!-- Status Filter Boxes -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Filter by Status</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="all" style="background-color: #6c757d; color: white; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>All</strong>
                                        <div class="count" id="count-all">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="Query" style="background-color: #FFB6C1; color: black; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Query</strong>
                                        <div class="count" id="count-query">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="Investigation" style="background-color: #ADD8E6; color: black; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Investigation</strong>
                                        <div class="count" id="count-investigation">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="UnderProcess" style="background-color: #FFC107; color: black; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Under Process</strong>
                                        <div class="count" id="count-underprocess">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="Approved" style="background-color: #28A745; color: white; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Approved</strong>
                                        <div class="count" id="count-approved">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="InProcess" style="background-color: #28A745; color: white; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Payment In Process</strong>
                                        <div class="count" id="count-inprocess">-</div>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6 mb-2">
                                    <div class="status-filter-box" data-status="empty_claim" style="background-color: #6F42C1; color: white; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                        <strong>Empty Claim No</strong>
                                        <div class="count" id="count-empty-claim">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive">
                <table id="casesTable" class="table text-sm">
                    <thead class="sticky_head bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Case code</th>
                            <th>Case Type</th>
                            <th>Claim no</th>
                            <th>Name</th>
                            <th>Corp</th>
                            <th>Hospital</th>
                            <th>Diagnosis</th>
                            <th>Member id</th>
                            <th>Courier date</th>
                            <th>Pod no</th>
                            <th>Aprv Amt</th>
                            <th>Status</th>
                            <th>Claim No</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="modal fade" id="tpaAllotmentModal" tabindex="-1" aria-labelledby="tpaAllotmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <form id="tpaAllotmentForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="tpaAllotmentModalLabel">TPA Allotment</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="case_id" id="caseId">
                        <div class="text-center">Main Claim Tpa</div>
                        <hr />
                        <!-- TPA Type Selection -->
                        <div class="form-group">
                            <label for="tpa_type">TPA Type</label>
                            <select class="form-control" name="tpa_type" id="tpa_type" required>
                                <option value="direct">Direct</option>
                                <option value="first">First</option>
                            </select>
                        </div>

                        <!-- TPA Allotment 1 -->
                        <div class="form-group">
                            <label for="tpa_allot_after_claim_no_received">TPA 1</label>
                            <select class="form-control" name="tpa_allot_after_claim_no_received" id="tpa_allotment">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- TPA Allotment 2 -->
                        <div class="form-group">
                            <label for="tpa_allot_after_claim_no_received_two">TPA 2</label>
                            <select class="form-control" name="tpa_allot_after_claim_no_received_two"
                                id="tpa_allotment_two">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <hr />
                        <div class="text-center">Post One Tpa</div>
                        <hr />

                        {{-- post tpa --}}
                        <!--Post TPA Type Selection -->
                        <div class="form-group">
                            <label for="post_tpa_type">Post One TPA Type</label>
                            <select class="form-control" name="post_tpa_type" id="post_tpa_type" required>
                                <option value="direct">Direct</option>
                                <option value="first">First</option>
                            </select>
                        </div>

                        <!--Post TPA Allotment 1 -->
                        <div class="form-group">
                            <label for="post_tpa_allot_after_claim_no_received">Post One TPA 1</label>
                            <select class="form-control" name="post_tpa_allot_after_claim_no_received"
                                id="post_tpa_allot_after_claim_no_received">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!--Post TPA Allotment 2 -->
                        <div class="form-group">
                            <label for="post_tpa_allot_after_claim_no_received_two">Post One TPA 2</label>
                            <select class="form-control" name="post_tpa_allot_after_claim_no_received_two"
                                id="post_tpa_allot_after_claim_no_received_two">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- post Two tpa --}}
                        <hr />
                        <div class="text-center">Post Two Tpa</div>
                        <hr />
                        <!--Post TPA Type Selection -->
                        <div class="form-group">
                            <label for="post_two_tpa_type">Post Two TPA Type</label>
                            <select class="form-control" name="post_two_tpa_type" id="post_two_tpa_type" required>
                                <option value="direct">Direct</option>
                                <option value="first">First</option>
                            </select>
                        </div>

                        <!--Post TPA Allotment 1 -->
                        <div class="form-group">
                            <label for="post_two_tpa_allot_after_claim_no_received">Post Two TPA 1</label>
                            <select class="form-control" name="post_two_tpa_allot_after_claim_no_received"
                                id="post_two_tpa_allot_after_claim_no_received">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <!--Post TPA Allotment 2 -->
                        <div class="form-group">
                            <label for="post_two_tpa_allot_after_claim_no_received_two">Post Two TPA 2</label>
                            <select class="form-control" name="post_two_tpa_allot_after_claim_no_received_two"
                                id="post_two_tpa_allot_after_claim_no_received_two">
                                <option value="" disabled>Select TPA</option>
                                @foreach ($tpa_roles as $tpa)
                                <option value="{{ $tpa->id }}">
                                    {{ $tpa->f_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="modal fade" id="clainNoModal" tabindex="-1" aria-labelledby="clainNoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="clainNoForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="clainNoModalLabel">Claim No</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="case_id" id="caseIdclaim">

                        <div class="form-group">
                            <label for="claim_no">Main Claim no</label>
                            <input class="form-control" name="claim_no" id="claim_no_form_id" required>
                        </div>
                        <div class="form-group">
                            <label for="claim_no">Main Claim link</label>
                            <input class="form-control" name="claim_no_link" id="claim_no_link_form_id">
                        </div>

                        <div class="form-group">
                            <label for="post_claim_no">Post 1 claim no</label>
                            <input class="form-control" name="post_claim_no" id="post_claim_no_form_id">
                        </div>
                        <div class="form-group">
                            <label for="post_claim_no">Post 1 claim link</label>
                            <input class="form-control" name="post_claim_no_link" id="post_claim_no_link_form_id">
                        </div>

                        <div class="form-group">
                            <label for="post_two_claim_no">Post 2 claim no</label>
                            <input class="form-control" name="post_two_claim_no" id="post_two_claim_no_form_id" />
                        </div>
                        <div class="form-group">
                            <label for="post_two_claim_no">Post 2 claim link</label>
                            <input class="form-control" name="post_two_claim_no_link" id="post_two_claim_no_link_form_id" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editCaseModal" tabindex="-1" aria-labelledby="editCaseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editCaseForm" action="{{route('postsales.courier.case.update')}}" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCaseModalLabel">Edit Case</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row">
                    @csrf
                    <input type="hidden" name="case_id" >
                    <div class="form-group col-lg-6 col-sm-12">
                        <label for="status">Status</label>
                        <select class="form-control" name="status" id="status" required>
                            <option value="select">Select</option>
                            <option value="Query">Query</option>
                            <option value="Investigation">Investigation</option>
                            <option value="Reject"> Reject</option>
                            <option value="UnderProcess">UnderProcess</option>
                            <option value="Approved">Approved</option>
                            <option value="InProcess">In Process</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="approvedAmtGroup">
                        <label for="approved_amt">Approved Amount</label>
                        <input type="text" class="form-control" name="approved_amt" id="approved_amt">
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="queryTextGroup" style="display:none;">
                        <label for="query_text">Query Remark</label>
                        <textarea class="form-control" name="query_text" id="query_text" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="editCasePostModal" tabindex="-1" aria-labelledby="editCasePostModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editCasePostModalForm"  action="{{route('postsales.courier.case.update.post_one')}}" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCasePostModalLabel">Add Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row">
                    @csrf
                    <input type="hidden" name="case_id">
                    <div class="form-group col-lg-6 col-sm-12">
                        <label for="post_status">Status</label>
                        <select class="form-control" name="post_status" id="post_status" required>
                            <option value="select">Select</option>
                            <option value="Query">Query</option>
                            <option value="Investigation">Investigation</option>
                            <option value="Reject"> Reject</option>
                            <option value="UnderProcess">UnderProcess</option>
                            <option value="Approved">Approved</option>
                            <option value="InProcess">In Process</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="postAmtGroup">
                        <label for="post_ammount">Approved Amount</label>
                        <input type="text" class="form-control" name="post_ammount" id="post_ammount">
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="postQueryTextGroup" style="display:none;">
                        <label for="post_query_text">Query Remark</label>
                        <textarea class="form-control" name="post_query_text" id="post_query_text" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="editCasePostTwoModal" tabindex="-1" aria-labelledby="editCasePostTwoModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form id="editCasePostTwoModalForm" action="{{route('postsales.courier.case.update.post_two')}}" method="post" enctype="multipart/form-data">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCasePostTwoModalLabel">Add Files</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body row">
                    @csrf
                    <input type="hidden" name="case_id">
                    <div class="form-group col-lg-6 col-sm-12">
                        <label for="post_two_status">Status</label>
                        <select class="form-control" name="post_two_status" id="post_two_status" required>
                            <option value="select">Select</option>
                            <option value="Query">Query</option>
                            <option value="Investigation">Investigation</option>
                            <option value="Reject"> Reject</option>
                            <option value="UnderProcess">UnderProcess</option>
                            <option value="Approved">Approved</option>
                            <option value="InProcess">In Process</option>
                            <option value="Paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="postTwoAmtGroup">
                        <label for="post_two_ammount">Approved Amount</label>
                        <input type="text" class="form-control" name="post_two_ammount" id="post_two_ammount">
                    </div>
                    <div class="form-group col-lg-6 col-sm-12" id="postTwoQueryTextGroup" style="display:none;">
                        <label for="post_two_query_text">Query Remark</label>
                        <textarea class="form-control" name="post_two_query_text" id="post_two_query_text" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

@section('footer-script')
<script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
<script>

        function openClaimForm(caseId, claim_no, post_claim_no, post_two_claim_no, claim_no_link, post_claim_no_link, post_two_claim_no_link) {
            $('#caseIdclaim').val(caseId);
            $('#claim_no_form_id').val(claim_no);
            $('#claim_no_link_form_id').val(claim_no_link);
            $('#post_claim_no_form_id').val(post_claim_no);
            $('#post_claim_no_link_form_id').val(post_claim_no_link);
            $('#post_two_claim_no_form_id').val(post_two_claim_no);
            $('#post_two_claim_no_link_form_id').val(post_two_claim_no_link);
            $('#clainNoModal').modal('show');
        }

        $('#clainNoForm').on('submit', function(e) {
            e.preventDefault();
            let formDataArray = $(this).serializeArray();
            let formData = {};
            formDataArray.forEach(item => {
                formData[item.name] = item.value;
            });
            formData._token = '{{ csrf_token() }}';
            $.ajax({
                url: `{{ route('postsales.cases.save-claimno') }}`,
                method: 'POST',
                data: formData,
                success: function(response) {
                    alert(response.message);
                    $('#clainNoModal').modal('hide');
                    table.ajax.reload();
                    updateStatusCounts();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });


        $(document).ready(function() {
            let currentStatusFilter = 'all';
            
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('postsales_courier_ajax') }}`,
                    data: function(d) {
                        d.status_filter = currentStatusFilter;
                    },
                    dataSrc: 'data'
                },
                columns: [{
                        data: 'courier_id',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'case_code',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'case_type',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'claim_no',
                        render: function(data, type, row) {
                            if(row.case_type == 'main'){
                                return data;
                            }else if(row.case_type == 'post'){
                            return row.post_claim_no;
                        }else{
                            return row.post_two_claim_no;
                        }
                        },
                        visible: true,
                        searchable: true
                    },
                    {
                        data: 'name',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'corp',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'hospital',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'diagnosis',
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'member_id',
                        visible: true,
                        searchable: true
                    },
                    {
                        data: 'pre_courier_date',
                        render: function(data, type, row) {
                            if(row.case_type == 'main'){
                                return data ?`${moment(data).format("DD-MMM-YYYY")} ` : '-';
                            }else if(row.case_type == 'post'){
                            return row.post_courier_date ?`${moment(row.post_courier_date).format("DD-MMM-YYYY")} ` : '-';
                        }else{
                            return row.post_two_courier_date ?`${moment(row.post_two_courier_date).format("DD-MMM-YYYY")} ` : '-';
                        }
                        },
                        visible: true,
                        searchable: true,
                        type: 'date',
                    },
                    {
                        data: 'pod_no',
                        visible: true,
                        searchable: true
                    },
                    {
                        data: 'approved_amt',
                        render: function(data, type, row) {
                            if(row.case_type == 'main'){
                                return data;
                            }else if(row.case_type == 'post'){
                            return row.post_ammount;
                        }else{
                            return row.post_two_ammount;
                        }
                        },
                        visible: true,
                        searchable: true
                    },
                    {
                        data: 'status',
                        render: function(data, type, row) {
                            if(row.case_type == 'main'){
                                return `<button class="btn btn-success btn-sm" onclick="openCaseModal('${row.case_type}', ${row.id}, '${row.status || ''}', '${row.approved_amt || ''}', '${row.post_status || ''}', '${row.post_ammount || ''}', '${row.post_two_status || ''}', '${row.post_two_ammount || ''}')">${data || 'Need To set'}</button>`;
                            }else if(row.case_type == 'post'){
                            return `<button class="btn btn-success btn-sm" onclick="openCaseModal('${row.case_type}', ${row.id}, '${row.status || ''}', '${row.approved_amt || ''}', '${row.post_status || ''}', '${row.post_ammount || ''}', '${row.post_two_status || ''}', '${row.post_two_ammount || ''}')">${row.post_status|| 'Need To set'}</button>`;;
                        }else{
                            return `<button class="btn btn-success btn-sm" onclick="openCaseModal('${row.case_type}', ${row.id}, '${row.status || ''}', '${row.approved_amt || ''}', '${row.post_status || ''}', '${row.post_ammount || ''}', '${row.post_two_status || ''}', '${row.post_two_ammount || ''}')">${row.post_two_status || 'Need To set'}</button>`;;
                        }
                        },
                        visible: true,
                        searchable: true
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
return `<button class="btn btn-primary btn-sm" onclick="openClaimForm('${row.id}', '${row.claim_no || ''}', '${row.post_claim_no || ''}', '${row.post_two_claim_no || ''}', '${row.claim_no_link || ''}', '${row.post_claim_no_link || ''}', '${row.post_two_claim_no_link || ''}')">Claim no.</button>`;
                        },
                        orderable: false
                    },
                    {
                        data: null,
                        render: function(data, type, row) {
                            return '<div class="display-flex"><button class="btn btn-info btn-view-case" data-id="' +
                                row.id + '" data-encrypted-id="' + row.encrypted_id +
                                '">View</button></div>';
                        },
                        orderable: false
                    },
                ],
                order: [
                    [10, 'desc'],
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false,
                pageLength: 200,
                lengthMenu: [10, 25, 50, 75, 100,200,500],
                rowCallback: function(row, data, index) {
                    row.style.backgroundColor = data.case_color;
                    row.style.color = data.text_color;
                }
            });

            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id');
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/postsales/cases/view/' + encryptedId;
            });
            $(document).on('click', '.btn-del-case', function() {
                var caseId = $(this).data('id');
                var confirmDelete = window.confirm(
                    'Are you sure you want to delete this ? This action cannot be undone.');
                if (confirmDelete) {
                    window.location.href = '/admin/cases/delete/' + caseId;
                }
            });

            window.openCaseModal = function (caseType, caseId, mainStatus, mainAmt, postStatus, postAmt, postTwoStatus, postTwoAmt) {
        if (caseType === 'main') {
            $('#editCaseForm input[name="case_id"]').val(caseId);
            $('#editCaseForm select[name="status"]').val(mainStatus);
            $('#editCaseForm input[name="approved_amt"]').val(mainAmt);
            $('#editCaseModal').modal('show');
        } else if (caseType === 'post') {
            $('#editCasePostModalForm input[name="case_id"]').val(caseId);
            $('#editCasePostModalForm select[name="post_status"]').val(postStatus);
            $('#editCasePostModalForm input[name="post_ammount"]').val(postAmt);
            $('#editCasePostModal').modal('show');
        } else if (caseType === 'postTwo') {
            $('#editCasePostTwoModalForm input[name="case_id"]').val(caseId);
            $('#editCasePostTwoModalForm select[name="post_two_status"]').val(postTwoStatus);
            $('#editCasePostTwoModalForm input[name="post_two_ammount"]').val(postTwoAmt);
            $('#editCasePostTwoModal').modal('show');
        } else {
            alert('Invalid case type');
        }
    };

    // Main modal status change
    $('#status').on('change', function() {
        if ($(this).val() === 'Query') {
            $('#queryTextGroup').show();
            $('#approvedAmtGroup').hide();
        } else {
            $('#queryTextGroup').hide();
            $('#approvedAmtGroup').show();
        }
    });
    // Post modal status change
    $('#post_status').on('change', function() {
        if ($(this).val() === 'Query') {
            $('#postQueryTextGroup').show();
            $('#postAmtGroup').hide();
        } else {
            $('#postQueryTextGroup').hide();
            $('#postAmtGroup').show();
        }
    });
    // Post Two modal status change
    $('#post_two_status').on('change', function() {
        if ($(this).val() === 'Query') {
            $('#postTwoQueryTextGroup').show();
            $('#postTwoAmtGroup').hide();
        } else {
            $('#postTwoQueryTextGroup').hide();
            $('#postTwoAmtGroup').show();
        }
    });
    // Trigger change on modal open to set correct state
    $('#editCasePostModal').on('shown.bs.modal', function () {
        $('#post_status').trigger('change');
    });
    $('#editCasePostTwoModal').on('shown.bs.modal', function () {
        $('#post_two_status').trigger('change');
    });

        // Status filter functionality
        $('.status-filter-box').on('click', function() {
            const status = $(this).data('status');
            
            // Remove active class from all boxes
            $('.status-filter-box').removeClass('active');
            
            // Add active class to clicked box
            $(this).addClass('active');
            
            // Update current filter
            currentStatusFilter = status;
            
            // Reload table with new filter
            table.ajax.reload();
        });

        // Function to update status counts from backend
        function updateStatusCounts() {
            $.ajax({
                url: '{{ route("postsales.courier.status-counts") }}',
                method: 'GET',
                success: function(counts) {
                    // Update count displays
                    Object.keys(counts).forEach(function(status) {
                        const countElement = $('#count-' + status.toLowerCase());
                        if (countElement.length) {
                            countElement.text(counts[status]);
                        }
                    });
                    
                    // Handle special cases for status names
                    if (counts['Paid']) {
                        $('#count-paid').text(counts['Paid']);
                    }
                    if (counts['Reject']) {
                        $('#count-reject').text(counts['Reject']);
                    }
                    if (counts['empty_claim']) {
                        $('#count-empty-claim').text(counts['empty_claim']);
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching status counts:', xhr.responseText);
                }
            });
        }

        // Update counts when table is drawn (only for initial load)
        table.on('draw', function() {
            // Only update counts on initial draw, not on every filter change
            if (table.page.info().page === 0) {
                updateStatusCounts();
            }
        });

        // Set initial active state
        $('.status-filter-box[data-status="all"]').addClass('active');
        
        // Load initial status counts
        updateStatusCounts();

        // AJAX handlers for status update forms
        $('#editCaseForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert(response.message);
                    $('#editCaseModal').modal('hide');
                    table.ajax.reload();
                    updateStatusCounts();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });

        $('#editCasePostModalForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert(response.message);
                    $('#editCasePostModal').modal('hide');
                    table.ajax.reload();
                    updateStatusCounts();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });

        $('#editCasePostTwoModalForm').on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('_token', '{{ csrf_token() }}');
            
            $.ajax({
                url: $(this).attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    alert(response.message);
                    $('#editCasePostTwoModal').modal('hide');
                    table.ajax.reload();
                    updateStatusCounts();
                },
                error: function(xhr) {
                    alert('An error occurred. Please try again.');
                    console.error(xhr.responseText);
                }
            });
        });
        });
</script>
@endsection
