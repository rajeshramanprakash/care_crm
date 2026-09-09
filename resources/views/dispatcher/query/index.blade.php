@extends('dispatcher.layouts.app')

@section('title', $page_heading)

@section('header-css')
    <link rel="stylesheet" href="//cdn.datatables.net/1.13.1/css/jquery.dataTables.min.css">
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
                <!-- Status Filter Cards -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-2 col-sm-4 col-6 mb-2">
                                        <div class="status-filter-box" data-status="all" style="background-color: #007BFF; color: white; padding: 15px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s;">
                                            <strong>All Queries</strong>
                                            <div class="count" id="count-all">-</div>
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
                                <th>Vendor</th>
                                <th>Claim Type</th>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Corp</th>
                                <th>Hospital</th>
                                <th>Diagnosis</th>
                                <th>Date of Admission</th>
                                <th>Date of Discharge</th>
                                <th>Claim No</th>
                                <th>Query</th>
                                <th>View Files</th>
                                <th>Upload PDF</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

    </div>




@endsection
@section('footer-script')
    <script src="//cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>
    <script>
        $(document).ready(function() {
            let currentStatusFilter = 'all';
            
            // Load status counts on page load
            loadStatusCounts();
            
            function loadStatusCounts() {
                $.ajax({
                    url: '{{ route("dispatcher.query.status-counts") }}',
                    method: 'GET',
                    success: function(response) {
                        $('#count-all').text(response.all);
                        $('#count-empty-claim').text(response.empty_claim);
                    },
                    error: function() {
                        $('#count-all').text('0');
                        $('#count-empty-claim').text('0');
                    }
                });
            }
            
            const table = $('#casesTable').DataTable({
                processing: true,
                serverSide: false,
                ajax: {
                    url: `{{ route('dispatcher_query_courier_ajax') }}`,
                    data: function(d) {
                        d.status_filter = currentStatusFilter;
                    },
                    dataSrc: 'data'
                },
                columns: [
                { data: 'courier_id', searchable: true, sortable: true },
                { data: 'f_name', searchable: true, sortable: true },
                { data: 'case_type', searchable: true, sortable: true },
                { data: 'name', searchable: true, sortable: true },
                { 
                    data: null,
                    render: function(data, type, row) {
                        return row.age + (row.gender ? ' (' + row.gender + ')' : '');
                    },
                    searchable: true, 
                    sortable: true 
                },
                    { data: 'corp', searchable: true, sortable: true },
                { data: 'hospital', searchable: true, sortable: true },
                { data: 'diagnosis', searchable: true, sortable: true },
                {
                        data: 'doa',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.doa_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: true
                    },
                    {
                        data: 'dod',
                        render: function(data, type, row) {
                            return data ?
                                `${moment(data).format("DD-MMM-YYYY")} at ${moment(row.dod_time, "HH:mm:ss").format("hh:mm A")}` :
                                '-';
                        },
                        searchable: true,
                        sortable: true
                    },
                {
                    data: null,
                    render: function(data, type, row) {
                        if(row.case_type == 'main'){
                            return row.claim_no;
                        }else if(row.case_type == 'post'){
                            return row.post_claim_no;
                        }else{
                            return row.post_two_claim_no;
                        }
                    },
                    searchable: true,
                    sortable: true
                },
                { data: 'query_text', searchable: true, sortable: true },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `<button class="btn btn-info btn-view-files" data-id="${row.id}">View Files</button>`;
                    },
                    orderable: false
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return `<form class='upload-pdf-form' data-id='${row.query_id}' enctype='multipart/form-data' style='display:inline;'>
                            <input type='file' name='pdf_file' accept='application/pdf' style='display:none;' />
                            <input type='hidden' name='pdf_field' value='query_pdf' />
                            <button type='button' class='btn btn-warning btn-upload-pdf'>Upload PDF</button>
                        </form>`;
                    },
                    orderable: false
                },
                {
                    data: null,
                    render: function(data, type, row) {
                        return '<div class="display-flex"><button class="btn btn-info btn-view-case" data-id="' + row.id + '" data-encrypted-id="' + row.encrypted_id + '">View</button></div> ';
                    },
                    orderable: false
                },
            ],
                order: [
                    [0, 'desc']
                ],
                responsive: true,
                paging: true,
                searching: true,
                lengthChange: true,
                autoWidth: false,
                pageLength: 200,
                lengthMenu: [10, 25, 50, 75, 100, 200, 500],
                rowCallback: function(row, data, index) {
                    row.style.backgroundColor = data.case_color;
                    row.style.color = data.text_color;
                }
            });

            // Status filter click handlers
            $(document).on('click', '.status-filter-box', function() {
                $('.status-filter-box').removeClass('active');
                $(this).addClass('active');
                currentStatusFilter = $(this).data('status');
                table.ajax.reload();
                // Refresh counts when filter changes
                loadStatusCounts();
            });

            // Set initial active state
            $('.status-filter-box[data-status="all"]').addClass('active');

            $(document).on('click', '.btn-view-case', function() {
                var caseId = $(this).data('id');
                var encryptedId = $(this).data('encrypted-id');
                window.location.href = '/dispatcher/cases/view/' + encryptedId;
            });

            $(document).on('click', '.btn-view-files', function() {
                var caseId = $(this).data('id');
                $.ajax({
                    url: '/dispatcher/query/files/' + caseId,
                    method: 'GET',
                    success: function(response) {
                        var files = response.files;
                        var labelMap = {
                            'aadhar_attachment': 'Aadhar Attachment',
                            'aadhar_attachment_2': 'Aadhar Attachment 2',
                            'policy': 'Policy',
                            'icp_attachment': 'ICP Attachment',
                            'medicine_vitals_attached': 'Medicine Vitals',
                            'pre_dispatch_pdf_attachment': 'Pre Dispatch PDF',
                            'post_dispatch_pdf_attachment': 'Post Dispatch PDF',
                            'post_two_dispatch_pdf_attachment': 'Post Two Dispatch PDF',
                            'bill_attachment_1': 'Bill Attachment 1',
                            'bill_attachment_post': 'Bill Attachment Post',
                            'bill_attachment_post_two': 'Bill Attachment Post Two',
                            'discharge_summary_attachment': 'Discharge Summary',
                        };
                        var iconMap = {
                            'pdf': '<i class="fa fa-file-pdf-o text-danger"></i>',
                            'jpg': '<i class="fa fa-file-image-o text-info"></i>',
                            'jpeg': '<i class="fa fa-file-image-o text-info"></i>',
                            'png': '<i class="fa fa-file-image-o text-info"></i>',
                            'doc': '<i class="fa fa-file-word-o text-primary"></i>',
                            'docx': '<i class="fa fa-file-word-o text-primary"></i>',
                            'xls': '<i class="fa fa-file-excel-o text-success"></i>',
                            'xlsx': '<i class="fa fa-file-excel-o text-success"></i>',
                            'default': '<i class="fa fa-file-o text-secondary"></i>'
                        };
                        function getFileIcon(filename) {
                            var ext = filename.split('.').pop().toLowerCase();
                            return iconMap[ext] || iconMap['default'];
                        }
                        var allKeys = [
                            'medicine_vitals_attached',
                            'aadhar_attachment',
                            'aadhar_attachment_2',
                            'icp_attachment',
                            'policy',
                            'pre_dispatch_pdf_attachment',
                            'post_dispatch_pdf_attachment',
                            'post_two_dispatch_pdf_attachment',
                            'bill_attachment_1',
                            'bill_attachment_post',
                            'bill_attachment_post_two',
                            'discharge_summary_attachment',
                        ];
                        var html = '<div class="container-fluid">';
                        html += '<div class="row">';
                        html += '<div class="col-12">';
                        html += '<div class="list-group">';
                        allKeys.forEach(function(key) {
                            var label = labelMap[key] || key.replace(/_/g, ' ');
                            html += '<div class="d-flex align-items-center mb-2">';
                            html += '<span class="font-weight-bold mr-2" style="min-width:180px;">' + label + ':</span>';
                            if (files[key]) {
                                var filename = files[key].split('/').pop();
                                html += getFileIcon(filename) +
                                    '<a href="/storage/' + files[key] + '" target="_blank" class="ml-2">View</a>' +
                                    '<a href="/storage/' + files[key] + '" download class="btn btn-primary btn-sm ml-2"><i class="fa fa-download"></i> Download</a>';
                            } else {
                                html += '<span class="text-muted">Not Available</span>';
                            }
                            html += '</div>';
                        });
                        html += '</div></div></div></div>';
                        $('#viewFilesModal .modal-body').html(html);
                        $('#viewFilesModal').modal('show');
                    },
                    error: function() {
                        $('#viewFilesModal .modal-body').html('<p>Error loading files.</p>');
                        $('#viewFilesModal').modal('show');
                    }
                });
            });

            $(document).on('click', '.btn-upload-pdf', function() {
                $(this).closest('form').find('input[type="file"]').click();
            });

            $(document).on('change', '.upload-pdf-form input[type="file"]', function() {
                var form = $(this).closest('form');
                var queryId = form.data('id');
                var file = this.files[0];
                var pdfField = form.find('input[name="pdf_field"]').val();
                var formData = new FormData();
                formData.append('pdf_file', file);
                formData.append('pdf_field', pdfField);
                // AJAX call to upload PDF
                $.ajax({
                    url: '/dispatcher/query/' + queryId + '/update-pdf',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        alert(response.message || 'PDF uploaded successfully.');
                        // Reload table to show updated data without page refresh
                        table.ajax.reload(null, false); // false = stay on current page
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'PDF upload failed.');
                    }
                });
            });


        });
    </script>
@endsection

<!-- Modal for View Files -->
<div class="modal fade" id="viewFilesModal" tabindex="-1" aria-labelledby="viewFilesModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewFilesModalLabel">View Files</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Files content will be loaded here via AJAX -->
        <p>Files for this case will be shown here.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
