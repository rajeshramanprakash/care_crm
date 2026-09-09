@extends('bill.layouts.app')
@section('title', 'Dashboard | Bill')
{{-- header code --}}
@section('header-css')
    <link rel="stylesheet" href="{{ asset('plugins/charts/chart.css') }}">
@endsection

@section('main')
    <div class="content-wrapper pb-5">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Bill Dashboard</h1>
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
                        <a  href="{{ route('bill.case.index', 'main_claim_cases') }}"
                            class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $main_claim_cases }}</h3>
                                    <p>Main Claim Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a  href="{{ route('bill.case.index', 'post_claim_cases') }}"
                            class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_claim_cases }}</h3>
                                    <p>Post One Claim Cases</p>
                                </div>
                                <div class="icon">
                                    <i class="ion ion-bag"></i>
                                </div>
                                <div class="small-box-footer">More info <i class="fas fa-arrow-circle-right"></i></div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a  href="{{ route('bill.case.index', 'post_two_claim_cases') }}"
                            class="text-light">
                            <div class="small-box text-sm bg-secondary">
                                <div class="inner">
                                    <h3>{{ $post_two_claim_cases }}</h3>
                                    <p>Post two Cases</p>
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
