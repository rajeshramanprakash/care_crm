@extends('lab.layouts.app')

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
                    @if ($case->check_box == 1)
                        <div class="btn btn-success text-light float-right">
                            Lab Created
                        </div>
                    @else
                        <a href="{{ route('lab.case.update', $case->id) }}" class="btn btn-primary text-light float-right"
                            onclick="return confirm('Are you sure you want to create this Lab case?');">
                            Create Lab
                        </a>
                    @endif
                    <button class="btn btn-info float-right mr-2" data-bs-toggle="modal" data-bs-target="#uploadLabFilesModal">Upload Lab File(s)</button>
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
                            <span class="mx-1 badge ">{{ $case->gender }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Admission: </span>
                            <span class="mx-1"> {{ date('d-M-Y', strtotime($case->doa)) }} at {{ date('h:i A', strtotime($case->doa_time)) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Discharge: </span>
                            <span class="mx-1"> {{ date('d-M-Y', strtotime($case->dod)) }} at
                                {{ date('h:i A', strtotime($case->dod_time)) }}</span>
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
                            <span class="text-bold mx-1" style="color: var(--wb-wood)"> Bill Range: </span>
                            <span class="mx-1">{{ $case->bill_range }}</span>
                        </div>
                    </div>
                </div>
            </div>


            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information Files</h3>
                </div>
                <div class="card-body">
                    <div class="row">
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
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Medicine Vitals: </span>
                            @if ($case->medicine_vitals_attached)
                                <a href="{{ asset('storage/' . $case->medicine_vitals_attached) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Medicine Detail: </span>
                            @if ($case->medicine_detail)
                                <a href="{{ asset('storage/' . $case->medicine_detail) }}" target="_blank"
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

            @if ($case->lab_files && is_array($case->lab_files) && count($case->lab_files))
                <div class="card mb-3">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Lab Files</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach ($case->lab_files as $idx => $file)
                                <div class="col-sm-6 mb-2">
                                    <span class="text-bold mx-1" style="color: var(--wb-wood)">File {{ $idx + 1 }}: </span>
                                    <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $file) }}" download class="btn btn-sm btn-outline-secondary ml-2">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if ($case->is_post_1 == 1)
                <div class="card mb-3">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Post 1 Information</h3>
                        @if ($case->check_box_post == 1)
                            <div class="btn btn-success text-light float-right">
                                Lab Created
                            </div>
                        @else
                            <a href="{{ route('lab.case.update.post_one', $case->id) }}"
                                class="btn btn-primary text-light float-right"
                                onclick="return confirm('Are you sure you want to create this Lab case?');">
                                Create Lab
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">OPD Attachemnt: </span>
                                @if ($case->opd_attachment)
                                    <a href="{{ asset('storage/' . $case->opd_attachment) }}" target="_blank"
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
            @endif

            @if ($case->is_post_2 == 1)
                <div class="card mb-3">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Post 2 Information</h3>
                        @if ($case->check_box_post_two == 1)
                            <div class="btn btn-success text-light float-right">
                                Lab Created
                            </div>
                        @else
                            <a href="{{ route('lab.case.update.post_two', $case->id) }}"
                                class="btn btn-primary text-light float-right"
                                onclick="return confirm('Are you sure you want to create this Lab case?');">
                                Create Lab
                            </a>
                        @endif
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">OPD Attachemnt: </span>
                                @if ($case->opd_attachment_2)
                                    <a href="{{ asset('storage/' . $case->opd_attachment_2) }}" target="_blank"
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
            @endif
        </section>
    </div>

    <!-- Upload Lab Files Modal -->
    <div class="modal fade" id="uploadLabFilesModal" tabindex="-1" aria-labelledby="uploadLabFilesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadLabFilesModalLabel">Upload Lab File(s)</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                </div>
                <form id="labFilesUploadForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div id="labFilesInputs">
                            <div class="input-group mb-2 lab-file-input-row">
                                <input type="file" name="lab_files[]" class="form-control" accept="application/pdf,image/*">
                                <button type="button" class="btn btn-success btn-add-file-input" title="Add another file">+</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">You can upload multiple files. Max size per file: 50MB.</small>
                        <div id="labFilesUploadError" class="text-danger mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@section('footer-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('labFilesUploadForm');
            const errorDiv = document.getElementById('labFilesUploadError');
            const inputsContainer = document.getElementById('labFilesInputs');

            // Add or remove file input fields
            inputsContainer.addEventListener('click', function(e) {
                if (e.target.classList.contains('btn-add-file-input')) {
                    const newRow = document.createElement('div');
                    newRow.className = 'input-group mb-2 lab-file-input-row';
                    newRow.innerHTML = `
                        <input type="file" name="lab_files[]" class="form-control" accept="application/pdf,image/*">
                        <button type="button" class="btn btn-danger btn-remove-file-input" title="Remove this file">🗑️</button>
                    `;
                    inputsContainer.appendChild(newRow);
                }
                if (e.target.classList.contains('btn-remove-file-input')) {
                    e.target.closest('.lab-file-input-row').remove();
                }
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                errorDiv.textContent = '';
                const formData = new FormData();

                // Collect all files from all input fields
                inputsContainer.querySelectorAll('input[type="file"]').forEach(input => {
                    if (input.files.length > 0) {
                        formData.append('lab_files[]', input.files[0]);
                    }
                });

                fetch(`{{ route('lab.case.upload_lab_files', $case->id) }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        errorDiv.textContent = 'Upload failed.';
                    }
                })
                .catch(err => {
                    errorDiv.textContent = 'Upload failed or file too large.';
                });
            });
        });
    </script>
@endsection

@endsection
