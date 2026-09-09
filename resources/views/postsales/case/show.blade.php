@extends('postsales.layouts.app')

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
                            <span class="mx-1"> {{ date('d-M-Y', strtotime($case->doa)) }} at
                                {{ date('h:i A', strtotime($case->doa_time)) }}</span>
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
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">IPD No: </span>
                            <span class="mx-1">{{ $case->ipd_no_entry }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier No: </span>
                            <span class="mx-1">{{ $case->pre_courier_no ?? 'N/A' }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier Date: </span>
                            <span class="mx-1">{{ $case->pre_courier_date }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Claim No: </span>
                            <span class="mx-1 ">{{ $case->claim_no }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Amount: </span>
                            <span class="mx-1 ">{{ $case->approved_amt }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Status: </span>
                            <span class="mx-1 ">{{ $case->status }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Paid Date: </span>
                            <span class="mx-1">
                                {{ $case->paid_date ? date('d-M-Y', strtotime($case->paid_date)) : 'NA' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Date: </span>
                            <span class="mx-1">
                                {{ $case->approved_date ? date('d-M-Y', strtotime($case->approved_date)) : 'NA' }}
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Patient Dtetails: </span>
                            @if ($case->patient_details_form)
                                <a href="{{ asset('storage/' . $case->patient_details_form) }}" target="_blank"
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

            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information Files</h3>
                    <button href="javascript:void(0);" class="btn p-0 text-light float-right" title="Edit Case files."
                        data-bs-toggle="modal" data-bs-target="#editCaseFileModal"><i class="fa fa-edit"></i></button>
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
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Queries</h3>
                    <button href="javascript:void(0);" class="btn p-0 text-light float-right" title="Add Query."
                        data-bs-toggle="modal" data-bs-target="#addQueryModal"><i class="fa fa-plus"></i></button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="serverTable" class="table mb-0" style="background-color: #e0eb3f5c">
                            <thead>
                                <tr>
                                    <th class="text-nowrap">S.No.</th>
                                    <th class="text-nowrap">Created At</th>
                                    <th class="">Query</th>
                                    <th class="text-nowrap">Query PDF</th>
                                </tr>
                            </thead>

                            <body>
                                @if (sizeof($case->get_guery) > 0)
                                    @foreach ($case->get_guery as $key => $query)
                                        <tr>
                                            <td>{{ $key + 1 }}</td>
                                            <td>{{ date('d-M-Y h:i a', strtotime($query->created_at)) }}</td>
                                            <td>
                                                <button class="btn"
                                                    onclick="handle_view_message(`{{ $query->query ?: 'N/A' }}`)"><i
                                                        class="fa fa-comment-dots"
                                                        style="color: var(--wb-renosand);"></i></button>
                                            </td>
                                            <td>
                                                @if ($query->query_pdf)
                                                    <a href="{{ asset('storage/' . $query->query_pdf) }}" target="_blank"
                                                        class="text-primary">
                                                        <i class="bi bi-file-earmark-text"></i> View
                                                    </a>
                                                    <a href="{{ asset('storage/' . $case->query_pdf) }}" download
                                                        class="btn btn-sm btn-primary m-1">
                                                        <i class="bi bi-download"></i> Download
                                                    </a>
                                                @else
                                                    <span class="text-muted">Not Available</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td class="text-center text-muted" colspan="5">No data available in
                                            table</td>
                                    </tr>
                                @endif
                            </body>
                        </table>
                    </div>
                </div>
            </div>

            @if ($case->is_post_1 == 1)
                <div class="card mb-3">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Post 1 Information</h3>
                        <button href="javascript:void(0);" class="btn p-0 text-light float-right"
                            title="Edit Post Case info." data-bs-toggle="modal" data-bs-target="#editCasePostModal"><i
                                class="fa fa-edit"></i></button>
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
                                    <a href="{{ asset('storage/' . $case->opd_attachment) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Bill Attachemnt: </span>
                                @if ($case->bill_attachment_post)
                                    <a href="{{ asset('storage/' . $case->bill_attachment_post) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->bill_attachment_post) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier No: </span>
                                <span class="mx-1">{{ $case->post_courier_no ?? 'N/A' }} </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier Date: </span>
                                <span class="mx-1">{{ $case->post_courier_date }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Claim No: </span>
                                <span class="mx-1 ">{{ $case->post_claim_no }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Amount: </span>
                                <span class="mx-1 ">{{ $case->post_ammount }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Status: </span>
                                <span class="mx-1 ">{{ $case->post_status }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Date: </span>
                                <span class="mx-1">
                                    {{ $case->post_approved_date ? date('d-M-Y', strtotime($case->post_approved_date)) : 'NA' }}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Paid Date: </span>
                                <span class="mx-1">
                                    {{ $case->post_paid_date ? date('d-M-Y', strtotime($case->post_paid_date)) : 'NA' }}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Patient Dtetails: </span>
                                @if ($case->post_patient_details_form)
                                    <a href="{{ asset('storage/' . $case->post_patient_details_form) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->post_patient_details_form) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Dispatch Pdf Attachment:
                                </span>
                                @if ($case->post_dispatch_pdf_attachment)
                                    <a href="{{ asset('storage/' . $case->post_dispatch_pdf_attachment) }}"
                                        target="_blank" class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->post_dispatch_pdf_attachment) }}" download
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
            @endif

            @if ($case->is_post_2 == 1)
                <div class="card mb-3">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Post 2 Information</h3>
                        <button href="javascript:void(0);" class="btn p-0 text-light float-right"
                            title="Edit Post Case info." data-bs-toggle="modal" data-bs-target="#editCasePostTwoModal"><i
                                class="fa fa-edit"></i></button>
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
                                    <a href="{{ asset('storage/' . $case->opd_attachment_2) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Bill Attachemnt: </span>
                                @if ($case->bill_attachment_post_two)
                                    <a href="{{ asset('storage/' . $case->bill_attachment_post_two) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->bill_attachment_post_two) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier No: </span>
                                <span class="mx-1">{{ $case->post_two_courier_no ?? 'N/A' }} </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Courier Date: </span>
                                <span class="mx-1">{{ $case->post_two_courier_date }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Claim No: </span>
                                <span class="mx-1 ">{{ $case->post_two_claim_no }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Amount: </span>
                                <span class="mx-1 ">{{ $case->post_two_ammount }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Status: </span>
                                <span class="mx-1 ">{{ $case->post_two_status }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Date: </span>
                                <span class="mx-1">
                                    {{ $case->post_two_approved_date ? date('d-M-Y', strtotime($case->post_two_approved_date)) : 'NA' }}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Paid Date: </span>
                                <span class="mx-1">
                                    {{ $case->post_two_paid_date ? date('d-M-Y', strtotime($case->post_two_paid_date)) : 'NA' }}
                                </span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Patient Dtetails: </span>
                                @if ($case->post_two_patient_details_form)
                                    <a href="{{ asset('storage/' . $case->post_two_patient_details_form) }}"
                                        target="_blank" class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->post_two_patient_details_form) }}" download
                                        class="btn btn-sm btn-primary m-1">
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Dispatch Pdf Attachment:
                                </span>
                                @if ($case->post_two_dispatch_pdf_attachment)
                                    <a href="{{ asset('storage/' . $case->post_two_dispatch_pdf_attachment) }}"
                                        target="_blank" class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                    <a href="{{ asset('storage/' . $case->post_two_dispatch_pdf_attachment) }}" download
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
            @endif
        </section>

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
                                <label for="status">Status</label>
                                <select class="form-control" name="status" id="status" required>
                                    <option value="select">Select</option>
                                    <option value="Query" {{ $case->status == 'Query' ? 'selected' : '' }}>Query</option>
                                    <option value="Investigation"
                                        {{ $case->status == 'Investigation' ? 'selected' : '' }}>Investigation
                                    </option>
                                    <option value="Reject" {{ $case->status == 'Reject' ? 'selected' : '' }}>Reject
                                    </option>
                                    <option value="UnderProcess" {{ $case->status == 'UnderProcess' ? 'selected' : '' }}>
                                        UnderProcess</option>
                                    <option value="Approved" {{ $case->status == 'Approved' ? 'selected' : '' }}>Approved
                                    </option>
                                    <option value="InProcess" {{ $case->status == 'InProcess' ? 'selected' : '' }}>In Process</option>
                                    <option value="Paid" {{ $case->status == 'Paid' ? 'selected' : '' }}>Paid</option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="approved_amt">Approved Amount</label>
                                <input type="text" class="form-control" name="approved_amt" id="approved_amt"
                                    value="{{ $case->approved_amt }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="claim_no">Claim No</label>
                                <input type="text" class="form-control" name="claim_no" id="claim_no"
                                    value="{{ $case->claim_no }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="paid_date">Paid Date</label>
                                <input type="date" class="form-control" name="paid_date" id="paid_date"
                                    value="{{ $case->paid_date }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="approved_date">Approved Date</label>
                                <input type="date" class="form-control" name="approved_date" id="approved_date"
                                    value="{{ $case->approved_date }}">
                            </div>
                            <div class="form-group col-sm-12 col-lg-6">
                                <label for="patient_details_form">Patient Dtetails</label>
                                <input type="file" class="form-control" name="patient_details_form"
                                    id="patient_details_form">
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
                <form id="editCasePostModalForm" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCasePostModalLabel">Add Files</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="post_status">Status</label>
                                <select class="form-control" name="post_status" id="post_status" required>
                                    <option value="select">Select</option>
                                    <option value="Query" {{ $case->post_status == 'Query' ? 'selected' : '' }}>Query
                                    </option>
                                    <option value="Investigation"
                                        {{ $case->post_status == 'Investigation' ? 'selected' : '' }}>Investigation
                                    </option>
                                    <option value="Reject" {{ $case->post_status == 'Reject' ? 'selected' : '' }}>Reject
                                    </option>
                                    <option value="UnderProcess"
                                        {{ $case->post_status == 'UnderProcess' ? 'selected' : '' }}>UnderProcess</option>
                                    <option value="Approved" {{ $case->post_status == 'Approved' ? 'selected' : '' }}>
                                        Approved</option>
                                        <option value="InProcess" {{ $case->post_status == 'InProcess' ? 'selected' : '' }}>In Process</option>
                                    <option value="Paid" {{ $case->post_status == 'Paid' ? 'selected' : '' }}>Paid
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="post_ammount">Approved Amount</label>
                                <input type="text" class="form-control" name="post_ammount" id="post_ammount"
                                    value="{{ $case->post_ammount }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_claim_no">Claim No</label>
                                <input type="text" class="form-control" name="post_claim_no" id="post_claim_no"
                                    value="{{ $case->post_claim_no }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_paid_date">Paid Date</label>
                                <input type="date" class="form-control" name="post_paid_date" id="post_paid_date"
                                    value="{{ $case->post_paid_date }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_approved_date">Approved Date</label>
                                <input type="date" class="form-control" name="post_approved_date" id="post_approved_date"
                                    value="{{ $case->post_approved_date }}">
                            </div>
                            <div class="form-group col-sm-12 col-lg-6">
                                <label for="post_patient_details_form">Patient Dtetails</label>
                                <input type="file" class="form-control" name="post_patient_details_form"
                                    id="post_patient_details_form">
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
                <form id="editCasePostTwoModalForm" enctype="multipart/form-data">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editCasePostTwoModalLabel">Add Files</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="post_two_status">Status</label>
                                <select class="form-control" name="post_two_status" id="post_two_status" required>
                                    <option value="select">Select</option>
                                    <option value="Query" {{ $case->post_two_status == 'Query' ? 'selected' : '' }}>Query
                                    </option>
                                    <option value="Investigation"
                                        {{ $case->post_two_status == 'Investigation' ? 'selected' : '' }}>Investigation
                                    </option>
                                    <option value="Reject" {{ $case->post_two_status == 'Reject' ? 'selected' : '' }}>
                                        Reject</option>
                                    <option value="UnderProcess"
                                        {{ $case->post_two_status == 'UnderProcess' ? 'selected' : '' }}>UnderProcess
                                    </option>
                                    <option value="Approved" {{ $case->post_two_status == 'Approved' ? 'selected' : '' }}>
                                        Approved</option>
                                        <option value="InProcess" {{ $case->post_two_status == 'InProcess' ? 'selected' : '' }}>In Process</option>

                                    <option value="Paid" {{ $case->post_two_status == 'Paid' ? 'selected' : '' }}>Paid
                                    </option>
                                </select>
                            </div>
                            <div class="form-group col-lg-6 col-sm-12">
                                <label for="post_two_ammount">Approved Amount</label>
                                <input type="text" class="form-control" name="post_two_ammount" id="post_two_ammount"
                                    value="{{ $case->post_two_ammount }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_two_claim_no">Claim No</label>
                                <input type="text" class="form-control" name="post_two_claim_no"
                                    id="post_two_claim_no" value="{{ $case->post_two_claim_no }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_two_paid_date">Paid Date</label>
                                <input type="date" class="form-control" name="post_two_paid_date" id="post_two_paid_date"
                                    value="{{ $case->post_two_paid_date }}">
                            </div>
                            <div class="form-group col-lg-6 col-sm-6">
                                <label for="post_two_approved_date">Approved Date</label>
                                <input type="date" class="form-control" name="post_two_approved_date" id="post_two_approved_date"
                                    value="{{ $case->post_two_approved_date }}">
                            </div>
                            <div class="form-group col-sm-12 col-lg-6">
                                <label for="post_two_patient_details_form">Patient Dtetails</label>
                                <input type="file" class="form-control" name="post_two_patient_details_form"
                                    id="post_two_patient_details_form">
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
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade" id="addQueryModal" tabindex="-1" aria-labelledby="addQueryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="addQueryForm" action="{{ route('postsales.query.add') }}" method="POST"
                    enctype="multipart/form-data">
                    <div class="modal-content">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="addQueryModalLabel">Edit Case</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body row">
                            <input type="hidden" name="case_id" value="{{ $case->id }}">
                            <div class="form-group col-lg-12 col-sm-12">
                                <label for="query">Query</label>
                                <textarea name="query" class="form-control"></textarea>
                            </div>
                            <div class="form-group col-sm-12 col-lg-12">
                                <label for="query_pdf">Query PDF</label>
                                <input type="file" class="form-control" name="query_pdf" id="query_pdf">
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
                    url: `{{ route('postsales.case.update', $case->id) }}`,
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
            $('#editCasePostModalForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('postsales.case.update.post_one', $case->id) }}`,
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
                            window.location.href = `{{ route('postsales.case.index') }}`;
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
            $('#editCasePostTwoModalForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                $.ajax({
                    url: `{{ route('postsales.case.update.post_two', $case->id) }}`,
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
                            window.location.href = `{{ route('postsales.case.index') }}`;
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

        $(document).ready(function() {
            $('#editCaseFileForm').submit(function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                $.ajax({
                    url: `{{ route('postsales.case.files.update', $case->id) }}`,
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
