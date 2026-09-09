@extends('subadmin.layouts.app')
@section('title', 'Case Details')
@section('header-css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
@endsection
@section('main')
    <div class="content-wrapper pb-5">
        <div class="card-header mb-3">
            <div class="dropdown d-inline-block">
                @if ($case->forward_status == 0 && $case->forward_status_remark == null)
                    <button class="btn btn-secondary dropdown-toggle btn-xs p-2 m-1" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Forward Status: Pending
                    </button>
                @elseif ($case->forward_status == 0 && $case->forward_status_remark != null)
                    <button class="btn btn-warning dropdown-toggle btn-xs p-2 m-1" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Forward Status: Hold
                    </button>
                @elseif($case->forward_status == 1)
                    <button class="btn btn-success dropdown-toggle btn-xs p-2 m-1" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Forward Status: Forward
                    </button>
                @elseif($case->forward_status == 2)
                    <button class="btn btn-danger dropdown-toggle btn-xs p-2 m-1" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Forward Status: Canceled
                    </button>
                @endif
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="openHoldRemarkModal()">Hold</a></li>
                    @if ($case->forward_status != 1)
                        <li><a class="dropdown-item"
                                href="{{ route('subadmin.caseStatus.update', ['cases_id' => $case->id, 'status' => 1]) }}">Re-forward</a>
                        </li>
                    @endif
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="openRemarkModal()">Cancel</a></li>
                </ul>
            </div>

            <div class="dropdown d-inline-block">
                @if ($case->forward_status == 2)
                    <button class="btn btn-danger btn-xs p-2 m-1"
                        onclick="handle_view_message(`{{ $case->forward_status_remark ?: 'N/A' }}`)"> Forward Status
                        Remark</button>
                @endif
            </div>
        </div>
        <section class="content">
            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Cases with same member Id</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach ($cases_with_same_memeber_id as $cas)
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">{{ $cas->id }}:</span>
                                <span class="mx-1">{{ $cas->case_code }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information</h3>
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
                            <span class="mx-1">{{ $case->gender }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Corp: </span>
                            <span class="mx-1">{{ $case->corp ?: 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Relation:
                            </span>
                            <span class="mx-1">{{ $case->relation ?: 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Member Id:
                            </span>
                            <span class="mx-1">{{ $case->member_id ?: 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Admission: </span>
                            <span class="mx-1">{{ date('d-M-Y', strtotime($case->doa)) }} at
                                {{ date('h:i A', strtotime($case->doa_time)) }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Date of Discharge: </span>
                            <span class="mx-1">{{ date('d-M-Y', strtotime($case->dod)) }} at
                                {{ date('h:i A', strtotime($case->dod_time)) }}</span>
                        </div>

                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information Sub Admin</h3>
                    <button href="javascript:void(0);" class="btn p-0 text-light float-right" title="Edit Case info."
                        data-bs-toggle="modal" data-bs-target="#editCaseModal"><i class="fa fa-edit"></i></button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">SI: </span>
                            <span class="mx-1"> {{ $case->sum_insured }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)"> Bill Range: </span>
                            <span class="mx-1">{{ $case->bill_range }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Past Hospital: </span>
                            <span class="mx-1">{{ $case->past_hospital }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Past Diagnosis: </span>
                            <span class="mx-1">{{ $case->past_diagnosis }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Tpa: </span>
                            <span class="mx-1">{{ $case->tpa ?: 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Alot Tpa:
                            </span>
                            <span class="mx-1">{{ $case->tpa_allot_after_claim_no_received ?: 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Claim No:
                            </span>
                            <span class="mx-1">{{ $case->claim_no ?: 'N/A' }}</span>
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
                                <a href="{{ asset('storage/' . $case->icp_attachment) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
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
                                <a href="{{ asset('storage/' . $case->medicine_vitals_attached) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
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
                                <a href="{{ asset('storage/' . $case->medicine_detail) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Aadhar Attachment: </span>
                            @if ($case->aadhar_attachment)
                                <a href="{{ asset('storage/' . $case->aadhar_attachment) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->aadhar_attachment) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Aadhar Attachment 2: </span>
                            @if ($case->aadhar_attachment_2)
                                <a href="{{ asset('storage/' . $case->aadhar_attachment_2) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->aadhar_attachment_2) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">PAN Card: </span>
                            @if ($case->pan_card)
                                <a href="{{ asset('storage/' . $case->pan_card) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->pan_card) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Cancelled Cheque: </span>
                            @if ($case->cancelled_cheque)
                                <a href="{{ asset('storage/' . $case->cancelled_cheque) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->cancelled_cheque) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Policy: </span>
                            @if ($case->policy)
                                <a href="{{ asset('storage/' . $case->policy) }}" target="_blank" class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->policy) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Bill Attachment: </span>
                            @if ($case->bill_attachment_1)
                                <a href="{{ asset('storage/' . $case->bill_attachment_1) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->bill_attachment_1) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Discharge Summary: </span>
                            @if ($case->discharge_summary_attachment)
                                <a href="{{ asset('storage/' . $case->discharge_summary_attachment) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->discharge_summary_attachment) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Dispatch Pdf Attachment: </span>
                            @if ($case->pre_dispatch_pdf_attachment)
                                <a href="{{ asset('storage/' . $case->pre_dispatch_pdf_attachment) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->pre_dispatch_pdf_attachment) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
                                </a>
                            @else
                                <span class="text-muted">Not Available</span>
                            @endif
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Patient Details: </span>
                            @if ($case->patient_details_form)
                                <a href="{{ asset('storage/' . $case->patient_details_form) }}" target="_blank"
                                    class="text-primary">
                                    <i class="bi bi-file-earmark-text"></i> View
                                </a>
                                <a href="{{ asset('storage/' . $case->patient_details_form) }}" download
                                    class="btn btn-sm btn-primary m-1">
                                    <i class="bi bi-download"></i> Download
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
                <form id="cancelRemarkForm" action="{{ route('subadmin_cases_status_remark') }}" method="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="cancelRemarkModalLabel">Cancellation Remark</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
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
        <div class="modal fade" id="holdRemarkModal" tabindex="-1" aria-labelledby="holdRemarkModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <form id="cancelRemarkForm" action="{{ route('subadmin_cases_status_remark_hold') }}" method="POST">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="holdRemarkModalLabel">Hold Remark</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        @csrf
                        <input type="hidden" name="id" value="{{ $case->id }}">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="remark">Please provide a reason for Hold:</label>
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


        <div class="modal fade" id="editCaseModal" tabindex="-1" aria-labelledby="editCaseModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="editCaseForm" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCaseModalLabel">Edit Case</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" name="name" id="name"
                                    value="{{ $case->name }}" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="age">Age</label>
                                <input type="number" class="form-control" name="age" id="age"
                                    value="{{ $case->age }}" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="corp">Corp</label>
                                <input type="text" class="form-control" name="corp" id="corp"
                                    value="{{ $case->corp }}" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="member_id">Member Id</label>
                                <input type="text" class="form-control" name="member_id" id="member_id"
                                    value="{{ $case->member_id }}" required>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="relation">Relation</label>
                                <select class="form-control" name="relation" id="relation" required>
                                    <option value="self" {{ $case->relation == 'self' ? 'selected' : '' }}>Self
                                    </option>
                                    <option value="mother" {{ $case->relation == 'mother' ? 'selected' : '' }}>Mother
                                    </option>
                                    <option value="father" {{ $case->relation == 'father' ? 'selected' : '' }}>Father
                                    </option>
                                    <option value="daughter" {{ $case->relation == 'daughter' ? 'selected' : '' }}>
                                        Daughter</option>
                                    <option value="son" {{ $case->relation == 'son' ? 'selected' : '' }}>Son</option>
                                    <option value="husband" {{ $case->relation == 'husband' ? 'selected' : '' }}>Husband
                                    </option>
                                    <option value="wife" {{ $case->relation == 'wife' ? 'selected' : '' }}>Wife
                                    </option>
                                    <option value="brother" {{ $case->relation == 'brother' ? 'selected' : '' }}>Brother
                                    </option>
                                    <option value="sister" {{ $case->relation == 'sister' ? 'selected' : '' }}>Sister
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="gender">Gender</label>
                                <select class="form-control" name="gender" id="gender" required>
                                    <option value="Male" {{ $case->gender == 'Male' ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ $case->gender == 'Female' ? 'selected' : '' }}>Female
                                    </option>
                                    <option value="Other" {{ $case->gender == 'Other' ? 'selected' : '' }}>Other
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="doa">Date of Admission</label>
                                <input type="date" class="form-control" name="doa" id="doa"
                                    value="{{ $case->doa }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="doa_time">Time of Admission</label>
                                <input type="time" class="form-control" name="doa_time" id="doa_time"
                                    value="{{$case->doa_time}}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="dod">Date of Discharge</label>
                                <input type="date" class="form-control" name="dod" id="dod"
                                    value="{{ $case->dod }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="dod_time">Time of Discharge</label>
                                <input type="time" class="form-control" name="dod_time" id="dod_time"
                                    value="{{ $case->dod_time }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="sum_insured">SI</label>
                                <input type="text" class="form-control" name="sum_insured" id="sum_insured"
                                    value="{{ $case->sum_insured }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="bill_range">Bill Range</label>
                                <input type="text" class="form-control" name="bill_range" id="bill_range"
                                    value="{{ $case->bill_range }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="past_hospital">Past Hospital</label>
                                <input type="text" class="form-control" name="past_hospital" id="past_hospital"
                                    value="{{ $case->past_hospital }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="past_diagnosis">Past Diagnosis</label>
                                <input type="text" class="form-control" name="past_diagnosis" id="past_diagnosis"
                                    value="{{ $case->past_diagnosis }}">
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

        <div class="modal fade" id="editCaseFileModal" tabindex="-1" aria-labelledby="editCaseFileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="editCaseFileForm" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCaseFileModalLabel">Edit Case Files</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="aadhar_attachment">Aadhar Attachment</label>
                                <input type="file" class="form-control" name="aadhar_attachment"
                                    id="aadhar_attachment">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="aadhar_attachment_2">Aadhar Attachment 2</label>
                                <input type="file" class="form-control" name="aadhar_attachment_2"
                                    id="aadhar_attachment_2">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="pan_card">PAN Card</label>
                                <input type="file" class="form-control" name="pan_card" id="pan_card">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="cancelled_cheque">Cancelled Cheque</label>
                                <input type="file" class="form-control" name="cancelled_cheque"
                                    id="cancelled_cheque">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="policy">Policy</label>
                                <input type="file" class="form-control" name="policy" id="policy">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>

                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="icp_attachment">ICP Attachment</label>
                                <input type="file" class="form-control" name="icp_attachment" id="icp_attachment">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="patient_details_form">Patient Details Form</label>
                                <input type="file" class="form-control" name="patient_details_form"
                                    id="patient_details_form">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="medicine_vitals_attached">Medicine Vitals</label>
                                <input type="file" class="form-control" name="medicine_vitals_attached"
                                    id="medicine_vitals_attached">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="medicine_detail">Medicine Detail</label>
                                <input type="file" class="form-control" name="medicine_detail" id="medicine_detail">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="bill_attachment_1">Bill Attachment</label>
                                <input type="file" class="form-control" name="bill_attachment_1"
                                    id="bill_attachment_1">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="discharge_summary_attachment">Discharge Summary</label>
                                <input type="file" class="form-control" name="discharge_summary_attachment"
                                    id="discharge_summary_attachment">
                                <small class="text-muted">Leave blank if you don't want to change</small>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="patient_details_form">Patient Details Form</label>
                                <input type="file" class="form-control" name="patient_details_form"
                                    id="patient_details_form">
                                <small class="text-muted">Leave blank if you don't want to change</small>
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


    <!-- Post 1 Edit Modal -->
    <div class="modal fade" id="editPost1Modal" tabindex="-1" aria-labelledby="editPost1ModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editPost1Form" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPost1ModalLabel">Edit Post 1 Information</h5>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="case_id" value="{{ $case->id }}">
                        <div class="form-group col-lg-6">
                            <label for="post_courier_no">Courier No</label>
                            <input type="text" class="form-control" name="post_courier_no" id="post_courier_no"
                                value="{{ $case->post_courier_no }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_courier_date">Courier Date</label>
                            <input type="date" class="form-control" name="post_courier_date" id="post_courier_date"
                                value="{{ $case->post_courier_date }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_claim_no">Claim No</label>
                            <input type="text" class="form-control" name="post_claim_no" id="post_claim_no"
                                value="{{ $case->post_claim_no }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_ammount">Approved Amount</label>
                            <input type="text" class="form-control" name="post_ammount" id="post_ammount"
                                value="{{ $case->post_ammount }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_patient_details_form">Patient Details Form</label>
                            <input type="file" class="form-control" name="post_patient_details_form"
                                id="post_patient_details_form">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_dispatch_pdf_attachment">Dispatch PDF</label>
                            <input type="file" class="form-control" name="post_dispatch_pdf_attachment"
                                id="post_dispatch_pdf_attachment">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="opd_attachment">OPD Attachment</label>
                            <input type="file" class="form-control" name="opd_attachment" id="opd_attachment">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="bill_attachment_post">Bill Attachment</label>
                            <input type="file" class="form-control" name="bill_attachment_post"
                                id="bill_attachment_post">
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

    <!-- Post 2 Edit Modal -->
    <div class="modal fade" id="editPost2Modal" tabindex="-1" aria-labelledby="editPost2ModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form id="editPost2Form" enctype="multipart/form-data">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPost2ModalLabel">Edit Post 2 Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body row">
                        <input type="hidden" name="case_id" value="{{ $case->id }}">
                        <div class="form-group col-lg-6">
                            <label for="post_two_courier_no">Courier No</label>
                            <input type="text" class="form-control" name="post_two_courier_no"
                                id="post_two_courier_no" value="{{ $case->post_two_courier_no }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_two_courier_date">Courier Date</label>
                            <input type="date" class="form-control" name="post_two_courier_date"
                                id="post_two_courier_date" value="{{ $case->post_two_courier_date }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_two_claim_no">Claim No</label>
                            <input type="text" class="form-control" name="post_two_claim_no"
                                id="post_two_claim_no" value="{{ $case->post_two_claim_no }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_two_ammount">Approved Amount</label>
                            <input type="text" class="form-control" name="post_two_ammount" id="post_two_ammount"
                                value="{{ $case->post_two_ammount }}">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_two_patient_details_form">Patient Details Form</label>
                            <input type="file" class="form-control" name="post_two_patient_details_form"
                                id="post_two_patient_details_form">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="post_two_dispatch_pdf_attachment">Dispatch PDF</label>
                            <input type="file" class="form-control" name="post_two_dispatch_pdf_attachment"
                                id="post_two_dispatch_pdf_attachment">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="opd_attachment_2">OPD Attachment</label>
                            <input type="file" class="form-control" name="opd_attachment_2"
                                id="opd_attachment_2">
                        </div>
                        <div class="form-group col-lg-6">
                            <label for="bill_attachment_post_two">Bill Attachment</label>
                            <input type="file" class="form-control" name="bill_attachment_post_two"
                                id="bill_attachment_post_two">
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


@section('footer-script')
    <script>
        function openRemarkModal() {
            $('#cancelRemarkModal').modal('show');
        }

        function openHoldRemarkModal() {
            $('#holdRemarkModal').modal('show');
        }
        $(document).ready(function() {
            $('#editCaseForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('subadmin.case.update', $case->id) }}`,
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
                            location.reload();
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

            $(document).ready(function() {
                // Handle Post 1 Edit Form Submission
                $('#editPost1Form').submit(function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    $.ajax({
                        url: '{{ route('subadmin.post.update') }}',
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#editPost1Modal').modal('hide');
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error: ', textStatus, errorThrown);
                            alert('An error occurred: ' + textStatus);
                        },
                    });
                });

                // Handle Post 2 Edit Form Submission
                $('#editPost2Form').submit(function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);

                    $.ajax({
                        url: '{{ route('subadmin.post.two.update') }}',
                        method: 'POST',
                        data: formData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        },
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                $('#editPost2Modal').modal('hide');
                                alert(response.message);
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                            }
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('AJAX Error: ', textStatus, errorThrown);
                            alert('An error occurred: ' + textStatus);
                        },
                    });
                });
            });


            $('#tpa_type').change(function() {
                if ($(this).val() === 'first') {
                    $('#tpa_allot_after_claim_no_received_two_container').show();
                } else {
                    $('#tpa_allot_after_claim_no_received_two_container').hide();
                }
            });

            if ($('#tpa_type').val() === 'first') {
                $('#tpa_allot_after_claim_no_received_two_container').show();
            }

        });

        $(document).ready(function() {
            $('#editCaseFileForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('subadmin.case.files.update', $case->id) }}`,
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
                            location.reload();
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
