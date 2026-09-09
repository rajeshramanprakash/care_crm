@extends('medicinevital.layouts.app')

@section('title', 'Case Details')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
@endsection

@section('main')
    <div class="content-wrapper pb-5 pt-4">
        <section class="content">
            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information</h3>
                    <button href="javascript:void(0);" class="btn p-0 text-light float-right" title="Edit Case info."
                        data-bs-toggle="modal" data-bs-target="#editCaseModal"><i class="fa fa-edit"></i></button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Id: </span>
                            <span class="mx-1"> {{ $case->id }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Case Code: </span>
                            <span class="mx-1">{{ $case->case_code }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)"> Name: </span>
                            <span class="mx-1">{{ $case->name }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Age: </span>
                            <span class="mx-1">{{ $case->age }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Gender: </span>
                            <span class="mx-1 ">{{ $case->gender }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Admission: </span>
                            <span class="mx-1"> {{ date('d-M-Y', strtotime($case->doa)) }} at {{ date('h:i A', strtotime($case->doa_time)) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Discharge: </span>
                            <span class="mx-1"> {{ date('d-M-Y', strtotime($case->dod)) }} at {{ date('h:i A', strtotime($case->dod_time)) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Hospital: </span>
                            <span class="mx-1">{{ $case->hospital ?? 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Diagnosis: </span>
                            <span class="mx-1">{{ $case->diagnosis ?? 'N/A' }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">ICP Attachment: </span>
                            @if ($case->icp_attachment)
                                <a href="{{ asset('storage/' . $case->icp_attachment) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="cancelRemarkModal" tabindex="-1" aria-labelledby="cancelRemarkModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form id="cancelRemarkForm" action="{{ route('admin_cases_status_remark') }}" method="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelRemarkModalLabel">Cancellation Remark</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                        </div>
                        @csrf
                        <input type="hidden" name="id" value="{{ $case->id }}">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="remark">Please provide a reason for cancellation:</label>
                                <textarea class="form-control" id="remark" name="remark" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-danger">Submit Remark</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <div class="modal fade" id="editCaseModal" tabindex="-1" aria-labelledby="editCaseModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="editCaseForm" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCaseModalLabel">Edit Case</h5>
                            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-sm-12 col-lg-6">
                                <label for="medicine_vitals_attached">Medicine Vitals</label>
                                <input type="file" class="form-control" name="medicine_vitals_attached"
                                    id="medicine_vitals_attached">
                            </div>
                            <div class="form-group col-sm-12 col-lg-6">
                                <label for="medicine_detail">Medicine Details</label>
                                <input type="file" class="form-control" name="medicine_detail" id="medicine_detail">
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

@section('footer-script')
    <script>
        function openRemarkModal() {
            $('#cancelRemarkModal').modal('show');
        }
        $(document).ready(function() {
            $('#editCaseForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('medicinevital.case.update', $case->id) }}`,
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#editCaseModal').modal('hide');
                            alert(response.message);
                            window.location.href = `{{ route('medicinevital.case.index') }}`;
                        } else {
                            alert('Error: ' + response.message);
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('AJAX Error: ', textStatus, errorThrown);
                        alert('An error occurred: ' + textStatus);
                    }
                });
            });
        });
    </script>
@endsection

@endsection
