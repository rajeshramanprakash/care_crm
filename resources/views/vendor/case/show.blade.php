@extends('vendor.layouts.app')

@section('title', 'Case Details')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
@endsection

@section('main')

    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="card-header mb-3">
                    <div class="d-inline-block">
                        <button class="btn btn-primary btn-xs p-2 m-1"
                            onclick="handle_view_message(`{{ $query_status ?: 'N/A' }}`)">Case Status</button>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            @if ($case->pre_courier_no !== null || $case->pre_dispatch_pdf_attachment !== null)
                <div class="container-fluid">
                    <div class="card ">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title my-2">Should We Make The Post</h3>
                            <div class="float-right">
                                @if ($case->is_post_1 == 0)
                                    <div class="d-flex">
                                        <a href="{{ route('vendor.should_create_post_1') }}/{{ $case->id }}/1"
                                            class="btn btn-light mx-2">
                                            Yes
                                        </a>
                                        <a href="{{ route('vendor.should_create_post_1') }}/{{ $case->id }}/2"
                                            class="btn btn-danger">
                                            No
                                        </a>
                                    </div>
                                @elseif ($case->is_post_1 == 1)
                                    <div class="d-flex">
                                        <button class="btn btn-success">
                                            Created
                                        </button>
                                    </div>
                                @elseif ($case->is_post_1 == 2)
                                    <div class="d-flex">
                                        <button class="btn btn-danger">
                                            Rejected
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            @if ($case->post_courier_no !== null || $case->post_dispatch_pdf_attachment)
                <div class="container-fluid">
                    <div class="card ">
                        <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                            <h3 class="card-title my-2">Should We Make The Post 2</h3>
                            <div class="float-right">
                                <div class="d-flex">
                                    @if ($case->is_post_2 == 0)
                                        <div class="d-flex">
                                            <a href="{{ route('vendor.should_create_post_2') }}/{{ $case->id }}/1"
                                                class="btn btn-light mx-2">
                                                Yes
                                            </a>
                                            <a href="{{ route('vendor.should_create_post_2') }}/{{ $case->id }}/2"
                                                class="btn btn-danger">
                                                No
                                            </a>
                                        </div>
                                    @elseif ($case->is_post_2 == 1)
                                        <div class="d-flex">
                                            <button class="btn btn-success">
                                                Created
                                            </button>
                                        </div>
                                    @elseif ($case->is_post_2 == 2)
                                        <div class="d-flex">
                                            <button class="btn btn-danger">
                                                Rejected
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </section>
        <section class="content">
            <div class="container-fluid">
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
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Member Id: </span>
                                <span class="mx-1">{{ $case->member_id ?? 'N/A' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Corp: </span>
                                <span class="mx-1">{{ $case->corp ?? 'N/A' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Relation: </span>
                                <span class="mx-1">{{ $case->relation ?? 'N/A' }}</span>
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Aadhar Attachment: </span>
                                @if ($case->aadhar_attachment)
                                    <a href="{{ asset('storage/' . $case->aadhar_attachment) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
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
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Pan Card: </span>
                                @if ($case->pan_card)
                                    <a href="{{ asset('storage/' . $case->pan_card) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
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
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Policy: </span>
                                @if ($case->policy)
                                    <a href="{{ asset('storage/' . $case->policy) }}" target="_blank"
                                        class="text-primary">
                                        <i class="bi bi-file-earmark-text"></i> View
                                    </a>
                                @else
                                    <span class="text-muted">Not Available</span>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)"></span>

                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Main Claim No: </span>
                                @if ($case->claim_no_link)
                                    <a href="{{$case->claim_no_link}}" target="_blank" class="text-primary"> {{$case->claim_no}}</a>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Post Claim No: </span>
                                @if ($case->post_claim_no_link)
                                    <a href="{{$case->post_claim_no_link}}" target="_blank" class="text-primary"> {{$case->post_claim_no_link}}</a>
                                @endif
                            </div>
                            <div class="col-sm-6">
                                <span class="text-bold mx-1" style="color: var(--wb-wood)">Post Two Claim No: </span>
                                @if ($case->post_two_claim_no_link)
                                    <a href="{{$case->post_two_claim_no_link}}" target="_blank" class="text-primary">{{$case->post_two_claim_no_link}}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-5">
                    <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                        <h3 class="card-title">Queries</h3>
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
                                                        <a href="{{ asset('storage/' . $query->query_pdf) }}"
                                                            target="_blank" class="text-primary">
                                                            <i class="bi bi-file-earmark-text"></i> View
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
            </div>
        </section>
    </div>

@section('footer-script')
    <script>
        $(document).ready(function() {

        });
    </script>
@endsection

@endsection
