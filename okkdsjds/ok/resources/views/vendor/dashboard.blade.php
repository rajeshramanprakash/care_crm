@extends('vendor.layouts.app')
@section('title', 'Dashboard | Vendor')
@section('header-css')
<link rel="stylesheet" href="{{ asset('plugins/charts/chart.css') }}">
<style>
    .bg-light-pink {
        background-color: #FFB6C1;
    }

    .bg-yellow {
        background-color: #FFFF00;
    }

    .bg-light-blue {
        background-color: #ADD8E6;
    }

    .bg-green {
        background-color: #00FF00;
    }

    .bg-red {
        background-color: #FF0000;
    }

    .bg-grey {
        background-color: #808080;
    }
</style>
@endsection
@section('navbar-right-links')
<li class="nav-item">
    <a href="#" class="btn btn-primary"
        style="padding: 3px 0; margin-top:8px; background-color: #ffffff50; border: 1px solid #ffffff80; border-radius: 30px; padding:0px 10px;"
        data-bs-toggle="modal" data-bs-target="#caseModal">
        Pending Activity: {{ $pending_activity_cases_count }}
    </a>
</li>
@endsection
@section('main')
<<div class="modal fade" id="caseModal" tabindex="-1" aria-labelledby="caseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="caseModalLabel">Case Details</h5>
                <button type="button" class="" data-bs-dismiss="modal" aria-label="Close"><i
                        class="fa fa-times"></i></button>
            </div>

            <div class="modal-body">
                <ul class="list-group">
                    @foreach ($casesData as $case)
                    <li class="list-group-item">
                        <div class="row">
                            <div class="col-2">
                                <strong>{{ $case['case_code'] }}</strong>
                            </div>
                            <div class="col-8">
                                {{ $case['desc'] }}
                            </div>
                            <div class="col-2">
                                <a href="{{route('vendor.case.notification_view')}}/{{ $case['case_code'] }}/{{ $case['type'] }}" class="btn btn-primary">View</a>
                            </div>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
    </div>


    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Dashboard</h1>
                    </div>
                </div>
            </div>
        </section>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Main Claim</h3>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases }}</h3>
                                    <p>Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_query') }}" class="text-light">
                            <div class="small-box text-sm bg-light-pink">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_query }}</h3>
                                    <p>Cases : Query</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_investigation') }}" class="text-light">
                            <div class="small-box text-sm bg-light-blue">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_investigation }}</h3>
                                    <p>Cases : Investigation</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_reject') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_reject }}</h3>
                                    <p>Cases : Reject</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_underprocess') }}" class="text-light">
                            <div class="small-box text-sm bg-yellow">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_underprocess }}</h3>
                                    <p>Cases : Under Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_approved') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_approved }}</h3>
                                    <p>Cases : Approved</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_inprocess') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_inprocess }}</h3>
                                    <p>Cases : Payment In Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_paid') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_paid }}</h3>
                                    <p>Cases : Paid</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_hold') }}" class="text-light">
                            <div class="small-box text-sm bg-danger">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_hold }}</h3>
                                    <p>Cases : Hold</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'main_claim_cases_cancelled') }}" class="text-light">
                            <div class="small-box text-sm bg-danger">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases_cancelled }}</h3>
                                    <p>Cases : Cancelled</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Post One Data</h3>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases }}</h3>
                                    <p>Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_query') }}" class="text-light">
                            <div class="small-box text-sm bg-light-pink">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_query }}</h3>
                                    <p>Cases : Query</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_investigation') }}" class="text-light">
                            <div class="small-box text-sm bg-light-blue">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_investigation }}</h3>
                                    <p>Cases : Investigation</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_reject') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_reject }}</h3>
                                    <p>Cases : Reject</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_underprocess') }}" class="text-light">
                            <div class="small-box text-sm bg-yellow">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_underprocess }}</h3>
                                    <p>Cases : Under Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_approved') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_approved }}</h3>
                                    <p>Cases : Approved</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_inprocess') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_inprocess }}</h3>
                                    <p>Cases : Payment In Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_claim_cases_paid') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases_paid }}</h3>
                                    <p>Cases : Paid</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12">
                        <h3>Post Two Data</h3>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases }}</h3>
                                    <p>Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_query') }}" class="text-light">
                            <div class="small-box text-sm bg-light-pink">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_query }}</h3>
                                    <p>Cases : Query</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_investigation') }}"
                            class="text-light">
                            <div class="small-box text-sm bg-light-blue">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_investigation }}</h3>
                                    <p>Cases : Investigation</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_reject') }}" class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_reject }}</h3>
                                    <p>Cases : Reject</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_underprocess') }}"
                            class="text-light">
                            <div class="small-box text-sm bg-yellow">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_underprocess }}</h3>
                                    <p>Cases : Under Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_approved') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_approved }}</h3>
                                    <p>Cases : Approved</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_inprocess') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_inprocess }}</h3>
                                    <p>Cases : Payment In Process</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="{{ route('vendor.case.index', 'post_two_claim_cases_paid') }}" class="text-light">
                            <div class="small-box text-sm bg-green">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases_paid }}</h3>
                                    <p>Cases : Paid</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- footer code --}}
    @section('footer-script')

    @endsection
    @endsection
