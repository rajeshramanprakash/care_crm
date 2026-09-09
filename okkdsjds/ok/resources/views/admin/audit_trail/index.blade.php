@extends('admin.layouts.app')
@section('title', $page_heading)
@section('header-css')
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
            <div class="card">
                <div class="card-body">
                    <form id="search-form">
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="case_code" placeholder="Enter Case Code">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </div>
                    </form>
                    <div id="results" class="mt-4">
                        <!-- Results will be displayed here -->
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
@section('footer-script')
<script>
    $(document).ready(function() {
        $('#search-form').on('submit', function(e) {
            e.preventDefault();
            const caseCode = $('#case_code').val();

            if (caseCode) {
                $.ajax({
                    url: "{{ route('admin.audit-trail.ajax') }}",
                    method: "GET",
                    data: { case_code: caseCode },
                    success: function(response) {
                        if (response.success) {
                            let html = '<table class="table table-bordered">';
                            html += '<thead><tr><th>ID</th><th>Case Code</th><th>File Uploaded By</th><th>Department</th><th>Claim Type</th><th>Created At</th></tr></thead>';
                            html += '<tbody>';
                            response.data.forEach(row => {
                                html += `<tr>
                                    <td>${row.id}</td>
                                    <td>${caseCode}</td>
                                    <td>${row.user_name}</td>
                                    <td>${row.role_name}</td>
                                    <td>${row.claim_type}</td>
                                    <td>${new Date(row.created_at).toLocaleString('en-IN', {timeZone: 'Asia/Kolkata',year: 'numeric',month: '2-digit',day: '2-digit',hour: '2-digit',minute: '2-digit',})}</td>
                                </tr>`;
                            });
                            html += '</tbody></table>';
                            $('#results').html(html);
                        } else {
                            $('#results').html(`<div class="alert alert-warning">${response.message}</div>`);
                        }
                    },
                    error: function() {
                        $('#results').html('<div class="alert alert-danger">An error occurred while fetching data.</div>');
                    }
                });
            } else {
                $('#results').html('<div class="alert alert-warning">Please enter a case code.</div>');
            }
        });
    });
</script>
@endsection
